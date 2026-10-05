<?php
/**
 * production_publication_diagnostic.php
 * Diagnostic page using ACTUAL existing tables only.
 * ew_production, ew_publication_records, ew_doi_metadata, ew_issue_assignments
 * do NOT exist — replaced with `production` table.
 */
require_once __DIR__ . '/production_common.php';

$db = db();
$u  = prod_user($db);
$mid = (int)($_GET['id'] ?? 1);
$m = prod_ms($db, $mid);

function showv($v) {
    return htmlspecialchars((string)($v ?? 'NULL'), ENT_QUOTES, 'UTF-8');
}

// Load production row (replaces ew_production + ew_doi_metadata + ew_issue_assignments + ew_publication_records)
$stmt = $db->prepare("SELECT * FROM production WHERE manuscript_id = ? LIMIT 1");
$stmt->execute([$mid]);
$production = $stmt->fetch(PDO::FETCH_ASSOC);

// Galley proofs (exists)
$stmt = $db->prepare("SELECT * FROM ew_galley_proofs WHERE manuscript_id = ? ORDER BY id DESC LIMIT 3");
$stmt->execute([$mid]);
$proofs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Author proof approvals (exists)
$stmt = $db->prepare("SELECT * FROM ew_author_proof_approval WHERE manuscript_id = ? ORDER BY id DESC LIMIT 3");
$stmt->execute([$mid]);
$approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);

prod_header('Production Publication Diagnostic', $u);
?>
<div class="panel">
<h1>Production Publication Diagnostic</h1>
<p><strong><?= showv($m['manuscript_no']) ?></strong> — <?= showv($m['title']) ?></p>
<p><em>Note: ew_production, ew_publication_records, ew_doi_metadata, ew_issue_assignments do not exist in this schema.
All production data is stored in the <strong>production</strong> table.</em></p>

<h2>1. Production Record</h2>
<?php if ($production): ?>
<table>
<tr><th>Field</th><th>Value</th></tr>
<tr><td>Copyediting Status</td><td><?= showv($production['copyediting_status']) ?></td></tr>
<tr><td>Typesetting Status</td><td><?= showv($production['typesetting_status']) ?></td></tr>
<tr><td>Proof Status</td><td><?= showv($production['proof_status']) ?></td></tr>
<tr><td>Publication Status</td><td><?= showv($production['publication_status']) ?></td></tr>
<tr><td>DOI</td><td><?= showv($production['doi']) ?></td></tr>
<tr><td>Volume</td><td><?= showv($production['volume']) ?></td></tr>
<tr><td>Issue</td><td><?= showv($production['issue']) ?></td></tr>
<tr><td>Year</td><td><?= showv($production['year']) ?></td></tr>
<tr><td>Pages</td><td><?= showv($production['pages']) ?></td></tr>
<tr><td>Final PDF</td><td><?= showv($production['final_pdf']) ?></td></tr>
</table>
<?php else: ?>
<p class="err">No production record found. Will be auto-created when any production page is opened.</p>
<?php endif; ?>

<h2>2. Latest Galley Proofs</h2>
<?php if ($proofs): ?>
<table><tr><th>ID</th><th>Version</th><th>Status</th><th>Sent At</th><th>Approved At</th></tr>
<?php foreach ($proofs as $p): ?>
<tr><td><?= showv($p['id']) ?></td><td><?= showv($p['proof_version']) ?></td>
    <td><?= showv($p['status']) ?></td><td><?= showv($p['sent_at']) ?></td>
    <td><?= showv($p['approved_at']) ?></td></tr>
<?php endforeach; ?>
</table>
<?php else: ?><p>No galley proofs yet.</p><?php endif; ?>

<h2>3. Author Proof Approvals</h2>
<?php if ($approvals): ?>
<table><tr><th>ID</th><th>Decision</th><th>Approved At</th><th>Comments</th></tr>
<?php foreach ($approvals as $a): ?>
<tr><td><?= showv($a['id']) ?></td><td><?= showv($a['decision']) ?></td>
    <td><?= showv($a['approved_at']) ?></td><td><?= showv($a['comments']) ?></td></tr>
<?php endforeach; ?>
</table>
<?php else: ?><p>No author proof approvals yet.</p><?php endif; ?>

<h2>Interpretation</h2>
<?php
$pubStatus = $production['publication_status'] ?? '';
if ($pubStatus === 'PUBLISHED') {
    echo '<p class="ok"><strong>PASS:</strong> Production record shows manuscript is PUBLISHED.</p>';
} elseif ($pubStatus === 'IN_PRESS') {
    echo '<p class="ok"><strong>IN PRESS:</strong> Manuscript is online-first. Final issue assignment pending.</p>';
} elseif ($production) {
    echo '<p class="err"><strong>NOT PUBLISHED:</strong> Production record exists but publication_status is not set.</p>';
} else {
    echo '<p class="err"><strong>NOT STARTED:</strong> No production record yet.</p>';
}
?>
</div>
<p><a class="button" href="production.php">← Production Dashboard</a></p>
<?php prod_footer(); ?>
