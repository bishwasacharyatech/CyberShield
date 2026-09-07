<?php
require_once '../includes/config.php';
requireAuth('admin');
require_once '../includes/layout.php';

$db = getDB();
$msg = '';

// --- Add new category ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if ($name) {
        $stmt = $db->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
        $stmt->bind_param('ss', $name, $desc);
        if ($stmt->execute()) {
            $msg = 'Category added!';
        } else {
            $msg = 'That category name already exists.';
        }
    } else {
        $msg = 'Name cannot be empty.';
    }
}

// --- Toggle active status ---
if (isset($_POST['toggle_id'])) {
    $id = (int) $_POST['toggle_id'];
    $stmt = $db->prepare("UPDATE categories SET is_active = NOT is_active WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $msg = 'Category updated!';
}

// --- Delete category ---
if (isset($_POST['delete_id'])) {
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

// --- Fetch all categories with report count ---
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

<!-- ADD FORM – hidden until "Add New" is clicked -->
<details style="margin-bottom:20px; display:block;">
    <summary style="cursor:pointer; display:inline-block; padding:8px 16px; background:var(--cy); color:#fff; border-radius:2rem; font-weight:600; font-size:0.9rem; border:none; outline:none; user-select:none;">
        ➕ Add New Category
    </summary>
    <div class="card" style="margin-top:12px; max-width:500px;">
        <div class="ch"><span class="ct">➕ Add New Category</span></div>
        <form method="POST">
            <input type="hidden" name="add" value="1">
            <div class="fg">
                <label class="fl">Category Name *</label>
                <input type="text" name="name" class="fi" placeholder="e.g. Data Breach" required>
            </div>
            <div class="fg">
                <label class="fl">Description</label>
                <textarea name="description" class="fi" style="min-height:60px" placeholder="Brief description"></textarea>
            </div>
            <button type="submit" class="btn btn-cy">➕ Add Category</button>
        </form>
    </div>
</details>

<!-- CATEGORY LIST -->
<div class="card">
    <div class="ch"><span class="ct">📋 All Categories</span></div>
    <?php if ($categories): ?>
        <div class="tw">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Reports</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td style="font-weight:600;color:var(--wh)"><?= e($cat['name']) ?></td>
                            <td style="font-size:13px;color:var(--mu)"><?= e($cat['description']) ?></td>
                            <td><?= $cat['is_active'] ? statusBadge('active') : statusBadge('suspended') ?></td>
                            <td style="font-family:monospace;text-align:center"><?= (int) $cat['report_count'] ?></td>
                            <td>
                                <div style="display:flex;gap:4px;flex-wrap:nowrap">
                                    <!-- Toggle -->
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="toggle_id" value="<?= $cat['id'] ?>">
                                        <button class="btn <?= $cat['is_active'] ? 'btn-re' : 'btn-gr' ?> btn-sm" title="<?= $cat['is_active'] ? 'Disable' : 'Enable' ?>">
                                            <?= $cat['is_active'] ? '⛔' : '✅' ?>
                                        </button>
                                    </form>
                                    <!-- Delete -->
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this category?')">
                                        <input type="hidden" name="delete_id" value="<?= $cat['id'] ?>">
                                        <button class="btn btn-re btn-sm" title="Delete">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align:center;padding:20px;color:var(--mu)">No categories yet.</div>
    <?php endif; ?>
</div>

<?php pageEnd(); ?>