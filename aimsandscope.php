<?php
declare(strict_types=1);

/*
 * The American Journal of Science and Medical Research (AJSMR)
 * ISSN: 2377-6196
 *
 * Aims and Scope Page
 * Official journal aims, disciplinary scope, contribution categories,
 * interdisciplinary research criteria, and editorial considerations.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

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

$schema = [
    '@context'            => 'https://schema.org',
    '@type'               => 'WebPage',
    'name'                => 'Aims and Scope :: ' . $journal . ' (' . $abbr . ')',
    'description'         => 'Official Aims and Scope of The American Journal of Science and Medical Research (AJSMR), ISSN: 2377-6196. Scope areas, contribution categories, interdisciplinary research, and editorial considerations.',
    'url'                 => 'https://ajsmrjournal.com/aimsandscope.php',
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
<title>Aims and Scope :: <?=h($journal)?> (<?=h($abbr)?>)</title>
<meta name="description" content="Official Aims and Scope of The American Journal of Science and Medical Research (AJSMR), ISSN: 2377-6196. Discover subject areas, accepted contribution types, and editorial considerations.">
<meta name="robots" content="index,follow">
<link rel="canonical" href="aimsandscope.php">
<link rel="icon" href="images/favicon.ico">
<meta property="og:type" content="website">
<meta property="og:title" content="Aims and Scope :: <?=h($journal)?> (<?=h($abbr)?>)">
<meta property="og:description" content="Aims, scope, and manuscript categories for The American Journal of Science and Medical Research (AJSMR), ISSN: 2377-6196.">
<meta property="og:url" content="aimsandscope.php">
<script type="application/ld+json"><?=json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?></script>
<link rel="stylesheet" href="ajsmr-homepage-v5-2.css">

<style>
/* Policy and Informational Page Styling */
.policy-hero {
    background: #f4f7fb;
    border-bottom: 1px solid #dfe7f0;
    padding: 40px 0 28px;
}
.policy-hero .eyebrow {
    letter-spacing: .12em;
    font-size: 12px;
    font-weight: 800;
    color: #55708d;
    text-transform: uppercase;
    display: block;
    margin-bottom: 6px;
}
.policy-hero h1 {
    margin: 0 0 10px;
    color: #102f4d;
    font-size: 35px;
    line-height: 1.2;
    font-weight: 800;
}
.policy-hero p {
    max-width: 860px;
    margin: 0 0 14px;
    color: #53677d;
    font-size: 15px;
    line-height: 1.6;
}
.policy-hero-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}
.policy-hero-badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 12px;
    background: #e7f0f8;
    border: 1px solid #cddde9;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    color: #103c68;
}

.policy-shell {
    background: #f7f9fc;
    padding: 42px 0 65px;
}
.policy-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 300px;
    gap: 30px;
    align-items: start;
}
.policy-content,
.policy-sidebar {
    background: #fff;
    border: 1px solid #e0e7ef;
    border-radius: 12px;
    box-shadow: 0 8px 26px rgba(16, 47, 77, .06);
}
.policy-content {
    padding: 38px 44px 46px;
}

/* Breadcrumb */
.page-breadcrumb {
    margin-bottom: 20px;
    color: #6b7280;
    font-size: 13.5px;
}
.page-breadcrumb a {
    color: #174a7c;
    text-decoration: none;
}
.page-breadcrumb a:hover {
    text-decoration: underline;
}

/* Typography */
.policy-content h2 {
    margin: 36px 0 16px;
    padding-bottom: 9px;
    border-bottom: 2px solid #eef3f8;
    color: #102f4d;
    font-size: 24px;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 10px;
}
.policy-content h2:first-of-type {
    margin-top: 10px;
}
.policy-content h2 .section-indicator {
    color: #075fa8;
    font-size: 18px;
    font-weight: 800;
}
.policy-content h3 {
    margin: 24px 0 10px;
    color: #14446c;
    font-size: 18px;
    font-weight: 700;
}
.policy-content p {
    font-size: 15px;
    line-height: 1.75;
    color: #394c60;
    margin: 0 0 16px;
    text-align: justify;
}
.policy-content ul,
.policy-content ol {
    margin: 10px 0 20px 22px;
    padding-left: 10px;
}
.policy-content li {
    font-size: 15px;
    line-height: 1.7;
    color: #394c60;
    margin-bottom: 8px;
}
.policy-content a {
    color: #075fa8;
    text-decoration: underline;
    text-underline-offset: 2px;
}
.policy-content a:hover {
    color: #102f4d;
}

