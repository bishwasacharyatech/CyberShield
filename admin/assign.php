<?php
require_once '../includes/config.php';
requireAuth('admin');
require_once '../includes/layout.php';

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);
$msg = '';
$err = '';

$report = $db->query("
    SELECT r.*, c.name AS cat_name, u.full_name AS uname
    FROM reports r
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.id = {$id}
")->fetch_assoc();

if (!$report) {
    header('Location: ' . BASE_URL . '/admin/reports.php');
    exit;
}

$analysts = $db->query("
    SELECT id, full_name
    FROM users
    WHERE role = 'analyst' AND status = 'active'
")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $analystId = (int) ($_POST['analyst_id'] ?? 0);
    $severity  = $_POST['severity'] ?? '';
    $status    = $_POST['status'] ?? '';

    $validAnalystIds = array_column($analysts, 'id');
    $validSeverities = ['Critical', 'High', 'Medium', 'Low'];
    $validStatuses   = ['New', 'Assigned', 'Under Review', 'In Progress', 'Resolved', 'Closed'];

    if ($analystId && !in_array($analystId, $validAnalystIds)) {
        $err = 'Invalid analyst selected.';
    } elseif (!in_array($severity, $validSeverities) || !in_array($status, $validStatuses)) {
        $err = 'Invalid severity or status.';
    } else {
        $assignedTo = $analystId ?: null;
        $stmt = $db->prepare('UPDATE reports SET assigned_to = ?, severity = ?, status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->bind_param('issi', $assignedTo, $severity, $status, $id);
        $stmt->execute();

        if ($analystId) {
            $timeline = $db->prepare('INSERT INTO report_timeline (report_id, user_id, action, note) VALUES (?, ?, ?, ?)');
            $adminId = $_SESSION['uid'];
            $action = 'Assigned';
            $note = 'Assigned by Admin';
            $timeline->bind_param('iiss', $id, $adminId, $action, $note);
            $timeline->execute();

            addNotif($report['user_id'], $id, "Your report [{$report['ticket_no']}] has been assigned to an analyst.");
            addNotif($analystId, $id, "You have been assigned a new case: [{$report['ticket_no']}] {$report['title']}");
        }

        $msg = 'Report updated!';

        $report = $db->query("
            SELECT r.*, c.name AS cat_name, u.full_name AS uname
            FROM reports r
            LEFT JOIN categories c ON r.category_id = c.id
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.id = {$id}
        ")->fetch_assoc();
    }
}

pageStart('Manage Report', 'admin');
sidebar('admin', 'reports');
?>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
    <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-gy btn-sm">← Back</a>
    <div class="pg-title" style="margin:0"><?= e($report['ticket_no']) ?></div>
    <?= statusBadge($report['status']) ?>
    <?= sevBadge($report['severity']) ?>
</div>

<?php if ($msg): ?>
    <div class="flash-ok">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="flash-er">⚠ <?= e($err) ?></div>
<?php endif; ?>

<div class="gr-22">
    <div class="card">
        <div class="ch"><span class="ct"><i class="ti ti-file-description"></i> Report Info</span></div>
        <?php
        $infoFields = [
            ['Reported By', $report['uname']],
            ['Category', $report['cat_name'] ?? '—'],
            ['Incident Date', $report['incident_date']],
            ['Submitted', substr($report['created_at'], 0, 16)]
        ];
        foreach ($infoFields as [$label, $value]): ?>
            <div style="display:flex;padding:8px 0;border-bottom:1px solid var(--bd)">
                <span style="color:var(--mu);font-size:12px;min-width:120px"><?= $label ?></span>
                <span><?= e($value) ?></span>
            </div>
        <?php endforeach; ?>
        <div style="margin-top:12px;font-size:13px;line-height:1.7">
            <?= nl2br(e(substr($report['description'], 0, 400))) ?>
        </div>
    </div>

    <div class="card">
        <div class="ch"><span class="ct"><i class="ti ti-user-check"></i> Assign & Update</span></div>
        <form method="POST">
            <div class="fg">
                <label class="fl">Assign To Analyst</label>
                <select name="analyst_id" class="fi">
                    <option value="0">-- Unassigned --</option>
                    <?php foreach ($analysts as $analyst): ?>
                        <option value="<?= $analyst['id'] ?>" <?= $report['assigned_to'] == $analyst['id'] ? 'selected' : '' ?>>
                            <?= e($analyst['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label class="fl">Severity</label>
                <select name="severity" class="fi">
                    <?php foreach (['Critical', 'High', 'Medium', 'Low'] as $s): ?>
                        <option <?= $report['severity'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label class="fl">Status</label>
                <select name="status" class="fi">
                    <?php foreach (['New', 'Assigned', 'Under Review', 'In Progress', 'Resolved', 'Closed'] as $s): ?>
                        <option <?= $report['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-cy">💾 Save Changes</button>
        </form>
    </div>
</div>

<?php pageEnd(); ?>