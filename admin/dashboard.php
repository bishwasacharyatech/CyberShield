<?php
require_once '../includes/config.php';
requireAuth('admin');
require_once '../includes/layout.php';

$db = getDB();

$stats = $db->query("
    SELECT
        (SELECT COUNT(*) FROM reports) AS total_reports,
        (SELECT COUNT(*) FROM reports WHERE status = 'New') AS new_reports,
        (SELECT COUNT(*) FROM users WHERE role = 'user') AS total_users,
        (SELECT COUNT(*) FROM users WHERE role = 'analyst') AS total_analysts,
        (SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = CURDATE()) AS audit_today
")->fetch_assoc();

$recentReports = $db->query("
    SELECT r.*, c.name AS cat_name, u.full_name AS uname
    FROM reports r
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN users u ON r.user_id = u.id
    ORDER BY r.id DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

pageStart('Dashboard', 'admin');
sidebar('admin', 'dashboard');
?>

<div class="pg-title">Admin Dashboard</div>
<div class="pg-sub">Cybersecurity Incident Management System overview.</div>

<div class="grid g4">
    <div class="metric m-cy">
        <div class="m-lbl">Total Reports</div>
        <div class="m-val"><?= (int) $stats['total_reports'] ?></div>
        <div class="m-sub"><?= (int) $stats['new_reports'] ?> new</div>
    </div>
    <div class="metric m-am">
        <div class="m-lbl">Users</div>
        <div class="m-val"><?= (int) $stats['total_users'] ?></div>
        <div class="m-sub">Analysts: <?= (int) $stats['total_analysts'] ?></div>
    </div>
    <div class="metric m-pu">
        <div class="m-lbl">Audit Today</div>
        <div class="m-val"><?= (int) $stats['audit_today'] ?></div>
    </div>
    <div class="metric m-gr">
        <div class="m-lbl">System Status</div>
        <div class="m-val" style="font-size:16px">● LIVE</div>
    </div>
</div>

<div class="card">
    <div class="ch">
        <span class="ct"><i class="ti ti-file-text"></i> Recent Reports</span>
        <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-gy btn-sm">All →</a>
    </div>
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Ticket</th><th>Title</th><th>Severity</th><th>Status</th><th>User</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentReports as $report): ?>
                    <tr>
                        <td style="font-family:monospace;color:var(--cy);font-size:11px"><?= e($report['ticket_no']) ?></td>
                        <td style="color:var(--wh)"><?= e(substr($report['title'], 0, 28)) ?></td>
                        <td><?= sevBadge($report['severity']) ?></td>
                        <td><?= statusBadge($report['status']) ?></td>
                        <td style="font-size:12px;color:var(--mu)"><?= e($report['uname']) ?></td>
                        <td><a href="<?= BASE_URL ?>/admin/assign.php?id=<?= (int) $report['id'] ?>" class="btn btn-gy btn-sm">Manage</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recentReports): ?>
                    <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--mu)">No reports yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:16px">
    <?php foreach ([
        ['📋', 'Reports', 'admin/reports.php'],
        ['👥', 'Users', 'admin/users.php'],
        ['📂', 'Categories', 'admin/categories.php'],
        ['📜', 'Audit', 'admin/audit.php']
    ] as [$icon, $label, $link]): ?>
        <a href="<?= BASE_URL ?>/<?= $link ?>" style="text-decoration:none">
            <div class="card" style="text-align:center;cursor:pointer">
                <div style="font-size:28px;margin-bottom:8px"><?= $icon ?></div>
                <div style="font-weight:700;color:var(--wh)"><?= $label ?></div>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<?php pageEnd(); ?>