<?php
require\_once '../includes/config.php';
require\_once '../includes/layout.php';

// Placeholder data for design preview — Bishwas wires the real query + update/delete logic today
$user = \[
    'id' => 2,
    'full\_name' => 'Sample User',
    'email' => 'sample@example.com',
    'phone' => '9800000000',
    'username' => 'sampleuser',
    'role' => 'user',
    'status' => 'active',
    'created\_at' => date('Y-m-d H:i:s'),
    'last\_login' => null,
];

pageStart('Edit User', 'admin');
sidebar('admin', 'users');
?>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
    <a href="<?= BASE\_URL ?>/admin/users.php" class="btn btn-gy btn-sm">← Back</a>
    <div class="pg-title" style="margin:0">Edit User #<?= (int) $user\['id'] ?></div>
    <?= statusBadge($user\['status']) ?>
</div>

<div class="gr-23">
    <div class="card">
        <div class="ch"><span class="ct"><i class="ti ti-user-edit"></i> Account Details</span></div>
        <form method="POST">
            <input type="hidden" name="update" value="1">
            <div class="fg">
                <label class="fl">Full Name \*</label>
                <input type="text" name="full\_name" class="fi" value="<?= e($user\['full\_name']) ?>" required>
            </div>
            <div class="fg">
                <label class="fl">Email \*</label>
                <input type="email" name="email" class="fi" value="<?= e($user\['email']) ?>" required>
            </div>
            <div class="fg">
                <label class="fl">Phone \* (10 digits)</label>
                <input type="tel" name="phone" class="fi" value="<?= e($user\['phone'] ?? '') ?>" required>
            </div>
            <div class="fg">
                <label class="fl">Role</label>
                <select name="role" class="fi">
                    <?php foreach (\['user', 'analyst', 'admin'] as $role): ?>
                        <option value="<?= $role ?>" <?= $user\['role'] === $role ? 'selected' : '' ?>><?= ucfirst($role) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label class="fl">Username</label>
                <input type="text" class="fi" value="<?= e($user\['username']) ?>" disabled>
            </div>
            <button type="submit" class="btn btn-cy">💾 Save Changes</button>
        </form>
    </div>

    <div class="card">
        <div class="ch"><span class="ct"><i class="ti ti-info-circle"></i> Account Info</span></div>
        <?php
        $infoFields = \[
            \['Joined', substr($user\['created\_at'], 0, 10)],
            \['Last Login', $user\['last\_login'] ? substr($user\['last\_login'], 0, 16) : 'Never'],
        ];
        foreach ($infoFields as \[$label, $value]): ?>
            <div style="display:flex;padding:8px 0;border-bottom:1px solid var(--bd)">
                <span style="color:var(--mu);font-size:12px;min-width:140px"><?= $label ?></span>
                <span><?= e($value) ?></span>
            </div>
        <?php endforeach; ?>

        <div style="margin-top:18px;border-top:1px solid var(--bd);padding-top:16px">
            <div style="font-size:12px;color:var(--re);font-weight:700;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px">⚠ Danger Zone</div>
            <form method="POST" onsubmit="return confirm('Permanently delete this account? This cannot be undone.')">
                <input type="hidden" name="delete" value="1">
                <button type="submit" class="btn btn-re">🗑 Delete Account</button>
            </form>
        </div>
    </div>
</div>

<?php pageEnd(); ?>