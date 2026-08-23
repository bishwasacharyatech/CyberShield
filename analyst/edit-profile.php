<?php
require\_once '../includes/config.php';
require\_once '../includes/layout.php';

// Placeholder data for design preview — Bishwas wires the real query + update logic today
$user = \[
    'full\_name' => 'Sample Analyst',
    'email' => 'analyst@example.com',
    'username' => 'sampleanalyst',
    'role' => 'analyst',
    'phone' => '9800000000',
    'created\_at' => date('Y-m-d H:i:s'),
];
$msg = '';
$err = '';

pageStart('Edit Profile', 'analyst');
sidebar('analyst', 'edit-profile');
?>

<div class="pg-title">Edit Profile</div>
<div class="pg-sub">Update your personal information.</div>

<div class="card" style="max-width:480px">
    <div style="background:var(--bg3);border-radius:10px;padding:14px;margin-bottom:16px">
        <?php foreach (\[\['Email', $user\['email']], \['Username', $user\['username']], \['Role', $user\['role']], \['Joined', substr($user\['created\_at'], 0, 10)]] as \[$label, $value]): ?>
            <div style="display:flex;padding:5px 0">
                <span style="color:var(--mu);font-size:12px;min-width:100px"><?= $label ?></span>
                <span style="color:var(--mu)"><?= e($value) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <form method="POST">
        <div class="fg">
            <label class="fl">Full Name \*</label>
            <input type="text" name="full\_name" class="fi" value="<?= e($user\['full\_name']) ?>" required>
        </div>
        <div class="fg">
            <label class="fl">Phone \* (10 digits)</label>
            <input type="tel" name="phone" class="fi" value="<?= e($user\['phone'] ?? '') ?>" required>
        </div>
        <button type="submit" class="btn btn-cy">💾 Save Changes</button>
    </form>
</div>

<?php pageEnd(); ?>