<?php
session_start();


if (!isset($_SESSION['customer_id'])) {
    
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'redirect' => 'login_register.php?msg=login_required']);
        exit();
    }
    header("Location: login_register.php?msg=login_required");
    exit();
}


$conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");
if ($conn->connect_error) {
    die("Помилка бази даних");
}
$conn->set_charset("utf8mb4");


$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$customer_id = intval($_SESSION['customer_id']);

if ($product_id > 0) {
   
    $sql_db = "INSERT INTO Cart (customer_id, product_id, quantity) 
               VALUES (?, ?, 1) 
               ON DUPLICATE KEY UPDATE quantity = quantity + 1";
    
    $stmt = $conn->prepare($sql_db);
    $stmt->bind_param("ii", $customer_id, $product_id);
    $stmt->execute();

    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]++;
    } else {
        $_SESSION['cart'][$product_id] = 1;
    }
}


if (isset($_GET['ajax'])) {
    
    echo "success";
    exit(); 
}


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