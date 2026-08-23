<?php

function pageStart($title, $role) {
    $rc = ['admin' => '#e0333f', 'analyst' => '#7c6ff2', 'user' => '#3b6fe8'][$role] ?? '#3b6fe8';
    $fn = e($_SESSION['fname'] ?? '');
    $base = BASE_URL;
    $unread = getUnread();
    $escTitle = e($title);

    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $escTitle . ' — CyberShield</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">
    <link rel="stylesheet" href="' . BASE_URL . '/assets/style.css">
    <style>
        :root { --rc: ' . $rc . '; }
    </style>
</head>
<body>
    <div class="topbar">
        <button class="hamb" id="hambBtn" onclick="document.getElementById(\'sbEl\').classList.toggle(\'open\');document.getElementById(\'ovEl\').classList.toggle(\'show\')">☰</button>
        <div class="logo"><span class="c-cyber">Cyber</span><span class="c-shield">Shield</span></div>
        <span class="rpill">' . $role . '</span>
        <span class="dot" style="margin-left:4px"></span>
        <div class="ml" style="display:flex;align-items:center;gap:10px">
            <a href="' . $base . '/' . $role . '/notifications.php" class="notif-btn">
                🔔' . ($unread > 0 ? '<span class="nbadge">' . $unread . '</span>' : '') . '
            </a>
            <span style="font-size:13px;color:var(--mu)">👤 <strong style="color:var(--wh)">' . $fn . '</strong></span>
            <a href="' . $base . '/logout.php" class="btn btn-gy btn-sm"><i class="ti ti-logout"></i> Logout</a>
        </div>
    </div>
    <div class="overlay" id="ovEl" onclick="document.getElementById(\'sbEl\').classList.remove(\'open\');this.classList.remove(\'show\')"></div>
    <div class="wrap">';
}

function sidebar($role, $active = '') {
    $base = BASE_URL;
    $links = [
        'user' => [
            ['dashboard', 'Dashboard', 'ti-layout-dashboard'],
            ['report', 'Submit Report', 'ti-plus'],
            ['my-reports', 'My Reports', 'ti-file-text'],
            ['notifications', 'Notifications', 'ti-bell'],
            ['edit-profile', 'Edit Profile', 'ti-user-edit'],
            ['change-password', 'Change Password', 'ti-lock'],
        ],
        'analyst' => [
            ['dashboard', 'Dashboard', 'ti-layout-dashboard'],
            ['assigned', 'My Cases', 'ti-clipboard-check'],
            ['notifications', 'Notifications', 'ti-bell'],
            ['edit-profile', 'Edit Profile', 'ti-user-edit'],
            ['change-password', 'Change Password', 'ti-lock'],
        ],
        'admin' => [
            ['dashboard', 'Dashboard', 'ti-layout-dashboard'],
            ['reports', 'All Reports', 'ti-file-text'],
            ['users', 'Manage Users', 'ti-users'],
            ['categories', 'Categories', 'ti-category'],
            ['audit', 'Audit Trail', 'ti-history'],
        ],
    ];

    echo '<div class="sidebar" id="sbEl">
        <div class="sb-sec">// Menu</div>';

    foreach ($links[$role] ?? [] as $link) {
        [$page, $label, $icon] = $link;
        $href = $base . '/' . $role . '/' . $page . '.php';
        $activeClass = ($active === $page) ? ' active' : '';
        echo '<a href="' . $href . '" class="sb-a' . $activeClass . '">
            <i class="ti ' . $icon . '"></i> ' . $label .
        '</a>';
    }

    echo '<div class="sb-bot">
            <a href="' . $base . '/logout.php" class="sb-a"><i class="ti ti-logout"></i> Logout</a>
        </div>
    </div>
    <div class="content">';
}

function pageEnd() {
    echo '</div></div></body></html>';
}