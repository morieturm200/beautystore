<?php session_start(); ?>
<?php include 'includes/header.php'; ?>

<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Про нас | BeautyStore</title>

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&family=Playfair+Display:ital,wght@0,700;1,400&display=swap" rel="stylesheet">

<style>

:root{
--primary:#1a1a1a;
--accent:#d4a373;
--border:#ececec;
--bg-light:#fdfaf9;
--white:#ffffff;
}

*{
box-sizing:border-box;
margin:0;
padding:0;
font-family:'Montserrat', sans-serif;
}

body{
background:var(--bg-light);
color:var(--primary);
}


/* HERO */

.about-hero{
padding:120px 20px;
text-align:center;
background:white;
border-bottom:1px solid var(--border);
}

.about-hero h1{
font-family:'Playfair Display', serif;
font-size:3rem;
margin-bottom:20px;
}

.about-hero p{
max-width:700px;
margin:auto;
color:#666;
line-height:1.7;
}


/* WHY US */

.why{
padding:100px 40px;
background:white;
}

.why h2{
text-align:center;
font-family:'Playfair Display', serif;
font-size:2.5rem;
margin-bottom:60px;
font-style:italic;
}

.why-grid{
max-width:1200px;
margin:auto;
display:flex;
flex-wrap:wrap;
gap:40px;
justify-content:center;
}

.why-card{
flex:1 1 220px;
max-width:260px;
padding:30px;
text-align:center;
border:1px solid var(--border);
transition:0.3s;
}

.why-card:hover{
transform:translateY(-6px);
border-color:var(--accent);
}

.why-icon{
font-size:30px;
margin-bottom:15px;
color:var(--accent);
}

.why-card h4{
font-size:14px;
margin-bottom:10px;
text-transform:uppercase;
}

.why-card p{
font-size:13px;
color:#666;
line-height:1.6;
}


/* REVIEWS */

.reviews{
padding:100px 40px;
background:var(--bg-light);
}

.reviews h2{
text-align:center;
font-family:'Playfair Display', serif;
font-size:2.5rem;
margin-bottom:60px;
}

.review-grid{
max-width:1200px;
margin:auto;
display:flex;
gap:30px;
flex-wrap:wrap;
justify-content:center;
}

.review{
flex:1 1 280px;
max-width:340px;
background:white;
padding:30px;
border:1px solid var(--border);
transition:0.3s;
}

.review:hover{
transform:translateY(-5px);
}

.review-text{
font-size:14px;
color:#666;
line-height:1.7;
margin-bottom:20px;
}

.review-user{
display:flex;
justify-content:space-between;
font-size:13px;
}

.stars{
color:#f1c40f;
}


/* STATS */

.stats{
padding:100px 20px;
background:white;
border-top:1px solid var(--border);
border-bottom:1px solid var(--border);
}

.stats-grid{
max-width:1000px;
margin:auto;
display:flex;
justify-content:space-around;
flex-wrap:wrap;
gap:40px;
text-align:center;
}

.stat h3{
font-family:'Playfair Display', serif;
font-size:2.5rem;
margin-bottom:10px;
}

.stat p{
font-size:12px;
letter-spacing:2px;
text-transform:uppercase;
color:#888;
}

</style>

</head>
<body>


<section class="about-hero">

<h1>Про BeautyStore</h1>

<p>
BeautyStore — це сучасний онлайн-магазин косметики, створений для тих, 
хто цінує якість, стиль та ефективний догляд. Ми пропонуємо популярні 
світові бренди косметики та парфумерії, щоб кожен клієнт міг знайти 
ідеальний продукт для себе.
</p>

</section>



<section class="stats">

<div class="stats-grid">

<div class="stat">
<h3>10K+</h3>
<p>Клієнтів</p>
</div>

<div class="stat">
<h3>50+</h3>
<p>Брендів</p>
</div>

<div class="stat">
<h3>5 років</h3>
<p>на ринку</p>
</div>

<div class="stat">
<h3>98%</h3>
<p>задоволених клієнтів</p>
</div>

</div>

</section>



<section class="why">

<h2>Чому обирають нас</h2>

<div class="why-grid">

<div class="why-card">
<div class="why-icon">★</div>
<h4>Оригінальна продукція</h4>
<p>Ми продаємо лише сертифіковану косметику від офіційних постачальників.</p>
</div>

<div class="why-card">
<div class="why-icon">⚡</div>
<h4>Швидка доставка</h4>
<p>Відправляємо замовлення по всій Україні протягом 24 годин.</p>
</div>

<div class="why-card">
<div class="why-icon">❤</div>
<h4>Популярні бренди</h4>
<p>У нашому каталозі представлені найвідоміші світові бренди косметики.</p>
</div>

<div class="why-card">
<div class="why-icon">✓</div>
<h4>Гарантія якості</h4>
<p>Кожен товар проходить перевірку перед відправкою.</p>
</div>

</div>

</section>



<section class="reviews">

<h2>Відгуки клієнтів</h2>

<div class="review-grid">

<div class="review">

<p class="review-text">
"Замовляла сироватку та крем. Доставка дуже швидка, 
товари оригінальні. Рекомендую цей магазин!"
</p>

<div class="review-user">
<strong>Олена К.</strong>
<span class="stars">★★★★★</span>
</div>

</div>



<div class="review">

<p class="review-text">
"Дуже зручний сайт і великий вибір косметики. 
Завжди знаходжу тут свої улюблені бренди."
</p>

<div class="review-user">
<strong>Марія Д.</strong>
<span class="stars">★★★★★</span>
</div>

</div>



<div class="review">

<p class="review-text">
"Замовляла парфуми — аромат оригінальний, 
все гарно запаковано. Буду замовляти ще."
</p>

<div class="review-user">
<strong>Ірина Л.</strong>
<span class="stars">★★★★★</span>
</div>

</div>

</div>

</section>



<?php include 'includes/footer.php'; ?>

</body>
</html>