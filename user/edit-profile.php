<?php
require_once '../includes/config.php';
requireAuth('user');
require_once '../includes/layout.php';

$db = getDB();
$uid = $_SESSION['uid'];
$msg = '';
$err = '';

$user = $db->query("SELECT * FROM users WHERE id = {$uid}")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');

    if (!$fullName || !$phone) {
        $err = 'All fields are required.';
    } elseif (!preg_match('/^[A-Za-z ]+$/', $fullName)) {
        $err = 'Full name should only contain letters and spaces.';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $err = 'Phone must be exactly 10 digits.';
    } else {
        $stmt = $db->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?');
        $stmt->bind_param('ssi', $fullName, $phone, $uid);
        $stmt->execute();

        $_SESSION['fname'] = $fullName;

        auditLog('UPDATE', 'Profile', 'Updated profile information');

        $msg = 'Profile updated successfully!';
        $user = $db->query("SELECT * FROM users WHERE id = {$uid}")->fetch_assoc();
    }
}

pageStart('Edit Profile', 'user');
sidebar('user', 'edit-profile');
?>

<div class="pg-title">Edit Profile</div>
<div class="pg-sub">Update your personal information.</div>

<?php if ($msg): ?>
    <div class="flash-ok">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="flash-er">⚠ <?= e($err) ?></div>
<?php endif; ?>

<div class="card" style="max-width:480px">
    <div style="background:var(--bg3);border-radius:10px;padding:14px;margin-bottom:16px">
        <?php foreach ([['Email', $user['email']], ['Username', $user['username']], ['Role', $user['role']], ['Joined', substr($user['created_at'], 0, 10)]] as [$label, $value]): ?>
            <div style="display:flex;padding:5px 0">
                <span style="color:var(--mu);font-size:12px;min-width:100px"><?= $label ?></span>
                <span style="color:var(--mu)"><?= e($value) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <form method="POST">
        <div class="fg">
            <label class="fl">Full Name *</label>
            <input type="text" name="full_name" class="fi" value="<?= e($user['full_name']) ?>" required>
        </div>
        <div class="fg">
            <label class="fl">Phone * (10 digits)</label>
            <input type="tel" name="phone" class="fi" value="<?= e($user['phone'] ?? '') ?>" required>
        </div>
        <button type="submit" class="btn btn-cy">💾 Save Changes</button>
    </form>
</div>

<?php pageEnd(); ?>