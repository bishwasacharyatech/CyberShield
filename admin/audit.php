<?php
require_once '../includes/config.php';
requireAuth('admin');
require_once '../includes/layout.php';

$db = getDB();

$search  = trim($_GET['search'] ?? '');
$frole   = $_GET['role'] ?? '';
$faction = $_GET['action'] ?? '';
$sort    = $_GET['sort'] ?? 'newest';

$where = [];
$params = [];
$types = '';

if ($search) {
    $where[] = "(username LIKE ? OR description LIKE ? OR ip_address LIKE ?)";
    $s = "%{$search}%";
    $params[] = $s; $params[] = $s; $params[] = $s;
    $types .= 'sss';
}
if ($frole) {
    $where[] = 'role = ?';
    $params[] = $frole;
    $types .= 's';
}
if ($faction) {
    $where[] = 'action = ?';
    $params[] = $faction;
    $types .= 's';
}

$orderBy = ($sort === 'oldest') ? 'id ASC' : 'id DESC';
$sql = "SELECT * FROM audit_logs";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= " ORDER BY {$orderBy} LIMIT 500";

$stmt = $db->prepare($sql);
if ($params) {
    $bind = [$types];
    foreach ($params as $key => $value) {
        $bind[] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind);
}
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = $db->query("SELECT COUNT(*) AS c FROM audit_logs")->fetch_assoc()['c'];
$actions = $db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetch_all(MYSQLI_ASSOC);

pageStart('Audit Trail', 'admin');
sidebar('admin', 'audit');
?>

<div class="pg-title">Audit Trail</div>
<div class="pg-sub">Every action logged automatically. Total: <strong style="color:var(--cy)"><?= (int) $total ?></strong></div>

<form method="GET" class="srchbar">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search user, description, IP..." class="srch-i" style="flex:1;min-width:180px">
    <select name="role" class="srch-i">
        <option value="">All Roles</option>
        <option value="user" <?= $frole === 'user' ? 'selected' : '' ?>>User</option>
        <option value="analyst" <?= $frole === 'analyst' ? 'selected' : '' ?>>Analyst</option>
        <option value="admin" <?= $frole === 'admin' ? 'selected' : '' ?>>Admin</option>
    </select>
    <select name="action" class="srch-i">
        <option value="">All Actions</option>
        <?php foreach ($actions as $a): ?>
            <option value="<?= e($a['action']) ?>" <?= $faction === $a['action'] ? 'selected' : '' ?>><?= e($a['action']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="sort" class="srch-i">
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
    </select>
    <button type="submit" class="btn btn-cy btn-sm">Filter</button>
    <a href="<?= BASE_URL ?>/admin/audit.php" class="btn btn-gy btn-sm">Reset</a>
</form>

<div class="card">
    <div class="tw" style="max-height:calc(100vh - 280px);overflow-y:auto">
        <table>
            <thead style="position:sticky;top:0">
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Description</th>
                    <th>IP</th>
                    <th>Device</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $actionColors = [
                    'LOGIN' => 'var(--gr)', 'LOGOUT' => 'var(--mu)', 'CREATE' => 'var(--cy)',
                    'UPDATE' => 'var(--am)', 'VIEW' => 'var(--tx)', 'ASSIGN' => 'var(--pu)',
                    'REGISTER' => 'var(--gr)', 'DELETE' => 'var(--re)'
                ];
                ?>
                <?php foreach ($logs as $log): ?>
                    <?php $color = $actionColors[$log['action']] ?? 'var(--mu)'; ?>
                    <tr>
                        <td style="font-family:monospace;font-size:11px;color:var(--mu);white-space:nowrap"><?= e(substr($log['created_at'], 0, 16)) ?></td>
                        <td style="color:var(--wh);font-weight:600"><?= e($log['username']) ?></td>
                        <td style="font-size:11px;color:var(--mu);text-transform:uppercase"><?= e($log['role']) ?></td>
                        <td><span style="color:<?= $color ?>;font-weight:700;font-size:12px"><?= e($log['action']) ?></span></td>
                        <td style="font-size:12px;color:var(--mu)"><?= e($log['module']) ?></td>
                        <td style="font-size:12px;max-width:240px;white-space:normal"><?= e($log['description']) ?></td>
                        <td style="font-family:monospace;font-size:11px;color:var(--cy)"><?= e($log['ip_address']) ?></td>

                        <!-- Device column: Line 1 = browser/OS/type, Line 2 = device code -->
                        <td style="max-width:180px;white-space:normal;word-break:break-word;line-height:1.4"
                            title="<?= e($log['user_agent'] ?? '') ?>">
                            <!-- Line 1: browser / OS / device type -->
                            <div style="font-size:11px;color:var(--mu)">
                                <?= e(parseUserAgent($log['user_agent'] ?? '')) ?>
                            </div>
                            <!-- Line 2: real device code (model code) -->
                            <?php
                                $code = getDeviceCode($log['user_agent'] ?? '');
                                if ($code) {
                                    echo '<div style="font-size:10px;color:var(--cy);opacity:0.85">' . e($code) . '</div>';
                                }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$logs): ?>
                    <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--mu);">No audit logs found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php pageEnd(); ?>