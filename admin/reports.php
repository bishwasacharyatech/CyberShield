<?php
require\_once '../includes/config.php';
require\_once '../includes/layout.php';

// Placeholder data for design preview — Bishwas wires real queries + filters today
$reports = \[];
$categories = \[];
$search = '';
$fsev = '';
$fstat = '';
$fcat = '';
$fasg = '';
$sort = 'newest';

pageStart('All Reports', 'admin');
sidebar('admin', 'reports');
?>

<div class="pg-title">All Reports</div>
<div class="pg-sub"><?= count($reports) ?> report(s) found</div>

<form method="GET" class="srchbar">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search ticket, title, user, analyst..." class="srch-i" style="flex:1;min-width:180px">
    <select name="category" class="srch-i">
        <option value="">All Categories</option>
    </select>
    <select name="severity" class="srch-i">
        <option value="">All Severity</option>
        <?php foreach (\['Critical', 'High', 'Medium', 'Low'] as $sev): ?>
            <option><?= $sev ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status" class="srch-i">
        <option value="">All Status</option>
        <?php foreach (\['New', 'Assigned', 'Under Review', 'In Progress', 'Resolved', 'Closed'] as $stat): ?>
            <option><?= $stat ?></option>
        <?php endforeach; ?>
    </select>
    <select name="assigned" class="srch-i">
        <option value="">All</option>
        <option value="assigned">Assigned</option>
        <option value="unassigned">Unassigned</option>
    </select>
    <select name="sort" class="srch-i">
        <option value="newest">Newest</option>
        <option value="oldest">Oldest</option>
        <option value="severity">Severity</option>
    </select>
    <button type="submit" class="btn btn-cy btn-sm">Filter</button>
    <a href="<?= BASE\_URL ?>/admin/reports.php" class="btn btn-gy btn-sm">Reset</a>
</form>

<div class="card">
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Ticket</th><th>Title</th><th>Category</th><th>Severity</th>
                    <th>Status</th><th>User</th><th>Analyst</th><th>Date</th><th></th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--mu)">No reports found</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php pageEnd(); ?>