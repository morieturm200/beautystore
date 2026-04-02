<?php

if (!isset($conn)) {
    $conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");
}
$conn->set_charset("utf8mb4");


$sql_brands = "SELECT p.*, i.image_url 
               FROM product p 
               LEFT JOIN Images i ON p.product_id = i.product_id AND i.is_primary = 1
               WHERE p.is_giveaway_participant = 1 
                  OR (p.promo_type IS NOT NULL AND p.promo_type != '')
                  OR p.is_sale = 1
               ORDER BY p.product_id DESC 
               LIMIT 4";

$res_brands = $conn->query($sql_brands);


if ($res_brands->num_rows == 0) {
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
    }
    .offer-card:hover { transform: translateY(-5px); border-color: var(--border); }

    
    .badge-prive { 
        position: absolute; top: 15px; left: 0; background: var(--primary); color: #fff; 
        padding: 5px 12px; font-size: 9px; font-weight: 700; text-transform: uppercase; z-index: 10; letter-spacing: 1px;
    }
    .badge-promo { 
        position: absolute; top: 42px; left: 0; background: #e74c3c; color: #fff; 
        padding: 4px 10px; font-size: 10px; font-weight: 800; z-index: 10;
    }

    .offer-img-box { width: 100%; height: 320px; overflow: hidden; margin-bottom: 15px; background: #fdfaf9; }
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
        font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
        cursor: pointer; transition: 0.3s; text-decoration: none; text-align: center; color: var(--primary);
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
            $is_sale = ($item['is_sale'] == 1);
            $display_price = $is_sale ? $item['sale_price'] : $item['price'];
        ?>
        <div class="offer-card">
            <?php if($item['is_giveaway_participant']): ?>
                <div class="badge-prive">Giveaway</div>
            <?php endif; ?>

            <?php if($has_promo && $item['promo_type'] !== 'GIFT'): ?>
                <div class="badge-promo"><?php echo htmlspecialchars($item['promo_type']); ?></div>
            <?php endif; ?>

            <a href="product_details.php?id=<?php echo $p_id; ?>" style="text-decoration: none; color: inherit;">
                <div class="offer-img-box">
                    <?php 
                        $img = !empty($item['image_url']) ? $item['image_url'] : "img/products/$p_id.jpg";
                    ?>
                    <img src="<?php echo $img; ?>" onerror="this.src='https://via.placeholder.com/300x400?text=Beauty+Store'">
                </div>

                <?php if($item['promo_type'] === 'GIFT'): ?>
                    <span class="gift-notice">🎁 + Подарунок до покупки</span>
                <?php endif; ?>

                <h4><?php echo htmlspecialchars($item['name']); ?></h4>

                <div class="price-area">
                    <span class="price-now <?php echo $is_sale ? 'sale' : ''; ?>">
                        <?php echo number_format($display_price, 0, '.', ' '); ?> ₴
                    </span>
                    <?php if($is_sale): ?>
                        <span class="price-old"><?php echo number_format($item['price'], 0, '.', ' '); ?> ₴</span>
                    <?php endif; ?>
                </div>
            </a>
            
            <a href="cart_add.php?id=<?php echo $p_id; ?>&redirect=cart" class="add-to-cart-btn">
                <?php echo $has_promo ? 'Скористатись акцією' : 'Додати в кошик'; ?>
                
            </a>
        </div>
        <?php endwhile; ?>
    </div>
</section>
<?php endif; ?>