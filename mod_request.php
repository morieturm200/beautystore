<?php


error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($pdo)) {
    $host = 'localhost';
    $db   = 'beautystore';
    $user = 'root';
    $pass = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        die("<div style='padding:50px; background:#1a1a1a; color:#ff4444; font-family:sans-serif;'>
                <h2>Помилка бази даних</h2><p>{$e->getMessage()}</p>
             </div>");
    }
}


$categories_list = [];
$customers_list = [];

try {
    
    $cat_stmt = $pdo->query("SELECT DISTINCT category FROM product WHERE category IS NOT NULL AND category != ''");
    $categories_list = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);

    
    $cust_stmt = $pdo->query("SELECT customer_id, first_name, last_name, email FROM customer ORDER BY first_name ASC");
    $customers_list = $cust_stmt->fetchAll();
} catch (Exception $e) {
    $system_error = "Помилка завантаження довідників: " . $e->getMessage();
}


$action = $_POST['action'] ?? null;
$query_results = [];
$query_error = null;
$execution_time = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action) {
    $start_time = microtime(true);
    try {
        switch ($action) {
            
            
            case 'q1_full_info':
                $sql = "SELECT 
                            p.product_id AS 'ID', 
                            p.name AS 'product_name', 
                            p.price AS 'Ціна', 
                            p.category AS 'Категорія', 
                            p.manufacturer AS 'Виробник', 
                            p.subcategory AS 'Підкатегорія', 
                            p.stock AS 'Залишок', 
                            p.color AS 'Колір', 
                            p.weight AS 'Вага', 
                            p.warranty AS 'Гарантія',
                            GROUP_CONCAT(CONCAT(c.characteristic_name, ': ', c.characteristic_value) SEPARATOR '; ') AS 'characteristics'
                        FROM product p
                        LEFT JOIN characteristics c ON p.product_id = c.product_id
                        GROUP BY p.product_id";
                $stmt = $pdo->query($sql);
                $query_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            
            case 'q2_by_category':
                $selected_cat = $_POST['category_filter'] ?? '';
                $sql = "SELECT 
                            product_id AS 'ID', 
                            name AS 'Назва', 
                            price AS 'Ціна', 
                            manufacturer AS 'Виробник', 
                            subcategory AS 'Підкатегорія', 
                            description AS 'Опис', 
                            stock AS 'Залишок'
                        FROM product 
                        WHERE category = :cat 
                        ORDER BY price DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':cat' => $selected_cat]);
                $query_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            
            case 'q3_top5_sold':
                $sql = "SELECT 
                            p.product_id AS 'ID', 
                            p.name AS 'Назва', 
                            p.category AS 'Категорія', 
                            p.manufacturer AS 'Виробник', 
                            SUM(od.quantity) AS 'total_quantity_sold'
                        FROM product p
                        JOIN Order_Details od ON p.product_id = od.product_id
                        GROUP BY p.product_id, p.name, p.category, p.manufacturer
                        ORDER BY total_quantity_sold DESC
                        LIMIT 5";
                $stmt = $pdo->query($sql);
                $query_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            
            case 'q4_client_history':
                $client_id = $_POST['client_filter'] ?? 0;
                $sql = "SELECT 
                            c.customer_id AS 'ID Клієнта', 
                            c.first_name AS 'Ім\'я', 
                            c.last_name AS 'Прізвище', 
                            p.product_id AS 'ID Товару', 
                            p.name AS 'product_name', 
                            od.quantity AS 'Кількість', 
                            p.price AS 'Ціна од.', 
                            (od.quantity * p.price) AS 'total_per_product',
                            SUM(od.quantity * p.price) OVER (PARTITION BY c.customer_id) AS 'total_spent'
                        FROM customer c
                        JOIN orders o ON c.customer_id = o.customer_id
                        JOIN Order_Details od ON o.order_id = od.order_id
                        JOIN product p ON od.product_id = p.product_id
                        WHERE c.customer_id = :cid";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':cid' => $client_id]);
                $query_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            
            case 'q5_top_categories':
                $sql = "SELECT 
                            p.category AS 'Категорія', 
                            SUM(od.quantity) AS 'total_quantity_sold'
                        FROM product p
                        JOIN Order_Details od ON p.product_id = od.product_id
                        GROUP BY p.category
                        ORDER BY total_quantity_sold DESC";
                $stmt = $pdo->query($sql);
                $query_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            
            case 'q6_loyal_clients':
                $sql = "SELECT 
                            c.customer_id AS 'ID', 
                            c.first_name AS 'Ім\'я', 
                            c.last_name AS 'Прізвище', 
                            c.email AS 'E-mail', 
                            COUNT(DISTINCT o.order_id) AS 'order_count'
                        FROM customer c
                        JOIN orders o ON c.customer_id = o.customer_id
                        GROUP BY c.customer_id, c.first_name, c.last_name, c.email
                        HAVING order_count > 1
                        ORDER BY order_count DESC";
                $stmt = $pdo->query($sql);
                $query_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            
            case 'q7_brand_flagships':
                $sql = "WITH RankedProducts AS (
                            SELECT 
                                p.manufacturer, 
                                p.product_id, 
                                p.name, 
                                SUM(od.quantity) AS total_quantity_sold,
                                ROW_NUMBER() OVER (PARTITION BY p.manufacturer ORDER BY SUM(od.quantity) DESC) AS rn
                            FROM product p
                            JOIN Order_Details od ON p.product_id = od.product_id
                            GROUP BY p.manufacturer, p.product_id, p.name
                        )
                        SELECT 
                            manufacturer AS 'Виробник', 
                            product_id AS 'ID', 
                            name AS 'Флагманська модель', 
                            total_quantity_sold AS 'Продано шт.'
                        FROM RankedProducts
                        WHERE rn = 1
                        ORDER BY total_quantity_sold DESC";
                $stmt = $pdo->query($sql);
                $query_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            
            case 'q8_high_spenders':
                $amount = (float)($_POST['amount_filter'] ?? 100000);
                $sql = "SELECT 
                            c.customer_id AS 'ID', 
                            c.first_name AS 'Ім\'я', 
                            c.last_name AS 'Прізвище', 
                            c.email AS 'E-mail', 
                            SUM(od.quantity * p.price) AS 'total_spent'
                        FROM customer c
                        JOIN orders o ON c.customer_id = o.customer_id
                        JOIN Order_Details od ON o.order_id = od.order_id
                        JOIN product p ON od.product_id = p.product_id
                        GROUP BY c.customer_id, c.first_name, c.last_name, c.email
                        HAVING total_spent > :amount
                        ORDER BY total_spent DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':amount' => $amount]);
                $query_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            
            case 'q9_never_sold':
                $sql = "SELECT 
                            p.product_id AS 'ID', 
                            p.name AS 'Назва товару', 
                            p.category AS 'Категорія', 
                            p.manufacturer AS 'Виробник', 
                            p.stock AS 'Залишок'
                        FROM product p
                        LEFT JOIN Order_Details od ON p.product_id = od.product_id
                        WHERE od.product_id IS NULL";
                $stmt = $pdo->query($sql);
                $query_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            
            case 'q10_low_stock':
                $sql = "SELECT 
                            product_id AS 'ID', 
                            name AS 'Назва', 
                            stock AS 'Залишок шт.'
                        FROM product
                        WHERE stock < 15
                        ORDER BY stock ASC";
                $stmt = $pdo->query($sql);
                $query_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
        }
    } catch (Exception $e) {
        $query_error = "Помилка SQL: " . $e->getMessage();
    }
    $execution_time = round(microtime(true) - $start_time, 4);
}


