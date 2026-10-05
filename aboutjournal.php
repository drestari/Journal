<?php
declare(strict_types=1);

/*
 * AJSMR — About Journal
 * Designed to match the AJSMR Homepage V5.9 visual language.
 * Source content adapted from the supplied About Journal page:
 * text and relevant links retained; source header/footer/sidebar design omitted.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$journal = 'The American Journal of Science and Medical Research';
$abbr = 'AJSMR';
$issn = '2377-6196';
$publisher = 'Advaitha Innovative Research Association (AIRA)';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>About Journal | The American Journal of Science and Medical Research (AJSMR)</title>
<meta name="description" content="About The American Journal of Science and Medical Research (AJSMR), ISSN 2377-6196.">
<meta name="robots" content="index,follow">
<link rel="canonical" href="aboutjournal.php">
<link rel="icon" href="images/favicon.ico">
<link rel="stylesheet" href="ajsmr-homepage-v5-2.css">

<style>
/* About Journal page — supplements the existing AJSMR homepage stylesheet */
.about-page {
    padding: 42px 0 65px;
    background: #f7f9fc;
}
.about-page .container {
    max-width: 1180px;
}
.about-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 300px;
    gap: 28px;
    align-items: start;
}
@media (max-width: 900px) {
    .about-layout { grid-template-columns: 1fr; }
}
.about-breadcrumb {
    margin-bottom: 18px;
    color: #6b7280;
    font-size: 14px;
}
.about-breadcrumb a {
    color: #174a7c;
    text-decoration: none;
}
.about-card {
    background: #fff;
    border: 1px solid #e3e8ef;
    border-radius: 8px;
    box-shadow: 0 8px 28px rgba(24, 49, 78, .07);
    padding: 38px 44px 46px;
}
.about-card .eyebrow {
    display: inline-block;
    margin-bottom: 8px;
}
.about-card h1 {
    margin: 0 0 24px;
    color: #173b63;
    font-size: 34px;
    line-height: 1.2;
}
.about-card h2 {
    margin: 34px 0 15px;
    padding-bottom: 8px;
    border-bottom: 2px solid #e5edf5;
    color: #173b63;
    font-size: 24px;
}
.about-card h3 {
    margin: 30px 0 14px;
    color: #173b63;
    font-size: 21px;
}
.about-card p,
.about-card li,
.about-card td,
.about-card th {
    color: #394b5f;
    font-size: 16px;
    line-height: 1.75;
}
.about-card p {
    margin: 0 0 16px;
    text-align: justify;
}
.about-card ul,
.about-card ol {
    margin: 10px 0 20px 25px;
    padding-left: 20px;
}
.about-card li {
    margin-bottom: 6px;
}
.about-card a {
    color: #0c5a91;
    text-decoration: underline;
    text-underline-offset: 2px;
}
.about-card a:hover {
    color: #173b63;
}
.journal-facts {
    width: 100%;
    border-collapse: collapse;
    margin: 12px 0 28px;
}
.journal-facts th,
.journal-facts td {
    border: 1px solid #dce4ec;
    padding: 12px 14px;
    vertical-align: top;
    text-align: left;
}
.journal-facts th {
    width: 28%;
    background: #f1f5f9;
    color: #173b63;
    font-weight: 700;
}
.journal-facts td {
    background: #fff;
}
.about-note {
    margin-top: 30px;
    padding: 15px 18px;
    background: #f4f8fc;
    border-left: 4px solid #1c628f;
    color: #46576a;
}
.about-updated {
    margin-top: 34px !important;
    padding-top: 16px;
    border-top: 1px solid #e1e7ee;
    color: #6b7280 !important;
    font-size: 14px !important;
}
@media (max-width: 700px) {
    .about-card {
        padding: 26px 20px 32px;
    }
    .about-card h1 {
        font-size: 28px;
    }
    .about-card h2 {
        font-size: 22px;
    }
    .about-card p,
    .about-card li,
    .about-card td,
    .about-card th {
        font-size: 15px;
    }
    .journal-facts th,
    .journal-facts td {
        display: block;
        width: 100%;
        box-sizing: border-box;
    }
    .journal-facts tr {
        display: block;
        margin-bottom: 10px;
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
      <a href="mailto:editorajsmr@gmail.com">✉ &nbsp;editorajsmr@gmail.com</a>
      <i></i>
      <a href="editorial/eic-login.php">↪ &nbsp;Editorial Login</a>
    </div>
  </div>
</div>

<!-- ============================================================
     AJSMR JOURNAL IDENTITY HEADER
     ============================================================ -->
<header class="identity">
  <div class="container identity-inner">
    <a class="journal-brand" href="index_ajsmr_v5_8.php">
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
      <a href="index.php">Home</a>

      <div class="drop">
        <button class="active" type="button">Journal Info <span>⌄</span></button>
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

<section class="about-page">
  <div class="container">

    <div class="about-breadcrumb">
      <a href="index.php">Home</a>
      <span> &nbsp;›&nbsp; </span>
      <span>About Journal</span>
    </div>

    <div class="about-layout">
      <article class="about-card">
      <span class="eyebrow">ABOUT AJSMR</span>
      <h1>About the Journal</h1>

      <p><strong>The American Journal of Science and Medical Research (AJSMR)</strong> (ISSN: 2377-6196, online) is an international, peer-reviewed, open access journal published quarterly by <strong>Advaitha Innovative Research Association (AIRA)</strong>, Warangal, Telangana, India. The journal has published continuously since 2015 and publishes research in the pharmaceutical, medical and biological sciences, and related areas of the natural sciences.</p>

      <p>AJSMR is a <strong>diamond open access</strong> journal: all articles are free to read from the moment of publication, and authors are <strong>not charged any fee</strong> for submission, processing or publication.</p>

      <h2>Journal Particulars</h2>

      <table class="journal-facts">
        <tr><th>Journal title</th><td>The American Journal of Science and Medical Research</td></tr>
        <tr><th>Abbreviation</th><td>AJSMR (Am. J. Sci. Med. Res.)</td></tr>
        <tr><th>ISSN (Online)</th><td>2377-6196</td></tr>
        <tr><th>Publisher</th><td>Advaitha Innovative Research Association (AIRA)</td></tr>
        <tr><th>Publisher address</th><td># 5-11-717/204, MG Road, Beside MGM Hospital, Warangal, Telangana, India</td></tr>
        <tr><th>Country of publication</th><td>India</td></tr>
        <tr><th>Year of first publication</th><td>2015 (Volume 1)</td></tr>
        <tr><th>Frequency</th><td>Quarterly: January–March, April–June, July–September, October–December</td></tr>
        <tr><th>Publication format</th><td>Online (PDF full text)</td></tr>
        <tr><th>Language</th><td>English</td></tr>
        <tr><th>Subject areas</th><td>Pharmaceutical sciences, medical and health sciences, biological and life sciences (see <a href="aimsandscope.php">Aims &amp; Scope</a>)</td></tr>
        <tr><th>Peer review</th><td>Double-blind peer review by at least two independent reviewers (see <a href="peerreviewpolicy.php">Peer Review Policy</a>)</td></tr>
        <tr><th>Access model</th><td>Full, immediate open access; no embargo; no registration required</td></tr>
        <tr><th>Licence</th><td><a href="https://creativecommons.org/licenses/by/4.0/" target="_blank" rel="license noopener">Creative Commons Attribution 4.0 International (CC BY 4.0)</a></td></tr>
        <tr><th>Copyright</th><td>Authors retain copyright without restrictions</td></tr>
        <tr><th>Article processing charges</th><td>None (see <a href="apcpolicy.php">APC Policy</a>)</td></tr>
        <tr><th>Submission charges</th><td>None</td></tr>
        <tr><th>Persistent identifiers</th><td>DOIs registered through Zenodo</td></tr>
        <tr><th>Editor-in-Chief</th><td>Dr. M. Praveen Kumar, Ph.D. (<a href="editorialboard.php">Editorial Board</a>)</td></tr>
        <tr><th>Editorial contact</th><td><a href="mailto:editorajsmr@gmail.com">editorajsmr@gmail.com</a></td></tr>
      </table>

      <h2 id="open-access-statement">Open Access Statement</h2>

      <p>AJSMR is an open access journal, which means that all content is freely available without charge to the user or his/her institution. Users are allowed to read, download, copy, distribute, print, search, or link to the full texts of the articles, or use them for any other lawful purpose, without asking prior permission from the publisher or the author. This is in accordance with the <a href="https://www.budapestopenaccessinitiative.org/read/" target="_blank" rel="noopener">Budapest Open Access Initiative (BOAI)</a> definition of open access.</p>

      <p>Articles are published under the <a href="https://creativecommons.org/licenses/by/4.0/" target="_blank" rel="license noopener">Creative Commons Attribution 4.0 International (CC BY 4.0)</a> licence. Full details are in the <a href="openaccesscopyrightpolicy.php">Open Access, Copyright &amp; Licensing Policy</a>.</p>

      <h2>Types of Articles Published</h2>
      <ul>
        <li>Original Research Articles</li>
        <li>Review Articles and Mini-Reviews</li>
        <li>Short Communications and Research Notes</li>
        <li>Opinions and Perspectives</li>
        <li>Book Reviews in the sciences</li>
      </ul>

      <h2>Editorial Process in Brief</h2>
      <ol>
        <li>Authors submit through the <a href="submitmanuscript.php">online submission form</a> or by e-mail to <a href="mailto:editorajsmr@gmail.com">editorajsmr@gmail.com</a>.</li>
        <li>The editorial office checks scope, formatting and similarity (plagiarism) before review.</li>
        <li>Suitable manuscripts are sent for double-blind review to at least two independent experts.</li>
        <li>The handling editor recommends a decision; the Editor-in-Chief makes the final decision.</li>
        <li>Accepted articles are copy-edited, typeset, assigned a DOI and published open access in the next available issue.</li>
      </ol>

      <h2>Journal Policies</h2>
      <ul>
        <li><a href="aimsandscope.php">Aims &amp; Scope</a></li>
        <li><a href="authorguidelines.php">Author Guidelines</a></li>
        <li><a href="peerreviewpolicy.php">Peer Review Policy</a></li>
        <li><a href="publicationethicseditorialpolicy.php">Publication Ethics &amp; Editorial Policy</a></li>
        <li><a href="plagiarismresearchintegritypolicy.php">Plagiarism, Research Integrity &amp; Retraction Policy</a></li>
        <li><a href="openaccesscopyrightpolicy.php">Open Access, Copyright &amp; Licensing Policy</a></li>
        <li><a href="apcpolicy.php">Article Processing Charges Policy</a></li>
        <li><a href="complaintsappealspolicy.php">Complaints &amp; Appeals Policy</a></li>
        <li><a href="archivingdigitalpolicy.php">Archiving &amp; Digital Preservation Policy</a></li>
        <li><a href="privacydataprotectionpolicy.php">Privacy &amp; Data Protection Policy</a></li>
      </ul>

      <h2>Advertising and Marketing</h2>
      <p>AJSMR does not accept paid advertising on its website or in its articles. Calls for papers are sent only to researchers in relevant fields; the journal does not guarantee acceptance or publication timelines in exchange for payment, and it never charges authors a fee.</p>

      <h2>Ownership and Management</h2>
      <p>AJSMR is owned and published by Advaitha Innovative Research Association (AIRA), Warangal, Telangana, India. Editorial decisions are made independently by the Editor-in-Chief and the Editorial Board and are kept separate from the publisher's administrative functions.</p>

      <p class="about-updated">Last updated: September 2026</p>
      </article>

      <aside class="policy-sidebar">
  <h3>Journal at a Glance</h3>
  <ul>
    <li><strong>Journal:</strong> The American Journal of Science and Medical Research (AJSMR)</li>
    <li><strong>ISSN:</strong> 2377-6196</li>
    <li><strong>Publisher:</strong> Advaitha Innovative Research Association (AIRA)</li>
    <li><strong>Frequency:</strong> Quarterly</li>
    <li><strong>Access:</strong> Open Access</li>
    <li><strong>Peer Review:</strong> Double-blind</li>
    <li><strong>Licence:</strong> CC BY 4.0</li>
    <li><strong>First Published:</strong> 2015</li>
  </ul>

  <div class="policy-nav">
    <div class="policy-nav-title">Policies</div>
    <a href="publicationethics.php">Publication Ethics &amp; Editorial Policy</a>
    <a href="peerreviewpolicy.php">Peer Review Policy</a>
    <a href="openaccesscopyrightpolicy.php">Open Access, Copyright &amp; Licensing Policy</a>
    <a href="plagiarismresearchintegritypolicy.php">Plagiarism, Research Integrity &amp; Retraction Policy</a>
    <a href="complaintsappealspolicy.php">Complaints &amp; Appeals Policy</a>
    <a href="archivingdigitalpolicy.php">Archiving &amp; Digital Preservation Policy</a>
    <a href="privacydataprotectionpolicy.php">Privacy &amp; Data Protection Policy</a>
  </div>
</aside>
    </div>
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
