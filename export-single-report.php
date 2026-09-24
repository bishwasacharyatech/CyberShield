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

$remarkEvents = array_filter($timeline, fn($ev) =>
    !empty($ev['note']) && !in_array($ev['action'], ['Submitted', 'Updated'], true)
);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report <?= e($report['ticket_no']) ?> — CyberShield</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
    <style>
        .report-wrap { max-width: 900px; margin: 20px auto; padding: 0 20px; }
        .paper {
            border: 1px solid var(--bd);
            border-radius: 6px;
            padding: 32px 40px;
            background: var(--bg2);
        }
        .report-header { text-align: center; padding-bottom: 14px; margin-bottom: 18px; }
        .report-header .r-title { font-size: 20px; font-weight: 800; color: var(--wh); margin-top: 6px; }
        .report-header .r-sub { font-size: 11px; letter-spacing: 2px; text-transform: uppercase; color: var(--mu); }
        .report-header .r-ticket { font-family: monospace; font-size: 12px; color: var(--cy); margin-top: 4px; }
        .paper h2 { font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: var(--mu); margin: 22px 0 8px; }
        .paper p { text-align: justify; text-justify: inter-word; margin-bottom: 6px; line-height: 1.6; font-size: 13px; }
        @media print {
            body { background: #fff !important; color: #000 !important; }
            .no-print { display: none !important; }
            .paper { background: #fff !important; border: 1px solid #000 !important; color: #000 !important; }
            .paper h2 { color: #333 !important; }
            .report-header .r-title { color: #000 !important; }
            .report-header .r-sub,
            .report-header .r-ticket { color: #333 !important; }
        }
    </style>
</head>

<body>

    <div class="report-wrap">

        <div class="no-print" style="display:flex;justify-content:space-between;gap:12px;margin-bottom:14px">
            <a href="javascript:history.back()" class="btn btn-gy">← Back</a>
            <button onclick="window.print()" class="btn btn-cy">🖨 Print / Save as PDF</button>
        </div>

        <div class="paper">

            <!-- HEADER -->
            <div class="report-header">
                <div class="logo" style="font-size:22px;font-weight:800">
                    <span class="c-cyber">Cyber</span><span class="c-shield">Shield</span>
                </div>
                <div class="r-sub">Cybersecurity Incident Report</div>
                <div class="r-ticket"><?= e($report['ticket_no']) ?></div>
                <div style="display:flex;gap:8px;justify-content:center;margin-top:6px">
                    <?= statusBadge($report['status']) ?>
                    <?= sevBadge($report['severity']) ?>
                </div>
                <div class="r-title"><?= e($report['title']) ?></div>
            </div>

            <!-- 1. REPORT INFORMATION -->
            <h2>1. Report Information</h2>
            <?php
            $infoRows = [
                ['Ticket Number',    $report['ticket_no']],
                ['Category',         $report['cat_name'] ?? '—'],
                ['Reported By',      $report['uname'] . ' <' . $report['uemail'] . '>'],
                ['Assigned Analyst', $report['aname'] ?? 'Unassigned'],
                ['Incident Date',    $report['incident_date']],
                ['Submitted At',     $report['created_at']],
            ];
            if ($report['updated_at'] && $report['updated_at'] !== $report['created_at']) {
                $infoRows[] = ['Last Updated', $report['updated_at']];
            }
            if ($report['resolved_at']) {
                $infoRows[] = ['Resolved At', $report['resolved_at']];
            }
            foreach ($infoRows as [$label, $value]): ?>
                <div style="display:flex;padding:4px 0;font-size:13px">
                    <span style="color:var(--mu);min-width:160px;text-transform:uppercase;font-size:11px;font-weight:600;letter-spacing:.5px"><?= $label ?></span>
                    <span><?= e($value) ?></span>
                </div>
            <?php endforeach; ?>

            <!-- 2. INCIDENT DESCRIPTION -->
            <h2>2. Incident Description</h2>
            <?php foreach (preg_split('/\n\s*\n/', trim($report['description'])) as $p): ?>
                <p><?= e(trim(preg_replace('/\s*\n\s*/', ' ', $p))) ?></p>
            <?php endforeach; ?>

            <!-- 3. SUSPECT INFORMATION -->
            <?php if (!empty($report['suspect_info'])): ?>
                <h2>3. Suspect Information</h2>
                <?php foreach (preg_split('/\n\s*\n/', trim($report['suspect_info'])) as $p): ?>
                    <p><?= e(trim(preg_replace('/\s*\n\s*/', ' ', $p))) ?></p>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- 4. REMARKS HISTORY -->
            <?php if ($remarkEvents): ?>
                <h2>4. Remarks History</h2>
                <?php foreach ($remarkEvents as $ev): ?>
                    <div style="margin-bottom:12px">
                        <div style="font-weight:700;font-size:13px;color:var(--wh)"><?= e($ev['action']) ?></div>
                        <div style="font-size:11px;color:var(--mu);font-family:monospace;margin-top:2px">
                            <?= e($ev['created_at']) ?>
                            <?php if (!empty($ev['un'])): ?> · <?= e($ev['un']) ?><?php endif; ?>
                        </div>
                        <div style="margin-top:4px">
                            <?php foreach (preg_split('/\n\s*\n/', trim($ev['note'])) as $p): ?>
                                <p><?= e(trim(preg_replace('/\s*\n\s*/', ' ', $p))) ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- 5. EVIDENCE FILES -->
            <?php if ($evidence): ?>
                <h2>5. Evidence Files</h2>
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
            <?php endif; ?>

            <div style="margin-top:28px;text-align:center;font-size:11px;color:var(--mu)">
                <p>This document was generated by CyberShield Incident Management System on <?= date('F d, Y \a\t H:i') ?>.</p>
                <p style="margin-top:4px">Confidential — For authorised personnel only.</p>
            </div>

        </div>

    </div>

</body>

</html>