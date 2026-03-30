<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Твоє підключення (перевір дані!)
try {
    $pdo = new PDO("mysql:host=localhost;dbname=beautystore;charset=utf8", "beautyuser", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(Exception $e) {
    die("❌ Помилка підключення: " . $e->getMessage());
}

if (isset($_POST['register_btn'])) {
    // 1. Отримуємо те, що прийшло з форми
    $un = $_POST['customer_username'] ?? 'user_' . time();
    $pw = password_hash($_POST['customer_password'], PASSWORD_DEFAULT);
    $fn = $_POST['first_name'] ?? 'Ім\'я';
    $ln = $_POST['last_name'] ?? 'Прізвище';
    $em = $_POST['email'] ?? '';

    // 2. ЗАГЛУШКИ для полів NOT NULL (яких немає у формі)
    // Якщо ти їх не передаси - база видасть помилку
    $ad = "Не вказана адреса"; 
    $gn = "Жінка"; // або 'Чоловік' / 'Інше' - як в ENUM
    $bd = "2000-01-01";
    $ph = "0000000000000";

    try {
        // 3. ЗАПИТ, який точно відповідає структурі таблиці Customer
        $sql = "INSERT INTO Customer (customer_username, customer_password, first_name, last_name, email, address, gender, birthdate, phone_number) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$un, $pw, $fn, $ln, $em, $ad, $gn, $bd, $ph]);

        echo "<script>alert('УРА! Реєстрація пройшла!'); window.location.href='login_register.php';</script>";

    } catch (PDOException $e) {
        // Якщо база знову виматюкається - ми побачимо причину
        die("❌ ПОМИЛКА БАЗИ: " . $e->getMessage());
    }
} else {
    die("❌ Кнопка register_btn не натиснута або не знайдена в POST.");
}