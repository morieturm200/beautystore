<?php
// Отримуємо номер замовлення
$order_id = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : '0000';
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Дякуємо за замовлення | BeautyStore Privé</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #000000;
            --accent: #bc9c64;
            --bg-page: #f8f8f8;
            --white: #ffffff;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        
        body { 
            background: var(--bg-page); 
            color: var(--primary); 
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .success-card {
            max-width: 550px;
            width: 100%;
            background: var(--white);
            padding: 80px 40px;
            border: 3px solid var(--primary); /* Контрастна чорна рамка як у Checkout */
            text-align: center;
            box-shadow: 20px 20px 0px rgba(0,0,0,0.05);
        }

        .success-icon {
            font-size: 50px;
            color: var(--accent);
            margin-bottom: 30px;
        }

        h1 { 
            font-family: 'Playfair Display', serif; 
            font-size: 2.5rem; 
            margin-bottom: 20px; 
            letter-spacing: -1px;
        }

        .order-number {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: var(--accent);
            background: #fdfaf5;
            display: inline-block;
            padding: 8px 15px;
            margin-bottom: 30px;
            border: 1px solid #eee;
        }

        p {
            font-size: 15px;
            line-height: 1.8;
            color: #555;
            margin-bottom: 40px;
        }

        .btn-home {
            display: inline-block;
            background: var(--primary);
            color: var(--white);
            text-decoration: none;
            padding: 20px 50px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 3px;
            border: 2px solid var(--primary);
            transition: 0.4s;
        }

        .btn-home:hover {
            background: var(--white);
            color: var(--primary);
        }

        .footer-note {
            margin-top: 50px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #ccc;
        }
    </style>
</head>
<body>

    <div class="success-card">
        <div class="success-icon">✓</div>
        
        <h1>Дякуємо!</h1>
        
        <div class="order-number">
            Замовлення #<?php echo $order_id; ?>
        </div>
        
        <p>Ваш запит на покупку успішно прийнято в обробку. <br> 
           Наш персональний менеджер зателефонує вам для підтвердження деталей протягом 15 хвилин.</p>
        
        <a href="index.php" class="btn-home">Повернутися на головну</a>
        
        <div class="footer-note">
            BeautyStore Privé Club
        </div>
    </div>

</body>
</html>