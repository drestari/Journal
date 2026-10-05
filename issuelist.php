<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

/* ── Legacy Config and Compatibility ── */
require_once __DIR__ . '/ajsmr@admin99/config/config.inc.php';
require_once __DIR__ . '/ajsmr@admin99/config/pagination.php';

/* ── Core journal facts ── */
$journal   = 'The American Journal of Science and Medical Research';
$issn      = '2377-6196';
$publisher = 'Advaitha Innovative Research Association (AIRA)';
$frequency = 'Quarterly';

if (!function_exists('h')) {
    function h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('ajsmr_issue_clean_path')) {
    function ajsmr_issue_clean_path($v, string $defaultFolder = ''): string {
        $s = trim(str_replace('\\', '/', (string)$v));
        if ($s === '') return '';
        if (preg_match('~^https?://~i', $s)) return $s;
        while (str_starts_with($s, '../')) $s = substr($s, 3);
        while (str_starts_with($s, './')) $s = substr($s, 2);
        $s = ltrim($s, '/');
        if ($defaultFolder !== '' && !str_starts_with($s, $defaultFolder)) {
            $s = $defaultFolder . $s;
        }
        return $s;
    }
}

/* ── Determine Target Issue ── */
$cat_id = 0;
if (isset($_GET['cat_id']) && is_numeric($_GET['cat_id'])) {
    $cat_id = (int)$_GET['cat_id'];
} elseif (isset($_GET['cid']) && is_numeric($_GET['cid'])) {
    $cat_id = (int)$_GET['cid'];
}

// Fallback to latest issue if none passed
if ($cat_id <= 0) {
    $latest_q = mysql_query("SELECT catid, catename, eventdate FROM ajsmr_issueyears WHERE status=1 ORDER BY catid DESC LIMIT 1");
    if ($latest_q && ($latest_row = mysql_fetch_assoc($latest_q))) {
        $cat_id = (int)$latest_row['catid'];
    }
}

/* ── Fetch Issue Details ── */
$issue_name = 'Journal Issues';
$issue_year = '';
if ($cat_id > 0) {
    $issue_q = mysql_query("SELECT catename, eventdate FROM ajsmr_issueyears WHERE catid=" . (int)$cat_id);
    if ($issue_q && ($issue_row = mysql_fetch_assoc($issue_q))) {
        $issue_name = trim((string)$issue_row['catename']);
        if (!empty($issue_row['eventdate']) && strtotime($issue_row['eventdate'])) {
            $issue_year = date('Y', strtotime($issue_row['eventdate']));
        }
    }
}

/* ── Pagination & Articles Query ── */
$q_limit = 32;
$start   = isset($_GET['start']) && is_numeric($_GET['start']) ? max(0, (int)$_GET['start']) : 0;
$filePath = basename($_SERVER['PHP_SELF']);
$otherParams = '&cat_id=' . $cat_id;

$qCount = "SELECT COUNT(*) as total FROM ajsmr_issuecontent WHERE status='1' AND catid='" . (int)$cat_id . "'";
$countRes = mysql_query($qCount);
$total_row = $countRes ? mysql_fetch_assoc($countRes) : null;
$no_rows = $total_row ? (int)$total_row['total'] : 0;

$qArticles = "SELECT * FROM ajsmr_issuecontent WHERE status='1' AND catid='" . (int)$cat_id . "' ORDER BY contentid ASC LIMIT $start, $q_limit";
$rsArticles = mysql_query($qArticles);
$articles = [];
if ($rsArticles) {
    while ($row = mysql_fetch_assoc($rsArticles)) {
        $articles[] = $row;
    }
}