/* Journal Identity Card */
.identity-highlight-card {
    background: #f4f8fd;
    border: 1px solid #d3e2ef;
    border-left: 4px solid #075fa8;
    border-radius: 0 8px 8px 0;
    padding: 16px 20px;
    margin: 0 0 26px;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}
.identity-highlight-card .journal-name {
    font-size: 16px;
    font-weight: 800;
    color: #103c68;
}
.identity-highlight-card .journal-meta {
    font-size: 13.5px;
    color: #4a6176;
}

/* Scope Areas Grid */
.scope-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 16px;
    margin: 22px 0 32px;
}
.scope-card {
    background: #fbfdff;
    border: 1px solid #e1ebf5;
    border-radius: 8px;
    padding: 18px 20px;
    transition: all .2s ease;
    display: flex;
    flex-direction: column;
}
.scope-card:hover {
    border-color: #075fa8;
    box-shadow: 0 6px 18px rgba(7, 95, 168, .09);
    transform: translateY(-2px);
    background: #ffffff;
}
.scope-card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}
.scope-badge {
    background: #e8f2fa;
    color: #075fa8;
    font-size: 11.5px;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 10px;
    letter-spacing: .03em;
    flex-shrink: 0;
}
.scope-card h3 {
    margin: 0;
    font-size: 15.5px;
    color: #123d63;
    line-height: 1.35;
    font-weight: 700;
}
.scope-card p {
    margin: 0;
    font-size: 13.5px;
    line-height: 1.6;
    color: #4b5e71;
    text-align: left;
    flex: 1;
}

/* Contribution Types Section */
.contributions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
    margin: 18px 0 24px;
}
.contribution-item {
    background: #f8fafc;
    border: 1px solid #e2eaf1;
    border-radius: 8px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 600;
    color: #1a3959;
}
.contribution-bullet {
    color: #075fa8;
    font-size: 14px;
    line-height: 1;
}

/* Callout / Information Boxes */
.info-box {
    background: #f2f7fc;
    border-left: 4px solid #075fa8;
    padding: 16px 20px;
    border-radius: 0 8px 8px 0;
    margin: 20px 0 26px;
}
.info-box p {
    margin: 0;
    font-size: 14px;
    line-height: 1.65;
    color: #28435d;
    text-align: left;
}
.info-box strong {
    color: #0b3c67;
}

.warning-box {
    background: #fffbf3;
    border: 1px solid #f6e2b9;
    border-left: 4px solid #e28712;
    padding: 16px 20px;
    border-radius: 0 8px 8px 0;
    margin: 20px 0 26px;
}
.warning-box p {
    margin: 0;
    font-size: 14px;
    line-height: 1.65;
    color: #5d461e;
    text-align: left;
}
.warning-box strong {
    color: #4a340e;
}

/* Sidebar consistency */
.policy-sidebar {
    padding: 24px;
    position: sticky;
    top: 70px;
}
.policy-sidebar h3 {
    margin: 0 0 15px;
    color: #123d63;
    font-size: 19px;
    border-bottom: 2px solid #eef3f8;
    padding-bottom: 10px;
}
.policy-sidebar ul {
    list-style: none;
    margin: 0;
    padding: 0;
}
.policy-sidebar li {
    padding: 8px 0;
    border-bottom: 1px solid #edf1f5;
    color: #536579;
    font-size: 13px;
    line-height: 1.45;
}
.policy-sidebar li:last-child {
    border-bottom: 0;
}
.policy-sidebar strong {
    color: #263f58;
}
.policy-nav {
    margin-top: 22px;
    padding-top: 18px;
    border-top: 1px solid #e5ebf1;
}
.policy-nav-title {
    font-size: 13px;
    font-weight: 800;
    color: #123d63;
    margin-bottom: 8px;
}
.policy-nav a {
    display: block;
    padding: 7px 8px;
    border-radius: 6px;
    color: #275d82;
    text-decoration: none;
    font-size: 13px;
}
.policy-nav a:hover {
    background: #eef5fb;
}

