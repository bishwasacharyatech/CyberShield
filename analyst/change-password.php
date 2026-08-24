<?php
require_once '../includes/config.php';
requireAuth('analyst');
require_once '../includes/layout.php';

$db = getDB();
$uid = $_SESSION['uid'];
$msg = '';
$err = '';

// Fetch user with prepared statement
$stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old  = $_POST['old_password'] ?? '';
    $new  = $_POST['new_password'] ?? '';
    $conf = $_POST['confirm_password'] ?? '';

    if (!password_verify($old, $user['password'])) {
        $err = 'Current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $err = 'New password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $new) || !preg_match('/[a-z]/', $new) || !preg_match('/[0-9]/', $new)) {
        $err = 'Password must contain uppercase, lowercase, and a number.';
    } elseif ($new !== $conf) {
        $err = 'New passwords do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->bind_param('si', $hash, $uid);
        $stmt->execute();

        auditLog('UPDATE', 'Security', 'Changed account password');

        $msg = 'Password changed successfully!';
    }
}

pageStart('Change Password', 'analyst');
sidebar('analyst', 'change-password');
?>

<div class="pg-title">Change Password</div>
<div class="pg-sub">Keep your account secure with a strong password.</div>

<?php if ($msg): ?>
    <div class="flash-ok">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="flash-er">⚠ <?= e($err) ?></div>
<?php endif; ?>

<div class="card" style="max-width:420px">
    <form method="POST">
        <div class="fg">
            <label class="fl">Current Password *</label>
            <input type="password" name="old_password" class="fi" placeholder="Enter current password" required>
        </div>
        <div class="fg">
            <label class="fl">New Password * (min 8 chars)</label>
            <input type="password" name="new_password" class="fi" placeholder="Enter new password" required>
        </div>
        <div class="fg">
            <label class="fl">Confirm New Password *</label>
            <input type="password" name="confirm_password" class="fi" placeholder="Repeat new password" required>
        </div>
        <button type="submit" class="btn btn-cy">🔒 Change Password</button>
    </form>
</div>

<?php pageEnd(); ?>