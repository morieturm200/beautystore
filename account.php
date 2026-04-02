<?php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: login_register.php");
    exit();
}

$conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");

if ($conn->connect_error) {
    die("Помилка підключення: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");


$user_id = intval($_SESSION['customer_id']);
$result = $conn->query("SELECT * FROM customer WHERE customer_id = $user_id");

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    
    session_destroy();
    header("Location: login_register.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Мій Профіль | BeautyStore</title>
    <style>
        :root {
            --main-pink: #fce4ec;
            --accent-pink: #e91e63;
        }
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--main-pink); margin: 0; padding: 20px; }
        
        .profile-card {
            background: white;
            max-width: 500px;
            margin: 50px auto;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            text-align: center;
        }

        .avatar {
            width: 80px; height: 80px;
            background: var(--main-pink);
            color: var(--accent-pink);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; margin: 0 auto 20px;
        }

        h2 { color: #333; margin-bottom: 5px; }
        .email { color: #888; margin-bottom: 25px; }

        .info-grid { text-align: left; background: #fdfdfd; padding: 20px; border-radius: 12px; border: 1px solid #eee; }
        .info-item { margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .info-item:last-child { border-bottom: none; }
        .label { font-size: 10px; color: #aaa; text-transform: uppercase; letter-spacing: 1px; }
        .value { font-size: 15px; color: #333; font-weight: 500; }

        .btn-back {
            display: inline-block; margin-top: 25px;
            text-decoration: none; color: var(--accent-pink);
            font-weight: bold; border: 2px solid var(--accent-pink);
            padding: 12px 30px; border-radius: 10px; transition: 0.3s;
        }
        .btn-back:hover { background: var(--accent-pink); color: white; }
    </style>
</head>
<body>

<div class="profile-card">
    <div class="avatar">🌸</div>
    <h2><?php echo htmlspecialchars($user['first_name'] . " " . $user['last_name']); ?></h2>
    <p class="email"><?php echo htmlspecialchars($user['email']); ?></p>

    <div class="info-grid">
        <div class="info-item">
            <div class="label">Логін</div>
            <div class="value">@<?php echo htmlspecialchars($user['customer_username']); ?></div>
        </div>
        <div class="info-item">
            <div class="label">Телефон</div>
            <div class="value"><?php echo htmlspecialchars($user['phone_number']); ?></div>
        </div>
        <div class="info-item">
            <div class="label">Адреса доставки</div>
            <div class="value"><?php echo htmlspecialchars($user['address']); ?></div>
        </div>
        <div class="info-item">
            <div class="label">Статус</div>
            <div class="value">
                <?php 
                
                echo (isset($user['discount']) && $user['discount'] > 0) ? 'VIP Клієнт' : 'Постійний покупець'; 
                ?>
            </div>
        </div>
    </div>

    <a href="index.php" class="btn-back">Повернутися до покупок</a>
    
    <a href="logout.php" style="color: #bbb; font-size: 12px; text-decoration: none; display: block; margin-top: 20px; font-weight: 600; text-transform: uppercase;">Вийти з акаунту</a>
</div>

</body>
</html>