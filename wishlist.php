<?php session_start(); ?>
<?php include 'includes/header.php'; ?>
<?php

$conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");
if ($conn->connect_error) { die("Помилка: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

if (isset($_GET['remove_id']) && isset($_SESSION['user_id'])) {
    $prod_id = intval($_GET['remove_id']);
    $cust_id = intval($_SESSION['user_id']);
    $stmt = $conn->prepare("DELETE FROM Wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $cust_id, $prod_id);
    $stmt->execute();
    header("Location: wishlist.php");
    exit();
}


if (isset($_POST['add_all_to_cart']) && !empty($_POST['product_ids']) && isset($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);


    $ord_stmt = $conn->prepare("
        SELECT o.order_id 
        FROM orders o
        JOIN Order_Details od ON o.order_id = od.order_id
        WHERE o.user_id = ? AND od.status = 'cart'
        LIMIT 1
    ");
    $ord_stmt->bind_param("i", $user_id);
    $ord_stmt->execute();
    $ord_res = $ord_stmt->get_result();

    if ($ord_res->num_rows > 0) {

        $order_id = $ord_res->fetch_assoc()['order_id'];
    } else {

        $new_ord = $conn->prepare("INSERT INTO orders (user_id, order_date) VALUES (?, NOW())");
        $new_ord->bind_param("i", $user_id);
        $new_ord->execute();
        $order_id = $conn->insert_id;
    }

    foreach ($_POST['product_ids'] as $pid) {
        $pid = intval($pid);

        $pr_stmt = $conn->prepare("SELECT price FROM product WHERE product_id = ?");
        $pr_stmt->bind_param("i", $pid);
        $pr_stmt->execute();
        $price = $pr_stmt->get_result()->fetch_assoc()['price'] ?? 0;

        $chk = $conn->prepare("SELECT order_details_id FROM Order_Details WHERE order_id = ? AND product_id = ? AND status = 'cart'");
        $chk->bind_param("ii", $order_id, $pid);
        $chk->execute();

        if ($chk->get_result()->num_rows > 0) {
            $conn->query("UPDATE Order_Details SET quantity = quantity + 1 WHERE order_id = $order_id AND product_id = $pid AND status = 'cart'");
        } else {
            $ins = $conn->prepare("INSERT INTO Order_Details (order_id, product_id, quantity, unit_price, status) VALUES (?, ?, 1, ?, 'cart')");
            $ins->bind_param("iid", $order_id, $pid, $price);
            $ins->execute();
        }
    }

    header("Location: cart.php");
    exit();
}

$is_shared = isset($_GET['items']);
if (!isset($_SESSION['user_id']) && !$is_shared) {
    header("Location: login_register.php?msg=auth_required");
    exit();
}

$products    = [];
$wishlist_ids = [];

if ($is_shared) {
    $wishlist_ids = array_map('intval', explode(',', $_GET['items']));
} elseif (isset($_SESSION['user_id'])) {
    $cust_id = intval($_SESSION['user_id']);
    $res = $conn->query("SELECT product_id FROM Wishlist WHERE user_id = $cust_id ORDER BY wishlist_id DESC");
    while ($row = $res->fetch_column()) { $wishlist_ids[] = $row; }
}

if (!empty($wishlist_ids)) {
    $ids_string = implode(',', array_map('intval', $wishlist_ids));
    $sql = "SELECT p.*, 
            (SELECT image_url FROM Images WHERE product_id = p.product_id ORDER BY is_primary DESC LIMIT 1) as image_url 
            FROM product p 
            WHERE p.product_id IN ($ids_string)";
    $res_products = $conn->query($sql);
    while ($row = $res_products->fetch_assoc()) { $products[] = $row; }
}


$wishlist_total = array_sum(array_column($products, 'price'));
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Обране | BeautyStore Privé</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --gold: #c5a059;
            --gold-light: #e8d5a3;
            --black: #161616;
            --white: #ffffff;
            --bg: #fdfaf8;
            --border: #eeebe6;
            --text-muted: #999;
            --sale: #c0392b;
            --stock-low: #e67e22;
            --stock-out: #c0392b;
            --stock-ok: #27ae60;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Montserrat', sans-serif; background: var(--bg); color: var(--black); min-height: 100vh; }


        .wish-hero {
            background: var(--black);
            color: var(--white);
            padding: 70px 40px 60px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .wish-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 60% 0%, rgba(197,160,89,0.18) 0%, transparent 60%);
        }
        .wish-hero .eyebrow {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 8px;
            text-transform: uppercase;
            color: var(--gold);
            display: block;
            margin-bottom: 18px;
        }
        .wish-hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 400;
            font-style: italic;
            line-height: 1.15;
            position: relative;
        }
        .wish-hero .meta {
            margin-top: 18px;
            font-size: 12px;
            color: rgba(255,255,255,0.45);
            letter-spacing: 2px;
            text-transform: uppercase;
            position: relative;
        }

        .wish-toolbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 0 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            min-height: 64px;
        }
        .toolbar-left {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .toolbar-right { display: flex; gap: 12px; align-items: center; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 22px;
            font-family: 'Montserrat', sans-serif;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        .btn-dark   { background: var(--black); color: var(--white); }
        .btn-dark:hover { background: var(--gold); color: var(--black); }
        .btn-outline { background: transparent; color: var(--black); border: 1px solid var(--black); }
        .btn-outline:hover { background: var(--black); color: var(--white); }
        .btn-gold   { background: var(--gold); color: var(--black); }
        .btn-gold:hover { background: var(--black); color: var(--gold); }

 
        .wish-layout {
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 40px 100px;
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 40px;
            align-items: start;
        }

        .wish-item {
            background: var(--white);
            border: 1px solid var(--border);
            display: grid;
            grid-template-columns: 110px 1fr auto;
            gap: 0;
            margin-bottom: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
        }
        .wish-item:hover {
            border-color: var(--gold);
            box-shadow: 0 8px 30px rgba(0,0,0,0.06);
        }
        .wish-item.out-of-stock { opacity: 0.55; }

        .item-img-wrap {
            background: #f8f5f0;
            border-right: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            min-height: 140px;
        }
        .item-img { width: 80px; height: 110px; object-fit: contain; }

        .item-body { padding: 22px 25px; display: flex; flex-direction: column; justify-content: space-between; }
        .item-brand {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: var(--gold);
            margin-bottom: 7px;
        }
        .item-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            color: var(--black);
            text-decoration: none;
            line-height: 1.35;
            margin-bottom: 12px;
            display: block;
        }
        .item-name:hover { color: var(--gold); }

      
        .stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 4px 10px;
            border-radius: 2px;
            width: fit-content;
        }
        .stock-badge.ok  { background: #edfaf3; color: var(--stock-ok); }
        .stock-badge.low { background: #fef3e7; color: var(--stock-low); }
        .stock-badge.out { background: #fdecea; color: var(--stock-out); }
        .stock-badge i   { font-size: 8px; }

        .item-actions {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-end;
            padding: 22px 20px 22px 0;
            gap: 12px;
        }

        .price-wrap { text-align: right; }
        .price-old  { font-size: 11px; text-decoration: line-through; color: #bbb; display: block; margin-bottom: 3px; }
        .price-curr {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--black);
        }
        .price-curr.on-sale { color: var(--sale); }

        .action-row { display: flex; gap: 8px; align-items: center; }

        .btn-cart {
            background: var(--black);
            color: var(--white);
            border: none;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            flex-shrink: 0;
        }
        .btn-cart:hover { background: var(--gold); color: var(--black); }
        .btn-cart.disabled { background: #e0e0e0; color: #aaa; cursor: not-allowed; pointer-events: none; }

        .btn-remove {
            background: none;
            border: 1px solid var(--border);
            color: #ccc;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
            flex-shrink: 0;
        }
        .btn-remove:hover { border-color: var(--sale); color: var(--sale); }

     
        .wish-sidebar { position: sticky; top: 100px; }

        .sidebar-card {
            background: var(--white);
            border: 1px solid var(--border);
            padding: 35px;
            margin-bottom: 20px;
        }
        .sidebar-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 400;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .summary-row.total {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            color: var(--black);
            font-weight: 700;
        }
        .summary-row.total span:last-child {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
        }

        .sidebar-card.share-card { background: var(--black); color: var(--white); border-color: var(--black); }
        .share-card .sidebar-title { color: var(--white); border-color: rgba(255,255,255,0.1); }
        .share-card p { font-size: 12px; color: rgba(255,255,255,0.5); line-height: 1.7; margin-bottom: 20px; }

       
        .wish-empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 100px 40px;
            background: var(--white);
            border: 1px solid var(--border);
        }
        .wish-empty i { font-size: 3rem; color: #ddd; margin-bottom: 25px; display: block; }
        .wish-empty h2 { font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 400; margin-bottom: 12px; }
        .wish-empty p { color: var(--text-muted); font-size: 13px; letter-spacing: 1px; margin-bottom: 30px; }

    
        #toast {
            position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(20px);
            background: var(--black); color: var(--white);
            padding: 16px 30px; font-size: 11px; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase;
            border-left: 3px solid var(--gold);
            opacity: 0; transition: all 0.4s ease; z-index: 9999;
            pointer-events: none;
        }
        #toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

        @media (max-width: 900px) {
            .wish-layout { grid-template-columns: 1fr; padding: 0 20px 80px; }
            .wish-toolbar { padding: 0 20px; flex-wrap: wrap; gap: 10px; padding: 15px 20px; }
            .wish-item { grid-template-columns: 90px 1fr; }
            .item-actions { display: none; }
        }
    </style>
</head>
<body>

<div class="wish-hero">
    <span class="eyebrow">Espace Privé</span>
    <?php if ($is_shared): ?>
        <h1>Колекція гостя</h1>
    <?php else: ?>
        <h1>Моє обране</h1>
    <?php endif; ?>
    <p class="meta"><?= count($products) ?> товар<?= count($products) == 1 ? '' : (count($products) < 5 ? 'и' : 'ів') ?></p>
</div>

<?php if (!$is_shared && !empty($products)): ?>
<div class="wish-toolbar">
    <span class="toolbar-left">Обрано <?= count($products) ?> позицій</span>
    <div class="toolbar-right">
        <button class="btn btn-outline" onclick="copyLink()">
            <i class="fa-solid fa-share-nodes"></i> Поділитись
        </button>
        <form method="POST" style="margin:0;">
            <?php foreach ($products as $p): ?>
                <input type="hidden" name="product_ids[]" value="<?= $p['product_id'] ?>">
            <?php endforeach; ?>
            <button type="submit" name="add_all_to_cart" class="btn btn-gold">
                <i class="fa-solid fa-bag-shopping"></i> Додати все в кошик
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="wish-layout">

    <?php if (!empty($products)): ?>

    <div class="wish-items">
        <?php foreach ($products as $p):
            $img = !empty($p['image_url']) ? $p['image_url'] : 'img/products/default.jpg';
            $has_sale = !empty($p['old_price']) && $p['old_price'] > $p['price'];
            $stock = intval($p['stock'] ?? 99);

            if ($stock === 0)    { $stock_class = 'out'; $stock_label = 'Немає в наявності'; $stock_icon = 'fa-xmark'; }
            elseif ($stock < 5)  { $stock_class = 'low'; $stock_label = "Залишилось $stock шт"; $stock_icon = 'fa-fire'; }
            else                 { $stock_class = 'ok';  $stock_label = 'В наявності'; $stock_icon = 'fa-check'; }
        ?>
        <div class="wish-item <?= $stock === 0 ? 'out-of-stock' : '' ?>">
            <div class="item-img-wrap">
                <img src="<?= htmlspecialchars($img) ?>" class="item-img"
                     onerror="this.src='https://via.placeholder.com/80x110?text=Beauty'">
            </div>

            <div class="item-body">
                <div>
                    <div class="item-brand"><?= htmlspecialchars($p['manufacturer'] ?? '') ?></div>
                    <a href="product_details.php?id=<?= $p['product_id'] ?>" class="item-name">
                        <?= htmlspecialchars($p['name']) ?>
                    </a>
                </div>
                <span class="stock-badge <?= $stock_class ?>">
                    <i class="fa-solid <?= $stock_icon ?>"></i>
                    <?= $stock_label ?>
                </span>
            </div>

            <div class="item-actions">
                <div class="price-wrap">
                    <?php if ($has_sale): ?>
                        <span class="price-old"><?= number_format($p['old_price'], 0, '.', ' ') ?> ₴</span>
                        <span class="price-curr on-sale"><?= number_format($p['price'], 0, '.', ' ') ?> ₴</span>
                    <?php else: ?>
                        <span class="price-curr"><?= number_format($p['price'], 0, '.', ' ') ?> ₴</span>
                    <?php endif; ?>
                </div>

                <div class="action-row">
                    <?php if (!$is_shared): ?>
                        <?php if ($stock > 0): ?>
                            <a href="cart_add.php?id=<?= $p['product_id'] ?>"
                               class="btn-cart"
                               onclick="handleCartAdd(event, this, <?= $p['product_id'] ?>)"
                               title="Додати в кошик">
                                <i class="fa-solid fa-bag-shopping"></i>
                            </a>
                        <?php else: ?>
                            <span class="btn-cart disabled" title="Немає в наявності">
                                <i class="fa-solid fa-bag-shopping"></i>
                            </span>
                        <?php endif; ?>

                        <button class="btn-remove"
                                onclick="confirmRemove(<?= $p['product_id'] ?>)"
                                title="Видалити зі списку">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    <?php else: ?>
                        <?php if ($stock > 0): ?>
                            <a href="cart_add.php?id=<?= $p['product_id'] ?>" class="btn-cart" title="Додати в кошик">
                                <i class="fa-solid fa-bag-shopping"></i>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="wish-sidebar">
        <div class="sidebar-card">
            <h3 class="sidebar-title">Підсумок</h3>
            <div class="summary-row">
                <span>Товарів</span>
                <span><?= count($products) ?></span>
            </div>
            <?php
                $in_stock_count = count(array_filter($products, fn($p) => intval($p['stock'] ?? 99) > 0));
                $out_count = count($products) - $in_stock_count;
            ?>
            <?php if ($out_count > 0): ?>
            <div class="summary-row">
                <span>Немає в наявності</span>
                <span><?= $out_count ?></span>
            </div>
            <?php endif; ?>
            <div class="summary-row total">
                <span>Загалом</span>
                <span><?= number_format($wishlist_total, 0, '.', ' ') ?> ₴</span>
            </div>

            <?php if (!$is_shared && $in_stock_count > 0): ?>
            <form method="POST" style="margin-top: 25px;">
                <?php foreach ($products as $p): if (intval($p['stock'] ?? 99) > 0): ?>
                    <input type="hidden" name="product_ids[]" value="<?= $p['product_id'] ?>">
                <?php endif; endforeach; ?>
                <button type="submit" name="add_all_to_cart" class="btn btn-dark" style="width:100%; justify-content:center;">
                    <i class="fa-solid fa-bag-shopping"></i>
                    Додати все в кошик
                </button>
            </form>
            <?php endif; ?>
        </div>

        <?php if (!$is_shared): ?>
        <div class="sidebar-card share-card">
            <h3 class="sidebar-title">Поділитись</h3>
            <p>Поділіться своїм списком з подругою або збережіть посилання на майбутнє.</p>
            <button class="btn btn-gold" style="width:100%; justify-content:center;" onclick="copyLink()">
                <i class="fa-solid fa-link"></i> Скопіювати посилання
            </button>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="wish-empty">
        <i class="fa-regular fa-heart"></i>
        <h2>Список порожній</h2>
        <p>Додайте товари що вас зацікавили</p>
        <a href="catalog.php" class="btn btn-dark">До каталогу</a>
    </div>
    <?php endif; ?>

</div>

<div id="toast">✓ Додано до кошика</div>

<script>
    const wishlistIds = <?= json_encode(array_map('intval', $wishlist_ids)) ?>;


    function handleCartAdd(e, btn, productId) {
        e.preventDefault();
        const icon = btn.querySelector('i');
        icon.className = 'fa-solid fa-spinner fa-spin';

        fetch(`cart_add.php?id=${productId}&ajax=1`)
            .then(r => {
                if (r.status === 401) { window.location.href = 'login_register.php'; return; }
                return r.text();
            })
            .then(() => {
                icon.className = 'fa-solid fa-check';
                btn.style.background = '#27ae60';
                showToast('Додано до кошика');

                setTimeout(() => {
                    icon.className = 'fa-solid fa-bag-shopping';
                    btn.style.background = '';
                }, 2000);

                const counter = document.querySelector('.cart-count, #cart-count, [data-cart-count]');
                if (counter) {
                    fetch('cart_add.php?id=' + productId + '&ajax=1')
                        .then(r => r.text())
                        .then(n => { if (!isNaN(n)) counter.textContent = n; });
                }
            })
            .catch(() => {
                window.location.href = `cart_add.php?id=${productId}`;
            });
    }

    function confirmRemove(productId) {
        if (confirm('Видалити товар зі списку обраного?')) {
            window.location.href = '?remove_id=' + productId;
        }
    }

    function copyLink() {
    const link = window.location.origin + window.location.pathname + '?items=' + wishlistIds.join(',');
    
  
    const textarea = document.createElement('textarea');
    textarea.value = link;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();
    
    try {
        document.execCommand('copy');
        showToast('Посилання скопійовано');
    } catch (err) {

        prompt('Скопіюйте посилання:', link);
    }
    
    document.body.removeChild(textarea);
}

    function showToast(msg) {
        const t = document.getElementById('toast');
        t.textContent = '✓ ' + msg;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 2800);
    }
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>
