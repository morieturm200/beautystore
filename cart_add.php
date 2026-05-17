<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        http_response_code(401);
        echo "unauthorized";
        exit();
    }
    header("Location: login_register.php?msg=login_required");
    exit();
}

$conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");
if ($conn->connect_error) { 
    die("Помилка підключення: " . $conn->connect_error); 
}
$conn->set_charset("utf8mb4");

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = intval($_SESSION['user_id']);

if ($product_id > 0) {

    $stmt_order = $conn->prepare("SELECT order_id FROM orders WHERE user_id = ? AND status = 'accepted' ORDER BY order_id DESC LIMIT 1");
    $stmt_order->bind_param("i", $user_id);
    $stmt_order->execute();
    $res_order = $stmt_order->get_result();

    if ($res_order->num_rows > 0) {
        $order_id = $res_order->fetch_assoc()['order_id'];
    } else {
        $invoice_no = 'INV-' . time() . '-' . rand(100, 999);
        $stmt_new_order = $conn->prepare("INSERT INTO orders (invoice_no, user_id, status, order_date) VALUES (?, ?, 'accepted', NOW())");
        $stmt_new_order->bind_param("si", $invoice_no, $user_id);
        $stmt_new_order->execute();
        $order_id = $conn->insert_id;
    }


    $price_stmt = $conn->prepare("SELECT price FROM product WHERE product_id = ?");
    $price_stmt->bind_param("i", $product_id);
    $price_stmt->execute();
    $unit_price = $price_stmt->get_result()->fetch_assoc()['price'] ?? 0;

    $check_stmt = $conn->prepare("SELECT order_details_id FROM Order_Details WHERE order_id = ? AND product_id = ? AND status = 'cart'");
    $check_stmt->bind_param("ii", $order_id, $product_id);
    $check_stmt->execute();
    $res_item = $check_stmt->get_result();

    if ($res_item->num_rows > 0) {
        $item_data = $res_item->fetch_assoc();
        $conn->query("UPDATE Order_Details SET quantity = quantity + 1 WHERE order_details_id = " . $item_data['order_details_id']);
    } else {
        $insert_stmt = $conn->prepare("INSERT INTO Order_Details (order_id, product_id, quantity, unit_price, status) VALUES (?, ?, 1, ?, 'cart')");
        $insert_stmt->bind_param("iid", $order_id, $product_id, $unit_price);
        $insert_stmt->execute();
    }
    

    $count_res = $conn->query("SELECT SUM(quantity) as total FROM Order_Details WHERE order_id = $order_id AND status = 'cart'");
    $total = $count_res->fetch_assoc()['total'] ?? 0;


    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        echo $total;
        exit(); 
    }
}

$referer = $_SERVER['HTTP_REFERER'] ?? 'cart.php';
header("Location: " . $referer);
exit();
?>
