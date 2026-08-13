<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cybershield');
define('BASE_URL', '/CYBERSHIELD_MID');

 function getDB() {
    static $c = null;
    if ($c === null) {
        try {
            $c = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $c->set_charset('utf8mb4');
        } catch (mysqli_sql_exception $e) {
            die('<div style="font-family:sans-serif;background:#07111c;color:#ef4444;padding:40px;min-height:100vh">
                <h2>Database Error</h2>
                <p style="color:#94a3b8;margin:12px 0">' . htmlspecialchars($e->getMessage()) . '</p>
                <p style="color:#64748b">Make sure MySQL is running and the database exists.</p>
            </div>');
        }
    }
    return $c;
}

function e($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
