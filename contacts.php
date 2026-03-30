<?php 
session_start(); 


// 1. ПІДКЛЮЧЕННЯ ДО БД
try {
    $pdo = new PDO("mysql:host=localhost;dbname=beautystore;charset=utf8", "beautyuser", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) { 
    $db_error = $e->getMessage(); 
}

// 2. ОБРОБКА AJAX-ЗАПИТУ ВІД ФОРМИ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    header('Content-Type: application/json');
    
    $customer_id = $_SESSION['customer_id'] ?? null;
    $name = $_POST['customer_name'] ?? 'Гість';
    $subject = "Запит від: " . $name;
    $message = $_POST['message'];
    $date = date('Y-m-d H:i:s');

    try {
        $sql = "INSERT INTO Support (customer_id, subject, message, submitted_date, status) VALUES (?, ?, ?, ?, 'new')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customer_id, $subject, $message, $date]);
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit; // Зупиняємо виконання, щоб не виводити HTML нижче
}

// 3. ДАНІ ДЛЯ ШАПКИ (Враховуючи твою нову логіку Wishlist з бази)
$wishlist_count = 0;
if (isset($_SESSION['customer_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Wishlist WHERE customer_id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $wishlist_count = $stmt->fetchColumn();
}
$total_items = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<?php include 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacts | BeautyStore Privé</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&family=Playfair+Display:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a1a1a;
            --accent: #d4a373; /* Твоє золото */
            --bg-light: #fdfaf9;
            --white: #ffffff;
            --border: #e8e8e8;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Montserrat', sans-serif; }
        body { background-color: var(--bg-light); color: var(--primary); line-height: 1.6; }

        nav a {
            margin-left: 25px; text-decoration: none; color: var(--primary);
            font-weight: 500; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;
        }
        nav a:hover { color: var(--accent); }

        .contact-hero {
            background: var(--white); padding: 120px 20px; text-align: center;
            border-bottom: 1px solid var(--border);
        }
        .badge-prive { color: var(--accent); font-size: 10px; letter-spacing: 5px; text-transform: uppercase; font-weight: 700; margin-bottom: 20px; display: block; }
        .contact-hero h1 { font-family: 'Playfair Display', serif; font-size: 4rem; font-style: italic; margin-bottom: 25px; font-weight: 400; }
        .contact-hero p { max-width: 650px; margin: 0 auto 40px; color: #666; font-size: 1.1rem; }

        .container { max-width: 1200px; margin: 80px auto; padding: 0 50px; }
        .contact-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 60px; }

        .info-panel { display: flex; flex-direction: column; gap: 40px; }
        .info-card {
            background: var(--white); border: 1px solid var(--border); padding: 40px;
            transition: 0.4s;
        }
        .info-card:hover { border-color: var(--accent); }
        .info-card span { display: block; font-size: 10px; text-transform: uppercase; color: var(--accent); font-weight: 700; letter-spacing: 2px; margin-bottom: 15px; }
        .info-card h3 { font-family: 'Playfair Display', serif; font-size: 1.6rem; margin-bottom: 10px; font-weight: 400; }
        .info-card p { font-size: 0.9rem; color: #888; }

        .form-panel {
            background: var(--white); border: 1px solid var(--border); padding: 60px;
            position: relative;
        }
        .form-panel h2 { font-family: 'Playfair Display', serif; font-size: 2.5rem; font-weight: 400; margin-bottom: 40px; font-style: italic; }
        
        .input-group { margin-bottom: 30px; position: relative; }
        .input-group label { font-size: 9px; text-transform: uppercase; letter-spacing: 2px; font-weight: 700; color: #bbb; display: block; margin-bottom: 10px; }
        
        input, textarea {
            width: 100%; padding: 15px 0; border: none; border-bottom: 1px solid var(--border);
            background: transparent; outline: none; font-size: 1rem; transition: 0.3s;
        }
        input:focus, textarea:focus { border-bottom-color: var(--accent); }

        .submit-btn {
            display: inline-block; width: 100%; padding: 22px; background: var(--primary);
            color: var(--white); text-transform: uppercase; font-size: 11px;
            letter-spacing: 3px; font-weight: 700; border: none; cursor: pointer; transition: 0.4s;
        }
        .submit-btn:hover { background: var(--accent); }

        #success-box { display: none; text-align: center; padding: 40px 0; }
        #success-box h3 { font-family: 'Playfair Display', serif; font-size: 2rem; color: var(--accent); margin-bottom: 20px; }

        footer {
            background: var(--primary); color: #fff; padding: 60px 50px; text-align: center;
            font-size: 10px; letter-spacing: 3px; text-transform: uppercase; opacity: 0.8;
        }

        @media (max-width: 992px) {
            .contact-grid { grid-template-columns: 1fr; }
            .contact-hero h1 { font-size: 2.5rem; }
            .form-panel { padding: 40px 20px; }
        }
    </style>
</head>
<body>



<section class="contact-hero">
    <span class="badge-prive">Customer Care & Concierge</span>
    <h1>Contact Privé</h1>
    <p>Ми тут, щоб зробити ваш досвід бездоганним. Зв'яжіться з нашою службою підтримки.</p>
</section>

<div class="container">
    <div class="contact-grid">
        <div class="info-panel">
            <div class="info-card">
                <span>Direct Line</span>
                <h3>Зателефонуйте нам</h3>
                <p>+380 50 123 45 67<br>Пн-Пт: 09:00 — 20:00</p>
            </div>
            <div class="info-card">
                <span>Email Support</span>
                <h3>Напишіть нам</h3>
                <p>prive-support@beautystore.ua<br>Відповідаємо протягом 2 годин.</p>
            </div>
            <div class="info-card">
                <span>Boutique Address</span>
                <h3>Завітайте до нас</h3>
                <p>Київ, вул. Хрещатик, 1</p>
            </div>
        </div>

        <div class="form-panel">
            <div id="form-content">
                <h2>Надіслати запит</h2>
                <form id="priveContactForm">
                    <div class="input-group">
                        <label>Ваше ім'я</label>
                        <input type="text" name="customer_name" placeholder="Юлія Ковальчук" required>
                    </div>
                    <div class="input-group">
                        <label>Електронна адреса</label>
                        <input type="email" name="customer_email" placeholder="example@gmail.com" required>
                    </div>
                    <div class="input-group">
                        <label>Ваше повідомлення</label>
                        <textarea name="message" rows="4" placeholder="Опишіть ваше питання..." required></textarea>
                    </div>
                    <button type="submit" class="submit-btn">Надіслати консьєржу</button>
                </form>
            </div>

            <div id="success-box">
                <h3>Merci!</h3>
                <p style="font-size: 0.9rem; color: #888;">Ваше повідомлення отримано.</p>
                <button onclick="location.reload()" style="margin-top:30px; background:none; border:none; border-bottom:1px solid #000; cursor:pointer; font-weight:700; text-transform:uppercase; font-size:10px; letter-spacing:2px;">Надіслати ще раз</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    document.getElementById('priveContactForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('form-content').style.display = 'none';
                document.getElementById('success-box').style.display = 'block';
            } else {
                alert('Помилка: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
</script>

</body>
</html>