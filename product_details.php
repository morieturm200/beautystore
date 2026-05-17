<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");

if ($conn->connect_error) {
    die("Помилка підключення: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (isset($_SESSION['user_id'])) { 
        $user_id = $_SESSION['user_id'];
        $rating = intval($_POST['rating']);
        $comment = trim($_POST['comment']);
        
        $stmt_rev = $conn->prepare("INSERT INTO Reviews (product_id, user_id, rating, comment, review_date) VALUES (?, ?, ?, ?, NOW())");
        $stmt_rev->bind_param("iiis", $product_id, $user_id, $rating, $comment);
        
        if($stmt_rev->execute()) {
            header("Location: product_details.php?id=$product_id&status=success#reviews");
            exit;
        }
    } else {
        $error_auth = "Будь ласка, увійдіть в систему, щоб залишити відгук.";
    }
}

$sql_product = "SELECT p.*, i.image_url, c.name as category_name 
                FROM product p 
                LEFT JOIN Images i ON p.product_id = i.product_id AND i.is_primary = 1 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                WHERE p.product_id = $product_id";

$res_product = $conn->query($sql_product);
$product = $res_product->fetch_assoc();

if (!$product) { 
    echo "<div style='padding:100px; text-align:center;'><h1>Товар відсутній</h1></div>";
    exit; 
}

if (!isset($_SESSION['recently_viewed'])) {
    $_SESSION['recently_viewed'] = [];
}
if (($key = array_search($product_id, $_SESSION['recently_viewed'])) !== false) {
    unset($_SESSION['recently_viewed'][$key]); 
}
array_unshift($_SESSION['recently_viewed'], $product_id); 
if (count($_SESSION['recently_viewed']) > 6) {
    array_pop($_SESSION['recently_viewed']); 
}

$sql_all_imgs = "SELECT image_url FROM Images WHERE product_id = $product_id ORDER BY is_primary DESC";
$res_all_imgs = $conn->query($sql_all_imgs);
$gallery_images = [];
while($img_row = $res_all_imgs->fetch_assoc()) {
    $gallery_images[] = $img_row['image_url'];
}
if(empty($gallery_images)) {
    $gallery_images[] = "img/products/" . $product['product_id'] . ".jpg";
}

$total_items = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; 

$user_wishlist_ids = [];
if (isset($_SESSION['user_id'])) {
    $c_id = $_SESSION['user_id'];
    $check_fav = $conn->query("SELECT * FROM Wishlist WHERE user_id = $c_id AND product_id = $product_id");
    $is_fav_status = ($check_fav && $check_fav->num_rows > 0);
    
    $wish_res = $conn->query("SELECT product_id FROM Wishlist WHERE user_id = $c_id");
    if ($wish_res) {
        $wishlist_count = $wish_res->num_rows;
        while($w_row = $wish_res->fetch_assoc()) {
            $user_wishlist_ids[] = $w_row['product_id'];
        }
    } else {
        $wishlist_count = 0;
    }
} else {
    $is_fav_status = false;
    $wishlist_count = 0; 
}


$sql_chars = "SELECT * FROM characteristics WHERE product_id = $product_id ORDER BY characteristic_id ASC";
$res_chars = $conn->query($sql_chars);
$grouped_specs = [];
$long_description = $product['description']; 
$usage_steps = "";
while($char = $res_chars->fetch_assoc()) {
    if ($char['characteristic_name'] == 'Застосування') {
        $usage_steps = $char['characteristic_value'];
    } else {
        $group = $char['group_name'] ?: 'Технічні характеристики';
        $grouped_specs[$group][] = $char;
    }
}

$cat_id = intval($product['category_id']);
$brand = $conn->real_escape_string($product['manufacturer'] ?? '');
$sql_related = "SELECT p.*, (SELECT image_url FROM Images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as img 
                FROM product p 
                WHERE (p.category_id = $cat_id OR p.manufacturer = '$brand') 
                AND p.product_id != $product_id 
                LIMIT 4";
$related_res = $conn->query($sql_related);

$stmt_r = $conn->prepare("SELECT r.*, u.first_name FROM Reviews r JOIN users u ON r.user_id = u.user_id WHERE r.product_id = ? ORDER BY r.review_date DESC");
$stmt_r->bind_param("i", $product_id);
$stmt_r->execute();
$reviews = $stmt_r->get_result();

