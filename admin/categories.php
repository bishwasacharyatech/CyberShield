<?php
require\_once '../includes/config.php';
require\_once '../includes/layout.php';

// Placeholder data for design preview — Bishwas wires real queries + update/toggle/delete logic today
$categories = \[
    \['id' => 1, 'name' => 'Sample Category', 'description' => 'Sample description', 'is\_active' => 1, 'report\_count' => 0],
];
$msg = '';
$editCategory = null;

pageStart('Categories', 'admin');
sidebar('admin', 'categories');
?>

<div class="pg-title">Report Categories</div>
<div class="pg-sub">Manage incident report categories.</div>

<div class="gr-23">
    <div class="card">
        <div class="ch">
            <span class="ct"><i class="ti ti-category"></i> All Categories (<?= count($categories) ?>)</span>
        </div>
        <?php foreach ($categories as $cat): ?>
            <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--bd)">
                <div style="width:8px;height:8px;border-radius:50%;background:<?= $cat\['is\_active'] ? 'var(--gr)' : 'var(--mu)' ?>;flex-shrink:0"></div>
                <div style="flex:1">
                    <div style="font-weight:600;color:var(--wh);font-size:13px"><?= e($cat\['name']) ?></div>
                    <div style="font-size:12px;color:var(--mu)"><?= e($cat\['description']) ?></div>
                </div>
                <span style="font-size:11px;color:var(--mu);font-family:monospace"><?= (int) $cat\['report\_count'] ?> reports</span>
                <a href="<?= BASE\_URL ?>/admin/categories.php?edit=<?= $cat\['id'] ?>" class="btn btn-pu btn-sm">✎ Edit</a>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="toggle\_id" value="<?= $cat\['id'] ?>">
                    <button class="btn <?= $cat\['is\_active'] ? 'btn-re' : 'btn-gr' ?> btn-sm">
                        <?= $cat\['is\_active'] ? 'Disable' : 'Enable' ?>
                    </button>
                </form>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this category?')">
                    <input type="hidden" name="delete\_id" value="<?= $cat\['id'] ?>">
                    <button class="btn btn-re btn-sm">🗑</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <?php if ($editCategory): ?>
            <div class="ch">
                <span class="ct"><i class="ti ti-edit"></i> Edit Category</span>
                <a href="<?= BASE\_URL ?>/admin/categories.php" class="btn btn-gy btn-sm">Cancel</a>
            </div>
        <?php else: ?>
            <div style="padding:20px;text-align:center;color:var(--mu)">
                <p>Select a category to edit or manage existing categories.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php pageEnd(); ?>