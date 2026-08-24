<?php
require\_once '../includes/config.php';
require\_once '../includes/layout.php';

// Placeholder data for design preview — Bishwas wires the real query + submit logic today
$existingRequest = null;
$msg = '';
$err = '';

pageStart('Apply as Analyst', 'user');
sidebar('user', 'apply-analyst');
?>

<div class="pg-title">Apply to Become a SOC Analyst</div>
<div class="pg-sub">Submit your request for review. An admin will approve or reject it.</div>

<?php if ($err): ?>
    <div class="flash-er">⚠ <?= e($err) ?></div>
<?php endif; ?>

<div class="card" style="max-width:560px">
    <form method="POST">
        <div class="fg">
            <label class="fl">Why do you want to become an analyst? \*</label>
            <textarea name="reason" class="fi" placeholder="Explain your motivation and relevant experience..." required style="min-height:110px"></textarea>
        </div>
        <div class="fg">
            <label class="fl">Relevant Skills (optional)</label>
            <textarea name="skills" class="fi" placeholder="e.g., Networking, Linux, incident response, certifications..." style="min-height:80px"></textarea>
        </div>
        <button type="submit" class="btn btn-cy">📨 Submit Request</button>
    </form>
</div>

<?php pageEnd(); ?>