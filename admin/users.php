<?php
require\_once '../includes/config.php';
require\_once '../includes/layout.php';

// Placeholder data for design preview — Bishwas wires real queries + suspend logic today
$users = \[
    \['id' => 2, 'full\_name' => 'Sample User', 'username' => 'sampleuser', 'email' => 'sample@example.com', 'role' => 'user', 'status' => 'active', 'created\_at' => date('Y-m-d H:i:s'), 'report\_count' => 0],
];
$msg = '';
$search = '';
$frole = '';
$fstat = '';
$sort = 'newest';

pageStart('Manage Users', 'admin');
sidebar('admin', 'users');
?>

<div class="pg-title">Manage Users</div>
<div class="pg-sub"><?= count($users) ?> account(s) found</div>

<form method="GET" class="srchbar">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name, username, email..." class="srch-i" style="flex:1;min-width:180px">
    <select name="role" class="srch-i">
        <option value="">All Roles</option>
        <option value="user">User</option>
        <option value="analyst">Analyst</option>
        <option value="admin">Admin</option>
    </select>
    <select name="status" class="srch-i">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="suspended">Suspended</option>
    </select>
    <select name="sort" class="srch-i">
        <option value="newest">Newest</option>
        <option value="oldest">Oldest</option>
        <option value="name">Name A-Z</option>
    </select>
    <button type="submit" class="btn btn-cy btn-sm">Filter</button>
    <a href="<?= BASE\_URL ?>/admin/users.php" class="btn btn-gy btn-sm">Reset</a>
</form>

<div class="card">
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th>
                    <th>Reports</th><th>Status</th><th>Joined</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $roleColors = \['admin' => 'var(--re)', 'analyst' => 'var(--pu)', 'user' => 'var(--cy)']; ?>
                <?php foreach ($users as $user): ?>
                    <?php $color = $roleColors\[$user\['role']] ?? 'var(--mu)'; ?>
                    <tr>
                        <td style="color:var(--mu);font-family:monospace">#<?= (int) $user\['id'] ?></td>
                        <td style="color:var(--wh);font-weight:600"><?= e($user\['full\_name']) ?></td>
                        <td style="font-family:monospace;color:var(--cy)"><?= e($user\['username']) ?></td>
                        <td style="font-size:12px;color:var(--mu)"><?= e($user\['email']) ?></td>
                        <td>
                            <span style="background:<?= $color ?>18;color:<?= $color ?>;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;text-transform:uppercase">
                                <?= e($user\['role']) ?>
                            </span>
                        </td>
                        <td style="text-align:center;font-family:monospace"><?= (int) $user\['report\_count'] ?></td>
                        <td><?= statusBadge($user\['status']) ?></td>
                        <td style="font-size:12px;color:var(--mu)"><?= e(substr($user\['created\_at'], 0, 10)) ?></td>
                        <td>
                            <div style="display:flex;gap:4px">
                                <a href="<?= BASE\_URL ?>/admin/edit-user.php?id=<?= (int) $user\['id'] ?>" class="btn btn-pu btn-sm">Edit</a>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="toggle\_id" value="<?= (int) $user\['id'] ?>">
                                    <button type="submit" class="btn btn-re btn-sm">Suspend</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php pageEnd(); ?>