<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$u = role_required(['author']);
$pageTitle = 'Author Dashboard — AJSMR Portal';
$pdo = db();
$id = (int)$u['id'];

// Real database metrics
$totalCount     = (int)$pdo->query("SELECT COUNT(*) FROM manuscripts WHERE corresponding_author_id=$id")->fetchColumn();
$draftCount     = (int)$pdo->query("SELECT COUNT(*) FROM manuscripts WHERE corresponding_author_id=$id AND status IS NULL")->fetchColumn();
$reviewCount    = (int)$pdo->query("SELECT COUNT(*) FROM manuscripts WHERE corresponding_author_id=$id AND status IN ('SUBMITTED','UNDER_REVIEW','TECHNICAL_CHECK')")->fetchColumn();
$revisionCount  = (int)$pdo->query("SELECT COUNT(*) FROM manuscripts WHERE corresponding_author_id=$id AND status IN ('REVISION_REQUIRED','REVISED_SUBMISSION')")->fetchColumn();
$acceptedCount  = (int)$pdo->query("SELECT COUNT(*) FROM manuscripts WHERE corresponding_author_id=$id AND status IN ('ACCEPTED','PRODUCTION','PROOF_SENT','PROOF_APPROVED')")->fetchColumn();
$publishedCount = (int)$pdo->query("SELECT COUNT(*) FROM manuscripts WHERE corresponding_author_id=$id AND status = 'PUBLISHED'")->fetchColumn();

// Recent 5 submissions
$stmt = $pdo->prepare("SELECT * FROM manuscripts WHERE corresponding_author_id = ? ORDER BY COALESCE(submitted_at, updated_at) DESC, id DESC LIMIT 5");
$stmt->execute([$id]);
$recentRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Badge helper
function apBadge(string $status): string {
    $slug  = strtolower(str_replace(' ','_',$status));
    $label = ucwords(strtolower(str_replace('_',' ',$status)));
    return "<span class=\"ap-badge ap-badge-{$slug}\">{$label}</span>";
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Welcome Banner -->
<div class="ap-welcome-banner">
  <div class="ap-welcome-text">
    <h2>Welcome back, <?=e($u['full_name'] ?? $u['name'] ?? 'Author')?> 👋</h2>
    <p>Track evaluation progress, upload revisions, respond to galley proofs, and manage your published research.</p>
  </div>
  <a href="<?=BASE_URL?>author/submit.php" class="ap-btn ap-btn-white">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
      <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    Submit Manuscript
  </a>
</div>

<!-- Stats Grid -->
<div class="ap-stats-grid">

  <div class="ap-stat-card">
    <div class="ap-stat-header">
      <span class="ap-stat-label">Total</span>
      <div class="ap-stat-icon" style="background:#eff6ff;color:#1d4ed8;">📄</div>
    </div>
    <div class="ap-stat-number"><?=$totalCount?></div>
  </div>

  <div class="ap-stat-card">
    <div class="ap-stat-header">
      <span class="ap-stat-label">Drafts</span>
      <div class="ap-stat-icon" style="background:#f1f5f9;color:#475569;">✏️</div>
    </div>
    <div class="ap-stat-number"><?=$draftCount?></div>
  </div>

  <div class="ap-stat-card">
    <div class="ap-stat-header">
      <span class="ap-stat-label">Under Review</span>
      <div class="ap-stat-icon" style="background:#f0f9ff;color:#0369a1;">🔍</div>
    </div>
    <div class="ap-stat-number"><?=$reviewCount?></div>
  </div>

  <div class="ap-stat-card">
    <div class="ap-stat-header">
      <span class="ap-stat-label">Revisions</span>
      <div class="ap-stat-icon" style="background:#fff7ed;color:#c2410c;">✎</div>
    </div>
    <div class="ap-stat-number"><?=$revisionCount?></div>
  </div>

  <div class="ap-stat-card">
    <div class="ap-stat-header">
      <span class="ap-stat-label">Accepted</span>
      <div class="ap-stat-icon" style="background:#f0fdf4;color:#166534;">✓</div>
    </div>
    <div class="ap-stat-number"><?=$acceptedCount?></div>
  </div>

  <div class="ap-stat-card">
    <div class="ap-stat-header">
      <span class="ap-stat-label">Published</span>
      <div class="ap-stat-icon" style="background:#faf5ff;color:#7e22ce;">🌐</div>
    </div>
    <div class="ap-stat-number"><?=$publishedCount?></div>
  </div>

</div>

<!-- Recent Submissions Card -->
<div class="ap-card">
  <div class="ap-card-header">
    <div>
      <p class="ap-card-title">Recent Submissions</p>
      <p class="ap-card-subtitle">Your latest manuscript submissions and current evaluation statuses.</p>
    </div>
    <a href="<?=BASE_URL?>author/submissions.php" class="ap-btn ap-btn-secondary ap-btn-sm">
      View All &rarr;
    </a>
  </div>

  <?php if (empty($recentRows)): ?>
    <div class="ap-empty-state">
      <span class="ap-empty-icon">📑</span>
      <p class="ap-empty-title">No Manuscripts Yet</p>
      <p class="ap-empty-desc">You haven't submitted any manuscripts yet. Get started with your first submission.</p>
      <a href="<?=BASE_URL?>author/submit.php" class="ap-btn ap-btn-primary">+ Submit Your First Manuscript</a>
    </div>
  <?php else: ?>
    <div class="ap-table-wrap">
      <table class="ap-table">
        <thead>
          <tr>
            <th>Manuscript ID</th>
            <th>Title</th>
            <th>Article Type</th>
            <th>Status</th>
            <th>Date</th>
            <th style="text-align:right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentRows as $r):
            $st = $r['status'] ? strtolower((string)$r['status']) : 'draft';
          ?>
          <tr>
            <td><strong><?=e($r['manuscript_no'] ?: ('DRAFT-'.$r['id']))?></strong></td>
            <td style="max-width:320px;font-weight:600;">
              <a href="<?=BASE_URL?>author/workflow.php?id=<?=(int)$r['id']?>" style="color:var(--ap-text);">
                <?=e($r['title'] ?: 'Untitled Manuscript Draft')?>
              </a>
            </td>
            <td style="color:var(--ap-text-secondary);font-size:13px;"><?=e($r['article_type'] ?: 'Research Article')?></td>
            <td>
              <?php if (!$r['status']): ?>
                <span class="ap-badge ap-badge-draft">Draft</span>
              <?php else: ?>
                <span class="ap-badge ap-badge-<?=e($st)?>"><?=e(slabel((string)$r['status']))?></span>
              <?php endif; ?>
            </td>
            <td style="font-size:12.5px;color:var(--ap-muted);">
              <?=date('d M Y', strtotime((string)($r['submitted_at'] ?? $r['updated_at'])))?>
            </td>
            <td style="text-align:right;">
              <?php if (!$r['status']): ?>
                <a href="<?=BASE_URL?>author/submit.php?id=<?=(int)$r['id']?>" class="ap-btn ap-btn-primary ap-btn-sm">Resume Draft &rarr;</a>
              <?php else: ?>
                <a href="<?=BASE_URL?>author/workflow.php?id=<?=(int)$r['id']?>" class="ap-btn ap-btn-secondary ap-btn-sm">View Workflow &rarr;</a>
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