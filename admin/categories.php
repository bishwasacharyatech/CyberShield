<?php
require_once '../includes/config.php';
requireAuth('admin');
require_once '../includes/layout.php';

$db = getDB();
$msg = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update category
    if (isset($_POST['update_id'])) {
        $id = (int) $_POST['update_id'];
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($name) {
            $stmt = $db->prepare('UPDATE categories SET name = ?, description = ? WHERE id = ?');
            $stmt->bind_param('ssi', $name, $desc, $id);
            $stmt->execute() ? $msg = 'Category updated!' : $msg = 'That category name already exists.';
        } else {
            $msg = 'Name cannot be empty.';
        }
    }
    // Toggle active status
    elseif (isset($_POST['toggle_id'])) {
        $id = (int) $_POST['toggle_id'];
        $stmt = $db->prepare("UPDATE categories SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $msg = 'Category updated!';
    }
    // Delete category
    elseif (isset($_POST['delete_id'])) {
        $id = (int) $_POST['delete_id'];
        $stmt = $db->prepare("SELECT COUNT(*) AS c FROM reports WHERE category_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $inUse = $stmt->get_result()->fetch_assoc()['c'];
        if ($inUse > 0) {
            $msg = "Cannot delete — {$inUse} report(s) use this category.";
        } else {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $msg = 'Category deleted!';
        }
    }
}

// Get category to edit (if any)
$editId = (int) ($_GET['edit'] ?? 0);
$editCategory = null;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editCategory = $stmt->get_result()->fetch_assoc();
}

// Get all categories with report count
$categories = $db->query("
    SELECT c.*, (SELECT COUNT(*) FROM reports WHERE category_id = c.id) AS report_count
    FROM categories c
    ORDER BY c.name
")->fetch_all(MYSQLI_ASSOC);

pageStart('Categories', 'admin');
sidebar('admin', 'categories');
?>

<div class="pg-title">📂 Report Categories</div>
<div class="pg-sub"><?= count($categories) ?> categories total</div>

<?php if ($msg): ?>
    <div class="flash-ok">✅ <?= e($msg) ?></div>
<?php endif; ?>

<div class="gr-23">
    <!-- Left: Category List -->
    <div class="card">
        <div class="ch"><span class="ct">📋 All Categories</span></div>
        <?php foreach ($categories as $cat): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--bd)">
                <!-- Status indicator -->
                <span style="font-size:14px;color:<?= $cat['is_active'] ? 'var(--gr)' : 'var(--mu)' ?>">●</span>
                
                <!-- Name & Description -->
                <div style="flex:1">
                    <div style="font-weight:600;color:var(--wh);font-size:14px"><?= e($cat['name']) ?></div>
                    <div style="font-size:12px;color:var(--mu)"><?= e($cat['description']) ?></div>
                </div>
                
                <!-- Report count -->
                <span style="font-size:13px;color:var(--mu);font-family:monospace">📊 <?= (int) $cat['report_count'] ?></span>
                
                <!-- Action buttons -->
                <a href="<?= BASE_URL ?>/admin/categories.php?edit=<?= $cat['id'] ?>" class="btn btn-pu btn-sm" style="font-size:16px;padding:4px 10px" title="Edit">✏️</a>
                
                <form method="POST" style="display:inline">
                    <input type="hidden" name="toggle_id" value="<?= $cat['id'] ?>">
                    <button class="btn <?= $cat['is_active'] ? 'btn-re' : 'btn-gr' ?> btn-sm" style="font-size:16px;padding:4px 10px" title="<?= $cat['is_active'] ? 'Disable' : 'Enable' ?>">
                        <?= $cat['is_active'] ? '⛔' : '✅' ?>
                    </button>
                </form>
                
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this category?')">
                    <input type="hidden" name="delete_id" value="<?= $cat['id'] ?>">
                    <button class="btn btn-re btn-sm" style="font-size:16px;padding:4px 10px" title="Delete">🗑️</button>
                </form>
            </div>
        <?php endforeach; ?>
        <?php if (!$categories): ?>
            <div style="text-align:center;padding:20px;color:var(--mu)">No categories yet.</div>
        <?php endif; ?>
    </div>

    <!-- Right: Edit Form -->
    <div class="card">
        <?php if ($editCategory): ?>
            <div class="ch">
                <span class="ct">✏️ Edit Category</span>
                <a href="<?= BASE_URL ?>/admin/categories.php" class="btn btn-gy btn-sm">Cancel</a>
            </div>
            <form method="POST">
                <input type="hidden" name="update_id" value="<?= $editCategory['id'] ?>">
                <div class="fg">
                    <label class="fl">Name *</label>
                    <input type="text" name="name" class="fi" value="<?= e($editCategory['name']) ?>" required>
                </div>
                <div class="fg">
                    <label class="fl">Description</label>
                    <textarea name="description" class="fi" style="min-height:60px"><?= e($editCategory['description']) ?></textarea>
                </div>
                <button type="submit" class="btn btn-cy">💾 Update</button>
            </form>
        <?php else: ?>
            <div style="text-align:center;padding:30px;color:var(--mu)">
                <div style="font-size:48px;margin-bottom:8px">📂</div>
                <p style="color:var(--wh);font-size:14px">Select a category to edit</p>
                <p style="font-size:12px">Click the <strong>✏️</strong> button on any category</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php pageEnd(); ?>