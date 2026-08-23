<?php
require_once '../includes/config.php';
requireAuth('user');
require_once '../includes/layout.php';

auditLog('VIEW', 'Dashboard', 'Viewed user dashboard');

$db = getDB();
$uid = $_SESSION['uid'];

// Real statistics
$total = $db->prepare("SELECT COUNT(*) AS c FROM reports WHERE user_id = ?");
$total->bind_param('i', $uid);
$total->execute();
$total = $total->get_result()->fetch_assoc()['c'];

$pending = $db->prepare("SELECT COUNT(*) AS c FROM reports WHERE user_id = ? AND status IN ('New', 'Assigned')");
$pending->bind_param('i', $uid);
$pending->execute();
$pending = $pending->get_result()->fetch_assoc()['c'];

$inProgress = $db->prepare("SELECT COUNT(*) AS c FROM reports WHERE user_id = ? AND status IN ('Under Review', 'In Progress')");
$inProgress->bind_param('i', $uid);
$inProgress->execute();
$inProgress = $inProgress->get_result()->fetch_assoc()['c'];

$resolved = $db->prepare("SELECT COUNT(*) AS c FROM reports WHERE user_id = ? AND status IN ('Resolved', 'Closed')");
$resolved->bind_param('i', $uid);
$resolved->execute();
$resolved = $resolved->get_result()->fetch_assoc()['c'];

// Recent reports with category and analyst name

$reports = $db->prepare("
    SELECT r.*, c.name AS cat_name, u.full_name AS aname
    FROM reports r
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN users u ON r.assigned_to = u.id
    WHERE r.user_id = ?
    ORDER BY r.id DESC
    LIMIT 8
");
$reports->bind_param('i', $uid);
$reports->execute();
$reports = $reports->get_result()->fetch_all(MYSQLI_ASSOC);

$notifications = $db->prepare("
    SELECT * FROM notifications
    WHERE user_id = ? AND is_read = 0
    ORDER BY id DESC
    LIMIT 5
");
$notifications->bind_param('i', $uid);
$notifications->execute();
$notifications = $notifications->get_result()->fetch_all(MYSQLI_ASSOC);

pageStart('Dashboard', 'user');
sidebar('user', 'dashboard');
?>

<div class="pg-title">Welcome, <?= e($_SESSION['fname']) ?>!</div>
<div class="pg-sub">Cybersecurity Incident Management System</div>

<div class="grid g4">
    <div class="metric m-cy">
        <div class="m-lbl">Total Reports</div>
        <div class="m-val"><?= (int) $total ?></div>
    </div>
    <div class="metric m-am">
        <div class="m-lbl">Pending</div>
        <div class="m-val"><?= (int) $pending ?></div>
    </div>
    <div class="metric m-pu">
        <div class="m-lbl">In Progress</div>
        <div class="m-val"><?= (int) $inProgress ?></div>
    </div>
    <div class="metric m-gr">
        <div class="m-lbl">Resolved</div>
        <div class="m-val"><?= (int) $resolved ?></div>
    </div>
</div>

<?php if ($notifications): ?>
    <div class="card">
        <div class="ch">
            <span class="ct">🔔 New Notifications</span>
            <a href="<?= BASE_URL ?>/user/notifications.php" class="btn btn-gy btn-sm">View All →</a>
        </div>
        <?php foreach ($notifications as $notif): ?>
            <div style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid var(--bd);align-items:center">
                <span style="color:var(--cy);font-size:16px">🔔</span>
                <div style="flex:1">
                    <div style="font-size:13px;color:var(--wh)"><?= e($notif['message']) ?></div>
                    <div style="font-size:11px;color:var(--mu)"><?= timeAgo($notif['created_at']) ?></div>
                </div>
                <?php if ($notif['report_id']): ?>
                    <a href="<?= BASE_URL ?>/user/view-report.php?id=<?= (int) $notif['report_id'] ?>" class="btn btn-gy btn-sm">View</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="ch">
        <span class="ct">📄 My Recent Reports</span>
        <a href="<?= BASE_URL ?>/user/report.php" class="btn btn-cy btn-sm">➕ New Report</a>
    </div>
    <?php if ($reports): ?>
        <div class="tw">
            <table>
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Analyst</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td style="font-family:monospace;color:var(--cy);font-size:11px"><?= e($report['ticket_no']) ?></td>
                            <td style="color:var(--wh)"><?= e(substr($report['title'], 0, 35)) ?></td>
                            <td style="font-size:12px"><?= e($report['cat_name'] ?? '—') ?></td>
                            <td><?= sevBadge($report['severity']) ?></td>
                            <td><?= statusBadge($report['status']) ?></td>
                            <td style="font-size:12px;color:var(--mu)"><?= e($report['aname'] ?? 'Unassigned') ?></td>
                            <td style="font-size:12px;color:var(--mu)"><?= e(substr($report['created_at'], 0, 10)) ?></td>
                           <td><a href="<?= BASE_URL ?>/user/view-report.php?id=<?= (int) $report['id'] ?>" class="btn btn-gy btn-sm">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align:center;padding:40px;color:var(--mu)">
            <div style="font-size:40px;margin-bottom:10px">📋</div>
            <p>No reports yet. <a href="<?= BASE_URL ?>/user/report.php">Submit your first complaint →</a></p>
        </div>
    <?php endif; ?>
</div>

<?php pageEnd(); ?>