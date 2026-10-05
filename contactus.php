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

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<meta name="description" content="The American Journal of Science and Medical Research (AJSMR) is a peer-reviewed open-access quarterly journal publishing scholarly research across science and medicine.">
<meta name="robots" content="index,follow">
<link rel="canonical" href="https://ajsmrjournal.com/">
<link rel="icon" href="images/favicon.ico">
<meta property="og:type" content="website">
<meta property="og:title" content="<?=h($journal)?>">
<meta property="og:description" content="Peer-reviewed open-access research across science and medicine.">
<meta property="og:url" content="https://ajsmrjournal.com/">
<script type="application/ld+json"><?=json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?></script>
<link rel="stylesheet" href="ajsmr-homepage-v5-2.css">
<style>
body {
    background-color: #f2f6fa;
}
</style>
<title>Contact Us :: The American Journal of Science and Medical Research (AJSMR)</title>


<style>
.contact-page{padding:34px 0 55px}
.contact-intro{background:#fff;border:1px solid #e3e8ef;border-radius:14px;padding:28px 30px;margin-bottom:24px;box-shadow:0 4px 18px rgba(0,0,0,.04)}
.contact-intro .eyebrow{font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#63758a}
.contact-intro h1{margin:5px 0 8px;color:#123b63;font-size:32px}
.contact-intro p{margin:0;color:#5b6b7c;line-height:1.65}
.contact-layout{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:30px;align-items:start;width:100%}
.contact-main{grid-column:1;grid-row:1;display:grid;grid-template-columns:1fr 1fr;gap:20px;min-width:0}
.contact-card{background:#fff;border:1px solid #e3e8ef;border-radius:14px;padding:25px;box-shadow:0 4px 18px rgba(0,0,0,.04)}
.contact-card.full{grid-column:1/-1}
.contact-card h2{margin:0 0 18px;color:#123b63;font-size:20px}
.contact-inner{height:100%}
.contact-info ul{list-style:none;margin:0;padding:0}
.contact-info li{position:relative;padding:0 0 20px 56px;margin:0}
.contact-info li:last-child{padding-bottom:0}
.contact-info .icon{position:absolute;left:0;top:-3px;width:45px;height:45px;border:1px solid #e1e6ed;border-radius:50%;display:flex;align-items:center;justify-content:center;box-sizing:border-box}
.contact-info .icon svg{width:30px;height:30px;fill:none;stroke:#2748a3;stroke-width:1.35;stroke-linecap:round;stroke-linejoin:round}
.contact-info h5{margin:0 0 4px;color:#111;font-size:20px;font-weight:400}
.contact-info a{color:#0876d1;text-decoration:none}
.contact-info a:hover{text-decoration:underline}
.glance-card{grid-column:2;grid-row:1;background:#fff;border:1px solid #e3e8ef;border-radius:14px;padding:25px;box-shadow:0 4px 18px rgba(0,0,0,.04);align-self:start}
.glance-kicker{font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#63758a;margin-bottom:6px}
.glance-card h2{margin:0 0 18px;color:#123b63;font-size:22px}
.glance-list{border-top:1px solid #e7ebf0}
.glance-row{padding:13px 0;border-bottom:1px solid #e7ebf0;display:flex;flex-direction:column;gap:4px}
.glance-row span{font-size:12px;color:#718096;text-transform:uppercase;letter-spacing:.04em}
.glance-row strong{font-size:14px;color:#233b53;line-height:1.45}
@media(max-width:900px){
  .contact-layout{grid-template-columns:1fr}
  .contact-main{grid-column:1;grid-row:1;grid-template-columns:1fr}
  .glance-card{grid-column:1;grid-row:2}
}
  .contact-main{grid-column:1;grid-row:1}
  .glance-card{grid-column:1;grid-row:2;position:static
}
@media(max-width:650px){
  .contact-main{grid-template-columns:1fr}
  .contact-card.full{grid-column:auto}
  .contact-intro h1{font-size:28px}
}
</style>

</head>
<body>

<a class="skip" href="#main">Skip to main content</a>

<?php
$ajsmr_active_nav = 'contact';
require_once __DIR__ . '/includes/ajsmr_header.php';
?>

<main id="main" class="container contact-page">
  <section class="contact-intro">
    <div class="eyebrow">AJSMR</div>
    <h1>Contact Us</h1>
    <p>For publication-related and general enquiries, please use the contact information below.</p>
  </section>

  <div class="contact-layout">
    <div class="contact-main">
      <section class="contact-card">
        
                        <div class="inner-box">

                            <div class="text">
							   <h2>PUBLISHERS</h2>
                                
                            </div>

                            <div class="contact-info">
                                <ul>
                                    <li>
                                        <span class="icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-6.2 7-12A7 7 0 0 0 5 9c0 5.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg></span>
										<h5>Address</h5> 
										Advaitha Innovative Research<br> Association, # 5-11-717/204,<br> MG Road, Beside MGM Hospital,<br> 
										Warangal, Telangana, India.Mobile: +91-7330985744, +91-7989071642 
  
                                    </li>
                                    <li>
                                        <span class="icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg></span>
                                        <h5>e-Mail</h5><a href="mailto:aira@airaacademy.com">
										aira@airaacademy.com</a><br>
                                    </li>
									
									
									<li>
										<span class="icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18M5 6.5h14M5 17.5h14"/></svg></span>
										<h5>Website</h5><a href="http://airaacademy.com/" target="_blank">www.airaacademy.com</a>
                                    </li>
                                   
                                </ul>
                            </div>
                        </div>
						
						
    </section>
      <section class="contact-card">
        
                        <div class="inner-box">

                            <div class="text">
							   <h2>CHIEF EDITOR</h2>
                               
                            </div>

                            <div class="contact-info">
                                <ul>
                                    <li>
                                        <span class="icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-6.2 7-12A7 7 0 0 0 5 9c0 5.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg></span>
                                        <h5>Address</h5>
                                    Dr. M. Praveen Kumar Ph.D.,<br>
									Synteny Life Sciences Pvt Ltd<br>
									Hyderabad, Telangana State, India<br>
									Email: editorajsmr@gmail.com<br>
                                    </li>
                                    <li>
                                        <span class="icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg></span>
                                        <h5>e-Mail</h5> <a href="maito:editorajsmr@gmail.com">
										editorajsmr@gmail.com</a>
                                    </li>
									<li>
										<span class="icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18M5 6.5h14M5 17.5h14"/></svg></span>
										<h5>Website</h5><a href="http://ajsmrjournal.com" target="_blank">http://ajsmrjournal.com</a>
                                    </li>
									<li>
									
                            
                    </div>
	      </section>
	  
	  <section class="contact-card">
        
                        <div class="inner-box">

                            <div class="text">
							<h2>Contact Person</h2>
                                
                            </div>

                            <div class="contact-info">
                                <ul>
                                    <li>
									<span class="icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-6.2 7-12A7 7 0 0 0 5 9c0 5.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg></span>
									<h5>Address</h5> 
									Dr. M. Praveen Kumar Ph.D.,<br>
									Chief Editor, AJSMR<br>
									Synteny Life Sciences Pvt Ltd<br>
									Hyderabad, Telangana State, India<br>
									Email: editorajsmr@gmail.com<br></a>
										
                                    </li>
									<li>
									
                            </div>
                        
	      </section>
	  
    </div>

    <?php require_once __DIR__ . '/includes/ajsmr_sidebar.php'; ?>
  </div>
</main>

<?php require_once __DIR__ . '/includes/ajsmr_footer.php'; ?>
</body>
</html>
