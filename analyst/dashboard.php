<?php
require_once '../includes/config.php';
requireAuth('analyst');
require_once '../includes/layout.php';

$db = getDB();
$uid = $_SESSION['uid'];

$totalAssigned = $db->prepare("SELECT COUNT(*) AS c FROM reports WHERE assigned_to = ?");
$totalAssigned->bind_param('i', $uid);
$totalAssigned->execute();
$totalAssigned = $totalAssigned->get_result()->fetch_assoc()['c'];

$openCases = $db->prepare("SELECT COUNT(*) AS c FROM reports WHERE assigned_to = ? AND status NOT IN ('Resolved', 'Closed')");
$openCases->bind_param('i', $uid);
$openCases->execute();
$openCases = $openCases->get_result()->fetch_assoc()['c'];

$resolved = $db->prepare("SELECT COUNT(*) AS c FROM reports WHERE assigned_to = ? AND status IN ('Resolved', 'Closed')");
$resolved->bind_param('i', $uid);
$resolved->execute();
$resolved = $resolved->get_result()->fetch_assoc()['c'];

$criticalHigh = $db->prepare("SELECT COUNT(*) AS c FROM reports WHERE assigned_to = ? AND severity IN ('Critical', 'High') AND status NOT IN ('Resolved', 'Closed')");
$criticalHigh->bind_param('i', $uid);
$criticalHigh->execute();
$criticalHigh = $criticalHigh->get_result()->fetch_assoc()['c'];

$myCases = $db->prepare("
    SELECT r.*, c.name AS cat_name, u.full_name AS uname
    FROM reports r
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.assigned_to = ?
    ORDER BY FIELD(r.severity, 'Critical', 'High', 'Medium', 'Low'), r.id DESC
    LIMIT 8
");
$myCases->bind_param('i', $uid);
$myCases->execute();
$myCases = $myCases->get_result()->fetch_all(MYSQLI_ASSOC);

$recentActivity = $db->prepare("
    SELECT t.*, r.ticket_no
    FROM report_timeline t
    JOIN reports r ON t.report_id = r.id
    WHERE r.assigned_to = ?
    ORDER BY t.id DESC
    LIMIT 6
");
$recentActivity->bind_param('i', $uid);
$recentActivity->execute();
$recentActivity = $recentActivity->get_result()->fetch_all(MYSQLI_ASSOC);

pageStart('Dashboard', 'analyst');
sidebar('analyst', 'dashboard');
?>

<div class="pg-title">SOC Analyst Dashboard</div>
<div class="pg-sub">Cases assigned to you. You only see reports specifically assigned to your account.</div>

<div class="grid g4">
    <div class="metric m-pu">
        <div class="m-lbl">Assigned to Me</div>
        <div class="m-val"><?= (int) $totalAssigned ?></div>
    </div>
    <div class="metric m-am">
        <div class="m-lbl">Open Cases</div>
        <div class="m-val"><?= (int) $openCases ?></div>
    </div>
    <div class="metric m-re">
        <div class="m-lbl">High Priority Open</div>
        <div class="m-val"><?= (int) $criticalHigh ?></div>
    </div>
    <div class="metric m-gr">
        <div class="m-lbl">Resolved</div>
        <div class="m-val"><?= (int) $resolved ?></div>
    </div>
</div>

<div class="gr-23">
    <div class="card">
        <div class="ch">
            <span class="ct">📋 My Assigned Cases</span>
            <a href="<?= BASE_URL ?>/analyst/assigned.php" class="btn btn-gy btn-sm">All →</a>
        </div>
        <?php if ($myCases): ?>
            <div class="tw">
                <table>
                    <thead>
                        <tr>
                            <th>Ticket</th><th>Title</th><th>Category</th><th>Severity</th>
                            <th>Status</th><th>Reporter</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myCases as $case): ?>
                            <tr>
                                <td style="font-family:monospace;color:var(--cy);font-size:11px"><?= e($case['ticket_no']) ?></td>
                                <td style="color:var(--wh)"><?= e(substr($case['title'], 0, 30)) ?></td>
                                <td style="font-size:12px"><?= e($case['cat_name'] ?? '—') ?></td>
                                <td><?= sevBadge($case['severity']) ?></td>
                                <td><?= statusBadge($case['status']) ?></td>
                                <td style="font-size:12px;color:var(--mu)"><?= e($case['uname']) ?></td>
                                <td><a href="<?= BASE_URL ?>/analyst/investigate.php?id=<?= (int) $case['id'] ?>" class="btn btn-gy btn-sm">Investigate</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="padding:30px;text-align:center;color:var(--mu)">
                No cases assigned yet. New assignments will appear here and in your notifications.
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="ch">
            <span class="ct">📜 Recent Activity on My Cases</span>
        </div>
        <?php if ($recentActivity): ?>
            <?php foreach ($recentActivity as $activity): ?>
                <div style="display:flex;gap:8px;padding:8px 0;border-bottom:1px solid var(--bd)">
                    <div style="flex:1">
                        <div style="font-size:12px;color:var(--wh)">
                            <span style="color:var(--cy);font-family:monospace;font-size:11px"><?= e($activity['ticket_no']) ?></span>
                            — <?= e($activity['action']) ?>
                        </div>
                        <?php if ($activity['note']): ?>
                            <div style="font-size:11px;color:var(--mu)"><?= e(substr($activity['note'], 0, 60)) ?></div>
                        <?php endif; ?>
                    </div>
                    <span style="font-size:10px;color:var(--mu);white-space:nowrap"><?= timeAgo($activity['created_at']) ?></span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="color:var(--mu);font-size:12px;padding:10px">No activity yet</div>
        <?php endif; ?>
    </div>
</div>

<?php pageEnd(); ?>