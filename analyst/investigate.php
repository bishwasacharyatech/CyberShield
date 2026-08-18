<?php
require_once '../includes/config.php';
requireAuth('analyst');
require_once '../includes/layout.php';

$db = getDB();
$uid = $_SESSION['uid'];
$id = (int) ($_GET['id'] ?? 0);
$msg = '';

$stmt = $db->prepare("
    SELECT r.*, c.name AS cat_name, u.full_name AS uname, u.email AS uemail
    FROM reports r
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.id = ? AND r.assigned_to = ?
");
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

if (!$report) {
    header('Location: ' . BASE_URL . '/analyst/assigned.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status  = $_POST['status'] ?? $report['status'];
    $remarks = trim($_POST['remarks'] ?? '');

    $update = $db->prepare('UPDATE reports SET status = ?, analyst_remarks = ?, assigned_to = ?, updated_at = NOW() WHERE id = ?');
    $update->bind_param('ssii', $status, $remarks, $uid, $id);
    $update->execute();

    $note = "Status updated to [{$status}] by " . $_SESSION['fname'];
    $timeline = $db->prepare('INSERT INTO report_timeline (report_id, user_id, action, note) VALUES (?, ?, ?, ?)');
    $timeline->bind_param('iiss', $id, $uid, $status, $note);
    $timeline->execute();

    addNotif($report['user_id'], $id, "Your report [{$report['ticket_no']}] status updated to: {$status}");

    $msg = 'Report updated!';

    $stmt->execute();
    $report = $stmt->get_result()->fetch_assoc();
}

$timelineStmt = $db->prepare("
    SELECT t.*, u.full_name AS un
    FROM report_timeline t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.report_id = ?
    ORDER BY t.id ASC
");
$timelineStmt->bind_param('i', $id);
$timelineStmt->execute();
$timeline = $timelineStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$evidenceStmt = $db->prepare("SELECT * FROM evidence_files WHERE report_id = ?");
$evidenceStmt->bind_param('i', $id);
$evidenceStmt->execute();
$evidence = $evidenceStmt->get_result()->fetch_all(MYSQLI_ASSOC);

pageStart('Investigate', 'analyst');
sidebar('analyst', 'assigned');
?>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
    <a href="<?= BASE_URL ?>/analyst/assigned.php" class="btn btn-gy btn-sm">← Back</a>
    <div class="pg-title" style="margin:0"><?= e($report['ticket_no']) ?></div>
    <?= statusBadge($report['status']) ?>
    <?= sevBadge($report['severity']) ?>
</div>

<?php if ($msg): ?>
    <div class="flash-ok">✅ <?= e($msg) ?></div>
<?php endif; ?>

<div class="gr-32">
    <div>
        <div class="card">
            <div class="ch"><span class="ct"><i class="ti ti-file-description"></i> Report Details</span></div>
            <?php
            $detailFields = [
                ['Ticket', $report['ticket_no'], 'var(--cy)'],
                ['Title', $report['title'], 'var(--wh)'],
                ['Category', $report['cat_name'] ?? '—', null],
                ['Reporter', $report['uname'] . ' (' . $report['uemail'] . ')', null],
                ['Incident Date', $report['incident_date'], null],
                ['Submitted', substr($report['created_at'], 0, 16), 'var(--mu)']
            ];
            foreach ($detailFields as [$label, $value, $color]): ?>
                <div style="display:flex;padding:8px 0;border-bottom:1px solid var(--bd)">
                    <span style="color:var(--mu);font-size:12px;min-width:120px;flex-shrink:0"><?= $label ?></span>
                    <span style="<?= $color ? "color:{$color}" : '' ?>"><?= e($value) ?></span>
                </div>
            <?php endforeach; ?>
            <div style="margin-top:14px">
                <div style="font-size:11px;color:var(--mu);margin-bottom:6px">Description</div>
                <div style="font-size:14px;line-height:1.7"><?= nl2br(e($report['description'])) ?></div>
            </div>
            <?php if ($report['suspect_info']): ?>
                <div style="margin-top:14px">
                    <div style="font-size:11px;color:var(--am);margin-bottom:6px">⚠ Suspect Info</div>
                    <div style="color:var(--am)"><?= nl2br(e($report['suspect_info'])) ?></div>
                </div>
            <?php endif; ?>
            <?php if ($evidence): ?>
                <div style="margin-top:14px">
                    <div style="font-size:11px;color:var(--mu);margin-bottom:6px">📎 Evidence Files</div>
                    <?php foreach ($evidence as $file): ?>
                        <a href="<?= UPLOAD_URL . e($file['stored_name']) ?>" target="_blank"
                           style="display:inline-flex;align-items:center;gap:6px;background:var(--bg3);border:1px solid var(--bd);border-radius:6px;padding:5px 10px;margin-right:6px;font-size:12px;color:var(--cy)">
                            📎 <?= e($file['original_name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="ch"><span class="ct"><i class="ti ti-edit"></i> Update Status & Remarks</span></div>
            <form method="POST">
                <div class="fg">
                    <label class="fl">Update Status</label>
                    <select name="status" class="fi">
                        <?php foreach (['New', 'Assigned', 'Under Review', 'In Progress', 'Resolved', 'Closed'] as $status): ?>
                            <option <?= $report['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label class="fl">Analyst Remarks</label>
                    <textarea name="remarks" class="fi" placeholder="Add investigation findings and recommendations..." style="min-height:130px"><?= e($report['analyst_remarks'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-cy">💾 Save Update</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="ch"><span class="ct"><i class="ti ti-timeline"></i> Case Timeline</span></div>
        <?php
        $timelineColors = [
            'Submitted' => '#00d4ff', 'Assigned' => '#8b5cf6', 'Under Review' => '#f59e0b',
            'In Progress' => '#f97316', 'Resolved' => '#00e676', 'Closed' => '#64748b'
        ];
        ?>
        <?php foreach ($timeline as $event): ?>
            <?php $color = $timelineColors[$event['action']] ?? '#4a6a88'; ?>
            <div class="tl-item">
                <div class="tl-dot" style="background:<?= $color ?>18;color:<?= $color ?>;border:2px solid <?= $color ?>44;font-size:10px">●</div>
                <div style="flex:1">
                    <div style="font-weight:600;font-size:13px;color:var(--wh)"><?= e($event['action']) ?></div>
                    <div style="font-size:11px;color:var(--mu);font-family:monospace"><?= e($event['created_at']) ?></div>
                    <?php if ($event['note']): ?>
                        <div style="font-size:12px;margin-top:2px"><?= e($event['note']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$timeline): ?>
            <div style="color:var(--mu);font-size:12px">No timeline yet</div>
        <?php endif; ?>
    </div>
</div>

<?php pageEnd(); ?>