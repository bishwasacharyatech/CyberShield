<?php
require_once '../includes/config.php';
requireAuth('admin');
require_once '../includes/layout.php';

$db = getDB();

$search = trim($_GET['search'] ?? '');
$fsev   = $_GET['severity'] ?? '';
$fstat  = $_GET['status'] ?? '';
$fcat   = $_GET['category'] ?? '';
$fasg   = $_GET['assigned'] ?? '';
$sort   = $_GET['sort'] ?? 'newest';

$where = [];
$params = [];
$types = '';

if ($search) {
    $where[] = "(r.title LIKE ? OR r.ticket_no LIKE ? OR u.full_name LIKE ? OR a.full_name LIKE ?)";
    $s = "%{$search}%";
    $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
    $types .= 'ssss';
}
if ($fsev) {
    $where[] = 'r.severity = ?';
    $params[] = $fsev;
    $types .= 's';
}
if ($fstat) {
    $where[] = 'r.status = ?';
    $params[] = $fstat;
    $types .= 's';
}
if ($fcat) {
    $where[] = 'r.category_id = ?';
    $params[] = (int) $fcat;
    $types .= 'i';
}
if ($fasg === 'unassigned') {
    $where[] = 'r.assigned_to IS NULL';
} elseif ($fasg === 'assigned') {
    $where[] = 'r.assigned_to IS NOT NULL';
}

$orderBy = match ($sort) {
    'oldest'   => 'r.id ASC',
    'severity' => 'FIELD(r.severity, "Critical", "High", "Medium", "Low")',
    default    => 'r.id DESC'
};

$sql = "
    SELECT r.*, c.name AS cat_name, u.full_name AS uname, a.full_name AS aname
    FROM reports r
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN users a ON r.assigned_to = a.id
";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= " ORDER BY {$orderBy}";

$stmt = $db->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$categories = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);

pageStart('All Reports', 'admin');
sidebar('admin', 'reports');
?>

<div class="pg-title">All Reports</div>
<div class="pg-sub"><?= count($reports) ?> report(s) found</div>

<form method="GET" class="srchbar">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search ticket, title, user, analyst..." class="srch-i" style="flex:1;min-width:180px">
    <select name="category" class="srch-i">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $fcat == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="severity" class="srch-i">
        <option value="">All Severity</option>
        <?php foreach (['Critical', 'High', 'Medium', 'Low'] as $sev): ?>
            <option <?= $fsev === $sev ? 'selected' : '' ?>><?= $sev ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status" class="srch-i">
        <option value="">All Status</option>
        <?php foreach (['New', 'Assigned', 'Under Review', 'In Progress', 'Resolved', 'Closed'] as $stat): ?>
            <option <?= $fstat === $stat ? 'selected' : '' ?>><?= $stat ?></option>
        <?php endforeach; ?>
    </select>
    <select name="assigned" class="srch-i">
        <option value="">All</option>
        <option value="assigned" <?= $fasg === 'assigned' ? 'selected' : '' ?>>Assigned</option>
        <option value="unassigned" <?= $fasg === 'unassigned' ? 'selected' : '' ?>>Unassigned</option>
    </select>
    <select name="sort" class="srch-i">
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
        <option value="severity" <?= $sort === 'severity' ? 'selected' : '' ?>>Severity</option>
    </select>
    <button type="submit" class="btn btn-cy btn-sm">Filter</button>
    <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-gy btn-sm">Reset</a>
</form>

<div class="card">
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Ticket</th><th>Title</th><th>Category</th><th>Severity</th>
                    <th>Status</th><th>User</th><th>Analyst</th><th>Date</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td style="font-family:monospace;color:var(--cy);font-size:11px"><?= e($report['ticket_no']) ?></td>
                        <td style="color:var(--wh)"><?= e(substr($report['title'], 0, 28)) ?></td>
                        <td style="font-size:12px"><?= e($report['cat_name'] ?? '—') ?></td>
                        <td><?= sevBadge($report['severity']) ?></td>
                        <td><?= statusBadge($report['status']) ?></td>
                        <td style="font-size:12px;color:var(--mu)"><?= e($report['uname']) ?></td>
                        <td style="font-size:12px;color:var(--mu)"><?= e($report['aname'] ?? 'Unassigned') ?></td>
                        <td style="font-size:12px;color:var(--mu)"><?= e(substr($report['created_at'], 0, 10)) ?></td>
                        <td><a href="<?= BASE_URL ?>/admin/assign.php?id=<?= (int) $report['id'] ?>" class="btn btn-gy btn-sm">Manage</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$reports): ?>
                    <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--mu)">No reports found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php pageEnd(); ?>