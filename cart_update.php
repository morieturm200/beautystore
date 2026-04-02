<?php
session_start();


$host = "localhost";
$db   = "beautystore";
$user = "beautyuser";
$pass = "1234";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Помилка підключення");
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    
    if (isset($_SESSION['customer_id'])) {
        $c_id = $_SESSION['customer_id'];

        if ($action == 'plus') {
            
            $pdo->prepare("UPDATE Cart SET quantity = quantity + 1 WHERE customer_id = ? AND product_id = ?")->execute([$c_id, $id]);
            
            $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
        } 
        elseif ($action == 'minus') {
            
            $pdo->prepare("UPDATE Cart SET quantity = quantity - 1 WHERE customer_id = ? AND product_id = ? AND quantity > 1")->execute([$c_id, $id]);
            
            
            $check = $pdo->prepare("SELECT quantity FROM Cart WHERE customer_id = ? AND product_id = ?");
            $check->execute([$c_id, $id]);
            $row = $check->fetch();
            
            if ($row && $row['quantity'] > 0) {
                $_SESSION['cart'][$id] = $row['quantity'];
            } else {
               
                $pdo->prepare("DELETE FROM Cart WHERE customer_id = ? AND product_id = ?")->execute([$c_id, $id]);
                unset($_SESSION['cart'][$id]);
            }
        }
        elseif ($action == 'remove') {
            $pdo->prepare("DELETE FROM Cart WHERE customer_id = ? AND product_id = ?")->execute([$c_id, $id]);
            unset($_SESSION['cart'][$id]);
        }
    } 
    
    else {
        if (isset($_SESSION['cart'][$id])) {
            if ($action == 'plus') $_SESSION['cart'][$id]++;
            elseif ($action == 'minus') {
                $_SESSION['cart'][$id]--;
                if ($_SESSION['cart'][$id] <= 0) unset($_SESSION['cart'][$id]);
            }
            elseif ($action == 'remove') unset($_SESSION['cart'][$id]);
        }
    }
}

header("Location: cart.php");
exit();