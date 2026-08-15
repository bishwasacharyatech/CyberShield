<?php
require_once '../includes/config.php';
requireAuth('user');
require_once '../includes/layout.php';

auditLog('VIEW', 'Dashboard', 'Viewed user dashboard');

$total = 0;
$pending = 0;
$inProgress = 0;
$resolved = 0;
$reports = [];

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

<div class="card">
    <div class="ch">
        <span class="ct"><i class="ti ti-file-text"></i> My Recent Reports</span>
        <a href="<?= BASE_URL ?>/user/report.php" class="btn btn-cy btn-sm"><i class="ti ti-plus"></i> New Report</a>
    </div>
    <?php if ($reports): ?>
        <div class="tw">
            <table>
                <thead>
                    <tr>
                        <th>Ticket</th><th>Title</th><th>Category</th><th>Severity</th>
                        <th>Status</th><th>Analyst</th><th>Date</th><th></th>
                    </tr>
                </thead>
                <tbody></tbody>
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