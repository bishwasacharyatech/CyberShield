<?php
require\_once '../includes/config.php';
require\_once '../includes/layout.php';

// Placeholder data for design preview — Bishwas wires the real query + update logic today
$report = \[
    'ticket\_no' => 'CS-XXXXXXXX',
    'title' => 'Sample Report Title',
    'status' => 'New',
    'severity' => 'Medium',
    'cat\_name' => 'Sample Category',
    'uname' => 'Sample User',
    'uemail' => 'user@example.com',
    'incident\_date' => date('Y-m-d'),
    'created\_at' => date('Y-m-d H:i:s'),
    'description' => 'Sample description text goes here.',
    'suspect\_info' => '',
    'analyst\_remarks' => '',
];
$timeline = \[];
$evidence = \[];

pageStart('Investigate', 'analyst');
sidebar('analyst', 'assigned');
?>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
    <a href="<?= BASE\_URL ?>/analyst/assigned.php" class="btn btn-gy btn-sm">← Back</a>
    <div class="pg-title" style="margin:0"><?= e($report\['ticket\_no']) ?></div>
    <?= statusBadge($report\['status']) ?>
    <?= sevBadge($report\['severity']) ?>
</div>

<div class="gr-32">
    <div>
        <div class="card">
            <div class="ch"><span class="ct"><i class="ti ti-file-description"></i> Report Details</span></div>
            <?php
            $detailFields = \[
                \['Ticket', $report\['ticket\_no'], 'var(--cy)'],
                \['Title', $report\['title'], 'var(--wh)'],
                \['Category', $report\['cat\_name'] ?? '—', null],
                \['Reporter', $report\['uname'] . ' (' . $report\['uemail'] . ')', null],
                \['Incident Date', $report\['incident\_date'], null],
                \['Submitted', substr($report\['created\_at'], 0, 16), 'var(--mu)']
            ];
            foreach ($detailFields as \[$label, $value, $color]): ?>
                <div style="display:flex;padding:8px 0;border-bottom:1px solid var(--bd)">
                    <span style="color:var(--mu);font-size:12px;min-width:120px;flex-shrink:0"><?= $label ?></span>
                    <span style="<?= $color ? "color:{$color}" : '' ?>"><?= e($value) ?></span>
                </div>
            <?php endforeach; ?>
            <div style="margin-top:14px">
                <div style="font-size:11px;color:var(--mu);margin-bottom:6px">Description</div>
                <div style="font-size:14px;line-height:1.7"><?= nl2br(e($report\['description'])) ?></div>
            </div>
        </div>

        <div class="card">
            <div class="ch"><span class="ct"><i class="ti ti-edit"></i> Update Status \& Remarks</span></div>
            <form method="POST">
                <div class="fg">
                    <label class="fl">Update Status</label>
                    <select name="status" class="fi">
                        <?php foreach (\['New', 'Assigned', 'Under Review', 'In Progress', 'Resolved', 'Closed'] as $status): ?>
                            <option <?= $report\['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label class="fl">Analyst Remarks</label>
                    <textarea name="remarks" class="fi" placeholder="Add investigation findings and recommendations..." style="min-height:130px"><?= e($report\['analyst\_remarks'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-cy">💾 Save Update</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="ch"><span class="ct"><i class="ti ti-timeline"></i> Case Timeline</span></div>
        <div style="color:var(--mu);font-size:12px">No timeline yet</div>
    </div>
</div>

<?php pageEnd(); ?>