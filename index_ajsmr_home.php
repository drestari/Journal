<?php
declare(strict_types=1);

/*
 * AJSMR Homepage — Banner Based V1
 * Local testing build.
 * Reads the existing AJSMR database; does not modify legacy data.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config.inc.php';
$db = $Config['link'] ?? null;
if (!$db) {
    http_response_code(500);
    exit('<div style="font:16px Arial;padding:40px"><h2>AJSMR database connection failed</h2><p>' .
        htmlspecialchars(mysqli_connect_error()) . '</p></div>');
}

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function first_value(array $row, array $keys, string $fallback=''): string {
    foreach ($keys as $key) {
        if (isset($row[$key]) && trim((string)$row[$key]) !== '') {
            return trim((string)$row[$key]);
        }
    }
    return $fallback;
}
function clean_text($v, int $max=700): string {
    $s = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string)$v), ENT_QUOTES, 'UTF-8')));
    if (function_exists('mb_strlen') && mb_strlen($s) > $max) {
        return mb_substr($s, 0, $max - 1) . '…';
    }
    return strlen($s) > $max ? substr($s, 0, $max - 1) . '…' : $s;
}
function asset_path(string $p): string {
    $p = str_replace('\\', '/', trim($p));
    while (str_starts_with($p, '../')) $p = substr($p, 3);
    return ltrim($p, '/');
}

/* Existing journal data */
$journal = 'The American Journal of Science and Medical Research';
$shortName = 'AJSMR';
$issn = '2377-6196';
$publisher = 'Advaitha Innovative Research Association (AIRA)';

$welcome = [];
$q = @mysqli_query($db, "SELECT * FROM contentpages WHERE TRIM(title)='Welcome to AJSMR' LIMIT 1");
if ($q && ($r = mysqli_fetch_assoc($q))) $welcome = $r;

$about = clean_text(first_value($welcome, ['description','content','pagecontent','body','details']));
if ($about === '') {
    $about = 'The American Journal of Science and Medical Research provides a professional platform for scholarly communication in science and medical research.';
}

$issues = [];
$q = @mysqli_query($db, "SELECT catid, catename, eventdate, status FROM ajsmr_issueyears WHERE status=1 ORDER BY catid DESC");
if ($q) while ($r = mysqli_fetch_assoc($q)) $issues[] = $r;

$latestIssue = $issues[0] ?? [];
$latestIssueId = (int)($latestIssue['catid'] ?? 0);
$latestIssueName = first_value($latestIssue, ['catename'], 'Current Issue');

$articles = [];
if ($latestIssueId) {
    $stmt = mysqli_prepare($db, "SELECT * FROM ajsmr_issuecontent WHERE status=1 AND catid=? ORDER BY contentid DESC");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $latestIssueId);
        mysqli_stmt_execute($stmt);
        $rs = mysqli_stmt_get_result($stmt);
        while ($r = mysqli_fetch_assoc($rs)) $articles[] = $r;
        mysqli_stmt_close($stmt);
    }
}
if (!$articles) {
    $q = @mysqli_query($db, "SELECT * FROM ajsmr_issuecontent WHERE status=1 ORDER BY contentid DESC LIMIT 6");
    if ($q) while ($r = mysqli_fetch_assoc($q)) $articles[] = $r;
}

$articleTotal = 0;
$q = @mysqli_query($db, "SELECT COUNT(*) AS c FROM ajsmr_issuecontent WHERE status=1");
if ($q && ($r = mysqli_fetch_assoc($q))) $articleTotal = (int)$r['c'];

$issueYear = date('Y');
if (!empty($latestIssue['eventdate'])) {
    $ts = strtotime((string)$latestIssue['eventdate']);
    if ($ts) $issueYear = date('Y', $ts);
}

$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Periodical',
    'name' => $journal,
    'alternateName' => $shortName,
    'issn' => $issn,
    'url' => 'https://ajsmrjournal.com/',
    'publisher' => [
        '@type' => 'Organization',
        'name' => $publisher
    ],
    'inLanguage' => 'en'
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?=h($journal)?> | AJSMR</title>
<meta name="description" content="The American Journal of Science and Medical Research (AJSMR), a peer-reviewed open-access journal publishing research across science and medicine.">
<meta name="robots" content="index,follow">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="canonical" href="https://ajsmrjournal.com/">
<link rel="icon" href="images/favicon.ico">
<meta property="og:type" content="website">
<meta property="og:title" content="<?=h($journal)?>">
<meta property="og:description" content="Peer-reviewed open-access research in science and medicine.">
<meta property="og:url" content="https://ajsmrjournal.com/">
<link rel="stylesheet" href="ajsmr-homepage-final.css">
<script type="application/ld+json"><?=json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?></script>
</head>
<body>

<a class="skip" href="#main">Skip to main content</a>

