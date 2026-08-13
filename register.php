<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Create Account — CyberShield</title>
        <link rel="stylesheet" href="assets/style.css">
        <style>
        .auth-card{max-width:500px;}
        .form-grid{display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem;}
        .full-width{grid-column: span 2;}
        @media(max-width: 480px){
        .form-grid{ grid-template-columns: 1fr;}
        .full-width{grid-column: span 1;}     
        }
        .password-group input { padding-right:45px !important;}
        #togglePassword{transition:color 0.2s;}
        #togglePassword:hover { color:var(--blue) !important; }
    </style>
</head>
<body class="auth-card">
    <div class="brand-header">
        <a href="landing.php" class="brand-logo-link">
            <div class="brand-logo">
                <span class="c-cyber">Cyber</span><span class="c-shield">Shield</span>
            </div>
        </a>
            <div class="brand-subtitle">Register a new account</div>
    </div>

    <form method="POST" action="" id="regForm">
        <div class="form-grid">
                     <div class="form-group">
                <label for="full\_name">Full Name \*</label>
                <input type="text" id="full\_name" name="full\_name" placeholder="John Doe" required>
                <div class="input-hint" id="name-hint">Must be 3+ letters (letters \& spaces only).</div>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number \*</label>
                <input type="tel" id="phone" name="phone" placeholder="10-digit number" maxlength="10" required>
                <div class="input-hint" id="phone-hint">Must be exactly 10 digits.</div>
            </div>

            <div class="form-group full-width">
                <label for="email">Email Address \*</label>
                <input type="email" id="email" name="email" placeholder="name@domain.com" required>
                <div class="input-hint" id="email-hint">Enter a valid email address.</div>
            </div>

            <div class="form-group full-width">
                <label for="username">Username \*</label>
                <input type="text" id="username" name="username" placeholder="Choose a unique handle" required>
                <div class="input-hint" id="user-hint">Must be 3–20 characters (letters, numbers, \_).</div>
            </div>

            <div class="form-group password-group">
                <label for="password">Password \*</label>
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
                <label for="confirm\_password">Confirm Password \*</label>
                <input type="password" id="confirm\_password" name="confirm\_password" placeholder="Re-enter password" required>
                <div class="input-hint" id="pw2-hint">Passwords do not match.</div>
            </div>
        </div>

        <label class="terms-option">
            <input type="checkbox" name="agree" value="1" required>
            <span>I accept the <a href="terms.php">Terms and Conditions</a></span>
        </label>

        <button type="submit" class="btn-submit">Create Account</button>
    </form>

    <hr class="divider">

    <div class="auth-footer">
        Already registered? <a href="login.php">Sign in here</a>
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
   
        </div>
    </form>

</body>
</html>
