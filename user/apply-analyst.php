<?php
require_once '../includes/config.php';
requireAuth('user');
require_once '../includes/layout.php';

$db = getDB();
$uid = $_SESSION['uid'];
$err = '';

$stmt = $db->prepare("SELECT * FROM analyst_requests WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param('i', $uid);
$stmt->execute();
$existingRequest = $stmt->get_result()->fetch_assoc();

$canApply = !$existingRequest || $existingRequest['status'] === 'rejected';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canApply) {
    $reason = trim($_POST['reason'] ?? '');
    $skills = trim($_POST['skills'] ?? '');

    if (!$reason) {
        $err = 'Please explain why you want to become an analyst.';
    } else {
        $stmt = $db->prepare('INSERT INTO analyst_requests (user_id, reason, skills) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $uid, $reason, $skills);
        $stmt->execute();

        auditLog('CREATE', 'Analyst Request', 'Submitted a request to become an analyst');

        $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetch_all(MYSQLI_ASSOC);
        foreach ($admins as $admin) {
            addNotif($admin['id'], null, "New analyst request from {$_SESSION['fname']}");
        }

        header('Location: ' . BASE_URL . '/user/apply-analyst.php');
        exit;
    }
}

pageStart('Apply as Analyst', 'user');
sidebar('user', 'apply-analyst');
?>

<div class="pg-title">Apply to Become a SOC Analyst</div>
<div class="pg-sub">Submit your request for review. An admin will approve or reject it.</div>

<?php if ($err): ?>
    <div class="flash-er">⚠ <?= e($err) ?></div>
<?php endif; ?>

<div class="card" style="max-width:560px">
    <?php if ($existingRequest && $existingRequest['status'] === 'pending'): ?>
        <div class="ch"><span class="ct"><i class="ti ti-clock"></i> Request Pending</span></div>
        <p style="color:var(--mu);font-size:13px;line-height:1.7">
            Your application is awaiting admin review. You'll be notified once a decision is made.
        </p>
        <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--bd)">
            <div style="font-size:11px;color:var(--mu);margin-bottom:6px;text-transform:uppercase">Submitted</div>
            <div style="font-size:12px;color:var(--mu)"><?= e(substr($existingRequest['created_at'], 0, 16)) ?></div>
        </div>
    <?php else: ?>
        <?php if ($existingRequest && $existingRequest['status'] === 'rejected'): ?>
            <div class="ch"><span class="ct"><i class="ti ti-x"></i> Previous Request Rejected</span></div>
            <p style="color:var(--mu);font-size:13px;line-height:1.7;margin-bottom:16px">
                Your last application wasn't approved. You can submit a new request below.
            </p>
        <?php endif; ?>
        <form method="POST">
            <div class="fg">
                <label class="fl">Why do you want to become an analyst? *</label>
                <textarea name="reason" class="fi" placeholder="Explain your motivation and relevant experience..." required style="min-height:110px"><?= e($_POST['reason'] ?? '') ?></textarea>
            </div>
            <div class="fg">
                <label class="fl">Relevant Skills (optional)</label>
                <textarea name="skills" class="fi" placeholder="e.g., Networking, Linux, incident response, certifications..." style="min-height:80px"><?= e($_POST['skills'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-cy">📨 Submit Request</button>
        </form>
    <?php endif; ?>
</div>

<?php pageEnd(); ?>