<?php session_start(); ?>
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<?php include 'includes/header.php'; ?>
<?php 
// ПІДКЛЮЧЕННЯ ДО БАЗИ (якщо воно не інклюдиться в header.php, розкоментуй рядок нижче)
// $conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");

// Підрахунок товарів у кошику
$total_items = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; 
// ПІДРАХУНОК ОБРАНОГО
$wishlist_count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;

// 1. Запит для Новинок (беремо останні 4 товари з картинками)
$sql_new_arrival = "
    SELECT p.*, i.image_url 
    FROM product p 
    LEFT JOIN Images i ON p.product_id = i.product_id AND i.is_primary = 1 
    ORDER BY p.product_id DESC 
    LIMIT 4";
$res_new_arrival = $conn->query($sql_new_arrival);

// 2. Запит для Пропозицій брендів (ті, що мають мітки)
$sql_brands = "
    SELECT p.*, i.image_url 
    FROM product p 
    LEFT JOIN Images i ON p.product_id = i.product_id AND i.is_primary = 1 
    WHERE p.badge IS NOT NULL AND p.badge != '' 
    LIMIT 4";
$res_brands = $conn->query($sql_brands);

// 3. Запит для Топ продажів (з картинками та підрахунком продажів)
// 3. Запит для Топ продажів (ВИПРАВЛЕНА ВЕРСІЯ)
$sql_top_sales = "
    SELECT p.*, MAX(i.image_url) as image_url, SUM(od.quantity) as total_sold 
    FROM product p 
    LEFT JOIN Images i ON p.product_id = i.product_id AND i.is_primary = 1 
    JOIN Order_Details od ON p.product_id = od.product_id 
    GROUP BY p.product_id 
    ORDER BY total_sold DESC 
    LIMIT 4";
$res_top_sales = $conn->query($sql_top_sales);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeautyStore - Головна</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&family=Playfair+Display:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a1a1a;
            --accent: #d4a373; 
            --bg-light: #fdfaf9;
            --white: #ffffff;
            --border: #ececec;
            --heart: #e74c3c;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Montserrat', sans-serif; }
        
        body {
            background-color: var(--bg-light);
            background-image: radial-gradient(var(--border) 0.5px, transparent 0.5px);
            background-size: 30px 30px;
            color: var(--primary);
            overflow-x: hidden;
        }

        /* --- ОНОВЛЕНИЙ HERO З ВІДЕО --- */
        .hero {
            position: relative;
            height: 85vh; /* Висота банера */
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            background-color: #000; /* Фон поки вантажиться відео */
            overflow: hidden;
            border-bottom: 1px solid var(--border);
        }

        .hero-video-bg {
            position: absolute;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            z-index: 1;
            transform: translate(-50%, -50%);
            object-fit: cover;
            filter: brightness(0.9); /* Трохи приглушимо яскравість для читабельності тексту */
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1); /* Дуже легкий світлий оверлей */
            z-index: 2;
        }

        .hero-content {
            position: relative;
            z-index: 3;
            max-width: 800px;
            padding: 0 20px;
        }

        .hero h1 { 
    font-family: 'Playfair Display', serif;
    font-size: 4.5rem; 
    margin-bottom: 25px; 
    font-weight: 400;
    color: var(--primary);
    line-height: 1.1;
    /* Додано тінь: м'яке біле сяйво для контрасту з відео */
    text-shadow: 0 0 15px rgba(255, 255, 255, 0.8), 0 0 5px rgba(255, 255, 255, 0.5);
}

