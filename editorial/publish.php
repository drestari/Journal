<?php
/**
 * publish.php
 * ew_publication_records, ew_doi_metadata, ew_issue_assignments, ew_production
 * do not exist. All data is in the `production` table:
 *   doi, volume, issue, year, pages, publication_status, proof_status, final_pdf
 * Author proof approval is in ew_author_proof_approval.
 * Galley proof is in ew_galley_proofs.
 */
require_once __DIR__ . '/production_common.php';

$db = db();
$u  = prod_user($db);
$mid = (int)($_GET['id'] ?? $_POST['manuscript_id'] ?? 0);
if (!$mid) exit('Invalid manuscript ID.');

$m = prod_ms($db, $mid);
prod_ensure($db, $mid, (int)$u['id']);

// Load single production row (contains doi, volume, issue, year, pages, publication_status, proof_status)
$prod = $db->prepare("SELECT * FROM production WHERE manuscript_id = ? LIMIT 1");
$prod->execute([$mid]);
$pr = $prod->fetch(PDO::FETCH_ASSOC);

// Latest galley proof
$s = $db->prepare("SELECT * FROM ew_galley_proofs WHERE manuscript_id = ? ORDER BY id DESC LIMIT 1");
$s->execute([$mid]);
$gp = $s->fetch(PDO::FETCH_ASSOC);

// Latest author proof approval
$s = $db->prepare("SELECT * FROM ew_author_proof_approval WHERE manuscript_id = ? ORDER BY id DESC LIMIT 1");
$s->execute([$mid]);
$ap = $s->fetch(PDO::FETCH_ASSOC);

$error = ''; $saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    prod_check_csrf();

    $finalPdf    = trim($_POST['final_pdf'] ?? '');
    $pubStatus   = trim($_POST['pub_status'] ?? 'IN_PRESS');

    if (!in_array($pubStatus, ['IN_PRESS', 'PUBLISHED'], true)) {
        $error = 'Invalid publication status.';
    } elseif (empty($pr['doi'])) {
        $error = 'A DOI must be registered before publication. Please complete DOI/Metadata first.';
    } elseif (empty($pr['volume']) || empty($pr['issue'])) {
        $error = 'Volume and issue must be assigned before publication. Please complete Issue Assignment first.';
    } elseif (empty($gp) || $gp['status'] !== 'approved') {
        $error = 'The latest galley proof must be approved before publishing.';
    } elseif (empty($ap) || $ap['decision'] !== 'approved') {
        $error = 'Author proof approval must be recorded before publishing.';
    } else {
        $db->beginTransaction();
        try {
            // Update production with publication status and final PDF
            $stmt = $db->prepare("UPDATE production SET publication_status=?, final_pdf=? WHERE manuscript_id=?");
            $stmt->execute([$pubStatus, $finalPdf ?: $pr['final_pdf'], $mid]);

            // Update manuscript status
            $msStatus = ($pubStatus === 'PUBLISHED') ? 'PUBLISHED' : 'IN_PRESS';
            $publishedAt = ($pubStatus === 'PUBLISHED') ? date('Y-m-d H:i:s') : null;
            $stmt = $db->prepare("UPDATE manuscripts SET status=?, published_at=COALESCE(published_at,?), updated_at=NOW() WHERE id=?");
            $stmt->execute([$msStatus, $publishedAt, $mid]);

            prod_log($db, $mid, (int)$u['id'], 'publication_update', 'Publication status set to: ' . $pubStatus);
            $db->commit();
            $saved = true;

            if ($pubStatus === 'PUBLISHED') {
                sendWorkflowNotification($db, 'PUBLISHED', $mid, ['doi' => $pr['doi'] ?? '', 'volume_issue' => 'Vol ' . ($pr['volume'] ?? '') . ' Issue ' . ($pr['issue'] ?? '')]);
            } else {
                sendWorkflowNotification($db, 'IN_PRESS', $mid);
            }

            $prod->execute([$mid]);
            $pr = $prod->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = 'Publication could not be completed: ' . $e->getMessage();
        }
    }
}

prod_header('Publish Manuscript', $u);
?>
<div class="panel">
<h1>Publish Manuscript</h1>
<p><strong><?=prod_h($m['manuscript_no'])?></strong> — <?=prod_h($m['title'])?></p>

<?php if($saved): ?>
<div class="ok"><strong>Publication status updated successfully.</strong></div>
<?php endif; ?>
<?php if($error): ?><div class="err"><?=prod_h($error)?></div><?php endif; ?>

<h2>Publication Prerequisites</h2>
<table>
<tr><th>Requirement</th><th>Status</th></tr>
<tr><td>DOI Registered</td><td><?=prod_h($pr['doi']?:'Not set')?></td></tr>
<tr><td>Volume / Issue</td><td><?=prod_h(($pr['volume']&&$pr['issue'])?'Vol '.$pr['volume'].' No '.$pr['issue']:'Not assigned')?></td></tr>
<tr><td>Proof Status</td><td><?=prod_h($pr['proof_status']??'PENDING')?></td></tr>
<tr><td>Galley Proof</td><td><?=prod_h($gp['status']??'not created')?></td></tr>
<tr><td>Author Proof Decision</td><td><?=prod_h($ap['decision']??'pending')?></td></tr>
<tr><td>Current Publication Status</td><td><?=prod_h($pr['publication_status']??'not published')?></td></tr>
</table>

<form method="post">
<input type="hidden" name="csrf" value="<?=prod_h(prod_csrf())?>">
<input type="hidden" name="manuscript_id" value="<?=$mid?>">
<label>Publication Status</label>
<select name="pub_status">
<?php foreach(['IN_PRESS'=>'In Press (Online First)','PUBLISHED'=>'Published (Issue)'] as $v=>$l): ?>
<option value="<?=$v?>" <?=($pr['publication_status']===$v?'selected':'')?>><?=$l?></option>
<?php endforeach; ?>
</select>
<label>Final PDF Path (relative to editorial/)</label>
<input name="final_pdf" value="<?=prod_h($pr['final_pdf']??'')?>" placeholder="uploads/articles/filename.pdf">
<button type="submit">Save Publication Status</button>
</form>
</div>
<p><a class="button" href="production.php">← Production Dashboard</a></p>
<?php prod_footer(); ?>
