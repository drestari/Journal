<?php
/**
 * doi_verify.php
 * Verifies a registered DOI for a manuscript.
 * ew_doi_metadata and ew_publication_records do NOT exist.
 * DOI is stored in production.doi.
 */
require_once __DIR__ . '/production_common.php';

$db = db();
$u  = prod_user($db);

$mid = (int)($_GET['id'] ?? 0);
if (!$mid) exit('Invalid manuscript ID.');

$m = prod_ms($db, $mid);

// Load from `production` table (doi column)
$s = $db->prepare("SELECT doi, publication_status, volume, issue, year, pages FROM production WHERE manuscript_id=? LIMIT 1");
$s->execute([$mid]);
$r = $s->fetch(PDO::FETCH_ASSOC);

// Derive metadata_status from whether doi is set and publication_status
$metaStatus = 'draft';
if (!empty($r['doi'])) $metaStatus = 'ready';
if (!empty($r['publication_status'])) $metaStatus = 'registered';

prod_header('DOI Verification', $u);
?>
<div class="panel">
<h1>DOI Verification</h1>
<p><strong><?= prod_h($m['manuscript_no']) ?></strong> — <?= prod_h($m['title']) ?></p>

<?php if (!$r || !$r['doi']): ?>
    <div class="err">No DOI is recorded for this manuscript. Please complete <a href="doi_metadata.php?id=<?=$mid?>">DOI/Metadata</a> first.</div>
<?php else: ?>
    <p><strong>Recorded DOI:</strong> <?= prod_h($r['doi']) ?></p>
    <p><strong>DOI URL:</strong>
        <a href="https://doi.org/<?= rawurlencode($r['doi']) ?>" target="_blank" rel="noopener">
            https://doi.org/<?= prod_h($r['doi']) ?>
        </a>
    </p>
    <p><strong>Metadata status:</strong> <?= prod_h($metaStatus) ?></p>
    <p><strong>Publication status:</strong> <?= prod_h($r['publication_status'] ?? 'not set') ?></p>
    <p><strong>Volume/Issue/Year:</strong> <?= prod_h('Vol. '.$r['volume'].' / Issue '.$r['issue'].' / '.$r['year']) ?></p>
    <p>
        Use the DOI link above to confirm that the DOI resolves to the intended
        article landing page. A successful DOI resolution is the final external
        registration check.
    </p>
<?php endif; ?>
</div>

<p><a class="button" href="doi_archive.php">← DOI / Archive Management</a></p>
<?php prod_footer(); ?>