function renderOutputTable($data, $error, $exec_time) {
    if ($error) {
        return "<div class='p-6 bg-red-50 text-red-600 rounded-2xl border border-red-200 font-bold text-xs uppercase tracking-widest mt-6 shadow-sm'>⚠️ {$error}</div>";
    }
    if (empty($data)) {
        return "<div class='p-12 text-center text-gray-400 font-black uppercase tracking-[5px] bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200 mt-6'>Дані відсутні або порожній результат</div>";
    }

    $html = "<div class='mt-8 mb-4 flex justify-between items-center text-[10px] font-black text-gray-400 uppercase tracking-widest'>";
    $html .= "<span>Знайдено: <span class='text-[#c5a059]'>" . count($data) . " рядків</span></span>";
    $html .= "<span>Виконано за: {$exec_time} сек</span>";
    $html .= "</div>";
    
    $html .= "<div class='overflow-x-auto bg-white rounded-[30px] border border-gray-100 shadow-xl'>";
    $html .= "<table class='w-full text-left border-collapse'>";
    $html .= "<thead class='bg-gray-50/80 border-b-2 border-gray-100'><tr>";
    
    foreach (array_keys($data[0]) as $head) {
        $html .= "<th class='p-6 text-[10px] font-black uppercase text-gray-500 tracking-[2px]'>" . str_replace('_', ' ', $head) . "</th>";
    }
    $html .= "</tr></thead><tbody class='text-[11px] font-bold text-gray-800'>";
    
    foreach ($data as $row) {
        $html .= "<tr class='hover:bg-[#fcfaf8] transition-colors border-b border-gray-50 last:border-0'>";
        foreach ($row as $key => $val) {
            if (strpos(mb_strtolower($key), 'ціна') !== false || strpos(mb_strtolower($key), 'spent') !== false || strpos(mb_strtolower($key), 'прибуток') !== false || strpos(mb_strtolower($key), 'разом') !== false) {
                $html .= "<td class='p-6 text-gray-900 font-black whitespace-nowrap'>" . number_format((float)$val, 0, '.', ' ') . " ₴</td>";
            } elseif ($key == 'characteristics' || $key == 'Повні характеристики') {
                $html .= "<td class='p-6 text-[10px] text-gray-400 font-medium italic lowercase max-w-xs truncate' title='" . htmlspecialchars((string)$val) . "'>" . htmlspecialchars((string)$val) . "</td>";
            } elseif (strpos(mb_strtolower($key), 'залишок') !== false && (int)$val < 15) {
                $html .= "<td class='p-6 whitespace-nowrap'><span class='bg-red-50 text-red-500 px-3 py-1 rounded-full text-[9px] font-black uppercase'>🚨 {$val} шт</span></td>";
            } else {
                $html .= "<td class='p-6'>" . htmlspecialchars((string)$val) . "</td>";
            }
        }
        $html .= "</tr>";
    }
    $html .= "</tbody></table></div>";
    return $html;
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BI Console | BeautyStore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --c-gold: #c5a059; --c-black: #0a0a0a; --c-bg: #fafafa; }
        body { font-family: 'Montserrat', sans-serif; background-color: var(--c-bg); color: var(--c-black); }
        
        
        details {
            background: #ffffff;
            border: 1px solid #f0f0f0;
            border-radius: 35px;
            margin-bottom: 24px;
            box-shadow: 0 10px 30px -15px rgba(0,0,0,0.03);
            transition: all 0.4s ease;
            overflow: hidden;
        }
        details[open] {
            box-shadow: 0 30px 60px -15px rgba(0,0,0,0.08);
            border-color: #eaeaea;
            transform: scale(1.01);
        }
        summary {
            padding: 30px 40px;
            font-size: 14px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            list-style: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            transition: 0.3s;
            user-select: none;
        }
        summary::-webkit-details-marker { display: none; }
        summary:hover { background: #fcfaf8; color: var(--c-gold); }
        details[open] summary { border-bottom: 1px solid #f0f0f0; background: #fafafa; color: var(--c-gold); }
        
        summary::after {
            content: '+';
            font-size: 24px;
            font-weight: 400;
            color: #ccc;
            transition: transform 0.4s ease, color 0.3s ease;
        }
        details[open] summary::after { content: '−'; color: var(--c-gold); transform: rotate(180deg); }

        .details-body { padding: 40px; background: #ffffff; animation: fadeDown 0.4s ease-out; }
        @keyframes fadeDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        
        .form-control {
            width: 100%; max-width: 450px;
            padding: 16px 24px;
            border-radius: 20px;
            border: 2px solid #f0f0f0;
            background: #fafafa;
            font-family: 'Montserrat', sans-serif;
            font-size: 12px; font-weight: 800; color: #333;
            outline: none; transition: all 0.3s ease;
        }
        .form-control:focus { border-color: var(--c-gold); background: #fff; box-shadow: 0 10px 20px rgba(197,160,89,0.1); }
        
        .btn-submit {
            background: var(--c-black);
            color: #fff;
            padding: 16px 36px;
            border-radius: 20px;
            font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px;
            cursor: pointer; transition: all 0.3s ease; border: none;
        }
        .btn-submit:hover { background: var(--c-gold); transform: translateY(-3px); box-shadow: 0 15px 25px rgba(197,160,89,0.3); }

        
        ::-webkit-scrollbar { height: 8px; width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #d1d1d1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--c-gold); }
    </style>
</head>
<body class="p-6 md:p-12">

    <header class="max-w-7xl mx-auto mb-20 flex flex-col md:flex-row justify-between items-start md:items-center gap-8">
        <div>
            <h1 class="text-6xl font-black uppercase tracking-tighter italic leading-none text-gray-900 mb-2">SQL <span class="text-[#c5a059]">Console</span></h1>
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-[8px] ml-1">Монолітний модуль запитів (POST System)</p>
        </div>
        <div class="bg-white px-8 py-5 rounded-[30px] border border-gray-100 shadow-xl flex items-center gap-5">
            <div class="text-right">
                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">State: Active</p>
                <p class="text-xs font-black text-green-500 uppercase tracking-widest">Router Safe Mode</p>
            </div>
            <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center">
                <div class="w-3 h-3 bg-green-500 rounded-full animate-ping absolute"></div>
                <div class="w-3 h-3 bg-green-500 rounded-full relative z-10"></div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto">
        <div class="mb-10 pl-4 border-l-4 border-[#c5a059]">
            <h3 class="text-sm font-black uppercase text-gray-800 tracking-[3px] italic">Доступні аналітичні лістинги:</h3>
            <p class="text-[10px] font-bold text-gray-400 mt-2 uppercase tracking-widest">Оберіть секцію, введіть параметри та натисніть "Виконати"</p>
        </div>

        <details <?= $action === 'q1_full_info' ? 'open' : '' ?>>
            <summary><span class="text-[#c5a059] mr-3">1</span> Повна інформація про кожен продукт</summary>
            <div class="details-body">
                <div class="mb-8 p-6 bg-[#fcfaf8] rounded-2xl border border-[#f0e6e0] text-xs font-bold text-gray-600 italic">
                    Запит витягує деталі кожного товару, агрегуючи характеристики через GROUP_CONCAT. Не потребує параметрів.
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="q1_full_info">
                    <button type="submit" class="btn-submit">Виконати SQL-запит</button>
                </form>
                <?php if($action === 'q1_full_info') echo renderOutputTable($query_results, $query_error, $execution_time); ?>
            </div>
        </details>

        <details <?= $action === 'q2_by_category' ? 'open' : '' ?>>
            <summary><span class="text-[#c5a059] mr-3">2</span> Усі продукти з обраної категорії</summary>
            <div class="details-body">
                <div class="mb-8 p-6 bg-[#fcfaf8] rounded-2xl border border-[#f0e6e0] text-xs font-bold text-gray-600 italic">
                    Динамічний запит. Дозволяє переглянути товари за вказаною категорією для сегментованого аналізу запасів.
                </div>
                <form method="POST" class="flex flex-col md:flex-row items-start md:items-end gap-6">
                    <input type="hidden" name="action" value="q2_by_category">
                    <div class="flex-1 w-full md:w-auto">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3 ml-2">Оберіть категорію з бази:</label>
                        <select name="category_filter" class="form-control" required>
                            <option value="">-- Натисніть для вибору --</option>
                            <?php foreach($categories_list as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= (isset($_POST['category_filter']) && $_POST['category_filter'] == $cat) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-submit w-full md:w-auto">Застосувати фільтр</button>
                </form>
                <?php if($action === 'q2_by_category') echo renderOutputTable($query_results, $query_error, $execution_time); ?>
            </div>
        </details>

        <details <?= $action === 'q3_top5_sold' ? 'open' : '' ?>>
            <summary><span class="text-[#c5a059] mr-3">3</span> Топ 5 проданих продуктів</summary>
            <div class="details-body">
                <div class="mb-8 p-6 bg-[#fcfaf8] rounded-2xl border border-[#f0e6e0] text-xs font-bold text-gray-600 italic">
                    Скрипт визначає п’ять найпопулярніших товарів за кількістю реалізованих одиниць (агрегація SUM).
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="q3_top5_sold">
                    <button type="submit" class="btn-submit">Визначити бестселери</button>
                </form>
                <?php if($action === 'q3_top5_sold') echo renderOutputTable($query_results, $query_error, $execution_time); ?>
            </div>
        </details>

        <details <?= $action === 'q4_client_history' ? 'open' : '' ?>>
            <summary><span class="text-[#c5a059] mr-3">4</span> Усі продукти, куплені обраним клієнтом</summary>
            <div class="details-body">
                <div class="mb-8 p-6 bg-[#fcfaf8] rounded-2xl border border-[#f0e6e0] text-xs font-bold text-gray-600 italic">
                    Запит показує історію покупок конкретного клієнта. Використовує віконну функцію (SUM OVER PARTITION) для розрахунку загальної суми.
                </div>
                <form method="POST" class="flex flex-col md:flex-row items-start md:items-end gap-6">
                    <input type="hidden" name="action" value="q4_client_history">
                    <div class="flex-1 w-full md:w-auto">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3 ml-2">Оберіть клієнта:</label>
                        <select name="client_filter" class="form-control" required>
                            <option value="">-- База клієнтів --</option>
                            <?php foreach($customers_list as $c): ?>
                                <option value="<?= $c['customer_id'] ?>" <?= (isset($_POST['client_filter']) && $_POST['client_filter'] == $c['customer_id']) ? 'selected' : '' ?>>
                                    [ID: <?= $c['customer_id'] ?>] <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-submit w-full md:w-auto">Отримати історію</button>
                </form>
                <?php if($action === 'q4_client_history') echo renderOutputTable($query_results, $query_error, $execution_time); ?>
            </div>
        </details>

        <details <?= $action === 'q5_top_categories' ? 'open' : '' ?>>
            <summary><span class="text-[#c5a059] mr-3">5</span> Топ категорії за продажами</summary>
            <div class="details-body">
                <div class="mb-8 p-6 bg-[#fcfaf8] rounded-2xl border border-[#f0e6e0] text-xs font-bold text-gray-600 italic">
                    Цей запит ранжує категорії за обсягом продажів для виявлення трендів ринку.
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="q5_top_categories">
                    <button type="submit" class="btn-submit">Генерувати рейтинг</button>
                </form>
                <?php if($action === 'q5_top_categories') echo renderOutputTable($query_results, $query_error, $execution_time); ?>
            </div>
        </details>

        <details <?= $action === 'q6_loyal_clients' ? 'open' : '' ?>>
            <summary><span class="text-[#c5a059] mr-3">6</span> Клієнти з кількістю замовлень більше 1</summary>
            <div class="details-body">
                <div class="mb-8 p-6 bg-[#fcfaf8] rounded-2xl border border-[#f0e6e0] text-xs font-bold text-gray-600 italic">
                    Запит використовує GROUP BY та HAVING для виявлення активних користувачів (Retention Rate).
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="q6_loyal_clients">
                    <button type="submit" class="btn-submit">Знайти лояльних клієнтів</button>
                </form>
                <?php if($action === 'q6_loyal_clients') echo renderOutputTable($query_results, $query_error, $execution_time); ?>
            </div>
        </details>

        <details <?= $action === 'q7_brand_flagships' ? 'open' : '' ?>>
            <summary><span class="text-[#c5a059] mr-3">7</span> Найкраще продаваний продукт від кожного виробника</summary>
            <div class="details-body">
                <div class="mb-8 p-6 bg-[#fcfaf8] rounded-2xl border border-[#f0e6e0] text-xs font-bold text-gray-600 italic">
                    Складний запит: використовує CTE (WITH) та аналітичну функцію ROW_NUMBER() для ранжування всередині груп (виробників).
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="q7_brand_flagships">
                    <button type="submit" class="btn-submit">Показати флагмани брендів</button>
                </form>
                <?php if($action === 'q7_brand_flagships') echo renderOutputTable($query_results, $query_error, $execution_time); ?>
            </div>
        </details>

        <details <?= $action === 'q8_high_spenders' ? 'open' : '' ?>>
            <summary><span class="text-[#c5a059] mr-3">8</span> Клієнти, які витратили понад вказану суму</summary>
            <div class="details-body">
                <div class="mb-8 p-6 bg-[#fcfaf8] rounded-2xl border border-[#f0e6e0] text-xs font-bold text-gray-600 italic">
                    Скрипт показує VIP-клієнтів, чия загальна сума покупок перевищує заданий поріг (наприклад, 100 000 грн).
                </div>
                <form method="POST" class="flex flex-col md:flex-row items-start md:items-end gap-6">
                    <input type="hidden" name="action" value="q8_high_spenders">
                    <div class="flex-1 w-full md:w-auto">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3 ml-2">Введіть суму порогу (грн):</label>
                        <input type="number" name="amount_filter" value="<?= htmlspecialchars($_POST['amount_filter'] ?? '100000') ?>" class="form-control w-full" required>
                    </div>
                    <button type="submit" class="btn-submit w-full md:w-auto">Відфільтрувати VIP</button>
                </form>
                <?php if($action === 'q8_high_spenders') echo renderOutputTable($query_results, $query_error, $execution_time); ?>
            </div>
        </details>

        <details <?= $action === 'q9_never_sold' ? 'open' : '' ?>>
            <summary><span class="text-[#c5a059] mr-3">9</span> Продукти, які ніколи не купувались</summary>
            <div class="details-body">
                <div class="mb-8 p-6 bg-[#fcfaf8] rounded-2xl border border-[#f0e6e0] text-xs font-bold text-gray-600 italic">
                    Запит виводить товари без продажів за допомогою LEFT JOIN та перевірки на IS NULL. Створено для аналізу неліквіду.
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="q9_never_sold">
                    <button type="submit" class="btn-submit">Знайти неліквід (Dead Stock)</button>
                </form>
                <?php if($action === 'q9_never_sold') echo renderOutputTable($query_results, $query_error, $execution_time); ?>
            </div>
        </details>

        <details <?= $action === 'q10_low_stock' ? 'open' : '' ?>>
            <summary><span class="text-[#c5a059] mr-3">10</span> Продукти з запасом менше 15 одиниць</summary>
            <div class="details-body">
                <div class="mb-8 p-6 bg-[#fcfaf8] rounded-2xl border border-[#f0e6e0] text-xs font-bold text-gray-600 italic">
                    Моніторинг критичного рівня залишків. Додано для ефективного відстежування складських запасів.
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="q10_low_stock">
                    <button type="submit" class="btn-submit">Перевірити складські ризики</button>
                </form>
                <?php if($action === 'q10_low_stock') echo renderOutputTable($query_results, $query_error, $execution_time); ?>
            </div>
        </details>

    </main>

    <footer class="max-w-7xl mx-auto mt-20 pt-10 pb-10 border-t border-gray-200 text-center">
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-[10px] italic">BeautyStore Core Engine © 2026</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
           
            const openDetail = document.querySelector('details[open]');
            if (openDetail) {
                setTimeout(() => {
                    openDetail.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 150);
            }

            
            const detailsElements = document.querySelectorAll('details');
            detailsElements.forEach((targetDetail) => {
                targetDetail.addEventListener('click', (e) => {
                    
                    if(e.target.tagName !== 'SUMMARY' && e.target.closest('summary') === null) return;
                    
                    detailsElements.forEach((detail) => {
                        if (detail !== targetDetail && detail.hasAttribute('open')) {
                            detail.removeAttribute('open');
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>