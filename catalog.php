<?php
include 'includes/header.php'; // Тут вже є $conn
include 'includes/search_logic.php'; // Підключаємо логіку пошуку ($search_where)

// 1. ОПЕРАЦІЙНА ЛОГІКА ФІЛЬТРІВ
$cat_filter = isset($_GET['cat']) ? $_GET['cat'] : '';
$sub_filter = isset($_GET['sub']) ? $_GET['sub'] : '';
$brand_filter = isset($_GET['brand']) ? $_GET['brand'] : '';
$min_price = (isset($_GET['min_price']) && $_GET['min_price'] !== '') ? (int)$_GET['min_price'] : 0;
$max_price = (isset($_GET['max_price']) && $_GET['max_price'] !== '') ? (int)$_GET['max_price'] : 50000;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Побудова запиту (Base) - ОБ'ЄДНУЄМО З ПОШУКОМ ТУТ
$sql = "SELECT p.*, 
        (SELECT i.image_url FROM Images i WHERE i.product_id = p.product_id ORDER BY i.is_primary DESC, i.image_id ASC LIMIT 1) as image_url
        FROM product p
        WHERE 1=1";

// ДОДАЄМО ПОШУКОВУ УМОВУ (якщо вона є)
if (!empty($search_where)) {
    $sql .= " AND $search_where";
}

// Фільтр по ціні (враховуємо акційну ціну в пошуку)
$sql .= " AND (CASE WHEN p.is_sale = 1 AND p.sale_price > 0 THEN p.sale_price ELSE p.price END) BETWEEN $min_price AND $max_price";

// Додаємо умови категорій та брендів
if ($cat_filter !== '') {
    $sql .= " AND p.category = '" . $conn->real_escape_string($cat_filter) . "'";
}
if ($sub_filter !== '') {
    $sql .= " AND p.subcategory = '" . $conn->real_escape_string($sub_filter) . "'";
}
if ($brand_filter !== '') {
    $sql .= " AND p.manufacturer = '" . $conn->real_escape_string($brand_filter) . "'";
}

// Сортування (враховуємо акційну ціну для коректного порядку)
switch ($sort) {
    case 'cheap': 
        $sql .= " ORDER BY (CASE WHEN p.is_sale = 1 THEN p.sale_price ELSE p.price END) ASC"; 
        break;
    case 'expensive': 
        $sql .= " ORDER BY (CASE WHEN p.is_sale = 1 THEN p.sale_price ELSE p.price END) DESC"; 
        break;
    default: 
        $sql .= " ORDER BY p.product_id DESC"; 
        break;
}

$result = $conn->query($sql);
if (!$result) {
    die("Помилка SQL: " . $conn->error);
}

