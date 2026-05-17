<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($conn)) {
    $conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");
    $conn->set_charset("utf8mb4");
}


$user_wishlist_ids = [];
if (isset($_SESSION['user_id'])) {
    $c_id = intval($_SESSION['user_id']);
    $wish_res = $conn->query("SELECT product_id FROM Wishlist WHERE user_id = $c_id");
    if ($wish_res) {
        while($w_row = $wish_res->fetch_assoc()) {
            $user_wishlist_ids[] = $w_row['product_id'];
        }
    }
}


$sql_brands = "SELECT p.*, i.image_url 
               FROM product p 
               LEFT JOIN Images i ON p.product_id = i.product_id AND i.is_primary = 1
               WHERE p.is_giveaway_participant = 1 
                  OR (p.promo_type IS NOT NULL AND p.promo_type != '')
                  OR p.badge = 'SALE'
               ORDER BY p.product_id DESC 
               LIMIT 4";

$res_brands = $conn->query($sql_brands);

if (!$res_brands || $res_brands->num_rows == 0) {
    $res_brands = $conn->query("SELECT p.*, i.image_url FROM product p LEFT JOIN Images i ON p.product_id = i.product_id AND i.is_primary = 1 LIMIT 4");
}

if ($res_brands && $res_brands->num_rows > 0): 
?>
<style>
    :root {
        --primary: #1a1a1a;
        --accent: #d4a373;
        --white: #ffffff;
        --border: #e8e8e8;
        --heart: #e74c3c;
    }

    .brand-offers { padding: 80px 50px; background: var(--white); }
    .brand-offers h2 { 
        font-family: 'Playfair Display', serif; text-align: center; margin-bottom: 50px; 
        font-size: 2.5rem; font-weight: 400; font-style: italic;
    }

    .offers-grid { display: flex; flex-wrap: wrap; gap: 30px; justify-content: center; max-width: 1400px; margin: 0 auto; }

    .offer-card { 
        flex: 1 1 250px; max-width: 280px; position: relative; 
        transition: 0.4s; border: 1px solid transparent; padding-bottom: 20px;
        display: flex;
        flex-direction: column;
    }
    .offer-card:hover { transform: translateY(-5px); border-color: var(--border); }


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

    .badge-prive { 
        position: absolute; top: 15px; left: 0; background: var(--primary); color: #fff; 
        padding: 5px 12px; font-size: 9px; font-weight: 700; text-transform: uppercase; z-index: 10; letter-spacing: 1px;
    }
    .badge-promo { 
        position: absolute; top: 42px; left: 0; background: #e74c3c; color: #fff; 
        padding: 4px 10px; font-size: 10px; font-weight: 800; z-index: 10;
    }

    .offer-img-box { width: 100%; height: 320px; overflow: hidden; margin-bottom: 15px; background: #fdfaf9; position: relative; }
    .offer-img-box img { width: 100%; height: 100%; object-fit: contain; padding: 20px; transition: 0.5s; }
    .offer-card:hover img { transform: scale(1.05); }

    .offer-card h4 { 
        font-size: 14px; margin: 10px 15px; text-transform: uppercase; 
        height: 40px; overflow: hidden; line-height: 1.4;
    }

    .price-area { margin: 10px 15px; display: flex; align-items: center; gap: 10px; }
    .price-now { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 1.3rem; }
    .price-now.sale { color: #e74c3c; }
    .price-old { color: #bbb; text-decoration: line-through; font-size: 0.9rem; }

    .add-to-cart-btn {
        display: block; width: calc(100% - 30px); margin: 15px auto 0; padding: 15px;
        background: transparent; border: 1px solid var(--primary);
        font-family: 'Montserrat', sans-serif;
        font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
        cursor: pointer; transition: 0.3s; text-decoration: none; text-align: center; color: var(--primary);
        border-radius: 0;
    }
    .add-to-cart-btn:hover { background: var(--primary); color: #fff; }

    .gift-notice { color: #27ae60; font-size: 10px; font-weight: 700; margin: 0 15px; text-transform: uppercase; }
</style>

<section class="brand-offers">
    <h2>Privé Special Offers</h2>

    <div class="offers-grid">
        <?php while($item = $res_brands->fetch_assoc()): 
            $p_id = $item['product_id'];
            $has_promo = !empty($item['promo_type']);
            

            $is_fav = in_array($p_id, $user_wishlist_ids) ? 'active' : '';

            $has_discount = (isset($item['badge']) && $item['badge'] === 'SALE') || (!empty($item['old_price']) && $item['old_price'] > $item['price']);
        ?>
        <div class="offer-card">
            <?php if($item['is_giveaway_participant']): ?>
                <div class="badge-prive">Giveaway</div>
            <?php endif; ?>

            <?php if($has_promo && $item['promo_type'] !== 'GIFT'): ?>
                <div class="badge-promo"><?php echo htmlspecialchars($item['promo_type']); ?></div>
            <?php endif; ?>

            <a href="wishlist_add.php?id=<?php echo $p_id; ?>" class="wishlist-btn <?php echo $is_fav; ?>">❤</a>

            <a href="product_details.php?id=<?php echo $p_id; ?>" style="text-decoration: none; color: inherit; display: flex; flex-direction: column;">
                <div class="offer-img-box">
                    <?php 
                        $img = !empty($item['image_url']) ? $item['image_url'] : "img/products/$p_id.jpg";
                    ?>
                    <img src="<?php echo htmlspecialchars($img); ?>" onerror="this.src='https://via.placeholder.com/300x400?text=Beauty+Store'">
                </div>

                <?php if($item['promo_type'] === 'GIFT'): ?>
                    <span class="gift-notice">🎁 + Подарунок до покупки</span>
                <?php endif; ?>

                <h4><?php echo htmlspecialchars($item['name']); ?></h4>

                <div class="price-area">
                    <span class="price-now <?php echo $has_discount ? 'sale' : ''; ?>">
                        <?php echo number_format($item['price'], 0, '.', ' '); ?> ₴
                    </span>
                    
                    <?php if(!empty($item['old_price']) && $item['old_price'] > $item['price']): ?>
                        <span class="price-old"><?php echo number_format($item['old_price'], 0, '.', ' '); ?> ₴</span>
                    <?php endif; ?>
                </div>
            </a>
            
            <button onclick="addToCart(event, this, <?php echo $p_id; ?>)" class="add-to-cart-btn">
                <?php echo $has_promo ? 'Скористатись акцією' : 'Додати в кошик'; ?>
            </button>
        </div>
        <?php endwhile; ?>
    </div>
</section>
<?php endif; ?>
