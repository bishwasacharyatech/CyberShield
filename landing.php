<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (!empty($_SESSION['uid'])) {
    header('Location: ' . BASE_URL . '/' . $_SESSION['role'] . '/dashboard.php');
    exit;
}

// Get some stats for the landing page
$db = getDB();
$totalReports = $db->query("SELECT COUNT(*) AS c FROM reports")->fetch_assoc()['c'] ?? 0;
$totalUsers = $db->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'] ?? 0;
$resolved = $db->query("SELECT COUNT(*) AS c FROM reports WHERE status = 'Resolved' OR status = 'Closed'")->fetch_assoc()['c'] ?? 0;
$pending = $db->query("SELECT COUNT(*) AS c FROM reports WHERE status NOT IN ('Resolved','Closed')")->fetch_assoc()['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberShield – Cybercrime Complaint System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
    <style>
        .hero .subhead {
            font-size: 1.1rem;
            color: var(--mu);
            max-width: 600px;
            margin: 0 auto 16px;
            line-height: 1.7;
        }
        .complaint-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        .complaint-card {
            background: var(--bg3);
            border: 1px solid var(--bd);
            border-radius: 10px;
            padding: 16px;
            text-align: center;
        }
        .complaint-card .icon {
            font-size: 2.2rem;
            margin-bottom: 4px;
        }
        .complaint-card h4 {
            color: var(--wh);
            font-size: 0.95rem;
            margin-bottom: 4px;
        }
        .complaint-card p {
            font-size: 0.8rem;
            color: var(--mu);
            line-height: 1.4;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 14px;
            margin: 20px 0;
        }
        .stat-box {
            background: var(--bg3);
            border: 1px solid var(--bd);
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }
        .stat-box .number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--cy);
        }
        .stat-box .label {
            font-size: 0.7rem;
            color: var(--mu);
            text-transform: uppercase;
        }
        .steps-mini {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 20px;
            margin-top: 10px;
        }
        .step-mini {
            text-align: center;
        }
        .step-mini .num {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(88,166,255,0.12);
            border: 1px solid rgba(88,166,255,0.25);
            color: var(--cy);
            font-weight: 700;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
        }
        .step-mini h4 {
            color: var(--wh);
            font-size: 1rem;
            margin-bottom: 4px;
        }
        .step-mini p {
            color: var(--mu);
            font-size: 0.85rem;
            line-height: 1.4;
        }
        .cta-section {
            background: var(--bg2);
            border: 1px solid var(--bd);
            border-radius: 12px;
            padding: 30px 20px;
            text-align: center;
            margin: 30px 0;
        }
        .cta-section h2 {
            font-size: 1.6rem;
            color: var(--wh);
            margin-bottom: 8px;
        }
        .cta-section p {
            color: var(--mu);
            max-width: 500px;
            margin: 0 auto 16px;
        }
        @media (max-width: 480px) {
            .complaint-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .steps-mini { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- Navigation -->
<header class="nav">
    <div class="logo">
        <span class="c-cyber">Cyber</span><span class="c-shield">Shield</span>
    </div>
    <div class="nav-r">
        <a href="<?= BASE_URL ?>/login.php" class="nbtn nbtn-line">Sign In</a>
        <a href="<?= BASE_URL ?>/register.php" class="nbtn nbtn-fill">Register</a>
    </div>
</header>

<main>

    <!-- Hero Section -->
    <section class="hero">
        <h1>Report a Cybercrime. <span>Get Help.</span></h1>
        <p class="subhead">
            CyberShield is a complaint documentation system that <strong>fills the information gap</strong>
            and connects you directly to the platform where you can document your cybercrime case.
            Whether it's online fraud, hacking, social media abuse, or other cybercrimes – file your complaint,
            attach evidence, and track its progress – all in one place.
            <br><br>
            <strong style="color:var(--mu); font-weight:400;">We help you document and track your complaint – we are not a recovery service and cannot reset passwords or intervene directly in ongoing incidents.</strong>
        </p>
        <div class="hero-btns">
            <a href="<?= BASE_URL ?>/register.php" class="nbtn nbtn-fill">File a Complaint →</a>
            <a href="<?= BASE_URL ?>/login.php" class="nbtn nbtn-line">Track Your Complaint</a>
        </div>
    </section>

    <!-- Quick Stats -->
    <div style="max-width:820px; margin: 0 auto 20px;">
        <div class="stats-grid">
            <div class="stat-box">
                <div class="number"><?= number_format($totalReports) ?></div>
                <div class="label">Complaints Filed</div>
            </div>
            <div class="stat-box">
                <div class="number"><?= number_format($totalUsers) ?></div>
                <div class="label">Registered Users</div>
            </div>
            <div class="stat-box">
                <div class="number"><?= number_format($resolved) ?></div>
                <div class="label">Resolved Cases</div>
            </div>
            <div class="stat-box">
                <div class="number"><?= number_format($pending) ?></div>
                <div class="label">Pending Cases</div>
            </div>
        </div>
    </div>

    <!-- How It Works -->
    <section style="max-width:820px; margin: 0 auto 30px; padding: 0 20px;">
        <h2 style="text-align:center; color:var(--wh); font-size:1.6rem; margin-bottom:12px;">How to File a Complaint</h2>
        <div class="steps-mini">
            <div class="step-mini">
                <div class="num">1</div>
                <h4>Register / Login</h4>
                <p>Create a free account or sign in.</p>
            </div>
            <div class="step-mini">
                <div class="num">2</div>
                <h4>Fill the Form</h4>
                <p>Describe the incident, attach evidence (screenshots, PDFs).</p>
            </div>
            <div class="step-mini">
                <div class="num">3</div>
                <h4>Track Progress</h4>
                <p>Get notified when your complaint is assigned and resolved.</p>
            </div>
        </div>
    </section>

    <!-- What Can You Report? – ALL 8 CATEGORIES -->
    <section style="max-width:820px; margin: 0 auto 30px; padding: 0 20px;">
        <h2 style="text-align:center; color:var(--wh); font-size:1.6rem; margin-bottom:8px;">What Can You Report?</h2>
        <p style="text-align:center; color:var(--mu); margin-bottom:16px;">We handle a wide range of cybercrime incidents.</p>
        <div class="complaint-grid">
            <div class="complaint-card">
                <div class="icon">🕵️</div>
                <h4>Cybercrime</h4>
                <p>Social media hacking, phishing, online fraud, identity theft.</p>
            </div>
            <div class="complaint-card">
                <div class="icon">🔐</div>
                <h4>Security Incident</h4>
                <p>Unauthorized access, data breach, account compromise.</p>
            </div>
            <div class="complaint-card">
                <div class="icon">🐞</div>
                <h4>Bug Report</h4>
                <p>Login errors, broken features, system crashes.</p>
            </div>
            <div class="complaint-card">
                <div class="icon">💳</div>
                <h4>Online Fraud</h4>
                <p>Scams, fake websites, payment fraud.</p>
            </div>
            <div class="complaint-card">
                <div class="icon">📱</div>
                <h4>Social Media Abuse</h4>
                <p>Harassment, impersonation, cyberbullying.</p>
            </div>
            <div class="complaint-card">
                <div class="icon">🔒</div>
                <h4>Ransomware</h4>
                <p>File encryption, ransom demands.</p>
            </div>
            <div class="complaint-card">
                <div class="icon">🐛</div>
                <h4>Vulnerability</h4>
                <p>SQL injection, XSS, weak passwords.</p>
            </div>
            <div class="complaint-card">
                <div class="icon">📋</div>
                <h4>Other Issues</h4>
                <p>Anything else related to cybersecurity.</p>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <div style="max-width:820px; margin: 0 auto; padding: 0 20px;">
        <div class="cta-section">
            <h2>Don't Stay Silent. Report It.</h2>
            <p>Your complaint matters. We'll help you get it in front of the right people.</p>
            <div class="hero-btns" style="justify-content:center;">
                <a href="<?= BASE_URL ?>/register.php" class="nbtn nbtn-fill">Start Now</a>
                <a href="<?= BASE_URL ?>/login.php" class="nbtn nbtn-line">Existing User? Sign In</a>
            </div>
        </div>
    </div>

</main>

<footer class="global-footer">
    <p>&copy; <?= date('Y') ?> CyberShield — Cybersecurity Incident Management System</p>
</footer>

</body>
</html>