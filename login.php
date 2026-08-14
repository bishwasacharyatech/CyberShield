<?php
require_once 'includes/config.php';

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $un = trim($_POST['username'] ?? '');
    $pw = $_POST['password'] ?? '';

    if ($un === '' || $pw === '') {
        $err = 'Please enter both username and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
        $stmt->bind_param('ss', $un, $un);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($pw, $user['password']) && $user['status'] === 'active') {
            $success = "Login successful! Welcome, {$user['full_name']} ({$user['role']}). Dashboard arrives Day 4.";
        } else {
            $err = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CyberShield</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body class="auth-body">

<main class="auth-card">
    <header class="brand-header">
        <a href="<?= BASE_URL ?>/landing.php" class="brand-logo-link">
            <div class="brand-logo">
                <span class="c-cyber">Cyber</span><span class="c-shield">Shield</span>
            </div>
        </a>
        <div class="brand-subtitle">Authorized Personnel Only</div>
    </header>

    <?php if ($success): ?>
        <div style="background:rgba(63,185,80,.1);border:1px solid #3fb950;color:#3fb950;padding:0.75rem;border-radius:6px;font-size:0.85rem;margin-bottom:1.25rem;line-height:1.4;">
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <div class="alert-error">
            <span>⚠ <?= e($err) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username or Email</label>
            <input type="text" id="username" name="username" placeholder="Enter username or email" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
        </div>

        <div class="form-group password-group">
            <label for="password">Password</label>
            <div style="position:relative;">
                <input type="password" id="password" name="password" placeholder="Enter password" required>
                <button type="button" id="togglePassword" 
                        style="position:absolute; right:10px; top:50%; transform:translateY(-50%); 
                               background:none; border:none; color:#9ca3af; cursor:pointer; font-size:18px;">
                    👁️
                </button>
            </div>
        </div>

        <button type="submit" class="btn-submit">🔐 Sign In</button>
    </form>

    <hr class="divider">

    <footer class="auth-footer">
    <a href="<?= BASE_URL ?>/register.php">Create an account</a>
    </footer>
</main>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    if (togglePassword && password) {
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    }
</script>

</body>
</html>