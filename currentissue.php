<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

/* ── Core journal facts ── */
$journal   = 'The American Journal of Science and Medical Research';
$issn      = '2377-6196';
$publisher = 'Advaitha Innovative Research Association (AIRA)';
$frequency = 'Quarterly';

require_once __DIR__ . '/config.inc.php';
$db = $Config['link'] ?? null;
if (!$db) {
    http_response_code(500);
    exit('<div style="font-family:Arial;padding:40px"><h2>AJSMR database connection failed</h2><p>' .
        htmlspecialchars(mysqli_connect_error()) . '</p></div>');
}

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function pick(array $row, array $keys, string $fallback = ''): string {
    foreach ($keys as $k) {
        if (isset($row[$k]) && trim((string)$row[$k]) !== '') return trim((string)$row[$k]);
    }
    return $fallback;
}
function issue_image_path($v): string {
    $s = trim(str_replace('\\', '/', (string)$v));
    if ($s === '') return '';
    if (preg_match('~^https?://~i', $s)) return $s;
    while (str_starts_with($s, '../')) $s = substr($s, 3);
    while (str_starts_with($s, './')) $s = substr($s, 2);
    return ltrim($s, '/');
}

/* ── Current / latest issue ── */
$issues = [];
$q = @mysqli_query($db, "SELECT * FROM ajsmr_issueyears WHERE status=1 ORDER BY catid DESC");
if ($q) while ($r = mysqli_fetch_assoc($q)) $issues[] = $r;
$issue    = $issues[0] ?? [];
$issueId  = (int)($issue['catid'] ?? 0);
$issueName = pick($issue, ['catename'], 'Current Issue');
$issueDate = pick($issue, ['eventdate']);
$issueYear = ($issueDate && strtotime($issueDate)) ? date('Y', strtotime($issueDate)) : date('Y');

/* ── Articles in current issue ── */
$articles = [];
if ($issueId) {
    $st = mysqli_prepare($db, "SELECT * FROM ajsmr_issuecontent WHERE status=1 AND catid=? ORDER BY contentid DESC");
    if ($st) {
        mysqli_stmt_bind_param($st, 'i', $issueId);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        while ($r = mysqli_fetch_assoc($rs)) $articles[] = $r;
        mysqli_stmt_close($st);
    }
}
if (!$articles) {
    $q = @mysqli_query($db, "SELECT * FROM ajsmr_issuecontent WHERE status=1 ORDER BY contentid DESC LIMIT 10");
    if ($q) while ($r = mysqli_fetch_assoc($q)) $articles[] = $r;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Current Issue :: <?=h($journal)?> (AJSMR)</title>
<meta name="description" content="Current issue of The American Journal of Science and Medical Research (AJSMR). Browse latest published articles.">
<meta name="robots" content="index,follow">
<link rel="canonical" href="https://ajsmrjournal.com/currentissue.php">
<link rel="icon" href="images/favicon.ico">
<meta property="og:type" content="website">
<meta property="og:title" content="Current Issue | AJSMR">
<meta property="og:url" content="https://ajsmrjournal.com/currentissue.php">
<link rel="stylesheet" href="ajsmr-homepage-v5-2.css">
<link rel="stylesheet" href="css/ajsmr-pdf-viewer.css">
</head>
<body>
<a class="skip" href="#main">Skip to main content</a>

<?php
$ajsmr_active_nav = 'articles';
require_once __DIR__ . '/includes/ajsmr_header.php';
?>

<main id="main">

<section class="section issue-section">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">CURRENT ISSUE</span>
        <h1><?=h($issueName)?></h1>
        <p class="issue-subtitle">Latest published articles in the current issue.</p>
      </div>
      <a class="section-link" href="archives.php">View Archives →</a>
    </div>

    <div class="issue-grid">
      <div class="article-list">
        <?php foreach ($articles as $a):
          $id     = (int)($a['contentid'] ?? 0);
          $title  = pick($a, ['conttitle', 'title'], 'Published Article');
          $authors = pick($a, ['authors', 'authorname']);
          $type   = pick($a, ['type'], 'Research Article');
          $citation = pick($a, ['journal']);
          if ($citation === '') $citation = $journal . ' (' . $issueYear . ')';
          $doi    = trim((string)($a['doi'] ?? ''));
          $photo  = issue_image_path($a['photopath'] ?? '');
        ?>
        <article class="current-article-row">
          <div class="article-image">
            <?php if ($photo): ?>
              <img src="<?=h($photo)?>" alt="<?=h($title)?>" loading="lazy">
            <?php else: ?>
              <div class="article-image-placeholder"><span>AJSMR</span><small>Research Article</small></div>
            <?php endif; ?>
          </div>
          <div class="current-article-content">
            <div class="article-type"><?=h($type)?></div>
            <h3><?php if ($id): ?><a href="abstracts_details.php?id=<?=$id?>"><?=h($title)?></a><?php else: ?><?=h($title)?><?php endif; ?></h3>
            <?php if ($authors !== ''): ?><p class="authors"><strong>Author details:</strong> <?=h($authors)?></p><?php endif; ?>
            <p class="citation"><?=h($citation)?></p>
            <?php if ($doi !== ''): ?>
              <p class="doi"><strong>DOI:</strong> <a href="<?=h($doi)?>" target="_blank" rel="noopener noreferrer"><?=h($doi)?></a></p>
            <?php endif; ?>
            <div class="article-links">
              <?php
              $abs    = trim((string)($a['abstract'] ?? ''));
              $full   = trim((string)($a['fullpaper'] ?? ''));
              $absurl = trim((string)($a['absurl'] ?? ''));
              ?>
              <?php if ($absurl !== ''): ?><a href="<?=h($absurl)?>" target="_blank" rel="noopener">Abstract</a><?php elseif ($abs !== ''): ?><a href="<?=h(issue_image_path($abs))?>" target="_blank" rel="noopener">Abstract</a><?php endif; ?>
              <?php if (($absurl !== '' || $abs !== '') && $full !== ''): ?><span>|</span><?php endif; ?>
              <?php if ($full !== ''): 
                $pdfPath = issue_image_path($full);
              ?>
                <a href="<?=h($pdfPath)?>" data-ajsmr-pdf="<?=h($pdfPath)?>" data-ajsmr-title="<?=h($title)?>" onclick="return openAjsmrPdfViewer(event, this)">Full Paper</a>
              <?php endif; ?>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
        <?php if (!$articles): ?><div class="empty">No current published article records are available.</div><?php endif; ?>
      </div>

      <?php require_once __DIR__ . '/includes/ajsmr_sidebar.php'; ?>
    </div>

  </div>
</section>

</main>

<?php require_once __DIR__ . '/includes/ajsmr_footer.php'; ?>

<script src="js/ajsmr-pdf-viewer.js"></script>
</body>
</html>