.hero p { 
    font-size: 1.1rem; 
    color: var(--primary); 
    margin-bottom: 45px; 
    text-transform: uppercase;
    letter-spacing: 4px;
    font-weight: 500;
    /* Додано тінь для підзаголовка */
    text-shadow: 0 0 10px rgba(255, 255, 255, 0.8);
}

        .btn-collection {
            padding: 20px 55px;
            background: var(--primary);
            color: var(--white);
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 3px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: 0.4s;
            display: inline-block;
        }
        .btn-collection:hover { 
            background: var(--accent); 
            transform: translateY(-5px);
        }

        /* Categories Section */
        .categories {
            display: flex;
            flex-wrap: nowrap;
            justify-content: center;
            gap: 15px;
            padding: 80px 40px;
            margin: 0 auto;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .categories::-webkit-scrollbar { display: none; }
        
        .category-card {
            flex: 1 0 160px;
            max-width: 220px;
            background-color: var(--white);
            padding: 30px 10px;
            text-align: center;
            position: relative;
            transition: 0.4s;
            border: 1px solid var(--border);
        }
        .category-card:hover { transform: translateY(-10px); border-color: var(--accent); z-index: 10; }

        .category-card img {
            width: 70px; height: 70px;
            object-fit: cover;
            margin-bottom: 15px;
        }
        .category-card h3 { 
            font-family: 'Playfair Display', serif;
            font-size: 1rem; 
            margin-bottom: 10px; 
            white-space: nowrap;
        }

        .subcategory {
            display: none;
            position: absolute;
            top: 100%; left: 0;
            background-color: var(--white);
            padding: 20px;
            list-style: none;
            width: 220px;
            text-align: left;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            z-index: 1000;
            border: 1px solid var(--border);
        }
        .category-card:hover .subcategory { display: block; }
        .subcategory li a {
            text-decoration: none;
            color: #666;
            font-size: 0.8rem;
            display: block;
            margin-bottom: 8px;
            transition: 0.2s;
        }
        .subcategory li a:hover { color: var(--accent); padding-left: 5px; }

        /* Promo Banner */
        .promo-banner {
            background-color: var(--white);
            padding: 100px 50px;
            text-align: center;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            margin: 60px 0;
            position: relative;
        }
        .promo-banner::before {
            content: "Limited Offer";
            position: absolute;
            top: 40px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 4px;
            color: var(--accent);
        }
        .promo-banner h2 { 
            font-family: 'Playfair Display', serif; 
            font-size: 3rem; 
            font-weight: 400; 
            font-style: italic; 
            margin-bottom: 20px; 
        }
        .promo-banner p { font-size: 1rem; color: #666; max-width: 700px; margin: 0 auto 40px; line-height: 1.8; }
        .btn-outline { 
            padding: 15px 40px; border: 1px solid var(--primary); background: transparent; 
            color: var(--primary); text-decoration: none; font-size: 0.75rem; font-weight: 600; 
            text-transform: uppercase; letter-spacing: 2px; transition: 0.4s; 
        }
        .btn-outline:hover { background: var(--primary); color: var(--white); }

        /* Brand Offers / Products */
        .brand-offers { 
            padding: 100px 50px; 
            background: var(--white); 
        }

        .brand-offers h2 { 
            font-family: 'Playfair Display', serif;
            text-align: center; 
            margin-bottom: 60px; 
            font-size: 2.5rem; 
            color: var(--primary);
            font-weight: 400;
            font-style: italic;
        }

        .offers-grid { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 30px; 
            justify-content: center; 
            max-width: 1440px;
            margin: 0 auto;
        }

        .offer-card {
            flex: 1 1 240px;
            max-width: 280px;
            text-align: left;
            position: relative;
            transition: 0.4s;
            display: flex;
            flex-direction: column;
            background: white; 
            padding: 20px; 
            border-radius: 20px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.02);
        }

        .offer-card:hover { transform: translateY(-5px); }

        .offer-img-box { 
            width: 100%; 
            height: 350px; 
            overflow: hidden; 
            margin-bottom: 15px;
            background: #fdfdfd; 
            position: relative;
        }
        
        .offer-img-box img { 
            width: 100%; 
            height: 100%; 
            object-fit: contain; 
            padding: 20px;
            transition: 0.6s;
        }

        /* Кнопка Wishlist на фото (AJAX ВЕРСІЯ) */
        .wishlist-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            color: #ccc;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            z-index: 20;
            transition: 0.3s;
        }
        .wishlist-btn.active { color: var(--heart); }
        .wishlist-btn:hover { transform: scale(1.1); }

        .badge-black { 
            position: absolute; 
            top: 15px; 
            left: 0; 
            background: var(--primary); 
            color: var(--white); 
            padding: 6px 15px; 
            font-size: 9px; 
            font-weight: 700; 
            letter-spacing: 2px; 
            text-transform: uppercase;
            z-index: 10;
        }

        .sponsored-tag {
            font-size: 9px;
            color: #bbb;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            display: block;
            margin-bottom: 8px;
        }

        .offer-card h4 { 
            font-size: 14px; 
            font-weight: 500; 
            margin-bottom: 10px; 
            text-transform: uppercase; 
            color: var(--primary);
            height: 40px;
            overflow: hidden;
        }

        .rating-mini {
            font-size: 11px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .price-area {
            display: flex;
            align-items: baseline;
            gap: 12px;
        }

        .price-now { 
            font-family: 'Playfair Display', serif; 
            font-weight: 700; 
            color: var(--primary); 
            font-size: 1.4rem; 
        }

        .price-old {
            font-size: 0.9rem;
            color: #bbb;
            text-decoration: line-through;
        }

        .add-to-cart-btn {
            margin-top: auto; 
            padding: 15px 0;
            width: 100%;
            background: transparent;
            border: none;
            border-top: 1px solid var(--border);
            color: var(--primary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 2px;
            cursor: pointer;
            transition: 0.3s;
        }

        .add-to-cart-btn:hover { background: var(--primary); color: var(--white); }

        /* TOAST NOTIFICATION STYLES */
        #wishlistToast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: var(--primary);
            color: white;
            padding: 15px 30px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            z-index: 9999;
            transition: 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border: 1px solid var(--accent);
        }
        #wishlistToast.show { transform: translateX(-50%) translateY(0); }

        /* Newsletter */
        .newsletter { background: #111; padding: 100px 20px; text-align: center; color: #fff; }

    </style>
</head>
<body>

<section class="hero">
    <video autoplay muted loop playsinline class="hero-video-bg">
        <source src="video/videobanner.mp4" type="video/mp4">
        Ваш браузер не підтримує відео.
    </video>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>Відкрийте світ косметики</h1>
        <p>Якісні продукти для догляду та макіяжу для кожного</p>
        <a href="catalog.php" class="btn-collection">Переглянути колекцію</a>
    </div>
</section>

<?php
// 1. Отримуємо всі унікальні категорії та їх підкатегорії з бази
$sql_cat = "SELECT category, subcategory FROM product WHERE category IS NOT NULL AND category != '' GROUP BY category, subcategory ORDER BY category ASC";
$res_cat = $conn->query($sql_cat);

$categories_data = [];

// 2. Групуємо підкатегорії всередині кожної категорії
while($row = $res_cat->fetch_assoc()) {
    $cat_name = $row['category'];
    $sub_name = $row['subcategory'];
    
    if (!isset($categories_data[$cat_name])) {
        $categories_data[$cat_name] = [];
    }
    if ($sub_name) {
        $categories_data[$cat_name][] = $sub_name;
    }
}

// 3. Масив картинок для категорій
$category_images = [
    'Макіяж' => 'https://static.ukrinform.com/photos/2024_12/thumb_files/630_360_1733754503-802.jpg',
    'Догляд за шкірою' => 'https://permamed.com.ua/wp-content/uploads/2021/09/2-5.png',
    'Парфумерія' => 'https://cdn-azure.notinoimg.com/blog/gallery/clanky/Women_AT_714x350_top.jpg',
    'Догляд за волоссям' => 'https://bogomoletsclinic.ua/sites/default/files/styles/compressor/2000x1334/inline-images/side-view-woman-hair-slugging-night-routine-min_0.jpg?itok=L20lEURl',
    'Манікюр' => 'https://content2.rozetka.com.ua/goods/images/big/648935139.png',
    'Аксесуари' => 'https://content2.rozetka.com.ua/goods/images/big/622458524.webp',
    'Чоловікам' => 'https://content1.rozetka.com.ua/goods/images/big/466955900.jpg'
];
?>

<section class="categories">
    <?php foreach ($categories_data as $cat_title => $subcategories): ?>
        <div class="category-card">
            <img src="<?php echo $category_images[$cat_title] ?? 'https://via.placeholder.com/150?text=' . urlencode($cat_title); ?>" alt="<?php echo htmlspecialchars($cat_title); ?>">
            
            <h3><?php echo htmlspecialchars($cat_title); ?></h3>
            
            <?php if (!empty($subcategories)): ?>
                <ul class="subcategory">
                    <?php foreach ($subcategories as $sub): ?>
                        <li>
                            <a href="catalog.php?sub=<?php echo urlencode($sub); ?>">
                                <?php echo htmlspecialchars($sub); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>

<section class="promo-banner">
    <h2>Luxe Beauty Giveaway</h2>
    <p>Приєднуйтесь до нашого ексклюзивного розіграшу Beauty Box. Кожна покупка акційних товарів — це ваш шанс отримати набір преміального догляду від провідних світових брендів.</p>
    <a href="sales.php" class="btn-outline">Дізнатися більше</a>
</section>

<section class="brand-offers">
    <h2>Топ продажів</h2>
    <div class="offers-grid">
        <?php if ($res_top_sales && $res_top_sales->num_rows > 0): ?>
            <?php while($top_item = $res_top_sales->fetch_assoc()): 
                $is_fav = (isset($_SESSION['wishlist']) && in_array($top_item['product_id'], $_SESSION['wishlist'])) ? 'active' : '';
                // Перевірка наявності image_url
                $img_src = !empty($top_item['image_url']) ? $top_item['image_url'] : "img/products/" . $top_item['product_id'] . ".jpg";
            ?>
                <div class="offer-card">
                    <div class="badge-black" style="background: var(--accent);">BESTSELLER</div>
                    
                    <button class="wishlist-btn <?php echo $is_fav; ?>" onclick="toggleWishlist(this, <?php echo $top_item['product_id']; ?>)">❤</button>

                    <a href="product_details.php?id=<?php echo $top_item['product_id']; ?>" style="text-decoration: none; color: inherit;">
                        <div class="offer-img-box">
                            <img src="<?php echo $img_src; ?>" 
                                 onerror="this.src='https://via.placeholder.com/300x400?text=Beauty+Top'" 
                                 alt="<?php echo htmlspecialchars($top_item['name']); ?>">
                        </div>
                        
                        <span class="sponsored-tag">● Обрано тисячами</span>
                        
                        <div style="margin-bottom: 5px;">
                            <span style="font-size: 10px; color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                                <?php echo htmlspecialchars($top_item['manufacturer']); ?>
                            </span>
                        </div>

                        <h4><?php echo htmlspecialchars($top_item['name']); ?></h4>
                        
                        <div class="rating-mini">
                            ★★★★★ <span style="color: #999; font-size: 9px; margin-left: 5px;">ПРОДАНО: <?php echo $top_item['total_sold']; ?> шт.</span>
                        </div>

                        <div class="price-area">
                            <span class="price-now">₴<?php echo number_format($top_item['price'], 0, '.', ' '); ?></span>
                        </div>
                    </a>
                    
                    <button class="add-to-cart-btn" onclick="addToCart(event, this, <?php echo $top_item['product_id']; ?>)">
    Додати в кошик
</button>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; width: 100%; color: #999;">Найпопулярніші товари формуються...</p>
        <?php endif; ?>
    </div>
</section>

<?php include 'brand_offers.php'; ?>

<section class="brand-offers">
    <h2>Новинки</h2>
    <div class="offers-grid">
        <?php if ($res_new_arrival && $res_new_arrival->num_rows > 0): ?>
            <?php while($new_item = $res_new_arrival->fetch_assoc()): 
                $is_fav = (isset($_SESSION['wishlist']) && in_array($new_item['product_id'], $_SESSION['wishlist'])) ? 'active' : '';
                // Перевірка наявності image_url
                $new_img_src = !empty($new_item['image_url']) ? $new_item['image_url'] : "img/products/" . $new_item['product_id'] . ".jpg";
            ?>
                <div class="offer-card">
                    <div class="badge-black">NEW</div>
                    
                    <button class="wishlist-btn <?php echo $is_fav; ?>" onclick="toggleWishlist(this, <?php echo $new_item['product_id']; ?>)">❤</button>

                    <a href="product_details.php?id=<?php echo $new_item['product_id']; ?>" style="text-decoration: none; color: inherit;">
                        <div class="offer-img-box">
                            <img src="<?php echo $new_img_src; ?>" 
                                 onerror="this.src='https://via.placeholder.com/300x400?text=Beauty+Care'" 
                                 alt="<?php echo htmlspecialchars($new_item['name']); ?>">
                        </div>
                        
                        <span class="sponsored-tag">● Нове надходження</span>
                        
                        <div style="margin-bottom: 5px;">
                            <span style="font-size: 10px; color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                                <?php echo htmlspecialchars($new_item['manufacturer']); ?>
                            </span>
                        </div>

                        <h4><?php echo htmlspecialchars($new_item['name']); ?></h4>
                        
                        <div class="rating-mini">
                            ★★★★★ <span style="color: #ddd; margin-left: 5px;">(<?php echo rand(5, 50); ?>)</span>
                        </div>

                        <div class="price-area">
                            <span class="price-now">₴<?php echo number_format($new_item['price'], 0, '.', ' '); ?></span>
                            <?php if(!empty($new_item['old_price'])): ?>
                                <span class="price-old">₴<?php echo number_format($new_item['old_price'], 0, '.', ' '); ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                    
                   <button class="add-to-cart-btn" onclick="addToCart(event, this, <?php echo $new_item['product_id']; ?>)">
    Додати в кошик
</button>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; width: 100%; color: #999; padding: 40px;">Товари очікуються найближчим часом...</p>
        <?php endif; ?>
    </div>
</section>

<section class="newsletter">
    <h2 style="font-family: 'Playfair Display', serif; font-size: 2.5rem; margin-bottom: 15px;">Отримайте -10% знижки</h2>
    <p style="font-size: 0.9rem; margin-bottom: 30px; opacity: 0.7; letter-spacing: 1px;">ПРИЄДНУЙТЕСЬ ДО PRIVATE CLUB ТА ДІЗНАВАЙТЕСЬ ПРО АКЦІЇ ПЕРШИМИ</p>
    <form style="display: flex; justify-content: center; gap: 15px; max-width: 500px; margin: 0 auto;">
        <input type="email" placeholder="Ваш Email" style="flex: 1; padding: 15px; background: transparent; border: none; border-bottom: 1px solid #444; color: #fff; outline: none;">
        <button type="button" style="padding: 15px 35px; background: var(--accent); color: #fff; border: none; font-weight: 600; cursor: pointer; text-transform: uppercase; font-size: 0.7rem;">Підписатися</button>
    </form>
</section>

<?php include 'includes/footer.php'; ?>
<div id="wishlistToast">Додано в обране</div>

<?php include 'beauty-bot.php'; ?>

<script>
    // ПЕРЕВІРКА АВТОРИЗАЦІЇ
    const isLoggedIn = <?php echo isset($_SESSION['customer_id']) ? 'true' : 'false'; ?>;

    // ФУНКЦІЯ ДЛЯ ОБРАНОГО (Твоя існуюча)
    function toggleWishlist(btn, id) {
        if (!isLoggedIn) {
            window.location.href = 'login_register.php';
            return;
        }
        fetch('wishlist_add.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'error') {
                window.location.href = 'login_register.php?msg=login_required';
                return;
            }
            const toast = document.getElementById('wishlistToast');
            if (data.status === 'added') {
                btn.classList.add('active');
                toast.innerText = "ДОДАНО В ОБРАНЕ";
            } else {
                btn.classList.remove('active');
                toast.innerText = "ВИДАЛЕНО З ОБРАНОГО";
            }
            // Оновлюємо лічильник обраного
            const wishCount = document.getElementById('wish-count');
            if(wishCount) wishCount.innerText = data.count;

            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2500);
        });
    }

    // НОВА ФУНКЦІЯ ДЛЯ КОШИКА
    function addToCart(event, btn, id) {
        if(event) {
            event.preventDefault();
            event.stopPropagation();
        }

        // ПЕРЕВІРКА: Якщо не залогінений - кидаємо на логін
        if (!isLoggedIn) {
            window.location.href = 'login_register.php';
            return;
        }

        // Якщо залогінений - AJAX запит
        fetch('cart_add.php?id=' + id + '&ajax=1')
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
                return;
            }
            return response.text();
        })
        .then(data => {
            // Виводимо повідомлення
            alert("Дякуємо! Товар успішно додано до вашого кошика.");
            
            // Оновлюємо цифру кошика в меню
            const cartLinks = document.querySelectorAll('nav a, header a');
            cartLinks.forEach(link => {
                if (link.textContent.toUpperCase().includes('КОШИК')) {
                    let match = link.textContent.match(/\d+/);
                    let current = match ? parseInt(match[0]) : 0;
                    link.innerHTML = `КОШИК (<span>${current + 1}</span>)`;
                }
            });
        })
        .catch(err => console.error('Помилка кошика:', err));
    }
</script>
</body>
</html>