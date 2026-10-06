<?php
/*
 * AJSMR Shared Public Header Component
 * =====================================
 * Single source of truth for the AJSMR public-facing header.
 *
 * Usage:
 *   From root pages (e.g. publicationethics.php):
 *     $ajsmr_active_nav = 'policies'; // optional: home|journal|policies|authors|reviewers|articles|contact
 *     require_once __DIR__ . '/includes/ajsmr_header.php';
 *
 *   From editorial/ subdirectory (e.g. editorial/article.php):
 *     $ajsmr_active_nav = 'articles';
 *     $ajsmr_root = '../';             // path prefix to reach site root
 *     require_once __DIR__ . '/../includes/ajsmr_header.php';
 *
 * Variables consumed (all optional, with safe defaults):
 *   $ajsmr_active_nav  – which nav item is active ('home','journal','policies','authors','reviewers','articles','contact')
 *   $ajsmr_root        – URL prefix to reach the site root (default '' for root pages, '../' for editorial/ pages)
 *   $issn              – journal ISSN (default '2377-6196')
 *   $frequency         – publishing frequency (default 'Quarterly')
 *
 * The header CSS is loaded from ajsmr-homepage-v5-2.css (relative to site root).
 * This file must NOT be included inside an open <html> or <body> tag — it outputs
 * the utility bar, identity header and navigation only (no <html>, <head>, or <body>).
 */

/* ── safe defaults ── */
if (!isset($ajsmr_active_nav)) $ajsmr_active_nav = $GLOBALS['ajsmr_active_nav'] ?? '';
$GLOBALS['ajsmr_active_nav'] = $ajsmr_active_nav;

if (!isset($ajsmr_root))       $ajsmr_root       = $GLOBALS['ajsmr_root'] ?? '';  // '' for root pages; '../' for editorial/
$GLOBALS['ajsmr_root'] = $ajsmr_root;

if (!isset($issn))             $issn             = '2377-6196';
if (!isset($frequency))        $frequency        = 'Quarterly';

/* ── helper if not already declared ── */
if (!function_exists('ajsmr_h')) {
    function ajsmr_h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

/* ── active-state helper ── */
if (!function_exists('ajsmr_nav_active')) {
    function ajsmr_nav_active(string $key): string {
        $active = $GLOBALS['ajsmr_active_nav'] ?? '';
        return ($active === $key) ? ' class="active"' : '';
    }
}

$r = $ajsmr_root; // short alias for URL prefix
?>
<!-- ============================================================
     AJSMR TOP UTILITY BAR
     ============================================================ -->
<div class="utility">
  <div class="container utility-inner">
    <div class="utility-left">
      <span>▥ &nbsp;ISSN (Online): <?=ajsmr_h($issn)?></span>
      <i></i><span>♙ &nbsp;Open Access</span>
      <i></i><span>▣ &nbsp;<?=ajsmr_h($frequency)?></span>
    </div>
    <div class="utility-right">
      <a href="mailto:editor@ajsmrjournal.com">✉ &nbsp;editor@ajsmrjournal.com</a>
      <i></i>
      <a href="<?=ajsmr_h($r)?>editorial/eic-login.php">↪ &nbsp;Editorial Login</a>
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
    <a class="journal-brand" href="<?=ajsmr_h($r)?>index.php">
      <img class="brand-logo-image" src="<?=ajsmr_h($r)?>images/ajsmr-logo.png"
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

    <form class="search" action="<?=ajsmr_h($r)?>search.php" method="get">
      <input type="search" name="q" placeholder="Search articles/Authors..." aria-label="Search articles" value="<?=isset($_GET['q']) ? ajsmr_h($_GET['q']) : ''?>">
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
      <a<?=ajsmr_nav_active('home')?> href="<?=ajsmr_h($r)?>index.php">Home</a>

      <div class="drop">
        <button<?=ajsmr_nav_active('journal')?> type="button">Journal Info <span>⌄</span></button>
        <div class="drop-menu">
          <a href="<?=ajsmr_h($r)?>aboutjournal.php">About Journal</a>
          <a href="<?=ajsmr_h($r)?>editorialboard.php">Editorial Board</a>
          <a href="<?=ajsmr_h($r)?>currentissue.php">Current Issue</a>
          <a href="<?=ajsmr_h($r)?>archives.php">Archives</a>
          <a href="<?=ajsmr_h($r)?>contactus.php">Contact Us</a>
        </div>
      </div>

      <div class="drop">
        <button<?=ajsmr_nav_active('policies')?> type="button">Policies <span>⌄</span></button>
        <div class="drop-menu">
          <a href="<?=ajsmr_h($r)?>publicationethics.php">Publication Ethics</a>
          <a href="<?=ajsmr_h($r)?>peerreviewpolicy.php">Peer Review Policy</a>
          <a href="<?=ajsmr_h($r)?>openaccesscopyrightpolicy.php">Open Access &amp; Copyright</a>
          <a href="<?=ajsmr_h($r)?>plagiarismresearchintegritypolicy.php">Plagiarism &amp; Research Integrity</a>
          <a href="<?=ajsmr_h($r)?>complaintsappealspolicy.php">Complaints &amp; Appeals</a>
          <a href="<?=ajsmr_h($r)?>archivingdigitalpolicy.php">Digital Archiving</a>
          <a href="<?=ajsmr_h($r)?>privacydataprotectionpolicy.php">Privacy &amp; Data Protection</a>
        </div>
      </div>

      <div class="drop">
        <button<?=ajsmr_nav_active('authors')?> type="button">For Authors <span>⌄</span></button>
        <div class="drop-menu">
          <a href="<?=ajsmr_h($r)?>authorguidelines.php">Author Guidelines</a>
          <a href="<?=ajsmr_h($r)?>guidelinestoauthors.php">Guidelines to Authors</a>
          <a href="<?=ajsmr_h($r)?>editorial/login.php">Submit Manuscript</a>
          <a href="<?=ajsmr_h($r)?>ajsmr@admin99/templatesandcopyrightforms.php">Templates &amp; Copyright Forms</a>
        </div>
      </div>

      <div class="drop">
        <button<?=ajsmr_nav_active('reviewers')?> type="button">For Reviewers <span>⌄</span></button>
        <div class="drop-menu">
          <a href="<?=ajsmr_h($r)?>peerreviewpolicy.php">Peer Review Policy</a>
          <a href="<?=ajsmr_h($r)?>publicationethics.php">Reviewer Ethics</a>
          <a href="<?=ajsmr_h($r)?>editorial/login.php">Reviewer / Editorial Login</a>
        </div>
      </div>

      <div class="drop">
        <button<?=ajsmr_nav_active('articles')?> type="button">Articles <span>⌄</span></button>
        <div class="drop-menu">
          <a href="<?=ajsmr_h($r)?>currentissue.php">Current Issue</a>
          <a href="<?=ajsmr_h($r)?>archives.php">Archives</a>
        </div>
      </div>

      <a<?=ajsmr_nav_active('contact')?> href="<?=ajsmr_h($r)?>contactus.php">Contact</a>
    </div>

    <a class="submit-nav" href="<?=ajsmr_h($r)?>editorial/login.php">Submit Manuscript <span>➤</span></a>
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
