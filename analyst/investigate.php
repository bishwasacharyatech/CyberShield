<?php
require_once '../includes/config.php';
requireAuth('analyst');
require_once '../includes/layout.php';

$db = getDB();
$uid = $_SESSION['uid'];
$id = (int) ($_GET['id'] ?? 0);
$msg = '';
$err = '';

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

// Sequential status flow — only the NEXT step is allowed
$statusFlow = ['New', 'Assigned', 'Under Review', 'In Progress', 'Resolved', 'Closed'];
$idx = array_search($report['status'], $statusFlow, true);

$allowedStatuses = [];
if ($idx !== false && $idx < count($statusFlow) - 1) {
    $next = $statusFlow[$idx + 1];
    if ($next === 'Assigned') $next = 'Under Review';
    $allowedStatuses = [$next];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status  = $_POST['status'] ?? $report['status'];
    $remarks = trim($_POST['remarks'] ?? '');

    if (!in_array($status, $allowedStatuses, true)) {
        $err = 'Invalid status value.';
    } else {
        $oldStatus  = $report['status'];
        $oldRemarks = $report['analyst_remarks'] ?? '';

        $sql = "
            UPDATE reports
            SET status = ?,
                analyst_remarks = ?,
                assigned_to = ?,
                resolved_at = CASE
                    WHEN ? IN ('Resolved', 'Closed') AND resolved_at IS NULL THEN NOW()
                    WHEN ? NOT IN ('Resolved', 'Closed') THEN NULL
                    ELSE resolved_at
                END,
                updated_at = NOW()
            WHERE id = ?
        ";
        $update = $db->prepare($sql);
        $update->bind_param('ssissi', $status, $remarks, $uid, $status, $status, $id);
        $update->execute();

        if ($status !== $oldStatus || $remarks !== $oldRemarks) {
            $note = $remarks !== '' ? $remarks : "Status updated to [{$status}]";
            $timeline = $db->prepare('INSERT INTO report_timeline (report_id, user_id, action, note) VALUES (?, ?, ?, ?)');
            $timeline->bind_param('iiss', $id, $uid, $status, $note);
            $timeline->execute();
        }

        addNotif($report['user_id'], $id, "Your report [{$report['ticket_no']}] status updated to: {$status}");

        auditLog('UPDATE', 'Report', "Updated status of #{$report['ticket_no']} to {$status}");

        $msg = 'Report updated!';

        $stmt->execute();
        $report = $stmt->get_result()->fetch_assoc();

        $idx = array_search($report['status'], $statusFlow, true);
        $allowedStatuses = [];
        if ($idx !== false && $idx < count($statusFlow) - 1) {
            $next = $statusFlow[$idx + 1];
            if ($next === 'Assigned') $next = 'Under Review';
            $allowedStatuses = [$next];
        }
    }
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

$timelineColors = [
    'Submitted'    => '#00d4ff',
    'Assigned'     => '#8b5cf6',
    'Under Review' => '#f59e0b',
    'In Progress'  => '#f97316',
    'Resolved'     => '#00e676',
    'Closed'       => '#64748b',
    'Updated'      => '#f472b6',
];

pageStart('Investigate', 'analyst');
sidebar('analyst', 'assigned');
?>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
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

<div class="gr-32">
    <div>
        <div class="card">
            <div class="ch"><span class="ct">📝 Report Details</span></div>
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
                        <a href="<?= UPLOAD_URL . e($file['stored_name']) ?>" target="_blank" rel="noopener noreferrer"
                            style="display:inline-flex;align-items:center;gap:6px;background:var(--bg3);border:1px solid var(--bd);border-radius:6px;padding:5px 10px;margin-right:6px;font-size:12px;color:var(--cy)">
                            📎 <?= e($file['original_name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 📝 REMARKS HISTORY -->
        <div class="card">
            <div class="ch">
                <span class="ct">📝 Remarks History</span>
                <?php
                $remarkEvents = array_filter($timeline, fn($e) =>
                    !empty($e['note']) && !in_array($e['action'], ['Submitted', 'Updated'], true)
                );
                ?>
                <span style="font-size:11px;color:var(--mu)"><?= count($remarkEvents) ?> entries</span>
            </div>
            <?php if ($remarkEvents): ?>
                <?php foreach ($remarkEvents as $event): ?>
                    <div style="padding:10px 0;border-bottom:1px solid var(--bd)">
                        <div style="font-weight:700;font-size:13px;color:var(--wh)"><?= e($event['action']) ?></div>
                        <div style="font-size:11px;color:var(--mu);font-family:monospace;margin-top:2px">
                            <?= e($event['created_at']) ?>
                            <?php if (!empty($event['un'])): ?> · <?= e($event['un']) ?><?php endif; ?>
                        </div>
                        <div style="margin-top:6px;font-size:13px;line-height:1.6;color:var(--tx);white-space:pre-wrap"><?= nl2br(e($event['note'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="color:var(--mu);font-size:13px;padding:8px 0">No remarks yet.</div>
            <?php endif; ?>
        </div>

        <!-- DYNAMIC UPDATE STATUS & REMARKS SECTION -->
        <div class="card">
            <?php
            $statusMeta = [
                'New' => ['icon' => '📩', 'title' => 'New Report', 'sub' => 'Awaiting initial review'],
                'Assigned' => ['icon' => '📋', 'title' => 'Assigned', 'sub' => 'Waiting for investigation to begin'],
                'Under Review' => ['icon' => '🔍', 'title' => 'Under Review', 'sub' => 'Actively investigating'],
                'In Progress' => ['icon' => '🔄', 'title' => 'In Progress', 'sub' => 'Working on resolution'],
                'Resolved' => ['icon' => '✅', 'title' => 'Resolved', 'sub' => 'Close the case when ready'],
                'Closed' => ['icon' => '🔒', 'title' => 'Closed', 'sub' => 'Case completed – no further action'],
            ];
            $meta = $statusMeta[$report['status']] ?? ['icon' => '📝', 'title' => 'Update Status', 'sub' => ''];
            ?>
            <div class="ch">
                <span class="ct"><?= $meta['icon'] ?> <?= $meta['title'] ?></span>
                <span style="font-size:11px;color:var(--mu)"><?= $meta['sub'] ?></span>
            </div>

            <?php if (empty($allowedStatuses)): ?>
                <div style="background:var(--bg3);border-radius:8px;padding:16px;margin-bottom:10px;border-left:3px solid var(--gr)">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
                        <span style="font-size:24px">✅</span>
                        <div>
                            <div style="font-weight:700;font-size:15px;color:var(--gr)">Case <?= e($report['status']) ?></div>
                            <div style="font-size:12px;color:var(--mu)">No further updates allowed — case is closed</div>
                        </div>
                    </div>
                    <div style="margin-top:10px;font-size:12px;color:var(--mu);text-align:center;border-top:1px solid var(--bd);padding-top:10px">
                        💡 To reopen this case, please contact the Administrator.
                    </div>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="fg">
                        <label class="fl">Next Status</label>
                        <select name="status" class="fi">
                            <?php foreach ($allowedStatuses as $status): ?>
                                <option value="<?= e($status) ?>"><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg">
                        <label class="fl">Analyst Remarks</label>
                        <textarea name="remarks" class="fi" placeholder="Add investigation findings and recommendations..."
                            style="min-height:130px"></textarea>
                    </div>
                    <button type="submit" class="btn btn-cy">💾 Save Update</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="ch"><span class="ct">⏳ Case Timeline</span></div>
        <?php $total = count($timeline); ?>
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
                    <div style="font-weight:600;font-size:13px;color:var(--wh)">
                        <?= e($event['action']) ?>
                        <?php if ($isCurrent): ?>
                            <span style="font-size:10px;color:var(--gr);font-weight:400;margin-left:6px">← Current</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:11px;color:var(--mu);font-family:monospace"><?= e($event['created_at']) ?></div>
                    <?php if ($event['note']): ?>
                        <div style="font-size:12px;margin-top:4px;color:var(--tx);white-space:pre-wrap"><?= nl2br(e($event['note'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($i < $total - 1): ?>
                <div style="padding-left:9px;color:var(--mu);font-size:14px;line-height:1;opacity:0.6">↓</div>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$timeline): ?>
            <div style="color:var(--mu);font-size:12px">No timeline yet</div>
        <?php endif; ?>
    </div>
</div>

<div style="text-align:center;margin-top:20px">
    <a href="<?= BASE_URL ?>/export-single-report.php?id=<?= $id ?>" class="btn btn-gr">📄 View / Print Report</a>
</div>

<?php pageEnd(); ?>