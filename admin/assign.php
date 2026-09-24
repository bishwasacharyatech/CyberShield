<?php
require_once '../includes/config.php';
requireAuth('admin');
require_once '../includes/layout.php';

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);
$msg = '';
$err = '';

$validSeverities = ['Critical', 'High', 'Medium', 'Low'];

$stmt = $db->prepare("
    SELECT r.*, c.name AS cat_name, u.full_name AS uname
    FROM reports r
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

if (!$report) {
    header('Location: ' . BASE_URL . '/admin/reports.php');
    exit;
}

// Fetch timeline
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

// Fetch evidence
$evidenceStmt = $db->prepare("SELECT * FROM evidence_files WHERE report_id = ?");
$evidenceStmt->bind_param('i', $id);
$evidenceStmt->execute();
$evidence = $evidenceStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$analysts = $db->query("
    SELECT id, full_name
    FROM users
    WHERE role = 'analyst' AND status = 'active'
")->fetch_all(MYSQLI_ASSOC);

$isAssigned = !empty($report['assigned_to']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAssigned) {
    $analystId  = (int) ($_POST['analyst_id'] ?? 0);
    $severity   = $_POST['severity'] ?? $report['severity'];
    $adminNote  = trim($_POST['admin_remarks'] ?? '');
    $validAnalystIds = array_column($analysts, 'id');

    if (!in_array($severity, $validSeverities, true)) {
        $severity = $report['severity'];
    }

    if (!$analystId || !in_array($analystId, $validAnalystIds)) {
        $err = 'Please select a valid analyst.';
    } else {
        $oldSeverity = $report['severity'];

        $upd = $db->prepare("UPDATE reports SET assigned_to = ?, status = 'Assigned', severity = ?, analyst_remarks = ?, updated_at = NOW() WHERE id = ?");
        $upd->bind_param('issi', $analystId, $severity, $adminNote, $id);
        $upd->execute();

        $adminId = $_SESSION['uid'];
        $action = 'Assigned';
        $note = $adminNote !== '' ? $adminNote : "Assigned to analyst";
        if ($severity !== $oldSeverity) {
            $note .= "\nSeverity changed: {$oldSeverity} → {$severity}";
        }

        $timelineInsert = $db->prepare('INSERT INTO report_timeline (report_id, user_id, action, note) VALUES (?, ?, ?, ?)');
        $timelineInsert->bind_param('iiss', $id, $adminId, $action, $note);
        $timelineInsert->execute();

        addNotif($report['user_id'], $id, "Your report [{$report['ticket_no']}] has been assigned to an analyst.");
        addNotif($analystId, $id, "You have been assigned a new case: [{$report['ticket_no']}] {$report['title']}");

        // Audit log for assignment (was previously missing)
        auditLog(
            'ASSIGN',
            'Report',
            "Assigned #{$report['ticket_no']} to analyst ID {$analystId}" .
            ($severity !== $oldSeverity ? " (severity: {$oldSeverity} → {$severity})" : '')
        );

        $msg = 'Report assigned!';

        $stmt->execute();
        $report = $stmt->get_result()->fetch_assoc();
        $isAssigned = !empty($report['assigned_to']);

        $timelineStmt->execute();
        $timeline = $timelineStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

pageStart('Manage Report', 'admin');
sidebar('admin', 'assign');
?>

<div style="margin-bottom:20px">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px">
        <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-gy btn-sm">← Back to Reports</a>
        <div class="pg-title" style="margin:0;font-size:20px"><?= e($report['ticket_no']) ?></div>
        <?= statusBadge($report['status']) ?>
        <?= sevBadge($report['severity']) ?>
    </div>

    <div style="display:flex;gap:20px;flex-wrap:wrap;background:var(--bg3);padding:10px 16px;border-radius:6px;border:1px solid var(--bd);font-size:13px">
        <span><strong style="color:var(--wh)">Reported By:</strong> <?= e($report['uname']) ?></span>
        <span><strong style="color:var(--wh)">Category:</strong> <?= e($report['cat_name'] ?? '—') ?></span>
        <span><strong style="color:var(--wh)">Assigned To:</strong>
            <?php
            $assignedName = 'Unassigned';
            foreach ($analysts as $a) {
                if ($a['id'] == $report['assigned_to']) { $assignedName = $a['full_name']; break; }
            }
            echo e($assignedName);
            ?>
        </span>
        <span><strong style="color:var(--wh)">Updated:</strong> <?= e(substr($report['updated_at'] ?? $report['created_at'], 0, 16)) ?></span>
    </div>
</div>

<?php if ($msg): ?>
    <div class="flash-ok">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="flash-er">⚠ <?= e($err) ?></div>
<?php endif; ?>

<div class="card">
    <div class="ch">
        <span class="ct">📝 Incident Description</span>
        <span style="font-size:11px;color:var(--mu)"><?= e($report['incident_date']) ?></span>
    </div>
    <div style="font-size:14px;line-height:1.8;white-space:pre-wrap">
        <?= nl2br(e($report['description'])) ?>
    </div>
</div>

<?php if ($report['suspect_info']): ?>
    <div class="card" style="border-left:3px solid var(--am)">
        <div class="ch"><span class="ct">⚠️ Suspect Information</span></div>
        <div style="font-size:13px;line-height:1.7;white-space:pre-wrap;color:var(--am)">
            <?= nl2br(e($report['suspect_info'])) ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($evidence): ?>
    <div class="card">
        <div class="ch"><span class="ct">📎 Evidence Files</span></div>
        <div style="display:flex;flex-wrap:wrap;gap:8px">
            <?php foreach ($evidence as $file): ?>
                <a href="<?= UPLOAD_URL . e($file['stored_name']) ?>" target="_blank" rel="noopener noreferrer"
                   style="display:inline-flex;align-items:center;gap:8px;background:var(--bg3);border:1px solid var(--bd);border-radius:6px;padding:8px 14px;font-size:13px;color:var(--cy);text-decoration:none">
                    📎 <?= e($file['original_name']) ?>
                    <span style="font-size:11px;color:var(--mu)">(<?= number_format($file['file_size'] / 1024, 1) ?> KB)</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($report['analyst_remarks'])): ?>
    <div class="card" style="border-left:3px solid var(--pu)">
        <div class="ch"><span class="ct">📝 Current Analyst Remarks</span></div>
        <div style="font-size:13px;line-height:1.7;white-space:pre-wrap">
            <?= nl2br(e($report['analyst_remarks'])) ?>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="ch">
        <span class="ct">⏳ Investigation Timeline</span>
        <span style="font-size:11px;color:var(--mu)"><?= count($timeline) ?> events</span>
    </div>
    <?php
    $timelineColors = [
        'Submitted'    => '#00d4ff',
        'Assigned'     => '#8b5cf6',
        'Under Review' => '#f59e0b',
        'In Progress'  => '#f97316',
        'Resolved'     => '#00e676',
        'Closed'       => '#64748b',
        'Updated'      => '#f472b6',
    ];
    $total = count($timeline);
    ?>
    <?php foreach ($timeline as $i => $event): ?>
        <?php
        $color = $timelineColors[$event['action']] ?? '#4a6a88';
        $isCurrent = ($event['action'] == $report['status']);
        ?>
        <div style="display:flex;gap:10px;padding:6px 0;align-items:flex-start">
            <div style="width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;
                        background:<?= $color ?>18;color:<?= $color ?>;border:2px solid <?= $color ?>44;font-size:10px">
                <?= $isCurrent ? '●' : '○' ?>
            </div>
            <div style="flex:1">
                <div style="font-weight:600;font-size:14px;color:var(--wh)">
                    <?= e($event['action']) ?>
                    <?php if ($isCurrent): ?>
                        <span style="font-size:10px;color:var(--gr);font-weight:400;margin-left:6px">← Current</span>
                    <?php endif; ?>
                    <?php if ($event['un']): ?>
                        <span style="font-size:12px;color:var(--cy);font-weight:400;margin-left:6px">— <?= e($event['un']) ?></span>
                    <?php endif; ?>
                </div>
                <div style="font-size:12px;color:var(--mu);font-family:monospace"><?= e($event['created_at']) ?></div>
                <?php if ($event['note']): ?>
                    <div style="font-size:12px;color:var(--mu);margin-top:4px;white-space:pre-wrap"><?= nl2br(e($event['note'])) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($i < $total - 1): ?>
            <div style="padding-left:9px;color:var(--mu);font-size:14px;line-height:1;opacity:0.6">↓</div>
        <?php endif; ?>
    <?php endforeach; ?>
    <?php if (!$timeline): ?>
        <div style="color:var(--mu);font-size:13px;padding:8px 0">No timeline events yet.</div>
    <?php endif; ?>
</div>

<?php if (!$isAssigned): ?>
    <div class="card">
        <div class="ch"><span class="ct">✅ Assign to Analyst</span></div>
        <form method="POST" style="max-width:480px">
            <div class="fg">
                <label class="fl">Select Analyst</label>
                <select name="analyst_id" class="fi" required>
                    <option value="">-- Choose an analyst --</option>
                    <?php foreach ($analysts as $analyst): ?>
                        <option value="<?= $analyst['id'] ?>"><?= e($analyst['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="fg">
                <label class="fl">Severity</label>
                <select name="severity" class="fi" required>
                    <?php foreach ($validSeverities as $sev): ?>
                        <option value="<?= $sev ?>" <?= $report['severity'] === $sev ? 'selected' : '' ?>>
                            <?= $sev ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size:11px;color:var(--mu);margin-top:4px">
                    Triage priority — Critical / High / Medium / Low.
                    <?php if (!empty($report['severity'])): ?>
                        Currently: <strong style="color:var(--am)"><?= e($report['severity']) ?></strong>
                    <?php endif; ?>
                </div>
            </div>

            <div class="fg">
                <label class="fl">Assignment Remarks</label>
                <textarea name="admin_remarks" class="fi" style="min-height:100px"
                          placeholder="Add a note for the analyst..."></textarea>
            </div>
            <button type="submit" class="btn btn-cy">💾 Save Assignment</button>
        </form>
    </div>
<?php else: ?>
    <div class="card">
        <div class="ch"><span class="ct">✅ Assignment Locked</span></div>
        <div style="font-size:13px;color:var(--mu);line-height:1.7">
            This report has been assigned to
            <strong style="color:var(--wh)"><?= e($assignedName) ?></strong>
            with severity
            <?= sevBadge($report['severity']) ?>.
            Further updates can only be made by the assigned analyst.
        </div>
    </div>
<?php endif; ?>

<div style="text-align:center;margin-top:20px">
    <a href="<?= BASE_URL ?>/export-single-report.php?id=<?= $id ?>" class="btn btn-gr">📄 View / Print Report</a>
</div>
<?php pageEnd(); ?>