<?php
require_once '../includes/config.php';
requireAuth('user');
require_once '../includes/layout.php';

$db = getDB();
$uid = $_SESSION['uid'];
$error = '';
$success = '';

$categories = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$title = $severity = $description = $incidentDate = '';
$extraFields = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = trim($_POST['title'] ?? '');
    $categoryId    = (int) ($_POST['category_id'] ?? 0);
    $severity      = $_POST['severity'] ?? 'Medium';
    $description   = trim($_POST['description'] ?? '');
    $incidentDate  = $_POST['incident_date'] ?? '';
    $extraFields   = $_POST['extra'] ?? [];

    $validCategoryIds = array_column($categories, 'id');

    $dateValid = false;
    if ($incidentDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $incidentDate)) {
        [$yy, $mm, $dd] = explode('-', $incidentDate);
        if (checkdate((int) $mm, (int) $dd, (int) $yy) && strtotime($incidentDate) <= strtotime(date('Y-m-d'))) {
            $dateValid = true;
        }
    }

    if (!$title || !$categoryId || !$description || !$incidentDate) {
        $error = 'Please fill all required fields.';
    } elseif (!in_array($categoryId, $validCategoryIds)) {
        $error = 'Invalid category selected.';
    } elseif (!$dateValid) {
        $error = 'Incident date must be a valid date and cannot be in the future.';
    } else {
        $ticketNo = genTicket();

        $extraInfo = '';
        foreach ($extraFields as $key => $value) {
            if (!empty($value)) {
                $label = str_replace('_', ' ', ucfirst($key));
                $extraInfo .= "{$label}: {$value}\n";
            }
        }
        $suspectInfo = trim($extraInfo);

        $stmt = $db->prepare('
            INSERT INTO reports (ticket_no, user_id, category_id, title, severity, description, incident_date, suspect_info)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->bind_param('siisssss', $ticketNo, $uid, $categoryId, $title, $severity, $description, $incidentDate, $suspectInfo);

        if ($stmt->execute()) {
            $reportId = $db->insert_id;

            auditLog('CREATE', 'Report', "Submitted #{$ticketNo}: {$title}");

            $timeline = $db->prepare('INSERT INTO report_timeline (report_id, user_id, action, note) VALUES (?, ?, ?, ?)');
            $note = 'Report submitted by ' . $_SESSION['fname'];
            $action = 'Submitted';
            $timeline->bind_param('iiss', $reportId, $uid, $action, $note);
            $timeline->execute();
                
            if (!empty($_FILES['evidence']['name'])) {
                $file = $_FILES['evidence'];
                $validation = validateUpload($file);

                if ($validation['ok']) {
                    $storedName = uniqid('ev_', true) . '.' . $validation['ext'];
                    if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $storedName)) {
                        $evidenceStmt = $db->prepare('
                            INSERT INTO evidence_files (report_id, uploaded_by, original_name, stored_name, file_type, file_size)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ');
                        $evidenceStmt->bind_param('iisssi', $reportId, $uid, $file['name'], $storedName, $file['type'], $file['size']);
                        $evidenceStmt->execute();
                    }
                } else {
                    $error .= ' File: ' . $validation['err'];
                }
            }

            $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetch_all(MYSQLI_ASSOC);
            foreach ($admins as $admin) {
                addNotif($admin['id'], $reportId, "New report submitted: [{$ticketNo}] {$title} — needs assignment");
            }

            $success = "✅ Report submitted! Ticket: <strong style='color:var(--cy)'>{$ticketNo}</strong>. <a href='" . BASE_URL . "/user/my-reports.php'>Track it →</a>";


        } else {
            $error = 'Submission failed. Please try again.';
        }
    }
}

pageStart('Submit Report', 'user');
sidebar('user', 'report');
?>

<div class="pg-title">Submit Incident Report</div>
<div class="pg-sub">Provide all details about the cybersecurity incident. Fields marked * are required.</div>

