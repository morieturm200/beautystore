<?php
// 1. СЕСІЯ МАЄ СТАРТУВАТИ ПЕРШОЮ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Підключення
$host = "localhost";
$db   = "beautystore";
$user = "beautyuser";
$pass = "1234";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Помилка підключення: " . $e->getMessage());
}

$message = "";

// --- ЛОГІКА ВХОДУ ---
if (isset($_POST['login_btn'])) {
    $em = trim($_POST['email']);
    $pw = trim($_POST['password']);

    // Шукаємо в таблиці customer (з малої літери!)
    $stmt = $pdo->prepare("SELECT * FROM customer WHERE email = ?");
    $stmt->execute([$em]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Перевіряємо пароль (і хешований, і звичайний для тестів)
        if (password_verify($pw, $user['customer_password']) || $pw === $user['customer_password']) {
            
            // КРИТИЧНО: Назва має бути customer_id, щоб профіль її побачив!
            $_SESSION['customer_id'] = $user['customer_id'];
            $_SESSION['customer_name'] = $user['first_name'];
            
            // Редирект через PHP (надійніше за JS)
            header("Location: profile.php");
            exit();
        } else {
            $message = "❌ Невірний пароль!";
        }
    } else {
        // Якщо не клієнт, шукаємо в Admin
        $stmt_admin = $pdo->prepare("SELECT * FROM Admin WHERE email = ?");
        $stmt_admin->execute([$em]);
        $admin = $stmt_admin->fetch(PDO::FETCH_ASSOC);

        if ($admin && (password_verify($pw, $admin['admin_password']) || $pw === $admin['admin_password'])) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['first_name'];
            $_SESSION['is_prive_admin'] = true;
            
            header("Location: admin/admin_prive.php");
            exit();
        } else {
            $message = "❌ Користувача з таким Email не знайдено!";
        }
    }
}

// --- ЛОГІКА РЕЄСТРАЦІЇ ---
if (isset($_POST['register_btn'])) {
    $un = $_POST['customer_username'];
    $fn = $_POST['first_name'];
    $ln = $_POST['last_name'];
    $em = $_POST['email'];
    $pw = password_hash($_POST['customer_password'], PASSWORD_DEFAULT);

    try {
        // Вставляємо дані в Customer
        $sql = "INSERT INTO customer (customer_username, customer_password, discount, first_name, last_name, email, address, gender, birthdate, phone_number) 
                VALUES (?, ?, 0, ?, ?, ?, 'Не вказана', 'Інше', '2000-01-01', '0000000000')";
        $pdo->prepare($sql)->execute([$un, $pw, $fn, $ln, $em]);
        $message = "✅ Реєстрація успішна! Тепер увійдіть.";
    } catch (Exception $e) {
        $message = "❌ Помилка реєстрації: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Вхід та Реєстрація | BeautyStore</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; background: #fdfaf9; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .container { background: white; padding: 50px; border: 3px solid black; width: 100%; max-width: 400px; text-align: center; box-shadow: 20px 20px 0px rgba(0,0,0,0.05); }
        h1 { font-family: 'Playfair Display', serif; font-size: 2.5rem; margin-bottom: 30px; letter-spacing: -1px; }
        input { width: 100%; padding: 15px; margin: 10px 0; border: 2px solid #eee; box-sizing: border-box; font-size: 14px; }
        input:focus { border-color: black; outline: none; }
        button { width: 100%; background: black; color: white; padding: 18px; border: none; cursor: pointer; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-top: 15px; transition: 0.3s; }
        button:hover { background: #333; }
        .msg { padding: 15px; margin-bottom: 20px; font-weight: bold; font-size: 13px; border: 1px solid black; background: #fff; width: 400px; box-sizing: border-box; text-align: center; }
        .hidden { display: none; }
        .toggle { cursor: pointer; text-decoration: underline; font-size: 12px; margin-top: 25px; display: block; color: #888; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>

    <?php if($message): ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="container" id="login-box">
        <h1>BeautyStore</h1>
        <form method="POST">
            <input type="email" name="email" placeholder="Ваш Email" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit" name="login_btn">Увійти в кабінет</button>
        </form>
        <span class="toggle" onclick="toggleForm()">Створити новий акаунт</span>
    </div>

    <div class="container hidden" id="reg-box">
        <h1>Приєднатися</h1>
        <form method="POST">
            <input type="text" name="customer_username" placeholder="Нікнейм (Логін)" required>
            <input type="text" name="first_name" placeholder="Ім'я" required>
            <input type="text" name="last_name" placeholder="Прізвище" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="customer_password" placeholder="Пароль" required>
            <button type="submit" name="register_btn">Зареєструватися</button>
        </form>
        <span class="toggle" onclick="toggleForm()">Вже є акаунт? Увійти</span>
    </div>

    <script>
        function toggleForm() {
            document.getElementById('login-box').classList.toggle('hidden');
            document.getElementById('reg-box').classList.toggle('hidden');
        }
    </script>
</body>
</html>