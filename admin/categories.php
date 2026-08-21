<?php
require_once '../includes/config.php';
requireAuth('admin');
require_once '../includes/layout.php';

$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_id'])) {
        $id = (int) $_POST['update_id'];
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($name) {
            $stmt = $db->prepare('UPDATE categories SET name = ?, description = ? WHERE id = ?');
            $stmt->bind_param('ssi', $name, $desc, $id);
            if ($stmt->execute()) {
                $msg = 'Category updated!';
            } else {
                $msg = 'That category name already exists.';
            }
        } else {
            $msg = 'Name cannot be empty.';
        }
    } elseif (isset($_POST['toggle_id'])) {
        $id = (int) $_POST['toggle_id'];
        $db->query("UPDATE categories SET is_active = NOT is_active WHERE id = {$id}");
        $msg = 'Category updated!';
    } elseif (isset($_POST['delete_id'])) {
        $id = (int) $_POST['delete_id'];
        $inUse = $db->query("SELECT COUNT(*) AS c FROM reports WHERE category_id = {$id}")->fetch_assoc()['c'];
        if ($inUse > 0) {
            $msg = "Cannot delete — {$inUse} report(s) use this category. Disable it instead.";
        } else {
            $db->query("DELETE FROM categories WHERE id = {$id}");
            $msg = 'Category deleted!';
        }
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editCategory = $editId ? $db->query("SELECT * FROM categories WHERE id = {$editId}")->fetch_assoc() : null;

$categories = $db->query("
    SELECT c.*, (SELECT COUNT(*) FROM reports WHERE category_id = c.id) AS report_count
    FROM categories c
    ORDER BY c.name
")->fetch_all(MYSQLI_ASSOC);

pageStart('Categories', 'admin');
sidebar('admin', 'categories');
?>

<div class="pg-title">Report Categories</div>
<div class="pg-sub">Manage incident report categories.</div>

<?php if ($msg): ?>
    <div class="flash-ok">✅ <?= e($msg) ?></div>
<?php endif; ?>

<div class="gr-23">
    <div class="card">
        <div class="ch">
            <span class="ct"><i class="ti ti-category"></i> All Categories (<?= count($categories) ?>)</span>
        </div>
        <?php foreach ($categories as $cat): ?>
            <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--bd)">
                <div style="width:8px;height:8px;border-radius:50%;background:<?= $cat['is_active'] ? 'var(--gr)' : 'var(--mu)' ?>;flex-shrink:0"></div>
                <div style="flex:1">
                    <div style="font-weight:600;color:var(--wh);font-size:13px"><?= e($cat['name']) ?></div>
                    <div style="font-size:12px;color:var(--mu)"><?= e($cat['description']) ?></div>
                </div>
                <span style="font-size:11px;color:var(--mu);font-family:monospace"><?= (int) $cat['report_count'] ?> reports</span>
                <a href="<?= BASE_URL ?>/admin/categories.php?edit=<?= $cat['id'] ?>" class="btn btn-pu btn-sm">✎ Edit</a>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="toggle_id" value="<?= $cat['id'] ?>">
                    <button class="btn <?= $cat['is_active'] ? 'btn-re' : 'btn-gr' ?> btn-sm">
                        <?= $cat['is_active'] ? 'Disable' : 'Enable' ?>
                    </button>
                </form>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this category?')">
                    <input type="hidden" name="delete_id" value="<?= $cat['id'] ?>">
                    <button class="btn btn-re btn-sm">🗑</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <?php if ($editCategory): ?>
            <div class="ch">
                <span class="ct"><i class="ti ti-edit"></i> Edit Category</span>
                <a href="<?= BASE_URL ?>/admin/categories.php" class="btn btn-gy btn-sm">Cancel</a>
            </div>
            <form method="POST">
                <input type="hidden" name="update_id" value="<?= $editCategory['id'] ?>">
                <div class="fg">
                    <label class="fl">Category Name *</label>
                    <input type="text" name="name" class="fi" value="<?= e($editCategory['name']) ?>" required>
                </div>
                <div class="fg">
                    <label class="fl">Description</label>
                    <textarea name="description" class="fi" style="min-height:80px"><?= e($editCategory['description']) ?></textarea>
                </div>
                <button type="submit" class="btn btn-cy">💾 Update Category</button>
            </form>
        <?php else: ?>
            <div style="padding:20px;text-align:center;color:var(--mu)">
                <p>Select a category to edit or manage existing categories.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php pageEnd(); ?>