<div class="topbar">
  <div class="container topbar-inner">
    <div class="top-left">
      <span>AJSMR</span>
      <span>Scholarly publishing in science and medical research</span>
    </div>
    <div class="top-right">
      <a href="authorguidelines.php">Author Guidelines</a>
      <span>|</span>
      <a href="contactus.php">Contact</a>
      <span>|</span>
      <a href="editorial/login.php">Editorial Login</a>
    </div>
  </div>
</div>

<header class="site-header">
  <div class="container identity">
    <a class="brand" href="index_ajsmr_home.php" aria-label="AJSMR Home">
      <span class="brand-mark">AJSMR</span>
      <span class="brand-title"><?=h($journal)?></span>
    </a>

    <div class="header-facts">
      <div><small>ISSN</small><strong><?=h($issn)?></strong></div>
      <div><small>PUBLISHER</small><strong>AIRA</strong></div>
      <div><small>ACCESS</small><strong>Open Access</strong></div>
    </div>

    <a class="header-submit" href="submitmanuscript_v4.php">Submit Manuscript <span>→</span></a>
  </div>

  <nav class="main-nav" aria-label="Main navigation">
    <div class="container nav-inner">
      <button class="menu-button" id="menuButton" type="button" aria-expanded="false">Menu</button>

      <div class="nav-links" id="navLinks">
        <a class="active" href="index_ajsmr_home.php">Home</a>
        <a href="editorialboard.php">Editorial Board</a>
        <a href="authorguidelines.php">For Authors</a>
        <a href="currentissue.php">Current Issue</a>
        <a href="archives.php">Archives</a>

        <div class="nav-dropdown">
          <button type="button" class="dropdown-trigger">Policies <span>▾</span></button>
          <div class="dropdown-panel">
            <a href="policies.php">Policy Hub</a>
            <a href="publicationethics.php">Publication Ethics</a>
            <a href="peerreviewpolicy.php">Peer Review Policy</a>
            <a href="plagiarismresearchintegritypolicy.php">Plagiarism &amp; Research Integrity</a>
            <a href="openaccesscopyrightpolicy.php">Open Access &amp; Copyright</a>
            <a href="privacydataprotectionpolicy.php">Privacy &amp; Data Protection</a>
            <a href="archivingdigitalpolicy.php">Digital Archiving</a>
          </div>
        </div>

        <a href="contactus.php">Contact</a>
      </div>

      <a class="nav-submit" href="submitmanuscript_v4.php">Submit Manuscript <span>→</span></a>
    </div>
  </nav>
</header>

<main id="main">

<!-- Final AJSMR banner. Text remains part of the image as requested. -->
<section class="hero">
  <div class="hero-image-wrap">
    <img class="hero-image" src="images/ajsmr-homepage-hero.png"
         alt="AJSMR — The American Journal of Science and Medical Research">
    <!-- These are real HTML links positioned over the two CTA buttons in the banner. -->
    <a class="hero-hotspot issue-hotspot" href="currentissue.php" aria-label="View Current Issue"></a>
    <a class="hero-hotspot submit-hotspot" href="submitmanuscript_v4.php" aria-label="Submit Your Research"></a>
  </div>
</section>

<section class="trust-strip">
  <div class="container trust-grid">
    <div><span class="trust-icon">▤</span><div><strong>Peer Reviewed</strong><small>Rigorous scholarly review</small></div></div>
    <div><span class="trust-icon">◉</span><div><strong>Open Access</strong><small>Accessible research</small></div></div>
    <div><span class="trust-icon">✦</span><div><strong>Multidisciplinary</strong><small>Science and medicine</small></div></div>
    <div><span class="trust-icon">◎</span><div><strong>Global Reach</strong><small>Connecting researchers</small></div></div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-heading">
      <div><span class="eyebrow">CURRENT ISSUE</span><h2><?=h($latestIssueName)?></h2></div>
      <a class="text-link" href="currentissue.php">View Current Issue →</a>
    </div>

    <div class="issue-layout">
      <article class="issue-main">
        <div class="issue-label">AJSMR • <?=h($issueYear)?></div>
        <h3>Latest peer-reviewed research in science and medical research</h3>
        <p>Explore the latest published research, article records and full papers available through AJSMR.</p>
        <div class="button-row">
          <a class="button primary" href="currentissue.php">View Current Issue →</a>
          <a class="button outline" href="archives.php">Browse Archives</a>
        </div>
      </article>

      <aside class="journal-card">
        <div class="card-head">JOURNAL INFORMATION</div>
        <div class="info-row"><span>Journal</span><strong>AJSMR</strong></div>
        <div class="info-row"><span>ISSN</span><strong><?=h($issn)?></strong></div>
        <div class="info-row"><span>Review</span><strong>Peer reviewed</strong></div>
        <div class="info-row"><span>Access</span><strong>Open Access</strong></div>
        <div class="info-row"><span>Frequency</span><strong>Quarterly</strong></div>
      </aside>
    </div>
  </div>
</section>

<section class="section light">
  <div class="container">
    <div class="section-heading">
      <div><span class="eyebrow">LATEST RESEARCH</span><h2>Published articles</h2></div>
      <a class="text-link" href="archives.php">All Issues →</a>
    </div>

    <div class="articles">
