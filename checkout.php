<?php
session_start();

$conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");
$total = 0;

if (empty($_SESSION['cart'])) {
    header("Location: catalog.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформлення | BeautyStore Privé</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #000000;
            --accent: #bc9c64; 
            --bg-page: #f8f8f8;
            --white: #ffffff;
            --border: #000000;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        
        body { 
            background: var(--bg-page); 
            color: var(--primary); 
            padding: 60px 20px;
            letter-spacing: -0.01em;
        }

        .checkout-container {
            max-width: 650px;
            margin: 0 auto;
            background: var(--white);
            padding: 60px;
            border: 3px solid var(--primary); 
        }

        .logo-box { text-align: center; margin-bottom: 50px; }
        .logo-box a { 
            font-family: 'Playfair Display', serif; 
            font-size: 2.5rem; 
            color: var(--primary); 
            text-decoration: none;
        }

        .step-marker {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 40px 0 25px 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .step-marker::after { content: ""; flex: 1; height: 2px; background: var(--primary); }

        .form-field { margin-bottom: 25px; }
        
        .form-field label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .form-field input, .form-field select, .form-field textarea {
            width: 100%;
            padding: 16px;
            border: 2px solid #eeeeee; /* Світла рамка, яка стає чорною при фокусі */
            background: #fff;
            font-size: 15px;
            font-weight: 500;
            color: var(--primary);
            outline: none;
            transition: all 0.2s ease;
            border-radius: 0;
        }

        .form-field input:focus, .form-field select:focus {
            border-color: var(--primary);
            background: #fafafa;
        }

        /* Підсумок замовлення */
        .order-summary {
            background: #000;
            color: #fff;
            padding: 40px;
            margin: 50px 0;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 12px;
            opacity: 0.8;
        }

        .total-line {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .total-line span { font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--accent); }
        .total-line b { font-family: 'Playfair Display', serif; font-size: 2.5rem; color: #fff; }

        .submit-btn {
            width: 100%;
            background: var(--primary);
            color: var(--white);
            border: 2px solid var(--primary);
            padding: 25px;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 4px;
            cursor: pointer;
            transition: 0.4s;
        }

        .submit-btn:hover {
            background: var(--white);
            color: var(--primary);
        }

        .back-nav { text-align: center; margin-top: 40px; }
        .back-nav a { font-size: 11px; font-weight: 600; color: #999; text-decoration: none; text-transform: uppercase; letter-spacing: 1px; }
        .back-nav a:hover { color: var(--primary); }
    </style>
</head>
<body>

<div class="checkout-container">
    <div class="logo-box">
        <a href="index.php">BeautyStore</a>
    </div>

    <form action="process_order.php" method="POST">
        
        <div class="step-marker">01. ПЕРСОНАЛЬНІ ДАНІ</div>
        <div class="form-field"><label>Ім'я</label><input type="text" name="fname" required></div>
        <div class="form-field"><label>Прізвище</label><input type="text" name="lname" required></div>
        <div class="form-field"><label>Електронна пошта</label><input type="email" name="email" required></div>
        <div class="form-field"><label>Телефон</label><input type="tel" name="phone" placeholder="+380" required></div>

        <div class="step-marker">02. ДОСТАВКА ТА ОПЛАТА</div>
        <div class="form-field">
            <label>Область</label>
            <select name="region" required>
                <option value="">Оберіть область...</option>
                <option value="kyiv">Київська</option>
                <option value="lviv">Львівська</option>
                <option value="odesa">Одеська</option>
                <option value="kharkiv">Харківська</option>
                <option value="dnipro">Дніпропетровська</option>
            </select>
        </div>
        <div class="form-field"><label>Місто</label><input type="text" name="city" required></div>
        <div class="form-field"><label>Адреса або № відділення НП</label><input type="text" name="address" required></div>
        
        <div class="form-field">
            <label>Спосіб оплати</label>
            <select name="payment" required>
                <option value="cash">Оплата при отриманні</option>
                <option value="card">Картою на сайті (Visa/Mastercard)</option>
                <option value="privat24">Приват24</option>
            </select>
        </div>

        <div class="form-field">
            <label>Коментар (необов'язково)</label>
            <textarea name="comment" rows="2" placeholder="Наприклад: кодовий замок або час доставки..."></textarea>
        </div>

        <div class="order-summary">
            <?php 
            foreach ($_SESSION['cart'] as $id => $qty): 
                $res = $conn->query("SELECT name, price FROM product WHERE product_id = $id");
                if ($res && $p = $res->fetch_assoc()):
                    $sub = $p['price'] * $qty; $total += $sub;
            ?>
            <div class="order-item">
                <span><?php echo htmlspecialchars($p['name']); ?> (x<?php echo $qty; ?>)</span>
                <b><?php echo number_format($sub, 0, '.', ' '); ?> ₴</b>
            </div>
            <?php endif; endforeach; ?>

            <div class="total-line">
                <span>До сплати</span>
                <b><?php echo number_format($total, 0, '.', ' '); ?> ₴</b>
            </div>
        </div>

        <button type="submit" class="submit-btn">ОФОРМИТИ ЗАМОВЛЕННЯ</button>
    </form>

    <div class="back-nav">
        <a href="cart.php">← Повернутися до кошика</a>
    </div>
</div>

</body>
</html>