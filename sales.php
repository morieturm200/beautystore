<?php session_start(); ?>
<?php include 'includes/header.php'; ?>
<?php

// 1. Налаштування розіграшу прямо в коді
$giveaway = [
    'title' => 'The Royal Beauty Giveaway',
    'description' => 'Ваш вхідний квиток у світ високої краси. Оберіть акційний догляд або товари з позначкою Giveaway та отримайте шанс на ексклюзивний приз.',
    'end_date' => '30.04.2026'
];

// 2. Отримуємо товари (ОНОВЛЕНО: додано JOIN для зображень)
$sql = "SELECT p.*, i.image_url, 
        ROUND(((p.price - p.sale_price) / p.price) * 100) as discount_percent 
        FROM product p
        LEFT JOIN Images i ON p.product_id = i.product_id AND i.is_primary = 1
        WHERE (p.is_sale = 1 AND p.sale_price < p.price) 
        OR p.is_giveaway_participant = 1
        ORDER BY p.is_giveaway_participant DESC, discount_percent DESC";

$result = $conn->query($sql);

$total_items = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; 
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privé Club & Offers | BeautyStore</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a1a1a;
            --accent: #d4a373; /* Золото */
            --bg-light: #fdfaf9;
            --white: #ffffff;
            --border: #e8e8e8;
            --heart: #e74c3c;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Montserrat', sans-serif; }
        body { background-color: var(--bg-light); color: var(--primary); line-height: 1.6; }

        /* GIVEAWAY HERO */
        .giveaway-hero {
            background: var(--white); padding: 100px 20px; text-align: center;
            border-bottom: 1px solid var(--border);
        }
        .badge-prive { color: var(--accent); font-size: 10px; letter-spacing: 5px; text-transform: uppercase; font-weight: 700; margin-bottom: 20px; display: block; }
        .giveaway-hero h1 { font-family: 'Playfair Display', serif; font-size: 3.5rem; font-style: italic; margin-bottom: 20px; font-weight: 400; }
        .giveaway-hero p { max-width: 650px; margin: 0 auto 30px; color: #666; font-size: 1rem; }

        /* PRIZE GRID */
        .prizes-grid {
            display: flex; justify-content: center; gap: 20px; max-width: 1200px; margin: 0 auto; padding: 0 20px;
        }
        .prize-item {
            background: #fff; border: 1px solid var(--border); padding: 30px 20px; flex: 1;
            transition: 0.4s; position: relative;
        }
        .prize-item.main { border: 2px solid var(--accent); transform: scale(1.05); z-index: 2; box-shadow: 0 15px 30px rgba(0,0,0,0.05); }
        .prize-item .rank { font-size: 9px; text-transform: uppercase; color: var(--accent); font-weight: 700; letter-spacing: 2px; display: block; margin-bottom: 10px; }
        .prize-item h3 { font-family: 'Playfair Display', serif; font-size: 1.4rem; margin-bottom: 8px; }
        .prize-item p { font-size: 0.8rem; color: #888; }

        /* RULES SECTION */
        .rules-bar {
            background: var(--primary); color: #fff; padding: 60px 40px; text-align: center; margin-top: -1px;
        }
        .rules-container {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; max-width: 1100px; margin: 40px auto 0;
        }
        .rule-card span { display: block; font-size: 2.5rem; font-family: 'Playfair Display', serif; color: var(--accent); margin-bottom: 8px; opacity: 0.8; }
        .rule-card h4 { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 12px; color: var(--accent); }
        .rule-card p { font-size: 0.8rem; opacity: 0.6; line-height: 1.6; }

        /* PRODUCTS GRID */
        .container { max-width: 1300px; margin: 80px auto; padding: 0 20px; }
        .section-header { text-align: center; margin-bottom: 60px; }
        .section-header h2 { font-family: 'Playfair Display', serif; font-size: 2.5rem; font-weight: 400; }

        /* НОВИЙ ДИЗАЙН КАРТОК (КОМПАКТНІШИЙ) */
        .product-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); 
            gap: 25px; 
        }
        .product-card { 
            background: var(--white); 
            position: relative; 
            transition: 0.3s; 
            border: 1px solid #f0f0f0; 
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .product-card:hover { 
            border-color: var(--accent); 
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        .discount-tag { position: absolute; top: 15px; left: 15px; background: var(--accent); color: #fff; padding: 4px 10px; font-weight: 700; z-index: 10; font-size: 11px; letter-spacing: 0.5px; }
        .giveaway-tag { position: absolute; top: 15px; right: 15px; background: var(--primary); color: var(--accent); padding: 4px 10px; font-weight: 700; z-index: 10; font-size: 8px; letter-spacing: 1px; border: 1px solid var(--accent); }
        
        .img-box { 
            width: 100%; 
            height: 320px; /* Зменшено висоту */
            background: #fff; 
            overflow: hidden; 
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }
        .product-card img { 
            max-width: 100%; 
            max-height: 100%; 
            object-fit: contain; /* Щоб бачити весь товар */
            transition: 0.5s ease; 
        }
        .product-card:hover img { transform: scale(1.05); }

        .card-details { 
            padding: 20px 15px; 
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            text-align: center;
        }
        .brand { font-size: 9px; letter-spacing: 2px; text-transform: uppercase; color: #bbb; margin-bottom: 8px; display: block; font-weight: 700; }
        .name { 
            font-size: 13px; 
            margin-bottom: 15px; 
            height: 38px; 
            overflow: hidden; 
            color: var(--primary); 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .prices { 
            display: flex; 
            align-items: center; 
            justify-content: center;
            gap: 10px; 
            margin-top: auto;
        }
        .price-new { font-family: 'Playfair Display', serif; font-size: 20px; font-weight: 700; }
        .price-new.sale { color: var(--heart); }
        .price-old { text-decoration: line-through; color: #ccc; font-size: 13px; }

        .buy-btn {
            display: block; width: 100%; padding: 18px; text-align: center; background: #fafafa;
            border-top: 1px solid var(--border); color: var(--primary); text-transform: uppercase;
            font-size: 10px; letter-spacing: 1.5px; font-weight: 700; text-decoration: none; transition: 0.3s;
        }
        .buy-btn:hover { background: var(--primary); color: var(--white); }

        /* Toast */
        .toast {
            position: fixed; bottom: 30px; right: 30px; background: var(--primary); color: #fff;
            padding: 20px 40px; transform: translateY(150%); transition: 0.5s; z-index: 9999;
            font-size: 12px; text-transform: uppercase; letter-spacing: 1px; border: 1px solid var(--accent);
        }
        .toast.show { transform: translateY(0); }

        @media (max-width: 992px) {
            .prizes-grid, .rules-container { grid-template-columns: 1fr; flex-direction: column; }
            .prize-item.main { transform: none; }
            .giveaway-hero h1 { font-size: 2.5rem; }
            .product-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); }
        }
    </style>
</head>
<body>

<section class="giveaway-hero">
    <span class="badge-prive">The Royal Beauty Giveaway</span>
    <h1><?php echo $giveaway['title']; ?></h1>
    <p><?php echo $giveaway['description']; ?></p>
    
    <div class="prizes-grid">
        <div class="prize-item">
            <span class="rank">II місце</span>
            <h3>Gold Selection</h3>
            <p>Набір професійної косметики для вечірніх образів.</p>
        </div>
        <div class="prize-item main">
            <span class="rank">I місце (Головний приз)</span>
            <h3>Grand Luxe Box</h3>
            <p>Стайлер Dyson Airwrap та преміальний догляд.</p>
        </div>
        <div class="prize-item">
            <span class="rank">III місце</span>
            <h3>Silver Mist</h3>
            <p>Сет органічної косметики для SPA-ритуалів.</p>
        </div>
    </div>
</section>

<section class="rules-bar">
    <h2 style="font-family:'Playfair Display'; font-size: 2rem; font-style:italic; font-weight:400;">Умови участі</h2>
    <div class="rules-container">
        <div class="rule-card">
            <span>01</span>
            <h4>Придбайте товар</h4>
            <p>Виберіть продукт із розділу Special Offers або міткою Giveaway.</p>
        </div>
        <div class="rule-card">
            <span>02</span>
            <h4>Реєстрація</h4>
            <p>Ваше замовлення автоматично стає учасником розіграшу.</p>
        </div>
        <div class="rule-card">
            <span>03</span>
            <h4>Прямий ефір</h4>
            <p>Переможці будуть обрані в Instagram <strong><?php echo $giveaway['end_date']; ?></strong>.</p>
        </div>
    </div>
</section>

<div class="container">
    <div class="section-header">
        <h2>Special Offers</h2>
    </div>
    
    <div class="product-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $has_discount = ($row['is_sale'] && $row['sale_price'] < $row['price']);
                $display_price = $has_discount ? $row['sale_price'] : $row['price'];
                $img_src = !empty($row['image_url']) ? $row['image_url'] : "img/products/" . $row['product_id'] . ".jpg";
            ?>
                <div class="product-card">
                    <?php if ($has_discount): ?>
                        <div class="discount-tag">-<?php echo $row['discount_percent']; ?>%</div>
                    <?php endif; ?>

                    <?php if ($row['is_giveaway_participant']): ?>
                        <div class="giveaway-tag">🎁 GIVEAWAY</div>
                    <?php endif; ?>

                    <a href="product_details.php?id=<?php echo $row['product_id']; ?>" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; height: 100%;">
                        <div class="img-box">
                            <img src="<?php echo $img_src; ?>" onerror="this.src='https://via.placeholder.com/300x400?text=Beauty'">
                        </div>
                        <div class="card-details">
                            <span class="brand"><?php echo htmlspecialchars($row['manufacturer']); ?></span>
                            <h4 class="name"><?php echo htmlspecialchars($row['name']); ?></h4>
                            <div class="prices">
                                <span class="price-new <?php echo $has_discount ? 'sale' : ''; ?>"><?php echo number_format($display_price, 0, '.', ' '); ?> ₴</span>
                                <?php if ($has_discount): ?>
                                    <span class="price-old"><?php echo number_format($row['price'], 0, '.', ' '); ?> ₴</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <a href="cart_add.php?id=<?php echo $row['product_id']; ?>&added=1" class="buy-btn">
                        <?php echo $row['is_giveaway_participant'] ? 'Взяти участь' : 'У кошик'; ?>
                    </a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; grid-column: 1/-1; color: #999; padding: 100px;">Пропозиції оновлюються.</p>
        <?php endif; ?>
    </div>
</div>

<div id="toast" class="toast">
    ✓ Додано до розіграшу! <a href="cart.php" style="color:var(--accent); text-decoration:none; margin-left:10px;">Кошик</a>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('added')) {
        const t = document.getElementById('toast');
        t.classList.add('show');
        setTimeout(() => {
            t.classList.remove('show');
            const cleanUrl = window.location.pathname + window.location.search.replace(/[?&]added=1/, '');
            window.history.replaceState({}, document.title, cleanUrl);
        }, 4000);
    }
</script>

</body>
</html>