<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");
if ($conn->connect_error) { die("Помилка підключення: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cart.php");
    exit();
}


if (!isset($_SESSION['user_id'])) {
    header("Location: login_register.php");
    exit();
}
$user_id = intval($_SESSION['user_id']);


$region  = trim($_POST['region']  ?? '');
$city    = trim($_POST['city']    ?? '');
$address = trim($_POST['address'] ?? '');
$phone   = trim($_POST['phone']   ?? '');
$payment = trim($_POST['payment'] ?? 'cash');
$cashback_to_use = floatval($_POST['cashback_to_use'] ?? 0);


if (isset($_SESSION['checkout_processing']) && $_SESSION['checkout_processing'] === true) {
    header("Location: cart.php");
    exit();
}
$_SESSION['checkout_processing'] = true;


$conn->begin_transaction();

try {

    $stmt_find = $conn->prepare("
        SELECT od.order_id
        FROM Order_Details od
        JOIN orders o ON od.order_id = o.order_id
        WHERE o.user_id = ?
          AND od.status = 'cart'
        ORDER BY od.order_id DESC
        LIMIT 1
    ");
    $stmt_find->bind_param("i", $user_id);
    $stmt_find->execute();
    $order_res = $stmt_find->get_result();

    if ($order_res->num_rows === 0) {
        throw new Exception('Кошик не знайдено або у ньому немає товарів для оформлення!');
    }
    $order_id = $order_res->fetch_assoc()['order_id'];

    $stmt_check = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM Order_Details
        WHERE order_id = ? AND status = 'cart'
    ");
    $stmt_check->bind_param("i", $order_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->fetch_assoc()['cnt'] == 0) {
        throw new Exception('Ваш кошик порожній!');
    }


    $stmt_bal = $conn->prepare("SELECT discount FROM users WHERE user_id = ?");
    $stmt_bal->bind_param("i", $user_id);
    $stmt_bal->execute();
    $current_balance = floatval($stmt_bal->get_result()->fetch_assoc()['discount'] ?? 0);

    if ($cashback_to_use > $current_balance) {
        $cashback_to_use = $current_balance;
    }
    $cashback_safe = floatval($cashback_to_use);


    $invoice_no = 'INV-' . time() . '-' . rand(100, 999);


    $stmt_update = $conn->prepare("
        UPDATE orders SET
            status          = 'processing',
            invoice_no      = ?,
            delivery_region = ?,
            delivery_city   = ?,
            delivery_street = ?,
            delivery_phone  = ?,
            payment_method  = ?,
            cashback_spent  = ?,
            order_date      = NOW()
        WHERE order_id = ?
    ");
    $stmt_update->bind_param("ssssssdi",
        $invoice_no, $region, $city, $address,
        $phone, $payment, $cashback_safe, $order_id
    );
    $stmt_update->execute();


    $stmt_fix = $conn->prepare("
        UPDATE Order_Details od
        JOIN product p ON od.product_id = p.product_id
        SET od.status     = 'ordered',
            od.unit_price = p.price
        WHERE od.order_id = ?
          AND od.status   = 'cart'
    ");
    $stmt_fix->bind_param("i", $order_id);
    $stmt_fix->execute();


    $stmt_items = $conn->prepare("
        SELECT product_id, quantity
        FROM Order_Details
        WHERE order_id = ? AND status = 'ordered'
    ");
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $items = $stmt_items->get_result();

    $stmt_stock = $conn->prepare("
        UPDATE product
        SET stock = GREATEST(0, stock - ?)
        WHERE product_id = ?
    ");
    while ($item = $items->fetch_assoc()) {
        $stmt_stock->bind_param("ii", $item['quantity'], $item['product_id']);
        $stmt_stock->execute();
    }


    if ($cashback_safe > 0) {
        $stmt_minus_cb = $conn->prepare("UPDATE users SET discount = GREATEST(0, discount - ?) WHERE user_id = ?");
        $stmt_minus_cb->bind_param("di", $cashback_safe, $user_id);
        $stmt_minus_cb->execute();
    }

    $conn->commit();


    unset($_SESSION['checkout_processing']);


    header("Location: success.php?id=" . $order_id);
    exit();

} catch (Exception $e) {
    $conn->rollback();
    unset($_SESSION['checkout_processing']);
    echo "<script>alert('Помилка: " . addslashes($e->getMessage()) . "'); window.location.href='cart.php';</script>";
    exit();
}
?>
