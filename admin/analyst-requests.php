<?php
require\_once '../includes/config.php';
require\_once '../includes/layout.php';

// Placeholder data for design preview — Bishwas wires the real query + approve/reject logic today
$requests = \[
    \['id' => 1, 'user\_id' => 2, 'full\_name' => 'Sample User', 'email' => 'sample@example.com', 'reason' => 'I want to help investigate incidents.', 'skills' => 'Networking, Linux', 'status' => 'pending', 'created\_at' => date('Y-m-d H:i:s')],
];
$fstat = '';
$msg = '';

pageStart('Analyst Requests', 'admin');
sidebar('admin', 'analyst-requests');
?>

<div class="pg-title">Analyst Requests</div>
<div class="pg-sub"><?= count($requests) ?> request(s) found</div>

<?php if ($msg): ?>
    <div class="flash-ok">✅ <?= e($msg) ?></div>
<?php endif; ?>

<form method="GET" class="srchbar">
    <select name="status" class="srch-i">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
    </select>
    <button type="submit" class="btn btn-cy btn-sm">Filter</button>
    <a href="<?= BASE\_URL ?>/admin/analyst-requests.php" class="btn btn-gy btn-sm">Reset</a>
</form>

<div class="card">
    <?php foreach ($requests as $req): ?>
        <div style="padding:14px 0;border-bottom:1px solid var(--bd)">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                <span style="font-weight:600;color:var(--wh)"><?= e($req\['full\_name']) ?></span>
                <span style="font-size:12px;color:var(--mu)"><?= e($req\['email']) ?></span>
                <?= statusBadge($req\['status']) ?>
                <span style="margin-left:auto;font-size:11px;color:var(--mu)"><?= e(substr($req\['created\_at'], 0, 16)) ?></span>
            </div>
            <div style="font-size:13px;color:var(--tx);margin-bottom:6px"><strong style="color:var(--mu);font-size:11px;text-transform:uppercase">Reason:</strong> <?= e($req\['reason']) ?></div>
            <?php if ($req\['skills']): ?>
                <div style="font-size:13px;color:var(--tx);margin-bottom:10px"><strong style="color:var(--mu);font-size:11px;text-transform:uppercase">Skills:</strong> <?= e($req\['skills']) ?></div>
            <?php endif; ?>
            <?php if ($req\['status'] === 'pending'): ?>
                <div style="display:flex;gap:8px">
                    <form method="POST"><input type="hidden" name="req\_id" value="<?= $req\['id'] ?>"><input type="hidden" name="decision" value="approve"><button class="btn btn-gr btn-sm">✓ Approve</button></form>
                    <form method="POST"><input type="hidden" name="req\_id" value="<?= $req\['id'] ?>"><input type="hidden" name="decision" value="reject"><button class="btn btn-re btn-sm">✗ Reject</button></form>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if (!$requests): ?>
        <div style="text-align:center;padding:40px;color:var(--mu)">No analyst requests found</div>
    <?php endif; ?>
</div>

<?php pageEnd(); ?>