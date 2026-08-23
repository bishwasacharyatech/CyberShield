<?php
require\_once '../includes/config.php';
require\_once '../includes/layout.php';

// Placeholder — Bishwas wires the real verify/update logic today
$msg = '';
$err = '';

pageStart('Change Password', 'user');
sidebar('user', 'change-password');
?>

<div class="pg-title">Change Password</div>
<div class="pg-sub">Keep your account secure with a strong password.</div>

<div class="card" style="max-width:420px">
    <form method="POST">
        <div class="fg">
            <label class="fl">Current Password \*</label>
            <input type="password" name="old\_password" class="fi" placeholder="Enter current password" required>
        </div>
        <div class="fg">
            <label class="fl">New Password \* (min 8 chars)</label>
            <input type="password" name="new\_password" class="fi" placeholder="Enter new password" required>
        </div>
        <div class="fg">
            <label class="fl">Confirm New Password \*</label>
            <input type="password" name="confirm\_password" class="fi" placeholder="Repeat new password" required>
        </div>
        <button type="submit" class="btn btn-cy">🔒 Change Password</button>
    </form>
</div>

<?php pageEnd(); ?>