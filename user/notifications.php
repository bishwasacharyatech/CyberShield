<?php
require\_once '../includes/config.php';
require\_once '../includes/layout.php';

// Placeholder data for design preview — Bishwas wires the real query + mark-as-read logic today
$notifications = \[];

pageStart('Notifications', 'user');
sidebar('user', 'notifications');
?>

<div class="pg-title">Notifications</div>
<div class="pg-sub"><?= count($notifications) ?> total notification(s)</div>

<div class="card">
    <div style="text-align:center;padding:40px;color:var(--mu)">
        <div style="font-size:36px;margin-bottom:10px">🔕</div>
        <p>No notifications yet</p>
    </div>
</div>

<?php pageEnd(); ?>