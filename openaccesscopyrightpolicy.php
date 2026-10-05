<?php
declare(strict_types=1);

/*
 * AJSMR Homepage V5.9 — Article Citation and DOI Link Fix
 * CSS/HTML recreation of the approved homepage design.
 * NO banner/image file is required.
 *
 * Existing AJSMR database is read only.
 */

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
function pick(array $row, array $keys, string $fallback=''): string {
    foreach ($keys as $k) {
        if (isset($row[$k]) && trim((string)$row[$k]) !== '') return trim((string)$row[$k]);
    }
    return $fallback;
}
function plain($v, int $max=650): string {
    $s = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string)$v), ENT_QUOTES, 'UTF-8')));
    if (function_exists('mb_strlen') && mb_strlen($s) > $max) return mb_substr($s, 0, $max-1).'…';
    return strlen($s) > $max ? substr($s,0,$max-1).'…' : $s;
}
function issue_image_path($v): string {
    $s = trim(str_replace('\\','/',(string)$v));
    if ($s === '') return '';
    if (preg_match('~^https?://~i',$s)) return $s;
    while (str_starts_with($s,'../')) $s = substr($s,3);
    while (str_starts_with($s,'./')) $s = substr($s,2);
    return ltrim($s,'/');
}

/* Core journal facts */
$journal = 'The American Journal of Science and Medical Research';
$abbr = 'AJSMR';
$issn = '2377-6196';
$publisher = 'Advaitha Innovative Research Association (AIRA)';
$frequency = 'Quarterly';

/* About text from existing database where available */
$welcome = [];
$q = @mysqli_query($db, "SELECT * FROM contentpages WHERE TRIM(title)='Welcome to AJSMR' LIMIT 1");
if ($q && ($r = mysqli_fetch_assoc($q))) $welcome = $r;
$description = plain(pick($welcome, ['description','content','pagecontent','body','details']));
if ($description === '') {
    $description = 'The American Journal of Science and Medical Research (AJSMR) is an open-access, peer-reviewed journal providing a professional platform for scholarly research, peer review and dissemination of scientific and medical findings.';
}

/* AJSMR established publication year */
$startingYear = '2014';

/* Current/latest issue */
$issues = [];
$q = @mysqli_query($db, "SELECT * FROM ajsmr_issueyears WHERE status=1 ORDER BY catid DESC");
if ($q) while ($r = mysqli_fetch_assoc($q)) $issues[] = $r;
$issue = $issues[0] ?? [];
$issueId = (int)($issue['catid'] ?? 0);
$issueName = pick($issue, ['catename'], 'Current Issue');
$issueDate = pick($issue, ['eventdate']);
$issueYear = ($issueDate && strtotime($issueDate)) ? date('Y', strtotime($issueDate)) : date('Y');

/* Current issue article records */
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
    $q = @mysqli_query($db, "SELECT * FROM ajsmr_issuecontent WHERE status=1 ORDER BY contentid DESC LIMIT 6");
    if ($q) while ($r = mysqli_fetch_assoc($q)) $articles[] = $r;
}

$articleCount = 0;
$q = @mysqli_query($db, "SELECT COUNT(*) AS c FROM ajsmr_issuecontent WHERE status=1");
if ($q && ($r = mysqli_fetch_assoc($q))) $articleCount = (int)$r['c'];

$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Periodical',
    'name' => $journal,
    'alternateName' => $abbr,
    'issn' => $issn,
    'url' => 'https://ajsmrjournal.com/',
    'publisher' => ['@type'=>'Organization','name'=>$publisher],
    'inLanguage' => 'en',
    'isAccessibleForFree' => true
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Open Access, Copyright &amp; Licensing Policy :: The American Journal of Science and Medical Research (AJSMR)</title>
<meta name="description" content="The American Journal of Science and Medical Research (AJSMR) is a peer-reviewed open-access quarterly journal publishing scholarly research across science and medicine.">
<meta name="robots" content="index,follow">
<link rel="canonical" href="https://ajsmrjournal.com/openaccesscopyrightpolicy.php">
<link rel="icon" href="images/favicon.ico">
<meta property="og:type" content="website">
<meta property="og:title" content="<?=h($journal)?>">
<meta property="og:description" content="Peer-reviewed open-access research across science and medicine.">
<meta property="og:url" content="https://ajsmrjournal.com/openaccesscopyrightpolicy.php">
<script type="application/ld+json"><?=json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?></script>
<link rel="stylesheet" href="ajsmr-homepage-v5-2.css">
</head>
<body>
<a class="skip" href="#main">Skip to main content</a>

