<?php
require\_once '../includes/config.php';
require\_once '../includes/layout.php';

// Placeholder data for design preview — Bishwas wires real queries today
$totalAssigned = 0;
$openCases = 0;
$resolved = 0;
$criticalHigh = 0;
$myCases = \[];
$recentActivity = \[];

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
            <span class="ct"><i class="ti ti-clipboard-check"></i> My Assigned Cases</span>
            <a href="<?= BASE\_URL ?>/analyst/assigned.php" class="btn btn-gy btn-sm">All →</a>
        </div>
        <div style="padding:30px;text-align:center;color:var(--mu)">
            No cases assigned yet. New assignments will appear here and in your notifications.
        </div>
    </div>

    <div class="card">
        <div class="ch">
            <span class="ct"><i class="ti ti-history"></i> Recent Activity on My Cases</span>
        </div>
        <div style="color:var(--mu);font-size:12px;padding:10px">No activity yet</div>
    </div>
</div>

<?php pageEnd(); ?>