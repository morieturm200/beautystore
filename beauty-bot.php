<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:ital,wght@0,600;1,500&display=swap');

    .beauty-bot-btn {
        position: fixed; bottom: 30px; right: 30px;
        background: #111; color: #d4a373;
        width: 65px; height: 65px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 30px; cursor: pointer;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        z-index: 2000; transition: 0.4s;
        border: 1px solid #d4a373;
    }
    .beauty-bot-btn:hover { transform: scale(1.1); background: #d4a373; color: white; }

    .bot-window {
        position: fixed; bottom: 110px; right: 30px;
        width: 350px; background: white; border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.12);
        display: none; flex-direction: column;
        overflow: hidden; z-index: 2000; border: 1px solid #eee;
        font-family: 'Montserrat', sans-serif;
    }

    .bot-header { 
        background: #111; color: #d4a373; padding: 20px; 
        font-family: 'Playfair Display', serif; font-size: 1.2rem;
        font-style: italic; text-align: center; display: flex;
        justify-content: space-between; align-items: center;
    }

    .bot-content { padding: 25px; min-height: 280px; display: flex; flex-direction: column; gap: 15px; }

    .bot-msg { 
        background: #fdfaf9; padding: 15px; border-radius: 18px 18px 18px 4px; 
        font-size: 14px; color: #444; line-height: 1.6; border: 1px solid #f1e9e5;
        animation: fadeIn 0.5s ease;
    }

    .bot-option { 
        background: white; border: 1px solid #d4a373; color: #111; 
        padding: 12px; border-radius: 12px; cursor: pointer; 
        text-align: center; font-size: 13px; font-weight: 500;
        text-transform: uppercase; letter-spacing: 1px; transition: 0.3s;
    }
    .bot-option:hover { background: #111; color: white; border-color: #111; }
    
    .bot-close { cursor: pointer; font-size: 22px; }

    .bot-result-btn {
        background: #d4a373; color: white; text-decoration: none; 
        padding: 16px; border-radius: 12px; text-align: center; 
        font-weight: 600; font-size: 13px; text-transform: uppercase;
        letter-spacing: 1.5px; margin-top: 10px; transition: 0.3s;
        display: block;
    }
    .bot-result-btn:hover { background: #111; }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="beauty-bot-btn" id="botBtn" onclick="toggleBot()">✨</div>

<div class="bot-window" id="botWindow">
    <div class="bot-header">
        <span>Beauty Concierge</span>
        <span class="bot-close" onclick="toggleBot()">×</span>
    </div>
    <div class="bot-content" id="botContent">
        </div>
</div>

<script>
let userSelections = { skin: '', goal: '' };

function toggleBot() {
    const win = document.getElementById('botWindow');
    win.style.display = (win.style.display === 'flex') ? 'none' : 'flex';
    if (win.style.display === 'flex' && userSelections.skin === '') botStart();
}

function botStart() {
    const content = document.getElementById('botContent');
    content.innerHTML = `
        <div class="bot-msg">Привіт! Я підберу догляд за 10 секунд. Яка у вас шкіра?</div>
        <div class="bot-option" onclick="botNextStep('суха')">Суха / Чутлива</div>
        <div class="bot-option" onclick="botNextStep('жирна')">Жирна / Проблемна</div>
        <div class="bot-option" onclick="botNextStep('нормальна')">Нормальна / Комбі</div>
    `;
}

function botNextStep(skinType) {
    userSelections.skin = skinType;
    const content = document.getElementById('botContent');
    content.innerHTML = `
        <div class="bot-msg">Зрозумів. Що саме шукаємо для вашої шкіри?</div>
        <div class="bot-option" onclick="botShowResult('крем')">Зволожуючий крем</div>
        <div class="bot-option" onclick="botShowResult('сироватка')">Активна сироватка</div>
        <div class="bot-option" onclick="botShowResult('очищення')">Засіб для вмивання</div>
    `;
}

function botShowResult(productType) {
    userSelections.goal = productType;
    const content = document.getElementById('botContent');
    
    // Формуємо максимально простий запит для бази
    // Наприклад: "суха+крем"
    const searchQuery = userSelections.skin + " " + userSelections.goal;
    
    content.innerHTML = `
        <div class="bot-msg">Готово! Я знайшов найкращі варіанти <b>${userSelections.goal}</b> для вашого типу шкіри.</div>
        <a href="catalog.php?search=${encodeURIComponent(searchQuery)}" class="bot-result-btn">Відкрити підбірку ✨</a>
        <div style="text-align:center; margin-top:15px; font-size:10px; color:#ccc; cursor:pointer;" onclick="botStart()">🔄 Почати спочатку</div>
    `;
}
</script>