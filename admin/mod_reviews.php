<?php
// 1. ПІДКЛЮЧЕННЯ
$host = "localhost"; $db = "beautystore"; $user = "beautyuser"; $pass = "1234";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) { die("Помилка БД"); }

// --- 1. ЛОГІКА ВИДАЛЕННЯ ТА ВІДПОВІДІ ---
if (isset($_GET['del_rev'])) {
    $pdo->prepare("DELETE FROM Reviews WHERE review_id = ?")->execute([$_GET['del_rev']]);
    header("Location: admin_prive.php?tab=reviews&product_id=" . ($_GET['product_id'] ?? '')); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_reply'])) {
    $rev_id = intval($_POST['review_id']);
    $reply_text = trim($_POST['admin_reply']);
    $pdo->prepare("UPDATE Reviews SET admin_reply = ? WHERE review_id = ?")->execute([$reply_text, $rev_id]);
    header("Location: admin_prive.php?tab=reviews&product_id=" . $_POST['p_id'] . "&msg=Відповідь збережена"); exit();
}

// --- 2. ГРУПУВАННЯ ТОВАРІВ З ПІДРАХУНКОМ НОВИХ ВІДГУКІВ ---
$products_query = $pdo->query("
    SELECT p.product_id, p.name, p.manufacturer,
           COUNT(r.review_id) as rev_count,
           AVG(r.rating) as avg_rating,
           /* Рахуємо відгуки, де admin_reply порожній */
           SUM(CASE WHEN r.admin_reply IS NULL OR r.admin_reply = '' THEN 1 ELSE 0 END) as unreplied_count,
           (SELECT image_url FROM Images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as p_img
    FROM product p
    JOIN Reviews r ON p.product_id = r.product_id
    GROUP BY p.product_id
    ORDER BY unreplied_count DESC, rev_count DESC
");
$review_groups = $products_query->fetchAll(PDO::FETCH_ASSOC);

// --- 3. ОТРИМАННЯ ВІДГУКІВ ДЛЯ ОБРАНОГО ТОВАРУ ---
$active_product = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
$product_reviews = [];
if ($active_product) {
    $rev_st = $pdo->prepare("
        SELECT r.*, c.first_name, c.email 
        FROM Reviews r 
        JOIN customer c ON r.customer_id = c.customer_id 
        WHERE r.product_id = ? 
        ORDER BY (r.admin_reply IS NULL OR r.admin_reply = '') DESC, r.review_date DESC
    ");
    $rev_st->execute([$active_product]);
    $product_reviews = $rev_st->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="flex flex-col lg:flex-row gap-8 min-h-[650px]">
    
    <div class="w-full lg:w-1/3 space-y-4">
        <h2 class="text-2xl font-black italic uppercase text-gray-800 px-4 tracking-tighter">Products Feedback</h2>
        <div class="bg-white rounded-[40px] border border-[#f0e6e0] overflow-hidden shadow-sm h-[650px] overflow-y-auto">
            <?php foreach($review_groups as $group): ?>
                <a href="?tab=reviews&product_id=<?= $group['product_id'] ?>" 
                   class="flex items-center gap-4 p-5 border-b border-gray-50 hover:bg-[#fcfaf8] transition relative <?= $active_product == $group['product_id'] ? 'bg-[#fdfaf9] border-r-8 border-[#d4a373]' : '' ?>">
                    
                    <?php if($group['unreplied_count'] > 0): ?>
                        <span class="absolute top-4 left-4 w-5 h-5 bg-[#d4a373] text-white text-[9px] font-black rounded-full flex items-center justify-center z-10 border-2 border-white animate-pulse">
                            <?= $group['unreplied_count'] ?>
                        </span>
                    <?php endif; ?>

                    <img src="<?= $group['p_img'] ?>" class="w-14 h-14 rounded-2xl object-cover bg-gray-50 <?= $group['unreplied_count'] > 0 ? 'ring-2 ring-[#d4a373]' : '' ?>">
                    
                    <div class="flex-1 min-w-0">
                        <div class="text-[9px] font-black text-[#d4a373] uppercase tracking-widest truncate"><?= $group['manufacturer'] ?></div>
                        <div class="text-xs font-bold <?= $group['unreplied_count'] > 0 ? 'text-black' : 'text-gray-500' ?> truncate"><?= htmlspecialchars($group['name']) ?></div>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-[10px] font-black">⭐ <?= round($group['avg_rating'], 1) ?></span>
                            <span class="text-[9px] text-gray-300 font-bold uppercase"><?= $group['rev_count'] ?> відгуків</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="flex-1 space-y-6">
        <?php if($active_product): ?>
            <div class="mb-4 px-4 flex justify-between items-center">
                <h3 class="font-black uppercase text-xs tracking-widest text-gray-400">Листування по товару #<?= $active_product ?></h3>
            </div>

            <?php foreach($product_reviews as $r): ?>
                <div class="bg-white p-8 rounded-[45px] shadow-sm border <?= empty($r['admin_reply']) ? 'border-[#d4a373] border-2' : 'border-[#f0e6e0]' ?> transition hover:shadow-md relative">
                    
                    <div class="flex justify-between items-start mb-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gray-100 rounded-2xl flex items-center justify-center text-gray-400 font-black text-sm uppercase">
                                <?= mb_substr($r['first_name'], 0, 1) ?>
                            </div>
                            <div>
                                <h4 class="font-black text-gray-800 text-sm uppercase italic">
                                    <?= htmlspecialchars($r['first_name']) ?>
                                    <span class="text-[10px] text-gray-400 font-normal ml-2 italic lowercase"><?= htmlspecialchars($r['email']) ?></span>
                                </h4>
                                <div class="flex text-[#d4a373] text-xs mt-1">
                                    <?php for($i=1; $i<=5; $i++) echo $i <= $r['rating'] ? '★' : '☆'; ?>
                                </div>
                            </div>
                        </div>
                        <a href="?tab=reviews&product_id=<?= $active_product ?>&del_rev=<?= $r['review_id'] ?>" 
                           onclick="return confirm('Видалити цей відгук?')" 
                           class="text-red-200 hover:text-red-500 transition">✕</a>
                    </div>

                    <div class="bg-[#fcfaf9] p-6 rounded-[30px] border border-gray-50 mb-6">
                        <p class="text-sm text-gray-700 leading-relaxed italic">"<?= nl2br(htmlspecialchars($r['comment'])) ?>"</p>
                        <div class="text-[8px] text-gray-300 font-bold uppercase mt-4 text-right"><?= date('H:i | d.m.Y', strtotime($r['review_date'])) ?></div>
                    </div>

                    <div class="space-y-4">
                        <?php if($r['admin_reply']): ?>
                            <div class="p-6 bg-black text-white rounded-[30px] rounded-tr-none text-[11px] relative shadow-xl">
                                <span class="absolute -top-2 left-6 bg-[#d4a373] text-[8px] font-black px-3 py-1 rounded-full uppercase tracking-tighter shadow-sm">Офіційна відповідь</span>
                                <p class="italic leading-relaxed">"<?= htmlspecialchars($r['admin_reply']) ?>"</p>
                            </div>
                        <?php endif; ?>

                        <div class="flex justify-between items-center">
                            <button onclick="document.getElementById('form-<?= $r['review_id'] ?>').classList.toggle('hidden')" 
                                    class="text-[9px] font-black uppercase <?= empty($r['admin_reply']) ? 'text-red-500 animate-pulse' : 'text-[#d4a373]' ?> tracking-widest hover:underline">
                                <?= $r['admin_reply'] ? '✎ Редагувати відповідь' : '↵ Надати відповідь' ?>
                            </button>
                        </div>

                        <form id="form-<?= $r['review_id'] ?>" method="POST" class="hidden space-y-4 mt-4 bg-gray-50 p-6 rounded-[30px] border border-dashed border-gray-200">
                            <input type="hidden" name="review_id" value="<?= $r['review_id'] ?>">
                            <input type="hidden" name="p_id" value="<?= $active_product ?>">
                            <textarea name="admin_reply" rows="3" class="w-full p-5 text-xs bg-white border border-gray-100 rounded-[20px] outline-none focus:border-[#d4a373] transition" placeholder="Ваша ввічлива відповідь..."><?= htmlspecialchars($r['admin_reply'] ?? '') ?></textarea>
                            <div class="flex justify-end">
                                <button type="submit" name="save_reply" class="bg-black text-white text-[9px] font-black uppercase px-8 py-3 rounded-full hover:bg-[#d4a373] transition tracking-widest shadow-lg">Надіслати</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="h-full flex flex-col items-center justify-center p-20 text-center opacity-20 bg-white rounded-[50px] border border-dashed border-gray-200">
                <div class="text-8xl mb-6">✨</div>
                <h3 class="text-sm font-black uppercase tracking-[0.4em]">Оберіть товар</h3>
                <p class="text-[10px] font-bold mt-2 text-gray-400 uppercase tracking-widest">щоб модерувати відгуки</p>
            </div>
        <?php endif; ?>
    </div>
</div>