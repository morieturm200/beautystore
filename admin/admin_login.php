<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Параметри підключення (перевір чи beautyuser має доступ до localhost)
$host = "localhost"; $db = "beautystore"; $user = "beautyuser"; $pass = "1234";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_POST['login_admin'])) {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        // Шукаємо в таблиці Admin
        $stmt = $pdo->prepare("SELECT * FROM Admin WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && $password === $admin['admin_password']) {
            // Встановлюємо ПРАВИЛЬНІ змінні сесії для доступу всюди
            $_SESSION['admin_logged_in'] = true; 
            $_SESSION['is_prive_admin'] = true;
            $_SESSION['admin_name'] = $admin['first_name'];
            $_SESSION['user_role'] = 'admin'; // додаємо роль про всяк випадок
            
            header("Location: admin_prive.php");
            exit();
        } else {
            $error = "НЕВІРНИЙ ЛОГІН АБО ПАРОЛЬ";
        }
    }
} catch (PDOException $e) {
    die("Помилка з'єднання з БД: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>SECURE ACCESS | Privé</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f4f1ef] flex items-center justify-center min-h-screen">
    <div class="bg-white p-10 border-4 border-black shadow-[10px_10px_0_0_#000] w-96">
        <h2 class="text-2xl font-black mb-6 uppercase italic italic">Admin Entrance</h2>
        <?php if(isset($error)) echo "<p style='color:red; font-weight:bold; margin-bottom:15px;'>$error</p>"; ?>
        <form method="POST" class="space-y-4">
            <input type="email" name="email" placeholder="admin@beauty.ua" class="w-full p-4 border-2 border-black font-bold outline-none" required>
            <input type="password" name="password" placeholder="PASSWORD" class="w-full p-4 border-2 border-black font-bold outline-none" required>
            <button type="submit" name="login_admin" class="w-full bg-black text-white p-4 font-black uppercase hover:bg-yellow-600 transition">Увійти в систему</button>
        </form>
    </div>
</body>
</html>