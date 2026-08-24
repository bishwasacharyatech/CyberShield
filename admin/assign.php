<?php
require_once '../includes/config.php';
requireAuth('admin');
require_once '../includes/layout.php';

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);
$msg = '';
$err = '';


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

        // ============================================================
        // FIX 2: Use prepared statement for refresh query
        // ============================================================
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

        $timelineStmt->execute();
        $timeline = $timelineStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

pageStart('Manage Report', 'admin');
sidebar('admin', 'reports');
?>

<!-- ============================================================ -->
<!-- HEADER WITH QUICK STATUS                                       -->
<!-- ============================================================ -->
<div style="margin-bottom:20px">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px">
        <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-gy btn-sm">← Back to Reports</a>
        <div class="pg-title" style="margin:0;font-size:20px"><?= e($report['ticket_no']) ?></div>
        <?= statusBadge($report['status']) ?>
        <?= sevBadge($report['severity']) ?>
    </div>

    <!-- Quick status bar -->
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

<!-- ============================================================ -->
<!-- MAIN CONTENT: Full-width, clean cards                        -->
<!-- ============================================================ -->

<!-- 1. REPORT DESCRIPTION -->
<div class="card">
    <div class="ch">
        <span class="ct">📝 Incident Description</span>
        <span style="font-size:11px;color:var(--mu)"><?= e($report['incident_date']) ?></span>
    </div>
    <div style="font-size:14px;line-height:1.8;white-space:pre-wrap">
        <?= nl2br(e($report['description'])) ?>
    </div>
</div>

<!-- 2. SUSPECT INFO (if any) -->
<?php if ($report['suspect_info']): ?>
    <div class="card" style="border-left:3px solid var(--am)">
        <div class="ch"><span class="ct">⚠️ Suspect Information</span></div>
        <div style="font-size:13px;line-height:1.7;white-space:pre-wrap;color:var(--am)">
            <?= nl2br(e($report['suspect_info'])) ?>
        </div>
    </div>
<?php endif; ?>

<!-- 3. EVIDENCE FILES (if any) -->
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

<!-- 4. ANALYST REMARKS (if any) -->
<?php if (!empty($report['analyst_remarks'])): ?>
    <div class="card" style="border-left:3px solid var(--pu)">
        <div class="ch"><span class="ct">📝 Analyst Remarks</span></div>
        <div style="font-size:13px;line-height:1.7;white-space:pre-wrap">
            <?= nl2br(e($report['analyst_remarks'])) ?>
        </div>
    </div>
<?php endif; ?>

<!-- 5. INVESTIGATION TIMELINE -->
<div class="card">
    <div class="ch">
        <span class="ct">⏳ Investigation Timeline</span>
        <span style="font-size:11px;color:var(--mu)"><?= count($timeline) ?> events</span>
    </div>
    <?php if ($timeline): ?>
        <div style="position:relative;padding-left:24px">
            <!-- Vertical line -->
            <div style="position:absolute;left:7px;top:8px;bottom:8px;width:2px;background:var(--bd)"></div>

            <?php foreach ($timeline as $event): ?>
                <?php
                $colors = [
                    'Submitted' => '#00d4ff',
                    'Assigned' => '#8b5cf6',
                    'Under Review' => '#f59e0b',
                    'In Progress' => '#f97316',
                    'Resolved' => '#00e676',
                    'Closed' => '#64748b'
                ];
                $color = $colors[$event['action']] ?? '#4a6a88';
                ?>
                <div style="position:relative;padding-bottom:16px;padding-left:16px;border-left:2px solid <?= $color ?>33">
                    <!-- Dot -->
                    <div style="position:absolute;left:-7px;top:2px;width:12px;height:12px;border-radius:50%;background:<?= $color ?>;border:2px solid var(--bg2)"></div>

                    <div style="display:flex;flex-wrap:wrap;gap:4px 16px;align-items:baseline">
                        <span style="font-weight:600;font-size:14px;color:var(--wh)"><?= e($event['action']) ?></span>
                        <span style="font-size:12px;color:var(--mu);font-family:monospace"><?= e($event['created_at']) ?></span>
                        <?php if ($event['un']): ?>
                            <span style="font-size:12px;color:var(--cy)">— <?= e($event['un']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($event['note']): ?>
                        <div style="font-size:12px;color:var(--mu);margin-top:2px"><?= e($event['note']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="color:var(--mu);font-size:13px;padding:8px 0">No timeline events yet.</div>
    <?php endif; ?>
</div>

<!-- 6. ASSIGN & UPDATE FORM -->
<div class="card">
    <div class="ch"><span class="ct">✅ Assign & Update</span></div>
    <form method="POST" style="max-width:480px">
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

<?php pageEnd(); ?>