<?php foreach (array_slice($articles, 0, 6) as $a):
    $id = (int)($a['contentid'] ?? 0);
    $title = first_value($a, ['conttitle'], 'Published article');
    $authors = first_value($a, ['authors']);
    $type = first_value($a, ['type'], 'Research Article');
    $published = first_value($a, ['published']);
    $doi = first_value($a, ['doi']);
    $pdf = asset_path(first_value($a, ['fullpaper','abstract']));
?>
      <article class="article-card">
        <div class="article-accent"></div>
        <div class="article-body">
          <span class="article-type"><?=h($type)?></span>
          <h3><?php if ($id): ?><a href="abstracts_details.php?id=<?=$id?>"><?=h($title)?></a><?php else: ?><?=h($title)?><?php endif; ?></h3>
          <?php if ($authors !== ''): ?><p><?=h($authors)?></p><?php endif; ?>
          <div class="article-meta">
            <?php if ($published !== ''): ?><span><?=h($published)?></span><?php endif; ?>
            <?php if ($doi !== ''): ?><span>DOI</span><?php endif; ?>
          </div>
          <div class="article-actions">
            <?php if ($id): ?><a href="abstracts_details.php?id=<?=$id?>">Article Details →</a><?php endif; ?>
            <?php if ($pdf !== ''): ?><a href="<?=h($pdf)?>" target="_blank" rel="noopener">Full Paper ↗</a><?php endif; ?>
          </div>
        </div>
      </article>
<?php endforeach; ?>
<?php if (!$articles): ?>
      <div class="empty">No published article records were returned from the current AJSMR database.</div>
<?php endif; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container about-grid">
    <div>
      <span class="eyebrow">ABOUT AJSMR</span>
      <h2>Science, medicine and scholarly research</h2>
      <p class="large-text"><?=h($about)?></p>
      <div class="button-row">
        <a class="button outline" href="editorialboard.php">Editorial Board →</a>
        <a class="button outline" href="publicationethics.php">Publication Ethics →</a>
      </div>
    </div>
    <div class="resource-panel">
      <h3>Author &amp; Reader Resources</h3>
      <a href="authorguidelines.php"><strong>Author Guidelines</strong><span>Submission requirements →</span></a>
      <a href="peerreviewpolicy.php"><strong>Peer Review Policy</strong><span>Review process →</span></a>
      <a href="policies.php"><strong>Policy Hub</strong><span>All journal policies →</span></a>
      <a href="contactus.php"><strong>Contact AJSMR</strong><span>Editorial office →</span></a>
    </div>
  </div>
</section>

<section class="pathway">
  <div class="container">
    <div class="center-heading">
      <span class="eyebrow">SCHOLARLY PUBLISHING</span>
      <h2>From submission to publication</h2>
      <p>A structured pathway for authors, editors and reviewers.</p>
    </div>
    <div class="steps">
      <div><span>01</span><strong>Submission</strong><small>Files and declarations</small></div>
      <div><span>02</span><strong>Technical Check</strong><small>Initial assessment</small></div>
      <div><span>03</span><strong>Peer Review</strong><small>Reviewer reports</small></div>
      <div><span>04</span><strong>Decision</strong><small>Revision or acceptance</small></div>
      <div><span>05</span><strong>Publication</strong><small>Production and release</small></div>
    </div>
  </div>
</section>

</main>

<footer>
  <div class="container footer-grid">
    <div>
      <div class="footer-brand">AJSMR</div>
      <p><?=h($journal)?><br>Published by <?=h($publisher)?></p>
      <p>ISSN <?=h($issn)?> • Open Access • Quarterly</p>
    </div>
    <div><h3>Journal</h3><a href="currentissue.php">Current Issue</a><a href="archives.php">Archives</a><a href="editorialboard.php">Editorial Board</a></div>
    <div><h3>Authors</h3><a href="authorguidelines.php">Author Guidelines</a><a href="submitmanuscript_v4.php">Submit Manuscript</a><a href="contactus.php">Contact</a></div>
    <div><h3>Policies</h3><a href="policies.php">Policy Hub</a><a href="publicationethics.php">Publication Ethics</a><a href="peerreviewpolicy.php">Peer Review</a></div>
  </div>
  <div class="container footer-bottom">© <?=date('Y')?> AJSMR • The American Journal of Science and Medical Research • ISSN <?=h($issn)?></div>
</footer>

<script>
(function(){
  const button = document.getElementById('menuButton');
  const nav = document.getElementById('navLinks');
  const dropdown = document.querySelector('.nav-dropdown');
  const trigger = document.querySelector('.dropdown-trigger');

  button.addEventListener('click', function(){
    const open = nav.classList.toggle('open');
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  trigger.addEventListener('click', function(){
    dropdown.classList.toggle('open');
  });

  document.addEventListener('click', function(e){
    if (!dropdown.contains(e.target)) dropdown.classList.remove('open');
  });
})();
</script>
</body>
</html>
