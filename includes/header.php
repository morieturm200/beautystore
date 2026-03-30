<?php 
// 1. Ініціалізація сесії та бази
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli("localhost", "beautyuser", "1234", "beautystore");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// 2. Логіка авторизації
$is_logged_in = false;
$display_name = "";
$wishlist_count = 0;

if (isset($_SESSION['customer_id'])) {
    $is_logged_in = true;
    $c_id = $_SESSION['customer_id'];

    // Отримуємо ім'я користувача (з сесії або бази)
    if (isset($_SESSION['customer_name'])) {
        $display_name = $_SESSION['customer_name'];
    } else {
        $user_res = $conn->query("SELECT first_name FROM Customer WHERE customer_id = $c_id");
        if ($user_res && $row = $user_res->fetch_assoc()) {
            $display_name = $row['first_name'];
            $_SESSION['customer_name'] = $display_name;
        }
    }

    // ЛІЧИЛЬНИК ОБРАНОГО (тільки з бази для зареєстрованих)
    $wish_res = $conn->query("SELECT COUNT(*) as cnt FROM Wishlist WHERE customer_id = $c_id");
    $wishlist_count = ($wish_res) ? $wish_res->fetch_assoc()['cnt'] : 0;
}

// 3. ЛІЧИЛЬНИК КОШИКА (універсальний)
$total_items = 0;
if (isset($_SESSION['cart'])) {
    $total_items = array_sum($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<style>
:root{ 
    --primary:#1a1a1a; 
    --accent:#d4a373; 
    --bg-light:#fdfaf9; 
    --white:#ffffff; 
    --border:#ececec; 
    --heart:#e74c3c; 
}
*{ box-sizing:border-box; margin:0; padding:0; font-family:'Montserrat', sans-serif; }
body{ background-color:var(--bg-light); color:var(--primary); overflow-x:hidden; }
header{ 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    padding:25px 50px; 
    background-color:rgba(255,255,255,0.98); 
    border-bottom:1px solid var(--border); 
    position:sticky; 
    top:0; 
    z-index:1000; 
}
header .logo{ font-family:'Playfair Display', serif; font-size:2rem; font-weight:700; color:var(--primary); text-decoration:none; }
.search-container{ flex-grow:1; max-width:400px; margin:0 30px; position:relative; }
.search-container input{ 
    width:100%; padding:10px 20px; border:1px solid var(--border); 
    outline:none; font-size:0.9rem; background:transparent; 
}
.search-container button{ 
    position:absolute; right:10px; top:50%; transform:translateY(-50%); 
    background:none; border:none; cursor:pointer; color:var(--accent); 
}
nav{ display: flex; align-items: center; }
nav a{ 
    margin-left:25px; text-decoration:none; color:var(--primary); 
    font-weight:500; font-size:0.75rem; text-transform:uppercase; 
    letter-spacing:1px; transition: 0.3s; 
}
nav a:hover{ color:var(--accent); }
.user-link { 
    display: flex; align-items: center; gap: 8px; 
    color: var(--accent) !important; font-weight: 700 !important; 
    border-bottom: 1px solid var(--accent); padding-bottom: 2px; 
}
.logout-link { color: #e74c3c !important; font-size: 0.65rem !important; margin-left: 15px !important; }

/* Додатково: стиль для цифр у дужках */
nav a span { color: var(--accent); font-weight: 700; }
</style>
</head>
<body>
<header>
    <a href="index.php" class="logo">BeautyStore</a>
    
    <form action="catalog.php" method="GET" class="search-container">
        <input type="text" name="search" placeholder="Що ви шукаєте?" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <button type="submit">🔍</button>
    </form>
    
    <nav>
        <a href="index.php">Головна</a>
        <a href="catalog.php">Каталог</a>
        <a href="sales.php">Акції</a>
        
        <?php if ($is_logged_in): ?>
            <a href="wishlist.php">ОБРАНЕ (<span><?php echo $wishlist_count; ?></span>)</a>
            <a href="cart.php">Кошик (<span><?php echo $total_items; ?></span>)</a>
            
            <a href="profile.php" class="user-link">
                👤 <?php echo htmlspecialchars($display_name); ?>
            </a>
            <a href="logout.php" class="logout-link">Вихід</a>
        <?php else: ?>
            <a href="login_register.php" style="color:var(--accent); font-weight:700;">Вхід</a>
        <?php endif; ?>
    </nav>
</header>