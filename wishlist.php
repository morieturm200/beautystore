<?php
session_start();

// 1. ПІДКЛЮЧЕННЯ 
$conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");
if ($conn->connect_error) { die("Помилка: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

// --- ОБРОБКА: ДОДАТИ ВСЕ В КОШИК ---
if (isset($_POST['add_all_to_cart']) && !empty($_POST['product_ids'])) {
    if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
    foreach ($_POST['product_ids'] as $id) {
        $id = intval($id);
        if (!isset($_SESSION['cart'][$id])) { $_SESSION['cart'][$id] = 1; }
    }
    header("Location: cart.php");
    exit();
}

// 2. ОБРОБКА ВИДАЛЕННЯ 
if (isset($_GET['remove_id']) && isset($_SESSION['customer_id'])) {
    $prod_id = intval($_GET['remove_id']);
    $cust_id = intval($_SESSION['customer_id']);
    $stmt = $conn->prepare("DELETE FROM Wishlist WHERE customer_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $cust_id, $prod_id);
    $stmt->execute();
    header("Location: wishlist.php");
    exit();
}

// 3. ПЕРЕВІРКА АВТОРИЗАЦІЇ
$is_shared = isset($_GET['items']);
if (!isset($_SESSION['customer_id']) && !$is_shared) {
    header("Location: login_register.php?msg=auth_required");
    exit();
}

// 4. ОТРИМАННЯ ТОВАРІВ
$products = [];
$wishlist_ids = [];

if ($is_shared) {
    $wishlist_ids = explode(',', $_GET['items']);
} elseif (isset($_SESSION['customer_id'])) {
    $cust_id = intval($_SESSION['customer_id']);
    $res = $conn->query("SELECT product_id FROM Wishlist WHERE customer_id = $cust_id");
    while($row = $res->fetch_column()) { $wishlist_ids[] = $row; }
}

if (!empty($wishlist_ids)) {
    $ids_string = implode(',', array_map('intval', $wishlist_ids));
    $sql = "SELECT p.*, 
            (SELECT image_url FROM Images WHERE product_id = p.product_id ORDER BY is_primary DESC LIMIT 1) as image_url 
            FROM product p 
            WHERE p.product_id IN ($ids_string)";
    $res_products = $conn->query($sql);
    while($row = $res_products->fetch_assoc()) { $products[] = $row; }
}
?>

<?php include 'includes/header.php'; ?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Обране | BeautyStore Privé</title>
    <style>
        :root { --primary: #1a1a1a; --accent: #d4a373; --bg-light: #fdfaf9; --white: #ffffff; --border: #e8e8e8; --sale: #e74c3c; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Montserrat', sans-serif; }
        body { background-color: var(--bg-light); color: var(--primary); }
        .container { max-width: 1000px; margin: 40px auto; background: var(--white); border: 1px solid var(--border); padding: 50px; }
        .header-box { text-align: center; margin-bottom: 50px; }
        .header-box h1 { font-family: 'Playfair Display', serif; font-size: 3rem; font-style: italic; font-weight: 400; }
        .badge { color: var(--accent); font-size: 10px; letter-spacing: 4px; text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 15px; }
        .actions-bar { display: flex; justify-content: center; gap: 15px; margin-top: 25px; }
        .wish-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .wish-row { border-bottom: 1px solid var(--border); transition: 0.3s; }
        .wish-row:hover { background: #fcfcfc; }
        .cell { padding: 25px 10px; vertical-align: middle; }
        .p-img { width: 90px; height: 120px; object-fit: contain; background: #fff; }
        .mfg { font-size: 9px; font-weight: 700; text-transform: uppercase; color: var(--accent); letter-spacing: 2px; display: block; }
        .name { font-family: 'Playfair Display', serif; font-size: 1.3rem; text-decoration: none; color: var(--primary); }
        .price-box { font-family: 'Playfair Display', serif; font-size: 1.4rem; font-weight: 700; text-align: right; }
        .old-price { font-size: 0.8rem; text-decoration: line-through; color: #bbb; display: block; font-family: 'Montserrat'; }
        .sale { color: var(--sale); }
        .btn-prive { background: black; color: white; padding: 12px 25px; border: none; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; cursor: pointer; text-decoration: none; transition: 0.3s; display: inline-block; }
        .btn-prive:hover { background: var(--accent); }
        .btn-outline { background: transparent; color: black; border: 1px solid black; }
        .remove-icon { color: #ddd; cursor: pointer; font-size: 20px; border: none; background: none; transition: 0.3s; }
        .remove-icon:hover { color: var(--sale); transform: rotate(90deg); }
        #toast { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); background: black; color: white; padding: 15px 30px; font-size: 11px; display: none; letter-spacing: 1px; z-index: 9999; border: 1px solid var(--accent); }
    </style>
</head>
<body>

<div class="container">
    <div class="header-box">
        <span class="badge">Espace Privé</span>
        <?php if ($is_shared): ?>
            <h1>Колекція гостя</h1>
        <?php else: ?>
            <h1>Обране</h1>
            <div class="actions-bar">
                <button class="btn-prive btn-outline" onclick="copyLink()">Поділитися списком</button>
                <?php if (!empty($products)): ?>
                <form method="POST" style="display:inline;">
                    <?php foreach ($products as $p): ?>
                        <input type="hidden" name="product_ids[]" value="<?= $p['product_id'] ?>">
                    <?php endforeach; ?>
                    <button type="submit" name="add_all_to_cart" class="btn-prive">Додати все в кошик</button>
                </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($products)): ?>
        <table class="wish-table">
            <?php foreach ($products as $p): ?>
                <tr class="wish-row">
                    <td class="cell" style="width: 110px;">
                        <img src="<?= $p['image_url'] ?: 'img/products/default.jpg' ?>" class="p-img" onerror="this.src='https://via.placeholder.com/100x130'">
                    </td>
                    <td class="cell">
                        <span class="mfg"><?= htmlspecialchars($p['manufacturer']) ?></span>
                        <a href="product_details.php?id=<?= $p['product_id'] ?>" class="name"><?= htmlspecialchars($p['name']) ?></a>
                    </td>
                    <td class="cell price-box">
                        <?php if ($p['is_sale'] && $p['sale_price'] > 0): ?>
                            <span class="old-price"><?= number_format($p['price'], 0, '.', ' ') ?> ₴</span>
                            <span class="sale"><?= number_format($p['sale_price'], 0, '.', ' ') ?> ₴</span>
                        <?php else: ?>
                            <?= number_format($p['price'], 0, '.', ' ') ?> ₴
                        <?php endif; ?>
                    </td>
                    <td class="cell" style="text-align: right; width: 60px;">
                        <?php if (!$is_shared): ?>
                            <button class="remove-icon" onclick="if(confirm('Видалити цей товар зі списку?')) location.href='?remove_id=<?= $p['product_id'] ?>'">✕</button>
                        <?php else: ?>
                            <a href="cart_add.php?id=<?= $p['product_id'] ?>" class="btn-prive" style="padding: 8px 12px;">+</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <div style="text-align: center; padding: 60px 0;">
            <p style="color: #aaa; text-transform: uppercase; letter-spacing: 2px;">Ваш список порожній</p>
            <a href="catalog.php" class="btn-prive" style="margin-top: 30px;">До каталогу</a>
        </div>
    <?php endif; ?>
</div>

<div id="toast">✓ Посилання скопійовано у буфер</div>

<script>
    function copyLink() {
        const ids = <?= json_encode($wishlist_ids) ?>;
        // Використовуємо URLSearchParams для чистого посилання
        const link = window.location.origin + window.location.pathname + '?items=' + ids.join(',');
        navigator.clipboard.writeText(link).then(() => {
            const t = document.getElementById('toast');
            t.style.display = 'block';
            setTimeout(() => t.style.display = 'none', 2500);
        });
    }
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>