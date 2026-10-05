<?php
declare(strict_types=1);

require_once __DIR__ . '/config.inc.php';
$db = $Config['link'] ?? null;

if (!function_exists('ajsmr_h')) {
    function ajsmr_h($v): string {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('ajsmr_clean_path')) {
    function ajsmr_clean_path($v): string {
        $s = trim(str_replace('\\', '/', (string)$v));
        if ($s === '') return '';
        if (preg_match('~^https?://~i', $s)) return $s;
        while (str_starts_with($s, '../')) $s = substr($s, 3);
        while (str_starts_with($s, './')) $s = substr($s, 2);
        return ltrim($s, '/');
    }
}

if (!function_exists('ajsmr_clean_image_path')) {
    function ajsmr_clean_image_path(array $art): string {
        $raw = '';
        foreach (['photopath', 'cover_image', 'photo_img'] as $k) {
            if (isset($art[$k]) && trim((string)$art[$k]) !== '') {
                $raw = trim((string)$art[$k]);
                break;
            }
        }
        if ($raw === '') return '';
        $s = trim(str_replace('\\', '/', $raw));
        if (preg_match('~^https?://~i', $s)) return $s;
        while (str_starts_with($s, '../')) $s = substr($s, 3);
        while (str_starts_with($s, './')) $s = substr($s, 2);
        $s = ltrim($s, '/');
        if (!str_starts_with($s, 'issuesimgs/') && !str_starts_with($s, 'images/')) {
            $s = 'issuesimgs/' . $s;
        }
        return $s;
    }
}

if (!function_exists('ajsmr_clean_pdf_path')) {
    function ajsmr_clean_pdf_path(array $art): string {
        $raw = '';
        foreach (['pdf_file', 'fullpaper', 'full_paper', 'abstract'] as $k) {
            if (isset($art[$k]) && trim((string)$art[$k]) !== '' && str_contains(strtolower((string)$art[$k]), '.pdf')) {
                $raw = trim((string)$art[$k]);
                break;
            }
        }
        if ($raw === '') return '';
        $s = trim(str_replace('\\', '/', $raw));
        if (preg_match('~^https?://~i', $s)) return $s;
        while (str_starts_with($s, '../')) $s = substr($s, 3);
        while (str_starts_with($s, './')) $s = substr($s, 2);
        $s = ltrim($s, '/');
        if (!str_starts_with($s, 'pdffiles/')) {
            $s = 'pdffiles/' . $s;
        }
        return $s;
    }
}

/* ── Fetch Current Issue & Articles ── */
$current_issue_title = 'Current Issue';
$current_issue_articles = [];
$has_current_issue = false;

if ($db) {
    $issue_q = @mysqli_query($db, "SELECT catid, catename, eventdate FROM ajsmr_issueyears WHERE status=1 ORDER BY catid DESC LIMIT 1");
    if ($issue_q && ($issue_row = mysqli_fetch_assoc($issue_q))) {
        $has_current_issue = true;
        if (!empty($issue_row['catename'])) {
            $current_issue_title = trim($issue_row['catename']);
        }
        $catid = (int)$issue_row['catid'];
        
        $art_st = mysqli_prepare($db, "SELECT * FROM ajsmr_issuecontent WHERE status=1 AND catid=? ORDER BY contentid DESC");
        if ($art_st) {
            mysqli_stmt_bind_param($art_st, 'i', $catid);
            mysqli_stmt_execute($art_st);
            $art_rs = mysqli_stmt_get_result($art_st);
            while ($r = mysqli_fetch_assoc($art_rs)) {
                $current_issue_articles[] = $r;
            }
            mysqli_stmt_close($art_st);
        }
    }
    
    // Fallback if active issue has no articles attached yet
    if (empty($current_issue_articles)) {
        $fallback_q = @mysqli_query($db, "SELECT * FROM ajsmr_issuecontent WHERE status=1 ORDER BY contentid DESC LIMIT 10");
        if ($fallback_q) {
            while ($r = mysqli_fetch_assoc($fallback_q)) {
                $current_issue_articles[] = $r;
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>The American Journal of Science and Medical Research (AJSMR) | Peer-Reviewed Open Access Journal</title>
<meta name="description" content="The American Journal of Science and Medical Research (AJSMR) is an open-access, peer-reviewed journal published by Advaitha Innovative Research Association (AIRA). The journal provides a professional platform for scholarly research, peer review and dissemination of scientific and medical findings. It encourages interdisciplinary collaboration, promotes methodological innovation, and supports the exchange of reliable scientific knowledge across medical, biomedical, pharmaceutical, biological, and computational sciences, fostering research excellence and contributing to advancements in healthcare and scientific understanding.....">
<meta name="robots" content="index,follow">
<link rel="canonical" href="https://ajsmrjournal.com/">
<link rel="icon" href="images/favicon.ico">
<meta property="og:type" content="website">
<meta property="og:title" content="The American Journal of Science and Medical Research">
<meta property="og:description" content="Peer-reviewed open-access research across science and medicine.">
<meta property="og:url" content="https://ajsmrjournal.com/">
<script type="application/ld+json">{"@context":"https://schema.org","@type":"Periodical","name":"The American Journal of Science and Medical Research","alternateName":"AJSMR","issn":"2377-6196","url":"https://ajsmrjournal.com/","publisher":{"@type":"Organization","name":"Advaitha Innovative Research Association (AIRA)"},"inLanguage":"en","isAccessibleForFree":true}</script>
<link rel="stylesheet" href="ajsmr-homepage-v5-2.css">
<style>
/* Editor-in-Chief Leadership Profile Section */
.eic-section {
    background: #404040;
    color: #ffffff;
    padding: 32px 0 34px;
    border-top: 1px solid #363d48;
    border-bottom: 1px solid #363d48;
}
.eic-header {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}
.eic-title {
    font-size: 21px;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    line-height: 1.2;
    letter-spacing: .02em;
}
.eic-divider {
    color: rgba(255, 255, 255, 0.45);
    font-weight: 300;
    font-size: 16px;
    user-select: none;
}
.eic-board-link {
    color: #9ec5fe;
    text-decoration: underline;
    text-underline-offset: 3px;
    font-size: 13.5px;
    font-weight: 600;
    transition: color 0.15s ease;
}
.eic-board-link:hover,
.eic-board-link:focus {
    color: #ffffff;
}
.eic-profile-row {
    display: flex;
    align-items: center;
    gap: 24px;
}
.eic-photo {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    object-position: center;
    display: block;
    flex-shrink: 0;
    border: 3px solid rgba(255, 255, 255, 0.25);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.4);
}
.eic-details {
    min-width: 0;
}
.eic-name {
    color: #ffffff;
    font-size: 19px;
    font-weight: 700;
    margin: 0 0 6px;
    line-height: 1.35;
}
.eic-affiliation {
    color: #cbd5e1;
    font-size: 14px;
    line-height: 1.55;
    margin: 0;
}
@media (max-width: 768px) {
    .eic-section {
        padding: 26px 0 28px;
    }
    .eic-profile-row {
        gap: 18px;
    }
    .eic-photo {
        width: 86px;
        height: 86px;
    }
    .eic-name {
        font-size: 17px;
    }
    .eic-affiliation {
        font-size: 13.5px;
    }
}
@media (max-width: 520px) {
    .eic-header {
        gap: 8px 10px;
        margin-bottom: 16px;
    }
    .eic-title {
        font-size: 19px;
    }
    .eic-board-link {
        font-size: 13px;
    }
    .eic-profile-row {
        flex-direction: row;
        align-items: center;
        gap: 16px;
    }
    .eic-photo {
        width: 76px;
        height: 76px;
    }
    .eic-name {
        font-size: 16px;
    }
    .eic-affiliation {
        font-size: 13px;
    }
}
</style>
</head>

<body>
<a class="skip" href="#main">Skip to main content</a>

<!-- ============================================================
     AJSMR TOP UTILITY BAR
     ============================================================ -->
<div class="utility">
  <div class="container utility-inner">
    <div class="utility-left">
      <span>▥ &nbsp;ISSN (Online): 2377-6196</span>
      <i></i><span>♙ &nbsp;Open Access</span>
      <i></i><span>▣ &nbsp;Quarterly</span>
    </div>
    <div class="utility-right">
      <a href="mailto:editor@ajsmrjournal.com">✉ &nbsp;editor@ajsmrjournal.com</a>
      <i></i>
      <a href="editorial/eic-login.php">↪ &nbsp;Editorial Login</a>
      <i></i>
      <a href="editorial/login.php">↪ &nbsp;Sign In</a>
    </div>
  </div>
</div>

<!-- ============================================================
     AJSMR JOURNAL IDENTITY HEADER
     ============================================================ -->
<header class="identity">
  <div class="container identity-inner">
    <a class="journal-brand" href="index.php">
      <img class="brand-logo-image" src="images/ajsmr-logo.png"
           alt="AJSMR logo">
      <div class="brand-copy">
        <div class="script-title">The American Journal of</div>
        <div class="stencil-title">SCIENCE AND MEDICAL RESEARCH</div>
        <p>Open Access &nbsp; | &nbsp; Peer Reviewed &nbsp; | &nbsp; AIRA Publisher</p>
      </div>
    </a>

    <div class="access-mark">
      <div class="lock-symbol">♙</div>
      <div><strong>OPEN ACCESS</strong><span>Knowledge for a<br>better tomorrow</span></div>
    </div>

    <form class="search" action="search.php" method="get">
      <input type="search" name="q" placeholder="Search articles..." aria-label="Search articles">
      <button type="submit" aria-label="Search">⌕</button>
    </form>
  </div>
</header>

<!-- ============================================================
     AJSMR MAIN NAVIGATION BAR
     ============================================================ -->
<nav class="main-nav" aria-label="Main navigation">
  <div class="container nav-inner">
    <button class="mobile-menu" id="mobileMenu" type="button" aria-expanded="false">☰ Menu</button>

    <div class="nav-links" id="navLinks">
      <a class="active" href="index.php">Home</a>

      <div class="drop">
        <button type="button">Journal Info <span>⌄</span></button>
        <div class="drop-menu">
          <a href="aboutjournal.php">About Journal</a>
          <a href="editorialboard.php">Editorial Board</a>
          <a href="currentissue.php">Current Issue</a>
          <a href="archives.php">Archives</a>
          <a href="contactus.php">Contact Us</a>
        </div>
      </div>

      <div class="drop">
        <button type="button">Policies <span>⌄</span></button>
        <div class="drop-menu">
          <a href="publicationethics.php">Publication Ethics</a>
          <a href="peerreviewpolicy.php">Peer Review Policy</a>
          <a href="openaccesscopyrightpolicy.php">Open Access &amp; Copyright</a>
          <a href="plagiarismresearchintegritypolicy.php">Plagiarism &amp; Research Integrity</a>
          <a href="complaintsappealspolicy.php">Complaints &amp; Appeals</a>
          <a href="archivingdigitalpolicy.php">Digital Archiving</a>
          <a href="privacydataprotectionpolicy.php">Privacy &amp; Data Protection</a>
        </div>
      </div>

      <div class="drop">
        <button type="button">For Authors <span>⌄</span></button>
        <div class="drop-menu">
          <a href="authorguidelines.php">Author Guidelines</a>
          <a href="guidelinestoauthors.php">Guidelines to Authors</a>
          <a href="editorial/login.php">Submit Manuscript</a>
          <a href="ajsmr@admin99/templatesandcopyrightforms.php">Templates &amp; Copyright Forms</a>
        </div>
      </div>

      <div class="drop">
        <button type="button">For Reviewers <span>⌄</span></button>
        <div class="drop-menu">
          <a href="peerreviewpolicy.php">Peer Review Policy</a>
          <a href="publicationethics.php">Reviewer Ethics</a>
          <a href="editorial/login.php">Reviewer Sign In</a>
        </div>
      </div>

      <div class="drop">
        <button type="button">Articles <span>⌄</span></button>
        <div class="drop-menu">
          <a href="currentissue.php">Current Issue</a>
          <a href="archives.php">Archives</a>
        </div>
      </div>

      <a href="contactus.php">Contact</a>
    </div>

    <a class="submit-nav" href="editorial/login.php">Submit Manuscript <span>➤</span></a>
  </div>
</nav>

<script>
/* AJSMR navigation dropdown and mobile-menu script */
(function(){
  var btn=document.getElementById('mobileMenu');
  var nav=document.getElementById('navLinks');
  if(btn && nav){
    btn.addEventListener('click',function(){
      var open=nav.classList.toggle('open');
      btn.setAttribute('aria-expanded',open?'true':'false');
    });
  }
  document.querySelectorAll('.drop>button').forEach(function(b){
    b.addEventListener('click',function(e){
      e.stopPropagation();
      var p=b.parentElement;
      document.querySelectorAll('.drop.open').forEach(function(x){if(x!==p)x.classList.remove('open')});
      p.classList.toggle('open');
    });
  });
  document.addEventListener('click',function(e){
    if(!e.target.closest('.drop')) document.querySelectorAll('.drop.open').forEach(function(x){x.classList.remove('open')});
  });
})();
</script>

<main id="main">

<!-- Compact journal information hero -->
<section class="journal-hero">
  <div class="container journal-hero-grid">

    <div class="hero-intro" style="text-align: justify;">
      <p>
        <strong>The American Journal of Science and Medical Research (AJSMR)</strong>
        is an open-access, peer-reviewed journal published by
        <strong>Advaitha Innovative Research Association (AIRA)</strong>.
        The American Journal of Science and Medical Research (AJSMR) is an open-access, peer-reviewed journal published by Advaitha Innovative Research Association (AIRA). The journal provides a professional platform for scholarly research, peer review and dissemination of scientific and medical findings. It encourages interdisciplinary collaboration, promotes methodological innovation, and supports the exchange of reliable scientific knowledge across medical, biomedical, pharmaceutical,....
      <a href="aimsandscope.php" class="aims-scope-link">
    View full aims &amp; scope &rarr;
</a>
	  </p>

      <div class="hero-actions">
        <a href="editorial/login.php" class="hero-btn primary">Submit Manuscript <span>➤</span></a>
        <a href="currentissue.php" class="hero-btn outline">Current Issue</a>
      </div>
      <a class="more-link" href="policies.php">Journal policies and information →</a>
    </div>

    <div class="hero-highlights">
      <div class="highlights-title">Journal Highlights</div>
      <div class="highlight-table">
        <div><strong>Title</strong><span>The American Journal of Science and Medical Research</span></div>
        <div><strong>Title Abbreviation</strong><span>Am. J. Sci. Med. Res. (AJSMR)</span></div>
        <div><strong>ISSN</strong><span>ISSN (Online): 2377-6196</span></div>
        <div><strong>Starting Year</strong><span>2014</span></div>
        <div><strong>Frequency</strong><span>Quarterly (January–March | April–June | July–September | October–December)</span></div>
        <div><strong>Mode</strong><span>Online</span></div>
        <div><strong>Access</strong><span>Open Access</span></div>
      </div>
    </div>

  </div>
</section>

<!-- Four concise journal features -->
<section class="feature-strip">
  <div class="container feature-grid">
    <div class="feature"><div class="feature-icon">▤</div><div><strong>Peer Reviewed</strong><span>Rigorous scholarly review</span></div></div>
    <div class="feature"><div class="feature-icon">♙</div><div><strong>Open Access</strong><span>Accessible research</span></div></div>
    <div class="feature"><div class="feature-icon">✣</div><div><strong>Multidisciplinary</strong><span>Science and medicine</span></div></div>
    <div class="feature"><div class="feature-icon">◎</div><div><strong>Global Reach</strong><span>Connecting researchers</span></div></div>
  </div>
</section>

<!-- Editor-in-Chief Leadership Profile -->
<section class="eic-section" aria-labelledby="eicHeading">
  <div class="container">
    <div class="eic-header">
      <h2 id="eicHeading" class="eic-title">Editor-in-Chief</h2>
      <span class="eic-divider" aria-hidden="true">|</span>
      <a class="eic-board-link" href="editorialboard.php" target="_blank" rel="noopener noreferrer">View full editorial board</a>
    </div>
    <div class="eic-profile-row">
      <img class="eic-photo" src="images/Praveen Kumar.jpeg" alt="Prof. Estari Mamidala, Editor-in-Chief of AJSMR" width="100" height="100" loading="lazy">
      <div class="eic-details">
        <h3 class="eic-name">Dr. Praveen Kumar, PhD</h3>
        <p class="eic-affiliation">Synteny Life Sciences, Hyderabad, Telangana State, India.</p>
      </div>
    </div>
  </div>
</section>

<!-- Current issue -->
<section class="section issue-section">
  <div class="container">
    <div class="section-head">
      <div><span class="eyebrow">CURRENT ISSUE</span><h2><?=ajsmr_h($current_issue_title)?></h2><p class="issue-subtitle">Latest published articles with article images and full-text links.</p></div>
      <a class="section-link" href="currentissue.php">View Current Issue →</a>
    </div>

    <div class="issue-grid">
      <div class="article-list">
        <?php if (!empty($current_issue_articles)): ?>
          <?php foreach ($current_issue_articles as $art): ?>
            <?php
              $art_title    = $art['conttitle'] ?? '';
              $art_authors  = $art['authors'] ?? '';
              $art_citation = $art['issuedetails'] ?? ($art['journal'] ?? 'The American Journal of Science and Medical Research');
              $art_doi      = trim((string)($art['doi'] ?? ''));
              $art_pdf      = ajsmr_clean_pdf_path($art);
              $art_img      = ajsmr_clean_image_path($art);
              $art_id       = (int)($art['contentid'] ?? 0);
              $art_type     = trim((string)($art['article_type'] ?? ($art['type'] ?? 'Research Article')));
              if ($art_type === '') {
                  $art_type = 'Research Article';
              }

              $doi_url = '';
              if ($art_doi !== '') {
                  if (preg_match('~^https?://~i', $art_doi)) {
                      $doi_url = $art_doi;
                  } else {
                      $doi_url = 'https://dx.doi.org/' . ltrim($art_doi, '/');
                  }
              }
            ?>
            <article class="article-row">
              <div class="article-image">
                <?php if ($art_img !== ''): ?>
                  <img src="<?=ajsmr_h($art_img)?>" alt="<?=ajsmr_h($art_title)?>" loading="lazy">
                <?php else: ?>
                  <img src="images/logo-icon.png" alt="<?=ajsmr_h($art_title)?>" loading="lazy">
                <?php endif; ?>
              </div>
              <div class="article-content">
                <div class="article-type"><?=ajsmr_h($art_type)?></div>
                <h3><a href="abstracts_details.php?id=<?=$art_id?>"><?=ajsmr_h($art_title)?></a></h3>
                <?php if ($art_authors !== ''): ?>
                  <p class="authors"><strong>Author details:</strong> <?=ajsmr_h($art_authors)?></p>
                <?php endif; ?>
                <?php if ($art_citation !== ''): ?>
                  <p class="citation"><?=ajsmr_h($art_citation)?></p>
                <?php endif; ?>
                <?php if ($art_doi !== ''): ?>
                  <p class="doi"><strong>DOI:</strong>
                    <a href="<?=ajsmr_h($doi_url)?>" target="_blank" rel="noopener noreferrer"><?=ajsmr_h($art_doi)?></a>
                  </p>
                <?php endif; ?>
                <div class="article-links">
                  <a href="abstracts_details.php?id=<?=$art_id?>">Abstract</a>
                  <?php if ($art_pdf !== ''): ?>
                    <span>|</span>
                    <a href="<?=ajsmr_h($art_pdf)?>" target="_blank" rel="noopener">Full Paper</a>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="padding: 30px; background: #f8fafc; border-radius: 8px; text-align: center; border: 1px solid #e2e8f0; width: 100%;">
            <p style="margin: 0; color: #64748b; font-size: 16px;">No current issue available at this time.</p>
          </div>
        <?php endif; ?>
      </div>

      <aside class="sidebar">
        <div class="side-card">
          <div class="side-title">JOURNAL INFORMATION</div>
          <div class="side-row"><span>Title</span><strong>The American Journal of Science and Medical Research</strong></div>
          <div class="side-row"><span>Abbreviation</span><strong>AJSMR</strong></div>
          <div class="side-row"><span>ISSN</span><strong>2377-6196</strong></div>
          <div class="side-row"><span>Starting Year</span><strong>2014</strong></div>
          <div class="side-row"><span>Frequency</span><strong>Quarterly</strong></div>
          <div class="side-row"><span>Access</span><strong>Open Access</strong></div>
          <div class="side-row"><span>Publisher</span><strong>AIRA</strong></div>
        </div>

        <div class="side-card">
          <div class="side-title">AUTHOR RESOURCES</div>
          <a class="side-link" href="authorguidelines.php">Author Guidelines →</a>
          <a class="side-link" href="editorial/login.php">Submit Your Research →</a>
         
          <a class="side-link" href="archives.php">Journal Archives →</a>
        </div>
      </aside>
    </div>
  </div>
</section>



<section class="pathway">
  <div class="container">
    <div class="center">
      <span class="eyebrow">PUBLICATION PROCESS</span>
      <h2>From submission to publication</h2>
      <p>A structured pathway for manuscript submission, technical assessment, peer review, editorial decision and publication.</p>
    </div>
    <div class="steps">
      <div><b>01</b><strong>Submission</strong><span>Submission to first decision</span></div>
      <div><b>02</b><strong>Technical Check</strong><span>Submission to decision after review</span></div>
      <div><b>03</b><strong>Peer Review</strong><span>Submission to acceptance</span></div>
      <div><b>04</b><strong>Editorial Decision</strong><span>Acceptance to online publication</span></div>
      <div><b>05</b><strong>Publication</strong><span>Production and release</span></div>
    </div>
  </div>
</section>

<section class="contact-cta">
  <div class="container">
    <div><span class="eyebrow">AJSMR EDITORIAL OFFICE</span><h2>Questions about submission or publication?</h2><p>Authors and reviewers can contact the Editorial Office for journal-related assistance.</p></div>
    <a class="btn white" href="contactus.php">Contact the Journal →</a>
  </div>
</section>

</main>

<footer>
  <div class="container footer-grid">
    <div class="footer-about">
      <div class="footer-logo">AJSMR</div>
      <h3>The American Journal of Science and Medical Research</h3>
      <p>Published by Advaitha Innovative Research Association (AIRA).</p>
      <p>ISSN 2377-6196 · Quarterly · Open Access</p>
    </div>
    <div><h4>Journal</h4><a href="currentissue.php">Current Issue</a><a href="archives.php">Archives</a><a href="editorialboard.php">Editorial Board</a></div>
    <div><h4>Authors &amp; Reviewers</h4><a href="authorguidelines.php">Author Guidelines</a><a href="editorial/login.php">Submit Manuscript</a><a href="peerreviewpolicy.php">Peer Review Policy</a></div>
    <div><h4>Policies</h4><a href="policies.php">Policy Hub</a><a href="publicationethics.php">Publication Ethics</a><a href="openaccesscopyrightpolicy.php">Open Access &amp; Copyright</a><a href="archivingdigitalpolicy.php">Digital Archiving</a></div>
  </div>
  <div class="footer-bottom"><div class="container">© 2026 The American Journal of Science and Medical Research · ISSN 2377-6196 · All rights reserved.</div></div>
</footer>

</body>
</html>