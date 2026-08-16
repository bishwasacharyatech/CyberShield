<?php

require\_once '../includes/config.php';
require\_once '../includes/layout.php';

// Placeholder categories for design preview — Bishwas swaps this for a real DB query today
$categories = \[
    \['id' => 1, 'name' => 'Cybercrime'],
    \['id' => 2, 'name' => 'Security Incident'],
    \['id' => 3, 'name' => 'Vulnerability Report'],
    \['id' => 4, 'name' => 'Bug Report'],
    \['id' => 5, 'name' => 'Online Fraud'],
    \['id' => 6, 'name' => 'Social Media Abuse'],
    \['id' => 7, 'name' => 'Ransomware'],
    \['id' => 8, 'name' => 'Other'],
];

pageStart('Submit Report', 'user');
sidebar('user', 'report');
?>

<div class="pg-title">Submit Incident Report</div>
<div class="pg-sub">Provide all details about the cybersecurity incident. Fields marked \* are required.</div>

<div class="card">
    <form method="POST" enctype="multipart/form-data">
        <div class="fg">
            <label class="fl">Incident Title \*</label>
            <input type="text" name="title" class="fi" placeholder="Brief description of the incident" required>
        </div>

        <div class="grid g3">
            <div class="fg">
                <label class="fl">Category \*</label>
                <select name="category\_id" id="categorySelect" class="fi" required>
                    <option value="">Select category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat\['id'] ?>"><?= e($cat\['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="fg">
                <label class="fl">Severity \*</label>
                <select name="severity" class="fi">
                    <?php foreach (\['Critical', 'High', 'Medium', 'Low'] as $sev): ?>
                        <option <?= $sev === 'Medium' ? 'selected' : '' ?>><?= $sev ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="fg">
                <label class="fl">Incident Date \*</label>
                <input type="date" name="incident\_date" class="fi" max="<?= date('Y-m-d') ?>" required value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <!-- Dynamic fields container -->
        <div id="dynamicFields"></div>

        <div class="fg">
            <label class="fl">Full Incident Description \*</label>
            <textarea name="description" class="fi" placeholder="Describe the whole incident: what happened, how it occurred, who was involved, what was the impact..." required style="min-height:130px"></textarea>
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
            <a href="<?= BASE\_URL ?>/user/dashboard.php" class="btn btn-gy">Cancel</a>
        </div>
    </form>
</div>