.policy-updated {
    margin-top: 34px;
    padding-top: 16px;
    border-top: 1px solid #e1e7ee;
    color: #6b7280;
    font-size: 13.5px;
}

/* Responsive adjustments */
@media (max-width: 960px) {
    .policy-layout {
        grid-template-columns: 1fr;
    }
    .policy-sidebar {
        position: static;
        margin-top: 24px;
    }
    .policy-content {
        padding: 28px 24px 34px;
    }
    .policy-hero h1 {
        font-size: 30px;
    }
}
@media (max-width: 640px) {
    .policy-content {
        padding: 22px 18px 28px;
    }
    .policy-hero h1 {
        font-size: 26px;
    }
    .policy-hero {
        padding: 30px 0 20px;
    }
    .scope-grid {
        grid-template-columns: 1fr;
    }
    .contributions-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>
<a class="skip" href="#main">Skip to main content</a>

<?php
$ajsmr_active_nav = 'journal';
require_once __DIR__ . '/includes/ajsmr_header.php';
?>

<main id="main">

  <!-- ============================================================
       AIMS & SCOPE HERO SECTION
       ============================================================ -->
  <section class="policy-hero">
    <div class="container">
      <span class="eyebrow">AJSMR Journal Information</span>
      <h1>Aims and Scope</h1>
      <p>Official editorial aims, thematic scope areas, accepted contribution categories, and peer review standards for <strong>The American Journal of Science and Medical Research (AJSMR)</strong>.</p>
      <div class="policy-hero-badges">
        <span class="policy-hero-badge">▥ ISSN: <?=h($issn)?></span>
        <span class="policy-hero-badge">♙ Open Access</span>
        <span class="policy-hero-badge">⚖ Double-Blind Peer Review</span>
        <span class="policy-hero-badge">▣ <?=h($frequency)?> Publication</span>
      </div>
    </div>
  </section>

  <!-- ============================================================
       MAIN CONTENT AND SIDEBAR SHELL
       ============================================================ -->
  <section class="policy-shell">
    <div class="container policy-layout">

      <article class="policy-content">

        <div class="page-breadcrumb">
          <a href="index_ajsmr_v5_8.php">Home</a>
          <span> &nbsp;›&nbsp; </span>
          <a href="aboutjournal.php">About Journal</a>
          <span> &nbsp;›&nbsp; </span>
          <span>Aims and Scope</span>
        </div>

        <div class="identity-highlight-card">
          <div class="journal-name">The American Journal of Science and Medical Research (AJSMR)</div>
          <div class="journal-meta"><strong>ISSN:</strong> 2377-6196 &nbsp; | &nbsp; <strong>Publisher:</strong> Advaitha Innovative Research Association (AIRA)</div>
        </div>

        <!-- Section A: Aims -->
        <h2><span class="section-indicator">A.</span> Journal Aims</h2>

        <p>The American Journal of Science and Medical Research (AJSMR) aims to promote scientific advancement and interdisciplinary innovation across medical, biomedical, pharmaceutical, biological, and computational sciences. The journal provides a platform for communicating original research, methodological developments, analytical approaches, computational tools, and technologies that advance biomedical research and contribute to the understanding, diagnosis, prevention, and treatment of disease.</p>

        <p>AJSMR welcomes fundamental and applied research that strengthens scientific knowledge, supports reproducible research, encourages collaboration across disciplines, and facilitates the translation of scientific discoveries into biomedical and healthcare applications.</p>

        <p>The journal encourages research involving experimental investigations, computational analysis, bioinformatics, artificial intelligence, machine learning, data science, drug discovery, disease mechanisms, therapeutic targets, and emerging methods relevant to medical and life sciences.</p>

        <!-- Section B: Scope -->
        <h2><span class="section-indicator">B.</span> Scope of the Journal</h2>

        <p>AJSMR invites scholarly submissions across the following core scientific, medical, and computational domains:</p>

        <div class="scope-grid">

          <div class="scope-card">
            <div class="scope-card-header">
              <span class="scope-badge">Area 01</span>
              <h3>Medical and Biomedical Sciences</h3>
            </div>
            <p>Basic and applied research in medical science, human biology, disease mechanisms, diagnosis, prevention, and treatment.</p>
          </div>

          <div class="scope-card">
            <div class="scope-card-header">
              <span class="scope-badge">Area 02</span>
              <h3>Biomedical Informatics and Computational Sciences</h3>
            </div>
            <p>Biomedical data analysis, bioinformatics, computational biology, medical informatics, modelling, simulation, and scientific computing.</p>
          </div>

          <div class="scope-card">
            <div class="scope-card-header">
              <span class="scope-badge">Area 03</span>
              <h3>Artificial Intelligence and Machine Learning</h3>
            </div>
            <p>AI/ML applications in biomedical research, medical data interpretation, disease prediction, diagnostic support, and therapeutic research.</p>
          </div>

          <div class="scope-card">
            <div class="scope-card-header">
              <span class="scope-badge">Area 04</span>
              <h3>Drug Discovery and Pharmaceutical Sciences</h3>
            </div>
            <p>Drug design, molecular docking, virtual screening, pharmacoinformatics, pharmaceutical development, drug delivery, and therapeutic evaluation.</p>
          </div>

          <div class="scope-card">
            <div class="scope-card-header">
              <span class="scope-badge">Area 05</span>
              <h3>Molecular Biology, Genetics and Genomics</h3>
            </div>
            <p>Gene regulation, molecular mechanisms, genomics, transcriptomics, proteomics, and related omics research.</p>
          </div>

          <div class="scope-card">
            <div class="scope-card-header">
              <span class="scope-badge">Area 06</span>
              <h3>Microbiology and Infectious Diseases</h3>
            </div>
            <p>Bacteriology, virology, mycology, parasitology, microbial pathogenesis, antimicrobial research, and infectious disease biology.</p>
          </div>

          <div class="scope-card">
            <div class="scope-card-header">
              <span class="scope-badge">Area 07</span>
              <h3>Immunology and Cancer Research</h3>
            </div>
            <p>Immunological mechanisms, immunotherapy, cancer biology, tumour biomarkers, and experimental or computational oncology.</p>
          </div>

          <div class="scope-card">
            <div class="scope-card-header">
              <span class="scope-badge">Area 08</span>
              <h3>Pharmacology and Toxicology</h3>
            </div>
            <p>Drug action, pharmacokinetics, pharmacodynamics, safety assessment, toxicological mechanisms, and experimental pharmacology.</p>
          </div>

          <div class="scope-card">
            <div class="scope-card-header">
              <span class="scope-badge">Area 09</span>
              <h3>Biotechnology and Biological Sciences</h3>
            </div>
            <p>Biotechnology, cellular biology, biochemistry, biological engineering, and related life-science research.</p>
          </div>

          <div class="scope-card">
            <div class="scope-card-header">
              <span class="scope-badge">Area 10</span>
              <h3>Public Health and Epidemiology</h3>
            </div>
            <p>Disease surveillance, epidemiological methods, population health, prevention strategies, and evidence-based public health research.</p>
          </div>

          <div class="scope-card">
            <div class="scope-card-header">
              <span class="scope-badge">Area 11</span>
              <h3>Neuroscience and Behavioral Sciences</h3>
            </div>
            <p>Neural systems, neurobiology, neuropharmacology, cognitive and behavioral research, and neurological disorders.</p>
          </div>

          <div class="scope-card">
            <div class="scope-card-header">
              <span class="scope-badge">Area 12</span>
              <h3>Clinical and Translational Research</h3>
            </div>
            <p>Research connecting laboratory findings, clinical investigations, diagnostic approaches, and potential healthcare applications.</p>
          </div>

          <div class="scope-card">
            <div class="scope-card-header">
              <span class="scope-badge">Area 13</span>
              <h3>Environmental, Nutritional and Metabolic Sciences</h3>
            </div>
            <p>Environmental health, nutrition, metabolism, metabolic disorders, and interactions between biological systems and environmental factors.</p>
          </div>

          <div class="scope-card">
            <div class="scope-card-header">
              <span class="scope-badge">Area 14</span>
              <h3>Medical Technology and Research Methodology</h3>
            </div>
            <p>Biomedical instrumentation, diagnostic technologies, laboratory methods, analytical validation, and improvements in research methodology.</p>
          </div>

          <div class="scope-card">
            <div class="scope-card-header">
              <span class="scope-badge">Area 15</span>
              <h3>Natural Products and Integrative Biomedical Research</h3>
            </div>
            <p>Pharmacological evaluation of natural products, medicinal plants, phytochemicals, and evidence-based studies of bioactive compounds.</p>
          </div>

          <div class="scope-card">
            <div class="scope-card-header">
              <span class="scope-badge">Area 16</span>
              <h3>Scientific Software, Data and Reproducible Research</h3>
            </div>
            <p>Development and application of scientific software, validated computational workflows, research datasets, methodological standards, and reproducible analytical tools relevant to biomedicine.</p>
          </div>

        </div>

        <!-- Section C: Types of Contributions -->
        <h2><span class="section-indicator">C.</span> Types of Contributions</h2>

        <p>AJSMR accepts the following categories of scholarly manuscripts, subject to the journal's editorial policies and peer review standards:</p>

        <div class="contributions-grid">
          <div class="contribution-item"><span class="contribution-bullet">✦</span> Original Research Articles</div>
          <div class="contribution-item"><span class="contribution-bullet">✦</span> Review Articles</div>
          <div class="contribution-item"><span class="contribution-bullet">✦</span> Systematic Reviews and Meta-Analyses</div>
          <div class="contribution-item"><span class="contribution-bullet">✦</span> Short Communications</div>
          <div class="contribution-item"><span class="contribution-bullet">✦</span> Methodological and Technical Articles</div>
          <div class="contribution-item"><span class="contribution-bullet">✦</span> Brief Reports</div>
          <div class="contribution-item"><span class="contribution-bullet">✦</span> Case Reports or Case Studies (where appropriate to journal policies)</div>
          <div class="contribution-item"><span class="contribution-bullet">✦</span> Other suitable scholarly contributions approved by the editorial office</div>
        </div>

        <div class="info-box">
          <p><strong>Note on Manuscript Categories:</strong> Acceptance of any manuscript is not unconditional. All submissions must conform to the journal's manuscript formatting requirements, undergo initial editorial triage, and meet the specific criteria outlined in our <a href="authorguidelines.php">Author Guidelines</a> and <a href="peerreviewpolicy.php">Peer Review Policy</a>.</p>
        </div>

        <!-- Section D: Interdisciplinary Research -->
        <h2><span class="section-indicator">D.</span> Interdisciplinary Research</h2>

        <p>AJSMR actively encourages and welcomes scientifically sound contributions combining experimental biology, clinical science, computational methods, pharmaceutical research, engineering, and data-driven analysis, where relevant to the journal's scope. The journal values cross-disciplinary collaborations that bridge theoretical innovation and practical healthcare or biomedical implementations.</p>

        <!-- Section E: Editorial Considerations -->
        <h2><span class="section-indicator">E.</span> Editorial Considerations</h2>

        <p>All submitted manuscripts must demonstrate appropriate methodology, clear reporting, valid statistical analysis, and conclusions supported by the experimental or computational findings. Computational and software-based studies must provide sufficient methodological detail, source code accessibility, and workflow documentation to facilitate independent reproducibility.</p>

        <p>Research involving human participants, human biological samples, or animals must strictly comply with applicable ethical guidelines and regulatory requirements, including institutional review board approval and informed consent (see <a href="publicationethics.php">Publication Ethics &amp; Editorial Policy</a>).</p>

        <div class="warning-box">
          <p><strong>Editorial Assessment &amp; Peer Review:</strong> All manuscripts submitted to AJSMR are subject to rigorous editorial assessment, similarity screening, and independent double-blind peer review according to the journal's applicable policies. The journal does not guarantee acceptance or publication timelines for any submission.</p>
        </div>

        <p class="policy-updated">Last updated: September 2026</p>

      </article>

      <!-- Shared Sidebar Component -->
      <?php require_once __DIR__ . '/includes/ajsmr_sidebar.php'; ?>

    </div>
  </section>

</main>

<!-- Shared Footer Component -->
<?php require_once __DIR__ . '/includes/ajsmr_footer.php'; ?>

</body>
</html>
