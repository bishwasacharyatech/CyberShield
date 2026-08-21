<?php
require_once '../includes/config.php';
requireAuth('admin');
require_once '../includes/layout.php';

$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $tid = (int) $_POST['toggle_id'];
    if ($tid == 1) {
        $msg = 'Cannot suspend the protected admin account.';
    } else {
        $user = $db->query("SELECT status FROM users WHERE id = {$tid}")->fetch_assoc();
        if ($user) {
            $newStatus = ($user['status'] === 'active') ? 'suspended' : 'active';
            $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $newStatus, $tid);
            $stmt->execute();
            $msg = "User status updated to {$newStatus}";
        }
    }
}

$search = trim($_GET['search'] ?? '');
$frole  = $_GET['role'] ?? '';
$fstat  = $_GET['status'] ?? '';
$sort   = $_GET['sort'] ?? 'newest';

$where = [];
$params = [];
$types = '';

if ($search) {
    $where[] = "(full_name LIKE ? OR username LIKE ? OR email LIKE ?)";
    $s = "%{$search}%";
    $params[] = $s; $params[] = $s; $params[] = $s;
    $types .= 'sss';
}
if ($frole) {
    $where[] = 'role = ?';
    $params[] = $frole;
    $types .= 's';
}
if ($fstat) {
    $where[] = 'status = ?';
    $params[] = $fstat;
    $types .= 's';
}

$orderBy = match ($sort) {
    'oldest' => 'id ASC',
    'name'   => 'full_name ASC',
    default  => 'id DESC'
};

$sql = "
    SELECT u.*, (SELECT COUNT(*) FROM reports WHERE user_id = u.id) AS report_count
    FROM users u
";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= " ORDER BY {$orderBy}";

$stmt = $db->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

pageStart('Manage Users', 'admin');
sidebar('admin', 'users');
?>

<div class="pg-title">Manage Users</div>
<div class="pg-sub"><?= count($users) ?> account(s) found</div>

<?php if ($msg): ?>
    <div class="flash-ok">✅ <?= e($msg) ?></div>
<?php endif; ?>

<form method="GET" class="srchbar">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name, username, email..." class="srch-i" style="flex:1;min-width:180px">
    <select name="role" class="srch-i">
        <option value="">All Roles</option>
        <option value="user" <?= $frole === 'user' ? 'selected' : '' ?>>User</option>
        <option value="analyst" <?= $frole === 'analyst' ? 'selected' : '' ?>>Analyst</option>
        <option value="admin" <?= $frole === 'admin' ? 'selected' : '' ?>>Admin</option>
    </select>
    <select name="status" class="srch-i">
        <option value="">All Status</option>
        <option value="active" <?= $fstat === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="suspended" <?= $fstat === 'suspended' ? 'selected' : '' ?>>Suspended</option>
    </select>
    <select name="sort" class="srch-i">
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A-Z</option>
    </select>
    <button type="submit" class="btn btn-cy btn-sm">Filter</button>
    <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-gy btn-sm">Reset</a>
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
                <?php $roleColors = ['admin' => 'var(--re)', 'analyst' => 'var(--pu)', 'user' => 'var(--cy)']; ?>
                <?php foreach ($users as $user): ?>
                    <?php $color = $roleColors[$user['role']] ?? 'var(--mu)'; ?>
                    <tr>
                        <td style="color:var(--mu);font-family:monospace">#<?= (int) $user['id'] ?></td>
                        <td style="color:var(--wh);font-weight:600"><?= e($user['full_name']) ?></td>
                        <td style="font-family:monospace;color:var(--cy)"><?= e($user['username']) ?></td>
                        <td style="font-size:12px;color:var(--mu)"><?= e($user['email']) ?></td>
                        <td>
                            <span style="background:<?= $color ?>18;color:<?= $color ?>;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;text-transform:uppercase">
                                <?= e($user['role']) ?>
                            </span>
                        </td>
                        <td style="text-align:center;font-family:monospace"><?= (int) $user['report_count'] ?></td>
                        <td><?= statusBadge($user['status']) ?></td>
                        <td style="font-size:12px;color:var(--mu)"><?= e(substr($user['created_at'], 0, 10)) ?></td>
                        <td>
                            <div style="display:flex;gap:4px">
                                <a href="<?= BASE_URL ?>/admin/edit-user.php?id=<?= (int) $user['id'] ?>" class="btn btn-pu btn-sm">Edit</a>
                                <?php if ($user['id'] != 1): ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="toggle_id" value="<?= (int) $user['id'] ?>">
                                        <button type="submit" class="btn <?= $user['status'] === 'active' ? 'btn-re' : 'btn-gr' ?> btn-sm">
                                            <?= $user['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color:var(--mu);font-size:12px">Protected</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$users): ?>
                    <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--mu)">No users found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php pageEnd(); ?>