<script>
    const categoryFields = {
        'Bug Report': \[
            { name: 'website\_url', label: 'Website / App URL', type: 'text', placeholder: 'e.g., https://example.com/page' },
            { name: 'browser\_device', label: 'Browser \& Device', type: 'text', placeholder: 'e.g., Chrome 120 on Windows 11' },
            { name: 'steps\_to\_reproduce', label: 'Steps to Reproduce', type: 'textarea', placeholder: '1. Go to page... 2. Click on... 3. Error appears...' },
            { name: 'expected\_result', label: 'Expected Result', type: 'text', placeholder: 'What should have happened?' },
            { name: 'actual\_result', label: 'Actual Result', type: 'text', placeholder: 'What actually happened?' },
        ],
        'Cybercrime': \[
            { name: 'suspect\_name', label: 'Suspect Name', type: 'text', placeholder: 'Name of the suspect (if known)' },
            { name: 'suspect\_email', label: 'Suspect Email', type: 'email', placeholder: 'email@example.com' },
            { name: 'suspect\_phone', label: 'Suspect Phone', type: 'tel', placeholder: '98XXXXXXXX' },
            { name: 'suspect\_ip', label: 'Suspect IP Address', type: 'text', placeholder: '192.168.1.1' },
            { name: 'suspect\_social', label: 'Social Media Profile', type: 'text', placeholder: 'Facebook/Instagram/Twitter profile link' },
        ],
        'Security Incident': \[
            { name: 'affected\_system', label: 'Affected System / Account', type: 'text', placeholder: 'e.g., Gmail account, Server 192.168.1.10' },
            { name: 'access\_method', label: 'How was it accessed?', type: 'text', placeholder: 'Phishing, stolen credentials, brute force, etc.' },
            { name: 'data\_exposed', label: 'What data was exposed?', type: 'text', placeholder: 'Emails, passwords, financial data, etc.' },
            { name: 'detection\_method', label: 'How was it detected?', type: 'text', placeholder: 'User report, system alert, monitoring tool, etc.' },
        ],
        'Vulnerability Report': \[
            { name: 'vulnerable\_url', label: 'Vulnerable URL / System', type: 'text', placeholder: 'https://example.com/page.php?id=1' },
            { name: 'vulnerability\_type', label: 'Type of Vulnerability', type: 'text', placeholder: 'SQL Injection, XSS, CSRF, IDOR, etc.' },
            { name: 'impact', label: 'Potential Impact', type: 'text', placeholder: 'What could an attacker do with this vulnerability?' },
            { name: 'reproduction\_steps', label: 'Steps to Reproduce', type: 'textarea', placeholder: 'How can this vulnerability be triggered?' },
        ],
        'Online Fraud': \[
            { name: 'fraud\_amount', label: 'Amount Lost (in NPR)', type: 'number', placeholder: 'e.g., 100000' },
            { name: 'payment\_method', label: 'Payment Method', type: 'text', placeholder: 'eSewa, Khalti, Bank Transfer, PayPal, etc.' },
            { name: 'transaction\_id', label: 'Transaction ID', type: 'text', placeholder: 'TRX-123456789' },
            { name: 'fraud\_website', label: 'Fake Website / App', type: 'text', placeholder: 'URL of the fake website or app name' },
            { name: 'contact\_method', label: 'How were you contacted?', type: 'text', placeholder: 'Phone call, SMS, WhatsApp, Facebook message, etc.' },
        ],
        'Social Media Abuse': \[
            { name: 'platform', label: 'Platform', type: 'text', placeholder: 'Facebook, Instagram, TikTok, Twitter, etc.' },
            { name: 'fake\_profile\_url', label: 'Fake Profile URL', type: 'text', placeholder: 'https://facebook.com/fakeprofile' },
            { name: 'fake\_username', label: 'Fake Username', type: 'text', placeholder: 'Username of the fake account' },
            { name: 'abuse\_type', label: 'Type of Abuse', type: 'text', placeholder: 'Harassment, Impersonation, Cyberbullying, Defamation, etc.' },
            { name: 'evidence\_links', label: 'Evidence Links (screenshots, posts)', type: 'textarea', placeholder: 'Share links or describe the evidence available' },
        ],
        'Ransomware': \[
            { name: 'ransom\_amount', label: 'Ransom Amount Demanded', type: 'text', placeholder: 'e.g., $500 USD or 0.5 BTC' },
            { name: 'payment\_method', label: 'Payment Method Requested', type: 'text', placeholder: 'Bitcoin, eSewa, Bank Transfer, etc.' },
            { name: 'attacker\_contact', label: 'Attacker Contact', type: 'text', placeholder: 'Email, Telegram, WhatsApp number, etc.' },
            { name: 'encrypted\_files', label: 'Affected Files / Systems', type: 'text', placeholder: 'What files or systems were encrypted?' },
            { name: 'ransom\_note', label: 'Ransom Note Content', type: 'textarea', placeholder: 'What did the ransom note say?' },
        ],
        'Other': \[
            { name: 'additional\_info', label: 'Additional Information', type: 'textarea', placeholder: 'Any other relevant details...' },
        ]
    };

    function updateFields() {
        const select = document.getElementById('categorySelect');
        const selectedOption = select.options\[select.selectedIndex];
        const categoryName = selectedOption ? selectedOption.text : null;

        const container = document.getElementById('dynamicFields');
        container.innerHTML = '';

        if (!categoryName || categoryName === 'Select category') {
            container.innerHTML = '<div style="color:var(--mu);padding:10px 0;font-size:13px;">Select a category above to see relevant fields.</div>';
            return;
        }

        const fields = categoryFields\[categoryName] || \[];
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
                        <textarea name="extra\[${field.name}]" class="fi" placeholder="${field.placeholder}" style="min-height:70px"></textarea>
                    </div>
                `;
            } else {
                html += `
                    <div class="fg">
                        <label class="fl">${field.label}</label>
                        <input type="${field.type}" name="extra\[${field.name}]" class="fi" placeholder="${field.placeholder}">
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