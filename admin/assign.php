<?php
require\_once '../includes/config.php';
require\_once '../includes/layout.php';

// Placeholder data for design preview — Bishwas wires the real query + update logic today
$report = \[
    'ticket\_no' => 'CS-XXXXXXXX',
    'status' => 'New',
    'severity' => 'Medium',
    'uname' => 'Sample User',
    'cat\_name' => 'Sample Category',
    'incident\_date' => date('Y-m-d'),
    'created\_at' => date('Y-m-d H:i:s'),
    'description' => 'Sample description text goes here.',
    'assigned\_to' => null,
];
$analysts = \[];
$msg = '';
$err = '';

pageStart('Manage Report', 'admin');
sidebar('admin', 'assign');
?>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
    <a href="<?= BASE\_URL ?>/admin/reports.php" class="btn btn-gy btn-sm">← Back</a>
    <div class="pg-title" style="margin:0"><?= e($report\['ticket\_no']) ?></div>
    <?= statusBadge($report\['status']) ?>
    <?= sevBadge($report\['severity']) ?>
</div>

<div class="gr-22">
    <div class="card">
        <div class="ch"><span class="ct"><i class="ti ti-file-description"></i> Report Info</span></div>
        <?php
        $infoFields = \[
            \['Reported By', $report\['uname']],
            \['Category', $report\['cat\_name'] ?? '—'],
            \['Incident Date', $report\['incident\_date']],
            \['Submitted', substr($report\['created\_at'], 0, 16)]
        ];
        foreach ($infoFields as \[$label, $value]): ?>
            <div style="display:flex;padding:8px 0;border-bottom:1px solid var(--bd)">
                <span style="color:var(--mu);font-size:12px;min-width:120px"><?= $label ?></span>
                <span><?= e($value) ?></span>
            </div>
        <?php endforeach; ?>
        <div style="margin-top:12px;font-size:13px;line-height:1.7">
            <?= nl2br(e(substr($report\['description'], 0, 400))) ?>
        </div>
    </div>

    <div class="card">
        <div class="ch"><span class="ct"><i class="ti ti-user-check"></i> Assign \& Update</span></div>
        <form method="POST">
            <div class="fg">
                <label class="fl">Assign To Analyst</label>
                <select name="analyst\_id" class="fi">
                    <option value="0">-- Unassigned --</option>
                </select>
            </div>
            <div class="fg">
                <label class="fl">Severity</label>
                <select name="severity" class="fi">
                    <?php foreach (\['Critical', 'High', 'Medium', 'Low'] as $s): ?>
                        <option <?= $report\['severity'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label class="fl">Status</label>
                <select name="status" class="fi">
                    <?php foreach (\['New', 'Assigned', 'Under Review', 'In Progress', 'Resolved', 'Closed'] as $s): ?>
                        <option <?= $report\['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-cy">💾 Save Changes</button>
        </form>
    </div>
</div>

<?php pageEnd(); ?>