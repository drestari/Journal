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

/* ── All archive issues that have published content ── */
$archiveIssues = [];
$q = @mysqli_query($db,
    "SELECT DISTINCT c.catid, c.catename, c.eventdate
     FROM ajsmr_issueyears AS c
     INNER JOIN ajsmr_issuecontent AS p ON p.catid = c.catid
     WHERE p.status = 1
     ORDER BY c.eventdate DESC, c.catid DESC"
);
if ($q) while ($r = mysqli_fetch_assoc($q)) $archiveIssues[] = $r;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Archives :: <?=h($journal)?> (AJSMR)</title>
<meta name="description" content="Browse all archived issues of The American Journal of Science and Medical Research (AJSMR). Open access research across science and medicine.">
<meta name="robots" content="index,follow">
<link rel="canonical" href="https://ajsmrjournal.com/archives.php">
<link rel="icon" href="images/favicon.ico">
<meta property="og:type" content="website">
<meta property="og:title" content="Archives | AJSMR">
<meta property="og:url" content="https://ajsmrjournal.com/archives.php">
<link rel="stylesheet" href="ajsmr-homepage-v5-2.css">
<style>
.page-hero{background:#f4f7fb;border-bottom:1px solid #dfe7f0;padding:36px 0 26px}
.page-hero .eyebrow{letter-spacing:.12em;font-size:11px;font-weight:800;color:#55708d;text-transform:uppercase}
.page-hero h1{margin:6px 0 8px;color:#102f4d;font-size:32px;line-height:1.2}
.page-hero p{max-width:760px;margin:0;color:#5a6c7f;font-size:14px}
.page-shell{background:#f7f9fc;padding:40px 0 60px}
.page-layout{display:grid;grid-template-columns:minmax(0,1fr) 270px;gap:26px;align-items:start}
.archive-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
.archive-card{background:#fff;border:1px solid #d4dfe8;border-radius:6px;overflow:hidden}
.archive-card a{display:flex;align-items:center;justify-content:center;padding:14px 10px;color:#1a3a5c;text-decoration:none;font-size:13px;font-weight:600;line-height:1.35;text-align:center;min-height:58px}
.archive-card a:hover{background:#edf5fb;color:#075fa8}
.page-sidebar{background:#fff;border:1px solid #e0e7ef;border-radius:12px;box-shadow:0 8px 26px rgba(16,47,77,.06);padding:22px;position:sticky;top:70px}
.page-sidebar h3{margin:0 0 14px;color:#123d63;font-size:18px;border-bottom:2px solid #eef3f8;padding-bottom:10px}
.page-sidebar a{display:block;padding:8px 9px;border-radius:6px;color:#275d82;text-decoration:none;font-size:13px;border-bottom:1px solid #f0f4f8}
.page-sidebar a:last-child{border-bottom:0}
.page-sidebar a:hover{background:#eef5fb}
@media(max-width:1100px){.archive-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:900px){.page-layout{grid-template-columns:1fr}.page-sidebar{position:static}.archive-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:600px){.archive-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:400px){.archive-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<a class="skip" href="#main">Skip to main content</a>

<?php
$ajsmr_active_nav = 'articles';
require_once __DIR__ . '/includes/ajsmr_header.php';
?>

<main id="main">

<div class="page-hero">
  <div class="container">
    <span class="eyebrow">Articles</span>
    <h1>Archives</h1>
    <p>Browse all published issues of The American Journal of Science and Medical Research (AJSMR).</p>
  </div>
</div>

<div class="page-shell">
  <div class="container page-layout">

    <div>
      <?php if ($archiveIssues): ?>
        <div class="archive-grid">
          <?php foreach ($archiveIssues as $ai): ?>
            <div class="archive-card">
              <a href="issueslist.php?cat_id=<?=(int)$ai['catid']?>"><?=h($ai['catename'])?></a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p style="color:#5a6c7f;margin-top:12px">No archived issues are currently available.</p>
      <?php endif; ?>
    </div>

    <?php require_once __DIR__ . '/includes/ajsmr_sidebar.php'; ?>

  </div>
</div>

</main>

<?php require_once __DIR__ . '/includes/ajsmr_footer.php'; ?>

</body>
</html>