<?php if ($success): ?>
    <div class="flash-ok"><?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="flash-er">⚠ <?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" enctype="multipart/form-data">
        <div class="fg">
            <label class="fl">Incident Title *</label>
            <input type="text" name="title" class="fi" placeholder="Brief description of the incident" required value="<?= e($_POST['title'] ?? '') ?>">
        </div>

        <div class="grid g3">
            <div class="fg">
                <label class="fl">Category *</label>
                <select name="category_id" id="categorySelect" class="fi" required>
                    <option value="">Select category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="fg">
                <label class="fl">Severity *</label>
                <select name="severity" class="fi">
                    <?php foreach (['Critical', 'High', 'Medium', 'Low'] as $sev): ?>
                        <option <?= ($_POST['severity'] ?? 'Medium') === $sev ? 'selected' : '' ?>><?= $sev ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="fg">
                <label class="fl">Incident Date *</label>
                <input type="date" name="incident_date" class="fi" max="<?= date('Y-m-d') ?>" required value="<?= e($_POST['incident_date'] ?? date('Y-m-d')) ?>">
            </div>
        </div>

        <div id="dynamicFields"></div>

        <div class="fg">
            <label class="fl">Full Incident Description *</label>
            <textarea name="description" class="fi" placeholder="Describe the whole incident: what happened, how it occurred, who was involved, what was the impact..." required style="min-height:130px"><?= e($_POST['description'] ?? '') ?></textarea>
        </div>

       <div class="fg">
            <label class="fl">Evidence File (optional — max 5MB)</label>
            <input type="file" name="evidence" class="fi" style="padding:8px;cursor:pointer" accept=".jpg,.jpeg,.png,.gif,.pdf,.txt,.doc,.docx">
            <div style="font-size:11px;color:var(--mu);margin-top:4px">
                Allowed: JPG, PNG, PDF, TXT, DOC, DOCX — Max 5MB
            </div>
        </div>

        <div style="display:flex;gap:10px">
            <button type="submit" class="btn btn-cy">📋 Submit Report</button>
            <a href="<?= BASE_URL ?>/user/dashboard.php" class="btn btn-gy">Cancel</a>
        </div>
    </form>
</div>

