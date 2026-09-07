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
        /* ─── Only custom styles not in style.css ─── */
        html {
            scroll-behavior: smooth;
        }

        /* Second nav row */
        .nav-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            padding: 8px 0;
            border-top: 1px solid var(--bd);
            font-size: 0.9rem;
            color: var(--mu);
            background: var(--bg);
        }

        .nav-row a {
            color: var(--mu);
            transition: color 0.2s;
        }

        .nav-row a:hover {
            color: var(--wh);
        }

        /* Complaint cards – existing style */
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

        /* Stats grid – uses .card from global CSS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 16px;
            max-width: 820px;
            margin: 0 auto 20px;
            padding: 0 20px;
        }

        .stats-grid .card {
            text-align: center;
            padding: 16px 12px;
        }

        .stats-grid .number {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .stats-grid .number.cy {
            color: var(--cy);
        }

        .stats-grid .number.gr {
            color: var(--gr);
        }

        .stats-grid .number.am {
            color: var(--am);
        }

        .stats-grid .number.pu {
            color: var(--pu);
        }

        .stats-grid .label {
            font-size: 0.7rem;
            color: var(--mu);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        /* FAQ toggle */
        .faq-item {
            border-bottom: 1px solid var(--bd);
            padding: 16px 0;
        }

        .faq-item:last-child {
            border-bottom: none;
        }

        .faq-item h3 {
            font-size: 1.05rem;
            color: var(--wh);
            font-weight: 600;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }

        .faq-item h3::after {
            content: "▼";
            font-size: 0.8rem;
            color: var(--mu);
            transition: transform 0.3s;
        }

        .faq-item.active h3::after {
            transform: rotate(180deg);
        }

        .faq-item .answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            color: var(--mu);
            font-size: 0.95rem;
            padding-top: 0;
        }

        .faq-item.active .answer {
            max-height: 200px;
            padding-top: 8px;
        }

        @media (max-width: 480px) {
            .nav-row {
                gap: 12px;
                font-size: 0.8rem;
                flex-wrap: wrap;
            }

            .complaint-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- ─── HEADER ─── -->
    <header>
        <!-- First row: logo + auth buttons (reuses .nav) -->
        <div class="nav">
            <div class="logo">
                <span class="c-cyber">Cyber</span><span class="c-shield">Shield</span>
            </div>
            <div class="nav-r">
                <a href="<?= BASE_URL ?>/login.php" class="nbtn nbtn-line">Sign In</a>
                <a href="<?= BASE_URL ?>/register.php" class="nbtn nbtn-fill">Register</a>
            </div>
        </div>

        <!-- Second row: navigation links (Home, About, FAQ) -->
        <div class="nav-row">
            <a href="#home">Home</a>
            <a href="#about">About</a>
            <a href="#faq">FAQ</a>
        </div>
    </header>

    <main>

        <!-- ─── HOME (Hero) ─── -->
        <section id="home" class="hero">
            <h1>Report a Cybercrime. <span>Get Help.</span></h1>
            <p class="subhead">
                CyberShield is a complaint documentation system that <strong>fills the information gap</strong>
                and connects you directly to the platform where you can document your cybercrime case.
                Whether it's online fraud, hacking, social media abuse, or other cybercrimes – file your complaint,
                attach evidence, and track its progress – all in one place.
                <br><br>
                <strong style="color:var(--mu); font-weight:400;">We help you document and track your complaint – we are
                    not a recovery service and cannot reset passwords or intervene directly in ongoing
                    incidents.</strong>
            </p>
            <div class="hero-btns">
                <a href="<?= BASE_URL ?>/register.php" class="nbtn nbtn-fill">File a Complaint →</a>
                <a href="<?= BASE_URL ?>/login.php" class="nbtn nbtn-line">Track Your Complaint</a>
            </div>
        </section>

        <!-- ─── STATS (using .card from global CSS) ─── -->
        <div class="stats-grid">
            <div class="card">
                <div class="number cy"><?= number_format($totalReports) ?></div>
                <div class="label">Complaints Filed</div>
            </div>
            <div class="card">
                <div class="number pu"><?= number_format($totalUsers) ?></div>
                <div class="label">Registered Users</div>
            </div>
            <div class="card">
                <div class="number gr"><?= number_format($resolved) ?></div>
                <div class="label">Resolved Cases</div>
            </div>
            <div class="card">
                <div class="number am"><?= number_format($pending) ?></div>
                <div class="label">Pending Cases</div>
            </div>
        </div>

        <!-- ─── FEATURES ─── -->
        <section class="feat-wrap">
            <h2 style="text-align:center; color:var(--wh); font-size:1.8rem; margin-bottom:4px;">Why CyberShield?</h2>
            <p style="text-align:center; color:var(--mu); max-width:560px; margin:0 auto 28px;">A simple, secure, and
                transparent way to report and track cybercrime complaints.</p>
            <div class="feat-grid">
                <div class="feat">
                    <div class="ic">📋</div>
                    <h3>Submit & Track Reports</h3>
                    <p>Report cybercrime with evidence attachments, then track status in real time.</p>
                </div>
                <div class="feat">
                    <div class="ic">🔍</div>
                    <h3>Analyst Investigation</h3>
                    <p>Register as a SOC analyst and investigate assigned cases with status updates.</p>
                </div>
                <div class="feat">
                    <div class="ic">🛡️</div>
                    <h3>Security Monitoring</h3>
                    <p>Every login and activity is logged and monitored by the system's audit engine.</p>
                </div>
            </div>
        </section>

        <!-- ─── HOW IT WORKS ─── -->
        <section class="steps">
            <div class="steps-in">
                <h2>How to File a Complaint</h2>
                <div class="step-row">
                    <div class="step">
                        <div class="step-num">1</div>
                        <h4>Register / Login</h4>
                        <p>Create a free account or sign in to get started.</p>
                    </div>
                    <div class="step">
                        <div class="step-num">2</div>
                        <h4>Fill the Form</h4>
                        <p>Describe the incident, attach evidence (screenshots, PDFs).</p>
                    </div>
                    <div class="step">
                        <div class="step-num">3</div>
                        <h4>Track Progress</h4>
                        <p>Get notified when your complaint is assigned and resolved.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── ABOUT ─── -->
        <section id="about" style="max-width:820px; margin:40px auto; padding:0 20px;">
            <h2 style="text-align:center; color:var(--wh); font-size:1.8rem; margin-bottom:12px;">About CyberShield</h2>
            <p
                style="color:var(--mu); font-size:1rem; line-height:1.8; text-align:center; max-width:700px; margin:0 auto;">
                CyberShield is a comprehensive platform designed to help individuals and organizations report, track,
                and resolve cybercrime incidents. Our mission is to <strong style="color:var(--wh);">fill the
                    information gap</strong> between victims and law enforcement / security teams.
            </p>
            <p
                style="color:var(--mu); font-size:1rem; line-height:1.8; text-align:center; max-width:700px; margin:12px auto 0;">
                We provide a secure, transparent way to file complaints – whether it's online fraud, hacking, social
                media abuse, ransomware, or any other cybercrime. With dedicated analysts and a full audit trail, we
                ensure every case is handled with care and professionalism.
            </p>
        </section>

        <!-- ─── CATEGORIES ─── -->
        <div style="max-width:860px; margin:0 auto 30px; padding:0 20px;">
            <h2 style="text-align:center; color:var(--wh); font-size:1.8rem; margin-bottom:4px;">What Can You Report?
            </h2>
            <p style="text-align:center; color:var(--mu); margin-bottom:24px;">We handle a wide range of cybercrime
                incidents.</p>
            <div class="complaint-grid">
                <div class="complaint-card">
                    <div class="icon">🕵️</div>
                    <h4>Cybercrime</h4>
                    <p>Hacking, phishing, online fraud, identity theft.</p>
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
        </div>

        <!-- ─── TRUST ─── -->
        <div style="max-width:820px; margin:0 auto 30px; padding:0 20px;">
            <h2 style="text-align:center; color:var(--wh); font-size:1.8rem; margin-bottom:4px;">Why Trust CyberShield?
            </h2>
            <div
                style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px,1fr)); gap:16px; margin-top:12px;">
                <div class="card" style="text-align:center; padding:20px 16px;">
                    <div style="font-size:2rem; margin-bottom:6px;">🔒</div>
                    <h4 style="color:var(--wh); font-size:0.95rem;">Secure & Private</h4>
                    <p style="color:var(--mu); font-size:0.8rem;">Your data is encrypted and only accessible to
                        authorised personnel.</p>
                </div>
                <div class="card" style="text-align:center; padding:20px 16px;">
                    <div style="font-size:2rem; margin-bottom:6px;">📊</div>
                    <h4 style="color:var(--wh); font-size:0.95rem;">Real-time Tracking</h4>
                    <p style="color:var(--mu); font-size:0.8rem;">Get live updates on your complaint status.</p>
                </div>
                <div class="card" style="text-align:center; padding:20px 16px;">
                    <div style="font-size:2rem; margin-bottom:6px;">👥</div>
                    <h4 style="color:var(--wh); font-size:0.95rem;">Dedicated Analysts</h4>
                    <p style="color:var(--mu); font-size:0.8rem;">Qualified SOC analysts investigate every case.</p>
                </div>
            </div>
        </div>

        <!-- ─── CTA ─── -->
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

        <!-- ─── FAQ ─── -->
        <section id="faq" style="max-width:820px; margin: 0 auto 40px; padding: 0 20px;">
            <h2 style="text-align:center; color:var(--wh); font-size:1.8rem; margin-bottom:24px;">Frequently Asked
                Questions</h2>
            <div class="faq-item">
                <h3>What types of incidents can I report?</h3>
                <div class="answer">You can report cybercrimes like phishing, hacking, online fraud, social media abuse,
                    ransomware, vulnerabilities, and more.</div>
            </div>
            <div class="faq-item">
                <h3>Is my data safe and private?</h3>
                <div class="answer">Yes. All data is encrypted and only accessible to authorised personnel. We follow
                    strict security practices.</div>
            </div>
            <div class="faq-item">
                <h3>How do I track my complaint?</h3>
                <div class="answer">Once you file a complaint, you'll get a ticket number. You can check its status
                    anytime from your dashboard.</div>
            </div>
            <div class="faq-item">
                <h3>Can I become an analyst?</h3>
                <div class="answer">Yes. Registered users can apply to become a SOC analyst. Admin reviews the
                    application and grants access.</div>
            </div>
        </section>

    </main>

    <footer class="global-footer">
        <p>&copy; <?= date('Y') ?> CyberShield — Cybersecurity Incident Management System</p>
    </footer>

    <!-- ─── FAQ Toggle ─── -->
    <script>
        document.querySelectorAll('.faq-item h3').forEach(item => {
            item.addEventListener('click', function () {
                this.parentElement.classList.toggle('active');
            });
        });
    </script>

</body>

</html>