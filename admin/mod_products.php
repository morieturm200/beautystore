<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. ПІДКЛЮЧЕННЯ
$host = "localhost"; $db = "beautystore"; $user = "beautyuser"; $pass = "1234";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) { die("Помилка підключення: " . $e->getMessage()); }

// 2. ЛОГІКА ВИДАЛЕННЯ
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $pdo->prepare("DELETE FROM product WHERE product_id = ?")->execute([$del_id]);
    header("Location: admin_prive.php?tab=products&msg=Товар видалено"); exit();
}

// 3. ЛОГІКА РЕДАГУВАННЯ
$edit = null;
$edit_chars = [];
if (isset($_GET['edit_id'])) {
    $target_id = (int)$_GET['edit_id'];
    $st = $pdo->prepare("SELECT * FROM product WHERE product_id = ?"); 
    $st->execute([$target_id]);
    $edit = $st->fetch(PDO::FETCH_ASSOC);

    // Завантажуємо характеристики для цього товару
    $char_st = $pdo->prepare("SELECT * FROM characteristics WHERE product_id = ? ORDER BY sort_order");
    $char_st->execute([$target_id]);
    $edit_chars = $char_st->fetchAll(PDO::FETCH_ASSOC);
}

// 4. ЗБЕРЕЖЕННЯ / ОНОВЛЕННЯ
if (isset($_POST['save_full_product'])) {
    $id = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    
    // Підготовка основних даних товару
    $params = [
        $_POST['name'], 
        $_POST['price'], 
        !empty($_POST['sale_price']) ? $_POST['sale_price'] : null,
        isset($_POST['is_sale']) ? 1 : 0,
        $_POST['category'], 
        $_POST['subcategory'],
        $_POST['manufacturer'], 
        $_POST['description'], 
        $_POST['stock'],
        $_POST['color'], 
        (!empty($_POST['weight']) ? $_POST['weight'] : null), 
        $_POST['warranty'],
        $_POST['badge'],
        isset($_POST['is_giveaway_participant']) ? 1 : 0,
        $_POST['promo_type']
    ];

    if ($id) {
        $sql = "UPDATE product SET name=?, price=?, sale_price=?, is_sale=?, category=?, subcategory=?, manufacturer=?, description=?, stock=?, color=?, weight=?, warranty=?, badge=?, is_giveaway_participant=?, promo_type=? WHERE product_id=?";
        $params[] = $id;
        $pdo->prepare($sql)->execute($params);
        $msg = "Товар оновлено!";
    } else {
        $sql = "INSERT INTO product (name, price, sale_price, is_sale, category, subcategory, manufacturer, description, stock, color, weight, warranty, badge, is_giveaway_participant, promo_type) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $pdo->prepare($sql)->execute($params);
        $id = $pdo->lastInsertId();
        $msg = "Товар створено!";
    }

    // --- ЛОГІКА ХАРАКТЕРИСТИК ---
    $pdo->prepare("DELETE FROM characteristics WHERE product_id = ?")->execute([$id]);
    
    if (!empty($_POST['char_name'])) {
        foreach ($_POST['char_name'] as $key => $name) {
            if (!empty($name)) {
                $char_sql = "INSERT INTO characteristics (product_id, characteristic_name, characteristic_value, group_name) VALUES (?, ?, ?, ?)";
                $pdo->prepare($char_sql)->execute([ $id, $name, $_POST['char_value'][$key], $_POST['char_group'][$key] ]);
            }
        }
    }

    // ОБРОБКА ПОСИЛАННЯ НА ФОТО
    if (!empty($_POST['image_url'])) {
        $img_url = trim($_POST['image_url']);
        $pdo->prepare("DELETE FROM Images WHERE product_id = ? AND is_primary = 1")->execute([$id]);
        $img_sql = "INSERT INTO Images (product_id, image_url, is_primary) VALUES (?, ?, 1)";
        $pdo->prepare($img_sql)->execute([$id, $img_url]);
    }
    header("Location: admin_prive.php?tab=products&msg=" . urlencode($msg)); exit();
}
?>

