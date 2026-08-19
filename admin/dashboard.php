<?php
require\_once '../includes/config.php';
require\_once '../includes/layout.php';

// Placeholder data for design preview — Bishwas wires real queries today
$stats = \[
    'total\_reports' => 0,
    'new\_reports' => 0,
    'total\_users' => 0,
    'total\_analysts' => 0,
    'audit\_today' => 0,
];
$recentReports = \[];

pageStart('Dashboard', 'admin');
sidebar('admin', 'dashboard');
?>

<div class="pg-title">Admin Dashboard</div>
<div class="pg-sub">Cybersecurity Incident Management System overview.</div>

<div class="grid g4">
    <div class="metric m-cy">
        <div class="m-lbl">Total Reports</div>
        <div class="m-val"><?= (int) $stats\['total\_reports'] ?></div>
        <div class="m-sub"><?= (int) $stats\['new\_reports'] ?> new</div>
    </div>
    <div class="metric m-am">
        <div class="m-lbl">Users</div>
        <div class="m-val"><?= (int) $stats\['total\_users'] ?></div>
        <div class="m-sub">Analysts: <?= (int) $stats\['total\_analysts'] ?></div>
    </div>
    <div class="metric m-pu">
        <div class="m-lbl">Audit Today</div>
        <div class="m-val"><?= (int) $stats\['audit\_today'] ?></div>
    </div>
    <div class="metric m-gr">
        <div class="m-lbl">System Status</div>
        <div class="m-val" style="font-size:16px">● LIVE</div>
    </div>
</div>

<div class="card">
    <div class="ch">
        <span class="ct"><i class="ti ti-file-text"></i> Recent Reports</span>
        <a href="<?= BASE\_URL ?>/admin/reports.php" class="btn btn-gy btn-sm">All →</a>
    </div>
    <div style="text-align:center;padding:20px;color:var(--mu)">No reports yet</div>
</div>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:16px">
    <?php foreach (\[
        \['📋', 'Reports', 'admin/reports.php'],
        \['👥', 'Users', 'admin/users.php'],
        \['📂', 'Categories', 'admin/categories.php'],
        \['📜', 'Audit', 'admin/audit.php']
    ] as \[$icon, $label, $link]): ?>
        <a href="<?= BASE\_URL ?>/<?= $link ?>" style="text-decoration:none">
            <div class="card" style="text-align:center;cursor:pointer">
                <div style="font-size:28px;margin-bottom:8px"><?= $icon ?></div>
                <div style="font-weight:700;color:var(--wh)"><?= $label ?></div>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<?php pageEnd(); ?>