<?php 
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login_register.php");
    exit();
}

$conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");
if ($conn->connect_error) { die("Помилка: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

$total_sum        = 0;
$cart_items       = [];
$total_count_items = 0;
$delivery_price   = 200;
$c_id             = intval($_SESSION['user_id']);


$cb_res           = $conn->query("SELECT discount FROM users WHERE user_id = $c_id");
$cashback_balance = floatval($cb_res->fetch_assoc()['discount'] ?? 0);


$order_res = $conn->query("SELECT order_id FROM orders WHERE user_id = $c_id AND status = 'accepted' ORDER BY order_id DESC LIMIT 1");

if ($order_res && $order_res->num_rows > 0) {
    $active_order_id = $order_res->fetch_assoc()['order_id'];

    $sql = "SELECT p.*, od.quantity, od.order_details_id, i.image_url 
            FROM Order_Details od 
            JOIN product p ON od.product_id = p.product_id 
            LEFT JOIN Images i ON p.product_id = i.product_id AND i.is_primary = 1
            WHERE od.order_id = $active_order_id AND od.status = 'cart'";
            
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cart_items[]       = $row;
            $total_count_items += $row['quantity'];
        }
    }
}

foreach ($cart_items as $p) {
    $total_sum += $p['price'] * $p['quantity'];
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кошик | BeautyStore Privé</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --gold:       #c5a059;
            --black:      #1a1a1a;
            --white:      #ffffff;
            --bg:         #fdfaf8;
            --border:     #eeebe6;
            --text-muted: #999;
            --sale:       #c0392b;
            --stock-low:  #e67e22;
            --stock-out:  #c0392b;
            --stock-ok:   #27ae60;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Montserrat', sans-serif; background: var(--bg); color: var(--black); }

      
        .cart-hero {
            background: var(--black);
            color: var(--white);
            padding: 70px 40px 60px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .cart-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 40% 0%, rgba(197,160,89,0.18) 0%, transparent 60%);
        }
        .cart-hero .eyebrow {
            font-size: 10px; font-weight: 800; letter-spacing: 8px;
            text-transform: uppercase; color: var(--gold);
            display: block; margin-bottom: 18px;
        }
        .cart-hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 400; font-style: italic;
            position: relative;
        }
        .cart-hero .meta {
            margin-top: 14px; font-size: 12px;
            color: rgba(255,255,255,0.4);
            letter-spacing: 2px; text-transform: uppercase;
            position: relative;
        }

    
        .cart-wrap {
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 40px 100px;
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 40px;
            align-items: start;
        }

        
        .cart-item {
            background: var(--white);
            border: 1px solid var(--border);
            display: grid;
            grid-template-columns: 110px 1fr auto;
            margin-bottom: 16px;
            transition: border-color .3s, box-shadow .3s;
            position: relative;
        }
        .cart-item:hover { border-color: var(--gold); box-shadow: 0 8px 30px rgba(0,0,0,0.05); }

        .item-img-wrap {
            background: #f8f5f0;
            border-right: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            padding: 15px; min-height: 150px;
        }
        .item-img { width: 80px; height: 110px; object-fit: contain; }

        .item-body { padding: 22px 25px; display: flex; flex-direction: column; justify-content: space-between; }
        .item-brand {
            font-size: 9px; font-weight: 800;
            text-transform: uppercase; letter-spacing: 3px;
            color: var(--gold); margin-bottom: 7px;
        }
        .item-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem; color: var(--black);
            text-decoration: none; line-height: 1.35;
            margin-bottom: 14px; display: block;
        }
        .item-name:hover { color: var(--gold); }

      
        .stock-badge {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 9px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1px;
            padding: 4px 10px; width: fit-content;
        }
        .stock-badge.ok  { background: #edfaf3; color: var(--stock-ok); }
        .stock-badge.low { background: #fef3e7; color: var(--stock-low); }
        .stock-badge.out { background: #fdecea; color: var(--stock-out); }

      
        .qty-row { display: flex; align-items: center; gap: 15px; margin-top: 15px; }
        .qty-box { display: flex; align-items: center; border: 1px solid var(--border); }
        .qty-btn {
            width: 36px; height: 36px;
            display: flex; align-items: center; justify-content: center;
            background: none; border: none; cursor: pointer;
            font-size: 16px; color: var(--black);
            transition: .25s; font-family: 'Montserrat', sans-serif;
        }
        .qty-btn:hover { background: var(--black); color: var(--white); }
        .qty-btn:disabled { color: #ddd; cursor: not-allowed; }
        .qty-val { padding: 0 16px; font-weight: 700; font-size: 14px; min-width: 20px; text-align: center; }

        .btn-remove-text {
            background: none; border: none; cursor: pointer;
            font-size: 10px; color: #ccc;
            text-transform: uppercase; letter-spacing: 1px;
            font-family: 'Montserrat', sans-serif;
            transition: color .25s; padding: 0;
        }
        .btn-remove-text:hover { color: var(--sale); }

       
        .item-price-col {
            padding: 22px 20px 22px 0;
            display: flex; flex-direction: column;
            align-items: flex-end; justify-content: center;
            gap: 6px; min-width: 110px;
        }
        .price-old { font-size: 11px; text-decoration: line-through; color: #bbb; }
        .price-curr {
            font-family: 'Playfair Display', serif;
            font-size: 1.45rem; font-weight: 700;
        }
        .price-curr.sale { color: var(--sale); }

       
        .cart-sidebar { position: sticky; top: 100px; display: flex; flex-direction: column; gap: 20px; }

        .sidebar-card {
            background: var(--white);
            border: 1px solid var(--border);
            padding: 35px;
        }
        .sidebar-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.45rem; font-weight: 400;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }
        .sum-row {
            display: flex; justify-content: space-between;
            font-size: 12px; margin-bottom: 13px;
            color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 1px;
        }
        .sum-row.cashback-row { color: var(--gold); font-weight: 700; }
        .sum-row.total-row {
            margin-top: 20px; padding-top: 20px;
            border-top: 2px solid var(--black);
            color: var(--black); font-weight: 700;
        }
        .sum-row.total-row span:last-child {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
        }

        .btn-checkout {
            display: block; width: 100%; margin-top: 25px;
            background: var(--black); color: var(--white);
            border: none; padding: 20px;
            font-family: 'Montserrat', sans-serif;
            font-size: 11px; font-weight: 800;
            text-transform: uppercase; letter-spacing: 4px;
            cursor: pointer; text-align: center;
            text-decoration: none; transition: .3s;
        }
        .btn-checkout:hover { background: var(--gold); color: var(--black); }

        .btn-continue {
            display: block; text-align: center; margin-top: 15px;
            font-size: 11px; color: var(--text-muted);
            text-decoration: none; text-transform: uppercase;
            letter-spacing: 2px; transition: color .25s;
        }
        .btn-continue:hover { color: var(--black); }

 
        .cb-card { background: #faf4e8; border: 1px solid #e8d5a3; padding: 25px; }
        .cb-card-title {
            font-size: 10px; font-weight: 800;
            text-transform: uppercase; letter-spacing: 3px;
            color: var(--gold); margin-bottom: 10px;
        }
        .cb-card-val {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem; color: var(--black);
        }
        .cb-card-hint { font-size: 11px; color: var(--text-muted); margin-top: 8px; line-height: 1.6; }


        .cart-empty {
            grid-column: 1 / -1;
            background: var(--white); border: 1px solid var(--border);
            text-align: center; padding: 100px 40px;
        }
        .cart-empty i { font-size: 3rem; color: #ddd; display: block; margin-bottom: 25px; }
        .cart-empty h2 { font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 400; margin-bottom: 12px; }
        .cart-empty p { color: var(--text-muted); font-size: 13px; letter-spacing: 1px; margin-bottom: 30px; }
        .btn-catalog {
            display: inline-block; background: var(--black); color: var(--white);
            padding: 16px 40px; text-decoration: none;
            font-size: 11px; font-weight: 800;
            text-transform: uppercase; letter-spacing: 3px;
            transition: .3s;
        }
        .btn-catalog:hover { background: var(--gold); color: var(--black); }

        
        .cart-item.loading { opacity: .5; pointer-events: none; }

      
        #toast {
            position: fixed; bottom: 30px; left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: var(--black); color: var(--white);
            padding: 15px 28px; font-size: 11px; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase;
            border-left: 3px solid var(--gold);
            opacity: 0; transition: all .4s; z-index: 9999;
            pointer-events: none;
        }
        #toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

        @media (max-width: 960px) {
            .cart-wrap { grid-template-columns: 1fr; padding: 0 20px 80px; }
            .cart-sidebar { position: static; }
            .cart-item { grid-template-columns: 90px 1fr; }
            .item-price-col { display: none; }
        }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>


<div class="cart-hero">
    <span class="eyebrow">Ваш вибір</span>
    <h1>Кошик</h1>
    <p class="meta">
        <?php if (!empty($cart_items)): ?>
            <?= $total_count_items ?> позиц<?= $total_count_items == 1 ? 'ія' : ($total_count_items < 5 ? 'ії' : 'ій') ?>
        <?php else: ?>
            Порожній
        <?php endif; ?>
    </p>
</div>

<div class="cart-wrap">

    <?php if (!empty($cart_items)): ?>

    <!-- ITEMS -->
    <div class="cart-items-list">
        <?php foreach ($cart_items as $p):
            $id    = $p['product_id'];
            $qty   = intval($p['quantity']);
            $stock = intval($p['stock'] ?? 99);
            $sub   = $p['price'] * $qty;
            $img   = !empty($p['image_url']) ? $p['image_url'] : "img/products/$id.jpg";
            $has_sale = !empty($p['old_price']) && $p['old_price'] > $p['price'];

            if ($stock === 0)   { $sc = 'out'; $sl = 'Немає в наявності'; $si = 'fa-xmark'; }
            elseif ($stock < 5) { $sc = 'low'; $sl = "Залишилось $stock шт"; $si = 'fa-fire'; }
            else                { $sc = 'ok';  $sl = 'В наявності'; $si = 'fa-check'; }
        ?>
        <div class="cart-item" id="item-<?= $id ?>">
            <div class="item-img-wrap">
                <img src="<?= htmlspecialchars($img) ?>" class="item-img"
                     onerror="this.src='https://via.placeholder.com/80x110?text=Beauty'">
            </div>

            <div class="item-body">
                <div>
                    <div class="item-brand"><?= htmlspecialchars($p['manufacturer'] ?? '') ?></div>
                    <a href="product_details.php?id=<?= $id ?>" class="item-name">
                        <?= htmlspecialchars($p['name']) ?>
                    </a>
                    <span class="stock-badge <?= $sc ?>">
                        <i class="fa-solid <?= $si ?>"></i> <?= $sl ?>
                    </span>
                </div>
                <div class="qty-row">
                    <div class="qty-box">
                        <button class="qty-btn" onclick="updateQty(<?= $id ?>, 'minus')"
                            <?= $qty <= 1 ? '' : '' ?>>−</button>
                        <span class="qty-val" id="qty-<?= $id ?>"><?= $qty ?></span>
                        <button class="qty-btn" onclick="updateQty(<?= $id ?>, 'plus')"
                            <?= $qty >= $stock ? 'disabled title="Більше немає на складі"' : '' ?>>+</button>
                    </div>
                    <button class="btn-remove-text" onclick="removeItem(<?= $id ?>)">
                        <i class="fa-solid fa-xmark"></i> Видалити
                    </button>
                </div>
            </div>

            <div class="item-price-col">
                <?php if ($has_sale): ?>
                    <span class="price-old"><?= number_format($p['old_price'] * $qty, 0, '.', ' ') ?> ₴</span>
                <?php endif; ?>
                <span class="price-curr <?= $has_sale ? 'sale' : '' ?>" id="price-<?= $id ?>">
                    <?= number_format($sub, 0, '.', ' ') ?> ₴
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    
    <div class="cart-sidebar">

     
        <?php if ($cashback_balance > 0): ?>
        <div class="cb-card">
            <div class="cb-card-title"><i class="fa-solid fa-coins"></i> Ваші бонуси</div>
            <div class="cb-card-val"><?= number_format($cashback_balance, 0, '.', ' ') ?> ₴</div>
            <p class="cb-card-hint">Можна застосувати при оформленні замовлення</p>
        </div>
        <?php endif; ?>

     
        <div class="sidebar-card">
            <h3 class="sidebar-title">Підсумок</h3>

            <div class="sum-row">
                <span>Товари (<?= $total_count_items ?>)</span>
                <span id="sidebar-goods"><?= number_format($total_sum, 0, '.', ' ') ?> ₴</span>
            </div>
            <div class="sum-row">
                <span>Доставка</span>
                <span><?= $delivery_price ?> ₴</span>
            </div>
            <?php if ($cashback_balance > 0): ?>
            <div class="sum-row cashback-row">
                <span><i class="fa-solid fa-coins"></i> Можлива знижка</span>
                <span>до −<?= number_format(min($cashback_balance, $total_sum), 0, '.', ' ') ?> ₴</span>
            </div>
            <?php endif; ?>
            <div class="sum-row total-row">
                <span>До сплати</span>
                <span id="sidebar-total"><?= number_format($total_sum + $delivery_price, 0, '.', ' ') ?> ₴</span>
            </div>

            <a href="checkout.php" class="btn-checkout">
                Оформити зараз
            </a>
            <a href="catalog.php" class="btn-continue">← Продовжити покупки</a>
        </div>

    </div>

    <?php else: ?>
    <div class="cart-empty">
        <i class="fa-regular fa-bag-shopping"></i>
        <h2>Кошик порожній</h2>
        <p>Додайте товари зі списку бажань або каталогу</p>
        <a href="catalog.php" class="btn-catalog">До каталогу</a>
    </div>
    <?php endif; ?>

</div>

<div id="toast"></div>

<script>
    const DELIVERY = <?= $delivery_price ?>;
    let prices = {
        <?php foreach ($cart_items as $p): ?>
        <?= $p['product_id'] ?>: <?= floatval($p['price']) ?>,
        <?php endforeach; ?>
    };
    let quantities = {
        <?php foreach ($cart_items as $p): ?>
        <?= $p['product_id'] ?>: <?= intval($p['quantity']) ?>,
        <?php endforeach; ?>
    };
    let stocks = {
        <?php foreach ($cart_items as $p): ?>
        <?= $p['product_id'] ?>: <?= intval($p['stock'] ?? 99) ?>,
        <?php endforeach; ?>
    };

    function updateQty(id, action) {
        const item = document.getElementById('item-' + id);
        item.classList.add('loading');

        fetch(`cart_update.php?id=${id}&action=${action}&ajax=1`)
            .then(r => r.text())
            .then(total => {
                if (action === 'plus')  quantities[id]++;
                if (action === 'minus') quantities[id] = Math.max(0, quantities[id] - 1);

             
                const qtyEl = document.getElementById('qty-' + id);
                if (qtyEl) qtyEl.textContent = quantities[id];

             
                const priceEl = document.getElementById('price-' + id);
                if (priceEl) priceEl.textContent = formatNum(prices[id] * quantities[id]) + ' ₴';

                
                const plusBtn = item.querySelector('.qty-btn:last-child');
                if (plusBtn) plusBtn.disabled = (quantities[id] >= stocks[id]);

                recalcSidebar();
                item.classList.remove('loading');
            })
            .catch(() => {
                window.location.href = `cart_update.php?id=${id}&action=${action}`;
            });
    }

    function removeItem(id) {
        const item = document.getElementById('item-' + id);
        item.classList.add('loading');

        fetch(`cart_update.php?id=${id}&action=remove&ajax=1`)
            .then(() => {
                item.style.transition = 'opacity .4s, max-height .4s';
                item.style.opacity = '0';
                item.style.overflow = 'hidden';
                item.style.maxHeight = item.offsetHeight + 'px';
                setTimeout(() => { item.style.maxHeight = '0'; }, 10);
                setTimeout(() => {
                    item.remove();
                    delete quantities[id];
                    delete prices[id];
                    recalcSidebar();

                    if (Object.keys(quantities).length === 0) {
                        location.reload();
                    }
                }, 450);
            })
            .catch(() => {
                window.location.href = `cart_update.php?id=${id}&action=remove`;
            });
    }

    function recalcSidebar() {
        let goods = 0;
        for (const id in quantities) {
            goods += prices[id] * quantities[id];
        }
        const goodsEl = document.getElementById('sidebar-goods');
        const totalEl = document.getElementById('sidebar-total');
        if (goodsEl) goodsEl.textContent = formatNum(goods) + ' ₴';
        if (totalEl) totalEl.textContent = formatNum(goods + DELIVERY) + ' ₴';
    }

    function formatNum(n) {
        return Math.round(n).toLocaleString('uk-UA').replace(/\u202f/g, ' ');
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
