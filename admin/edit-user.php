<?php
require_once '../includes/config.php';
requireAuth('admin');
require_once '../includes/layout.php';

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);
$msg = '';
$err = '';

// Fetch user with prepared statement
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update'])) {
        $name  = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role  = $_POST['role'] ?? $user['role'];

        if (!$name || !$email || !$phone) {
            $err = 'All fields are required.';
        } elseif (!preg_match('/^[A-Za-z ]+$/', $name)) {
            $err = 'Full name should only contain letters and spaces.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'Invalid email address.';
        } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
            $err = 'Phone must be 10 digits.';
        } elseif ($id == 1 && $role !== 'admin') {
            $err = 'Cannot change role of the protected admin account.';
        } else {
            $check = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $check->bind_param('si', $email, $id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $err = 'That email is already used by another account.';
            } else {
                $stmt = $db->prepare('UPDATE users SET full_name = ?, email = ?, phone = ?, role = ? WHERE id = ?');
                $stmt->bind_param('ssssi', $name, $email, $phone, $role, $id);
                $stmt->execute();
                $msg = 'User updated successfully!';

                // Refetch user with prepared statement
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            }
        }
    } elseif (isset($_POST['delete'])) {
        if ($id == 1) {
            $err = 'Cannot delete the protected admin account.';
        } else {
            // Use prepared statements for counts
            $stmt = $db->prepare("SELECT COUNT(*) AS c FROM reports WHERE user_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $asReporter = $stmt->get_result()->fetch_assoc()['c'];

            $stmt = $db->prepare("SELECT COUNT(*) AS c FROM reports WHERE assigned_to = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $asAnalyst = $stmt->get_result()->fetch_assoc()['c'];

            if ($asReporter > 0 || $asAnalyst > 0) {
                $err = "Cannot delete — this account has {$asReporter} submitted report(s) and {$asAnalyst} assigned case(s).";
            } else {
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                header('Location: ' . BASE_URL . '/admin/users.php');
                exit;
            }
        }
    }
}

pageStart('Edit User', 'admin');
sidebar('admin', 'users');
?>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
    <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-gy btn-sm">← Back</a>
    <div class="pg-title" style="margin:0">Edit User #<?= (int) $user['id'] ?></div>
    <?= statusBadge($user['status']) ?>
</div>

<?php if ($msg): ?>
    <div class="flash-ok">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="flash-er">⚠ <?= e($err) ?></div>
<?php endif; ?>

<div class="gr-23">
    <div class="card">
        <div class="ch"><span class="ct">✏️ Account Details</span></div>
        <form method="POST">
            <input type="hidden" name="update" value="1">
            <div class="fg">
                <label class="fl">Full Name *</label>
                <input type="text" name="full_name" class="fi" value="<?= e($user['full_name']) ?>" required>
            </div>
            <div class="fg">
                <label class="fl">Email *</label>
                <input type="email" name="email" class="fi" value="<?= e($user['email']) ?>" required>
            </div>
            <div class="fg">
                <label class="fl">Phone * (10 digits)</label>
                <input type="tel" name="phone" class="fi" value="<?= e($user['phone'] ?? '') ?>" required>
            </div>
            <div class="fg">
                <label class="fl">Role</label>
                <select name="role" class="fi" <?= $user['id'] == 1 ? 'disabled' : '' ?>>
                    <?php foreach (['user', 'analyst', 'admin'] as $role): ?>
                        <option value="<?= $role ?>" <?= $user['role'] === $role ? 'selected' : '' ?>><?= ucfirst($role) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($user['id'] == 1): ?>
                    <input type="hidden" name="role" value="admin">
                    <div style="font-size:11px;color:var(--mu);margin-top:4px">Protected account — role cannot be changed</div>
                <?php endif; ?>
            </div>
            <div class="fg">
                <label class="fl">Username</label>
                <input type="text" class="fi" value="<?= e($user['username']) ?>" disabled>
            </div>
            <button type="submit" class="btn btn-cy">💾 Save Changes</button>
        </form>
    </div>

    <div class="card">
        <div class="ch"><span class="ct">ℹ️ Account Info</span></div>
        <?php
        // Use prepared statements for info counts
        $stmt = $db->prepare("SELECT COUNT(*) AS c FROM reports WHERE user_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $reportCount = $stmt->get_result()->fetch_assoc()['c'];

        $stmt = $db->prepare("SELECT COUNT(*) AS c FROM reports WHERE assigned_to = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $assignedCount = $stmt->get_result()->fetch_assoc()['c'];

        $infoFields = [
            ['Joined', substr($user['created_at'], 0, 10)],
            ['Last Login', $user['last_login'] ? substr($user['last_login'], 0, 16) : 'Never'],
            ['Reports Submitted', $reportCount],
            ['Cases Assigned', $assignedCount]
        ];
        foreach ($infoFields as [$label, $value]): ?>
            <div style="display:flex;padding:8px 0;border-bottom:1px solid var(--bd)">
                <span style="color:var(--mu);font-size:12px;min-width:140px"><?= $label ?></span>
                <span><?= e($value) ?></span>
            </div>
        <?php endforeach; ?>

        <?php if ($user['id'] != 1): ?>
            <div style="margin-top:18px;border-top:1px solid var(--bd);padding-top:16px">
                <div style="font-size:12px;color:var(--re);font-weight:700;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px">⚠ Danger Zone</div>
                <form method="POST" onsubmit="return confirm('Permanently delete this account? This cannot be undone.')">
                    <input type="hidden" name="delete" value="1">
                    <button type="submit" class="btn btn-re">🗑 Delete Account</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php pageEnd(); ?>