<?php
require_once 'includes/config.php';

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $uname = trim($_POST['username'] ?? '');
    $pw    = $_POST['password'] ?? '';
    $pw2   = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    $role = isset($_POST['is_analyst']) ? 'analyst' : 'user';

    if (!$name || !$email || !$uname || !$pw || !$phone) {
        $err = 'All required fields must be filled out.';
    } elseif (strlen($name) < 3 || !preg_match('/^[A-Za-z ]+$/', $name)) {
        $err = 'Full name must contain at least 3 letters (only letters and spaces).';
    } elseif (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $uname)) {
        $err = 'Username must be 3–20 characters and contain only letters, numbers, and underscores.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $err = 'Phone number must be exactly 10 digits.';
    } elseif (strlen($pw) < 6) {
        $err = 'Password must be at least 6 characters.';
    } elseif ($pw !== $pw2) {
        $err = 'Passwords do not match.';
    } elseif (!isset($_POST['agree'])) {
        $err = 'You must accept the Terms and Conditions.';
    } else {
        $db = getDB();
        $chk = $db->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
        $chk->bind_param('ss', $email, $uname);
        $chk->execute();

        if ($chk->get_result()->num_rows > 0) {
            $err = 'Email or username is already registered.';
        } else {
            $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $db->prepare('INSERT INTO users (full_name, email, username, password, phone, role) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssss', $name, $email, $uname, $hash, $phone, $role);

            if ($stmt->execute()) {
                $success = 'Account created and saved to the database. Login page arrives Day 3.';
            } else {
                $err = 'Registration failed due to a system error. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — CyberShield</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
    <style>
        .auth-card { max-width: 500px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; }
        .full-width { grid-column: span 2; }
        @media (max-width: 480px) {
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
        }
        .password-group input { padding-right: 45px !important; }
        #togglePassword { transition: color 0.2s; }
        #togglePassword:hover { color: var(--blue) !important; }
    </style>
</head>
<body class="auth-body">

<div class="auth-card">
    <div class="brand-header">
        <a href="<?= BASE_URL ?>/landing.php" class="brand-logo-link">
            <div class="brand-logo">
                <span class="c-cyber">Cyber</span><span class="c-shield">Shield</span>
            </div>
        </a>
        <div class="brand-subtitle">Register a new account</div>
    </div>

    <?php if ($success): ?>
        <div style="background:rgba(63,185,80,.1);border:1px solid #3fb950;color:#3fb950;padding:0.75rem;border-radius:6px;font-size:0.85rem;margin-bottom:1.25rem;line-height:1.4;">
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <div class="alert-error">
            <span><?= e($err) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="" id="regForm">
        <div class="form-grid">
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" placeholder="John Doe" required value="<?= e($_POST['full_name'] ?? '') ?>">
                <div class="input-hint" id="name-hint">Must be 3+ letters (letters & spaces only).</div>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number *</label>
                <input type="tel" id="phone" name="phone" placeholder="10-digit number" maxlength="10" required value="<?= e($_POST['phone'] ?? '') ?>">
                <div class="input-hint" id="phone-hint">Must be exactly 10 digits.</div>
            </div>

            <div class="form-group full-width">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" placeholder="name@domain.com" required value="<?= e($_POST['email'] ?? '') ?>">
                <div class="input-hint" id="email-hint">Enter a valid email address.</div>
            </div>

            <div class="form-group full-width">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" placeholder="Choose a unique handle" required value="<?= e($_POST['username'] ?? '') ?>">
                <div class="input-hint" id="user-hint">Must be 3–20 characters (letters, numbers, _).</div>
            </div>

            <div class="form-group password-group">
                <label for="password">Password *</label>
                <div style="position:relative;">
                    <input type="password" id="password" name="password" placeholder="Min. 6 characters" required>
                    <button type="button" id="togglePassword" 
                        style="position:absolute; right:10px; top:50%; transform:translateY(-50%); 
                               background:none; border:none; color:#9ca3af; cursor:pointer; font-size:18px;">
                        👁️
                    </button>
                </div>
                <div class="input-hint" id="pw-hint">Min 6 characters.</div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                <div class="input-hint" id="pw2-hint">Passwords do not match.</div>
            </div>
        </div>

        <div class="role-box">
            <label class="role-option">
                <input type="checkbox" name="is_analyst" value="1" <?= isset($_POST['is_analyst']) ? 'checked' : '' ?>>
                <span>Register as a <strong>SOC Analyst</strong></span>
            </label>
        </div>

        <label class="terms-option">
            <input type="checkbox" name="agree" value="1" required <?= isset($_POST['agree']) ? 'checked' : '' ?>>
            <span>I accept the <a href="<?= BASE_URL ?>/terms.php">Terms and Conditions</a></span>
        </label>

        <button type="submit" class="btn-submit">Create Account</button>
    </form>

    <hr class="divider">

    <div class="auth-footer">
        Already registered? <a href="<?= BASE_URL ?>/login.php">Sign in here</a>
    </div>
</div>

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