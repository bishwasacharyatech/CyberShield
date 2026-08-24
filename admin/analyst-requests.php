<?php
require_once '../includes/config.php';
requireAuth('admin');
require_once '../includes/layout.php';

$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['req_id'], $_POST['decision'])) {
    $reqId = (int) $_POST['req_id'];
    $decision = $_POST['decision'];
    $adminId = $_SESSION['uid'];

    $stmt = $db->prepare("SELECT * FROM analyst_requests WHERE id = ? AND status = 'pending'");
    $stmt->bind_param('i', $reqId);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();

    if ($request && in_array($decision, ['approve', 'reject'])) {
        $newStatus = $decision === 'approve' ? 'approved' : 'rejected';

        $upd = $db->prepare('UPDATE analyst_requests SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?');
        $upd->bind_param('sii', $newStatus, $adminId, $reqId);
        $upd->execute();

        if ($decision === 'approve') {
    $roleUpd = $db->prepare("UPDATE users SET role = 'analyst' WHERE id = ?");
    $roleUpd->bind_param('i', $request['user_id']);
    $roleUpd->execute();
    addNotif($request['user_id'], null, 'Your analyst application was approved! You now have analyst access.');
    auditLog('APPROVE', 'Analyst Request', "Approved analyst request #{$reqId} for user ID {$request['user_id']}");
} else {
    addNotif($request['user_id'], null, 'Your analyst application was not approved.');
    auditLog('REJECT', 'Analyst Request', "Rejected analyst request #{$reqId} for user ID {$request['user_id']}");
}

        $msg = 'Request ' . $newStatus . '.';
    }
}

$fstat = $_GET['status'] ?? '';
$validStatuses = ['pending', 'approved', 'rejected'];

if ($fstat && in_array($fstat, $validStatuses)) {
    $stmt = $db->prepare("
        SELECT ar.*, u.full_name, u.email
        FROM analyst_requests ar
        JOIN users u ON ar.user_id = u.id
        WHERE ar.status = ?
        ORDER BY ar.id DESC
    ");
    $stmt->bind_param('s', $fstat);
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $fstat = '';
    $requests = $db->query("
        SELECT ar.*, u.full_name, u.email
        FROM analyst_requests ar
        JOIN users u ON ar.user_id = u.id
        ORDER BY ar.id DESC
    ")->fetch_all(MYSQLI_ASSOC);
}

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
        <option value="pending" <?= $fstat === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="approved" <?= $fstat === 'approved' ? 'selected' : '' ?>>Approved</option>
        <option value="rejected" <?= $fstat === 'rejected' ? 'selected' : '' ?>>Rejected</option>
    </select>
    <button type="submit" class="btn btn-cy btn-sm">Filter</button>
    <a href="<?= BASE_URL ?>/admin/analyst-requests.php" class="btn btn-gy btn-sm">Reset</a>
</form>

<div class="card">
    <?php foreach ($requests as $req): ?>
        <div style="padding:14px 0;border-bottom:1px solid var(--bd)">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                <span style="font-weight:600;color:var(--wh)"><?= e($req['full_name']) ?></span>
                <span style="font-size:12px;color:var(--mu)"><?= e($req['email']) ?></span>
                <?= statusBadge($req['status']) ?>
                <span style="margin-left:auto;font-size:11px;color:var(--mu)"><?= e(substr($req['created_at'], 0, 16)) ?></span>
            </div>
            <div style="font-size:13px;color:var(--tx);margin-bottom:6px"><strong style="color:var(--mu);font-size:11px;text-transform:uppercase">Reason:</strong> <?= e($req['reason']) ?></div>
            <?php if ($req['skills']): ?>
                <div style="font-size:13px;color:var(--tx);margin-bottom:10px"><strong style="color:var(--mu);font-size:11px;text-transform:uppercase">Skills:</strong> <?= e($req['skills']) ?></div>
            <?php endif; ?>
            <?php if ($req['status'] === 'pending'): ?>
                <div style="display:flex;gap:8px">
                    <form method="POST"><input type="hidden" name="req_id" value="<?= $req['id'] ?>"><input type="hidden" name="decision" value="approve"><button class="btn btn-gr btn-sm">✓ Approve</button></form>
                    <form method="POST"><input type="hidden" name="req_id" value="<?= $req['id'] ?>"><input type="hidden" name="decision" value="reject"><button class="btn btn-re btn-sm">✗ Reject</button></form>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if (!$requests): ?>
        <div style="text-align:center;padding:40px;color:var(--mu)">No analyst requests found</div>
    <?php endif; ?>
</div>

<?php pageEnd(); ?>