<?php 
session_start();
$conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");
if ($conn->connect_error) { die("Помилка: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");


if (isset($_SESSION['customer_id'])) {
    $c_id = intval($_SESSION['customer_id']);
    
    $res_sync = $conn->query("SELECT product_id, quantity FROM Cart WHERE customer_id = $c_id");
    
    
    if ($res_sync->num_rows > 0) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        while($row_sync = $res_sync->fetch_assoc()) {
            $_SESSION['cart'][$row_sync['product_id']] = $row_sync['quantity'];
        }
    }
}

$total_sum = 0;
$cart_items = [];
$total_count_items = 0;


if (isset($_SESSION['customer_id'])) {
    $customer_id = intval($_SESSION['customer_id']);
   
    $sql = "SELECT p.*, c.quantity, i.image_url 
            FROM Cart c 
            JOIN product p ON c.product_id = p.product_id 
            LEFT JOIN Images i ON p.product_id = i.product_id AND i.is_primary = 1
            WHERE c.customer_id = $customer_id";
    $res = $conn->query($sql);
    while($row = $res->fetch_assoc()) {
        $cart_items[] = $row;
        $total_count_items += $row['quantity'];
    }
} else {
  
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $id => $qty) {
            $id = intval($id);
            $res = $conn->query("SELECT p.*, i.image_url 
                                 FROM product p 
                                 LEFT JOIN Images i ON p.product_id = i.product_id AND i.is_primary = 1 
                                 WHERE p.product_id = $id");
            if ($p = $res->fetch_assoc()) {
                $p['quantity'] = $qty;
                $cart_items[] = $p;
                $total_count_items += $qty;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Кошик | BeautyStore Premium</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <style>
       :root {
    --primary: #1a1a1a;
    --accent: #d4a373; 
    --bg-light: #fdfaf9;
    --white: #ffffff;
    --border: #e8e8e8;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Montserrat', sans-serif;
}

body { 
    background-color: var(--bg-light); 
    color: var(--primary); 
    padding: 60px 20px;
}


.cart-container { 
    max-width: 1400px; 
    margin: 0 auto; 
    display: flex;
    gap: 40px;
}


.cart-main {
    flex: 2;
}


.cart-sidebar {
    flex: 1;
    background: var(--white);
    border: 2px solid var(--primary);
    padding: 35px;
    height: fit-content;
    position: sticky;
    top: 120px;
}


.cart-header h1 {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    font-weight: 400;
    font-style: italic;
    margin-bottom: 40px;
}


.cart-item { 
    display: flex; 
    align-items: center; 
    border: 1px solid var(--border);
    margin-bottom: 20px;
    transition: 0.3s;
    background: var(--white); 
}

.cart-item:hover {
    border-color: var(--primary);
    box-shadow: 10px 10px 0 rgba(0,0,0,0.03);
}


.item-img { 
    width: 120px; 
    height: 150px; 
    object-fit: contain;
    padding: 20px;
    border-right: 1px solid var(--border);
}


.item-info { 
    flex: 1; 
    padding: 20px 25px; 
}

.item-info .mfg {
    font-size: 9px;
    color: var(--accent);
    text-transform: uppercase;
    letter-spacing: 3px;
    font-weight: 700;
    margin-bottom: 8px;
}

.item-info .name {
    font-size: 14px;
    text-transform: uppercase;
    margin-bottom: 15px;
}


.qty-box { 
    display: flex; 
    align-items: center; 
    border: 1px solid var(--border);
    width: fit-content;
}

.qty-btn { 
    text-decoration: none; 
    color: var(--primary); 
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.3s;
}

.qty-btn:hover {
    background: var(--primary);
    color: white;
}

.qty-val {
    padding: 0 15px;
    font-weight: 600;
}


.item-price { 
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    font-weight: 700;
    min-width: 120px;
    text-align: right;
    padding-right: 20px;
}


.sidebar-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.8rem;
    margin-bottom: 25px;
}


.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 2px;
}


.total-price {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    margin: 30px 0;
    border-top: 1px solid var(--border);
    padding-top: 20px;
}


.checkout-btn { 
    background: var(--primary);
    color: white;
    border: none;
    padding: 18px;
    width: 100%;
    cursor: pointer;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
    font-size: 11px;
    transition: 0.3s;
    text-align: center;
    text-decoration: none;
    display: block;
}

.checkout-btn:hover {
    background: var(--accent);
}


.back-link {
    display: block;
    margin-top: 20px;
    text-decoration: none;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #777;
}

.back-link:hover {
    color: var(--primary);
}


.empty-cart {
    border: 1px solid var(--border);
    padding: 60px;
    text-align: center;
}


@media (max-width: 900px) {
    .cart-container {
        flex-direction: column;
    }

    .cart-sidebar {
        position: static;
    }
}
    </style>
</head>
<body>

<div class="cart-container">
    <div class="cart-main">
        <div class="cart-header">
            <h1>Кошик</h1>
        </div>

        <?php if (!empty($cart_items)): ?>
            <?php foreach ($cart_items as $p): 
                $id = $p['product_id'];
                $qty = $p['quantity'];
                
               
                $current_price = ($p['is_sale'] && $p['sale_price'] > 0) ? $p['sale_price'] : $p['price'];
                $sub = $current_price * $qty; 
                $total_sum += $sub;

                
                $img_path = !empty($p['image_url']) ? $p['image_url'] : "img/products/" . $id . ".jpg";
            ?>
            <div class="cart-item">
                <img src="<?php echo $img_path; ?>" class="item-img" onerror="this.src='https://via.placeholder.com/100x130?text=Beauty'">
                
                <div class="item-info">
                    <div class="mfg"><?php echo htmlspecialchars($p['manufacturer']); ?></div>
                    <div class="name"><?php echo htmlspecialchars($p['name']); ?></div>
                    
                    <div style="display: flex; align-items: center; gap: 20px; margin-top: 10px;">
                        <div class="qty-box">
                            <a href="cart_update.php?id=<?php echo $id; ?>&action=minus" class="qty-btn">−</a>
                            <span class="qty-val"><?php echo $qty; ?></span>
                            <a href="cart_update.php?id=<?php echo $id; ?>&action=plus" class="qty-btn">+</a>
                        </div>
                        <a href="cart_update.php?id=<?php echo $id; ?>&action=remove" style="text-decoration:none; font-size: 10px; color: #bbb; text-transform: uppercase; letter-spacing: 1px;">Видалити</a>
                    </div>
                </div>

                <div class="item-price">
                    <?php if($p['is_sale']): ?>
                        <div style="font-size: 12px; text-decoration: line-through; color: #bbb; font-family: Montserrat; font-weight: 400;">
                            <?php echo number_format($p['price'] * $qty, 0, '.', ' '); ?> ₴
                        </div>
                    <?php endif; ?>
                    <?php echo number_format($sub, 0, '.', ' '); ?> ₴
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-cart">
                <p style="color: #888; text-transform: uppercase; letter-spacing: 2px;">Ваш кошик порожній</p>
                <a href="catalog.php" class="checkout-btn" style="display: inline-block; margin-top: 30px; width: auto; padding: 15px 40px;">В каталог</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($total_sum > 0): ?>
    <div class="cart-sidebar">
        <div>
            <h2 class="sidebar-title">Підсумок</h2>
            <div class="summary-row">
                <span>Товари (<?php echo $total_count_items; ?>)</span>
                <span><?php echo number_format($total_sum, 0, '.', ' '); ?> ₴</span>
            </div>
            <div class="summary-row">
                <span>Доставка</span>
                <span>Безкоштовно</span>
            </div>
            
            <div class="total-price">
                <span style="font-size: 12px; display: block; text-transform: uppercase; letter-spacing: 2px; color: var(--accent);">До сплати</span>
                <?php echo number_format($total_sum, 0, '.', ' '); ?> ₴
            </div>
        </div>

        <div>
            <button class="checkout-btn" onclick="location.href='checkout.php'">Оформити зараз</button>
            <a href="catalog.php" class="back-link">← Продовжити покупки</a>
        </div>
    </div>
    <?php endif; ?>
</div>

</body>
</html>