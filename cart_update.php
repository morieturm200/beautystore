<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['ajax'])) {
        http_response_code(401);
        echo "unauthorized";
        exit();
    }
    header("Location: login_register.php?msg=login_required");
    exit();
}


$conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");
if ($conn->connect_error) { die("Помилка бази даних"); }
$conn->set_charset("utf8mb4");

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'plus';
$user_id = intval($_SESSION['user_id']);
$total_items = 0;

if ($product_id > 0) {

    $stmt_order = $conn->prepare("SELECT order_id FROM orders WHERE user_id = ? AND status = 'accepted' ORDER BY order_id DESC LIMIT 1");
    $stmt_order->bind_param("i", $user_id);
    $stmt_order->execute();
    $res_order = $stmt_order->get_result();

    if ($res_order->num_rows > 0) {
        $order_id = $res_order->fetch_assoc()['order_id'];
    } else {

        $stmt_new_order = $conn->prepare("INSERT INTO orders (user_id, status, order_date) VALUES (?, 'accepted', NOW())");
        $stmt_new_order->bind_param("i", $user_id);
        $stmt_new_order->execute();
        $order_id = $conn->insert_id;
    }

    if ($action === 'plus') {
  
        $check_stmt = $conn->prepare("SELECT order_details_id, quantity FROM Order_Details WHERE order_id = ? AND product_id = ? AND status = 'cart'");
        $check_stmt->bind_param("ii", $order_id, $product_id);
        $check_stmt->execute();
        $res_item = $check_stmt->get_result();

        if ($res_item->num_rows > 0) {
            $row = $res_item->fetch_assoc();
            $stmt = $conn->prepare("UPDATE Order_Details SET quantity = quantity + 1 WHERE order_details_id = ?");
            $stmt->bind_param("i", $row['order_details_id']);
            $stmt->execute();
        } else {

            $price_stmt = $conn->prepare("SELECT price FROM product WHERE product_id = ?");
            $price_stmt->bind_param("i", $product_id);
            $price_stmt->execute();
            $price = $price_stmt->get_result()->fetch_assoc()['price'] ?? 0;

            $stmt = $conn->prepare("INSERT INTO Order_Details (order_id, product_id, quantity, unit_price, status) VALUES (?, ?, 1, ?, 'cart')");
            $stmt->bind_param("iid", $order_id, $product_id, $price);
            $stmt->execute();
        }
    } 
    elseif ($action === 'minus') {
        $check_stmt = $conn->prepare("SELECT order_details_id, quantity FROM Order_Details WHERE order_id = ? AND product_id = ? AND status = 'cart'");
        $check_stmt->bind_param("ii", $order_id, $product_id);
        $check_stmt->execute();
        $res_item = $check_stmt->get_result();

        if ($row = $res_item->fetch_assoc()) {
            if ($row['quantity'] > 1) {
                $stmt = $conn->prepare("UPDATE Order_Details SET quantity = quantity - 1 WHERE order_details_id = ?");
                $stmt->bind_param("i", $row['order_details_id']);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare("DELETE FROM Order_Details WHERE order_details_id = ?");
                $stmt->bind_param("i", $row['order_details_id']);
                $stmt->execute();
            }
        }
    } 
    elseif ($action === 'remove') {
        $stmt = $conn->prepare("DELETE FROM Order_Details WHERE order_id = ? AND product_id = ? AND status = 'cart'");
        $stmt->bind_param("ii", $order_id, $product_id);
        $stmt->execute();
    }
    

    $count_stmt = $conn->prepare("SELECT SUM(quantity) as total FROM Order_Details WHERE order_id = ? AND status = 'cart'");
    $count_stmt->bind_param("i", $order_id);
    $count_stmt->execute();
    $total_items = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
}


if (isset($_GET['ajax'])) {

    echo $total_items;
    exit(); 
}

$referer = $_SERVER['HTTP_REFERER'] ?? 'cart.php';
header("Location: " . $referer);
exit();
?>
