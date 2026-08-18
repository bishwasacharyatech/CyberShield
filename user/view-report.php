<?php
require_once '../includes/config.php';
requireAuth('user');
require_once '../includes/layout.php';

$db = getDB();
$uid = $_SESSION['uid'];
$id  = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT r.*, c.name AS cat_name, u.full_name AS uname, a.full_name AS aname
    FROM reports r
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN users a ON r.assigned_to = a.id
    WHERE r.id = ? AND r.user_id = ?
");
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

if (!$report) {
    header('Location: ' . BASE_URL . '/user/my-reports.php');
    exit;
}

auditLog('VIEW', 'Report', "Viewed report #{$report['ticket_no']}");

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

$markRead = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND report_id = ?");
$markRead->bind_param('ii', $uid, $id);
$markRead->execute();

pageStart('View Report', 'user');
sidebar('user', 'my-reports');
?>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
    <a href="<?= BASE_URL ?>/user/my-reports.php" class="btn btn-gy btn-sm">← Back</a>
    <div class="pg-title" style="margin:0"><?= e($report['ticket_no']) ?></div>
    <?= statusBadge($report['status']) ?>
    <?= sevBadge($report['severity']) ?>
</div>

<div class="gr-23">
    <div>
        <div class="card">
            <div class="ch"><span class="ct"><i class="ti ti-file-description"></i> Report Details</span></div>
            <?php
            $detailFields = [
                ['Title', $report['title'], 'var(--wh)', true],
                ['Category', $report['cat_name'] ?? '—', null, false],
                ['Incident Date', $report['incident_date'], null, false],
                ['Submitted By', $report['uname'], null, false],
                ['Assigned Analyst', $report['aname'] ?? 'Not assigned', null, false],
                ['Submitted', substr($report['created_at'], 0, 16), 'var(--mu)', false]
            ];
            foreach ($detailFields as [$label, $value, $color, $bold]): ?>
                <div style="display:flex;padding:8px 0;border-bottom:1px solid var(--bd)">
                    <span style="color:var(--mu);font-size:12px;min-width:130px;flex-shrink:0"><?= $label ?></span>
                    <span style="<?= $color ? "color:{$color};" : '' ?><?= $bold ? 'font-weight:600;' : '' ?>"><?= e($value) ?></span>
                </div>
            <?php endforeach; ?>
            <div style="margin-top:14px">
                <div style="font-size:11px;color:var(--mu);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Description</div>
                <div style="font-size:14px;line-height:1.7"><?= nl2br(e($report['description'])) ?></div>
            </div>
            <?php if ($report['suspect_info']): ?>
                <div style="margin-top:14px">
                    <div style="font-size:11px;color:var(--am);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">⚠ Suspect Info</div>
                    <div style="color:var(--am);line-height:1.7"><?= nl2br(e($report['suspect_info'])) ?></div>
                </div>
            <?php endif; ?>
            <?php if ($report['analyst_remarks']): ?>
                <div style="margin-top:14px">
                    <div style="font-size:11px;color:var(--pu);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">🔍 Analyst Remarks</div>
                    <div style="background:rgba(139,92,246,.06);border:1px solid rgba(139,92,246,.2);border-radius:8px;padding:12px;line-height:1.7">
                        <?= nl2br(e($report['analyst_remarks'])) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($evidence): ?>
            <div class="card">
                <div class="ch"><span class="ct"><i class="ti ti-paperclip"></i> Evidence Files</span></div>
                <?php foreach ($evidence as $file): ?>
                    <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--bd)">
                        <span style="font-size:20px">📎</span>
                        <div style="flex:1">
                            <div style="font-size:13px;color:var(--wh)"><?= e($file['original_name']) ?></div>
                            <div style="font-size:11px;color:var(--mu)">
                                <?= number_format($file['file_size'] / 1024, 1) ?> KB — <?= e(substr($file['uploaded_at'], 0, 16)) ?>
                            </div>
                        </div>
                        <a href="<?= UPLOAD_URL . e($file['stored_name']) ?>" class="btn btn-gy btn-sm" target="_blank">Download</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="ch"><span class="ct"><i class="ti ti-timeline"></i> Timeline</span></div>
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