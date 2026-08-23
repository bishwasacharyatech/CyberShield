<?php
require\_once '../includes/config.php';
require\_once '../includes/layout.php';

// Placeholder data for design preview — Bishwas wires the real query + filters today
$logs = \[];
$total = 0;
$actions = \[];
$search = '';
$frole = '';
$faction = '';
$sort = 'newest';

pageStart('Audit Trail', 'admin');
sidebar('admin', 'audit');
?>

<div class="pg-title">Audit Trail</div>
<div class="pg-sub">Every action logged automatically. Total: <strong style="color:var(--cy)"><?= (int) $total ?></strong></div>

<form method="GET" class="srchbar">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search user, description, IP..." class="srch-i" style="flex:1;min-width:180px">
    <select name="role" class="srch-i">
        <option value="">All Roles</option>
        <option value="user">User</option>
        <option value="analyst">Analyst</option>
        <option value="admin">Admin</option>
    </select>
    <select name="action" class="srch-i">
        <option value="">All Actions</option>
    </select>
    <select name="sort" class="srch-i">
        <option value="newest">Newest</option>
        <option value="oldest">Oldest</option>
    </select>
    <button type="submit" class="btn btn-cy btn-sm">Filter</button>
    <a href="<?= BASE\_URL ?>/admin/audit.php" class="btn btn-gy btn-sm">Reset</a>
</form>

<div class="card">
    <div class="tw" style="max-height:calc(100vh - 280px);overflow-y:auto">
        <table>
            <thead style="position:sticky;top:0">
                <tr>
                    <th>Time</th><th>User</th><th>Role</th><th>Action</th><th>Module</th><th>Description</th><th>IP</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--mu)">No audit logs found</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php pageEnd(); ?>