<script>
    const categoryFields = {
        'Bug Report': [
            { name: 'website_url', label: 'Website / App URL', type: 'text', placeholder: 'e.g., https://example.com/page' },
            { name: 'browser_device', label: 'Browser & Device', type: 'text', placeholder: 'e.g., Chrome 120 on Windows 11' },
            { name: 'steps_to_reproduce', label: 'Steps to Reproduce', type: 'textarea', placeholder: '1. Go to page... 2. Click on... 3. Error appears...' },
            { name: 'expected_result', label: 'Expected Result', type: 'text', placeholder: 'What should have happened?' },
            { name: 'actual_result', label: 'Actual Result', type: 'text', placeholder: 'What actually happened?' },
        ],
        'Cybercrime': [
            { name: 'suspect_name', label: 'Suspect Name', type: 'text', placeholder: 'Name of the suspect (if known)' },
            { name: 'suspect_email', label: 'Suspect Email', type: 'email', placeholder: 'email@example.com' },
            { name: 'suspect_phone', label: 'Suspect Phone', type: 'tel', placeholder: '98XXXXXXXX' },
            { name: 'suspect_ip', label: 'Suspect IP Address', type: 'text', placeholder: '192.168.1.1' },
            { name: 'suspect_social', label: 'Social Media Profile', type: 'text', placeholder: 'Facebook/Instagram/Twitter profile link' },
        ],
        'Security Incident': [
            { name: 'affected_system', label: 'Affected System / Account', type: 'text', placeholder: 'e.g., Gmail account, Server 192.168.1.10' },
            { name: 'access_method', label: 'How was it accessed?', type: 'text', placeholder: 'Phishing, stolen credentials, brute force, etc.' },
            { name: 'data_exposed', label: 'What data was exposed?', type: 'text', placeholder: 'Emails, passwords, financial data, etc.' },
            { name: 'detection_method', label: 'How was it detected?', type: 'text', placeholder: 'User report, system alert, monitoring tool, etc.' },
        ],
        'Vulnerability Report': [
            { name: 'vulnerable_url', label: 'Vulnerable URL / System', type: 'text', placeholder: 'https://example.com/page.php?id=1' },
            { name: 'vulnerability_type', label: 'Type of Vulnerability', type: 'text', placeholder: 'SQL Injection, XSS, CSRF, IDOR, etc.' },
            { name: 'impact', label: 'Potential Impact', type: 'text', placeholder: 'What could an attacker do with this vulnerability?' },
            { name: 'reproduction_steps', label: 'Steps to Reproduce', type: 'textarea', placeholder: 'How can this vulnerability be triggered?' },
        ],
        'Online Fraud': [
            { name: 'fraud_amount', label: 'Amount Lost (in NPR)', type: 'number', placeholder: 'e.g., 100000' },
            { name: 'payment_method', label: 'Payment Method', type: 'text', placeholder: 'eSewa, Khalti, Bank Transfer, PayPal, etc.' },
            { name: 'transaction_id', label: 'Transaction ID', type: 'text', placeholder: 'TRX-123456789' },
            { name: 'fraud_website', label: 'Fake Website / App', type: 'text', placeholder: 'URL of the fake website or app name' },
            { name: 'contact_method', label: 'How were you contacted?', type: 'text', placeholder: 'Phone call, SMS, WhatsApp, Facebook message, etc.' },
        ],
        'Social Media Abuse': [
            { name: 'platform', label: 'Platform', type: 'text', placeholder: 'Facebook, Instagram, TikTok, Twitter, etc.' },
            { name: 'fake_profile_url', label: 'Fake Profile URL', type: 'text', placeholder: 'https://facebook.com/fakeprofile' },
            { name: 'fake_username', label: 'Fake Username', type: 'text', placeholder: 'Username of the fake account' },
            { name: 'abuse_type', label: 'Type of Abuse', type: 'text', placeholder: 'Harassment, Impersonation, Cyberbullying, Defamation, etc.' },
            { name: 'evidence_links', label: 'Evidence Links (screenshots, posts)', type: 'textarea', placeholder: 'Share links or describe the evidence available' },
        ],
        'Ransomware': [
            { name: 'ransom_amount', label: 'Ransom Amount Demanded', type: 'text', placeholder: 'e.g., $500 USD or 0.5 BTC' },
            { name: 'payment_method', label: 'Payment Method Requested', type: 'text', placeholder: 'Bitcoin, eSewa, Bank Transfer, etc.' },
            { name: 'attacker_contact', label: 'Attacker Contact', type: 'text', placeholder: 'Email, Telegram, WhatsApp number, etc.' },
            { name: 'encrypted_files', label: 'Affected Files / Systems', type: 'text', placeholder: 'What files or systems were encrypted?' },
            { name: 'ransom_note', label: 'Ransom Note Content', type: 'textarea', placeholder: 'What did the ransom note say?' },
        ],
        'Other': [
            { name: 'additional_info', label: 'Additional Information', type: 'textarea', placeholder: 'Any other relevant details...' },
        ]
    };

    function updateFields() {
        const select = document.getElementById('categorySelect');
        const selectedOption = select.options[select.selectedIndex];
        const categoryName = selectedOption ? selectedOption.text : null;

        const container = document.getElementById('dynamicFields');
        container.innerHTML = '';

        if (!categoryName || categoryName === 'Select category') {
            container.innerHTML = '<div style="color:var(--mu);padding:10px 0;font-size:13px;">Select a category above to see relevant fields.</div>';
            return;
        }

        const fields = categoryFields[categoryName] || [];
        if (fields.length === 0) {
            container.innerHTML = '<div style="color:var(--mu);padding:10px 0;font-size:13px;">No additional fields for this category.</div>';
            return;
        }

        let html = '<div style="background:var(--bg3);border-radius:8px;padding:14px;margin-bottom:16px;border:1px solid var(--bd)"><div style="font-size:12px;color:var(--mu);margin-bottom:10px;font-weight:600">📋 Additional Details</div><div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">';

        fields.forEach(field => {
            if (field.type === 'textarea') {
                html += `
                    <div class="fg" style="grid-column: span 2;">
                        <label class="fl">${field.label}</label>
                        <textarea name="extra[${field.name}]" class="fi" placeholder="${field.placeholder}" style="min-height:70px"></textarea>
                    </div>
                `;
            } else {
                html += `
                    <div class="fg">
                        <label class="fl">${field.label}</label>
                        <input type="${field.type}" name="extra[${field.name}]" class="fi" placeholder="${field.placeholder}">
                    </div>
                `;
            }
        });

        html += '</div></div>';
        container.innerHTML = html;
    }

    document.getElementById('categorySelect').addEventListener('change', updateFields);
    updateFields();
</script>

<?php pageEnd(); ?>