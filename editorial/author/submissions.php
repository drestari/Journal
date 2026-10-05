<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$u = role_required(['author']);
$pageTitle = 'My Submissions — AJSMR Author Portal';
$pdo = db();

$s = $pdo->prepare('SELECT * FROM manuscripts WHERE corresponding_author_id = ? ORDER BY COALESCE(submitted_at, updated_at) DESC, id DESC');
$s->execute([$u['id']]);
$rows = $s->fetchAll(PDO::FETCH_ASSOC);

// Author progress summary (read-only — no DB changes)
function getAuthorProgress(PDO $db, array $m): array {
    $mid    = (int)$m['id'];
    $status = strtolower((string)$m['status']);

    // Technical check
    $tc = $db->query("SELECT result FROM ew_technical_checks WHERE manuscript_id = $mid ORDER BY id DESC LIMIT 1")->fetchColumn();
    $tcLabel = 'Pending';
    if ($tc === 'passed')             $tcLabel = 'Accepted';
    elseif ($tc === 'failed')         $tcLabel = 'Rejected';
    elseif ($tc === 'minor_corrections') $tcLabel = 'Corrections Required';

    // Reviewer assignment
    $hasRev    = (int)$db->query("SELECT COUNT(*) FROM ew_reviewer_assignments WHERE manuscript_id = $mid AND status <> 'cancelled'")->fetchColumn() > 0;
    $revLabel  = $hasRev ? 'Assigned' : 'Pending';

    // Reviewer report
    $hasReport = (int)$db->query("SELECT COUNT(*) FROM ew_peer_reviews pr JOIN ew_reviewer_assignments ra ON ra.id = pr.assignment_id WHERE ra.manuscript_id = $mid AND pr.submitted_at IS NOT NULL")->fetchColumn() > 0;
    $reportLabel = $hasReport ? 'Received' : ($hasRev ? 'In Progress' : 'Pending');

    // Revision
    $revRow = $db->query("SELECT status FROM ew_revisions WHERE manuscript_id = $mid ORDER BY version_no DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $revisionLabel = 'Pending';
    $needsUpload   = false;
    if ($revRow) {
        if ($revRow['status'] === 'requested') {
            $revisionLabel = 'Requested';
            $needsUpload   = true;
        } elseif ($revRow['status'] === 'received') {
            $revisionLabel = 'Doc Submitted';
        }
    }

    // Proof
    $proofRow        = $db->query("SELECT status FROM ew_galley_proofs WHERE manuscript_id = $mid ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $needsProofReview = false;
    $proofLabel      = 'Pending';
    if ($proofRow) {
        if ($proofRow['status'] === 'sent_to_author' || $status === 'proof_sent') {
            $proofLabel       = 'Review Required';
            $needsProofReview = true;
        } elseif ($proofRow['status'] === 'approved' || $status === 'proof_approved') {
            $proofLabel = 'Approved';
        } elseif (in_array($proofRow['status'], ['corrections_received'], true) || $status === 'proof_response_received') {
            $proofLabel = 'Response Submitted';
        }
    }

    // Final decision
    $decLabel = 'Pending';
    if ($status === 'accepted')  $decLabel = 'Accepted';
    elseif ($status === 'rejected')  $decLabel = 'Rejected';
    elseif ($status === 'published') $decLabel = 'Published';

    return [
        'tc'              => $tcLabel,
        'reviewer'        => $revLabel,
        'report'          => $reportLabel,
        'revision'        => $revisionLabel,
        'decision'        => $decLabel,
        'proof'           => $proofLabel,
        'needsUpload'     => $needsUpload,
        'needsProofReview'=> $needsProofReview,
    ];
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="ap-page-header">
  <div>
    <h1 class="ap-page-title">My Manuscripts</h1>
    <p class="ap-page-subtitle">Track manuscript evaluation progress, reviewer assignment, revisions, and editorial decisions.</p>
  </div>
  <a href="<?=BASE_URL?>author/submit.php" class="ap-btn ap-btn-primary">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
      <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    Submit New Manuscript
  </a>
</div>

<!-- Submissions Card -->
<div class="ap-card">

  <?php if (empty($rows)): ?>
    <div class="ap-empty-state">
      <span class="ap-empty-icon">📑</span>
      <p class="ap-empty-title">No Manuscripts Found</p>
      <p class="ap-empty-desc">You have not submitted any manuscripts yet. Start your first submission now.</p>
      <a href="<?=BASE_URL?>author/submit.php" class="ap-btn ap-btn-primary">Submit Your First Manuscript</a>
    </div>

  <?php else: ?>
    <div class="ap-table-wrap">
      <table class="ap-table">
        <thead>
          <tr>
            <th>Manuscript ID</th>
            <th>Title</th>
            <th>Type</th>
            <th>Status</th>
            <th>Ver.</th>
            <th>Submitted</th>
            <th>Progress</th>
            <th style="text-align:right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r):
            $prog   = getAuthorProgress($pdo, $r);
            $st     = $r['status'] ? strtolower((string)$r['status']) : 'draft';
            $stSlug = str_replace(' ', '_', $st);
          ?>
          <tr>
            <!-- ID -->
            <td><strong><?=e($r['manuscript_no'] ?: ('DRAFT-'.$r['id']))?></strong></td>

            <!-- Title -->
            <td style="max-width:280px;font-weight:600;">
              <a href="workflow.php?id=<?=(int)$r['id']?>"><?=e($r['title'] ?: 'Untitled Draft')?></a>
              <?php if ($prog['needsUpload']): ?>
                <div><span class="ap-action-pill ap-action-pill-warn">✎ Upload Revision Required</span></div>
              <?php endif; ?>
              <?php if ($prog['needsProofReview']): ?>
                <div><span class="ap-action-pill ap-action-pill-info">📄 Proof Review Required</span></div>
              <?php endif; ?>
            </td>

            <!-- Type -->
            <td style="font-size:13px;color:var(--ap-text-secondary);"><?=e($r['article_type'] ?: 'Research Article')?></td>

            <!-- Status -->
            <td>
              <?php if (!$r['status']): ?>
                <span class="ap-badge ap-badge-draft">Draft</span>
              <?php else: ?>
                <span class="ap-badge ap-badge-<?=e($stSlug)?>"><?=e(slabel((string)$r['status']))?></span>
              <?php endif; ?>
            </td>

            <!-- Version -->
            <td style="color:var(--ap-muted);font-size:13px;">v<?=e($r['version_no'] ?? '1')?></td>

            <!-- Date -->
            <td style="font-size:12.5px;color:var(--ap-muted);">
              <?= $r['submitted_at'] ? date('d M Y', strtotime((string)$r['submitted_at'])) : '—' ?>
            </td>

            <!-- Progress overview -->
            <td>
              <div class="ap-mini-progress">
                <div>Tech Check: <strong><?=e($prog['tc'])?></strong></div>
                <div>Reviewer: <strong><?=e($prog['reviewer'])?></strong></div>
                <div>Report: <strong><?=e($prog['report'])?></strong></div>
                <div>Decision:
                  <strong style="color:<?=$prog['decision']==='Accepted'?'var(--ap-success)':($prog['decision']==='Rejected'?'var(--ap-danger)':'inherit')?>">
                    <?=e($prog['decision'])?>
                  </strong>
                </div>
              </div>
            </td>

            <!-- Action -->
            <td style="text-align:right;white-space:nowrap;">
              <?php if (!$r['status']): ?>
                <a href="<?=BASE_URL?>author/submit.php?id=<?=(int)$r['id']?>" class="ap-btn ap-btn-primary ap-btn-sm">Resume &rarr;</a>
              <?php else: ?>
                <a href="workflow.php?id=<?=(int)$r['id']?>" class="ap-btn ap-btn-secondary ap-btn-sm">View Workflow &rarr;</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>