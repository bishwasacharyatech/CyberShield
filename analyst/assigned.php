<?php
require\_once '../includes/config.php';
require\_once '../includes/layout.php';

// Placeholder data for design preview — Bishwas wires real queries + filters today
$reports = \[];
$search = '';
$fstat = '';
$sort = 'newest';

pageStart('My Cases', 'analyst');
sidebar('analyst', 'assigned');
?>

<div class="pg-title">My Assigned Cases</div>
<div class="pg-sub"><?= count($reports) ?> case(s)</div>

<form method="GET" class="srchbar">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search ticket, title..." class="srch-i" style="flex:1;min-width:160px">
    <select name="status" class="srch-i">
        <option value="">All Status</option>
        <?php foreach (\['New', 'Assigned', 'Under Review', 'In Progress', 'Resolved', 'Closed'] as $status): ?>
            <option><?= $status ?></option>
        <?php endforeach; ?>
    </select>
    <select name="sort" class="srch-i">
        <option value="newest">Newest</option>
        <option value="oldest">Oldest</option>
        <option value="severity">Severity</option>
    </select>
    <button type="submit" class="btn btn-cy btn-sm">Filter</button>
    <a href="<?= BASE\_URL ?>/analyst/assigned.php" class="btn btn-gy btn-sm">Reset</a>
</form>

<div class="card">
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Ticket</th><th>Title</th><th>Category</th><th>Severity</th>
                    <th>Status</th><th>Reporter</th><th>Date</th><th></th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--mu)">No cases found</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php pageEnd(); ?>