<?php
$ajsmr_active_nav = 'policies';
require_once __DIR__ . '/includes/ajsmr_header.php';
?>

<main id="main">

<!-- Compact journal information hero -->

<style>
.policy-hero{background:#f4f7fb;border-bottom:1px solid #dfe7f0;padding:42px 0 30px}
.policy-hero .eyebrow{letter-spacing:.12em;font-size:12px;font-weight:800;color:#55708d}
.policy-hero h1{margin:8px 0 10px;color:#102f4d;font-size:36px;line-height:1.2}
.policy-hero p{max-width:850px;margin:0;color:#5a6c7f;font-size:15px}
.policy-shell{background:#f7f9fc;padding:44px 0 60px}
.policy-layout{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:28px;align-items:start}
.policy-content,.policy-sidebar{background:#fff;border:1px solid #e0e7ef;border-radius:12px;box-shadow:0 8px 26px rgba(16,47,77,.06)}
.policy-content{padding:34px 40px}
.policy-content h2{margin:0 0 22px;color:#123d63;font-size:28px}
.policy-content h3{margin:28px 0 10px;color:#164d79;font-size:19px}
.policy-content p,.policy-content li{font-size:15px;line-height:1.78;color:#405367}
.policy-content ul,.policy-content ol{padding-left:25px}
.policy-content a{color:#0b5e96}
.policy-content .policy-updated{margin-top:30px;padding-top:15px;border-top:1px solid #e4eaf0;color:#738092;font-size:13px}
.policy-sidebar{padding:24px;position:sticky;top:20px}
.policy-sidebar h3{margin:0 0 15px;color:#123d63;font-size:19px}
.policy-sidebar ul{list-style:none;margin:0;padding:0}
.policy-sidebar li{padding:8px 0;border-bottom:1px solid #edf1f5;color:#536579;font-size:13px;line-height:1.45}
.policy-sidebar li:last-child{border-bottom:0}
.policy-sidebar strong{color:#263f58}
.policy-nav{margin-top:22px;padding-top:18px;border-top:1px solid #e5ebf1}
.policy-nav-title{font-size:13px;font-weight:800;color:#123d63;margin-bottom:8px}
.policy-nav a{display:block;padding:7px 8px;border-radius:6px;color:#275d82;text-decoration:none;font-size:13px}
.policy-nav a:hover{background:#eef5fb}
@media(max-width:900px){
  .policy-layout{grid-template-columns:1fr}
  .policy-sidebar{position:static}
  .policy-content{padding:26px 22px}
  .policy-hero h1{font-size:29px}
}
</style>

<main id="main">
  <section class="policy-hero">
    <div class="container">
      <span class="eyebrow">AJSMR POLICIES</span>
      <h1>Open Access, Copyright &amp; Licensing Policy</h1>
      <p>The American Journal of Science and Medical Research (AJSMR) — policy and editorial framework.</p>
    </div>
  </section>

  <section class="policy-shell">
    <div class="container policy-layout">
      <article class="policy-content">
        <h2>Open Access, Copyright &amp; Licensing Policy</h2>
<h3>Open Access</h3>
<p>The American Journal of Science and Medical Research (AJSMR) (ISSN: 2377-6196), published by Advaitha Innovative Research Association (AIRA), is a fully open access, peer-reviewed, quarterly journal. All articles are freely available online immediately upon publication, with <strong>no embargo, no subscription, no registration and no payment</strong>.</p>
<p>All content is freely available without charge to the user or his/her institution. Users are allowed to read, download, copy, distribute, print, search, or link to the full texts of the articles, or use them for any other lawful purpose, without asking prior permission from the publisher or the author. This is in accordance with the <a href="https://www.budapestopenaccessinitiative.org/read/" rel="noopener" target="_blank">Budapest Open Access Initiative (BOAI)</a> definition of open access.</p>
<h3>Licence</h3>
<p>
<a href="https://creativecommons.org/licenses/by/4.0/" rel="license noopener" target="_blank"><img alt="Creative Commons Licence CC BY 4.0" height="31" src="https://licensebuttons.net/l/by/4.0/88x31.png" width="88"/></a>
</p>
<p>All articles published in AJSMR are licensed under the <a href="https://creativecommons.org/licenses/by/4.0/" rel="license noopener" target="_blank">Creative Commons Attribution 4.0 International Licence (CC BY 4.0)</a>. Under this licence anyone may:</p>
<ul>
<li><strong>Share</strong>: copy and redistribute the material in any medium or format, and</li>
<li><strong>Adapt</strong>: remix, transform and build upon the material,</li>
</ul>
<p>for any purpose, including commercial use, provided that <strong>appropriate credit</strong> is given to the original authors and to AJSMR as the original source (with the article's DOI or URL), a link to the licence is provided, and any changes are indicated.</p>
<p>The CC BY 4.0 licence applies to all articles published from Volume 12, Issue 3 (July–September 2026) onwards. Articles published before that remain free to read and download without restriction. Because their authors hold the copyright, they are released under CC BY 4.0 once the authors agree. Material from third parties included in an article (for example, a reproduced figure) may be subject to different terms, which are stated in the article.</p>
<h3>Copyright</h3>
<ul>
<li><strong>Authors retain copyright</strong> of their work without restrictions.</li>
<li>Authors grant AJSMR a non-exclusive right to publish the article as the version of record and to identify itself as the original publisher.</li>
<li>On acceptance, the corresponding author signs the <a href="copyrightandmanutemp/cimg234547_copy%20right%20certificate%20ajsmr.doc" target="_blank">Author Copyright &amp; Licence Form</a> on behalf of all authors, confirming that the work is original, that they have the right to publish it, and that it will be released under CC BY 4.0.</li>
<li>Authors must obtain written permission to reuse any previously published figures, tables or text, and state this permission in the manuscript.</li>
</ul>
<h3>Self-Archiving (Deposit) Policy</h3>
<p>Authors may deposit <strong>all versions</strong> of their work, including the submitted version (preprint), the accepted manuscript (postprint) and the final published version (version of record), in any institutional or subject repository, on personal websites, or on academic networking sites, <strong>without any embargo</strong>. We ask that the deposit links to the published article using its DOI.</p>
<p>Posting a preprint before submission does not count as prior publication.</p>
<h3>Machine Readability and Discoverability</h3>
<p>Every article has a persistent DOI registered through Zenodo, and its licence information is shown on the issue pages. Metadata is made available to indexing services to support discovery.</p>
<h3>Related Policies</h3>
<ul>
<li><a href="apcpolicy.php">Article Processing Charges Policy (no fees)</a></li>
<li><a href="archivingdigitalpolicy.php">Archiving &amp; Digital Preservation Policy</a></li>
<li><a href="publicationethicseditorialpolicy.php">Publication Ethics &amp; Editorial Policy</a></li>
</ul>
<h3>Downloadable Resources</h3>
<ul>
<li><a href="copyrightandmanutemp/cimg234547_ajsmr%20paper%20template.doc" target="_blank">Manuscript Template</a></li>
<li><a href="copyrightandmanutemp/cimg234547_copy%20right%20certificate%20ajsmr.doc" target="_blank">Author Copyright &amp; Licence Form</a></li>
</ul>
<p class="policy-updated">Last updated: September 2026</p>
      </article>

      <?php require_once __DIR__ . '/includes/ajsmr_sidebar.php'; ?>
    </div>
  </section>
</main>

<?php require_once __DIR__ . '/includes/ajsmr_footer.php'; ?>

</body>
</html>