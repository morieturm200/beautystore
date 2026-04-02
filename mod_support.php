<?php

$host = "localhost"; $db = "beautystore"; $user = "beautyuser"; $pass = "1234";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) { die("Помилка БД"); }


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $sid = $_POST['support_id'];
    $reply = $_POST['admin_reply'];
    $status = $_POST['new_status'] ?? 'pending';

    $st = $pdo->prepare("UPDATE Support SET admin_reply = ?, status = ? WHERE support_id = ?");
    $st->execute([$reply, $status, $sid]);
    
    header("Location: admin_prive.php?tab=support&chat_with=" . $_POST['customer_id'] . "&msg=Відповідь надіслано");
    exit();
}


$chat_query = $pdo->query("
    SELECT s.customer_id, c.first_name, c.email, 
           COUNT(*) as total_msgs,
           MAX(s.submitted_date) as last_date,
           SUM(CASE WHEN s.status = 'new' THEN 1 ELSE 0 END) as new_count
    FROM Support s
    JOIN customer c ON s.customer_id = c.customer_id
    GROUP BY s.customer_id
    ORDER BY new_count DESC, last_date DESC
");
$chats = $chat_query->fetchAll(PDO::FETCH_ASSOC);


$active_chat = isset($_GET['chat_with']) ? (int)$_GET['chat_with'] : null;
$messages = [];
if ($active_chat) {
    $msg_st = $pdo->prepare("SELECT * FROM Support WHERE customer_id = ? ORDER BY submitted_date ASC");
    $msg_st->execute([$active_chat]);
    $messages = $msg_st->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="flex flex-col lg:flex-row gap-8 min-h-[700px]">
    
    <div class="w-full lg:w-1/3 space-y-4">
        <div class="px-4 flex justify-between items-center">
            <h2 class="text-2xl font-black italic uppercase text-gray-800">Діалоги</h2>
            <span class="bg-black text-white text-[10px] px-3 py-1 rounded-full font-bold uppercase"><?= count($chats) ?></span>
        </div>
        
        <div class="bg-white rounded-[40px] border border-[#f0e6e0] overflow-hidden shadow-sm h-[600px] overflow-y-auto">
            <?php if(empty($chats)): ?>
                <div class="p-20 text-center text-gray-300 text-[10px] font-bold uppercase tracking-widest">Немає звернень</div>
            <?php endif; ?>

            <?php foreach($chats as $chat): ?>
                <a href="?tab=support&chat_with=<?= $chat['customer_id'] ?>" 
                   class="flex items-center justify-between p-6 border-b border-gray-50 hover:bg-[#fcfaf8] transition <?= $active_chat == $chat['customer_id'] ? 'bg-[#fdfaf9] border-r-8 border-[#d4a373]' : '' ?>">
                    <div class="flex flex-col">
                        <span class="font-black text-sm text-gray-800 uppercase tracking-tighter"><?= htmlspecialchars($chat['first_name']) ?></span>
                        <span class="text-[9px] text-gray-400 font-bold"><?= $chat['total_msgs'] ?> повідомлень</span>
                    </div>
                    <?php if($chat['new_count'] > 0): ?>
                        <div class="w-6 h-6 bg-[#d4a373] text-white flex items-center justify-center rounded-full text-[10px] font-black"><?= $chat['new_count'] ?></div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="flex-1 flex flex-col bg-white rounded-[50px] border border-[#f0e6e0] shadow-sm overflow-hidden h-[700px]">
        <?php if($active_chat): ?>
            <div class="p-8 border-b border-gray-50 bg-[#fcfaf8] flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-black rounded-2xl flex items-center justify-center text-white font-black">
                        <?= mb_substr($messages[0]['first_name'] ?? 'C', 0, 1) ?>
                    </div>
                    <div>
                        <h3 class="font-black text-gray-800 uppercase text-sm italic">Клієнт #<?= $active_chat ?></h3>
                        <p class="text-[10px] text-[#d4a373] font-bold uppercase tracking-widest"><?= htmlspecialchars($messages[0]['email'] ?? 'Емейл приховано') ?></p>
                    </div>
                </div>
                <a href="?tab=support" class="text-gray-300 hover:text-black transition">✕</a>
            </div>

            <div class="flex-1 p-8 space-y-8 overflow-y-auto bg-gray-50/50">
                <?php foreach($messages as $m): ?>
                    <div class="space-y-4">
                        <div class="flex flex-col items-start max-w-[85%]">
                            <div class="bg-white p-6 rounded-[30px] rounded-tl-none border border-gray-100 shadow-sm relative">
                                <span class="absolute -top-3 left-6 bg-[#d4a373] text-white text-[8px] px-2 py-0.5 rounded-full font-black uppercase">Тема: <?= htmlspecialchars($m['subject']) ?></span>
                                <p class="text-sm text-gray-700 leading-relaxed italic">"<?= nl2br(htmlspecialchars($m['message'])) ?>"</p>
                                <div class="text-[8px] text-gray-300 font-bold mt-4 uppercase tracking-widest"><?= date('H:i | d.m.Y', strtotime($m['submitted_date'])) ?></div>
                            </div>
                        </div>

                        <div class="flex flex-col items-end w-full">
                            <?php if(!empty($m['admin_reply'])): ?>
                                <div class="bg-black text-white p-6 rounded-[30px] rounded-tr-none shadow-xl max-w-[85%]">
                                    <p class="text-sm"><?= nl2br(htmlspecialchars($m['admin_reply'])) ?></p>
                                    <div class="text-[8px] text-gray-500 mt-4 italic uppercase font-black tracking-widest">Відповідь PRIVÉ Support</div>
                                </div>
                            <?php else: ?>
                                <form method="POST" class="w-full max-w-[85%] bg-white p-6 rounded-[30px] border-2 border-dashed border-[#d4a373]/30">
                                    <input type="hidden" name="support_id" value="<?= $m['support_id'] ?>">
                                    <input type="hidden" name="customer_id" value="<?= $active_chat ?>">
                                    <textarea name="admin_reply" class="w-full p-4 text-xs bg-gray-50 rounded-2xl outline-none focus:bg-white transition mb-3" rows="3" placeholder="Ваша відповідь клієнту..." required></textarea>
                                    <div class="flex justify-between items-center">
                                        <select name="new_status" class="text-[9px] font-black uppercase border-none bg-transparent outline-none text-[#d4a373] cursor-pointer">
                                            <option value="pending">В обробці</option>
                                            <option value="resolved">Вирішено</option>
                                            <option value="closed">Закрити</option>
                                        </select>
                                        <button type="submit" name="send_reply" class="bg-black text-white px-6 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-[#d4a373] transition">Надіслати</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="flex-1 flex flex-col items-center justify-center p-20 text-center opacity-20">
                <div class="text-8xl mb-6">✉️</div>
                <h3 class="text-sm font-black uppercase tracking-[0.4em]">Оберіть діалог</h3>
                <p class="text-[10px] font-bold mt-2">натисніть на клієнта зліва для перегляду історії</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    
    const chatBody = document.querySelector('.overflow-y-auto');
    if(chatBody) chatBody.scrollTop = chatBody.scrollHeight;
</script>