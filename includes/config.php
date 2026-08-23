<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cybershield');
define('BASE_URL', '/CYBERSHIELD');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');

function getDB() {
    static $c = null;
    if ($c === null) {
        try {
            $c = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $c->set_charset('utf8mb4');
        } catch (mysqli_sql_exception $e) {
            die('<div style="font-family:sans-serif;background:#07111c;color:#ef4444;padding:40px;min-height:100vh">
                <h2>⚠ Database Error</h2>
                <p style="color:#94a3b8;margin:12px 0">' . htmlspecialchars($e->getMessage()) . '</p>
                <p style="color:#64748b">Make sure MySQL is running and the database exists.</p>
            </div>');
        }
    }
    return $c;
}

if (session_status() === PHP_SESSION_NONE) session_start();

function requireAuth($role = null) {
    if (empty($_SESSION['uid'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    if ($role && $_SESSION['role'] !== $role) {
        header('Location: ' . BASE_URL . '/' . $_SESSION['role'] . '/dashboard.php');
        exit;
    }
}

function auditLog($a, $m, $d) {
    $ro = $_SESSION['role'] ?? 'unknown';
    // Admin actions are now logged too
    $db = getDB();
    $uid = $_SESSION['uid'] ?? null;
    $un = $_SESSION['uname'] ?? 'guest';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0';
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200);
    $s = $db->prepare('INSERT INTO audit_logs (user_id, username, role, action, module, description, ip_address, user_agent) VALUES (?,?,?,?,?,?,?,?)');
    $s->bind_param('isssssss', $uid, $un, $ro, $a, $m, $d, $ip, $ua);
    $s->execute();
}

function e($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function genTicket() {
    return 'CS-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
}

function sevBadge($s) {
    $colors = ['Critical'=>'#e0333f', 'High'=>'#f2a93c', 'Medium'=>'#3b6fe8', 'Low'=>'#22c55e'];
    $c = $colors[$s] ?? '#5c7291';
    return "<span style='background:{$c}18;color:{$c};padding:3px 10px;border-radius:4px;font-size:11px;font-weight:700;border:1px solid {$c}33'>{$s}</span>";
}

function statusBadge($s) {
    $m = [
        'New' => '#3b6fe8', 'Assigned' => '#7c6ff2', 'Under Review' => '#f2a93c',
        'In Progress' => '#f2751a', 'Resolved' => '#22c55e', 'Closed' => '#5c7291',
        'active' => '#22c55e', 'suspended' => '#e0333f'
    ];
    $c = $m[$s] ?? '#5c7291';
    return "<span style='background:{$c}18;color:{$c};padding:3px 10px;border-radius:4px;font-size:11px;font-weight:700;border:1px solid {$c}44'>{$s}</span>";
}

function validateUpload($file) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'doc', 'docx'];
    $max = 5 * 1024 * 1024;
    if ($file['size'] > $max) return ['ok' => false, 'err' => 'File too large. Max 5MB.'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return ['ok' => false, 'err' => 'Allowed types: ' . implode(', ', $allowed)];
    return ['ok' => true, 'ext' => $ext];
}

function addNotif($uid, $rid, $msg) {
    $db = getDB();
    $s = $db->prepare('INSERT INTO notifications (user_id, report_id, message) VALUES (?,?,?)');
    $s->bind_param('iis', $uid, $rid, $msg);
    $s->execute();
}

function getUnread() {
    if (empty($_SESSION['uid'])) return 0;
    $db = getDB();
    $uid = $_SESSION['uid'];
    $r = $db->prepare('SELECT COUNT(*) c FROM notifications WHERE user_id=? AND is_read=0');
    $r->bind_param('i', $uid);
    $r->execute();
    return $r->get_result()->fetch_assoc()['c'] ?? 0;
}

function timeAgo($dt) {
    $d = time() - strtotime($dt);
    if ($d < 60) return $d . 's ago';
    if ($d < 3600) return floor($d / 60) . 'm ago';
    if ($d < 86400) return floor($d / 3600) . 'h ago';
    return floor($d / 86400) . 'd ago';
}