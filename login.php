<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CyberShield</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-body">

<main class="auth-card">
    <header class="brand-header">
        <a href="landing.php" class="brand-logo-link">
            <div class="brand-logo">
                <span class="c-cyber">Cyber</span><span class="c-shield">Shield</span>
            </div>
        </a>
        <div class="brand-subtitle">Authorized Personnel Only</div>
    </header>

    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username or Email</label>
            <input type="text" id="username" name="username" placeholder="Enter username or email" required autofocus>
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
        New here? <a href="register.php">Create an account</a>
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