$cart_products = [];
if (isset($_SESSION['user_id'])) {
    $u_id = intval($_SESSION['user_id']);

    $sql_cart_sug = "SELECT p.*, (SELECT image_url FROM Images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as img 
                     FROM Order_Details od
                     JOIN orders o ON od.order_id = o.order_id
                     JOIN product p ON od.product_id = p.product_id
                     WHERE o.user_id = $u_id AND od.status = 'cart' AND o.invoice_no IS NULL AND p.product_id != $product_id LIMIT 4";
    $res_cart_sug = $conn->query($sql_cart_sug);
    if ($res_cart_sug) {
        while($row = $res_cart_sug->fetch_assoc()) {
            $cart_products[] = $row;
        }
    }
} else {
    if (!empty($_SESSION['cart'])) {
        $cart_ids = array_diff(array_keys($_SESSION['cart']), [$product_id]);
        if (!empty($cart_ids)) {
            $c_ids_str = implode(',', array_map('intval', $cart_ids));
            $sql_cart_sug = "SELECT p.*, (SELECT image_url FROM Images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as img 
                             FROM product p WHERE p.product_id IN ($c_ids_str) LIMIT 4";
            $res_cart_sug = $conn->query($sql_cart_sug);
            if ($res_cart_sug) {
                while($row = $res_cart_sug->fetch_assoc()) {
                    $cart_products[] = $row;
                }
            }
        }
    }
}

$recent_products = [];
$recent_ids = array_diff($_SESSION['recently_viewed'], [$product_id]);
if (!empty($recent_ids)) {
    $ids_str = implode(',', array_map('intval', $recent_ids));
    $sql_recent = "SELECT p.*, (SELECT image_url FROM Images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as img 
                   FROM product p WHERE p.product_id IN ($ids_str) ORDER BY FIELD(product_id, $ids_str) LIMIT 4";
    $res_recent = $conn->query($sql_recent);
    if ($res_recent) {
        while($row = $res_recent->fetch_assoc()) {
            $recent_products[] = $row;
        }
    }
}

?>

