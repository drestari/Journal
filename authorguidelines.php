<?php
declare(strict_types=1);

/*
 * The American Journal of Science and Medical Research (AJSMR)
 * ISSN: 2377-6196
 *
 * Author Guidelines Page
 * Single source of truth for manuscript preparation, formatting, submission,
 * peer review workflow, and publication fee policy.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');

/* Helper for safe HTML output */
if (!function_exists('h')) {
    function h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

/* Core journal identity facts */
$journal   = 'The American Journal of Science and Medical Research';
$abbr      = 'AJSMR';
$issn      = '2377-6196';
$publisher = 'Advaitha Innovative Research Association (AIRA)';
$frequency = 'Quarterly';

/* Schema.org structured metadata */
$schema = [
    '@context'            => 'https://schema.org',
    '@type'               => 'WebPage',
    'name'                => 'Author Guidelines :: ' . $journal . ' (' . $abbr . ')',
    'description'         => 'Complete author submission guidelines, scope, manuscript preparation standards, reporting checklists, and publication fee policy for The American Journal of Science and Medical Research (AJSMR).',
    'url'                 => 'https://ajsmrjournal.com/authorguidelines.php',
    'isPartOf'            => [
        '@type'              => 'Periodical',
        'name'               => $journal,
        'alternateName'      => $abbr,
        'issn'               => $issn,
        'url'                => 'https://ajsmrjournal.com/',
        'publisher'          => [
            '@type' => 'Organization',
            'name'  => $publisher
        ],
        'isAccessibleForFree' => true
    ]
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Author Guidelines :: <?=h($journal)?> (<?=h($abbr)?>)</title>
<meta name="description" content="Official Author Guidelines for The American Journal of Science and Medical Research (AJSMR), ISSN: 2377-6196. Scope, article types, manuscript formatting, submission checklist, and publication fee policy.">
<meta name="robots" content="index,follow">
<link rel="canonical" href="https://ajsmrjournal.com/authorguidelines.php">
<link rel="icon" href="images/favicon.ico">
<meta property="og:type" content="article">
<meta property="og:title" content="Author Guidelines | <?=h($abbr)?>">
<meta property="og:description" content="Author guidelines, scope, formatting standards, and Article Publishing Charges for <?=h($journal)?> (ISSN: <?=h($issn)?>).">
<meta property="og:url" content="https://ajsmrjournal.com/authorguidelines.php">
<script type="application/ld+json"><?=json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?></script>
<link rel="stylesheet" href="ajsmr-homepage-v5-2.css">
<style>
/* Page Layout & Structural Elements */
.ag-hero{background:#f4f7fb;border-bottom:1px solid #dfe7f0;padding:40px 0 28px}
.ag-hero .eyebrow{letter-spacing:.12em;font-size:12px;font-weight:800;color:#55708d;text-transform:uppercase;display:block;margin-bottom:6px}
.ag-hero h1{margin:0 0 10px;color:#102f4d;font-size:35px;line-height:1.2;font-weight:800}
.ag-hero p{max-width:860px;margin:0 0 16px;color:#53677d;font-size:15px;line-height:1.6}
.ag-hero-badges{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
.ag-hero-badge{display:inline-flex;align-items:center;padding:5px 12px;background:#e7f0f8;border:1px solid #cddde9;border-radius:20px;font-size:12px;font-weight:700;color:#103c68}

.ag-toc-strip{background:#fff;border-bottom:1px solid #e1e8f0;padding:12px 0}
.ag-toc-inner{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.ag-toc-label{font-size:12px;font-weight:800;color:#617487;text-transform:uppercase;margin-right:4px}
.ag-toc-link{font-size:12px;font-weight:700;color:#0b5e96;text-decoration:none;padding:4px 10px;background:#f3f7fb;border-radius:14px;transition:all .15s}
.ag-toc-link:hover{background:#0b5e96;color:#fff}

.ag-shell{background:#f7f9fc;padding:42px 0 60px}
.ag-layout{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:30px;align-items:start}
.ag-content,.policy-sidebar{background:#fff;border:1px solid #e0e7ef;border-radius:12px;box-shadow:0 8px 26px rgba(16,47,77,.06)}
.ag-content{padding:38px 44px}

/* Typography & Headings */
.ag-content h2{margin:36px 0 16px;color:#102f4d;font-size:24px;border-bottom:2px solid #eef3f8;padding-bottom:9px;display:flex;align-items:center;gap:10px}
.ag-content h2:first-of-type{margin-top:0}
.ag-content h2 .section-num{display:inline-block;color:#075fa8;font-size:18px;font-weight:800}
.ag-content h3{margin:24px 0 10px;color:#14446c;font-size:18px;font-weight:700}
.ag-content h4{margin:18px 0 8px;color:#193f60;font-size:15px;font-weight:700}
.ag-content p,.ag-content li{font-size:14.5px;line-height:1.75;color:#394c60}
.ag-content p{margin:0 0 14px}
.ag-content ul,.ag-content ol{margin:0 0 18px;padding-justify:24px}
.ag-content li{margin-bottom:6px}
.ag-content strong{color:#132d47}
.ag-content a{color:#075fa8;text-decoration:none}
.ag-content a:hover{text-decoration:underline}

/* Callout Boxes & Notices */
.ag-notice{background:#f2f7fc;border-left:4px solid #075fa8;padding:16px 20px;border-radius:0 8px 8px 0;margin:18px 0 22px}
.ag-notice p{margin:0;font-size:14px;line-height:1.65;color:#28435d}
.ag-notice strong{color:#0b3c67}

.ag-alert-box{background:#fff8ee;border:1px solid #f2d2a4;border-left:4px solid #f28c16;padding:16px 20px;border-radius:0 8px 8px 0;margin:18px 0 22px}
.ag-alert-box p{margin:0;font-size:14px;line-height:1.65;color:#5a4325}

/* Scope Pills Grid */
.ag-scope-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:10px;margin:18px 0 26px}
.ag-scope-item{background:#f6f9fc;border:1px solid #e1e9f1;border-radius:8px;padding:10px 14px;font-size:13.5px;color:#1e3c5a;display:flex;align-items:center;gap:9px;font-weight:600}
.ag-scope-item::before{content:"✦";color:#075fa8;font-size:11px}

/* Article Type Cards */
.ag-type-card{background:#fbfdff;border:1px solid #e1ebf4;border-radius:8px;padding:18px 20px;margin-bottom:16px}
.ag-type-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;flex-wrap:wrap;gap:8px}
.ag-type-header h4{margin:0;color:#103c68;font-size:16px}
.ag-type-tag{background:#e8f2fa;color:#0b5e96;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:800;letter-spacing:.04em;text-transform:uppercase}

/* Recommended Structure List */
.ag-structure-flow{display:flex;flex-wrap:wrap;gap:7px;margin:12px 0 16px;padding:0;list-style:none}
.ag-structure-flow li{background:#edf4fa;border:1px solid #d4e3ef;border-radius:6px;padding:5px 11px;font-size:12.5px;font-weight:700;color:#163d63;margin-bottom:0}

/* Publication Fee Card */
.ag-fee-card{background:#ffffff;border:2px solid #075fa8;border-radius:12px;box-shadow:0 8px 24px rgba(7,95,168,.09);margin:26px 0 32px;overflow:hidden}
.ag-fee-header{background:linear-gradient(135deg,#06346d 0%,#075fa8 100%);color:#fff;padding:20px 24px}
.ag-fee-header h3{margin:0 0 6px;color:#ffffff;font-size:20px;font-weight:800;letter-spacing:.04em;text-transform:uppercase}
.ag-fee-header p{margin:0;color:#e1effb;font-size:13.5px;line-height:1.55}
.ag-fee-body{padding:24px}
.ag-fee-table{width:100%;border-collapse:collapse;margin:12px 0 20px}
.ag-fee-table th{background:#f2f7fc;text-align:left;padding:12px 16px;font-size:13px;font-weight:800;color:#103459;border-bottom:2px solid #d3e2ef}
.ag-fee-table td{padding:13px 16px;border-bottom:1px solid #e8eff6;font-size:14px;color:#243e57}
.ag-fee-table tr:last-child td{border-bottom:0}
.ag-fee-amount{font-size:18px;font-weight:800;color:#075fa8}
.ag-fee-features{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;margin:18px 0 14px}
.ag-fee-pill{background:#f8fafc;border:1px solid #e1e9f0;border-radius:8px;padding:12px 14px;text-align:center}
.ag-fee-pill-title{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#0b4b7a;margin-bottom:3px}
.ag-fee-pill-desc{font-size:12.5px;color:#495f75}
.ag-fee-footer{background:#f8fafc;border-top:1px solid #e5edf4;padding:16px 24px;font-size:13px;color:#475d73;line-height:1.6}

/* Submission Checklist */
.ag-checklist{display:grid;grid-template-columns:repeat(auto-fit,minmax(310px,1fr));gap:10px;margin:20px 0 26px}
.ag-check-item{background:#f9fbfe;border:1px solid #e3ecf5;border-radius:8px;padding:11px 15px;display:flex;align-items:flex-start;gap:12px;font-size:13.5px;color:#233d57;line-height:1.5}
.ag-check-icon{color:#15803d;font-weight:900;font-size:16px;line-height:1.2;flex-shrink:0}

/* Reporting Guidelines Table */
.ag-std-table{width:100%;border-collapse:collapse;margin:16px 0 22px;border:1px solid #e2ebf4;border-radius:8px;overflow:hidden}
.ag-std-table th{background:#eef4fa;padding:11px 15px;font-size:13px;font-weight:800;color:#103459;text-align:left;border-bottom:2px solid #d4e3f1}
.ag-std-table td{padding:11px 15px;border-bottom:1px solid #edf2f8;font-size:13.5px;color:#354a5f}
.ag-std-table tr:nth-child(even) td{background:#fafcff}
.ag-std-table tr:last-child td{border-bottom:0}

/* Citation / Reference Examples */
.ag-ref-example{background:#f7fafd;border:1px solid #e1eaf3;border-left:4px solid #075fa8;border-radius:0 8px 8px 0;padding:14px 18px;margin-bottom:14px}
.ag-ref-type{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#0b5e96;margin-bottom:5px}
.ag-ref-text{font-size:13px;line-height:1.65;color:#2d4257;font-family:Consolas,Menlo,"Courier New",monospace}

/* Submission CTA Box */
.ag-submit-box{background:linear-gradient(135deg,#062b57 0%,#075fa8 100%);color:#fff;border-radius:12px;padding:28px 32px;margin:30px 0;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap}
.ag-submit-box-copy{flex:1;min-width:280px}
.ag-submit-box-copy h3{color:#fff;margin:0 0 6px;font-size:20px}
.ag-submit-box-copy p{color:#d8e8f8;margin:0;font-size:14px;line-height:1.55}
.ag-submit-btn{background:#f28c16;color:#fff!important;text-decoration:none!important;padding:12px 26px;border-radius:6px;font-weight:800;font-size:14px;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 14px rgba(0,0,0,.2);transition:all .15s;white-space:nowrap}
.ag-submit-btn:hover{background:#d97706;transform:translateY(-1px)}

/* Related Policies Grid */
.ag-policies-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;margin:20px 0 26px}
.ag-policy-card{background:#ffffff;border:1px solid #e1ebf5;border-radius:8px;padding:14px 16px;text-decoration:none!important;color:inherit;transition:all .18s;display:flex;flex-direction:column;justify-content:space-between}
.ag-policy-card:hover{border-color:#075fa8;box-shadow:0 6px 18px rgba(7,95,168,.1);transform:translateY(-2px)}
.ag-policy-card strong{color:#075fa8;font-size:14px;display:block;margin-bottom:4px}
.ag-policy-card span{font-size:12.5px;color:#55697d;line-height:1.45}

/* Contact Card */
.ag-contact-card{background:#f8fafc;border:1px solid #e2eaf1;border-radius:10px;padding:22px 24px;margin-top:20px}
.ag-contact-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;margin-top:14px}
.ag-contact-col h4{margin:0 0 8px;color:#103c68;font-size:14px;text-transform:uppercase;letter-spacing:.04em}
.ag-contact-col p{margin:0 0 6px;font-size:13.5px;line-height:1.55;color:#354a5f}

/* Sidebar Styling Consistency */
.policy-sidebar{padding:24px;position:sticky;top:70px}
.policy-sidebar h3{margin:0 0 15px;color:#123d63;font-size:19px;border-bottom:2px solid #eef3f8;padding-bottom:10px}
.policy-sidebar ul{list-style:none;margin:0;padding:0}
.policy-sidebar li{padding:8px 0;border-bottom:1px solid #edf1f5;color:#536579;font-size:13px;line-height:1.45}
.policy-sidebar li:last-child{border-bottom:0}
.policy-sidebar strong{color:#263f58}
.policy-nav{margin-top:22px;padding-top:18px;border-top:1px solid #e5ebf1}
.policy-nav-title{font-size:13px;font-weight:800;color:#123d63;margin-bottom:8px}
.policy-nav a{display:block;padding:7px 8px;border-radius:6px;color:#275d82;text-decoration:none;font-size:13px}
.policy-nav a:hover{background:#eef5fb}

/* Responsive adjustments */
@media(max-width:960px){
  .ag-layout{grid-template-columns:1fr}
  .policy-sidebar{position:static;margin-top:24px}
  .ag-content{padding:28px 24px}
  .ag-hero h1{font-size:30px}
}
@media(max-width:640px){
  .ag-content{padding:22px 18px}
  .ag-hero h1{font-size:26px}
  .ag-hero{padding:30px 0 20px}
  .ag-checklist{grid-template-columns:1fr}
  .ag-fee-table th,.ag-fee-table td{padding:10px 12px;font-size:13px}
  .ag-fee-amount{font-size:16px}
}
</style>
</head>
<body>
<a class="skip" href="#main">Skip to main content</a>

<?php
$ajsmr_active_nav = 'authors';
require_once __DIR__ . '/includes/ajsmr_header.php';
?>

<main id="main">

  <!-- ============================================================
       AUTHOR GUIDELINES HERO SECTION
       ============================================================ -->
  <section class="ag-hero">
    <div class="container">
      <span class="eyebrow">Information For Authors</span>
      <h1>Author Guidelines</h1>
      <p>Official submission guidelines, manuscript preparation instructions, reporting standards, and publication fee policy for <strong>The American Journal of Science and Medical Research (AJSMR)</strong>.</p>
      <div class="ag-hero-badges">
        <span class="ag-hero-badge">▥ ISSN: <?=h($issn)?></span>
        <span class="ag-hero-badge">♙ Open Access (CC BY)</span>
        <span class="ag-hero-badge">⚖ Double-Blind Peer Review</span>
        <span class="ag-hero-badge">▣ <?=h($frequency)?> Publication</span>
      </div>
    </div>
  </section>

  <!-- Quick Navigation / TOC Strip -->
  <nav class="ag-toc-strip" aria-label="Table of contents">
    <div class="container ag-toc-inner">
      <span class="ag-toc-label">Jump to:</span>
      <a class="ag-toc-link" href="#introduction">1. Introduction</a>
      <a class="ag-toc-link" href="#scope">2. Scope</a>
      <a class="ag-toc-link" href="#article-types">3. Article Types</a>
      <a class="ag-toc-link" href="#authorship">4. Authorship</a>
      <a class="ag-toc-link" href="#preparation">5. Preparation</a>
      <a class="ag-toc-link" href="#methods">6. Methods &amp; Data</a>
      <a class="ag-toc-link" href="#ethical-standards">7. Ethics &amp; Consent</a>
      <a class="ag-toc-link" href="#fee-policy">8. Publication Fee (APC)</a>
      <a class="ag-toc-link" href="#submission-checklist">9. Checklist</a>
      <a class="ag-toc-link" href="#peer-review">10. Peer Review</a>
      <a class="ag-toc-link" href="#related-policies">11. Related Policies</a>
    </div>
  </nav>

  <!-- ============================================================
       MAIN CONTENT AND SIDEBAR SHELL
       ============================================================ -->
  <section class="ag-shell">
    <div class="container ag-layout">

      <article class="ag-content">

        <!-- ============================================================
             3. INTRODUCTION
             ============================================================ -->
        <section id="introduction">
          <h2><span class="section-num">1.</span> Introduction</h2>
          <p><strong>The American Journal of Science and Medical Research (AJSMR)</strong> (ISSN: 2377-6196) is an international, peer-reviewed, open-access quarterly journal published by the <strong>Advaitha Innovative Research Association (AIRA)</strong>. The journal welcomes original research contributions that advance knowledge across science, medical sciences, biomedical sciences, life sciences, pharmaceutical sciences, public health, and related interdisciplinary areas.</p>
          <div class="ag-notice">
            <p><strong>Important Notice to Contributing Authors:</strong> Authors should carefully read these guidelines before submitting a manuscript. Submission of a manuscript to AJSMR indicates that all listed authors have read and approved the complete manuscript, agree with its submission to the journal, and accept full accountability for the integrity and validity of the reported findings.</p>
          </div>
        </section>

        <!-- ============================================================
             4. SCOPE OF THE JOURNAL
             ============================================================ -->
        <section id="scope">
          <h2><span class="section-num">2.</span> Scope of the Journal</h2>
          <p>AJSMR publishes rigorous original contributions across broad multidisciplinary domains spanning fundamental, clinical, and translational sciences. The scope encompasses, but is not limited to, the following disciplines:</p>

          <div class="ag-scope-grid">
            <div class="ag-scope-item">Biomedical Sciences</div>
            <div class="ag-scope-item">Medical Sciences</div>
            <div class="ag-scope-item">Life Sciences</div>
            <div class="ag-scope-item">Molecular Biology</div>
            <div class="ag-scope-item">Biotechnology</div>
            <div class="ag-scope-item">Microbiology</div>
            <div class="ag-scope-item">Biochemistry</div>
            <div class="ag-scope-item">Pharmacology</div>
            <div class="ag-scope-item">Pharmaceutical Sciences</div>
            <div class="ag-scope-item">Medicinal Chemistry</div>
            <div class="ag-scope-item">Drug Discovery and Development</div>
            <div class="ag-scope-item">Computational Biology and Bioinformatics</div>
            <div class="ag-scope-item">Molecular Modeling &amp; Computational Drug Design</div>
            <div class="ag-scope-item">Genetics and Genomics</div>
            <div class="ag-scope-item">Immunology</div>
            <div class="ag-scope-item">Cancer Biology and Oncology</div>
            <div class="ag-scope-item">Infectious Diseases</div>
            <div class="ag-scope-item">Public Health</div>
            <div class="ag-scope-item">Epidemiology</div>
            <div class="ag-scope-item">Environmental Health</div>
            <div class="ag-scope-item">Nutrition and Metabolic Sciences</div>
            <div class="ag-scope-item">Neuroscience</div>
            <div class="ag-scope-item">Clinical and Translational Research</div>
            <div class="ag-scope-item">Veterinary and Animal Sciences</div>
            <div class="ag-scope-item">Other interdisciplinary areas relevant to science and medical research</div>
          </div>
        </section>

        <!-- ============================================================
             5. TYPES OF MANUSCRIPTS
             ============================================================ -->
        <section id="article-types">
          <h2><span class="section-num">3.</span> Types of Manuscripts</h2>
          <p>AJSMR considers the following categories of submissions for publication:</p>

          <!-- 5.1 Original Research Article -->
          <div class="ag-type-card">
            <div class="ag-type-header">
              <h4>5.1 Original Research Article</h4>
              <span class="ag-type-tag">Primary Research</span>
            </div>
            <p>Full-length reports of innovative, empirical investigations that present significant novel scientific findings with comprehensive methodological detail, sound statistical evaluation, and substantiated conclusions.</p>
            <p><strong>Recommended Structure for Original Research Articles:</strong></p>
            <ul class="ag-structure-flow">
              <li>Title</li>
              <li>Abstract</li>
              <li>Keywords</li>
              <li>Introduction</li>
              <li>Materials and Methods</li>
              <li>Results</li>
              <li>Discussion</li>
              <li>Conclusion</li>
              <li>Acknowledgments</li>
              <li>Funding</li>
              <li>Conflict of Interest</li>
              <li>Data Availability Statement</li>
              <li>Ethical Statement (where applicable)</li>
              <li>References</li>
            </ul>
          </div>

          <!-- 5.2 Review Article -->
          <div class="ag-type-card">
            <div class="ag-type-header">
              <h4>5.2 Review Article</h4>
              <span class="ag-type-tag">Critical Synthesis</span>
            </div>
            <p>Comprehensive, authoritative, and balanced overviews of specific research topics, emerging concepts, current therapeutic strategies, or methodological advances. Systematic reviews and meta-analyses should follow PRISMA guidelines.</p>
          </div>

          <!-- 5.3 Short Communication -->
          <div class="ag-type-card">
            <div class="ag-type-header">
              <h4>5.3 Short Communication</h4>
              <span class="ag-type-tag">Brief Report</span>
            </div>
            <p>Concise communications of urgent, high-impact preliminary findings, novel observations, or compact research projects that warrant accelerated publication before comprehensive studies are finalized.</p>
          </div>

          <!-- 5.4 Case Report / Case Study -->
          <div class="ag-type-card">
            <div class="ag-type-header">
              <h4>5.4 Case Report / Case Study</h4>
              <span class="ag-type-tag">Clinical Practice</span>
            </div>
            <p>Detailed reports of rare, unusual, or diagnostically challenging clinical presentations, novel adverse reactions, unique treatment responses, or innovative clinical workflows. Must adhere to CARE guidelines and include patient consent.</p>
          </div>

          <!-- 5.5 Methodology / Technical Article -->
          <div class="ag-type-card">
            <div class="ag-type-header">
              <h4>5.5 Methodology / Technical Article</h4>
              <span class="ag-type-tag">Methods &amp; Tools</span>
            </div>
            <p>In-depth descriptions of novel experimental protocols, analytical techniques, computational pipelines, bioinformatic algorithms, or modified methodologies with rigorous benchmarking against standard existing approaches.</p>
          </div>

          <!-- 5.6 Editorial / Commentary / Perspective -->
          <div class="ag-type-card">
            <div class="ag-type-header">
              <h4>5.6 Editorial / Commentary / Perspective</h4>
              <span class="ag-type-tag">Opinion &amp; Analysis</span>
            </div>
            <p>Scholarly discussions, expert commentaries, policy assessments, or perspectives on pressing scientific, public health, or ethical questions. Usually commissioned by the Editor-in-Chief, but spontaneous proposals are considered.</p>
          </div>
        </section>

        <!-- ============================================================
             6. ORIGINALITY AND EXCLUSIVITY
             ============================================================ -->
        <section id="originality">
          <h2><span class="section-num">4.</span> Originality and Exclusivity of Submission</h2>
          <p>AJSMR insists upon the highest standards of academic integrity. Submitted manuscripts:</p>
          <ul>
            <li><strong>Must be original:</strong> The manuscript represents genuine, authentic work carried out by the authors.</li>
            <li><strong>Must not have been previously published:</strong> The work cannot have been published in substantially the same form in any other peer-reviewed journal, book chapter, or conference proceedings.</li>
            <li><strong>Must not be under consideration elsewhere:</strong> The submission must not be under review, accepted, or submitted concurrently to any other journal.</li>
            <li><strong>Must properly acknowledge previous work:</strong> All relevant prior publications, data sources, software packages, and third-party contributions must be accurately cited.</li>
          </ul>
          <p><strong>Preprint Policy:</strong> AJSMR supports scholarly communication and permits submissions of manuscripts previously deposited on recognized preprint servers (e.g., bioRxiv, medRxiv, arXiv, Preprints.org). Authors must disclose the preprint server name, DOI, and URL during initial submission. Upon publication, authors should update their preprint record with a citation and link to the final published version in AJSMR.</p>
        </section>

        <!-- ============================================================
             7. AUTHORSHIP & CORRESPONDING AUTHOR
             ============================================================ -->
        <section id="authorship">
          <h2><span class="section-num">5.</span> Authorship &amp; Contributorship</h2>
          <p>AJSMR strictly adheres to the authorship standards formulated by the International Committee of Medical Journal Editors (ICMJE). Every individual designated as an author must have made substantial intellectual contributions to the work, satisfying all of the following criteria:</p>
          <ol>
            <li><strong>Conception or design</strong> of the study, or the acquisition, analysis, or interpretation of data.</li>
            <li><strong>Methodology</strong> formulation or experimental execution.</li>
            <li><strong>Drafting the manuscript</strong> or critically revising it for important intellectual content.</li>
            <li><strong>Final approval</strong> of the version to be published.</li>
            <li><strong>Agreement to be accountable</strong> for all aspects of the work in ensuring that questions related to the accuracy or integrity of any part of the work are appropriately investigated and resolved.</li>
          </ol>
          <p>Acquisition of funding, collection of routine data, or general supervision of the research group alone does not justify authorship. Such contributions should be recognized in the <em>Acknowledgments</em> section.</p>

          <h3>Corresponding Author Responsibilities</h3>
          <p>One author must be designated as the <strong>Corresponding Author</strong>. The corresponding author acts on behalf of all co-authors as the primary contact with the editorial office throughout the submission, peer review, copyediting, and production stages. Key responsibilities include:</p>
          <ul>
            <li>Ensuring that all listed authors meet the ICMJE authorship criteria and have approved the final manuscript prior to submission.</li>
            <li>Submitting all necessary declarations, including conflicts of interest, funding sources, and ethical approvals.</li>
            <li>Coordinating prompt, comprehensive responses to reviewer comments and editorial revisions.</li>
            <li>Carefully reviewing and approving galley proofs within the designated timeframe.</li>
            <li>Handling post-publication correspondence, data requests, and material transfer queries.</li>
          </ul>
        </section>

        <!-- ============================================================
             8. CHANGES IN AUTHORSHIP
             ============================================================ -->
        <section id="authorship-changes">
          <h2><span class="section-num">6.</span> Changes in Authorship</h2>
          <p>Authors should carefully consider and finalize the list and order of authors before submitting their manuscript. Any proposed change to the authorship list—including:</p>
          <ul>
            <li>Adding new authors</li>
            <li>Removing authors</li>
            <li>Rearranging the order of authors</li>
          </ul>
          <p>after initial submission requires formal written justification sent to the Editor-in-Chief. The request must include written confirmation (via email or signed declaration) from <strong>all authors</strong> agreeing with the proposed addition, removal, or rearrangement, including any author being removed. Changes cannot be approved without unanimous written consent and editorial concurrence in accordance with Committee on Publication Ethics (COPE) guidelines.</p>
        </section>

        <!-- ============================================================
             9. MANUSCRIPT PREPARATION
             ============================================================ -->
        <section id="preparation">
          <h2><span class="section-num">7.</span> Manuscript Preparation Standards</h2>
          <p>Manuscripts must be prepared according to the following formatting requirements:</p>
          <ul>
            <li><strong>File Format:</strong> Microsoft Word document format (<code>.doc</code> or <code>.docx</code>). LaTeX users should compile to Word or clean RTF for initial submission.</li>
            <li><strong>Font &amp; Size:</strong> Times New Roman, 12 pt for main text; headings should be bold and clearly hierarchical (Level 1: 14 pt Bold; Level 2: 13 pt Bold; Level 3: 12 pt Bold Italic).</li>
            <li><strong>Line Spacing &amp; Margins:</strong> 1.5 or double line spacing throughout the manuscript with 1-inch (2.54 cm) margins on all four sides.</li>
            <li><strong>Page Numbering:</strong> Number all pages consecutively in Arabic numerals at the bottom right or center. Continuous line numbering is strongly encouraged to assist reviewers.</li>
            <li><strong>Tables:</strong> Must be editable Word tables (never inserted as non-editable images). Place each table on a new page or sequentially after its first mention.</li>
            <li><strong>Figures:</strong> High-resolution files (minimum 300 dpi for halftones/color photographs; 600–1200 dpi for line art and combinations). Formats accepted: TIFF, JPEG, PNG, EPS.</li>
            <li><strong>Consistent Terminology:</strong> Use standard international nomenclature (e.g., IUPAC for chemical compounds, HUGO Gene Nomenclature Committee for human genes).</li>
            <li><strong>Consistent Abbreviations &amp; Units:</strong> Define all non-standard abbreviations at their first mention in both abstract and main text. Use International System of Units (SI) throughout.</li>
            <li><strong>Consistent Reference Formatting:</strong> Follow the journal's prescribed reference citation standard consistently throughout text and bibliography.</li>
          </ul>
        </section>

        <!-- ============================================================
             10. TITLE PAGE
             ============================================================ -->
        <section id="title-page">
          <h2><span class="section-num">8.</span> Title Page Requirements</h2>
          <p>The manuscript must open with a comprehensive Title Page containing:</p>
          <ul>
            <li><strong>Article Title:</strong> Concise, informative, and scientifically descriptive (avoid unnecessary jargon, abbreviations, or rhetorical questions).</li>
            <li><strong>Full Author Names:</strong> First name, middle initial(s), and surname of each contributing author.</li>
            <li><strong>Institutional Affiliations:</strong> Department, Institution, City, State/Province, and Country for each author. Use superscript Arabic numerals (1, 2, 3) to link authors to their respective affiliations.</li>
            <li><strong>Corresponding Author:</strong> Identified with an asterisk (*), providing full name, institutional postal address, official telephone number, and official institutional email address.</li>
            <li><strong>ORCID iD:</strong> Authors are strongly encouraged to provide their 16-digit ORCID identifier (e.g., https://orcid.org/0000-0002-1825-0097).</li>
            <li><strong>Running Title:</strong> A short running head of no more than 50 characters including spaces.</li>
          </ul>
        </section>

        <!-- ============================================================
             11. ABSTRACT & 12. KEYWORDS
             ============================================================ -->
        <section id="abstract-keywords">
          <h2><span class="section-num">9.</span> Abstract &amp; Keywords</h2>
          <h3>Abstract Structure</h3>
          <p>For Original Research Articles, authors must provide a structured abstract of approximately <strong>200–300 words</strong> organized under the following mandatory subheadings:</p>
          <ul>
            <li><strong>Background:</strong> The context and scientific rationale for the study.</li>
            <li><strong>Objective:</strong> The specific aims, hypotheses, or research questions addressed.</li>
            <li><strong>Methods:</strong> Essential study design, setting, population or biological systems, interventions, and primary analytical/statistical techniques.</li>
            <li><strong>Results:</strong> Key findings with numerical data, effect sizes, and statistical significance values (e.g., p-values, 95% CI).</li>
            <li><strong>Conclusion:</strong> Direct, evidence-based conclusions and significant implications of the study.</li>
          </ul>
          <p><em>Note:</em> The abstract must be self-contained and concise. Do not include literature citations, unexplained abbreviations, trade names, or unsupported generalizations.</p>

          <h3>Keywords</h3>
          <p>Authors must provide <strong>4 to 8 relevant keywords</strong> immediately below the abstract. Keywords should accurately represent the major scientific concepts, methods, organisms, and themes of the study. Authors are encouraged to utilize standardized biomedical terminology, such as <strong>Medical Subject Headings (MeSH)</strong> terms from the U.S. National Library of Medicine, to optimize discovery and citation indexing.</p>
        </section>

        <!-- ============================================================
             13. INTRODUCTION
             ============================================================ -->
        <section id="introduction-section">
          <h2><span class="section-num">10.</span> Introduction</h2>
          <p>The Introduction should orient the reader and build a compelling, evidence-based rationale for the study. It must concisely cover:</p>
          <ul>
            <li><strong>Scientific Background:</strong> A focused summary of the broader scientific, clinical, or biological context.</li>
            <li><strong>Importance of the Problem:</strong> Why the research problem matters to public health, clinical medicine, or fundamental science.</li>
            <li><strong>Relevant Prior Research:</strong> A balanced synthesis of landmark and recent publications directly pertinent to the topic.</li>
            <li><strong>Knowledge Gap:</strong> Specific limitations, controversies, or unanswered questions in the existing literature.</li>
            <li><strong>Rationale &amp; Objectives:</strong> The distinct scientific reasoning underlying this study, concluding with a clear statement of the specific hypotheses and aims.</li>
          </ul>
          <p>Do not present results, detailed methods, or extensive conclusions in the Introduction.</p>
        </section>

        <!-- ============================================================
             14. MATERIALS AND METHODS (INCLUDING COMPUTATIONAL STUDIES)
             ============================================================ -->
        <section id="methods">
          <h2><span class="section-num">11.</span> Materials and Methods</h2>
          <p>The Materials and Methods section must provide complete, rigorous methodological detail to enable independent researchers to evaluate and reproduce the experimental, clinical, or computational work. Subsections should address:</p>
          <ul>
            <li><strong>Study Design:</strong> Overall framework (e.g., randomized controlled trial, prospective cohort, in vitro assay, cross-sectional survey).</li>
            <li><strong>Materials &amp; Reagents:</strong> Source, manufacturer, purity grade, chemical catalog numbers, or lot numbers for critical biological and chemical reagents.</li>
            <li><strong>Study Population / Experimental Model:</strong> Clear specification of human subjects, animal strains, cell lines, bacterial strains, or tissue samples.</li>
            <li><strong>Inclusion &amp; Exclusion Criteria:</strong> Detailed eligibility requirements where applicable.</li>
            <li><strong>Experimental Procedures:</strong> Step-by-step documentation of assays, interventions, instrument configurations, and timelines.</li>
            <li><strong>Sample Size &amp; Controls:</strong> Statistical power calculations, rationale for sample sizing, and rigorous negative/positive controls.</li>
            <li><strong>Data Collection:</strong> Instrumentation, calibrated measurement protocols, and blinding procedures.</li>
            <li><strong>Statistical Analysis:</strong> Specific statistical software, version numbers, statistical tests applied, assumptions tested (e.g., normality), and significance thresholds.</li>
            <li><strong>Accession Numbers:</strong> Public repository identifiers for novel DNA/RNA sequences (GenBank/ENA/DDBJ), protein structures (Protein Data Bank - PDB), microarray/RNA-seq data (GEO/ArrayExpress), or chemical compounds.</li>
            <li><strong>Relevant Protocols:</strong> Reference to validated published protocols or registered study protocols.</li>
          </ul>

          <h3>Special Requirements for Computational Studies</h3>
          <p>Manuscripts involving computational biology, bioinformatics, molecular modeling, virtual screening, or computer-aided drug design must include exact reproducible details:</p>
          <ul>
            <li><strong>Software &amp; Versions:</strong> Precise program names, versions, operating environments, and command-line parameters or scripts used.</li>
            <li><strong>Databases:</strong> Version numbers, release dates, and query parameters for all biological databases (e.g., UniProt, PDB, NCBI, ChEMBL, PubChem, Ensembl).</li>
            <li><strong>Target Identifiers:</strong> Specific PDB IDs, UniProt accession numbers, chain identifiers, resolution, and preparation steps (protonation states, missing loops, water removal).</li>
            <li><strong>Ligand Sources &amp; Preparation:</strong> 2D/3D structure sources, tautomers, protonation states at physiological pH, energy minimization algorithms, and force fields.</li>
            <li><strong>Docking Parameters:</strong> Coordinates of the binding grid (x, y, z center and dimensions), exhaustiveness, number of poses generated, and scoring functions applied.</li>
            <li><strong>Molecular Dynamics (MD) Parameters:</strong> Simulation package, force field (e.g., AMBER, CHARMM, GROMOS, OPLS), water model (e.g., TIP3P), periodic boundary conditions, neutralization ions, energy minimization steps, equilibration phases (NVT and NPT), temperature/pressure coupling algorithms, total simulation duration (ns), and trajectory analysis tools.</li>
            <li><strong>Statistical &amp; Analytical Procedures:</strong> Methods used for free energy calculations (e.g., MM-PBSA/MM-GBSA), RMSD, RMSF, radius of gyration, hydrogen bond analysis, and clustering.</li>
          </ul>
        </section>

        <!-- ============================================================
             15. RESULTS, 16. DISCUSSION, 17. CONCLUSION
             ============================================================ -->
        <section id="results-discussion-conclusion">
          <h2><span class="section-num">12.</span> Results, Discussion &amp; Conclusion</h2>

          <h3>Results</h3>
          <p>The Results section should present the experimental and observational findings clearly and logically:</p>
          <ul>
            <li>Present findings in chronological or thematic order using informative subheadings.</li>
            <li>Avoid redundant repetition of data already depicted in tables and figures; highlight significant trends and key observations in the narrative.</li>
            <li>Refer explicitly to tables and figures in sequence (e.g., "as summarized in Table 1", "Figure 2A illustrates...").</li>
            <li>Report appropriate quantitative metrics with exact statistical values (e.g., mean ± standard deviation, median with interquartile range, exact <em>p</em>-values, degrees of freedom, confidence intervals).</li>
            <li>Distinguish findings from interpretation: state the empirical observations neutrally without speculative commentary. Speculative interpretation belongs strictly in the Discussion.</li>
          </ul>

          <h3>Discussion</h3>
          <p>The Discussion interprets the findings in the context of broader scientific literature. Authors must thoroughly address:</p>
          <ul>
            <li><strong>Major Findings:</strong> Highlight key discoveries and direct answers to the original study hypotheses.</li>
            <li><strong>Comparison with Previous Literature:</strong> Compare and contrast results with relevant published studies, exploring similarities, contradictions, and mechanistic explanations.</li>
            <li><strong>Scientific Significance:</strong> Explain the biological, clinical, or technological meaning of the findings.</li>
            <li><strong>Strengths and Limitations:</strong> Critically assess the methodological strengths and candidly disclose limitations (e.g., sample size, model constraints, unmeasured confounders).</li>
            <li><strong>Implications &amp; Future Directions:</strong> Discuss practical clinical or scientific implications and outline fruitful avenues for subsequent research.</li>
          </ul>

          <h3>Conclusion</h3>
          <p>The Conclusion section should succinctly summarize the principal conclusions deduced from the study. <strong>All conclusions must be firmly supported by the experimental evidence presented in the manuscript.</strong> Authors must avoid unwarranted claims, unsubstantiated generalizations, or commercial endorsements that extend beyond the actual data.</p>
        </section>

        <!-- ============================================================
             18. TABLES, 19. FIGURES, 20. SUPPLEMENTARY
             ============================================================ -->
        <section id="tables-figures-supplementary">
          <h2><span class="section-num">13.</span> Tables, Figures &amp; Supplementary Material</h2>

          <h3>Tables</h3>
          <ul>
            <li>Number tables consecutively in Arabic numerals (Table 1, Table 2, etc.) in order of citation in the text.</li>
            <li>Provide a concise, descriptive title placed immediately above each table.</li>
            <li>Tables must be created using standard Microsoft Word table tools with cells, rows, and columns. <em>Do not paste tables as graphic images or screenshots.</em></li>
            <li>Cite each table explicitly in the text.</li>
            <li>Use explanatory footnotes placed below the table to define non-standard abbreviations, test statistics, and significance asterisks (e.g., <sup>*</sup><em>p</em> &lt; 0.05, <sup>**</sup><em>p</em> &lt; 0.01).</li>
          </ul>

          <h3>Figures and Illustrations</h3>
          <ul>
            <li>Number figures consecutively in Arabic numerals (Figure 1, Figure 2, etc.) in order of appearance in the text.</li>
            <li>Submit graphics in high resolution: minimum <strong>300 dpi</strong> for photographic images/halftones; minimum <strong>600 to 1200 dpi</strong> for graphs, line art, and multi-panel figures. Formats: TIFF, EPS, PNG, or high-quality JPEG.</li>
            <li>Include descriptive figure captions with sufficient detail to allow the reader to understand the illustration without referencing the main text. Place captions below figures or on a separate caption page.</li>
            <li>Ensure text labels, scale bars, axis titles, and symbols within the figures are crisp, legible, and uniform in font style (preferably Arial or Helvetica).</li>
            <li>Authors are solely responsible for obtaining formal written permission from the copyright owner to reproduce any previously published figure, table, or schematic. Appropriate credit lines must appear in the figure caption.</li>
          </ul>

          <h3>Supplementary Material</h3>
          <p>Supplementary material includes extended data, supplementary tables (e.g., Table S1, Table S2), high-resolution supplementary figures (e.g., Figure S1), raw datasets, molecular simulation trajectories, or full mathematical derivations that are relevant but too extensive for the main article. Supplementary files must be clearly labeled, formatted consistently, and cited sequentially in the text.</p>
        </section>

        <!-- ============================================================
             21. STATISTICAL ANALYSIS
             ============================================================ -->
        <section id="statistics">
          <h2><span class="section-num">14.</span> Statistical Analysis</h2>
          <p>Authors must ensure that all statistical methods applied are scientifically rigorous and appropriate for the study design, sampling frame, and distribution of data. Where applicable, provide:</p>
          <ul>
            <li>The specific statistical test applied for each comparison (e.g., Student's t-test, Mann-Whitney U test, one-way ANOVA with Tukey's post-hoc test, Chi-square test, Cox proportional hazards regression).</li>
            <li>The number of independent observations, biological replicates, and experimental repetitions (<em>n</em>).</li>
            <li>The predetermined significance threshold (typically α = 0.05).</li>
            <li>Exact <em>p</em>-values rather than vague inequalities (e.g., report <em>p</em> = 0.034 rather than <em>p</em> &lt; 0.05, reserving <em>p</em> &lt; 0.001 for extremely small values).</li>
            <li>Appropriate measures of central tendency and variability (mean and standard deviation for parametric data; median and interquartile range for non-parametric data).</li>
            <li>Effect sizes and 95% confidence intervals (95% CI) whenever appropriate to convey the magnitude and precision of observed effects.</li>
          </ul>
        </section>

        <!-- ============================================================
             22. ETHICAL APPROVAL & 23. INFORMED CONSENT & 24. CLINICAL TRIALS
             ============================================================ -->
        <section id="ethical-standards">
          <h2><span class="section-num">15.</span> Ethical Approval, Informed Consent &amp; Clinical Trials</h2>

          <h3>Ethical Approval for Human and Animal Research</h3>
          <p>All research involving human participants, human biological specimens, identifiable human health data, or vertebrate animals must comply with recognized international ethical guidelines:</p>
          <ul>
            <li><strong>Human Subjects:</strong> Research must comply with the ethical principles of the <a href="https://www.wma.net/policies-post/wma-declaration-of-helsinki/" target="_blank" rel="noopener">Declaration of Helsinki</a>. The manuscript must state the full name of the institutional review board (IRB) or independent ethics committee (IEC), the formal approval number/reference code, and the approval date.</li>
            <li><strong>Animal Research:</strong> Investigations involving animals must adhere to internationally accepted standards of laboratory animal care and local legislation. Manuscripts must state the approving institutional animal care and use committee (IACUC), ethical protocol number, and compliance with the 3Rs (Replacement, Reduction, Refinement).</li>
          </ul>

          <h3>Informed Consent</h3>
          <p>For any study involving living human participants, authors must state that <strong>written informed consent</strong> was obtained from all participants (or their legally authorized guardians/representatives) prior to study enrollment. Furthermore, identifiable photographs, pedigree charts, or identifying personal details will only be published if explicit written consent for publication was granted by the patient or guardian.</p>

          <h3>Clinical Trials Registration</h3>
          <p>In accordance with ICMJE requirements, all clinical trials must be prospectively registered in a publicly accessible trial registry approved by the WHO International Clinical Trials Registry Platform (ICTRP) or ClinicalTrials.gov (e.g., ClinicalTrials.gov, CTRI, ISRCTN). Authors must state the:</p>
          <ul>
            <li>Trial registration number</li>
            <li>Name of the trial registry</li>
            <li>Date of registration</li>
          </ul>
          <p>Manuscripts reporting randomized trials must adhere to the <strong>CONSORT Statement</strong> and include a completed CONSORT checklist and flow diagram upon submission.</p>
        </section>

        <!-- ============================================================
             25. REPORTING GUIDELINES
             ============================================================ -->
        <section id="reporting-guidelines">
          <h2><span class="section-num">16.</span> Reporting Guidelines</h2>
          <p>Authors are strongly encouraged to adhere to established reporting guidelines endorsed by the <strong>EQUATOR Network</strong> to maximize methodological completeness and reproducibility. Relevant guidelines include:</p>

          <table class="ag-std-table">
            <thead>
              <tr>
                <th style="width:25%">Guideline</th>
                <th style="width:40%">Study Type</th>
                <th style="width:35%">Resource</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>CONSORT</strong></td>
                <td>Randomized controlled trials</td>
                <td><a href="https://www.consort-statement.org" target="_blank" rel="noopener">consort-statement.org</a></td>
              </tr>
              <tr>
                <td><strong>STROBE</strong></td>
                <td>Observational studies (cohort, case-control, cross-sectional)</td>
                <td><a href="https://www.strobe-statement.org" target="_blank" rel="noopener">strobe-statement.org</a></td>
              </tr>
              <tr>
                <td><strong>PRISMA</strong></td>
                <td>Systematic reviews and meta-analyses</td>
                <td><a href="https://www.prisma-statement.org" target="_blank" rel="noopener">prisma-statement.org</a></td>
              </tr>
              <tr>
                <td><strong>CARE</strong></td>
                <td>Clinical case reports</td>
                <td><a href="https://www.care-statement.org" target="_blank" rel="noopener">care-statement.org</a></td>
              </tr>
              <tr>
                <td><strong>ARRIVE</strong></td>
                <td>In vivo animal experiments</td>
                <td><a href="https://arriveguidelines.org" target="_blank" rel="noopener">arriveguidelines.org</a></td>
              </tr>
              <tr>
                <td><strong>STARD</strong></td>
                <td>Diagnostic accuracy studies</td>
                <td><a href="https://www.equator-network.org/reporting-guidelines/stard/" target="_blank" rel="noopener">equator-network.org/stard</a></td>
              </tr>
              <tr>
                <td><strong>SRQR / COREQ</strong></td>
                <td>Qualitative research methodologies</td>
                <td><a href="https://www.equator-network.org" target="_blank" rel="noopener">equator-network.org</a></td>
              </tr>
            </tbody>
          </table>
        </section>

        <!-- ============================================================
             26. DATA AVAILABILITY & 27. FUNDING & 28. CONFLICTS & 29. ACKNOWLEDGMENTS
             ============================================================ -->
        <section id="declarations">
          <h2><span class="section-num">17.</span> Declarations &amp; Statements</h2>

          <h3>Data Availability Statement</h3>
          <p>AJSMR requires all original research submissions to include a mandatory Data Availability Statement describing where and how the data supporting the results can be accessed. Acceptable formats include:</p>
          <ul>
            <li><em>"Data are publicly available in [Repository Name] at [DOI or accession link]."</em></li>
            <li><em>"The datasets generated and/or analysed during the current study are available from the corresponding author upon reasonable request."</em></li>
            <li><em>"All data generated or analysed during this study are included in this published article and its supplementary information files."</em></li>
            <li><em>"The data that support the findings of this study cannot be shared publicly due to [ethical/legal/privacy/institutional] restrictions."</em></li>
          </ul>

          <h3>Funding Statement</h3>
          <p>All sources of direct and indirect financial support for the conduct of the research must be fully disclosed. If the research received grant funding, provide the agency name and grant number:</p>
          <div class="ag-notice">
            <p><strong>Example:</strong> <em>"Funding: This research received funding from [Funding Agency], Grant No. [XXXX]."</em></p>
          </div>
          <p>If no external funding was received, authors must state:</p>
          <div class="ag-notice">
            <p><strong>Example:</strong> <em>"Funding: The authors received no external funding for this study."</em></p>
          </div>

          <h3>Conflict of Interest</h3>
          <p>Authors must declare any financial, personal, or professional relationships that could be perceived as influencing the objectivity or interpretation of the reported research. If no conflict exists, declare:</p>
          <div class="ag-notice">
            <p><strong>Example:</strong> <em>"Conflict of Interest: The authors declare that they have no conflict of interest."</em></p>
          </div>

          <h3>Acknowledgments</h3>
          <p>Individuals who contributed substantively to the research (such as providing technical assistance, language proofreading, laboratory management, or scientific counsel) but do not fulfill the ICMJE authorship criteria should be acknowledged. Authors must obtain written permission from all named individuals prior to submission.</p>
        </section>

        <!-- ============================================================
             30. ARTIFICIAL INTELLIGENCE (AI) POLICY
             ============================================================ -->
        <section id="ai-policy">
          <h2><span class="section-num">18.</span> Artificial Intelligence &amp; Generative AI Policy</h2>
          <p>AJSMR recognizes the emergence of Artificial Intelligence and Generative AI technologies in research workflows. Authors must adhere to the following strict guidelines:</p>
          <ul>
            <li><strong>AI tools cannot be listed as authors:</strong> Generative AI models (such as ChatGPT, Claude, Gemini, Large Language Models, or image generators) cannot take legal or scientific accountability for published work, cannot hold copyright, and therefore cannot be listed as authors or co-authors.</li>
            <li><strong>Authors remain fully accountable:</strong> The human authors remain entirely responsible for the originality, scientific veracity, factual accuracy, and integrity of the entire manuscript.</li>
            <li><strong>Mandatory Disclosure:</strong> Authors who utilize generative AI or AI-assisted tools in the preparation of a manuscript (e.g., text drafting, image enhancement, coding, or data formatting) must explicitly disclose the tool name, version/model, date of use, and the exact scope of application in the Materials and Methods or Acknowledgments section.</li>
            <li><strong>Verification of AI Content:</strong> Authors are obligated to manually verify all AI-generated assertions, citations, calculations, and references for accuracy and authenticity to avoid hallucinations.</li>
            <li><strong>Protection of Confidentiality:</strong> Authors must not upload unpublished manuscript data, sensitive human genetic details, or confidential clinical information to public AI platforms where data may be retained or utilized for model training.</li>
          </ul>
        </section>

        <!-- ============================================================
             31. PLAGIARISM AND RESEARCH INTEGRITY
             ============================================================ -->
        <section id="plagiarism">
          <h2><span class="section-num">19.</span> Plagiarism &amp; Research Integrity</h2>
          <p>AJSMR maintains zero tolerance for scientific misconduct, data fabrication, falsification, plagiarism, duplicate submission, and improper authorship. All manuscripts are screened with professional plagiarism detection software upon submission and prior to publication.</p>
          <p>Manuscripts exhibiting significant text recycling, unauthorized verbatim copying, or unattributed ideas will be summarily rejected. For full details regarding our screening criteria, investigation workflows, and retraction policies, please review the complete <a href="plagiarismresearchintegritypolicy.php">Plagiarism, Research Integrity &amp; Retraction Policy</a>.</p>
        </section>

        <!-- ============================================================
             32. REFERENCES
             ============================================================ -->
        <section id="references">
          <h2><span class="section-num">20.</span> References</h2>
          <p>Authors should cite authoritative, peer-reviewed, and up-to-date primary research literature. Reference citations must adhere to the following principles:</p>
          <ul>
            <li>Every citation in the manuscript text must have a corresponding entry in the reference list, and vice versa.</li>
            <li>Include Digital Object Identifier (DOI) hyperlinks for all referenced articles where available.</li>
            <li>Avoid excessive self-citation, citation cartels, or irrelevant citations intended to artificially manipulate citation metrics.</li>
          </ul>

          <h3>Reference Format Examples</h3>

          <div class="ag-ref-example">
            <div class="ag-ref-type">1. Journal Article</div>
            <div class="ag-ref-text">
              Smith AB, Johnson CD, Kumar MP. Molecular docking and biological evaluation of novel quinoline derivatives against resistant bacterial pathogens. <em>Am J Sci Med Res</em>. 2024;10(2):45–56. https://doi.org/10.1234/ajsmr.2024.102045
            </div>
          </div>

          <div class="ag-ref-example">
            <div class="ag-ref-type">2. Book / Monograph</div>
            <div class="ag-ref-text">
              Rang HP, Dale MM, Ritter JM, Flower RJ, Henderson G. <em>Rang &amp; Dale's Pharmacology</em>. 9th ed. London: Elsevier Churchill Livingstone; 2020:120–135.
            </div>
          </div>

          <div class="ag-ref-example">
            <div class="ag-ref-type">3. Website / Online Database</div>
            <div class="ag-ref-text">
              World Health Organization. Antimicrobial resistance global report on surveillance. Geneva: WHO; 2024. Available from: https://www.who.int/news-room/fact-sheets/detail/antimicrobial-resistance. Accessed September 15, 2026.
            </div>
          </div>
        </section>

        <!-- ============================================================
             33. COPYRIGHT AND PERMISSIONS
             ============================================================ -->
        <section id="copyright-permissions">
          <h2><span class="section-num">21.</span> Copyright &amp; Permissions</h2>
          <p>Authors are responsible for obtaining formal written copyright permissions to reproduce any previously published text, illustrations, tables, or diagnostic graphics. AJSMR operates under an Open Access model where authors retain copyright under a <strong>Creative Commons Attribution (CC BY 4.0)</strong> license. Please consult our <a href="openaccesscopyrightpolicy.php">Open Access, Copyright &amp; Licensing Policy</a> for complete details.</p>
        </section>

        <!-- ============================================================
             34. PUBLICATION FEE POLICY (APC)
             ============================================================ -->
        <section id="fee-policy">
          <h2><span class="section-num">22.</span> Publication Fee Policy</h2>

          <div class="ag-fee-card">
            <div class="ag-fee-header">
              <h3>Publication Fee Policy</h3>
              <p>The American Journal of Science and Medical Research (AJSMR) is a fully open-access journal. All articles are freely available online immediately upon publication. To support the costs of editorial management, peer review, copyediting, typesetting, online hosting, and long-term archiving, authors are required to pay an Article Publishing Charge (APC) after acceptance.</p>
            </div>

            <div class="ag-fee-body">
              <table class="ag-fee-table">
                <thead>
                  <tr>
                    <th>Author Category</th>
                    <th style="text-align:right">Article Publishing Charge (APC)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><strong>Authors based in India</strong></td>
                    <td style="text-align:right"><span class="ag-fee-amount">₹3,000 INR</span></td>
                  </tr>
                  <tr>
                    <td><strong>International authors</strong></td>
                    <td style="text-align:right"><span class="ag-fee-amount">$200 USD</span></td>
                  </tr>
                </tbody>
              </table>

              <div class="ag-fee-features">
                <div class="ag-fee-pill">
                  <div class="ag-fee-pill-title">No Submission Fees</div>
                  <div class="ag-fee-pill-desc">Authors are not charged at the time of submission.</div>
                </div>
                <div class="ag-fee-pill">
                  <div class="ag-fee-pill-title">No Hidden Costs</div>
                  <div class="ag-fee-pill-desc">AJSMR does not levy page charges, color figure charges, or supplementary data fees.</div>
                </div>
                <div class="ag-fee-pill">
                  <div class="ag-fee-pill-title">Open Access License</div>
                  <div class="ag-fee-pill-desc">All articles are published under a Creative Commons Attribution (CC BY) license.</div>
                </div>
              </div>
            </div>

            <div class="ag-fee-footer">
              <p style="margin:0 0 8px"><strong>Waivers and Discounts:</strong> AJSMR may provide partial or full waivers for authors from low-income countries or in cases of demonstrated financial hardship. Authors seeking waiver assistance must apply at the time of manuscript submission with relevant documentation.</p>
              <p style="margin:0 0 8px"><strong>Payment Procedure:</strong> The Article Publishing Charge is invoiced only after the manuscript has completed peer review and received official editorial acceptance. Payment instructions and banking transfer details are communicated directly by the editorial office.</p>
              <p style="margin:8px 0 0;padding-top:8px;border-top:1px dashed #dbe5ee">For complete details, see the <a href="apcpolicy.php" style="font-weight:700;color:#075fa8;text-decoration:underline">Article Publication Charges Policy</a>.</p>
            </div>
          </div>
        </section>

        <!-- ============================================================
             35. MANUSCRIPT SUBMISSION & CTA
             ============================================================ -->
        <section id="submission">
          <h2><span class="section-num">23.</span> Manuscript Submission</h2>
          <p>Manuscripts must be submitted through the official <strong>AJSMR Online Submission System</strong>. Required submission files include:</p>
          <ul>
            <li><strong>Main Manuscript:</strong> Including title, structured abstract, keywords, main text, references, and tables in Microsoft Word format.</li>
            <li><strong>Title Page (where required for blind review):</strong> Author names, institutional affiliations, corresponding author contacts, and acknowledgments.</li>
            <li><strong>Figures:</strong> High-resolution graphical files (TIFF, PNG, EPS, or high-res JPEG).</li>
            <li><strong>Tables:</strong> Editable Word format.</li>
            <li><strong>Supplementary Files:</strong> Supporting data, extended methods, or compound characterization datasets.</li>
            <li><strong>Ethics Documentation:</strong> Official approval letters and participant consent forms where applicable.</li>
            <li><strong>Conflict of Interest Declaration:</strong> Signed disclosure form or in-manuscript statement.</li>
            <li><strong>Supporting Documents:</strong> Reporting guideline checklists (e.g., CONSORT, PRISMA, CARE) where applicable.</li>
          </ul>

          <div class="ag-submit-box">
            <div class="ag-submit-box-copy">
              <h3>Ready to Submit Your Manuscript?</h3>
              <p>Submit your article online to The American Journal of Science and Medical Research via our secure editorial platform.</p>
            </div>
            <a class="ag-submit-btn" href="editorial/login.php">Submit Manuscript <span>➤</span></a>
          </div>
        </section>

        <!-- ============================================================
             36. SUBMISSION CHECKLIST
             ============================================================ -->
        <section id="submission-checklist">
          <h2><span class="section-num">24.</span> Submission Checklist</h2>
          <p>Before uploading your manuscript to the online submission platform, please confirm that each of the following requirements has been fulfilled:</p>

          <div class="ag-checklist">
            <div class="ag-check-item"><span class="ag-check-icon">✓</span><span>Manuscript is within AJSMR scope</span></div>
            <div class="ag-check-item"><span class="ag-check-icon">✓</span><span>Manuscript is original</span></div>
            <div class="ag-check-item"><span class="ag-check-icon">✓</span><span>Manuscript is not under consideration elsewhere</span></div>
            <div class="ag-check-item"><span class="ag-check-icon">✓</span><span>All authors have approved the manuscript</span></div>
            <div class="ag-check-item"><span class="ag-check-icon">✓</span><span>Corresponding author identified</span></div>
            <div class="ag-check-item"><span class="ag-check-icon">✓</span><span>Author affiliations complete</span></div>
            <div class="ag-check-item"><span class="ag-check-icon">✓</span><span>Abstract provided</span></div>
            <div class="ag-check-item"><span class="ag-check-icon">✓</span><span>Keywords provided</span></div>
            <div class="ag-check-item"><span class="ag-check-icon">✓</span><span>Tables and figures numbered</span></div>
            <div class="ag-check-item"><span class="ag-check-icon">✓</span><span>References checked</span></div>
            <div class="ag-check-item"><span class="ag-check-icon">✓</span><span>Ethical approval included where applicable</span></div>
            <div class="ag-check-item"><span class="ag-check-icon">✓</span><span>Informed consent included where applicable</span></div>
            <div class="ag-check-item"><span class="ag-check-icon">✓</span><span>Funding disclosed</span></div>
            <div class="ag-check-item"><span class="ag-check-icon">✓</span><span>Conflict of Interest statement included</span></div>
            <div class="ag-check-item"><span class="ag-check-icon">✓</span><span>Data Availability Statement included where appropriate</span></div>
            <div class="ag-check-item"><span class="ag-check-icon">✓</span><span>AI use disclosed where applicable</span></div>
            <div class="ag-check-item"><span class="ag-check-icon">✓</span><span>Copyright permissions obtained where applicable</span></div>
            <div class="ag-check-item"><span class="ag-check-icon">✓</span><span>Manuscript checked for language and formatting</span></div>
            <div class="ag-check-item"><span class="ag-check-icon">✓</span><span>All required files uploaded</span></div>
          </div>
        </section>

        <!-- ============================================================
             37. PEER REVIEW & 38. REVISION OF MANUSCRIPTS
             ============================================================ -->
        <section id="peer-review">
          <h2><span class="section-num">25.</span> Peer Review &amp; Manuscript Revision</h2>

          <h3>Peer Review Process</h3>
          <p>Every submission undergoes an initial editorial evaluation by the Editor-in-Chief or an assigned Editorial Board member to determine scope alignment, scientific merit, and compliance with author guidelines. Manuscripts meeting the initial threshold are assigned to at least two independent expert reviewers for <strong>double-blind peer review</strong>. Evaluation criteria include:</p>
          <ul>
            <li>Scientific quality and originality</li>
            <li>Methodological rigor and reproducibility</li>
            <li>Clarity and coherence of presentation</li>
            <li>Relevance to scientific and medical research communities</li>
            <li>Validity of conclusions and appropriate statistical analysis</li>
            <li>Ethical compliance and data transparency</li>
          </ul>
          <p>For complete details on review stages, reviewer selection, and editorial decision thresholds, consult our comprehensive <a href="peerreviewpolicy.php">Peer Review Policy</a>.</p>

          <h3>Revision of Manuscripts</h3>
          <p>When an editorial decision requests manuscript revisions, authors must resubmit their revised package within the specified timeframe. Revised submissions must contain:</p>
          <ol>
            <li><strong>Revised Manuscript:</strong> A clean, finalized version incorporating all corrections.</li>
            <li><strong>Detailed Response to Reviewers:</strong> A point-by-point response document addressing every reviewer comment and editor instruction systematically.</li>
            <li><strong>Clear Indication of Changes:</strong> A marked-up version of the manuscript highlighting all modifications (using Word Track Changes or colored highlighting).</li>
          </ol>
        </section>

        <!-- ============================================================
             39. ACCEPTANCE, PRODUCTION & 40. POST-PUBLICATION
             ============================================================ -->
        <section id="production">
          <h2><span class="section-num">26.</span> Acceptance, Production &amp; Post-Publication Changes</h2>

          <h3>Post-Acceptance Production Workflow</h3>
          <p>Following formal acceptance, the manuscript enters the copyediting, typesetting, and digital formatting workflow. Authors will receive electronic galley proofs in PDF format. During proof verification, authors are asked to carefully verify:</p>
          <ul>
            <li>Full author names and institutional affiliations</li>
            <li>Accuracy and placement of tables, legends, and high-resolution figures</li>
            <li>Completeness of references and citations</li>
            <li>Corresponding author email address and physical address</li>
            <li>Article metadata and funding identifiers</li>
          </ul>
          <p>Galley proofs must be checked thoroughly and returned to the production editor within 48 to 72 hours. Substantial textual additions or alterations to scientific content are not permitted at the proof stage.</p>

          <h3>Corrections and Post-Publication Changes</h3>
          <p>Authors who detect significant factual errors, computational miscalculations, or omission of critical data after online publication should immediately notify the Editorial Office at <a href="mailto:editorajsmr@gmail.com">editorajsmr@gmail.com</a>. Appropriate post-publication notices (Errata, Corrigenda, or Retractions) will be published in accordance with COPE and AJSMR policies.</p>
        </section>

        <!-- ============================================================
             41. RESEARCH INTEGRITY
             ============================================================ -->
        <section id="research-integrity">
          <h2><span class="section-num">27.</span> Research Integrity</h2>
          <p>AJSMR is firmly dedicated to upholding research integrity across all published literature. The journal investigates any substantiated allegation regarding:</p>
          <ul>
            <li>Data fabrication and data falsification</li>
            <li>Plagiarism and text recycling</li>
            <li>Duplicate or redundant publication</li>
            <li>Digital image manipulation (e.g., western blot splicing or enhancement)</li>
            <li>Undisclosed conflicts of interest or hidden sponsorship</li>
            <li>Unethical research conducted without institutional approval</li>
            <li>Improper authorship attribution (guest, gift, or ghost authors)</li>
            <li>Citation manipulation and coercive citations</li>
            <li>Misrepresentation of scientific findings or clinical outcomes</li>
          </ul>
          <p>For detailed investigative procedures, sanctions, and retraction protocols, see our <a href="publicationethics.php">Publication Ethics &amp; Editorial Policy</a> and <a href="plagiarismresearchintegritypolicy.php">Plagiarism, Research Integrity &amp; Retraction Policy</a>.</p>
        </section>

        <!-- ============================================================
             42. RELATED JOURNAL POLICIES
             ============================================================ -->
        <section id="related-policies">
          <h2><span class="section-num">28.</span> Related Journal Policies</h2>
          <p>Authors are encouraged to review our complete suite of institutional policies governing peer review, ethical publication, digital preservation, and data governance:</p>

          <div class="ag-policies-grid">
            <a class="ag-policy-card" href="publicationethics.php">
              <strong>Publication Ethics</strong>
              <span>Editorial ethics, author and reviewer duties, misconduct handling.</span>
            </a>
            <a class="ag-policy-card" href="peerreviewpolicy.php">
              <strong>Peer Review Policy</strong>
              <span>Double-blind review workflow, timelines, and decision criteria.</span>
            </a>
            <a class="ag-policy-card" href="openaccesscopyrightpolicy.php">
              <strong>Open Access &amp; Copyright</strong>
              <span>CC BY licensing terms, author copyright retention, and reuse rights.</span>
            </a>
            <a class="ag-policy-card" href="plagiarismresearchintegritypolicy.php">
              <strong>Plagiarism &amp; Research Integrity</strong>
              <span>Similarity screening thresholds, duplicate publication, and retraction.</span>
            </a>
            <a class="ag-policy-card" href="complaintsappealspolicy.php">
              <strong>Complaints &amp; Appeals</strong>
              <span>Fair processes for appealing editorial decisions or filing complaints.</span>
            </a>
            <a class="ag-policy-card" href="archivingdigitalpolicy.php">
              <strong>Digital Archiving</strong>
              <span>Long-term archiving, repository preservation, and perpetual access.</span>
            </a>
            <a class="ag-policy-card" href="privacydataprotectionpolicy.php">
              <strong>Privacy &amp; Data Protection</strong>
              <span>Author privacy, GDPR compliance, and confidential data processing.</span>
            </a>
          </div>
        </section>

        <!-- ============================================================
             43. CONTACT
             ============================================================ -->
        <section id="contact">
          <h2><span class="section-num">29.</span> Contact Information</h2>
          <p>For editorial queries, manuscript status enquiries, or technical assistance with submissions, please contact our editorial office:</p>

          <div class="ag-contact-card">
            <div class="ag-contact-grid">
              <div class="ag-contact-col">
                <h4>Editorial Office</h4>
                <p><strong>Dr. M. Praveen Kumar Ph.D.</strong><br>
                Chief Editor, AJSMR<br>
                Synteny Life Sciences Pvt Ltd<br>
                Hyderabad, Telangana State, India<br>
                Email: <a href="mailto:editorajsmr@gmail.com">editorajsmr@gmail.com</a></p>
              </div>

              <div class="ag-contact-col">
                <h4>Publisher</h4>
                <p><strong>Advaitha Innovative Research Association (AIRA)</strong><br>
                # 5-11-717/204, MG Road, Beside MGM Hospital<br>
                Warangal, Telangana, India<br>
                Mobile: +91-7330985744, +91-7989071642<br>
                Email: <a href="mailto:aira@airaacademy.com">aira@airaacademy.com</a><br>
                Website: <a href="http://airaacademy.com/" target="_blank" rel="noopener">www.airaacademy.com</a></p>
              </div>
            </div>
            <p style="margin-top:14px;padding-top:12px;border-top:1px solid #e1e9f0;font-size:13px;color:#5a6e82">
              For complete contact details, directions, and submission inquiries, visit our official <a href="contactus.php"><strong>Contact Us</strong></a> page.
            </p>
          </div>
        </section>

      </article>

      <!-- ============================================================
           MASTER RIGHT SIDEBAR
           ============================================================ -->
      <?php require_once __DIR__ . '/includes/ajsmr_sidebar.php'; ?>

    </div>
  </section>

</main>

<!-- ============================================================
     MASTER FOOTER
     ============================================================ -->
<?php require_once __DIR__ . '/includes/ajsmr_footer.php'; ?>

</body>
</html>