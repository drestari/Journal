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
<title>Publication Ethics &amp; Editorial Policy :: The American Journal of Science and Medical Research (AJSMR)</title>
<meta name="description" content="The American Journal of Science and Medical Research (AJSMR) is a peer-reviewed open-access quarterly journal publishing scholarly research across science and medicine.">
<meta name="robots" content="index,follow">
<link rel="canonical" href="https://ajsmrjournal.com/publicationethics.php">
<link rel="icon" href="images/favicon.ico">
<meta property="og:type" content="website">
<meta property="og:title" content="<?=h($journal)?>">
<meta property="og:description" content="Peer-reviewed open-access research across science and medicine.">
<meta property="og:url" content="https://ajsmrjournal.com/publicationethics.php">
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
      <h1>Publication Ethics &amp; Editorial Policy</h1>
      <p>The American Journal of Science and Medical Research (AJSMR) — policy and editorial framework.</p>
    </div>
  </section>

  <section class="policy-shell">
    <div class="container policy-layout">
      <article class="policy-content">
        <h2>Publication Ethics &amp; Editorial Policy</h2>
<p>The American Journal of Science and Medical Research (AJSMR) follows the Best Practice Guidelines of the Committee on Publication Ethics (COPE), and this policy should be read together with the journal's Peer Review Policy and Author Guidelines.</p>
<h3>Duties of the Editor-in-Chief and Editorial Team</h3>
<ul>
<li><strong>Publication Decisions:</strong> The Editor-in-Chief (Dr. M. Praveen Kumar, Ph.D.) holds final responsibility for deciding which submitted articles are published, guided by the editorial board's policies and applicable legal requirements regarding defamation, copyright infringement, and plagiarism. Decisions are made in consultation with reviewers and editorial board members.</li>
<li><strong>Fair Play:</strong> Manuscripts are evaluated solely on their intellectual and scientific merit, without regard to the authors' race, gender, sexual orientation, religious belief, ethnicity, citizenship, or political views.</li>
<li><strong>Confidentiality:</strong> Editors and editorial staff do not disclose information about a submitted manuscript to anyone other than the corresponding author, reviewers, editorial advisers, and the publisher, as appropriate. The double-blind review process is strictly maintained — reviewer identities are not disclosed to authors, and vice versa.</li>
<li><strong>Conflicts of Interest:</strong> Unpublished material or data disclosed in a submitted manuscript must not be used by an editor, reviewer, or any other party with access to it, for their own research purposes, without the express written consent of the author.</li>
<li><strong>Handling Editors' Own Submissions:</strong> In accordance with COPE recommendations, if an editor is an author on a submitted manuscript, that submission is reassigned to another member of the editorial board or a guest editor to avoid a conflict of interest.</li>
</ul>
<h3>Duties of Authors</h3>
<ul>
<li><strong>Originality:</strong> Authors must submit only original work. Manuscripts must not have been published previously, nor be under consideration for publication elsewhere, in any form or language.</li>
<li><strong>Data Accuracy and Authenticity:</strong> Authors are solely responsible for the authenticity of cited literature and the accuracy of the data reported. Fabrication or falsification of data constitutes serious misconduct.</li>
<li><strong>Authorship:</strong> The corresponding author is responsible for ensuring that all listed co-authors have agreed to the content of the manuscript and its submission to AJSMR.</li>
<li><strong>Research Involving Animals:</strong> Where experiments involving animals have been conducted, authors must include a certification statement confirming that the experiments were carried out in accordance with the guidelines and regulations of the relevant institutional/national committee, consistent with internationally accepted principles of laboratory animal use and care.</li>
<li><strong>Plagiarism:</strong> All submissions may be checked for plagiarism as part of the editorial evaluation process (see the journal's Plagiarism &amp; Research Integrity Policy).</li>
<li><strong>Disclosure:</strong> Authors must disclose any financial or other conflicts of interest that might be construed as influencing the results or interpretation of their manuscript.</li>
</ul>
<h3>Duties of Reviewers</h3>
<ul>
<li><strong>Contribution to Editorial Decisions:</strong> Peer review assists the Editor-in-Chief in making informed publication decisions, and the review process also helps authors improve the quality of their manuscript.</li>
<li><strong>Promptness:</strong> A reviewer who feels unqualified to review a manuscript, or who cannot complete the review within the requested timeframe, should notify the Editor-in-Chief promptly and withdraw from the review process.</li>
<li><strong>Confidentiality:</strong> Manuscripts under review must be treated as confidential documents and must not be shown to, or discussed with, others, except as authorized by the Editor-in-Chief.</li>
<li><strong>Objectivity:</strong> Reviews must be conducted objectively and supported by clear, constructive arguments. Personal criticism of the author is not acceptable. Reviewers should clearly express their views on the manuscript with appropriate supporting evidence.</li>
</ul>
<h3>Research Involving Human Participants</h3>
<p>Studies involving human participants, human data or human tissue must have been approved by an appropriate ethics committee and carried out in accordance with the <a href="https://www.wma.net/policies-post/wma-declaration-of-helsinki/" rel="noopener" target="_blank">Declaration of Helsinki</a>. Authors must state the name of the committee and the approval number, and confirm that informed consent was obtained. Identifying details or images of participants may be published only with their written consent. Clinical trials must be registered in a public trial registry.</p>
<h3>Authorship and Contributorship</h3>
<p>AJSMR follows the <a href="https://www.icmje.org/recommendations/browse/roles-and-responsibilities/defining-the-role-of-authors-and-contributors.html" rel="noopener" target="_blank">ICMJE criteria</a> for authorship. Everyone listed as an author must have made a substantial contribution to the work, helped draft or revise it, approved the final version, and agreed to be accountable for it. Gift, guest and ghost authorship are not acceptable. Authorship disputes are handled using the COPE flowcharts.</p>
<h3>Conflicts of Interest (Competing Interests)</h3>
<ul>
<li><strong>Authors</strong> must declare all financial and non-financial competing interests in the manuscript.</li>
<li><strong>Reviewers</strong> must decline to review if they have a competing interest with the authors, their institution or the work.</li>
<li><strong>Editors</strong> must not handle manuscripts where they have a competing interest, including manuscripts from their own institution, recent collaborators, or family members. Such manuscripts are reassigned to another editor.</li>
</ul>
<h3>Citation Manipulation</h3>
<p>Editors and reviewers must not ask authors to cite their own work, or the journal, unless the citation is scientifically justified. Authors must not include irrelevant citations to inflate the citation counts of any person or journal. Suspected citation manipulation is investigated as misconduct.</p>
<h3>Data Sharing and Reproducibility</h3>
<p>Authors are encouraged to deposit the data supporting their findings in a public repository and must include a data availability statement. Editors may ask to see raw data during review. Image manipulation that changes the meaning of the data is not acceptable.</p>
<h3>Use of Generative AI</h3>
<p>AI tools cannot be credited as authors, because they cannot take responsibility for the work. Authors must disclose any use of generative AI in preparing a manuscript, and remain responsible for its accuracy and originality. Editors and reviewers must not upload manuscripts under review into generative AI tools.</p>
<h3>Editorial Independence</h3>
<p>Editorial decisions are made only on academic grounds by the Editor-in-Chief and the editorial team. The publisher does not influence decisions. AJSMR charges no author fees, so there is no financial incentive to accept manuscripts.</p>
<h3>Handling of Misconduct</h3>
<p>Allegations of research or publication misconduct — including but not limited to plagiarism, data fabrication or falsification, duplicate submission, or undisclosed conflicts of interest — will be investigated by the editorial office in line with COPE guidelines. Depending on the findings, this may result in rejection of the manuscript, a formal correction, retraction of a published article, or notification to the authors' institution.</p>
<p>See also the <a href="plagiarismresearchintegritypolicy.php">Plagiarism, Research Integrity &amp; Retraction Policy</a> and the <a href="complaintsappealspolicy.php">Complaints &amp; Appeals Policy</a>.</p>
<p>For questions regarding this policy, contact the Editorial Office at <a href="mailto:editorajsmr@gmail.com">editorajsmr@gmail.com</a>.</p>
<p class="policy-updated">Last updated: September 2026</p>
      </article>

      <?php require_once __DIR__ . '/includes/ajsmr_sidebar.php'; ?>
    </div>
  </section>
</main>

<?php require_once __DIR__ . '/includes/ajsmr_footer.php'; ?>

</body>
</html>