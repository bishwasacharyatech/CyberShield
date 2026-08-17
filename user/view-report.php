<?php
require\_once '../includes/config.php';
require\_once '../includes/layout.php';

// Placeholder data for design preview — Bishwas wires the real report/timeline/evidence queries today
$report = \[
    'ticket\_no' => 'CS-XXXXXXXX',
    'title' => 'Sample Report Title',
    'status' => 'New',
    'severity' => 'Medium',
    'cat\_name' => 'Sample Category',
    'incident\_date' => date('Y-m-d'),
    'uname' => 'Sample User',
    'aname' => null,
    'created\_at' => date('Y-m-d H:i:s'),
    'description' => 'Sample description text goes here.',
    'suspect\_info' => '',
    'analyst\_remarks' => '',
];
$timeline = \[];
$evidence = \[];

pageStart('View Report', 'user');
sidebar('user', 'my-reports');
?>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
    <a href="<?= BASE\_URL ?>/user/my-reports.php" class="btn btn-gy btn-sm">← Back</a>
    <div class="pg-title" style="margin:0"><?= e($report\['ticket\_no']) ?></div>
    <?= statusBadge($report\['status']) ?>
    <?= sevBadge($report\['severity']) ?>
</div>

<div class="gr-23">
    <div>
        <div class="card">
            <div class="ch"><span class="ct"><i class="ti ti-file-description"></i> Report Details</span></div>
            <?php
            $detailFields = \[
                \['Title', $report\['title'], 'var(--wh)', true],
                \['Category', $report\['cat\_name'] ?? '—', null, false],
                \['Incident Date', $report\['incident\_date'], null, false],
                \['Submitted By', $report\['uname'], null, false],
                \['Assigned Analyst', $report\['aname'] ?? 'Not assigned', null, false],
                \['Submitted', substr($report\['created\_at'], 0, 16), 'var(--mu)', false]
            ];
            foreach ($detailFields as \[$label, $value, $color, $bold]): ?>
                <div style="display:flex;padding:8px 0;border-bottom:1px solid var(--bd)">
                    <span style="color:var(--mu);font-size:12px;min-width:130px;flex-shrink:0"><?= $label ?></span>
                    <span style="<?= $color ? "color:{$color};" : '' ?><?= $bold ? 'font-weight:600;' : '' ?>"><?= e($value) ?></span>
                </div>
            <?php endforeach; ?>
            <div style="margin-top:14px">
                <div style="font-size:11px;color:var(--mu);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Description</div>
                <div style="font-size:14px;line-height:1.7"><?= nl2br(e($report\['description'])) ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="ch"><span class="ct"><i class="ti ti-timeline"></i> Timeline</span></div>
        <div style="color:var(--mu);font-size:12px">No timeline yet</div>
    </div>
</div>

<?php pageEnd(); ?>