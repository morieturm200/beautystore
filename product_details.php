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
    if (isset($_SESSION['customer_id'])) { 
        $customer_id = $_SESSION['customer_id'];
        $rating = intval($_POST['rating']);
        $comment = trim($_POST['comment']);
        
        $stmt_rev = $conn->prepare("INSERT INTO Reviews (product_id, customer_id, rating, comment, review_date) VALUES (?, ?, ?, ?, NOW())");
        $stmt_rev->bind_param("iiis", $product_id, $customer_id, $rating, $comment);
        
        if($stmt_rev->execute()) {
            header("Location: product_details.php?id=$product_id&status=success#reviews");
            exit;
        }
    } else {
        $error_auth = "Будь ласка, увійдіть в систему, щоб залишити відгук.";
    }
}


$sql_product = "SELECT p.*, i.image_url 
                FROM product p 
                LEFT JOIN Images i ON p.product_id = i.product_id AND i.is_primary = 1 
                WHERE p.product_id = $product_id";

$res_product = $conn->query($sql_product);
$product = $res_product->fetch_assoc();

if (!$product) { 
    echo "<div style='padding:100px; text-align:center;'><h1>Товар відсутній</h1></div>";
    exit; 
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


if (isset($_SESSION['customer_id'])) {
    $c_id = $_SESSION['customer_id'];
    $check_fav = $conn->query("SELECT * FROM Wishlist WHERE customer_id = $c_id AND product_id = $product_id");
    $is_fav_status = ($check_fav && $check_fav->num_rows > 0);
    $wish_res = $conn->query("SELECT COUNT(*) as cnt FROM Wishlist WHERE customer_id = $c_id");
    $wishlist_count = ($wish_res) ? $wish_res->fetch_assoc()['cnt'] : 0;
} else {
    $is_fav_status = false;
    $wishlist_count = 0; 
}


$sql_chars = "SELECT * FROM characteristics WHERE product_id = $product_id ORDER BY sort_order ASC, characteristic_id ASC";
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


$cat = $conn->real_escape_string($product['category']);
$brand = $conn->real_escape_string($product['manufacturer']);
$sql_related = "SELECT p.*, (SELECT image_url FROM Images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as img 
                FROM product p 
                WHERE (p.category = '$cat' OR p.manufacturer = '$brand') 
                AND p.product_id != $product_id 
                LIMIT 4";
$related_res = $conn->query($sql_related);


$stmt_r = $conn->prepare("SELECT r.*, c.first_name FROM Reviews r JOIN customer c ON r.customer_id = c.customer_id WHERE r.product_id = ? ORDER BY r.review_date DESC");
$stmt_r->bind_param("i", $product_id);
$stmt_r->execute();
$reviews = $stmt_r->get_result();
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

        /* СТИЛІ ВІДПОВІДІ АДМІНА */
        .admin-answer-box {
            margin-top: 25px;
            margin-left: 40px;
            padding: 25px;
            background: #f8f8f8;
            border-left: 3px solid var(--accent);
            position: relative;
        }
        .admin-tag {
            position: absolute;
            top: -12px;
            left: 15px;
            background: var(--primary);
            color: white;
            font-size: 9px;
            font-weight: 700;
            padding: 4px 12px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .admin-text {
            font-size: 14px;
            color: #444;
            line-height: 1.6;
            margin: 0;
        }

        .form-wrapper { background: var(--bg-light); padding: 50px; margin-top: 60px; border-radius: 4px; text-align: center; }
        .form-wrapper h3 { font-family: 'Playfair Display', serif; margin-bottom: 30px; font-size: 1.6rem; }
        .form-input { width: 100%; padding: 18px; margin-bottom: 20px; border: 1px solid #ddd; background: #fff; }
        #wishlistToast { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(120px); background: var(--primary); color: white; padding: 18px 40px; font-size: 11px; text-transform: uppercase; letter-spacing: 3px; z-index: 10000; transition: 0.6s; border: 1px solid var(--accent); }
        #wishlistToast.show { transform: translateX(-50%) translateY(0); }
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
            <span class="meta-brand"><?php echo htmlspecialchars($product['manufacturer']); ?></span>
            <h1 class="main-h1"><?php echo htmlspecialchars($product['name']); ?></h1>
            <div class="price-display">
                <?php $has_sale = ($product['is_sale'] == 1 && $product['sale_price'] > 0); $current_price = $has_sale ? $product['sale_price'] : $product['price']; ?>
                <span class="price-actual <?php echo $has_sale ? 'sale-price' : ''; ?>">₴<?php echo number_format($current_price, 0, '.', ' '); ?></span>
                <?php if($has_sale): ?> <span class="price-was">₴<?php echo number_format($product['price'], 0, '.', ' '); ?></span> <?php endif; ?>
            </div>
            <button class="btn-checkout" onclick="location.href='cart_add.php?id=<?php echo $product_id; ?>&redirect=cart'">Додати до кошика</button>
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
                <tr><td class="spec-label">Категорія</td><td class="spec-value"><?php echo htmlspecialchars($product['category']); ?></td></tr>
                <tr><td class="spec-label">Об'єм</td><td class="spec-value"><?php echo $product['weight']; ?> мл/г</td></tr>
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

                    <?php if (!empty($r['admin_reply'])): ?>
                        <div class="admin-answer-box">
                            <div class="admin-tag">Відповідь BeautyStore</div>
                            <p class="admin-text"><?php echo nl2br(htmlspecialchars($r['admin_reply'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; color: #999;">Відгуків ще немає. Будьте першими!</p>
        <?php endif; ?>

        <div class="form-wrapper">
            <h3>Залишити свій відгук</h3>
            <?php if (isset($_SESSION['customer_id'])): ?>
                <form method="POST">
                    <select name="rating" class="form-input" required>
                        <option value="5">Оцінка: 5 зірок</option>
                        <option value="4">4 зірки</option>
                        <option value="3">3 зірки</option>
                    </select>
                    <textarea name="comment" class="form-input" rows="5" placeholder="Ваші враження..." required></textarea>
                    <button type="submit" name="submit_review" class="btn-checkout" style="width: auto; padding: 15px 60px;">Опублікувати</button>
                </form>
            <?php else: ?>
                <p>Тільки авторизовані користувачі можуть залишати відгуки. <a href="login_register.php" style="color:var(--accent);">Увійти</a></p>
            <?php endif; ?>
        </div>
    </section>
</div>

<div id="wishlistToast">Додано в обране</div>

<script>
    function changePhoto(src, el) {
        document.getElementById('main-photo').src = src;
        document.querySelectorAll('.thumb-item').forEach(item => item.classList.remove('active'));
        el.classList.add('active');
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