<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($pdo)) {
    $host = "localhost"; $db = "beautystore"; $user = "beautyuser"; $pass = "1234";
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) { die("Помилка БД"); }
}


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    die("Доступ заборонено"); 
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $sid = intval($_POST['support_id']);
    $reply = trim($_POST['admin_reply']);
    $status = $_POST['new_status'] ?? 'pending';
    $staff_id = intval($_SESSION['user_id'] ?? 0);

   
    $st = $pdo->prepare("UPDATE Support SET reply_text = ?, status = ?, staff_id = ? WHERE support_id = ?");
    $st->execute([$reply, $status, $staff_id, $sid]);
    
    $redirect_id = !empty($_POST['user_id']) ? intval($_POST['user_id']) : '0&guest_id='.$sid;
    header("Location: admin_prive.php?tab=support&chat_with=" . $redirect_id . "&msg=Відповідь надіслано");
    exit();
}

$chat_query = $pdo->query("
    SELECT 
        s.user_id, 
        MAX(s.support_id) as support_id, 
        u.first_name, 
        u.email, 
        COUNT(*) as total_msgs,
        MAX(s.submitted_date) as last_date,
        SUM(CASE WHEN s.status = 'new' THEN 1 ELSE 0 END) as new_count
    FROM Support s
    LEFT JOIN users u ON s.user_id = u.user_id
    GROUP BY 
        s.user_id, 
        u.first_name, 
        u.email, 
        (CASE WHEN s.user_id IS NULL THEN s.support_id ELSE 0 END)
    ORDER BY new_count DESC, last_date DESC
");
$chats = $chat_query->fetchAll(PDO::FETCH_ASSOC);

$active_chat = isset($_GET['chat_with']) ? (int)$_GET['chat_with'] : null;
$guest_id = isset($_GET['guest_id']) ? (int)$_GET['guest_id'] : null;
$messages = [];


if ($active_chat || $guest_id) {
    if ($active_chat > 0) {
        $msg_st = $pdo->prepare("
            SELECT s.*, u.first_name, u.email 
            FROM Support s 
            LEFT JOIN users u ON s.user_id = u.user_id 
            WHERE s.user_id = ? 
            ORDER BY s.submitted_date ASC
        ");
        $msg_st->execute([$active_chat]);
    } else {
        $msg_st = $pdo->prepare("
            SELECT *, 'Гість' as first_name, 'Неавторизований користувач' as email 
            FROM Support 
            WHERE support_id = ?
        ");
        $msg_st->execute([$guest_id]);
    }
    $messages = $msg_st->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="flex flex-col lg:flex-row gap-8 min-h-[700px]">
    
    <div class="w-full lg:w-1/3 space-y-4">
        <div class="px-4 flex justify-between items-center">
            <h2 class="text-2xl font-black italic uppercase text-gray-800 tracking-tighter">Діалоги підтримки</h2>
            <span class="bg-black text-white text-[10px] px-3 py-1 rounded-full font-bold uppercase"><?= count($chats) ?></span>
        </div>
        
        <div class="bg-white rounded-[40px] border border-[#f0e6e0] overflow-hidden shadow-sm h-[600px] overflow-y-auto">
            <?php if(empty($chats)): ?>
                <div class="p-20 text-center text-gray-300 text-[10px] font-bold uppercase tracking-widest">Немає звернень</div>
            <?php endif; ?>

            <?php foreach($chats as $chat): ?>
                <?php 
                    $link = $chat['user_id'] ? "chat_with=".$chat['user_id'] : "chat_with=0&guest_id=".$chat['support_id'];
                    $is_active = ($active_chat && $active_chat == $chat['user_id']) || ($guest_id && $guest_id == $chat['support_id']);

                    $has_new = $chat['new_count'] > 0;
                ?>
                <a href="?tab=support&<?= $link ?>" 
                   class="flex items-center justify-between p-6 border-b border-gray-50 transition <?= $is_active ? 'bg-[#fdfaf9] border-r-8 border-[#d4a373]' : 'hover:bg-[#fcfaf8]' ?> <?= $has_new ? 'bg-[#fdfbf9]/60' : '' ?>">
                    <div class="flex flex-col min-w-0 flex-1">
                        <span class="text-sm text-gray-800 uppercase tracking-tighter <?= $has_new ? 'font-extrabold text-black' : 'font-bold' ?> truncate">
                            <?= $chat['first_name'] ? htmlspecialchars($chat['first_name']) : 'Гість #' . $chat['support_id'] ?>
                        </span>
                        <span class="text-[9px] text-gray-400 font-bold mt-0.5"><?= $chat['total_msgs'] ?> повідомлень</span>
                    </div>
                    
                    <?php if($has_new): ?>
                        <div class="w-6 h-6 bg-[#d4a373] text-white flex items-center justify-center rounded-full text-[10px] font-black animate-pulse shadow-sm ml-3 flex-shrink-0">
                            <?= $chat['new_count'] ?>
                        </div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="flex-1 flex flex-col bg-white rounded-[50px] border border-[#f0e6e0] shadow-sm overflow-hidden h-[700px]">
        <?php if(!empty($messages)): ?>
            <div class="p-8 border-b border-gray-50 bg-[#fcfaf8] flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-black rounded-2xl flex items-center justify-center text-white font-black uppercase border border-gray-900 shadow-md">
                        <?= mb_substr($messages[0]['first_name'] ?? 'G', 0, 1, 'UTF-8') ?>
                    </div>
                    <div>
                        <h3 class="font-black text-gray-800 uppercase text-sm italic">
                            <?= $active_chat > 0 ? htmlspecialchars($messages[0]['first_name']) . ' (Клієнт #' . $active_chat . ')' : 'Гість #' . $guest_id ?>
                        </h3>
                        <p class="text-[10px] text-[#d4a373] font-bold uppercase tracking-widest">
                            <?= htmlspecialchars($messages[0]['email']) ?>
                        </p>
                    </div>
                </div>
                <a href="?tab=support" class="text-gray-300 hover:text-black transition text-lg">✕</a>
            </div>

            <div class="flex-1 p-8 space-y-8 overflow-y-auto bg-gray-50/50">
                <?php foreach($messages as $m): 

                    $needs_reply = empty($m['reply_text']);
                ?>
                    <div class="space-y-4 mb-6">
                        <div class="flex flex-col items-start max-w-[85%]">
                            <div class="bg-white p-6 rounded-[30px] rounded-tl-none border shadow-sm relative w-full <?= $needs_reply ? 'border-[#d4a373] border-2 ring-4 ring-[#d4a373]/5' : 'border-gray-100' ?>">
                                <span class="absolute -top-3 left-6 bg-[#d4a373] text-white text-[8px] px-2 py-0.5 rounded-full font-black uppercase shadow-sm">Тема: <?= htmlspecialchars($m['subject']) ?></span>
                                <p class="text-sm text-gray-700 leading-relaxed italic">"<?= nl2br(htmlspecialchars($m['message'])) ?>"</p>
                                <div class="text-[8px] text-gray-300 font-bold mt-4 uppercase tracking-widest text-right"><?= date('H:i | d.m.Y', strtotime($m['submitted_date'])) ?></div>
                            </div>
                        </div>

                        <div class="flex flex-col items-end w-full">
                            <?php if(!$needs_reply): 
                                $staff_name = 'PRIVÉ Support';
                                if (!empty($m['staff_id'])) {
                                    $st_check = $pdo->prepare("SELECT first_name FROM users WHERE user_id = ?");
                                    $st_check->execute([$m['staff_id']]);
                                    $staff_name = $st_check->fetchColumn() ?: 'PRIVÉ Support';
                                }
                            ?>
                                <div class="bg-black text-white p-6 rounded-[30px] rounded-tr-none shadow-xl max-w-[85%] w-full">
                                    <p class="text-sm"><?= nl2br(htmlspecialchars($m['reply_text'])) ?></p>
                                    <div class="text-[8px] text-[#d4a373] mt-4 italic uppercase font-black tracking-widest text-right">Відповів менеджер: <?= htmlspecialchars($staff_name) ?> ✦</div>
                                </div>
                            <?php else: ?>
                                <form method="POST" class="w-full max-w-[85%] bg-white p-6 rounded-[30px] border-2 border-dashed border-[#d4a373]/30 shadow-sm">
                                    <input type="hidden" name="support_id" value="<?= $m['support_id'] ?>">
                                    <input type="hidden" name="user_id" value="<?= $m['user_id'] ?>">
                                    <textarea name="admin_reply" class="w-full p-4 text-xs bg-gray-50 rounded-2xl outline-none border border-transparent focus:border-[#d4a373] focus:bg-white transition mb-3" rows="3" placeholder="Ваша професійна відповідь клієнту..." required></textarea>
                                    <div class="flex justify-between items-center">
                                        <select name="new_status" class="text-[9px] font-black uppercase border-none bg-transparent outline-none text-[#d4a373] cursor-pointer font-sans">
                                            <option value="pending" <?= $m['status'] == 'new' ? 'selected' : '' ?>>В обробці</option>
                                            <option value="resolved">Вирішено</option>
                                            <option value="closed">Закрити</option>
                                        </select>
                                        <button type="submit" name="send_reply" class="bg-black text-white px-6 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-[#d4a373] transition shadow-md">Надіслати</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="flex-1 flex flex-col items-center justify-center p-20 text-center opacity-30 bg-white rounded-[50px]">
                <div class="text-6xl mb-6">📩</div>
                <h3 class="text-sm font-black uppercase tracking-[0.4em] text-gray-700">Оберіть діалог</h3>
                <p class="text-[10px] font-bold mt-2 text-gray-400 uppercase tracking-widest">натисніть на клієнта або гостя на лівій панелі</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const chatBody = document.querySelector('.overflow-y-auto');
    if(chatBody) chatBody.scrollTop = chatBody.scrollHeight;
</script>