<?php include 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['name']); ?> - BeautyStore</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #1a1a1a; --accent: #bc9c64; --bg-light: #fdfaf9; --white: #ffffff; --border: #ececec; --heart: #e74c3c; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Montserrat', sans-serif; }
        body { background-color: var(--white); color: var(--primary); overflow-x: hidden; }
        .shop-container { max-width: 1250px; margin: 0 auto; padding: 60px 25px; }
        .product-hero { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 80px; margin-bottom: 80px; align-items: start; }
        .gallery-wrapper { display: flex; gap: 20px; }
        .thumbnails { display: flex; flex-direction: column; gap: 15px; }
        .thumb-item { width: 70px; height: 95px; object-fit: cover; border: 1px solid var(--border); cursor: pointer; transition: 0.3s; opacity: 0.7; }
        .thumb-item.active, .thumb-item:hover { border-color: var(--accent); opacity: 1; }
        .image-viewport { flex-grow: 1; background: #fafafa; border: 1px solid #f5f5f5; height: 600px; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; }
        .image-viewport img { max-width: 85%; height: auto; transition: transform 0.8s ease; }
        .wishlist-btn-main { position: absolute; top: 25px; right: 25px; background: white; width: 48px; height: 48px; border-radius: 50%; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 22px; color: #ddd; box-shadow: 0 5px 15px rgba(0,0,0,0.05); z-index: 10; transition: 0.3s; }
        .wishlist-btn-main.active { color: var(--heart); }
        .purchase-column { padding-top: 40px; }
        .meta-brand { color: var(--accent); font-weight: 700; letter-spacing: 4px; text-transform: uppercase; font-size: 11px; margin-bottom: 15px; display: block; }
        .main-h1 { font-family: 'Playfair Display', serif; font-size: 2.8rem; line-height: 1.1; margin-bottom: 25px; font-weight: 400; }
        .price-display { margin: 35px 0; display: flex; align-items: baseline; gap: 15px; }
        .price-actual { font-size: 2.2rem; font-weight: 600; color: #000; }
        .price-actual.sale-price { color: var(--heart); }
        .price-was { text-decoration: line-through; color: #bbb; font-size: 1.3rem; }
        .btn-checkout { background: var(--primary); color: #fff; border: 1px solid var(--primary); padding: 22px; width: 100%; text-transform: uppercase; letter-spacing: 3px; font-weight: 700; font-size: 12px; cursor: pointer; transition: 0.4s; }
        .btn-checkout:hover { background: var(--accent); border-color: var(--accent); }
        
        .details-layout { display: grid; grid-template-columns: 1fr 380px; gap: 80px; padding: 80px 0; border-top: 1px solid var(--border); }
        .content-block h2 { font-family: 'Playfair Display', serif; font-size: 2rem; margin-bottom: 30px; font-style: italic; font-weight: 400; }
        .content-block p { font-size: 1rem; color: #444; line-height: 1.9; }
        .specs-card-highlight { background: var(--bg-light); padding: 40px; border-radius: 8px; border: 1px solid #f2ede9; position: sticky; top: 40px; }
        .specs-card-highlight h3 { font-family: 'Playfair Display', serif; font-size: 1.4rem; margin-bottom: 25px; text-align: center; font-weight: 400; }
        .specs-table { width: 100%; border-collapse: collapse; }
        .specs-table td { padding: 15px 0; border-bottom: 1px solid #eee; font-size: 13px; }
        .spec-label { text-transform: uppercase; font-size: 10px; font-weight: 700; color: var(--accent); letter-spacing: 1px; width: 45%; }
        .spec-value { font-weight: 500; color: #333; text-align: right; }

        .reviews-container { max-width: 800px; margin: 60px auto; padding: 80px 0; border-top: 1px solid var(--border); }
        .reviews-title { font-family: 'Playfair Display', serif; font-size: 2.2rem; text-align: center; margin-bottom: 50px; }
        .review-card { padding: 35px 0; border-bottom: 1px solid #f9f9f9; }
        .review-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .reviewer { font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 2px; }
        .stars { color: var(--accent); font-size: 14px; }
        .review-text { color: #555; font-size: 15px; line-height: 1.7; font-style: italic; }

        .admin-answer-box { margin-top: 25px; margin-left: 40px; padding: 25px; background: #f8f8f8; border-left: 3px solid var(--accent); position: relative; }
        .admin-tag { position: absolute; top: -12px; left: 15px; background: var(--primary); color: white; font-size: 9px; font-weight: 700; padding: 4px 12px; text-transform: uppercase; letter-spacing: 1.5px; }
        .admin-text { font-size: 14px; color: #444; line-height: 1.6; margin: 0; }

        .form-wrapper { background: var(--bg-light); padding: 50px; margin-top: 60px; border-radius: 4px; text-align: center; }
        .form-wrapper h3 { font-family: 'Playfair Display', serif; margin-bottom: 30px; font-size: 1.6rem; }
        .form-input { width: 100%; padding: 18px; margin-bottom: 20px; border: 1px solid #ddd; background: #fff; }
        
        #wishlistToast, #cartToast { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(120px); background: var(--primary); color: white; padding: 18px 40px; font-size: 11px; text-transform: uppercase; letter-spacing: 3px; z-index: 10000; transition: 0.6s; border: 1px solid var(--accent); text-align: center; min-width: 300px; }
        #wishlistToast.show, #cartToast.show { transform: translateX(-50%) translateY(0); }
        .toast-link { color: var(--accent); font-weight: 700; text-decoration: underline; margin-left: 10px; cursor: pointer; }

        .suggestions-wrapper { margin-top: 60px; padding-top: 80px; border-top: 1px solid var(--border); }
        .sugg-section-title { font-family: 'Playfair Display', serif; font-size: 2rem; margin-bottom: 40px; text-align: center; font-weight: 400; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 35px; margin-bottom: 80px;}
        .card { background: var(--white); border: 1px solid var(--border); transition: 0.4s; position: relative; display: flex; flex-direction: column; }
        .card:hover { border-color: var(--primary); box-shadow: 15px 15px 0px rgba(0,0,0,0.03); }
        
        .img-box { width: 100%; height: 380px; overflow: hidden; background: #fff; position: relative; border-bottom: 1px solid var(--border); }
        .img-box img { width: 100%; height: 100%; object-fit: contain; padding: 30px; transition: 0.7s; }
        .card:hover .img-box img { transform: scale(1.08); }

        .sale-badge { position: absolute; top: 20px; left: 20px; background: var(--heart); color: white; padding: 5px 12px; font-size: 10px; font-weight: 700; z-index: 10; text-transform: uppercase; }

        .wishlist-btn { 
            position: absolute; top: 20px; right: 20px; background: white; width: 38px; height: 38px; 
            border-radius: 50%; border: none; display: flex; align-items: center; justify-content: center; 
            cursor: pointer; font-size: 18px; color: #ccc; box-shadow: 0 5px 15px rgba(0,0,0,0.08); 
            z-index: 20; transition: 0.3s; 
        }
        .wishlist-btn.active { color: var(--heart); }
        .wishlist-btn:hover { color: var(--heart); }

        .info { padding: 25px; flex-grow: 1; display: flex; flex-direction: column; }
        .mfg { font-size: 9px; color: var(--accent); text-transform: uppercase; letter-spacing: 3px; font-weight: 700; margin-bottom: 10px; }
        .name { font-size: 14px; font-weight: 500; margin-bottom: 15px; height: 40px; overflow: hidden; text-transform: uppercase; color: var(--primary); line-height: 1.4; text-decoration: none;}
        a.name:hover { color: var(--accent); }
        
        .price-container { display: flex; align-items: baseline; gap: 10px; margin-top: auto; }
        .price { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; color: #000; }
        .price.old { font-size: 16px; color: #bbb; text-decoration: line-through; font-weight: 400; }
        .price.sale { color: var(--heart); }

        .btn-buy { 
            display: block; background: transparent; border: none; border-top: 1px solid var(--border); 
            padding: 20px; width: 100%; cursor: pointer; font-weight: 700; 
            text-transform: uppercase; font-size: 10px; letter-spacing: 2px;
            color: var(--primary); text-decoration: none; text-align: center; transition: 0.3s;
        }
        .btn-buy:hover { background: var(--primary); color: #fff; }
    </style>
</head>
<body>

<div class="shop-container">
    <div class="product-hero">
        <div class="gallery-wrapper">
            <div class="thumbnails">
                <?php foreach($gallery_images as $index => $img_url): ?>
                    <img src="<?php echo $img_url; ?>" class="thumb-item <?php echo $index === 0 ? 'active' : ''; ?>" onclick="changePhoto(this.src, this)">
                <?php endforeach; ?>
            </div>
            <div class="image-viewport">
                <button class="wishlist-btn-main <?php echo $is_fav_status ? 'active' : ''; ?>" onclick="toggleWishlist(this, <?php echo $product['product_id']; ?>)">❤</button>
                <img id="main-photo" src="<?php echo $gallery_images[0]; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
        </div>
        <div class="purchase-column">
            <span class="meta-brand"><?php echo htmlspecialchars($product['manufacturer'] ?? ''); ?></span>
            <h1 class="main-h1"><?php echo htmlspecialchars($product['name']); ?></h1>
            <div class="price-display">
                <?php 
                $has_sale = (!empty($product['old_price']) && $product['old_price'] > $product['price']) || (isset($product['badge']) && $product['badge'] === 'SALE'); 
                $current_price = $product['price']; 
                ?>
                <span class="price-actual <?php echo $has_sale ? 'sale-price' : ''; ?>">₴<?php echo number_format($current_price, 0, '.', ' '); ?></span>
                <?php if($has_sale && !empty($product['old_price'])): ?> 
                    <span class="price-was">₴<?php echo number_format($product['old_price'], 0, '.', ' '); ?></span> 
                <?php endif; ?>
            </div>
            <button class="btn-checkout" onclick="addToCart(<?php echo $product_id; ?>)">Додати до кошика</button>
        </div>
    </div>

    <div class="details-layout">
        <div class="content-block">
            <?php if($long_description): ?>
                <h2>Опис та властивості</h2>
                <p><?php echo nl2br(htmlspecialchars($long_description)); ?></p>
            <?php endif; ?>
            <?php if($usage_steps): ?>
                <h2 style="margin-top:50px;">Ритуал застосування</h2>
                <p><?php echo nl2br(htmlspecialchars($usage_steps)); ?></p>
            <?php endif; ?>
        </div>
        <div class="specs-card-highlight">
            <h3>Характеристики</h3>
            <table class="specs-table">
                <tr><td class="spec-label">Категорія</td><td class="spec-value"><?php echo htmlspecialchars($product['category_name'] ?? 'Не вказана'); ?></td></tr>
                <?php foreach($grouped_specs as $group => $items): ?>
                    <?php foreach($items as $spec): ?>
                        <tr>
                            <td class="spec-label"><?php echo htmlspecialchars($spec['characteristic_name']); ?></td>
                            <td class="spec-value"><?php echo htmlspecialchars($spec['characteristic_value']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <section class="reviews-container" id="reviews">
        <h2 class="reviews-title">Відгуки клієнтів</h2>
        <?php if ($reviews->num_rows > 0): ?>
            <?php while($r = $reviews->fetch_assoc()): ?>
                <div class="review-card">
                    <div class="review-header">
                        <span class="reviewer"><?php echo htmlspecialchars($r['first_name']); ?></span>
                        <span class="stars"><?php echo str_repeat('★', $r['rating']); ?></span>
                    </div>
                    <p class="review-text">"<?php echo nl2br(htmlspecialchars($r['comment'])); ?>"</p>

                    <?php if (!empty($r['reply_text'])): ?>
                        <div class="admin-answer-box">
                            <div class="admin-tag">Відповідь BeautyStore</div>
                            <p class="admin-text"><?php echo nl2br(htmlspecialchars($r['reply_text'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; color: #999;">Відгуків ще немає. Будьте першими!</p>
        <?php endif; ?>

        <div class="form-wrapper">
            <h3>Залишити свій відгук</h3>
            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST">
                    <select name="rating" class="form-input" required>
                        <option value="5">5 зірок</option>
                        <option value="4">4 зірки</option>
                        <option value="3">3 зірки</option>
                        <option value="2">2 зірки</option>
                        <option value="1">1 зірка</option>
                    </select>
                    <textarea name="comment" class="form-input" rows="5" placeholder="Ваші враження..." required></textarea>
                    <button type="submit" name="submit_review" class="btn-checkout" style="width: auto; padding: 15px 60px;">Опублікувати</button>
                </form>
            <?php else: ?>
                <p>Тільки авторизовані користувачі можуть залишати відгуки. <a href="login_register.php" style="color:var(--accent);">Увійти</a></p>
            <?php endif; ?>
        </div>
    </section>

    <div class="suggestions-wrapper">
        <?php if (!empty($cart_products)): ?>
            <h2 class="sugg-section-title">Також у вашому кошику</h2>
            <div class="grid">
                <?php foreach($cart_products as $p): 
                    $has_sale = (!empty($p['old_price']) && $p['old_price'] > $p['price']) || (isset($p['badge']) && $p['badge'] === 'SALE'); 
                ?>
                    <div class="card">
                        <?php if($has_sale): ?>
                            <div class="sale-badge">Sale</div>
                        <?php endif; ?>
                        <div class="img-box">
                            <?php $is_wished = in_array($p['product_id'], $user_wishlist_ids); ?>
                            <button class="wishlist-btn <?php echo $is_wished ? 'active' : ''; ?>" onclick="toggleWishlist(this, <?php echo $p['product_id']; ?>)">❤</button>
                            <a href="product_details.php?id=<?php echo $p['product_id']; ?>">
                                <img src="<?php echo $p['img'] ?: 'img/products/'.$p['product_id'].'.jpg'; ?>" onerror="this.src='https://via.placeholder.com/400x500?text=BeautyStore'">
                            </a>
                        </div>
                        <div class="info">
                            <div class="mfg"><?php echo htmlspecialchars($p['manufacturer'] ?? 'Premium'); ?></div>
                            <a href="product_details.php?id=<?php echo $p['product_id']; ?>" class="name"><?php echo htmlspecialchars($p['name']); ?></a>
                            <div class="price-container">
                                <?php if($has_sale && !empty($p['old_price'])): ?>
                                    <span class="price sale"><?php echo number_format($p['price'], 0, '.', ' '); ?> ₴</span>
                                    <span class="price old"><?php echo number_format($p['old_price'], 0, '.', ' '); ?> ₴</span>
                                <?php else: ?>
                                    <span class="price"><?php echo number_format($p['price'], 0, '.', ' '); ?> ₴</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button class="btn-buy" onclick="addToCart(<?php echo $p['product_id']; ?>)">У кошик</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($related_res && $related_res->num_rows > 0): ?>
            <h2 class="sugg-section-title">З цієї ж категорії</h2>
            <div class="grid">
                <?php while($p = $related_res->fetch_assoc()): 
                    $has_sale = (!empty($p['old_price']) && $p['old_price'] > $p['price']) || (isset($p['badge']) && $p['badge'] === 'SALE'); 
                ?>
                    <div class="card">
                        <?php if($has_sale): ?>
                            <div class="sale-badge">Sale</div>
                        <?php endif; ?>
                        <div class="img-box">
                            <?php $is_wished = in_array($p['product_id'], $user_wishlist_ids); ?>
                            <button class="wishlist-btn <?php echo $is_wished ? 'active' : ''; ?>" onclick="toggleWishlist(this, <?php echo $p['product_id']; ?>)">❤</button>
                            <a href="product_details.php?id=<?php echo $p['product_id']; ?>">
                                <img src="<?php echo $p['img'] ?: 'img/products/'.$p['product_id'].'.jpg'; ?>" onerror="this.src='https://via.placeholder.com/400x500?text=BeautyStore'">
                            </a>
                        </div>
                        <div class="info">
                            <div class="mfg"><?php echo htmlspecialchars($p['manufacturer'] ?? 'Premium'); ?></div>
                            <a href="product_details.php?id=<?php echo $p['product_id']; ?>" class="name"><?php echo htmlspecialchars($p['name']); ?></a>
                            <div class="price-container">
                                <?php if($has_sale && !empty($p['old_price'])): ?>
                                    <span class="price sale"><?php echo number_format($p['price'], 0, '.', ' '); ?> ₴</span>
                                    <span class="price old"><?php echo number_format($p['old_price'], 0, '.', ' '); ?> ₴</span>
                                <?php else: ?>
                                    <span class="price"><?php echo number_format($p['price'], 0, '.', ' '); ?> ₴</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button class="btn-buy" onclick="addToCart(<?php echo $p['product_id']; ?>)">У кошик</button>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($recent_products)): ?>
            <h2 class="sugg-section-title">Ви нещодавно переглядали</h2>
            <div class="grid">
                <?php foreach($recent_products as $p): 
                    $has_sale = (!empty($p['old_price']) && $p['old_price'] > $p['price']) || (isset($p['badge']) && $p['badge'] === 'SALE'); 
                ?>
                    <div class="card">
                        <?php if($has_sale): ?>
                            <div class="sale-badge">Sale</div>
                        <?php endif; ?>
                        <div class="img-box">
                            <?php $is_wished = in_array($p['product_id'], $user_wishlist_ids); ?>
                            <button class="wishlist-btn <?php echo $is_wished ? 'active' : ''; ?>" onclick="toggleWishlist(this, <?php echo $p['product_id']; ?>)">❤</button>
                            <a href="product_details.php?id=<?php echo $p['product_id']; ?>">
                                <img src="<?php echo $p['img'] ?: 'img/products/'.$p['product_id'].'.jpg'; ?>" onerror="this.src='https://via.placeholder.com/400x500?text=BeautyStore'">
                            </a>
                        </div>
                        <div class="info">
                            <div class="mfg"><?php echo htmlspecialchars($p['manufacturer'] ?? 'Premium'); ?></div>
                            <a href="product_details.php?id=<?php echo $p['product_id']; ?>" class="name"><?php echo htmlspecialchars($p['name']); ?></a>
                            <div class="price-container">
                                <?php if($has_sale && !empty($p['old_price'])): ?>
                                    <span class="price sale"><?php echo number_format($p['price'], 0, '.', ' '); ?> ₴</span>
                                    <span class="price old"><?php echo number_format($p['old_price'], 0, '.', ' '); ?> ₴</span>
                                <?php else: ?>
                                    <span class="price"><?php echo number_format($p['price'], 0, '.', ' '); ?> ₴</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button class="btn-buy" onclick="addToCart(<?php echo $p['product_id']; ?>)">У кошик</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="wishlistToast">Додано в обране</div>
<div id="cartToast">Товар додано у кошик</div>

<script>
    function changePhoto(src, el) {
        document.getElementById('main-photo').src = src;
        document.querySelectorAll('.thumb-item').forEach(item => item.classList.remove('active'));
        el.classList.add('active');
    }

    function addToCart(id) {
        fetch('cart_add.php?id=' + id + '&ajax=1')
        .then(response => response.text())
        .then(data => {
            const toast = document.getElementById('cartToast');
            toast.classList.add('show');
            setTimeout(() => { toast.classList.remove('show'); }, 4000);

            const cartCount = document.getElementById('cart-count');
            if(cartCount) {
                let current = parseInt(cartCount.innerText) || 0;
                cartCount.innerText = current + 1;
            }
        })
        .catch(error => console.error('Помилка:', error));
    }

    function toggleWishlist(btn, id) {
        fetch('wishlist_add.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'error') { window.location.href = 'login_register.php'; return; }
            const toast = document.getElementById('wishlistToast');
            if (data.status === 'added') { btn.classList.add('active'); toast.innerText = "ДОДАНО В ОБРАНЕ"; } 
            else { btn.classList.remove('active'); toast.innerText = "ВИДАЛЕНО З ОБРАНОГО"; }
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2500);
        });
    }
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>
