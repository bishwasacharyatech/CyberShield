<?php
require_once '../includes/config.php';
requireAuth('analyst');
require_once '../includes/layout.php';

$db = getDB();
$uid = $_SESSION['uid'];

$search = trim($_GET['search'] ?? '');
$fstat  = $_GET['status'] ?? '';
$sort   = $_GET['sort'] ?? 'newest';

$where = ["r.assigned_to = ?"];
$params = [$uid];
$types = 'i';

if ($search) {
    $where[] = "(r.title LIKE ? OR r.ticket_no LIKE ?)";
    $s = "%{$search}%";
    $params[] = $s; $params[] = $s;
    $types .= 'ss';
}
if ($fstat) {
    $where[] = 'r.status = ?';
    $params[] = $fstat;
    $types .= 's';
}

$orderBy = match ($sort) {
    'severity' => 'FIELD(r.severity, "Critical", "High", "Medium", "Low")',
    'oldest'   => 'r.id ASC',
    default    => 'r.id DESC'
};

$sql = "
    SELECT r.*, c.name AS cat_name, u.full_name AS uname
    FROM reports r
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN users u ON r.user_id = u.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY {$orderBy}
";

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

auditLog('VIEW', 'Assigned Cases', 'Viewed assigned cases list');

pageStart('My Cases', 'analyst');
sidebar('analyst', 'assigned');
?>

<div class="pg-title">My Assigned Cases</div>
<div class="pg-sub"><?= count($reports) ?> case(s)</div>

<form method="GET" class="srchbar">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search ticket, title..." class="srch-i" style="flex:1;min-width:160px">
    <select name="status" class="srch-i">
        <option value="">All Status</option>
        <?php foreach (['New', 'Assigned', 'Under Review', 'In Progress', 'Resolved', 'Closed'] as $status): ?>
            <option <?= $fstat === $status ? 'selected' : '' ?>><?= $status ?></option>
        <?php endforeach; ?>
    </select>
    <select name="sort" class="srch-i">
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
        <option value="severity" <?= $sort === 'severity' ? 'selected' : '' ?>>Severity</option>
    </select>
    <button type="submit" class="btn btn-cy btn-sm">Filter</button>
    <a href="<?= BASE_URL ?>/analyst/assigned.php" class="btn btn-gy btn-sm">Reset</a>
</form>

<div class="card">
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Ticket</th><th>Title</th><th>Category</th><th>Severity</th>
                    <th>Status</th><th>Reporter</th><th>Date</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td style="font-family:monospace;color:var(--cy);font-size:11px"><?= e($report['ticket_no']) ?></td>
                        <td style="color:var(--wh)"><?= e(substr($report['title'], 0, 32)) ?></td>
                        <td style="font-size:12px"><?= e($report['cat_name'] ?? '—') ?></td>
                        <td><?= sevBadge($report['severity']) ?></td>
                        <td><?= statusBadge($report['status']) ?></td>
                        <td style="font-size:12px;color:var(--mu)"><?= e($report['uname']) ?></td>
                        <td style="font-size:12px;color:var(--mu)"><?= e(substr($report['created_at'], 0, 10)) ?></td>
                        <td><a href="<?= BASE_URL ?>/analyst/investigate.php?id=<?= (int) $report['id'] ?>" class="btn btn-gy btn-sm">Investigate</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$reports): ?>
                    <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--mu)">No cases found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php pageEnd(); ?>