// Лічильники для хедера (залишаємо логіку)
$total_items = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; 
if (isset($_SESSION['customer_id'])) {
    $c_id = $_SESSION['customer_id'];
    $wish_res = $conn->query("SELECT COUNT(*) as cnt FROM Wishlist WHERE customer_id = $c_id");
    $wishlist_count = $wish_res->fetch_assoc()['cnt'];
} else {
    $wishlist_count = 0;
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог | BeautyStore Premium</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a1a1a;
            --accent: #d4a373; 
            --bg-light: #fdfaf9;
            --white: #ffffff;
            --border: #e8e8e8;
            --heart: #e74c3c;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Montserrat', sans-serif; }
        body { background-color: var(--bg-light); color: var(--primary); }

        /* Layout */
        .catalog-container { display: flex; max-width: 1600px; margin: 40px auto; padding: 0 50px; gap: 50px; }

        /* Sidebar */
        .sidebar { width: 300px; background: var(--white); padding: 35px; border: 2px solid var(--primary); height: fit-content; position: sticky; top: 110px; }
        .filter-group { margin-bottom: 35px; }
        .filter-group h4 { font-size: 11px; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; border-bottom: 2px solid var(--primary); padding-bottom: 10px; font-weight: 700; }
        
        .cat-list { list-style: none; }
        .cat-list li { margin-bottom: 12px; }
        .cat-list a { text-decoration: none; color: #777; font-size: 13px; transition: 0.3s; display: block; }
        .cat-list a:hover, .cat-list a.active { color: var(--primary); font-weight: 600; transform: translateX(5px); }

        .price-inputs { display: flex; gap: 10px; }
        .price-inputs input { width: 100%; padding: 12px; border: 1px solid var(--border); font-size: 12px; outline: none; background: #fafafa; }
        
        .brand-select { width: 100%; padding: 12px; border: 1px solid var(--border); background: #fafafa; font-size: 13px; outline: none; }

        .apply-btn { background: var(--primary); color: white; border: none; width: 100%; padding: 18px; cursor: pointer; margin-top: 20px; font-weight: 700; text-transform: uppercase; letter-spacing: 3px; font-size: 10px; transition: 0.3s; }
        .apply-btn:hover { background: var(--accent); }

        /* Main Content */
        .main-content { flex: 1; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 50px; }
        .top-bar h1 { font-family: 'Playfair Display', serif; font-size: 2.8rem; font-weight: 400; font-style: italic; }
        
        /* Product Card */
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 35px; }
        .card { background: var(--white); border: 1px solid var(--border); transition: 0.4s; position: relative; display: flex; flex-direction: column; }
        .card:hover { border-color: var(--primary); box-shadow: 15px 15px 0px rgba(0,0,0,0.03); }
        
        .img-box { width: 100%; height: 380px; overflow: hidden; background: #fff; position: relative; border-bottom: 1px solid var(--border); }
        .card img { width: 100%; height: 100%; object-fit: contain; padding: 30px; transition: 0.7s; }
        .card:hover img { transform: scale(1.08); }

        /* Sale Badge */
        .sale-badge { position: absolute; top: 20px; left: 20px; background: var(--heart); color: white; padding: 5px 12px; font-size: 10px; font-weight: 700; z-index: 10; text-transform: uppercase; }

        /* Wishlist Button */
        .wishlist-btn { 
            position: absolute; top: 20px; right: 20px; background: white; width: 38px; height: 38px; 
            border-radius: 50%; border: none; display: flex; align-items: center; justify-content: center; 
            cursor: pointer; font-size: 18px; color: #ccc; box-shadow: 0 5px 15px rgba(0,0,0,0.08); 
            z-index: 20; transition: 0.3s; 
        }
        .wishlist-btn.active { color: var(--heart); }

        .info { padding: 25px; flex-grow: 1; }
        .mfg { font-size: 9px; color: var(--accent); text-transform: uppercase; letter-spacing: 3px; font-weight: 700; margin-bottom: 10px; }
        .name { font-size: 14px; font-weight: 500; margin-bottom: 15px; height: 40px; overflow: hidden; text-transform: uppercase; color: var(--primary); line-height: 1.4; }
        
        .price-container { display: flex; align-items: baseline; gap: 10px; }
        .price { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; }
        .price.old { font-size: 16px; color: #bbb; text-decoration: line-through; font-weight: 400; }
        .price.sale { color: var(--heart); }

        .btn-buy { 
            display: block; background: transparent; border-top: 1px solid var(--border); 
            padding: 20px; width: 100%; cursor: pointer; font-weight: 700; 
            text-transform: uppercase; font-size: 10px; letter-spacing: 2px;
            color: var(--primary); text-decoration: none; text-align: center; transition: 0.3s;
        }
        .btn-buy:hover { background: var(--primary); color: #fff; }

        /* Toast Message */
        #wishlistToast { 
            position: fixed; bottom: 40px; left: 50%; transform: translateX(-50%) translateY(150px); 
            background: var(--primary); color: white; padding: 18px 40px; font-size: 11px; 
            text-transform: uppercase; letter-spacing: 3px; z-index: 10000; transition: 0.6s cubic-bezier(0.23, 1, 0.32, 1); 
            border: 1px solid var(--accent); 
        }
        #wishlistToast.show { transform: translateX(-50%) translateY(0); }

        .newsletter { background: #111; padding: 100px 20px; text-align: center; color: #fff; margin-top: 80px; }
    </style>
</head>
<body>

<div class="catalog-container">
    <aside class="sidebar">
        <form action="catalog.php" method="GET" id="filterForm">
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
            <input type="hidden" name="cat" value="<?php echo htmlspecialchars($cat_filter); ?>">
            <input type="hidden" name="sub" value="<?php echo htmlspecialchars($sub_filter); ?>">

            <div class="filter-group">
                <h4>Категорії</h4>
                <ul class="cat-list">
                    <li><a href="catalog.php?search=<?php echo urlencode($search_query); ?>" class="<?php echo !$cat_filter ? 'active' : ''; ?>">Всі товари</a></li>
                    <?php 
                    $cats = $conn->query("SELECT DISTINCT category FROM product WHERE category IS NOT NULL");
                    while($c = $cats->fetch_assoc()): ?>
                        <li><a href="catalog.php?cat=<?php echo urlencode($c['category']); ?>&search=<?php echo urlencode($search_query); ?>" 
                               class="<?php echo $cat_filter == $c['category'] ? 'active' : ''; ?>">
                               <?php echo $c['category']; ?>
                        </a></li>
                    <?php endwhile; ?>
                </ul>
            </div>

            <?php if($cat_filter): ?>
            <div class="filter-group">
                <h4>Підкатегорії</h4>
                <ul class="cat-list">
                    <?php 
                    $subs = $conn->query("SELECT DISTINCT subcategory FROM product WHERE category='" . $conn->real_escape_string($cat_filter) . "'");
                    while($s = $subs->fetch_assoc()): if(!$s['subcategory']) continue; ?>
                        <li><a href="catalog.php?cat=<?php echo urlencode($cat_filter); ?>&sub=<?php echo urlencode($s['subcategory']); ?>&search=<?php echo urlencode($search_query); ?>"
                               class="<?php echo $sub_filter == $s['subcategory'] ? 'active' : ''; ?>">
                               <?php echo $s['subcategory']; ?>
                        </a></li>
                    <?php endwhile; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="filter-group">
                <h4>Бренд</h4>
                <select name="brand" class="brand-select" onchange="this.form.submit()">
                    <option value="">Всі бренди</option>
                    <?php 
                    $b_sql = "SELECT DISTINCT manufacturer FROM product WHERE manufacturer != ''";
                    if($cat_filter) $b_sql .= " AND category='".$conn->real_escape_string($cat_filter)."'";
                    $brands = $conn->query($b_sql);
                    while($b = $brands->fetch_assoc()): ?>
                        <option value="<?php echo $b['manufacturer']; ?>" <?php echo $brand_filter == $b['manufacturer'] ? 'selected' : ''; ?>>
                            <?php echo $b['manufacturer']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="filter-group">
                <h4>Ціна (₴)</h4>
                <div class="price-inputs">
                    <input type="number" name="min_price" value="<?php echo $min_price; ?>" placeholder="Від">
                    <input type="number" name="max_price" value="<?php echo $max_price; ?>" placeholder="До">
                </div>
            </div>

            <button type="submit" class="apply-btn">Оновити</button>
            <a href="catalog.php" style="display:block; text-align:center; margin-top:20px; font-size:9px; color:#aaa; text-decoration:none; text-transform:uppercase; letter-spacing:2px;">Скинути</a>
        </form>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1><?php echo !empty($search_query) ? 'Результати для: '.htmlspecialchars($search_query) : ($sub_filter ?: ($cat_filter ?: 'Каталог')); ?></h1>
            <select class="brand-select" style="width:auto;" onchange="const u = new URL(window.location.href); u.searchParams.set('sort', this.value); window.location.href = u.href;">
                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Новинки</option>
                <option value="cheap" <?php echo $sort == 'cheap' ? 'selected' : ''; ?>>Дешевші</option>
                <option value="expensive" <?php echo $sort == 'expensive' ? 'selected' : ''; ?>>Дорожчі</option>
            </select>
        </div>

        <div class="grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
                    $p_id = $row['product_id'];
                    $is_fav = false;
                    if (isset($_SESSION['customer_id'])) {
                        $c_id = $_SESSION['customer_id'];
                        $check_fav = $conn->query("SELECT * FROM Wishlist WHERE customer_id = $c_id AND product_id = $p_id");
                        $is_fav = ($check_fav && $check_fav->num_rows > 0);
                    }
                    $has_sale = ($row['is_sale'] && $row['sale_price'] > 0);
                ?>
                <div class="card">
                    <?php if($has_sale): ?>
                        <div class="sale-badge">Sale</div>
                    <?php endif; ?>

                    <div class="img-box">
                        <button class="wishlist-btn <?php echo $is_fav ? 'active' : ''; ?>" onclick="toggleWishlist(this, <?php echo $p_id; ?>)">❤</button>
                        <a href="product_details.php?id=<?php echo $p_id; ?>">
                            <img src="<?php echo !empty($row['image_url']) ? $row['image_url'] : 'img/products/'.$p_id.'.jpg'; ?>" onerror="this.src='https://placehold.co/400x500?text=BeautyStore'">
                        </a>
                    </div>
                    
                    <div class="info">
                        <div class="mfg"><?php echo htmlspecialchars($row['manufacturer']); ?></div>
                        <div class="name"><?php echo htmlspecialchars($row['name']); ?></div>
                        
                        <div class="price-container">
                            <?php if($has_sale): ?>
                                <span class="price sale"><?php echo number_format($row['sale_price'], 0, '.', ' '); ?> ₴</span>
                                <span class="price old"><?php echo number_format($row['price'], 0, '.', ' '); ?> ₴</span>
                            <?php else: ?>
                                <span class="price"><?php echo number_format($row['price'], 0, '.', ' '); ?> ₴</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if(isset($_SESSION['customer_id'])): ?>
                        <a href="cart_add.php?id=<?php echo $p_id; ?>" class="btn-buy">Додати в кошик</a>
                    <?php else: ?>
                        <a href="login_register.php" class="btn-buy">Увійдіть, щоб купити</a>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 100px;">
                    <h3 style="font-family:'Playfair Display'; font-size: 2rem;">Нічого не знайдено</h3>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<div id="wishlistToast">Додано в обране</div>

<section class="newsletter">
    <h2 style="font-family: 'Playfair Display', serif; font-size: 2.5rem; margin-bottom: 15px;">Отримайте -10% знижки</h2>
    <p style="font-size: 0.9rem; margin-bottom: 30px; opacity: 0.7; letter-spacing: 1px;">ПРИЄДНУЙТЕСЬ ДО PRIVATE CLUB ТА ДІЗНАВАЙТЕСЬ ПРО АКЦІЇ ПЕРШИМИ</p>
</form>
</section>

<?php include 'includes/footer.php'; ?>

<script>
    function toggleWishlist(btn, id) {
        // Відправляємо запит до обробника
        fetch('wishlist_add.php?id=' + id)
        .then(response => {
            if (!response.ok) throw new Error('Помилка мережі');
            return response.json();
        })
        .then(data => {
            // 1. ГОЛОВНА ПЕРЕВІРКА: якщо юзер гість — відправляємо на логін
            if (data.status === 'error' || data.message === 'auth_required') {
                window.location.href = 'login_register.php?msg=login_required';
                return; // Зупиняємо функцію, щоб не показувати Toast
            }

            // 2. ЯКЩО ЮЗЕР ЗАРЕЄСТРОВАНИЙ — працюємо з інтерфейсом
            const toast = document.getElementById('wishlistToast');
            const countSpan = document.getElementById('wish-count');

            if (data.status === 'added') {
                btn.classList.add('active');
                toast.innerText = "ДОДАНО В ОБРАНЕ";
            } else if (data.status === 'removed') {
                btn.classList.remove('active');
                toast.innerText = "ВИДАЛЕНО З ОБРАНОГО";
            }

            // Оновлюємо цифру в хедері (якщо є такий елемент)
            if (countSpan) {
                countSpan.innerText = data.count;
            }

            // 3. ПОКАЗУЄМО ПОВІДОМЛЕННЯ (Toast)
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 2500);
        })
        .catch(err => {
            console.error('Помилка виконання:', err);
        });
    }
</script>