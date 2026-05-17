<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: login_register.php");
    exit();
}

$host = "localhost";
$db   = "beautystore";
$user = "beautyuser";
$pass = "1234";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $current_user_id = $_SESSION['user_id'];

   
    if (isset($_POST['cancel_order'])) {
        $o_id = intval($_POST['order_id']);
    
        $cancel_sql = "UPDATE orders SET status = 'cancelled' 
                       WHERE order_id = ? AND user_id = ? AND status = 'accepted'";
        $stmt = $pdo->prepare($cancel_sql);
        $stmt->execute([$o_id, $current_user_id]);
        $success_msg = "Замовлення №$o_id було успішно скасовано.";
    }


    if (isset($_POST['update_profile'])) {
        $f_name = $_POST['first_name'] ?? '';
        $l_name = $_POST['last_name'] ?? '';
        $u_name = $_POST['customer_username'] ?? ''; 
        $email  = $_POST['email'] ?? '';
        $phone  = $_POST['phone_number'] ?? '';
        
        $region = $_POST['region'] ?? '';
        $city   = $_POST['city'] ?? '';
        $s_house = $_POST['street_house'] ?? '';
        
        $gender = $_POST['gender'] ?? '';
        $b_date = (!empty($_POST['birthdate']) ? $_POST['birthdate'] : null);
        $new_pw = trim($_POST['customer_password'] ?? '');

        if (!empty($new_pw)) {
            $hashed_pw = password_hash($new_pw, PASSWORD_DEFAULT);
            
            $update_sql = "UPDATE users 
                           SET first_name = ?, last_name = ?, username = ?, email = ?, 
                               phone_number = ?, region = ?, city = ?, street_house = ?, 
                               gender = ?, birthdate = ?, password = ? 
                           WHERE user_id = ?";
            $stmt = $pdo->prepare($update_sql);
            $stmt->execute([$f_name, $l_name, $u_name, $email, $phone, $region, $city, $s_house, $gender, $b_date, $hashed_pw, $current_user_id]);
        } else {
            $update_sql = "UPDATE users 
                           SET first_name = ?, last_name = ?, username = ?, email = ?, 
                               phone_number = ?, region = ?, city = ?, street_house = ?, 
                               gender = ?, birthdate = ? 
                           WHERE user_id = ?";
            $stmt = $pdo->prepare($update_sql);
            $stmt->execute([$f_name, $l_name, $u_name, $email, $phone, $region, $city, $s_house, $gender, $b_date, $current_user_id]);
        }
        
        $_SESSION['user_name'] = $f_name; 
        $success_msg = "Ваш профіль успішно синхронізовано з сервером BeautyStore.";
    }


    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$current_user_id]);
    $user_data = $stmt->fetch();

  
    $order_sql = "
        SELECT o.*,
        (SELECT GROUP_CONCAT(p.name SEPARATOR ' • ') 
         FROM Order_Details od 
         JOIN product p ON od.product_id = p.product_id 
         WHERE od.order_id = o.order_id AND od.status = 'ordered') as items_list,
        (SELECT SUM(quantity * unit_price) 
         FROM Order_Details 
         WHERE order_id = o.order_id AND status = 'ordered') as order_total
        FROM orders o 
        WHERE o.user_id = ? 
          AND o.invoice_no IS NOT NULL 
          AND o.invoice_no != ''
        HAVING order_total > 0
        ORDER BY o.order_date DESC";
    $order_stmt = $pdo->prepare($order_sql);
    $order_stmt->execute([$current_user_id]);
    $orders = $order_stmt->fetchAll();


    $total_earned_cashback = 0;
    $total_spent_cashback = 0;
    $total_delivered_sum = 0;

    foreach($orders as $ord) {
        if($ord['status'] === 'delivered') {
            $actual_paid = $ord['order_total'] - floatval($ord['cashback_spent'] ?? 0);
            if ($actual_paid > 0) {
                $total_earned_cashback += ($actual_paid * 0.02);
            }
            $total_delivered_sum += $ord['order_total'];
        }
        if($ord['status'] !== 'cancelled') {
            $total_spent_cashback += floatval($ord['cashback_spent'] ?? 0);
        }
    }
    
    $cashback_balance = $total_earned_cashback - $total_spent_cashback;
    if ($cashback_balance < 0) $cashback_balance = 0;

    $current_discount_stmt = $pdo->prepare("SELECT discount FROM users WHERE user_id = ?");
    $current_discount_stmt->execute([$current_user_id]);
    $current_discount = floatval($current_discount_stmt->fetch()['discount'] ?? 0);

    if (round($current_discount, 2) != round($cashback_balance, 2)) {
        $sync_cashback = $pdo->prepare("UPDATE users SET discount = ? WHERE user_id = ?");
        $sync_cashback->execute([$cashback_balance, $current_user_id]);
    }


    $cart_count_stmt = $pdo->prepare("
        SELECT SUM(od.quantity) as total 
        FROM Order_Details od
        JOIN orders o ON od.order_id = o.order_id
        WHERE o.user_id = ? AND od.status = 'cart'
    ");
    $cart_count_stmt->execute([$current_user_id]);
    $cart_count = $cart_count_stmt->fetch()['total'] ?? 0;

    $wish_count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM Wishlist WHERE user_id = ?");
    $wish_count_stmt->execute([$current_user_id]);
    $wish_count = $wish_count_stmt->fetch()['total'] ?? 0;


    $notifications = [];
    $low_stock_stmt = $pdo->prepare("
        SELECT p.name, p.stock FROM product p
        WHERE p.stock < 5 AND p.stock > 0 AND (
            p.product_id IN (SELECT product_id FROM Order_Details od JOIN orders o ON od.order_id = o.order_id WHERE o.user_id = ? AND od.status = 'cart')
            OR 
            p.product_id IN (SELECT product_id FROM Wishlist WHERE user_id = ?)
        )
    ");
    $low_stock_stmt->execute([$current_user_id, $current_user_id]);
    while($low = $low_stock_stmt->fetch()) {
        $notifications[] = [
            'type' => 'urgent',
            'icon' => 'fa-fire-flame-curved',
            'text' => "Поспішайте! <strong>" . htmlspecialchars($low['name']) . "</strong> майже закінчився (лише {$low['stock']} шт)."
        ];
    }
    
    if ($cashback_balance > 0) {
        $notifications[] = [
            'type' => 'promo',
            'icon' => 'fa-coins',
            'text' => "На вашому рахунку накопичено <strong>" . number_format($cashback_balance, 2, '.', '') . " ₴ бонусів</strong>. Використайте їх при наступному замовленні!"
        ];
    }
    
    $notif_count = count($notifications);

    $support_stmt = $pdo->prepare("SELECT * FROM Support WHERE user_id = ? ORDER BY submitted_date ASC");
    $support_stmt->execute([$current_user_id]);
    $chat_history = $support_stmt->fetchAll();

} catch (PDOException $e) {
    die("<div style='padding:100px; text-align:center; background:#fff; color:#c5a059; font-family:Montserrat;'>CRITICAL DATA ERROR: " . $e->getMessage() . "</div>");
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privé Cabinet | Master Edition</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --prive-gold: #c5a059;
            --prive-gold-dark: #a6874a;
            --prive-black: #1a1a1a;
            --prive-white: #ffffff;
            --prive-gray-bg: #fdfdfd;
            --prive-gray-border: #f0f0f0;
            --prive-text-gray: #777777;
            --prive-shadow: 0 10px 40px rgba(0,0,0,0.04);
            --font-main: 'Montserrat', sans-serif;
            --font-title: 'Playfair Display', serif;
            --radius-btn: 4px;
            --transition: all 0.4s ease;
        }

        html { scroll-behavior: smooth; }

        .cabinet-hero {
            padding: 80px 20px 80px;
            text-align: center;
            background-color: var(--prive-gray-bg);
            border-bottom: 1px solid var(--prive-gray-border);
        }
        .cabinet-hero .gold-label { 
            color: var(--prive-gold); 
            font-size: 11px; 
            font-weight: 800; 
            text-transform: uppercase; 
            letter-spacing: 10px; 
            margin-bottom: 20px; 
            display: block; 
        }
        .cabinet-hero h1 { 
            font-family: var(--font-title); 
            font-size: 4rem; 
            margin-bottom: 15px; 
            color: var(--prive-black);
            font-weight: 400;
            font-style: italic;
        }
        .cabinet-hero p { max-width: 700px; margin: 0 auto; color: var(--prive-text-gray); font-size: 1rem; font-weight: 300; }

        .tabs-header {
            display: flex;
            justify-content: center;
            gap: 60px;
            background: #fff;
            padding: 10px 0;
            border-bottom: 1px solid var(--prive-gray-border);
        }
        .tab-item {
            background: none; border: none; cursor: pointer;
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 3px; color: #aaa; 
            padding: 15px 0;
            position: relative; transition: var(--transition);
            font-family: var(--font-main);
        }
        .tab-item.active { color: var(--prive-black); }
        .tab-item.active::after {
            content: ""; position: absolute; bottom: 0; left: 0; width: 100%; height: 2px; background: var(--prive-gold);
        }

        .notif-dot {
            position: absolute;
            top: 15px;
            right: -12px;
            width: 7px;
            height: 7px;
            background-color: #e74c3c;
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(231, 76, 60, 0.4);
            display: <?= ($notif_count > 0) ? 'block' : 'none' ?>;
        }

        .master-wrapper { max-width: 1400px; margin: 60px auto; padding: 0 60px 150px; }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; animation: contentFadeIn 0.6s ease; }
        @keyframes contentFadeIn { from { opacity: 0; } to { opacity: 1; } }

        .insight-feed { margin-bottom: 40px; display: flex; flex-direction: column; gap: 12px; }
        .insight-card {
            background: #fff;
            padding: 20px 30px;
            border-left: 3px solid var(--prive-gold);
            display: flex;
            align-items: center;
            gap: 25px;
            font-size: 14px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.02);
            border: 1px solid var(--prive-gray-border);
            border-radius: 4px;
        }
        .insight-card.urgent { border-left-color: #e74c3c; background: #fffafb; }
        .insight-card.promo { border-left-color: var(--prive-gold); }
        .insight-card i { font-size: 18px; color: var(--prive-gold); width: 25px; text-align: center; }
        .insight-card.urgent i { color: #e74c3c; }

        .stats-grid-box { display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px; margin-bottom: 60px; }
        .stat-card-supreme { 
            background: var(--prive-white); 
            padding: 50px 30px; 
            text-align: center; 
            border: 1px solid var(--prive-gray-border);
            transition: var(--transition);
            text-decoration: none;
            color: inherit;
        }
        .stat-card-supreme:hover { border-color: var(--prive-gold); box-shadow: var(--prive-shadow); }
        .stat-card-supreme h3 { font-size: 10px; text-transform: uppercase; letter-spacing: 4px; color: var(--prive-text-gray); margin-bottom: 15px; }
        .stat-card-supreme .stat-val { font-family: var(--font-title); font-size: 2.8rem; font-weight: 400; color: var(--prive-black); }

        .loyalty-banner {
            background: #fff;
            color: var(--prive-black);
            padding: 80px 40px;
            text-align: center;
            border: 1px solid var(--prive-gray-border);
        }
        .loyalty-banner h2 { font-family: var(--font-title); color: var(--prive-black); font-size: 2.5rem; margin-bottom: 20px; font-weight: 400; }
        .loyalty-banner p { max-width: 700px; margin: 0 auto; font-weight: 400; font-size: 1.1rem; color: #5a5a5a; }

        .account-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; }
        .card-prive-form { background: #fff; padding: 60px; border: 1px solid var(--prive-gray-border); }
        .card-prive-form h2 { font-family: var(--font-title); font-size: 2.2rem; margin-bottom: 40px; font-weight: 400; }

        .luxe-input-box { margin-bottom: 35px; }
        .luxe-input-box label { display: block; font-size: 9px; font-weight: 800; text-transform: uppercase; color: var(--prive-gold); letter-spacing: 3px; margin-bottom: 10px; }
        .luxe-input-box input, .luxe-input-box select { 
            width: 100%; padding: 15px 0; border: none; border-bottom: 1px solid var(--prive-gray-border); 
            font-size: 16px; color: var(--prive-black); background: transparent; transition: var(--transition);
            font-family: var(--font-main);
        }
        .luxe-input-box input:focus, .luxe-input-box select:focus { border-bottom-color: var(--prive-black); outline: none; }
        
        .btn-prive-master { 
            width: 100%; padding: 20px; background: var(--prive-black); color: #fff; 
            border: none; font-size: 12px; font-weight: 700; 
            text-transform: uppercase; letter-spacing: 5px; cursor: pointer; transition: var(--transition);
            font-family: var(--font-main);
        }
        .btn-prive-master:hover { background: var(--prive-gold); }

        .logistic-card { background: var(--prive-gray-bg); padding: 50px; height: fit-content; border: 1px solid var(--prive-gray-border); }
        .logistic-card h3 { font-family: var(--font-title); font-size: 1.5rem; margin-bottom: 25px; font-weight: 400; }

        .order-royal-card { 
            background: #fff; border-bottom: 1px solid var(--prive-gray-border); padding: 40px 0; 
            transition: var(--transition);
            display: grid;
            grid-template-columns: 1.5fr 3fr 1.5fr;
            gap: 30px;
            align-items: center;
        }
        .order-royal-card.is-void { opacity: 0.3; }
        
        .order-id-label { font-family: var(--font-title); font-size: 1.4rem; font-weight: 700; color: var(--prive-black); }
        .order-status-pill { 
            font-size: 9px; font-weight: 800; text-transform: uppercase; 
            padding: 5px 12px; border: 1px solid var(--prive-black);
            letter-spacing: 1px; display: inline-block; margin-top: 10px;
        }
        .items-cloud { font-size: 13px; color: var(--prive-text-gray); font-style: italic; line-height: 1.6; }
        
        .order-price-supreme { font-family: var(--font-title); font-size: 1.8rem; font-weight: 700; text-align: right; }
        .order-footer-links { margin-top: 15px; display: flex; gap: 20px; justify-content: flex-end; }
        .link-invoice-pdf { font-size: 10px; color: var(--prive-black); font-weight: 800; text-decoration: none; border-bottom: 1px solid var(--prive-black); }
        .btn-cancel-royal { background: none; border: none; color: #aaa; font-size: 10px; font-weight: 700; cursor: pointer; text-transform: uppercase; font-family: var(--font-main); }

        .bot-trigger-supreme {
            position: fixed; bottom: 40px; right: 40px; width: 85px; height: 85px;
            background: var(--prive-black); 
            color: var(--prive-gold); 
            border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            cursor: pointer; z-index: 10000; font-size: 2.2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
            border: 2px solid var(--prive-gold); 
            transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .bot-trigger-supreme:hover { transform: scale(1.1); background: var(--prive-gold); color: var(--prive-black); }

        .bot-window-supreme {
            position: fixed; bottom: 130px; right: 40px; width: 420px; height: 620px;
            background: #fff; border-radius: 12px; 
            box-shadow: 0 30px 120px rgba(0,0,0,0.2);
            display: none; flex-direction: column; overflow: hidden; z-index: 10001;
            border: 2px solid var(--prive-black);
        }
        .bot-head-luxe { background: var(--prive-black); color: var(--prive-gold); padding: 25px; display: flex; justify-content: space-between; align-items: center; }
        .bot-head-luxe h4 { font-family: var(--font-title); font-size: 1.4rem; font-weight: 400; letter-spacing: 1px; }
        
        .bot-body-luxe { flex: 1; padding: 30px; overflow-y: auto; background: #fafafa; display: flex; flex-direction: column; gap: 20px; }
        
        .msg-bubble-prive { max-width: 85%; padding: 18px 22px; border-radius: 20px; font-size: 13px; line-height: 1.6; }
        .msg-bubble-prive.me { background: var(--prive-black); color: var(--prive-gold); align-self: flex-end; border-bottom-right-radius: 2px; }
        .msg-bubble-prive.ai { background: var(--prive-white); color: var(--prive-black); align-self: flex-start; border-bottom-left-radius: 2px; border: 1px solid var(--prive-gray-border); box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .msg-bubble-prive .ts { font-size: 8px; text-transform: uppercase; margin-top: 10px; display: block; opacity: 0.5; font-weight: 700; }

        .bot-foot-luxe { padding: 25px; background: #fff; border-top: 1px solid var(--prive-gray-border); display: flex; gap: 15px; align-items: center; }
        .bot-foot-luxe input { flex: 1; background: var(--prive-gray-bg); border: 1px solid var(--prive-gray-border); padding: 15px 25px; border-radius: 30px; font-size: 14px; font-family: var(--font-main); }
        .bot-foot-luxe input:focus { border-color: var(--prive-gold); outline: none; }
        .btn-send-royal { background: var(--prive-black); color: var(--prive-gold); width: 50px; height: 50px; border-radius: 50%; border: none; cursor: pointer; transition: 0.3s; }
        .btn-send-royal:hover { background: var(--prive-gold); color: var(--prive-black); }

        .master-alert {
            position: fixed; top: 0; left: 0; width: 100%; background: var(--prive-black); color: #fff;
            padding: 20px; text-align: center; font-weight: 700; letter-spacing: 2px;
            text-transform: uppercase; font-size: 11px; z-index: 99999;
            animation: slideDownMaster 0.5s ease;
        }
        @keyframes slideDownMaster { from { transform: translateY(-100%); } to { transform: translateY(0); } }

        @media (max-width: 1000px) {
            .stats-grid-box { grid-template-columns: 1fr; }
            .account-layout { grid-template-columns: 1fr; }
            .order-royal-card { grid-template-columns: 1fr; text-align: center; }
            .order-price-supreme { text-align: center; }
            .order-footer-links { justify-content: center; }
        }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<?php if(isset($success_msg)): ?>
    <div id="masterAlert" class="master-alert">
        <i class="fa-solid fa-check" style="margin-right:15px; color:var(--prive-gold);"></i> <?= $success_msg ?>
    </div>
    <script>setTimeout(() => document.getElementById('masterAlert').style.display='none', 6000);</script>
<?php endif; ?>

<div class="cabinet-hero">
    <span class="gold-label">Bienvenue au Club</span>
    <h1>Привіт, <?= htmlspecialchars($user_data['first_name'] ?? 'Гість') ?></h1>
    <p>Ваш персональний простір краси. Управляйте замовленнями та налаштуваннями профілю.</p>
</div>

<div class="tabs-header">
    <button class="tab-item active" onclick="triggerPane(event, 'pane-dashboard')">Огляд</button>
    <button class="tab-item" onclick="triggerPane(event, 'pane-orders')">Замовлення (<?= count($orders) ?>)</button>
    <button class="tab-item" onclick="triggerPane(event, 'pane-insights')">
        Сповіщення
        <span class="notif-dot" id="global-notif-dot"></span>
    </button>
    <button class="tab-item" onclick="triggerPane(event, 'pane-profile')">Налаштування</button>
</div>

<div class="master-wrapper">
    
    <div id="pane-dashboard" class="tab-pane active">
        <div class="stats-grid-box">
            <div class="stat-card-supreme">
                <h3>Доставлено (Сума)</h3>
                <div class="stat-val"><?= number_format($total_delivered_sum, 0, '.', ' ') ?> ₴</div>
            </div>
            <a href="cart.php" class="stat-card-supreme">
                <h3>Товарів у кошику</h3>
                <div class="stat-val" style="color:var(--prive-gold);"><?= $cart_count ?></div>
            </a>
            <a href="wishlist.php" class="stat-card-supreme">
                <h3>Список бажань</h3>
                <div class="stat-val"><?= $wish_count ?></div>
            </a>
            
            <div class="stat-card-supreme">
                <h3>Ваш кешбек</h3>
                <div class="stat-val" style="color:var(--prive-gold);"><?= number_format($cashback_balance, 0, '.', ' ') ?> ₴</div>
            </div>
        </div>
        
        <div class="loyalty-banner">
            <h2>BeautyStore Cashback</h2>
            <p>Тепер кешбек нараховується автоматично. Використайте бонуси для оплати нових покупок!</p>
        </div>
    </div>

    <div id="pane-insights" class="tab-pane">
        <div style="max-width: 850px; margin: 0 auto;">
            <h2 style="font-family: var(--font-title); font-size: 3rem; margin-bottom: 40px; text-align: center; font-style: italic;">Privé Insights</h2>
            <?php if(empty($notifications)): ?>
                <div style="text-align: center; padding: 100px 0; color: #ccc;">
                    <i class="fa-regular fa-bell-slash" style="font-size: 3rem; margin-bottom: 20px; display: block;"></i>
                    <p>Наразі нових сповіщень немає.</p>
                </div>
            <?php else: ?>
                <div class="insight-feed">
                    <?php foreach($notifications as $n): ?>
                        <div class="insight-card <?= $n['type'] ?>">
                            <i class="fa-solid <?= $n['icon'] ?>"></i>
                            <span><?= $n['text'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="pane-orders" class="tab-pane">
        <div style="max-width: 1000px; margin: 0 auto;">
            <?php if(empty($orders)): ?>
                <div style="text-align: center; padding: 100px 0;">
                    <p style="color: #bbb; font-style: italic;">У вас поки немає замовлений.</p>
                </div>
            <?php else: ?>
                <?php foreach($orders as $order): 
                    $is_void = ($order['status'] == 'cancelled');
                ?>
                    <div class="order-royal-card <?= $is_void ? 'is-void' : '' ?>">
                        <div>
                            <span class="order-id-label">№ <?= $order['order_id'] ?></span><br>
                            
                            <?php 
                                $status_map_user = [
                                    'accepted'   => ['label' => 'Прийнято',    'style' => 'border-color: #3498db; color: #3498db;'],
                                    'processing' => ['label' => 'В обробці',   'style' => 'border-color: #f39c12; color: #f39c12;'],
                                    'shipped'    => ['label' => 'Відправлено', 'style' => 'border-color: #9b59b6; color: #9b59b6;'],
                                    'delivered'  => ['label' => 'Доставлено',  'style' => 'border-color: #2ecc71; color: #2ecc71;'],
                                    'cancelled'  => ['label' => 'Скасовано',   'style' => 'border-color: #e74c3c; color: #e74c3c;'],
                                ];
                                $lbl = $status_map_user[$order['status']]['label'] ?? $order['status'];
                                $stl = $status_map_user[$order['status']]['style'] ?? '';
                            ?>
                            <span class="order-status-pill" style="<?= $stl ?>"><?= $lbl ?></span>
                        </div>
                        
                        <div class="items-cloud">
                            <?= htmlspecialchars($order['items_list'] ?? 'Парфумерія та догляд') ?><br>
                            <small style="font-style: normal; color: #ccc;"><?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></small>
                        </div>

                        <div>
                            <div class="order-price-supreme"><?= number_format($order['order_total'] ?? 0, 0, '.', ' ') ?> ₴</div>
                            <div class="order-footer-links">
                                <a href="invoice.php?order_id=<?= $order['order_id'] ?>" target="_blank" class="link-invoice-pdf">Інвойс</a>
                                <?php if($order['status'] == 'accepted'): ?>
                                    <form method="POST" onsubmit="return confirm('Скасувати замовлення?')">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <button type="submit" name="cancel_order" class="btn-cancel-royal">Скасувати</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="pane-profile" class="tab-pane">
        <div class="account-layout">
            <div class="card-prive-form">
                <h2>Профіль</h2>
                <form method="POST" id="priveFormMaster">
                    <div class="luxe-input-box">
                        <label>Логін (Username)</label>
                        <input type="text" name="customer_username" value="<?= htmlspecialchars($user_data['username'] ?? '') ?>" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="luxe-input-box">
                            <label>Ім'я</label>
                            <input type="text" name="first_name" value="<?= htmlspecialchars($user_data['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="luxe-input-box">
                            <label>Прізвище</label>
                            <input type="text" name="last_name" value="<?= htmlspecialchars($user_data['last_name'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="luxe-input-box">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" required>
                    </div>
                    <div class="luxe-input-box">
                        <label>Телефон</label>
                        <input type="text" name="phone_number" value="<?= htmlspecialchars($user_data['phone_number'] ?? '') ?>">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="luxe-input-box">
                            <label>Дата народження</label>
                            <input type="date" name="birthdate" value="<?= $user_data['birthdate'] ?? '' ?>">
                        </div>
                        <div class="luxe-input-box">
                            <label>Стать</label>
                            <select name="gender">
                                <option value="" disabled <?= empty($user_data['gender']) ? 'selected' : '' ?>>Оберіть...</option>
                                <option value="Жіноча" <?= ($user_data['gender'] == 'Жіноча') ? 'selected' : '' ?>>Жіноча</option>
                                <option value="Чоловіча" <?= ($user_data['gender'] == 'Чоловіча') ? 'selected' : '' ?>>Чоловіча</option>
                                <option value="Інша" <?= ($user_data['gender'] == 'Інша') ? 'selected' : '' ?>>Інша</option>
                            </select>
                        </div>
                    </div>
                    <div class="luxe-input-box">
                        <label>Змінити пароль</label>
                        <input type="password" name="customer_password" placeholder="Новий пароль">
                    </div>
                    <button type="submit" name="update_profile" class="btn-prive-master">Зберегти дані</button>
                </form>
            </div>

            <div class="logistic-card">
                <h3>Доставка</h3>
                <div class="luxe-input-box" style="margin-top:40px;">
                    <label>Область</label>
                    <select name="region" form="priveFormMaster">
                        <option value="">Оберіть область...</option>
                        <option value="Вінницька" <?= (($user_data['region'] ?? '') == 'Вінницька') ? 'selected' : '' ?>>Вінницька</option>
                        <option value="Львівська" <?= (($user_data['region'] ?? '') == 'Львівська') ? 'selected' : '' ?>>Львівська</option>
                        <option value="Київська" <?= (($user_data['region'] ?? '') == 'Київська') ? 'selected' : '' ?>>Київська</option>
                        <option value="Одеська" <?= (($user_data['region'] ?? '') == 'Одеська') ? 'selected' : '' ?>>Одеська</option>
                        <option value="м. Київ" <?= (($user_data['region'] ?? '') == 'м. Київ') ? 'selected' : '' ?>>м. Київ</option>
                    </select>
                </div>
                <div class="luxe-input-box">
                    <label>Місто</label>
                    <input type="text" name="city" form="priveFormMaster" value="<?= htmlspecialchars($user_data['city'] ?? '') ?>" placeholder="Ваше місто">
                </div>
                <div class="luxe-input-box">
                    <label>Вулиця / № Відділення</label>
                    <input type="text" name="street_house" form="priveFormMaster" value="<?= htmlspecialchars($user_data['street_house'] ?? '') ?>" placeholder="Кульпарківська 176">
                </div>
            </div>
        </div>
    </div>

</div>

<div class="bot-trigger-supreme" onclick="toggleRoyalChat()">
    <i class="fa-solid fa-crown"></i>
</div>

<div class="bot-window-supreme" id="royalChatBox">
    <div class="bot-head-luxe">
        <h4>Concierge Support</h4>
        <i class="fa-solid fa-xmark" onclick="toggleRoyalChat()" style="cursor:pointer;"></i>
    </div>
    
    <div class="bot-body-luxe" id="royalFlow">
        <div class="msg-bubble-prive ai">
            Вітаємо у Privé Support. Чим я можу бути корисним сьогодні?
            <span class="ts">Online</span>
        </div>
        
        <?php foreach($chat_history as $m): ?>
            <div class="msg-bubble-prive me">
                <?= htmlspecialchars($m['message']) ?>
                <span class="ts"><?= date('H:i', strtotime($m['submitted_date'])) ?></span>
            </div>
            
            <?php if(!empty($m['reply_text'])): ?>
                <div class="msg-bubble-prive ai">
                    <strong>Support:</strong><br>
                    <?= htmlspecialchars($m['reply_text']) ?>
                    <span class="ts">Офіційна відповідь</span>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <div class="bot-foot-luxe">
        <input type="text" id="royalInputField" placeholder="Напишіть нам...">
        <button class="btn-send-royal" onclick="sendMasterMessage()">
            <i class="fa-solid fa-arrow-up"></i>
        </button>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    function triggerPane(event, paneId) {
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.tab-item').forEach(b => b.classList.remove('active'));
        document.getElementById(paneId).classList.add('active');
        event.currentTarget.classList.add('active');

        if(paneId === 'pane-insights') {
            const dot = document.getElementById('global-notif-dot');
            if(dot) dot.style.display = 'none';
        }
    }

    function toggleRoyalChat() {
        const win = document.getElementById('royalChatBox');
        const isOpen = win.style.display === 'flex';
        win.style.display = isOpen ? 'none' : 'flex';
        if (!isOpen) {
            const flow = document.getElementById('royalFlow');
            flow.scrollTop = flow.scrollHeight;
        }
    }

    async function sendMasterMessage() {
        const input = document.getElementById('royalInputField');
        const flow = document.getElementById('royalFlow');
        const text = input.value.trim();
        if (text === "") return;

        const msgDiv = document.createElement('div');
        msgDiv.className = 'msg-bubble-prive me';
        msgDiv.innerHTML = `${text}<span class="ts">Відправлено</span>`;
        flow.appendChild(msgDiv);
        input.value = "";
        flow.scrollTop = flow.scrollHeight;

        const formData = new FormData();
        formData.append('message', text);

        try {
            const response = await fetch('support_handler.php', { method: 'POST', body: formData });
            
            setTimeout(() => {
                const aiDiv = document.createElement('div');
                aiDiv.className = 'msg-bubble-prive ai';
                aiDiv.innerText = "Дякуємо! Менеджер відповість найближчим часом.";
                flow.appendChild(aiDiv);
                flow.scrollTop = flow.scrollHeight;
            }, 1200);
        } catch (e) { console.error("Error", e); }
    }

    document.getElementById('royalInputField').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMasterMessage();
    });
</script>

</body>
</html>
