<?php
session_start();

// 1. ПЕРЕВІРКА АВТОРИЗАЦІЇ
if (!isset($_SESSION['customer_id'])) {
    // Якщо це AJAX запит, відправляємо JSON з помилкою, щоб JS знав, куди редиректити
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'redirect' => 'login_register.php?msg=login_required']);
        exit();
    }
    header("Location: login_register.php?msg=login_required");
    exit();
}

// 2. ПІДКЛЮЧЕННЯ
$conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");
if ($conn->connect_error) {
    die("Помилка бази даних");
}
$conn->set_charset("utf8mb4");

// 3. ОТРИМУЄМО ДАНІ
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$customer_id = intval($_SESSION['customer_id']);

if ($product_id > 0) {
    // --- ЛОГІКА: БАЗА ДАНИХ ---
    $sql_db = "INSERT INTO Cart (customer_id, product_id, quantity) 
               VALUES (?, ?, 1) 
               ON DUPLICATE KEY UPDATE quantity = quantity + 1";
    
    $stmt = $conn->prepare($sql_db);
    $stmt->bind_param("ii", $customer_id, $product_id);
    $stmt->execute();

    // --- ЛОГІКА: СЕСІЯ ---
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]++;
    } else {
        $_SESSION['cart'][$product_id] = 1;
    }
}

// --- КРИТИЧНЕ ВИПРАВЛЕННЯ ДЛЯ ПЛАВНОСТІ (AJAX) ---
if (isset($_GET['ajax'])) {
    // Якщо запит прийшов від JS, ми просто кажемо "успіх" і НЕ перезавантажуємо сторінку
    echo "success";
    exit(); 
}

// 4. ПЕРЕНАПРАВЛЕННЯ (Для звичайних натискань, якщо AJAX не спрацював)
$redirect = $_GET['redirect'] ?? '';

if ($redirect === 'cart') {
    header("Location: cart.php");
} else {
    $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    $url_parts = parse_url($referer);
    $path = $url_parts['path'] ?? 'index.php';
    
    $params = [];
    if (isset($url_parts['query'])) {
        parse_str($url_parts['query'], $params);
    }
    $params['cart'] = 'success';
    
    $new_url = $path . '?' . http_build_query($params);
    header("Location: " . $new_url);
}
exit();