<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

try {

    $pdo = new PDO("mysql:host=localhost;dbname=beautystore;charset=utf8mb4", "beautyuser", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(Exception $e) {
    die("❌ Помилка підключення: " . $e->getMessage());
}

if (isset($_POST['register_btn'])) {
    

    $un = trim($_POST['customer_username'] ?? '');
    $em = trim($_POST['email'] ?? '');
    $pass_input = $_POST['customer_password'] ?? '';
    

    if (empty($un) || empty($em) || empty($pass_input)) {
        die("<script>alert('Помилка: Логін, Email та Пароль є обов\'язковими!'); window.history.back();</script>");
    }
    
  
    $check_sql = "SELECT username, email FROM users WHERE username = ? OR email = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$un, $em]);
    $existing_user = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_user) {
        if ($existing_user['username'] === $un) {
            die("<script>alert('Помилка: Користувач з таким логіном вже існує!'); window.history.back();</script>");
        }
        if ($existing_user['email'] === $em) {
            die("<script>alert('Помилка: Ця електронна адреса вже зареєстрована!'); window.history.back();</script>");
        }
    }


    $pw = password_hash($pass_input, PASSWORD_DEFAULT);
    

    $fn = !empty($_POST['first_name']) ? trim($_POST['first_name']) : null;
    $ln = !empty($_POST['last_name']) ? trim($_POST['last_name']) : null;

    try {
    
        $sql = "INSERT INTO users (username, password, first_name, last_name, email, role) 
                VALUES (?, ?, ?, ?, ?, 'customer')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$un, $pw, $fn, $ln, $em]);

  
        echo "<script>alert('Вітаємо! Реєстрація успішна!'); window.location.href='login_register.php';</script>";
        exit();

    } catch (PDOException $e) {
        die("❌ ПОМИЛКА БАЗИ: " . $e->getMessage());
    }
}
?>
