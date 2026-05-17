<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: login_register.php");
    exit();
}

$conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Помилка підключення: " . $conn->connect_error);
}

$u_id = intval($_SESSION['user_id']);
$user_data = null;


$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $u_id);
$stmt->execute();
$u_res = $stmt->get_result();
if ($u_res) {
    $user_data = $u_res->fetch_assoc();
}


$user_cashback_balance = floatval($user_data['discount'] ?? 0);

$total_sum = 0;
$cart_items = [];
$delivery_price = 200;


$order_stmt = $conn->prepare("
    SELECT o.order_id 
    FROM orders o
    JOIN Order_Details od ON o.order_id = od.order_id
    WHERE o.user_id = ? AND od.status = 'cart'
    LIMIT 1
");
$order_stmt->bind_param("i", $u_id);
$order_stmt->execute();
$order_res = $order_stmt->get_result();

if ($order_res && $order_res->num_rows > 0) {
    $active_order_id = $order_res->fetch_assoc()['order_id'];
    

    $items_sql = "SELECT p.name, p.price, od.quantity 
                  FROM Order_Details od 
                  JOIN product p ON od.product_id = p.product_id 
                  WHERE od.order_id = ? AND od.status = 'cart'";
    
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param("i", $active_order_id);
    $items_stmt->execute();
    $res = $items_stmt->get_result();
    
    while ($row = $res->fetch_assoc()) {
        $cart_items[] = $row;

        $total_sum += $row['price'] * $row['quantity'];
    }
}


if (empty($cart_items)) {
    header("Location: catalog.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформлення | BeautyStore Privé</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #000000;
            --accent: #bc9c64; 
            --bg-page: #f8f8f8;
            --white: #ffffff;
            --border: #000000;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        
        body { 
            background: var(--bg-page); 
            color: var(--primary); 
            padding: 60px 20px;
            letter-spacing: -0.01em;
        }

        .checkout-container {
            max-width: 650px;
            margin: 0 auto;
            background: var(--white);
            padding: 60px;
            border: 3px solid var(--primary); 
        }

        .logo-box { text-align: center; margin-bottom: 50px; }
        .logo-box a { 
            font-family: 'Playfair Display', serif; 
            font-size: 2.5rem; 
            color: var(--primary); 
            text-decoration: none;
        }

        .step-marker {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 40px 0 25px 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .step-marker::after { content: ""; flex: 1; height: 2px; background: var(--primary); }

        .autofill-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: 0.3s;
        }
        .autofill-section:hover { background: #e8e8e8; }
        .autofill-section input { width: 18px; height: 18px; cursor: pointer; }
        .autofill-section label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; }

        .form-field { margin-bottom: 25px; }
        .form-field label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .form-field input, .form-field select, .form-field textarea {
            width: 100%;
            padding: 16px;
            border: 2px solid #eeeeee;
            background: #fff;
            font-size: 15px;
            font-weight: 500;
            color: var(--primary);
            outline: none;
            transition: all 0.2s ease;
            border-radius: 0;
        }

        .form-field input:focus, .form-field select:focus {
            border-color: var(--primary);
            background: #fafafa;
        }


        .cashback-usage-box {
            background: #faf4e8;
            padding: 25px;
            border: 1px solid var(--accent);
            margin-bottom: 30px;
        }
        .cb-header { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 13px; font-weight: 700; color: var(--accent); }
        .cb-input-wrapper { display: flex; flex-direction: column; gap: 8px; }
        .cb-input-wrapper input { padding: 12px; border: 1px solid var(--accent); font-weight: 700; outline: none; }

        .order-summary {
            background: #000;
            color: #fff;
            padding: 40px;
            margin: 50px 0;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 12px;
            opacity: 0.8;
        }

        .total-line {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .total-line.no-border { border-top: none; margin-top: 5px; padding-top: 5px; }

        .total-line span { font-size: 12px; font-weight: 700; text-transform: uppercase; color: #aaa; }
        .total-line.final span { color: var(--accent); }
        .total-line b { font-family: 'Playfair Display', serif; font-size: 1.5rem; color: #fff; }
        .total-line.final b { font-size: 2.5rem; }

        .submit-btn {
            width: 100%;
            background: var(--primary);
            color: var(--white);
            border: 2px solid var(--primary);
            padding: 25px;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 4px;
            cursor: pointer;
            transition: 0.4s;
        }

        .submit-btn:hover { background: var(--white); color: var(--primary); }

        .back-nav { text-align: center; margin-top: 40px; }
        .back-nav a { font-size: 11px; font-weight: 600; color: #999; text-decoration: none; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>

<div class="checkout-container">
    <div class="logo-box">
        <a href="index.php">BeautyStore</a>
    </div>

    <?php if ($user_data): ?>
    <div class="autofill-section" onclick="toggleAutofill()">
        <input type="checkbox" id="iam_receiver">
        <label for="iam_receiver">Я отримувач (використати дані профілю)</label>
    </div>
    <?php endif; ?>

    <form action="process_order.php" method="POST" id="checkoutForm">
        
        <div class="step-marker">01. ПЕРСОНАЛЬНІ ДАНІ</div>
        <div class="form-field"><label>Ім'я</label><input type="text" name="fname" id="f_fname" required></div>
        <div class="form-field"><label>Прізвище</label><input type="text" name="lname" id="f_lname" required></div>
        <div class="form-field"><label>Електронна пошта</label><input type="email" name="email" id="f_email" required></div>
        <div class="form-field"><label>Телефон</label><input type="tel" name="phone" id="f_phone" placeholder="+380" required></div>

        <div class="step-marker">02. ДОСТАВКА ТА ОПЛАТА</div>
        <div class="form-field">
            <label>Область</label>
            <select name="region" id="f_region" required>
                <option value="">Оберіть область...</option>
                <option value="Вінницька">Вінницька</option>
                <option value="Львівська">Львівська</option>
                <option value="Київська">Київська</option>
                <option value="Одеська">Одеська</option>
                <option value="м. Київ">м. Київ</option>
            </select>
        </div>
        <div class="form-field"><label>Місто</label><input type="text" name="city" id="f_city" required></div>
        <div class="form-field"><label>Адреса або № відділення НП</label><input type="text" name="address" id="f_address" required></div>
        
        <div class="form-field">
            <label>Спосіб оплати</label>
            <select name="payment" required>
                <option value="cash">Оплата при отриманні</option>
                <option value="card">Картою на сайті</option>
            </select>
        </div>

        <div class="form-field">
            <label>Коментар (необов'язково)</label>
            <textarea name="comment" rows="2" placeholder="Наприклад: кодовий замок або час доставки..."></textarea>
        </div>


        <?php if ($user_cashback_balance > 0): ?>
        <div class="step-marker" style="color: var(--accent);">03. ВАШІ БОНУСИ</div>
        <div class="cashback-usage-box">
            <div class="cb-header">
                <span>Ваш баланс:</span>
                <span><?= number_format($user_cashback_balance, 0) ?> ₴</span>
            </div>
            <div class="cb-input-wrapper">
                <input type="number" name="cashback_to_use" id="cashback_input" 
                       placeholder="Введіть суму списання..." min="0" 
                       max="<?= min($user_cashback_balance, $total_sum) ?>" step="1"
                       oninput="calculateTotal()">
                <small style="color: var(--accent); font-size: 11px; margin-top: 5px;">
                    *Можна списати до <?= number_format(min($user_cashback_balance, $total_sum), 0) ?> ₴
                </small>
            </div>
        </div>
        <?php endif; ?>

        <div class="order-summary">
            <?php foreach ($cart_items as $item): 
                $sub = $item['price'] * $item['quantity']; 
            ?>
            <div class="order-item">
                <span><?= htmlspecialchars($item['name']) ?> (x<?= $item['quantity'] ?>)</span>
                <b><?= number_format($sub, 0, '.', ' ') ?> ₴</b>
            </div>
            <?php endforeach; ?>

            <div class="total-line">
                <span>Вартість товарів</span>
                <b><?= number_format($total_sum, 0, '.', ' ') ?> ₴</b>
            </div>
            <div class="total-line no-border">
                <span>Доставка</span>
                <b><?= $delivery_price ?> ₴</b>
            </div>

            <div class="total-line no-border" id="cb_row" style="display:none;">
                <span style="color:var(--accent);">Списання бонусів</span>
                <b id="cb_display_val" style="color:var(--accent);">- 0 ₴</b>
            </div>

            <div class="total-line final">
                <span>До сплати</span>
                <b id="final_total_display"><?= number_format($total_sum + $delivery_price, 0, '.', ' ') ?> ₴</b>
            </div>
        </div>

        <button type="submit" class="submit-btn">ОФОРМИТИ ЗАМОВЛЕННЯ</button>
    </form>

    <div class="back-nav"><a href="cart.php">← Повернутися до кошика</a></div>
</div>

<script>

    const userData = <?= $user_data ? json_encode($user_data) : 'null' ?>;
    const maxBalance = <?= $user_cashback_balance ?>;
    const goodsTotal = <?= $total_sum ?>;
    const delivery = <?= $delivery_price ?>;

    function calculateTotal() {
        const input = document.getElementById('cashback_input');
        const cbRow = document.getElementById('cb_row');
        const cbDisplay = document.getElementById('cb_display_val');
        const finalDisplay = document.getElementById('final_total_display');

        if (!input) return;

        let val = parseFloat(input.value) || 0;


        if (val > maxBalance) val = maxBalance;
        if (val > goodsTotal) val = goodsTotal;
        if (val < 0) val = 0;
        

        if (input.value !== "" && parseFloat(input.value) !== val) {
            input.value = val;
        }

        if (val > 0) {
            cbRow.style.display = 'flex';
            cbDisplay.innerText = '- ' + val.toLocaleString() + ' ₴';
        } else {
            cbRow.style.display = 'none';
        }

        const finalSum = (goodsTotal - val) + delivery;
        finalDisplay.innerText = finalSum.toLocaleString() + ' ₴';
    }

    function toggleAutofill() {
        const checkbox = document.getElementById('iam_receiver');

        if (event.target !== checkbox) checkbox.checked = !checkbox.checked;

        if (checkbox.checked && userData) {
            document.getElementById('f_fname').value = userData.first_name || '';
            document.getElementById('f_lname').value = userData.last_name || '';
            document.getElementById('f_email').value = userData.email || '';
            document.getElementById('f_phone').value = userData.phone_number || '';
            
            if (userData.region) {
                const regionValue = userData.region.trim();
                const selectElement = document.getElementById('f_region');
                selectElement.value = regionValue; 
                

                if (selectElement.selectedIndex === -1 && regionValue !== "") {
                    const newOpt = new Option(regionValue, regionValue);
                    selectElement.add(newOpt);
                    selectElement.value = regionValue;
                }
            }
            document.getElementById('f_city').value = userData.city || '';
            document.getElementById('f_address').value = userData.street_house || '';
        } else {

            ['f_fname', 'f_lname', 'f_email', 'f_phone', 'f_region', 'f_city', 'f_address'].forEach(id => {
                document.getElementById(id).value = '';
            });
        }
    }
</script>
</body>
</html>
