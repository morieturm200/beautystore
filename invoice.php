<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. ПІДКЛЮЧЕННЯ
$conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");
if ($conn->connect_error) {
    die("Помилка підключення: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Отримуємо дані замовлення
$sql = "SELECT o.*, c.first_name, c.last_name, c.email, c.address, c.phone_number 
        FROM orders o 
        JOIN customer c ON o.customer_id = c.customer_id 
        WHERE o.order_id = $order_id";
$res = $conn->query($sql);
$order = $res->fetch_assoc();

if (!$order) die("Замовлення не знайдено");

// 2. ПЕРЕВІРКА ДОСТУПУ
$is_admin = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) || 
             (isset($_SESSION['is_prive_admin']) && $_SESSION['is_prive_admin'] === true) ||
             (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');

$customer_id = $_SESSION['customer_id'] ?? null;
$is_owner = ($customer_id && $order['customer_id'] == $customer_id);

if (!$is_admin && !$is_owner) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <h2>Доступ обмежено</h2>
            <p>Будь ласка, авторизуйтесь як адміністратор.</p>
            <a href='admin/admin_login.php'>Перейти до логіну</a>
         </div>");
}

// 3. ОТРИМАННЯ ТОВАРІВ (ВИПРАВЛЕНО: додаємо ціну з таблиці деталей)
$items_sql = "SELECT od.*, p.name, p.manufacturer 
              FROM Order_Details od 
              JOIN product p ON od.product_id = p.product_id 
              WHERE od.order_id = $order_id";
$items_res = $conn->query($items_sql);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice_#<?php echo $order_id; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold: #bc9c64;
            --black: #1a1a1a;
            --gray: #f9f9f9;
            --text-muted: #888;
        }

        * { box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; color: var(--black); line-height: 1.6; background: #e0e0e0; margin: 0; padding: 40px 0; }

        .invoice-wrapper {
            background: #fff;
            max-width: 850px;
            margin: 0 auto;
            padding: 60px;
            position: relative;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .invoice-wrapper::before {
            content: 'PRIVÉ';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-family: 'Playfair Display', serif;
            font-size: 12rem;
            color: rgba(0,0,0,0.02);
            pointer-events: none;
            z-index: 0;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid #eee;
            padding-bottom: 40px;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }

        .brand-identity h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            margin: 0;
            letter-spacing: 3px;
            font-weight: 400;
        }
        .brand-identity p {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 5px;
            margin: 5px 0 0;
            color: var(--gold);
        }

        .invoice-details { text-align: right; }
        .invoice-details h2 {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 1.8rem;
            margin: 0;
            font-weight: 400;
        }
        .invoice-details p { margin: 5px 0; font-size: 0.85rem; }

        .client-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            margin-bottom: 60px;
            position: relative;
            z-index: 1;
        }

        .info-block h3 {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--gold);
            border-bottom: 1px solid var(--gold);
            display: inline-block;
            margin-bottom: 15px;
        }
        .info-block p { font-size: 0.9rem; margin: 0; font-weight: 400; }
        .info-block strong { display: block; margin-bottom: 5px; font-size: 1.1rem; }

        table { width: 100%; border-collapse: collapse; position: relative; z-index: 1; margin-bottom: 30px;}
        thead th {
            text-align: left;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            padding: 15px 0;
            border-bottom: 2px solid var(--black);
        }
        tbody td { padding: 25px 0; border-bottom: 1px solid #f2f2f2; }

        .prod-name { font-weight: 600; font-size: 0.95rem; display: block; }
        .prod-brand { 
            font-family: 'Playfair Display', serif; 
            font-style: italic; 
            font-size: 0.8rem; 
            color: var(--gold); 
        }

        .totals-area {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            position: relative;
            z-index: 1;
        }
        .totals-table { width: 250px; }
        .total-row { display: flex; justify-content: space-between; padding: 10px 0; font-size: 0.9rem; }
        .total-row.grand-total {
            border-top: 1px solid #000;
            margin-top: 15px;
            padding-top: 15px;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .footer {
            margin-top: 80px;
            text-align: center;
            font-size: 0.7rem;
            color: var(--text-muted);
            letter-spacing: 1px;
            position: relative;
            z-index: 1;
        }

        .btn-container {
            max-width: 850px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: flex-end;
        }

        .print-btn {
            background: var(--black);
            color: #fff;
            border: none;
            padding: 15px 30px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 2px;
            cursor: pointer;
            transition: 0.3s;
        }
        .print-btn:hover { background: var(--gold); }

        @media print {
            body { background: #fff; padding: 0; }
            .invoice-wrapper { box-shadow: none; max-width: 100%; padding: 40px; }
            .btn-container, .print-btn { display: none; }
        }
    </style>
</head>
<body>

    <div class="btn-container">
        <button class="print-btn" onclick="window.print()">ЗАВАНТАЖИТИ PDF / ДРУК</button>
    </div>

    <div class="invoice-wrapper">
        <div class="header">
            <div class="brand-identity">
                <h1>BEAUTYSTORE</h1>
                <p>Privé Boutique</p>
            </div>
            <div class="invoice-details">
                <h2>Invoice</h2>
                <p><strong>№ <?php echo date('Y'); ?>-<?php echo str_pad($order_id, 5, '0', STR_PAD_LEFT); ?></strong></p>
                <p><?php echo date('d.m.Y', strtotime($order['order_date'])); ?></p>
            </div>
        </div>

        <div class="client-grid">
            <div class="info-block">
                <h3>Відправник</h3>
                <strong>BeautyStore LLC</strong>
                <p>вул. Преміальна, 1<br>Київ, 01001, Україна<br>support@prive.beauty.ua</p>
            </div>
            <div class="info-block">
                <h3>Отримувач</h3>
                <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong>
                <p><?php echo htmlspecialchars($order['address']); ?><br>
                Тел: <?php echo htmlspecialchars($order['phone_number']); ?><br>
                Email: <?php echo htmlspecialchars($order['email']); ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Опис товару</th>
                    <th style="text-align: center;">К-сть</th>
                    <th style="text-align: right;">Ціна</th>
                    <th style="text-align: right;">Всього</th>
                </tr>
            </thead>
            <tbody>
                <?php while($item = $items_res->fetch_assoc()): 
                    // Визначаємо ціну. Якщо в таблиці однієї ціни немає, пробуємо unit_price або price
                    $price = $item['price'] ?? $item['unit_price'] ?? 0;
                ?>
                <tr>
                    <td>
                        <span class="prod-brand"><?php echo htmlspecialchars($item['manufacturer']); ?></span>
                        <span class="prod-name"><?php echo htmlspecialchars($item['name']); ?></span>
                    </td>
                    <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                    <td style="text-align: right;"><?php echo number_format($price, 0, '.', ' '); ?> ₴</td>
                    <td style="text-align: right; font-weight: 600;"><?php echo number_format($price * $item['quantity'], 0, '.', ' '); ?> ₴</td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="totals-area">
            <div class="qr-placeholder">
                <svg width="80" height="80" viewBox="0 0 100 100" style="fill: #eee;">
                    <rect width="100" height="100" />
                    <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-size="10" fill="#999">QR AUTH</text>
                </svg>
            </div>
            <div class="totals-table">
                <div class="total-row">
                    <span>Підсумок:</span>
                    <span><?php echo number_format($order['total_price'], 0, '.', ' '); ?> ₴</span>
                </div>
                <div class="total-row">
                    <span>ПДВ (0%):</span>
                    <span>0 ₴</span>
                </div>
                <div class="total-row grand-total">
                    <span>ВСЬОГО:</span>
                    <span><?php echo number_format($order['total_price'], 0, '.', ' '); ?> ₴</span>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Дякуємо, що обираєте вишуканість. BeautyStore Privé — ваш провідник у світ краси.</p>
            <p style="margin-top: 20px;">&copy; <?php echo date('Y'); ?> PRIVÉ BOUTIQUE. ALL RIGHTS RESERVED.</p>
        </div>
    </div>
</body>
</html>