$page_title = $issue_name !== 'Journal Issues' ? ($issue_name . ' :: ' . $journal . ' (AJSMR)') : ('Issues :: ' . $journal . ' (AJSMR)');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($page_title)?></title>
<meta name="description" content="<?=h($issue_name)?> of The American Journal of Science and Medical Research (AJSMR). Browse published articles.">
<meta name="robots" content="index,follow">
<link rel="icon" href="images/favicon.ico">
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
        <span class="eyebrow">JOURNAL ARCHIVES</span>
          <h1><?= h($issue_name) ?></h1>
        <p class="issue-subtitle">Articles published in this issue.</p>
      </div>
      <a class="section-link" href="archives.php">← Back to Archives</a>
    </div>

    <div class="issue-grid">
      <div class="article-list">
        <?php if (!empty($articles)): ?>
          <?php foreach ($articles as $a):
            $id        = (int)($a['contentid'] ?? 0);
            $type      = trim((string)($a['type'] ?? ''));
            if ($type === '') $type = 'Research Article';
            $title     = trim((string)($a['conttitle'] ?? $a['title'] ?? 'Published Article'));
            $authors   = trim((string)($a['authors'] ?? ''));
            $citation  = trim((string)($a['journal'] ?? ''));
            $doi       = trim((string)($a['doi'] ?? ''));
            $rec       = trim((string)($a['recevied'] ?? ''));
            $acc       = trim((string)($a['accepted'] ?? ''));
            $pub       = trim((string)($a['published'] ?? ''));
            $photo     = ajsmr_issue_clean_path($a['photopath'] ?? '', 'issuesimgs/');
            $absurl    = trim((string)($a['absurl'] ?? ''));
            $abstract  = trim((string)($a['abstract'] ?? ''));
            $fullpaper = trim((string)($a['fullpaper'] ?? ''));

            $abs_link = '';
            if ($absurl !== '') {
                $abs_link = $absurl;
            } elseif ($abstract !== '') {
                $abs_link = ajsmr_issue_clean_path($abstract, 'pdffiles/');
            }

            $pdf_link = '';
            if ($fullpaper !== '') {
                $pdf_link = ajsmr_issue_clean_path($fullpaper, 'pdffiles/');
            }
          ?>
          <article class="current-article-row">
            <div class="article-image">
              <?php if ($photo !== ''): ?>
                <img src="<?=h($photo)?>" alt="<?=h($title)?>" loading="lazy">
              <?php else: ?>
                <div class="article-image-placeholder"><span>AJSMR</span><small><?=h($type)?></small></div>
              <?php endif; ?>
            </div>
            <div class="current-article-content">
              <div class="article-type"><?=h($type)?></div>
              <h3>
                <?php if ($id > 0): ?>
                  <a href="abstracts_details.php?id=<?=$id?>"><?=h($title)?></a>
                <?php else: ?>
                  <?=h($title)?>
                <?php endif; ?>
              </h3>
              <?php if ($authors !== ''): ?>
                <p class="authors"><strong>Authors:</strong> <?=h($authors)?></p>
              <?php endif; ?>
              <?php if ($citation !== ''): ?>
                <p class="citation"><?=h($citation)?></p>
              <?php endif; ?>
              <?php if ($doi !== ''): ?>
                <p class="doi"><strong>DOI:</strong> <a href="<?=h($doi)?>" target="_blank" rel="noopener noreferrer"><?=h($doi)?></a></p>
              <?php endif; ?>
              <?php if ($rec !== '' || $acc !== '' || $pub !== ''): ?>
                <p class="dates" style="font-size:12px;color:#6b7280;margin:6px 0;">
                  <?php if ($rec !== ''): ?><strong>Received:</strong> <?=h($rec)?>;&nbsp;<?php endif; ?>
                  <?php if ($acc !== ''): ?><strong>Accepted:</strong> <?=h($acc)?>;&nbsp;<?php endif; ?>
                  <?php if ($pub !== ''): ?><strong>Published:</strong> <?=h($pub)?><?php endif; ?>
                </p>
              <?php endif; ?>
              <div class="article-links">
                <?php if ($abs_link !== ''): ?>
                  <a href="<?=h($abs_link)?>" target="_blank" rel="noopener">Abstract</a>
                <?php endif; ?>
                <?php if ($abs_link !== '' && $pdf_link !== ''): ?><span>|</span><?php endif; ?>
                <?php if ($pdf_link !== ''): ?>
                  <a href="<?=h($pdf_link)?>" data-ajsmr-pdf="<?=h($pdf_link)?>" data-ajsmr-title="<?=h($title)?>" onclick="return openAjsmrPdfViewer(event, this)">Full Paper</a>
                <?php endif; ?>
              </div>
            </div>
          </article>
          <?php endforeach; ?>

          <?php if (function_exists('paginate') && $no_rows > $q_limit): ?>
            <div class="pagination-box" style="margin:30px 0 10px;padding:16px;text-align:center;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
              <?php paginate($start, $q_limit, $no_rows, $filePath, $otherParams); ?>
            </div>
          <?php endif; ?>

        <?php else: ?>
          <div class="empty" style="padding:40px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;text-align:center;color:#64748b;">
            <p>No published articles were found for this issue.</p>
            <p><a href="archives.php" style="color:#0876d1;font-weight:600;">Browse Archives</a></p>
          </div>
        <?php endif; ?>
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
