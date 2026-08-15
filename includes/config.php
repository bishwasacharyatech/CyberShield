<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cybershield');
define('BASE_URL', '/CYBERSHIELD');

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
    if ($ro === 'admin') return;
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