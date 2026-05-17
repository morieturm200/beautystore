<?php
session_start();
header('Content-Type: application/json');


if (!isset($_SESSION['user_id'])) {

    echo json_encode(['status' => 'error', 'message' => 'auth_required']);
    exit();
}


$conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'database_error']);
    exit();
}
$conn->set_charset("utf8mb4");


$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$user_id = intval($_SESSION['user_id']);

if ($product_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'invalid_id']);
    exit();
}


$stmt = $conn->prepare("SELECT wishlist_id FROM Wishlist WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {

    $action_stmt = $conn->prepare("DELETE FROM Wishlist WHERE user_id = ? AND product_id = ?");
    $status = 'removed';
} else {

    $check_prod = $conn->prepare("SELECT product_id FROM product WHERE product_id = ?");
    $check_prod->bind_param("i", $product_id);
    $check_prod->execute();
    if ($check_prod->get_result()->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'product_not_found']);
        exit();
    }


    $action_stmt = $conn->prepare("INSERT INTO Wishlist (user_id, product_id) VALUES (?, ?)");
    $status = 'added';
}

$action_stmt->bind_param("ii", $user_id, $product_id);
$action_stmt->execute();


$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM Wishlist WHERE user_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];


echo json_encode([
    'status' => $status, 
    'count' => $total
]);
exit();
