<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config.inc.php';
$db = $Config['link'] ?? null;
if (!$db) {
    http_response_code(500);
    exit('<div style="font-family:Arial;padding:40px"><h2>AJSMR database connection failed</h2><p>' .
        htmlspecialchars(mysqli_connect_error()) . '</p></div>');
}

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/* Core journal facts */
$journal   = 'The American Journal of Science and Medical Research';
$issn      = '2377-6196';
$publisher = 'Advaitha Innovative Research Association (AIRA)';
$frequency = 'Quarterly';

/* Fetch editorial board content from DB */
$boardContent = '';
$q = @mysqli_query($db, "SELECT * FROM contentpages WHERE TRIM(title)='Editorial Board' LIMIT 1");
if ($q && ($r = mysqli_fetch_assoc($q))) {
    $boardContent = (string)($r['description'] ?? $r['content'] ?? $r['body'] ?? '');
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Editorial Board :: <?=h($journal)?> (AJSMR)</title>
<meta name="description" content="Meet the Editorial Board of The American Journal of Science and Medical Research (AJSMR), a peer-reviewed open-access journal.">
<meta name="robots" content="index,follow">
<link rel="canonical" href="https://ajsmrjournal.com/editorialboard.php">
<link rel="icon" href="images/favicon.ico">
<meta property="og:type" content="website">
<meta property="og:title" content="Editorial Board | AJSMR">
<meta property="og:url" content="https://ajsmrjournal.com/editorialboard.php">
<link rel="stylesheet" href="ajsmr-homepage-v5-2.css">
<style>
.page-hero{background:#f4f7fb;border-bottom:1px solid #dfe7f0;padding:36px 0 26px}
.page-hero .eyebrow{letter-spacing:.12em;font-size:11px;font-weight:800;color:#55708d;text-transform:uppercase}
.page-hero h1{margin:6px 0 8px;color:#102f4d;font-size:32px;line-height:1.2}
.page-hero p{max-width:760px;margin:0;color:#5a6c7f;font-size:14px}
.page-shell{background:#f7f9fc;padding:40px 0 60px}
.page-layout{display:grid;grid-template-columns:minmax(0,1fr) 290px;gap:26px;align-items:start}
.page-content{background:#fff;border:1px solid #e0e7ef;border-radius:12px;box-shadow:0 8px 26px rgba(16,47,77,.06);padding:32px 38px}
.page-content h2{margin:0 0 20px;color:#123d63;font-size:26px}
.page-sidebar{background:#fff;border:1px solid #e0e7ef;border-radius:12px;box-shadow:0 8px 26px rgba(16,47,77,.06);padding:22px;position:sticky;top:70px}
.page-sidebar h3{margin:0 0 14px;color:#123d63;font-size:18px;border-bottom:2px solid #eef3f8;padding-bottom:10px}
.page-sidebar a{display:block;padding:8px 9px;border-radius:6px;color:#275d82;text-decoration:none;font-size:13px;border-bottom:1px solid #f0f4f8}
.page-sidebar a:last-child{border-bottom:0}
.page-sidebar a:hover{background:#eef5fb}
@media(max-width:900px){.page-layout{grid-template-columns:1fr}.page-sidebar{position:static}}
@media(max-width:600px){.page-content{padding:22px 18px}}
</style>
</head>
<body>
<a class="skip" href="#main">Skip to main content</a>

<?php
$ajsmr_active_nav = 'journal';
require_once __DIR__ . '/includes/ajsmr_header.php';
?>

<main id="main">

<div class="page-hero">
  <div class="container">
    <span class="eyebrow">AJSMR</span>
    <h1>Editorial Board</h1>
    <p>The American Journal of Science and Medical Research (AJSMR) is supported by a distinguished international editorial board of researchers and scholars.</p>
  </div>
</div>

<div class="page-shell">
  <div class="container page-layout">

    <div class="page-content">
      <h2>Editorial Board Members</h2>
      <?php if ($boardContent !== ''): ?>
        <?= $boardContent ?>
      <?php else: ?>
        <p>Editorial Board information is currently being updated. Please check back soon.</p>
      <?php endif; ?>
    </div>

    <?php require_once __DIR__ . '/includes/ajsmr_sidebar.php'; ?>

  </div>
</div>

</main>

<?php require_once __DIR__ . '/includes/ajsmr_footer.php'; ?>

</body>
</html>