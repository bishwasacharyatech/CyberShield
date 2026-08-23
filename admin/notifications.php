<?php
require_once '../includes/config.php';
requireAuth('admin');
require_once '../includes/layout.php';

$db = getDB();
$uid = $_SESSION['uid'];

$markRead = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$markRead->bind_param('i', $uid);
$markRead->execute();

$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 50");
$stmt->bind_param('i', $uid);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

pageStart('Notifications', 'admin');
sidebar('admin', 'notifications');
?>

<div class="pg-title">Notifications</div>

<div class="card">
    <?php if ($notifications): ?>
        <?php foreach ($notifications as $notif): ?>
            <div style="display:flex;gap:12px;padding:12px 0;border-bottom:1px solid var(--bd)">
                <span style="font-size:20px">🔔</span>
                <div style="flex:1">
                    <div style="color:var(--wh)"><?= e($notif['message']) ?></div>
                    <div style="font-size:11px;color:var(--mu)"><?= timeAgo($notif['created_at']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="padding:30px;text-align:center;color:var(--mu)">No notifications</div>
    <?php endif; ?>
</div>

<?php pageEnd(); ?>