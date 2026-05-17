<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost"; 
$db = "beautystore"; 
$user = "beautyuser"; 
$pass = "1234";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_POST['login_admin'])) {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

       
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            $login_success = false;
            if (password_verify($password, $admin['password'])) {
                $login_success = true;
            } 
            elseif ($password === $admin['password']) {
                $login_success = true;
                
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $upd->execute([$new_hash, $admin['user_id']]);
            }

            if ($login_success) {
                $_SESSION['user_id'] = $admin['user_id'];
                $_SESSION['user_name'] = $admin['first_name'];
                $_SESSION['role'] = 'admin';
                $_SESSION['is_prive_admin'] = true;
                
                header("Location: admin_prive.php");
                exit();
            } else {
                $error = "ДОСТУП ЗАБОРОНЕНО АБО ДАНІ НЕВІРНІ";
            }
        } else {
            $error = "ДОСТУП ЗАБОРОНЕНО АБО ДАНІ НЕВІРНІ";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SECURE ACCESS | Privé</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f4f1ef] flex items-center justify-center min-h-screen">
    <div class="bg-white p-10 border-4 border-black shadow-[10px_10px_0_0_#000] w-96">
        <h2 class="text-2xl font-black mb-6 uppercase italic">Admin Entrance</h2>
        <?php if(isset($error)): ?>
            <p class="text-red-600 font-bold mb-4 uppercase text-xs">❌ <?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <input type="email" name="email" placeholder="admin@beautystore.ua" class="w-full p-4 border-2 border-black font-bold outline-none" required>
            <input type="password" name="password" placeholder="PASSWORD" class="w-full p-4 border-2 border-black font-bold outline-none" required>
            <button type="submit" name="login_admin" class="w-full bg-black text-white p-4 font-black uppercase hover:bg-yellow-600 transition">Увійти в систему</button>
        </form>
        <div class="mt-6 text-center">
            <a href="../index.php" class="text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-black">← Повернутися на сайт</a>
        </div>
    </div>
</body>
</html>
