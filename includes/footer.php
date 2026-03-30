<footer>
    <style>
        footer {
            background-color: var(--white); padding: 80px 50px;
            display: flex; justify-content: space-between; border-top: 1px solid var(--border);
        }
        footer h4 { margin-bottom: 20px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; }
        footer a { color: #888; text-decoration: none; display: block; margin-bottom: 10px; font-size: 0.9rem; }
        
        /* Повідомлення: Стиль */
        #wishlistToast, #cartToast {
            position: fixed; 
            bottom: 30px; 
            left: 50%; 
            transform: translateX(-50%) translateY(150px);
            padding: 16px 45px; 
            font-size: 11px;
            text-transform: uppercase; 
            letter-spacing: 2px; 
            z-index: 10001;
            transition: transform 0.5s cubic-bezier(0.23, 1, 0.32, 1);
            font-weight: 700; 
            text-align: center; 
            min-width: 280px;
            pointer-events: none;
            backdrop-filter: blur(5px);
        }

        #wishlistToast { background: #1a1a1a; color: white; border: 1px solid var(--accent); }
        #cartToast { background: #1a1a1a; color: #d4a373; border: 1px solid #d4a373; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }

        /* Клас для плавної появи */
        .show-toast { transform: translateX(-50%) translateY(0) !important; }
    </style>

    <div>
        <h4>House of Beauty</h4>
        <a href="about.php">Про нас</a>
    </div>
    <div>
        <h4>Підтримка</h4>
        <a href="contacts.php">Контакти</a>
    </div>
    <div>
        <h4>Соцмережі</h4>
        <a href="#">Instagram</a>
        <a href="#">Pinterest</a>
    </div>
</footer>

<div id="wishlistToast">Додано в обране</div>
<div id="cartToast">ТОВАР ДОДАНО В КОШИК ✨</div>

<script>
// Універсальна функція для AJAX запитів (Кошик та Обране)
async function handleAjaxAction(e, fileTarget, toastId) {
    const link = e.target.closest(`a[href*="${fileTarget}"]`);
    if (!link) return;

    e.preventDefault();
    e.stopImmediatePropagation();

    const url = new URL(link.href);
    const productId = url.searchParams.get('id');

    try {
        const response = await fetch(`${fileTarget}?id=${productId}&ajax=1`);
        
        if (response.redirected) {
            window.location.href = response.url;
            return;
        }

        // Спробуємо розпарсити як JSON (для вішліста), якщо ні — як текст
        const contentType = response.headers.get("content-type");
        let data;
        if (contentType && contentType.includes("application/json")) {
            data = await response.json();
            if (data.status === 'error' && data.redirect) {
                window.location.href = data.redirect;
                return;
            }
        } else {
            await response.text();
        }

        // 1. Показуємо потрібну плашку (Toast)
        const toast = document.getElementById(toastId);
        if (toast) {
            // Якщо це обране, змінюємо текст залежно від статусу
            if (toastId === 'wishlistToast' && data) {
                toast.innerText = data.status === 'added' ? "ДОДАНО В ОБРАНЕ" : "ВИДАЛЕНО З ОБРАНОГО";
            }
            
            toast.classList.add('show-toast');
            setTimeout(() => toast.classList.remove('show-toast'), 3000);
        }

        // 2. Оновлюємо лічильники в меню
        const menuLinks = document.querySelectorAll('nav a, header a');
        menuLinks.forEach(el => {
            const text = el.textContent.toUpperCase();
            
            // Оновлення Кошика
            if (fileTarget.includes('cart') && text.includes('КОШИК')) {
                updateCounter(el, 'КОШИК');
            }
            // Оновлення Обраного
            if (fileTarget.includes('wishlist') && text.includes('ОБРАНЕ')) {
                // Якщо маємо точну цифру з бази (data.count), ставимо її
                if (data && typeof data.count !== 'undefined') {
                    el.innerHTML = `ОБРАНЕ (<span>${data.count}</span>)`;
                } else {
                    updateCounter(el, 'ОБРАНЕ');
                }
            }
        });

    } catch (err) {
        console.error('Помилка AJAX:', err);
        window.location.href = link.href; // Резервний перехід
    }
}

// Допоміжна функція для інкременту цифри
function updateCounter(el, label) {
    let count = el.textContent.match(/\d+/) ? parseInt(el.textContent.match(/\d+/)[0]) : 0;
    el.innerHTML = `${label} (<span>${count + 1}</span>)`;
}

// Слухаємо кліки
document.addEventListener('click', (e) => {
    // Якщо клікнули на Кошик
    if (e.target.closest('a[href*="cart_add.php"]')) {
        handleAjaxAction(e, 'cart_add.php', 'cartToast');
    }
    // Якщо клікнули на Обране (сердечко)
    if (e.target.closest('a[href*="wishlist_add.php"]')) {
        handleAjaxAction(e, 'wishlist_add.php', 'wishlistToast');
    }
}, true);
</script>