<?php
require\_once '../includes/config.php';
require\_once '../includes/layout.php';

// Placeholder data for design preview — Bishwas wires real queries + filters today
$reports = \[];
$search = '';
$fsev = '';
$fstat = '';
$sort = 'newest';

pageStart('My Reports', 'user');
sidebar('user', 'my-reports');
?>

<div class="pg-title">My Reports</div>
<div class="pg-sub"><?= count($reports) ?> report(s) found</div>

<form method="GET" class="srchbar">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search title, ticket, category..." class="srch-i" style="flex:1;min-width:160px">
    <select name="severity" class="srch-i">
        <option value="">All Severity</option>
        <?php foreach (\['Critical', 'High', 'Medium', 'Low'] as $sev): ?>
            <option value="<?= $sev ?>"><?= $sev ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status" class="srch-i">
        <option value="">All Status</option>
        <?php foreach (\['New', 'Assigned', 'Under Review', 'In Progress', 'Resolved', 'Closed'] as $stat): ?>
            <option value="<?= $stat ?>"><?= $stat ?></option>
        <?php endforeach; ?>
    </select>
    <select name="sort" class="srch-i">
        <option value="newest">Newest First</option>
        <option value="oldest">Oldest First</option>
        <option value="severity">By Severity</option>
    </select>
    <button type="submit" class="btn btn-cy btn-sm">Filter</button>
    <a href="<?= BASE\_URL ?>/user/my-reports.php" class="btn btn-gy btn-sm">Reset</a>
</form>

<div class="card">
    <div class="ch">
        <span class="ct"><i class="ti ti-file-text"></i> Reports</span>
        <a href="<?= BASE\_URL ?>/user/report.php" class="btn btn-cy btn-sm"><i class="ti ti-plus"></i> New</a>
    </div>
    <div style="text-align:center;padding:40px;color:var(--mu)">
        <div style="font-size:36px;margin-bottom:10px">📭</div>
        <p>No reports found. <a href="<?= BASE\_URL ?>/user/report.php">Submit one →</a></p>
    </div>
</div>

<?php pageEnd(); ?>