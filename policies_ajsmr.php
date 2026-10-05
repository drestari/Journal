<?php
declare(strict_types=1);

/*
 * AJSMR — Policies
 * Standalone policy hub page matching the current AJSMR homepage visual style.
 * The policy links/content are based on the supplied policies.php.
 * The homepage header/footer and unrelated source design are intentionally omitted.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$journal = 'The American Journal of Science and Medical Research';
$issn = '2377-6196';
$publisher = 'Advaitha Innovative Research Association (AIRA)';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Policies | <?=h($journal)?> (AJSMR)</title>
<meta name="description" content="AJSMR policy hub covering publication ethics, peer review, research integrity, open access, copyright, preservation, privacy and publication charges.">
<meta name="robots" content="index,follow">
<link rel="canonical" href="policies.php">
<link rel="icon" href="images/favicon.ico">
<link rel="stylesheet" href="ajsmr-homepage-v5-2.css">

<style>
.policy-page {
    padding: 42px 0 65px;
    background: #f7f9fc;
}
.policy-page .container {
    max-width: 1180px;
}
.policy-breadcrumb {
    margin-bottom: 18px;
    color: #6b7280;
    font-size: 14px;
}
.policy-breadcrumb a {
    color: #174a7c;
    text-decoration: none;
}
.policy-card {
    background: #fff;
    border: 1px solid #e3e8ef;
    border-radius: 8px;
    box-shadow: 0 8px 28px rgba(24,49,78,.07);
    padding: 38px 44px 46px;
}
.policy-card .eyebrow {
    display: inline-block;
    margin-bottom: 8px;
}
.policy-card h1 {
    margin: 0 0 14px;
    color: #173b63;
    font-size: 34px;
    line-height: 1.2;
}
.policy-card .intro {
    margin: 0 0 30px;
    padding: 16px 19px;
    background: #f4f8fc;
    border-left: 4px solid #1c628f;
    color: #46576a;
    font-size: 16px;
    line-height: 1.7;
}
.policy-section {
    margin-top: 30px;
}
.policy-section h2 {
    margin: 0 0 13px;
    padding: 12px 16px;
    border-left: 4px solid #1c628f;
    background: #f1f5f9;
    color: #173b63;
    font-size: 21px;
    line-height: 1.35;
}
.policy-list {
    margin: 0;
    padding: 0;
    list-style: none;
}
.policy-list li {
    margin: 0;
    border-bottom: 1px solid #e5eaf0;
}
.policy-list li:last-child {
    border-bottom: 0;
}
.policy-list a {
    display: block;
    position: relative;
    padding: 13px 35px 13px 4px;
    color: #0c5a91;
    font-size: 16px;
    line-height: 1.55;
    text-decoration: none;
    transition: background .15s ease, padding-left .15s ease;
}
.policy-list a::after {
    content: "→";
    position: absolute;
    right: 7px;
    top: 50%;
    transform: translateY(-50%);
    color: #1c628f;
    font-weight: 700;
}
.policy-list a:hover {
    padding-left: 10px;
    background: #f7fafc;
    color: #173b63;
}
.policy-footer-note {
    margin-top: 34px;
    padding-top: 18px;
    border-top: 1px solid #e1e7ee;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.6;
}
@media (max-width:700px) {
    .policy-card {
        padding: 26px 20px 32px;
    }
    .policy-card h1 {
        font-size: 28px;
    }
    .policy-section h2 {
        font-size: 19px;
    }
    .policy-list a {
        font-size: 15px;
    }
}
</style>
</head>

<body>
<a class="skip" href="#main">Skip to main content</a>

<!-- Utility bar -->
<div class="utility">
  <div class="container utility-inner">
    <div class="utility-left">
      <span>▥ &nbsp;ISSN (Online): <?=h($issn)?></span>
      <i></i><span>♙ &nbsp;Open Access</span>
      <i></i><span>▣ &nbsp;Quarterly</span>
    </div>
    <div class="utility-right">
      <a href="mailto:editorajsrm@gmail.com">✉ &nbsp;editorajsrm@gmail.com</a>
      <i></i>
      <a href="editorial/eic-login.php">↪ &nbsp;Editorial Login</a>
    </div>
  </div>
</div>

<!-- Journal identity header -->
<header class="identity">
  <div class="container identity-inner">
    <a class="journal-brand" href="index_ajsmr_v5_8.php">
      <img class="brand-logo-image" src="images/ajsmr-logo.png" alt="AJSMR logo">
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

<!-- Main navigation -->
<nav class="main-nav" aria-label="Main navigation">
  <div class="container nav-inner">
    <button class="mobile-menu" id="mobileMenu" type="button" aria-expanded="false">☰ Menu</button>

    <div class="nav-links" id="navLinks">
      <a href="index_ajsmr_v5_8.php">Home</a>

      <div class="drop">
        <button type="button">Journal Info <span>⌄</span></button>
        <div class="drop-menu">
          <a href="aboutjournal.php">About Journal</a>
          <a class="active" href="policies.php">Policy Hub</a>
          <a href="editorialboard.php">Editorial Board</a>
          <a href="currentissue.php">Current Issue</a>
          <a href="archives.php">Archives</a>
          <a href="contactus.php">Contact Us</a>
        </div>
      </div>

      <div class="drop open">
        <button type="button">Policies <span>⌄</span></button>
        <div class="drop-menu">
          <a href="policies.php">Policy Hub</a>
          <a href="publicationethics.php">Publication Ethics</a>
          <a href="peerreviewpolicy.php">Peer Review Policy</a>
          <a href="plagiarismresearchintegritypolicy.php">Plagiarism &amp; Research Integrity</a>
          <a href="openaccesscopyrightpolicy.php">Open Access &amp; Copyright</a>
          <a href="privacydataprotectionpolicy.php">Privacy &amp; Data Protection</a>
          <a href="archivingdigitalpolicy.php">Digital Archiving</a>
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
          <a href="editorial/login.php">Reviewer / Editorial Login</a>
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

<main id="main">

<section class="policy-page">
  <div class="container">

    <div class="policy-breadcrumb">
      <a href="index_ajsmr_v5_8.php">Home</a>
      <span> &nbsp;›&nbsp; </span>
      <span>Policies</span>
    </div>

    <article class="policy-card">
      <span class="eyebrow">AJSMR POLICY HUB</span>
      <h1>AJSMR Policies</h1>

      <div class="intro">
        Central policy page for the AJSMR journal. The policy links below provide access to the journal's publication ethics, peer review, research integrity, open access, copyright, preservation, privacy and publication-charge policies.
      </div>

      <section class="policy-section">
        <h2>Publication Ethics &amp; Editorial Review</h2>
        <ul class="policy-list">
          <li><a href="publicationethics.php">Publication Ethics &amp; Editorial Policy</a></li>
          <li><a href="peerreviewpolicy.php">Peer Review Policy</a></li>
          <li><a href="plagiarismresearchintegritypolicy.php">Plagiarism, Research Integrity &amp; Retraction Policy</a></li>
          <li><a href="editorialindependence.php">Editorial Independence</a></li>
          <li><a href="complaintsappealspolicy.php">Complaints &amp; Appeals</a></li>
        </ul>
      </section>

      <section class="policy-section">
        <h2>Research Integrity &amp; Declarations</h2>
        <ul class="policy-list">
          <li><a href="researchethicsconsent.php">Research Ethics, Consent &amp; Clinical/Animal Research</a></li>
          <li><a href="authordeclarations.php">Conflict of Interest, Funding &amp; Author Contributions</a></li>
          <li><a href="dataavailabilitypolicy.php">Data Availability &amp; Research Data</a></li>
          <li><a href="aigenerativeaipolicy.php">AI &amp; Generative AI Policy</a></li>
        </ul>
      </section>

      <section class="policy-section">
        <h2>Access, Copyright &amp; Preservation</h2>
        <ul class="policy-list">
          <li><a href="openaccesscopyrightpolicy.php">Open Access, Copyright &amp; Self-Archiving Policy</a></li>
          <li><a href="licensecopyright.php">Licensing &amp; Copyright</a></li>
          <li><a href="archivingdigitalpolicy.php">Archiving &amp; Digital Preservation Policy</a></li>
          <li><a href="privacydataprotectionpolicy.php">Privacy &amp; Data Protection Policy</a></li>
        </ul>
      </section>

      <section class="policy-section">
        <h2>Charges</h2>
        <ul class="policy-list">
          <li><a href="publicationcharges.php">Publication Charges &amp; Waiver Policy</a></li>
        </ul>
      </section>

      <div class="policy-footer-note">
        The individual policy pages can be accessed through the links above. AJSMR's policy hub is intended to provide a central navigation point for authors, reviewers, editors and readers.
      </div>
    </article>

  </div>
</section>

</main>

<!-- Footer matching the current AJSMR homepage -->
<footer>
  <div class="container footer-grid">
    <div class="footer-about">
      <div class="footer-logo">AJSMR</div>
      <h3><?=h($journal)?></h3>
      <p>Published by <?=h($publisher)?>.</p>
      <p>ISSN <?=h($issn)?> · Quarterly · Open Access</p>
    </div>
    <div>
      <h4>Journal</h4>
      <a href="aboutjournal.php">About Journal</a>
      <a href="currentissue.php">Current Issue</a>
      <a href="archives.php">Archives</a>
      <a href="editorialboard.php">Editorial Board</a>
    </div>
    <div>
      <h4>Authors &amp; Reviewers</h4>
      <a href="authorguidelines.php">Author Guidelines</a>
      <a href="editorial/login.php">Submit Manuscript</a>
      <a href="peerreviewpolicy.php">Peer Review Policy</a>
    </div>
    <div>
      <h4>Policies</h4>
      <a href="policies.php">Policy Hub</a>
      <a href="publicationethics.php">Publication Ethics</a>
      <a href="openaccesscopyrightpolicy.php">Open Access &amp; Copyright</a>
      <a href="archivingdigitalpolicy.php">Digital Archiving</a>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container">© <?=date('Y')?> <?=h($journal)?> · ISSN <?=h($issn)?> · All rights reserved.</div>
  </div>
</footer>

<script>
(function(){
  const btn=document.getElementById('mobileMenu');
  const nav=document.getElementById('navLinks');

  if (btn && nav) {
    btn.addEventListener('click',function(){
      const open=nav.classList.toggle('open');
      btn.setAttribute('aria-expanded',open?'true':'false');
    });
  }

  document.querySelectorAll('.drop>button').forEach(function(b){
    b.addEventListener('click',function(e){
      e.stopPropagation();
      const p=b.parentElement;
      document.querySelectorAll('.drop.open').forEach(function(x){
        if(x!==p) x.classList.remove('open');
      });
      p.classList.toggle('open');
    });
  });

  document.addEventListener('click',function(e){
    if(!e.target.closest('.drop')) {
      document.querySelectorAll('.drop.open').forEach(function(x){
        x.classList.remove('open');
      });
    }
  });
})();
</script>
</body>
</html>
