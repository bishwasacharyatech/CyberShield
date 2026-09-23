<?php
require_once 'includes/config.php';
if (empty($_SESSION['uid'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$db = getDB();
$uid = $_SESSION['uid'];
$role = $_SESSION['role'];
$id = (int) ($_GET['id'] ?? 0);
if (!$id)
    die('Invalid report ID.');

$sql = "SELECT r.*, c.name AS cat_name, u.full_name AS uname, u.email AS uemail, a.full_name AS aname
        FROM reports r
        LEFT JOIN categories c ON r.category_id = c.id
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN users a ON r.assigned_to = a.id
        WHERE r.id = ?";
$params = [$id];
$types = 'i';
if ($role === 'user') {
    $sql .= " AND r.user_id = ?";
    $params[] = $uid;
    $types .= 'i';
} elseif ($role === 'analyst') {
    $sql .= " AND r.assigned_to = ?";
    $params[] = $uid;
    $types .= 'i';
}

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();
if (!$report)
    die('Report not found or access denied.');

$t = $db->prepare("SELECT t.*, u.full_name AS un FROM report_timeline t LEFT JOIN users u ON t.user_id = u.id WHERE t.report_id = ? ORDER BY t.id ASC");
$t->bind_param('i', $id);
$t->execute();
$timeline = $t->get_result()->fetch_all(MYSQLI_ASSOC);

$e = $db->prepare("SELECT * FROM evidence_files WHERE report_id = ?");
$e->bind_param('i', $id);
$e->execute();
$evidence = $e->get_result()->fetch_all(MYSQLI_ASSOC);

auditLog('VIEW', 'Export Report', "Exported report #{$report['ticket_no']} as HTML");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report <?= e($report['ticket_no']) ?> — CyberShield</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body>div {
                border: 1px solid #ccc !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>

<body>

    <div
        style="max-width:900px;margin:20px auto;padding:25px 35px;border:1px solid var(--bd);border-radius:12px;background:var(--bg2);box-shadow:0 4px 20px rgba(0,0,0,0.3)">

        <div class="no-print" style="display:flex;justify-content:space-between;gap:12px;margin-bottom:16px">
            <a href="javascript:history.back()" class="btn btn-gy">← Back</a>
            <button onclick="window.print()" class="btn btn-cy">🖨 Print / Save as PDF</button>
        </div>

        <div style="margin-bottom:20px">
            <div class="logo" style="font-size:22px;font-weight:800">
                <span class="c-cyber">Cyber</span><span class="c-shield">Shield</span>
            </div>
            <div class="brand-subtitle">Cybersecurity Incident Report</div>
        </div>

        <div class="pg-title" style="font-size:22px"><?= e($report['title']) ?></div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:20px">
            <span style="font-family:monospace;color:var(--cy);font-weight:700"><?= e($report['ticket_no']) ?></span>
            <?= statusBadge($report['status']) ?>
            <?= sevBadge($report['severity']) ?>
        </div>

        <div class="card">
            <div class="ch"><span class="ct">📋 Report Details</span></div>
            <div class="grid g3">
                <div class="fg">
                    <div class="fl">Category</div>
                    <div><?= e($report['cat_name'] ?? '—') ?></div>
                </div>
                <div class="fg">
                    <div class="fl">Incident Date</div>
                    <div><?= e($report['incident_date']) ?></div>
                </div>
                <div class="fg">
                    <div class="fl">Assigned Analyst</div>
                    <div><?= e($report['aname'] ?? 'Unassigned') ?></div>
                </div>
                <div class="fg" style="grid-column:span 2">
                    <div class="fl">Reported By</div>
                    <div><?= e($report['uname']) ?> (<?= e($report['uemail']) ?>)</div>
                </div>
                <div class="fg">
                    <div class="fl">Submitted At</div>
                    <div><?= e($report['created_at']) ?></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="ch"><span class="ct">📝 Incident Description</span></div>
            <div style="font-size:14px;line-height:1.7"><?= nl2br(e($report['description'])) ?></div>
        </div>

        <?php if (!empty($report['suspect_info'])): ?>
            <div class="card" style="border-left:3px solid var(--am)">
                <div class="ch"><span class="ct">⚠ Suspect Information</span></div>
                <div style="color:var(--am);line-height:1.7"><?= nl2br(e($report['suspect_info'])) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($report['analyst_remarks'])): ?>
            <div class="card" style="border-left:3px solid var(--pu)">
                <div class="ch"><span class="ct">🔍 Analyst Remarks</span></div>
                <div style="line-height:1.7"><?= nl2br(e($report['analyst_remarks'])) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($evidence): ?>
            <div class="card">
                <div class="ch"><span class="ct">📎 Evidence Files</span></div>
                <div class="tw">
                    <table>
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Size</th>
                                <th>Uploaded At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evidence as $f): ?>
                                <tr>
                                    <td><?= e($f['original_name']) ?></td>
                                    <td><?= number_format($f['file_size'] / 1024, 1) ?> KB</td>
                                    <td><?= e($f['uploaded_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($timeline): ?>
            <div class="card">
                <div class="ch"><span class="ct">⏳ Investigation Timeline</span></div>
                <?php
                $tc = ['Submitted' => '#00d4ff', 'Assigned' => '#8b5cf6', 'Under Review' => '#f59e0b', 'In Progress' => '#f97316', 'Resolved' => '#00e676', 'Closed' => '#64748b', 'Updated' => '#f472b6'];
                foreach ($timeline as $ev):
                    $c = $tc[$ev['action']] ?? '#4a6a88';
                    ?>
                    <div class="tl-item">
                        <div class="tl-dot" style="background:<?= $c ?>18;color:<?= $c ?>;border:2px solid <?= $c ?>44">●</div>
                        <div style="flex:1">
                            <div style="font-weight:600;font-size:13px;color:var(--wh)">
                                <?= e($ev['action']) ?>
                                <?php if (!empty($ev['un'])): ?><span style="color:var(--cy);font-weight:400"> —
                                        <?= e($ev['un']) ?></span><?php endif; ?>
                            </div>
                            <div style="font-size:11px;color:var(--mu);font-family:monospace"><?= e($ev['created_at']) ?></div>
                            <?php if (!empty($ev['note'])): ?>
                                <div style="font-size:12px;color:var(--mu);margin-top:2px"><?= e($ev['note']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="global-footer">
            <p>Generated on <?= date('F d, Y \a\t H:i') ?> — CyberShield Incident Management System</p>
        </div>

    </div>

</body>

</html>