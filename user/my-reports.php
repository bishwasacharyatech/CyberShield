<?php
require_once '../includes/config.php';
requireAuth('user');
require_once '../includes/layout.php';

$db = getDB();
$uid = $_SESSION['uid'];

$search = trim($_GET['search'] ?? '');
$fsev   = $_GET['severity'] ?? '';
$fstat  = $_GET['status'] ?? '';
$sort   = $_GET['sort'] ?? 'newest';

$where = ["r.user_id = ?"];
$params = [$uid];
$types = 'i';

if ($search) {
    $where[] = "(r.title LIKE ? OR r.ticket_no LIKE ? OR c.name LIKE ?)";
    $s = "%{$search}%";
    $params[] = $s; $params[] = $s; $params[] = $s;
    $types .= 'sss';
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

$orderBy = match ($sort) {
    'oldest'   => 'r.id ASC',
    'severity' => 'FIELD(r.severity, "Critical", "High", "Medium", "Low")',
    default    => 'r.id DESC'
};

$sql = "
    SELECT r.*, c.name AS cat_name, u.full_name AS aname
    FROM reports r
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN users u ON r.assigned_to = u.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY {$orderBy}
";

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

auditLog('VIEW', 'My Reports', 'Viewed own reports list');

pageStart('My Reports', 'user');
sidebar('user', 'my-reports');
?>

<div class="pg-title">My Reports</div>
<div class="pg-sub"><?= count($reports) ?> report(s) found</div>

<form method="GET" class="srchbar">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search title, ticket, category..." class="srch-i" style="flex:1;min-width:160px">
    <select name="severity" class="srch-i">
        <option value="">All Severity</option>
        <?php foreach (['Critical', 'High', 'Medium', 'Low'] as $sev): ?>
            <option value="<?= $sev ?>" <?= $fsev === $sev ? 'selected' : '' ?>><?= $sev ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status" class="srch-i">
        <option value="">All Status</option>
        <?php foreach (['New', 'Assigned', 'Under Review', 'In Progress', 'Resolved', 'Closed'] as $stat): ?>
            <option value="<?= $stat ?>" <?= $fstat === $stat ? 'selected' : '' ?>><?= $stat ?></option>
        <?php endforeach; ?>
    </select>
    <select name="sort" class="srch-i">
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
        <option value="severity" <?= $sort === 'severity' ? 'selected' : '' ?>>By Severity</option>
    </select>
    <button type="submit" class="btn btn-cy btn-sm">Filter</button>
    <a href="<?= BASE_URL ?>/user/my-reports.php" class="btn btn-gy btn-sm">Reset</a>
</form>

<div class="card">
    <div class="ch">
        <span class="ct"><i class="ti ti-file-text"></i> Reports</span>
        <a href="<?= BASE_URL ?>/user/report.php" class="btn btn-cy btn-sm"><i class="ti ti-plus"></i> New</a>
    </div>
    <?php if ($reports): ?>
        <div class="tw">
            <table>
                <thead>
                    <tr>
                        <th>Ticket</th><th>Title</th><th>Category</th><th>Severity</th>
                        <th>Status</th><th>Analyst</th><th>Date</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td style="font-family:monospace;color:var(--cy);font-size:11px"><?= e($report['ticket_no']) ?></td>
                            <td style="color:var(--wh)"><?= e(substr($report['title'], 0, 35)) ?></td>
                            <td style="font-size:12px"><?= e($report['cat_name'] ?? '—') ?></td>
                            <td><?= sevBadge($report['severity']) ?></td>
                            <td><?= statusBadge($report['status']) ?></td>
                            <td style="font-size:12px;color:var(--mu)"><?= e($report['aname'] ?? 'Unassigned') ?></td>
                            <td style="font-size:12px;color:var(--mu)"><?= e(substr($report['created_at'], 0, 10)) ?></td>
                            <td><a href="<?= BASE_URL ?>/user/view-report.php?id=<?= (int) $report['id'] ?>" class="btn btn-gy btn-sm">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align:center;padding:40px;color:var(--mu)">
            <div style="font-size:36px;margin-bottom:10px">📭</div>
            <p>No reports found. <a href="<?= BASE_URL ?>/user/report.php">Submit one →</a></p>
        </div>
    <?php endif; ?>
</div>

<?php pageEnd(); ?>