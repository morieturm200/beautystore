<?php
session_start();
header('Content-Type: application/json');

// 1. ЖОРСТКА ПЕРЕВІРКА АВТОРИЗАЦІЇ
if (!isset($_SESSION['customer_id'])) {
    // Якщо гість — віддаємо статус error для JS-редиректу
    echo json_encode(['status' => 'error', 'message' => 'auth_required']);
    exit();
}

// 2. ПІДКЛЮЧЕННЯ
$conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'database_error']);
    exit();
}
$conn->set_charset("utf8mb4");

// 3. ПЕРЕВІРКА ВХІДНИХ ДАНИХ
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$customer_id = intval($_SESSION['customer_id']);

if ($product_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'invalid_id']);
    exit();
}

// 4. ЛОГІКА TOGGLE (ДОДАТИ АБО ВИДАЛИТИ)
// Перевіряємо, чи вже є такий запис
$stmt = $conn->prepare("SELECT wishlist_id FROM Wishlist WHERE customer_id = ? AND product_id = ?");
$stmt->bind_param("ii", $customer_id, $product_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    // Товар є — видаляємо
    $action_stmt = $conn->prepare("DELETE FROM Wishlist WHERE customer_id = ? AND product_id = ?");
    $status = 'removed';
} else {
    // Товару немає — перевіряємо чи такий товар взагалі існує в базі (безпека)
    $check_prod = $conn->prepare("SELECT product_id FROM product WHERE product_id = ?");
    $check_prod->bind_param("i", $product_id);
    $check_prod->execute();
    if ($check_prod->get_result()->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'product_not_found']);
        exit();
    }

    // Додаємо
    $action_stmt = $conn->prepare("INSERT INTO Wishlist (customer_id, product_id) VALUES (?, ?)");
    $status = 'added';
}

$action_stmt->bind_param("ii", $customer_id, $product_id);
$action_stmt->execute();

// 5. ОНОВЛЕННЯ ЛІЧИЛЬНИКА 
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM Wishlist WHERE customer_id = ?");
$count_stmt->bind_param("i", $customer_id);
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];

// 6. ВІДПОВІДЬ ДЛЯ JS
echo json_encode([
    'status' => $status, 
    'count' => $total
]);
exit();