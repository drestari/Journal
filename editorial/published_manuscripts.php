<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$u = login_required();
$role = (string)($u['role'] ?? '');

$routes = [
    'admin' => 'dashboard.php',
    'editor_in_chief' => 'dashboard.php',
    'editor' => 'dashboard.php',
    'managing_editor' => 'dashboard.php',
    'reviewer' => 'reviewer/index.php',
    'author' => 'author/index.php',
    'production' => 'dashboard.php'
];

if (!in_array($role, ['editor_in_chief', 'admin', 'editor', 'managing_editor'], true)) {
    redirect($routes[$role] ?? 'author/index.php');
}

$pdo = db();

// Fetch only manuscripts whose authoritative status is PUBLISHED
$publishedManuscripts = [];
try {
    $stmt = $pdo->query("
        SELECT m.id, m.manuscript_no, m.title, m.article_type, m.status, m.submitted_at, m.published_at, m.updated_at,
               u.full_name AS author_name, u.email AS author_email,
               p.volume, p.issue, p.year, p.doi, p.pages,
               (SELECT GROUP_CONCAT(ma.author_name ORDER BY ma.author_order ASC SEPARATOR ', ') 
                FROM manuscript_authors ma 
                WHERE ma.manuscript_id = m.id) AS co_authors
        FROM manuscripts m
        LEFT JOIN users u ON u.id = m.corresponding_author_id
        LEFT JOIN production p ON p.manuscript_id = m.id
        WHERE m.status IN ('PUBLISHED', 'published')
        ORDER BY COALESCE(m.published_at, m.updated_at, m.submitted_at) DESC
    ");
    $publishedManuscripts = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $publishedManuscripts = [];
}

$pageTitle = 'All Published Manuscripts';
$pageSubtitle = 'Editor-in-Chief Administration Portal';

include __DIR__ . '/includes/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <div>
    <h1 class="title" style="margin:0 0 4px 0;">All Published Manuscripts</h1>
    <div class="muted">View all manuscripts that have been officially published in the American Journal of Science and Medical Research.</div>
  </div>
  <div>
    <a href="dashboard.php#manuscripts" class="btn light" style="display:inline-flex;align-items:center;gap:6px;">
      &larr; Back to Manuscript Queue
    </a>
  </div>
</div>

<div class="panel" style="padding:0;overflow:hidden;">
  <div style="padding:16px 20px;border-bottom:1px solid #eef2f6;display:flex;justify-content:space-between;align-items:center;background:#ffffff;">
    <h3 style="margin:0;font-size:15px;font-weight:800;color:#092b5f;">
      Published Manuscripts Registry
    </h3>
    <span style="font-size:13px;color:#64748b;font-weight:600;">
      Total: <?=count($publishedManuscripts)?> <?=count($publishedManuscripts) === 1 ? 'manuscript' : 'manuscripts'?>
    </span>
  </div>

  <?php if (empty($publishedManuscripts)): ?>
    <div style="padding:48px 24px;text-align:center;color:#64748b;background:#ffffff;">
      <div style="font-size:36px;margin-bottom:12px;line-height:1;">📚</div>
      <p style="font-size:15px;margin:0 0 6px 0;font-weight:700;color:#334155;">No published manuscripts found.</p>
      <span style="font-size:13px;color:#64748b;">Manuscripts will appear here once they complete the publication workflow and reach Published status.</span>
    </div>
  <?php else: ?>
    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;margin:0;">
        <thead>
          <tr>
            <th style="width:45px;">S.No</th>
            <th>Paper ID</th>
            <th>Title</th>
            <th>Authors</th>
            <th>Article Type</th>
            <th>Publication / Issue</th>
            <th>Published Date</th>
            <th>Status</th>
            <th style="min-width:80px;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($publishedManuscripts as $idx => $r): ?>
            <tr>
              <td><?=($idx + 1)?></td>
              <td><strong><?=e($r['manuscript_no'])?></strong></td>
              <td style="max-width:300px;font-weight:500;">
                <a href="manuscript_view.php?id=<?=(int)$r['id']?>" style="color:#092b5f;text-decoration:none;font-weight:600;">
                  <?=e($r['title'])?>
                </a>
                <?php if (!empty($r['doi'])): ?>
                  <div style="font-size:11.5px;color:#64748b;margin-top:3px;">
                    DOI: <a href="https://doi.org/<?=urlencode($r['doi'])?>" target="_blank" rel="noopener" style="color:#0b5fa5;text-decoration:none;"><?=e($r['doi'])?></a>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <?php 
                  $displayAuthor = !empty($r['co_authors']) ? $r['co_authors'] : ($r['author_name'] ?: 'Author');
                ?>
                <strong><?=e($displayAuthor)?></strong>
                <?php if (!empty($r['author_email'])): ?>
                  <br><small style="color:#64748b;"><?=e($r['author_email'])?></small>
                <?php endif; ?>
              </td>
              <td><?=e($r['article_type'] ?: 'Article')?></td>
              <td>
                <?php
                  $volIssue = [];
                  if (!empty($r['volume'])) $volIssue[] = 'Vol. ' . e($r['volume']);
                  if (!empty($r['issue']))  $volIssue[] = 'Issue ' . e($r['issue']);
                  if (!empty($r['year']))   $volIssue[] = '(' . e($r['year']) . ')';
                  $issueStr = implode(' ', $volIssue);
                ?>
                <?php if (!empty($issueStr)): ?>
                  <span style="font-weight:600;color:#0f284e;"><?=e($issueStr)?></span>
                  <?php if (!empty($r['pages'])): ?>
                    <br><small style="color:#64748b;">pp. <?=e($r['pages'])?></small>
                  <?php endif; ?>
                <?php else: ?>
                  <span style="color:#94a3b8;font-size:12px;">Not Assigned</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($r['published_at'])): ?>
                  <?=date('d M Y', strtotime((string)$r['published_at']))?>
                <?php elseif (!empty($r['updated_at'])): ?>
                  <?=date('d M Y', strtotime((string)$r['updated_at']))?>
                <?php else: ?>
                  <span style="color:#94a3b8;font-size:12px;">—</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge" style="background:#dcfce7;color:#166534;font-weight:700;">Published</span>
              </td>
              <td>
                <a class="btn light" href="manuscript_view.php?id=<?=(int)$r['id']?>" style="padding:5px 10px;font-size:12px;">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
