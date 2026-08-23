<?php
require\_once '../includes/config.php';
require\_once '../includes/layout.php';

// Placeholder — Bishwas wires the real query + mark-read logic today
$notifications = \[];

pageStart('Notifications', 'admin');
sidebar('admin', 'notifications');
?>

<div class="pg-title">Notifications</div>

<div class="card">
    <div style="padding:30px;text-align:center;color:var(--mu)">No notifications</div>
</div>

<?php pageEnd(); ?>