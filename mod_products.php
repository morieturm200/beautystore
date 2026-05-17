<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    die("Доступ заборонено"); 
}

if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $pdo->prepare("DELETE FROM product WHERE product_id = ?")->execute([$del_id]);
    header("Location: admin_prive.php?tab=products&msg=" . urlencode("Товар видалено")); 
    exit();
}

$edit       = null;
$edit_chars = [];
$current_parent_id = null;

if (isset($_GET['edit_id'])) {
    $target_id = (int)$_GET['edit_id'];

    $st = $pdo->prepare("
        SELECT p.*, c.parent_id,
               u.first_name AS admin_first, u.last_name AS admin_last
        FROM product p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN users      u ON p.last_modified_by = u.user_id
        WHERE p.product_id = ?
    ");
    $st->execute([$target_id]);
    $edit = $st->fetch(PDO::FETCH_ASSOC);
    if ($edit) {
        $current_parent_id = $edit['parent_id'];
    }

    $char_st = $pdo->prepare("
        SELECT * FROM characteristics
        WHERE product_id = ?
        ORDER BY characteristic_id ASC
    ");
    $char_st->execute([$target_id]);
    $edit_chars = $char_st->fetchAll(PDO::FETCH_ASSOC);
}

$all_cats        = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$main_categories = array_filter($all_cats, fn($c) => $c['parent_id'] === null);
$sub_categories  = array_filter($all_cats, fn($c) => $c['parent_id'] !== null);

if (isset($_POST['save_full_product'])) {
    $id       = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    $admin_id = $_SESSION['user_id'] ?? null;

    $old_price = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null;
    $price     = (float)$_POST['price'];

    if ($old_price !== null && $old_price <= $price) {
        $old_price = null;
    }

    $params = [
        trim($_POST['name']),
        (int)$_POST['subcategory_id'],
        $price,
        trim($_POST['manufacturer']),
        trim($_POST['description']),
        max(0, (int)$_POST['stock']),
        trim($_POST['badge'])     ?: null,
        $old_price,
        isset($_POST['is_giveaway_participant']) ? 1 : 0,
        trim($_POST['promo_type']) ?: null,
        $admin_id,
    ];

    $pdo->beginTransaction();
    try {
        if ($id) {
            $sql = "UPDATE product SET
                        name=?, category_id=?, price=?, manufacturer=?,
                        description=?, stock=?, badge=?, old_price=?,
                        is_giveaway_participant=?, promo_type=?, last_modified_by=?
                    WHERE product_id=?";
            $params[] = $id;
            $pdo->prepare($sql)->execute($params);
            $msg = "Товар оновлено!";
        } else {
            $sql = "INSERT INTO product
                        (name, category_id, price, manufacturer,
                         description, stock, badge, old_price,
                         is_giveaway_participant, promo_type, last_modified_by)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?)";
            $stmt_ins = $pdo->prepare($sql);
            $stmt_ins->execute($params);
            $id  = $pdo->lastInsertId();
            $msg = "Товар створено!";
        }

        $pdo->prepare("DELETE FROM characteristics WHERE product_id = ?")->execute([$id]);
        if (!empty($_POST['char_name'])) {
            $char_sql = "INSERT INTO characteristics
                            (product_id, characteristic_name, characteristic_value, group_name)
                         VALUES (?,?,?,?)";
            $char_st = $pdo->prepare($char_sql);
            foreach ($_POST['char_name'] as $key => $name) {
                $name = trim($name);
                if ($name === '') continue;
                $char_st->execute([
                    $id,
                    $name,
                    trim($_POST['char_value'][$key] ?? ''),
                    trim($_POST['char_group'][$key] ?? '') ?: 'Основне',
                ]);
            }
        }

        $img_url = trim($_POST['image_url'] ?? '');
        if ($img_url !== '') {
            $pdo->prepare("DELETE FROM Images WHERE product_id = ? AND is_primary = 1")->execute([$id]);
            $pdo->prepare("INSERT INTO Images (product_id, image_url, is_primary) VALUES (?,?,1)")
                ->execute([$id, $img_url]);
        }

        $pdo->commit();
        header("Location: admin_prive.php?tab=products&msg=" . urlencode($msg)); 
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Помилка збереження товару: " . $e->getMessage());
    }
}
?>

<style>
    .input-field { background:#fff!important; border:2px solid #e8e1db!important; border-radius:12px; padding:10px 15px; width:100%; color:#2d2d2d; font-weight:600; transition:0.3s; margin-bottom:5px; font-size:13px; }
    .input-field:focus { border-color:#d4a373!important; outline:none; box-shadow:0 0 0 4px rgba(212,163,115,.1); }
    label { font-size:9px; font-weight:800; text-transform:uppercase; color:#8a817c; margin-bottom:4px; display:block; letter-spacing:1px; }
    .card-admin { background:white; padding:40px; border-radius:40px; border:1px solid #f0e6e0; box-shadow:0 10px 30px -15px rgba(0,0,0,.05); }
    .btn-add-char { background:#f5ebe0; color:#d4a373; padding:8px 15px; border-radius:10px; font-size:10px; font-weight:900; text-transform:uppercase; cursor:pointer; border:none; transition:0.3s; }
    .btn-add-char:hover { background:#d4a373; color:white; }
</style>

<div class="space-y-10">

    <div class="card-admin">
        <div class="flex justify-between items-center mb-10">
            <h3 class="text-2xl font-black italic uppercase tracking-tighter text-gray-800">
                <?= $edit ? '🖋 Редагувати товар' : '➕ Додати новий товар' ?>
            </h3>
            <?php if ($edit): ?>
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
                    <label>Виробник / Бренд</label>
                    <input type="text" name="manufacturer" class="input-field" value="<?= htmlspecialchars($edit['manufacturer'] ?? '') ?>" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 bg-[#fcfaf8] p-6 rounded-3xl border border-[#f0e6e0]">
                <div>
                    <label>Актуальна ціна (₴)</label>
                    <input type="number" step="0.01" min="0.01" name="price" class="input-field" value="<?= $edit['price'] ?? '' ?>" required>
                </div>
                <div>
                    <label>Стара ціна (₴) — має бути вища</label>
                    <input type="number" step="0.01" min="0" name="old_price" class="input-field" value="<?= $edit['old_price'] ?? '' ?>">
                </div>
                <div class="flex items-center pt-5 gap-3">
                    <div class="w-5 h-5 rounded border-2 border-[#d4a373] flex items-center justify-center bg-white">
                        <?php if (!empty($edit['old_price'])): ?>
                            <div class="w-3 h-3 rounded-sm bg-[#d4a373]"></div>
                        <?php endif; ?>
                    </div>
                    <label class="mb-0 text-[10px]">Знижка активна (автоматично від старої ціни)</label>
                </div>
                <div>
                    <label>Тип промо (наприклад: GIFT)</label>
                    <input type="text" name="promo_type" class="input-field" value="<?= htmlspecialchars($edit['promo_type'] ?? '') ?>">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <label>Основна категорія</label>
                    <select id="main_cat_select" class="input-field" onchange="filterSubs(false)">
                        <option value="">Оберіть...</option>
                        <?php foreach ($main_categories as $m): ?>
                            <option value="<?= $m['category_id'] ?>" <?= ($current_parent_id == $m['category_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Підкатегорія</label>
                    <select name="subcategory_id" id="sub_cat_select" class="input-field" required>
                        <option value="">Спочатку оберіть основну</option>
                    </select>
                </div>
                <div>
                    <label>Бейдж (NEW, BEST…)</label>
                    <input type="text" name="badge" class="input-field" value="<?= htmlspecialchars($edit['badge'] ?? '') ?>">
                </div>
                <div>
                    <label>Залишок на складі</label>
                    <input type="number" min="0" name="stock" class="input-field" value="<?= $edit['stock'] ?? 0 ?>" required>
                </div>
            </div>

            <div class="p-8 bg-gray-50 rounded-[30px] border border-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h4 class="text-xs font-black uppercase tracking-widest text-gray-400">Характеристики товару</h4>
                    <button type="button" onclick="addCharRow()" class="btn-add-char">+ Додати поле</button>
                </div>
                <div id="characteristics-container" class="space-y-3">
                    <?php if (!empty($edit_chars)): ?>
                        <?php foreach ($edit_chars as $char): ?>
                            <div class="grid grid-cols-3 gap-3 char-row items-center">
                                <input type="text" name="char_group[]" placeholder="Група" class="input-field" value="<?= htmlspecialchars($char['group_name']) ?>">
                                <input type="text" name="char_name[]"  placeholder="Характеристика"         class="input-field" value="<?= htmlspecialchars($char['characteristic_name']) ?>">
                                <div class="flex gap-2">
                                    <input type="text" name="char_value[]" placeholder="Значення" class="input-field flex-1" value="<?= htmlspecialchars($char['characteristic_value']) ?>">
                                    <button type="button" onclick="this.closest('.char-row').remove()" class="text-red-300 hover:text-red-500 font-black text-lg leading-none pb-1">×</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="grid grid-cols-3 gap-3 char-row items-center">
                            <input type="text" name="char_group[]"  placeholder="Група"        class="input-field">
                            <input type="text" name="char_name[]"   placeholder="Назва"         class="input-field">
                            <div class="flex gap-2">
                                <input type="text" name="char_value[]" placeholder="Значення" class="input-field flex-1">
                                <button type="button" onclick="this.closest('.char-row').remove()" class="text-red-300 hover:text-red-500 font-black text-lg leading-none pb-1">×</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2">
                    <label>Опис товару</label>
                    <textarea name="description" class="input-field" rows="6"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
                    <div class="flex items-center mt-4 gap-3">
                        <input type="checkbox" name="is_giveaway_participant" id="is_giveaway"
                               class="w-5 h-5 accent-[#d4a373]"
                               <?= ($edit['is_giveaway_participant'] ?? 0) ? 'checked' : '' ?>>
                        <label for="is_giveaway" class="mb-0 text-[10px]">Товар бере участь у розіграші (Giveaway)</label>
                    </div>
                </div>
                <div>
                    <label>Фото товару (URL)</label>
                    <?php
                        $current_img = '';
                        if ($edit) {
                            $img_st = $pdo->prepare("SELECT image_url FROM Images WHERE product_id = ? AND is_primary = 1 LIMIT 1");
                            $img_st->execute([$edit['product_id']]);
                            $current_img = $img_st->fetchColumn() ?: '';
                        }
                    ?>
                    <input type="text" name="image_url" class="input-field mb-3"
                           placeholder="https://..."
                           value="<?= htmlspecialchars($current_img) ?>"
                           oninput="document.getElementById('pv').src=this.value">
                    <div class="border-2 border-dashed border-gray-200 rounded-3xl p-4 bg-gray-50 text-center">
                        <img id="pv"
                             src="<?= $current_img ?: 'https://placehold.co/400x400' ?>"
                             class="w-full h-32 object-cover rounded-2xl"
                             onerror="this.src='https://placehold.co/400x400?text=No+Image'">
                    </div>
                </div>
            </div>

            <?php if ($edit && !empty($edit['last_modified_at'])): ?>
            <div class="text-right px-4">
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">
                    Останнє редагування:
                    <span class="text-[#d4a373]">
                        <?= htmlspecialchars(trim(($edit['admin_first'] ?? '') . ' ' . ($edit['admin_last'] ?? '')) ?: 'Адмін') ?>
                    </span>
                    о <?= date('d.m.Y H:i', strtotime($edit['last_modified_at'])) ?>
                </span>
            </div>
            <?php endif; ?>

            <button type="submit" name="save_full_product"
                    class="w-full py-6 bg-black text-white rounded-3xl font-black uppercase text-sm tracking-[0.2em] shadow-2xl hover:bg-[#d4a373] transition duration-500">
                <?= $edit ? 'Зберегти зміни' : 'Опублікувати товар' ?>
            </button>
        </form>
    </div>

    <div class="card-admin !p-0 overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="text-[9px] font-black uppercase text-gray-400 bg-gray-50 border-b">
                    <th class="p-6">Товар</th>
                    <th class="p-6">Категорія</th>
                    <th class="p-6">Ціна</th>
                    <th class="p-6 text-right">Дія</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $list = $pdo->query("
                    SELECT p.*,
                           u.first_name AS admin_name,
                           c.name       AS sub_name,
                           pc.name      AS parent_name,
                           (SELECT image_url FROM Images
                            WHERE product_id = p.product_id AND is_primary = 1
                            LIMIT 1) AS img
                    FROM product p
                    LEFT JOIN categories c  ON p.category_id  = c.category_id
                    LEFT JOIN categories pc ON c.parent_id    = pc.category_id
                    LEFT JOIN users      u  ON p.last_modified_by = u.user_id
                    ORDER BY p.product_id DESC
                ")->fetchAll(PDO::FETCH_ASSOC);

                foreach ($list as $p): ?>
                <tr class="border-b border-gray-50 hover:bg-gray-50/50">
                    <td class="p-6">
                        <div class="flex items-center gap-4">
                            <img src="<?= htmlspecialchars($p['img'] ?? '') ?>"
                                 class="w-10 h-10 rounded-lg object-cover"
                                 onerror="this.src='https://placehold.co/100'">
                            <div>
                                <div class="font-bold text-xs"><?= htmlspecialchars($p['name']) ?></div>
                                <?php if (!empty($p['admin_name'])): ?>
                                    <div class="text-[9px] text-[#d4a373] mt-1 font-black tracking-widest uppercase">
                                        Змінено: <?= htmlspecialchars($p['admin_name']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($p['badge'])): ?>
                                    <span class="text-[8px] bg-[#f5ebe0] text-[#d4a373] px-2 py-0.5 rounded-full font-black uppercase">
                                        <?= htmlspecialchars($p['badge']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="p-6 text-[10px] font-bold uppercase text-gray-400">
                        <?= $p['parent_name'] ? htmlspecialchars($p['parent_name']) . ' › ' : '' ?>
                        <?= htmlspecialchars($p['sub_name'] ?: 'Без категорії') ?>
                    </td>
                    <td class="p-6 font-black text-[#d4a373]">
                        <?= number_format($p['price'], 0) ?> ₴
                        <?php if (!empty($p['old_price'])): ?>
                            <div class="text-[9px] text-gray-400 line-through font-normal">
                                <?= number_format($p['old_price'], 0) ?> ₴
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="p-6 text-right whitespace-nowrap">
                        <a href="?tab=products&edit_id=<?= $p['product_id'] ?>" class="text-blue-400 mr-4 text-lg">🖋</a>
                        <a href="?tab=products&delete_id=<?= $p['product_id'] ?>"
                           onclick="return confirm('Видалити цей товар?')"
                           class="text-red-300 text-lg">🗑</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const subCategories = <?= json_encode(array_values($sub_categories)) ?>;
const currentSubId  = <?= json_encode($edit['category_id'] ?? null) ?>;

function filterSubs(isFirstLoad = false) {
    const parentId = document.getElementById('main_cat_select').value;
    const subSelect = document.getElementById('sub_cat_select');
    subSelect.innerHTML = '<option value="">Оберіть підкатегорію...</option>';
    
    subCategories
        .filter(s => s.parent_id == parentId)
        .forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.category_id;
            opt.textContent = s.name;
            if (isFirstLoad && s.category_id == currentSubId) opt.selected = true;
            subSelect.appendChild(opt);
        });
}

function addCharRow() {
    const container = document.getElementById('characteristics-container');
    const row = document.createElement('div');
    row.className = 'grid grid-cols-3 gap-3 char-row items-center';
    row.innerHTML = `
        <input type="text" name="char_group[]"  placeholder="Група"     class="input-field">
        <input type="text" name="char_name[]"   placeholder="Назва"     class="input-field">
        <div class="flex gap-2">
            <input type="text" name="char_value[]" placeholder="Значення" class="input-field flex-1">
            <button type="button" onclick="this.closest('.char-row').remove()"
                    class="text-red-300 hover:text-red-500 font-black text-lg leading-none pb-1">×</button>
        </div>`;
    container.appendChild(row);
}

window.onload = function() {
    filterSubs(true);
};
</script>
