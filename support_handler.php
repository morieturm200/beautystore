<?php
session_start();


if (!isset($_SESSION['user_id']) || empty($_POST['message'])) {
    exit('error');
}

$host = "localhost";
$db   = "beautystore";
$user = "beautyuser";
$pass = "1234";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    

    $c_id = intval($_SESSION['user_id']);
    $msg = trim($_POST['message']);
    $subject = "Питання з кабінету"; 

    $sql = "INSERT INTO Support (user_id, subject, message, status) 
            VALUES (?, ?, ?, 'new')";
    
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$c_id, $subject, $msg])) {
        echo 'success';
    } else {
        echo 'error';
    }

} catch (Exception $e) {
    echo 'error: ' . $e->getMessage();
}
