<?php
require_once '../includes/config.php';
requireAuth('analyst');
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

auditLog('VIEW', 'Notifications', 'Viewed notifications');

pageStart('Notifications', 'analyst');
sidebar('analyst', 'notifications');
?>

<div class="pg-title">Notifications</div>
<div class="pg-sub"><?= count($notifications) ?> total notification(s)</div>

<div class="card">
    <?php if ($notifications): ?>
        <?php foreach ($notifications as $notif): ?>
            <div style="display:flex;gap:12px;padding:12px 0;border-bottom:1px solid var(--bd);align-items:flex-start">
                <span style="font-size:20px;flex-shrink:0">🔔</span>
                <div style="flex:1">
                    <div style="font-size:13px;color:var(--wh);margin-bottom:4px"><?= e($notif['message']) ?></div>
                    <div style="font-size:11px;color:var(--mu)">
                        <?= e(substr($notif['created_at'], 0, 16)) ?> — <?= timeAgo($notif['created_at']) ?>
                    </div>
                </div>
                <?php if ($notif['report_id']): ?>
                    <a href="<?= BASE_URL ?>/analyst/investigate.php?id=<?= (int) $notif['report_id'] ?>" class="btn btn-gy btn-sm">View Case</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align:center;padding:40px;color:var(--mu)">
            <div style="font-size:36px;margin-bottom:10px">🔕</div>
            <p>No notifications yet</p>
        </div>
    <?php endif; ?>
</div>

<?php pageEnd(); ?>