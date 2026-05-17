<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$db   = "beautystore";
$user = "beautyuser";
$pass = "1234";

try {

    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Помилка підключення: " . $e->getMessage());
}

$message = "";

if (isset($_POST['login_btn'])) {
    $em = trim($_POST['email']);
    $pw = trim($_POST['password']);

    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$em]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        $login_success = false;

        
        if (password_verify($pw, $user_data['password'])) {
            $login_success = true;
        } 
        
        elseif ($pw === $user_data['password']) {
            $login_success = true;
            
            $new_hash = password_hash($pw, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $upd->execute([$new_hash, $user_data['user_id']]);
        }

        if ($login_success) {
       
            $_SESSION['user_id'] = $user_data['user_id'];
            $_SESSION['user_name'] = $user_data['first_name'];
            $_SESSION['role'] = $user_data['role'];
            
          
            if ($user_data['role'] === 'admin') {
                $_SESSION['is_prive_admin'] = true;
                header("Location: admin/admin_prive.php");
                exit();
            } else {
                header("Location: profile.php");
                exit();
            }
        } else {
            $message = "❌ Невірний пароль!";
        }
    } else {
        $message = "❌ Користувача з таким Email не знайдено!";
    }
}


if (isset($_POST['register_btn'])) {
    $un = trim($_POST['customer_username'] ?? '');
    $em = trim($_POST['email'] ?? '');
    $pass_input = $_POST['customer_password'] ?? '';
    

    if (empty($un) || empty($em) || empty($pass_input)) {
        $message = "❌ Логін, Email та Пароль є обов'язковими!";
    } else {

        $check_stmt = $pdo->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
        $check_stmt->execute([$un, $em]);
        $existing_user = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_user) {
            if ($existing_user['username'] === $un) {
                $message = "❌ Цей логін вже зайнятий!";
            } elseif ($existing_user['email'] === $em) {
                $message = "❌ Ця електронна адреса вже зареєстрована!";
            }
        } else {

            $pw = password_hash($pass_input, PASSWORD_DEFAULT);
            $fn = !empty($_POST['first_name']) ? trim($_POST['first_name']) : null;
            $ln = !empty($_POST['last_name']) ? trim($_POST['last_name']) : null;

            try {
   
                $sql = "INSERT INTO users (username, password, first_name, last_name, email, role) 
                        VALUES (?, ?, ?, ?, ?, 'customer')";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$un, $pw, $fn, $ln, $em]);


                header("Location: login_register.php?success=1");
                exit();

            } catch (PDOException $e) {
                $message = "❌ Помилка бази: " . $e->getMessage();
            }
        }
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
        <div class="msg" style="color: #d32f2f; border-color: #d32f2f;"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if(isset($_GET['success'])): ?>
        <div class="msg" style="color: #166534; border-color: #166534; background: #f0fdf4;">
            ✅ Вітаємо! Реєстрація успішна. Тепер увійдіть.
        </div>
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

        document.querySelector('input[name="customer_username"]').addEventListener('input', function() {
            let username = this.value;
            let feedback = document.getElementById('username-feedback');
            
            if(!feedback) {
                feedback = document.createElement('div');
                feedback.id = 'username-feedback';
                feedback.style.cssText = "font-size: 10px; color: #d32f2f; font-weight: 700; margin-top: 5px; text-transform: uppercase; letter-spacing: 1px;";
                this.parentNode.appendChild(feedback);
            }

            if (username.length > 2) {
                fetch('check_username.php?username=' + username)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        this.style.borderColor = '#d32f2f';
                        feedback.innerText = '× Цей логін вже зайнятий';
                    } else {
                        this.style.borderColor = '#bbf7d0';
                        feedback.innerText = '';
                    }
                }).catch(e => console.log('AJAX Error:', e));
            }
        });
    </script>
</body>
</html>
