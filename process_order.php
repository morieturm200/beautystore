<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");

if ($conn->connect_error) {
    die("Помилка підключення: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_SESSION['cart'])) {
    
    if (isset($_SESSION['customer_id'])) {
        $customer_id = $_SESSION['customer_id'];
    } else {

        $fname = $conn->real_escape_string($_POST['fname'] ?? 'Гість');
        $lname = $conn->real_escape_string($_POST['lname'] ?? 'Beauty');
        $phone = $conn->real_escape_string($_POST['phone'] ?? '000');
        $address = $conn->real_escape_string($_POST['address'] ?? 'Самовивіз');

        $timestamp = time();
        $username = "guest_" . $timestamp;
        $email = "order_" . $timestamp . "@beauty.ua";
        $password = password_hash("12345", PASSWORD_DEFAULT);

        $sql_cust = "INSERT INTO customer (customer_username, customer_password, first_name, last_name, email, address, phone_number, gender, birthdate) 
                     VALUES ('$username', '$password', '$fname', '$lname', '$email', '$address', '$phone', 'Інше', '2000-01-01')";
        
        if ($conn->query($sql_cust)) {
            $customer_id = $conn->insert_id;
            $_SESSION['customer_id'] = $customer_id;
            $_SESSION['customer_name'] = $fname; 
        } else {
            die("Помилка створення гостя: " . $conn->error);
        }
    }

    $final_sum = 0;
    $order_items = [];

    foreach ($_SESSION['cart'] as $p_id => $qty) {
        $p_id = intval($p_id);
        $qty = intval($qty);
        
        $res = $conn->query("SELECT price, sale_price, is_sale FROM product WHERE product_id = $p_id");
        if ($row = $res->fetch_assoc()) {
            $current_unit_price = ($row['is_sale'] && $row['sale_price'] > 0) ? $row['sale_price'] : $row['price'];
            
            $final_sum += $current_unit_price * $qty;
            
            
            $order_items[] = [
                'id' => $p_id,
                'qty' => $qty,
                'price' => $current_unit_price
            ];
        }
    }

    
    $sql_order = "INSERT INTO orders (customer_id, order_date, total_price, status) 
                  VALUES ($customer_id, NOW(), $final_sum, 'Нове')";

    if ($conn->query($sql_order)) {
        $order_id = $conn->insert_id;

        
        foreach ($order_items as $item) {
            $p_id = $item['id'];
            $qty = $item['qty'];
            $u_price = $item['price'];
            
            $sql_details = "INSERT INTO Order_Details (order_id, product_id, quantity, unit_price) 
                            VALUES ($order_id, $p_id, $qty, $u_price)";
            $conn->query($sql_details);
        }

        
        unset($_SESSION['cart']);

        
        header("Location: success.php?id=" . $order_id);
        exit();
    } else {
        echo "Помилка в таблиці Orders: " . $conn->error;
    }

} else {
    echo "Кошик порожній або форма не відправлена.";
}
?>