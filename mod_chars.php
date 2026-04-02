<?php
// Якщо файл підключається через include в головному, $pdo вже існує.
if (!isset($pdo)) {
    $host = "localhost"; $db = "beautystore"; $user = "beautyuser"; $pass = "1234";
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) { die("Помилка БД: " . $e->getMessage()); }
}

// 1. Отримуємо базові метрики
$totalProducts = $pdo->query("SELECT COUNT(*) FROM product")->fetchColumn() ?: 0;
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0;

// 2. Додаткова аналітика (Дохід, Середній чек, Нові замовлення)
$totalRevenue = 0;
$avgOrderValue = 0;
$pendingOrders = 0;

try {
    $totalRevenue = $pdo->query("SELECT SUM(total_price) FROM orders")->fetchColumn() ?: 0;
    $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
    
    
    $pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Нове'")->fetchColumn() ?: 0;
} catch (Exception $e) {}


$pendingPercent = $totalOrders > 0 ? round(($pendingOrders / $totalOrders) * 100) : 0;
$completedPercent = $totalOrders > 0 ? (100 - $pendingPercent) : 0;
?>

<div class="space-y-8 fade-in">
    <div class="flex justify-between items-end">
        <div>
            <h2 class="font-['Playfair_Display'] text-3xl italic text-[#d4a373] mb-2">Аналітика та KPI</h2>
            <p class="text-[10px] uppercase tracking-[3px] text-gray-400 font-bold">Огляд ключових показників магазину</p>
        </div>
        <div class="text-[10px] font-bold text-gray-300 uppercase tracking-widest bg-white px-4 py-2 rounded-full border border-gray-100 shadow-sm">
            Оновлено: <?= date('d.m.Y H:i') ?>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <div class="p-8 bg-white rounded-[40px] border border-[#f0e6e0] shadow-sm flex flex-col items-center justify-center text-center hover:-translate-y-2 transition-transform duration-300">
            <div class="w-14 h-14 rounded-full bg-[#fcf8f5] flex items-center justify-center text-xl mb-4 text-[#d4a373]">📦</div>
            <div class="text-[9px] uppercase tracking-widest text-gray-400 font-bold mb-1">Товарів в базі</div>
            <div class="text-3xl font-black text-gray-800"><?= $totalProducts ?></div>
        </div>
        
        <div class="p-8 bg-white rounded-[40px] border border-[#f0e6e0] shadow-sm flex flex-col items-center justify-center text-center hover:-translate-y-2 transition-transform duration-300">
            <div class="w-14 h-14 rounded-full bg-[#fcf8f5] flex items-center justify-center text-xl mb-4 text-[#d4a373]">📜</div>
            <div class="text-[9px] uppercase tracking-widest text-gray-400 font-bold mb-1">Всього продажів</div>
            <div class="text-3xl font-black text-gray-800"><?= $totalOrders ?></div>
        </div>

        <div class="p-8 bg-white rounded-[40px] border border-[#f0e6e0] shadow-sm flex flex-col items-center justify-center text-center hover:-translate-y-2 transition-transform duration-300">
            <div class="w-14 h-14 rounded-full bg-[#fcf8f5] flex items-center justify-center text-xl mb-4 text-[#d4a373]">💎</div>
            <div class="text-[9px] uppercase tracking-widest text-gray-400 font-bold mb-1">Середній чек</div>
            <div class="text-3xl font-black text-gray-800">₴ <?= number_format((float)$avgOrderValue, 0, '.', ' ') ?></div>
        </div>

        <div class="p-8 bg-[#d4a373] rounded-[40px] shadow-lg flex flex-col items-center justify-center text-center text-white hover:-translate-y-2 transition-transform duration-300 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 text-7xl opacity-10">💰</div>
            <div class="text-[9px] uppercase tracking-widest text-white/80 font-bold mb-1 relative z-10">Загальний дохід</div>
            <div class="text-3xl font-black relative z-10">₴ <?= number_format((float)$totalRevenue, 0, '.', ' ') ?></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-8">
        
        <div class="lg:col-span-2 p-10 bg-white rounded-[40px] border border-[#f0e6e0] shadow-sm flex flex-col">
            <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
                <h4 class="text-xs font-black uppercase text-gray-800 tracking-widest">Останні транзакції</h4>
                <a href="?tab=orders" class="text-[9px] text-[#d4a373] font-bold uppercase hover:text-black transition">Всі →</a>
            </div>
            
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left border-collapse">
                    <tbody>
                        <?php
                        try {
                            $recent = $pdo->query("SELECT * FROM orders ORDER BY 1 DESC LIMIT 4")->fetchAll();
                            if (count($recent) > 0) {
                                foreach($recent as $r) {
                                    $id = $r['id'] ?? ($r['order_id'] ?? '-');
                                    $date = $r['created_at'] ?? ($r['date'] ?? date('d.m.Y'));
                                    $status = $r['status'] ?? 'Нове';
                                    $price = $r['total_price'] ?? 0;
                                    
                                    echo "<tr class='border-b border-gray-50 last:border-0 hover:bg-[#fcf8f5]/50 transition'>
                                            <td class='py-4 pl-2 text-sm font-black text-gray-800'>#{$id}</td>
                                            <td class='py-4 text-xs text-gray-500 font-medium'>Замовлення від {$date}</td>
                                            <td class='py-4 text-sm font-bold text-gray-800'>₴ {$price}</td>
                                            <td class='py-4 text-right pr-2'>
                                                <span class='px-4 py-1.5 bg-[#fcf8f5] border border-[#d4a373]/30 text-[#d4a373] rounded-full text-[9px] font-black uppercase tracking-wider'>
                                                    {$status}
                                                </span>
                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='py-12 text-center text-xs text-gray-400 font-bold uppercase tracking-widest'>Замовлень ще немає</td></tr>";
                            }
                        } catch(Exception $e) {
                            echo "<tr><td colspan='4' class='py-12 text-center text-xs text-red-400 font-bold uppercase tracking-widest'>Очікування структури БД</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="p-10 bg-[#fdfaf9] rounded-[40px] border border-[#f0e6e0] shadow-sm relative overflow-hidden">
            <h4 class="text-xs font-black uppercase mb-6 text-gray-800 tracking-widest border-b border-gray-200 pb-4">Статус роботи</h4>
            
            <div class="space-y-6">
                <div>
                    <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider mb-2">
                        <span class="text-[#d4a373]">Нові (Очікують)</span>
                        <span class="text-gray-800"><?= $pendingOrders ?> шт.</span>
                    </div>
                    <div class="w-full bg-white rounded-full h-2.5 border border-gray-100">
                        <div class="bg-[#d4a373] h-2.5 rounded-full" style="width: <?= $pendingPercent ?>%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider mb-2">
                        <span class="text-gray-400">В роботі / Готово</span>
                        <span class="text-gray-800"><?= $totalOrders - $pendingOrders ?> шт.</span>
                    </div>
                    <div class="w-full bg-white rounded-full h-2.5 border border-gray-100">
                        <div class="bg-gray-300 h-2.5 rounded-full" style="width: <?= $completedPercent ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="mt-10 p-4 bg-white/60 rounded-2xl border border-[#d4a373]/20 text-center">
                <span class="text-xl block mb-2">✨</span>
                <p class="text-[9px] font-bold uppercase text-gray-500 tracking-widest leading-relaxed">
                    "Найкращий сервіс — це коли клієнт повертається знову."
                </p>
            </div>
        </div>

    </div>
</div>