<style>
    .input-field { background: #fff !important; border: 2px solid #e8e1db !important; border-radius: 12px; padding: 10px 15px; width: 100%; color: #2d2d2d; font-weight: 600; transition: 0.3s; margin-bottom: 5px; font-size: 13px; }
    .input-field:focus { border-color: #d4a373 !important; outline: none; box-shadow: 0 0 0 4px rgba(212, 163, 115, 0.1); }
    label { font-size: 9px; font-weight: 800; text-transform: uppercase; color: #8a817c; margin-bottom: 4px; display: block; letter-spacing: 1px; }
    .card-admin { background: white; padding: 40px; border-radius: 40px; border: 1px solid #f0e6e0; box-shadow: 0 10px 30px -15px rgba(0,0,0,0.05); }
    .btn-add-char { background: #f5ebe0; color: #d4a373; padding: 8px 15px; border-radius: 10px; font-size: 10px; font-weight: 900; text-transform: uppercase; cursor: pointer; border: none; transition: 0.3s; }
    .btn-add-char:hover { background: #d4a373; color: white; }
</style>

<div class="space-y-10">
    <div class="card-admin">
        <div class="flex justify-between items-center mb-10">
            <h3 class="text-2xl font-black italic uppercase tracking-tighter text-gray-800">
                <?= $edit ? '🖋 Редагувати' : '➕ Новий товар' ?>
            </h3>
            <?php if($edit): ?>
                <a href="admin_prive.php?tab=products" class="text-[10px] font-black bg-gray-100 px-4 py-2 rounded-full uppercase text-gray-400">Скасувати</a>
            <?php endif; ?>
        </div>

        <form method="POST" class="space-y-8">
            <input type="hidden" name="product_id" value="<?= $edit['product_id'] ?? '' ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="md:col-span-3">
                    <label>Назва продукту</label>
                    <input type="text" name="name" class="input-field" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" required>
                </div>
                <div>
                    <label>Бренд</label>
                    <input type="text" name="manufacturer" class="input-field" value="<?= htmlspecialchars($edit['manufacturer'] ?? '') ?>" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 bg-[#fcfaf8] p-6 rounded-3xl border border-[#f0e6e0]">
                <div>
                    <label>Базова ціна (₴)</label>
                    <input type="number" step="0.01" name="price" class="input-field" value="<?= $edit['price'] ?? '' ?>" required>
                </div>
                <div>
                    <label>Акційна ціна (₴)</label>
                    <input type="number" step="0.01" name="sale_price" class="input-field" value="<?= $edit['sale_price'] ?? '' ?>">
                </div>
                <div class="flex items-center pt-5">
                    <input type="checkbox" name="is_sale" id="is_sale" class="w-5 h-5 accent-[#d4a373]" <?= ($edit['is_sale'] ?? 0) ? 'checked' : '' ?>>
                    <label for="is_sale" class="ml-3 mb-0">Активувати знижку</label>
                </div>
                <div>
                    <label>Тип промо (н-ад: 1+1=3)</label>
                    <input type="text" name="promo_type" class="input-field" value="<?= htmlspecialchars($edit['promo_type'] ?? '') ?>">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <label>Категорія</label>
                    <input type="text" name="category" class="input-field" value="<?= htmlspecialchars($edit['category'] ?? '') ?>" required>
                </div>
                <div>
                    <label>Підкатегорія</label>
                    <input type="text" name="subcategory" class="input-field" value="<?= htmlspecialchars($edit['subcategory'] ?? '') ?>">
                </div>
                <div>
                    <label>Бейдж (NEW, TOP)</label>
                    <input type="text" name="badge" class="input-field" value="<?= htmlspecialchars($edit['badge'] ?? '') ?>">
                </div>
                <div>
                    <label>Кількість</label>
                    <input type="number" name="stock" class="input-field" value="<?= $edit['stock'] ?? '' ?>" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <label>Колір</label>
                    <input type="text" name="color" class="input-field" value="<?= htmlspecialchars($edit['color'] ?? '') ?>">
                </div>
                <div>
                    <label>Вага (кг)</label>
                    <input type="number" step="0.01" name="weight" class="input-field" value="<?= $edit['weight'] ?? '' ?>">
                </div>
                <div>
                    <label>Гарантія</label>
                    <input type="text" name="warranty" class="input-field" value="<?= htmlspecialchars($edit['warranty'] ?? '') ?>">
                </div>
                <div class="flex items-center pt-5">
                    <input type="checkbox" name="is_giveaway_participant" id="is_giveaway" class="w-5 h-5 accent-[#d4a373]" <?= ($edit['is_giveaway_participant'] ?? 0) ? 'checked' : '' ?>>
                    <label for="is_giveaway" class="ml-3 mb-0">Учасник розіграшу</label>
                </div>
            </div>

            <div class="p-8 bg-gray-50 rounded-[30px] border border-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h4 class="text-xs font-black uppercase tracking-widest text-gray-400">Технічні характеристики</h4>
                    <button type="button" onclick="addCharRow()" class="btn-add-char">+ Додати поле</button>
                </div>
                <div id="characteristics-container" class="space-y-3">
                    <?php if(!empty($edit_chars)): foreach($edit_chars as $char): ?>
                        <div class="grid grid-cols-3 gap-3 char-row">
                            <input type="text" name="char_group[]" placeholder="Група" class="input-field" value="<?= htmlspecialchars($char['group_name']) ?>">
                            <input type="text" name="char_name[]" placeholder="Назва" class="input-field" value="<?= htmlspecialchars($char['characteristic_name']) ?>">
                            <input type="text" name="char_value[]" placeholder="Значення" class="input-field" value="<?= htmlspecialchars($char['characteristic_value']) ?>">
                        </div>
                    <?php endforeach; else: ?>
                        <div class="grid grid-cols-3 gap-3 char-row">
                            <input type="text" name="char_group[]" placeholder="Група" class="input-field">
                            <input type="text" name="char_name[]" placeholder="Назва" class="input-field">
                            <input type="text" name="char_value[]" placeholder="Значення" class="input-field">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2">
                    <label>Опис</label>
                    <textarea name="description" class="input-field" rows="6"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
                </div>
                <div>
                    <label>Головне фото (URL-посилання)</label>
                    <?php 
                        $current_img = '';
                        if($edit) {
                            $img_st = $pdo->prepare("SELECT image_url FROM Images WHERE product_id = ? AND is_primary = 1");
                            $img_st->execute([$edit['product_id']]);
                            $current_img = $img_st->fetchColumn();
                        }
                    ?>
                    <input type="text" name="image_url" class="input-field mb-3" placeholder="https://..." value="<?= htmlspecialchars($current_img) ?>" oninput="document.getElementById('pv').src=this.value">
                    <div class="border-2 border-dashed border-gray-200 rounded-3xl p-4 bg-gray-50 text-center">
                        <img id="pv" src="<?= $current_img ?>" class="w-full h-32 object-cover rounded-2xl" onerror="this.src='https://placehold.co/400x400?text=Beauty+Store'">
                    </div>
                </div>
            </div>

            <button type="submit" name="save_full_product" class="w-full py-6 bg-black text-white rounded-3xl font-black uppercase text-sm tracking-[0.2em] shadow-2xl hover:bg-[#d4a373] transition duration-500">
                <?= $edit ? 'Оновити дані продукту' : 'Опублікувати товар' ?>
            </button>
        </form>
    </div>

    <div class="card-admin !p-0 overflow-hidden">
        <table class="w-full text-left">
            <thead><tr class="text-[9px] font-black uppercase text-gray-400 bg-gray-50 border-b"><th class="p-6">Товар</th><th class="p-6">Категорія</th><th class="p-6">Ціна</th><th class="p-6 text-right">Дія</th></tr></thead>
            <tbody>
                <?php $list = $pdo->query("SELECT p.*, (SELECT image_url FROM Images WHERE product_id=p.product_id AND is_primary=1 LIMIT 1) as img FROM product p ORDER BY p.product_id DESC")->fetchAll();
                foreach($list as $p): ?>
                <tr class="border-b border-gray-50 hover:bg-gray-50/50"><td class="p-6 flex items-center gap-4"><img src="<?= $p['img'] ?>" class="w-10 h-10 rounded-lg object-cover" onerror="this.src='https://placehold.co/100'"><div class="font-bold text-xs"><?= htmlspecialchars($p['name']) ?></div></td><td class="p-6 text-[10px] font-bold uppercase text-gray-400"><?= $p['category'] ?></td><td class="p-6 font-black text-[#d4a373]"><?= number_format($p['price'], 0) ?> ₴</td><td class="p-6 text-right"><a href="?tab=products&edit_id=<?= $p['product_id'] ?>" class="text-blue-400 mr-4">🖋</a><a href="?tab=products&delete_id=<?= $p['product_id'] ?>" onclick="return confirm('Видалити?')" class="text-red-300">🗑</a></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function addCharRow() {
    const container = document.getElementById('characteristics-container');
    const row = document.createElement('div');
    row.className = 'grid grid-cols-3 gap-3 char-row';
    row.innerHTML = `<input type="text" name="char_group[]" placeholder="Група" class="input-field"><input type="text" name="char_name[]" placeholder="Назва" class="input-field"><input type="text" name="char_value[]" placeholder="Значення" class="input-field">`;
    container.appendChild(row);
}
</script>