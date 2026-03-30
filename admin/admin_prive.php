<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. ПІДКЛЮЧЕННЯ
$host = "localhost"; $db = "beautystore"; $user = "beautyuser"; $pass = "1234";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) { die("Помилка БД: " . $e->getMessage()); }

// 2. ЗАХИСТ
if (!isset($_SESSION['is_prive_admin'])) { header("Location: ../login_register.php"); exit(); }

// Визначаємо активну вкладку
$tab = $_GET['tab'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Privé Console | Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&family=Playfair+Display:ital,wght@1,600&display=swap" rel="stylesheet">
    <style>
        body { background: #fdfaf9; font-family: 'Plus Jakarta Sans', sans-serif; color: #2d2d2d; }
        .glass-card { 
            background: white; 
            border-radius: 40px; 
            border: 1px solid #f0e6e0; 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 30px rgba(212, 163, 115, 0.05);
        }
        .glass-card:hover { 
            transform: translateY(-10px); 
            box-shadow: 0 30px 60px rgba(212, 163, 115, 0.15);
            border-color: #d4a373;
        }
        .bg-pattern {
            background-image: radial-gradient(#d4a373 0.5px, transparent 0.5px);
            background-size: 30px 30px;
            opacity: 0.2;
        }
        .nav-active { color: #d4a373 !important; border-bottom: 2px solid #d4a373; }
        /* Анімація появи */
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="min-h-screen flex flex-col relative overflow-x-hidden">

    <div class="absolute inset-0 bg-pattern -z-10"></div>

    <?php if ($tab == 'dashboard'): ?>
        <div class="flex-1 flex flex-col items-center justify-center p-6 fade-in">
            
            <div class="text-center mb-16">
                <h1 class="font-['Playfair_Display'] text-5xl italic text-[#d4a373] mb-4">Панель адміністратора</h1>
                <p class="text-[10px] uppercase tracking-[10px] text-gray-400 font-bold">Панель керування системою</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-8 max-w-[90rem] w-full px-6">
                
                <a href="?tab=products" class="glass-card p-10 text-center group flex flex-col items-center">
                    <div class="w-16 h-16 rounded-full bg-[#fcf8f5] flex items-center justify-center text-2xl mb-6 group-hover:bg-[#d4a373] group-hover:text-white transition-all duration-500 shadow-sm">📦</div>
                    <h3 class="font-extrabold uppercase text-[10px] tracking-[3px] mb-3 text-gray-800">Склад</h3>
                    <p class="text-[9px] text-gray-400 font-medium uppercase tracking-wider">Товари</p>
                </a>

                <a href="?tab=orders" class="glass-card p-10 text-center group flex flex-col items-center">
                    <div class="w-16 h-16 rounded-full bg-[#fcf8f5] flex items-center justify-center text-2xl mb-6 group-hover:bg-[#d4a373] group-hover:text-white transition-all duration-500 shadow-sm">📜</div>
                    <h3 class="font-extrabold uppercase text-[10px] tracking-[3px] mb-3 text-gray-800">Продажі</h3>
                    <p class="text-[9px] text-gray-400 font-medium uppercase tracking-wider">Замовлення</p>
                </a>

                <a href="?tab=chars" class="glass-card p-10 text-center group flex flex-col items-center border-2 border-dashed border-[#d4a373]/20 bg-[#fdfaf9]/50">
                    <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center text-2xl mb-6 group-hover:bg-black group-hover:text-white transition-all duration-500 shadow-sm">📊</div>
                    <h3 class="font-extrabold uppercase text-[10px] tracking-[3px] mb-3 text-gray-800">Аналітика</h3>
                    <p class="text-[9px] text-[#d4a373] font-bold uppercase tracking-wider">Звіти та KPI</p>
                </a>

                <a href="?tab=support" class="glass-card p-10 text-center group flex flex-col items-center">
                    <div class="w-16 h-16 rounded-full bg-[#fcf8f5] flex items-center justify-center text-2xl mb-6 group-hover:bg-[#d4a373] group-hover:text-white transition-all duration-500 shadow-sm">📩</div>
                    <h3 class="font-extrabold uppercase text-[10px] tracking-[3px] mb-3 text-gray-800">Сервіс</h3>
                    <p class="text-[9px] text-gray-400 font-medium uppercase tracking-wider">Підтримка</p>
                </a>

                <a href="?tab=reviews" class="glass-card p-10 text-center group flex flex-col items-center">
                    <div class="w-16 h-16 rounded-full bg-[#fcf8f5] flex items-center justify-center text-2xl mb-6 group-hover:bg-[#d4a373] group-hover:text-white transition-all duration-500 shadow-sm">⭐</div>
                    <h3 class="font-extrabold uppercase text-[10px] tracking-[3px] mb-3 text-gray-800">Відгуки</h3>
                    <p class="text-[9px] text-gray-400 font-medium uppercase tracking-wider">Модерація</p>
                </a>

                <a href="?tab=request" class="glass-card p-10 text-center group flex flex-col items-center">
                    <div class="w-16 h-16 rounded-full bg-[#fcf8f5] flex items-center justify-center text-2xl mb-6 group-hover:bg-[#d4a373] group-hover:text-white transition-all duration-500 shadow-sm">📝</div>
                    <h3 class="font-extrabold uppercase text-[10px] tracking-[3px] mb-3 text-gray-800">Запити</h3>
                    <p class="text-[9px] text-gray-400 font-medium uppercase tracking-wider">Звернення</p>
                </a>

            </div>

            <div class="mt-20 flex flex-col items-center gap-6">
                <div class="flex gap-8 text-[9px] font-black uppercase tracking-[4px] text-gray-300">
                    <span>Товарів: <?php echo $pdo->query("SELECT COUNT(*) FROM product")->fetchColumn(); ?></span>
                    <span>•</span>
                    <span>Замовлень: <?php echo $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(); ?></span>
                </div>
                <a href="../logout.php" class="text-[10px] font-black uppercase text-red-400 border border-red-100 px-8 py-3 rounded-full hover:bg-red-500 hover:text-white transition duration-300">Вийти з акаунта</a>
            </div>
        </div>

    <?php else: ?>
        <header class="bg-white border-b border-gray-100 p-6 flex justify-between items-center px-12 sticky top-0 z-50 shadow-sm">
            <a href="?tab=dashboard" class="font-['Playfair_Display'] text-xl italic text-[#d4a373]">Privé Console</a>
            
            <nav class="flex gap-8 text-[10px] font-black uppercase tracking-widest">
                <a href="?tab=products" class="<?= $tab=='products'?'nav-active text-[#d4a373]':'text-gray-400' ?> hover:text-[#d4a373] transition">Склад</a>
                <a href="?tab=orders" class="<?= $tab=='orders'?'nav-active text-[#d4a373]':'text-gray-400' ?> hover:text-[#d4a373] transition">Продажі</a>
                <a href="?tab=chars" class="<?= $tab=='chars'?'nav-active text-[#d4a373]':'text-gray-400' ?> hover:text-[#d4a373] transition">Аналітика</a>
                <a href="?tab=support" class="<?= $tab=='support'?'nav-active text-[#d4a373]':'text-gray-400' ?> hover:text-[#d4a373] transition">Сервіс</a>
                <a href="?tab=reviews" class="<?= $tab=='reviews'?'nav-active text-[#d4a373]':'text-gray-400' ?> hover:text-[#d4a373] transition">Відгуки</a>
                <a href="?tab=request" class="<?= $tab=='request'?'nav-active text-[#d4a373]':'text-gray-400' ?> hover:text-[#d4a373] transition">Запити</a>
            </nav>

            <a href="?tab=dashboard" class="text-[10px] font-black text-gray-400 uppercase border-b border-gray-400 hover:text-black transition">← Меню</a>
        </header>

        <?php if (isset($_GET['msg'])): ?>
            <div class="max-w-7xl mx-auto mt-6 px-12">
                <div class="bg-green-50 border border-green-200 text-green-600 px-6 py-3 rounded-2xl text-xs font-bold uppercase tracking-widest fade-in">
                    ✨ <?= htmlspecialchars($_GET['msg']) ?>
                </div>
            </div>
        <?php endif; ?>

        <main class="p-12 max-w-7xl mx-auto w-full fade-in">
            <?php 
                $file = "mod_" . $tab . ".php";
                if (file_exists($file)) {
                    include $file;
                } else {
                    echo "
                    <div class='text-center py-20 bg-white rounded-[40px] border border-dashed border-gray-200'>
                        <p class='text-gray-400 uppercase font-bold text-xs tracking-widest'>Модуль <span class='text-red-400'>$file</span> ще не створено</p>
                    </div>";
                }
            ?>
        </main>
    <?php endif; ?>

</body>
</html>