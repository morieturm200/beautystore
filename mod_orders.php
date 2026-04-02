<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost"; $db = "beautystore"; $user = "beautyuser"; $pass = "1234";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) { die("Помилка підключення"); }

$date_from = $_GET['date_from'] ?? date('Y-m-01'); 
$date_to = $_GET['date_to'] ?? date('Y-m-d');

if (isset($_GET['update_status']) && isset($_GET['order_id'])) {
    $st = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $st->execute([$_GET['update_status'], (int)$_GET['order_id']]);
    header("Location: admin_prive.php?tab=orders&date_from=$date_from&date_to=$date_to");
    exit();
}

$stmt = $pdo->prepare("
    SELECT o.*, c.first_name, c.last_name, c.email, c.phone_number, c.address,
    (SELECT JSON_ARRAYAGG(
        JSON_OBJECT(
            'name', p.name,
            'qty', od.quantity,
            'price', od.unit_price,
            'brand', p.manufacturer
        )
    ) FROM Order_Details od JOIN product p ON od.product_id = p.product_id WHERE od.order_id = o.order_id) as items_json
    FROM orders o 
    LEFT JOIN customer c ON o.customer_id = c.customer_id 
    WHERE DATE(o.order_date) BETWEEN ? AND ?
    ORDER BY o.order_date DESC
");
$stmt->execute([$date_from, $date_to]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-8">
    <div class="bg-white rounded-[40px] shadow-sm border border-[#f0e6e0] overflow-hidden">
        <div class="p-8 border-b border-gray-50 flex justify-between items-center bg-[#fcfaf8]">
            <h2 class="text-xl font-black italic uppercase text-gray-800">Журнал замовлень</h2>
            <form method="GET" class="flex gap-2">
                <input type="hidden" name="tab" value="orders">
                <input type="date" name="date_from" value="<?= $date_from ?>" class="text-[10px] p-2 border rounded-xl outline-none">
                <input type="date" name="date_to" value="<?= $date_to ?>" class="text-[10px] p-2 border rounded-xl outline-none">
                <button type="submit" class="bg-black text-white px-4 rounded-xl text-[10px] font-bold uppercase tracking-widest">ОК</button>
            </form>
        </div>
        
        <table class="w-full text-left">
            <thead class="bg-gray-50/50 text-[10px] font-black uppercase text-gray-400 tracking-widest border-b">
                <tr>
                    <th class="p-6">ID</th>
                    <th class="p-6">Клієнт</th>
                    <th class="p-6">Сума</th>
                    <th class="p-6">Статус</th>
                    <th class="p-6 text-right">Управління</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach($orders as $o): ?>
                <tr class="hover:bg-[#fdfbf9] transition-colors">
                    <td class="p-6 font-bold text-[#d4a373]">#<?= $o['order_id'] ?></td>
                    <td class="p-6">
                        <div class="font-bold text-sm text-gray-800"><?= htmlspecialchars($o['first_name'] ?: 'Гість') ?></div>
                        <div class="text-[9px] text-gray-400 uppercase font-black tracking-tighter"><?= $o['order_date'] ?></div>
                    </td>
                    <td class="p-6 font-black text-gray-800"><?= number_format($o['total_price'], 2, '.', ' ') ?> ₴</td>
                    <td class="p-6">
                        <?php 
                            $status_styles = [
                                'Нове' => 'bg-blue-50 text-blue-500',
                                'В обробці' => 'bg-yellow-50 text-yellow-600',
                                'Відправлено' => 'bg-purple-50 text-purple-500',
                                'Доставлено' => 'bg-green-50 text-green-600',
                                'Скасовано' => 'bg-red-50 text-red-400',
                                'Прийнято' => 'bg-gray-100 text-gray-600'
                            ];
                            $style = $status_styles[$o['status']] ?? 'bg-gray-50 text-gray-500';
                        ?>
                        <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-tighter <?= $style ?>">
                            <?= $o['status'] ?>
                        </span>
                    </td>
                    <td class="p-6 text-right">
                        <div class="flex justify-end items-center gap-3">
                            <div class="hidden lg:flex bg-gray-50 p-1 rounded-xl gap-1 border border-gray-100">
                                <a href="?tab=orders&order_id=<?= $o['order_id'] ?>&update_status=В обробці&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" class="p-1 hover:bg-white rounded-lg transition" title="В обробку">⚙️</a>
                                <a href="?tab=orders&order_id=<?= $o['order_id'] ?>&update_status=Відправлено&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" class="p-1 hover:bg-white rounded-lg transition" title="Відправити">🚀</a>
                                <a href="?tab=orders&order_id=<?= $o['order_id'] ?>&update_status=Доставлено&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" class="p-1 hover:bg-white rounded-lg text-green-400 transition" title="Виконано">✅</a>
                                <button onclick="confirmCancel(<?= $o['order_id'] ?>)" class="p-1 hover:bg-red-50 rounded-lg text-red-400 transition" title="Скасувати">❌</button>
                            </div>
                            <div class="flex gap-1">
                                <?php $encoded = base64_encode(json_encode($o)); ?>
                                <button onclick="openOrderModal('<?= $encoded ?>')" class="w-10 h-10 flex items-center justify-center bg-[#f5ebe0] text-[#d4a373] rounded-xl hover:shadow-md transition text-lg">👁</button>
                                <a href="../invoice.php?order_id=<?= $o['order_id'] ?>" target="_blank" class="w-10 h-10 flex items-center justify-center bg-gray-100 rounded-xl text-lg hover:shadow-md transition">📄</a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="orderModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-[100] flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-[40px] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
        <div class="p-8 border-b flex justify-between items-center bg-[#fcfaf8]">
            <div>
                <h3 class="text-2xl font-black italic uppercase text-gray-800">Замовлення <span id="m_id" class="text-[#d4a373]"></span></h3>
                <p id="m_date" class="text-[10px] text-gray-400 font-bold uppercase tracking-widest"></p>
            </div>
            <button onclick="closeOrderModal()" class="text-2xl text-gray-300">✕</button>
        </div>
        <div class="p-8 space-y-6 max-h-[70vh] overflow-y-auto">
            <div class="grid grid-cols-2 gap-8">
                <div>
                    <label class="text-[9px] font-black text-[#d4a373] uppercase tracking-widest">Клієнт</label>
                    <p id="m_name" class="font-bold text-gray-800"></p>
                    <p id="m_email" class="text-sm text-gray-500"></p>
                    <p id="m_phone" class="text-sm text-gray-500"></p>
                </div>
                <div>
                    <label class="text-[9px] font-black text-[#d4a373] uppercase tracking-widest">Доставка</label>
                    <p id="m_address" class="text-sm text-gray-700 leading-relaxed italic"></p>
                </div>
            </div>
            <div id="m_items" class="space-y-2"></div>
            <div class="pt-6 border-t flex justify-between items-center">
                <div id="m_cancel_area"></div>
                <div class="text-right">
                    <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Сума замовлення</label>
                    <div id="m_total" class="text-3xl font-black text-gray-800"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="confirmModal" class="fixed inset-0 bg-black/60 backdrop-blur-md hidden z-[110] flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-sm rounded-[40px] shadow-2xl p-10 text-center space-y-6 animate-in zoom-in duration-200">
        <div class="w-20 h-20 bg-red-50 text-red-400 rounded-full flex items-center justify-center text-3xl mx-auto">⚠️</div>
        <div>
            <h3 class="text-xl font-black uppercase italic tracking-tighter text-gray-800">Скасувати замовлення?</h3>
            <p class="text-xs text-gray-400 font-bold mt-2 uppercase tracking-widest">Замовлення <span id="confirm_order_id" class="text-red-400"></span> буде переведено в статус "Скасовано"</p>
        </div>
        <div class="flex flex-col gap-2">
            <a id="confirm_link" href="#" class="bg-black text-white py-4 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] hover:bg-red-500 transition shadow-lg">Так, скасувати</a>
            <button onclick="closeConfirmModal()" class="py-4 text-[10px] font-black uppercase tracking-[0.2em] text-gray-300 hover:text-gray-800 transition">Залишити як є</button>
        </div>
    </div>
</div>

<script>
// Логіка для гарного підтвердження
function confirmCancel(orderId) {
    const modal = document.getElementById('confirmModal');
    const link = document.getElementById('confirm_link');
    const displayId = document.getElementById('confirm_order_id');
    
    displayId.innerText = '#' + orderId;
    link.href = `?tab=orders&order_id=${orderId}&update_status=Скасовано&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>`;
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.add('hidden');
    if (document.getElementById('orderModal').classList.contains('hidden')) {
        document.body.style.overflow = 'auto';
    }
}

// Логіка перегляду замовлення
function openOrderModal(base64Data) {
    const order = JSON.parse(atob(base64Data));
    document.getElementById('m_id').innerText = '#' + order.order_id;
    document.getElementById('m_date').innerText = order.order_date;
    document.getElementById('m_name').innerText = (order.first_name || 'Гість') + ' ' + (order.last_name || '');
    document.getElementById('m_email').innerText = order.email || '—';
    document.getElementById('m_phone').innerText = order.phone_number || '—';
    document.getElementById('m_address').innerText = order.address || 'Адреса відсутня';
    document.getElementById('m_total').innerText = parseFloat(order.total_price).toLocaleString() + ' ₴';

    const cancelArea = document.getElementById('m_cancel_area');
    if (order.status !== 'Скасовано') {
        // У модалці теж замінюємо стандартний confirm на нашу функцію
        cancelArea.innerHTML = `<button onclick="confirmCancel(${order.order_id})" class="bg-red-50 text-red-500 px-6 py-3 rounded-2xl text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition shadow-sm">Скасувати замовлення</button>`;
    } else { cancelArea.innerHTML = ''; }

    const container = document.getElementById('m_items');
    container.innerHTML = '<label class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-2">Товари</label>';
    if (order.items_json) {
        JSON.parse(order.items_json).forEach(item => {
            container.innerHTML += `
                <div class="flex justify-between items-center p-4 bg-gray-50 rounded-2xl border border-gray-100">
                    <div>
                        <div class="text-[9px] font-black text-[#d4a373] uppercase">${item.brand}</div>
                        <div class="text-sm font-bold text-gray-800">${item.name}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-bold text-gray-400">${item.qty} шт.</div>
                        <div class="font-black text-gray-800">${(item.qty * item.price).toLocaleString()} ₴</div>
                    </div>
                </div>`;
        });
    }
    document.getElementById('orderModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeOrderModal() {
    document.getElementById('orderModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}
</script>