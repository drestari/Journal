<?php
declare(strict_types=1);

require_once __DIR__ . '/article_common.php';

/*
 * AJSMR Article Page — Enhanced V2
 * Adds tabbed About this Article / How to Cite / References section
 * immediately after the abstract, following the supplied model layout.
 */

function article_get_by_request(): ?array {
    $id = trim((string)($_GET['id'] ?? ''));
    if ($id === '') return null;

    $pdo = db();

    if (ctype_digit($id)) {
        $st = $pdo->prepare('SELECT * FROM articles WHERE id=? AND status IN ("PUBLISHED","UPDATED") LIMIT 1');
        $st->execute([(int)$id]);
    } else {
        $st = $pdo->prepare('SELECT * FROM articles WHERE article_id=? AND status IN ("PUBLISHED","UPDATED") LIMIT 1');
        $st->execute([$id]);
    }

    $row = $st->fetch();
    return $row ?: null;
}

function article_authors_list(int $articleId): array {
    $st = db()->prepare(
        'SELECT * FROM article_authors WHERE article_id=? ORDER BY author_order ASC, id ASC'
    );
    $st->execute([$articleId]);
    return $st->fetchAll() ?: [];
}

function article_references_list(int $articleId): array {
    $st = db()->prepare(
        'SELECT * FROM article_references WHERE article_id=? ORDER BY reference_order ASC, id ASC'
    );
    $st->execute([$articleId]);
    return $st->fetchAll() ?: [];
}

function article_file_list(int $articleId): array {
    $st = db()->prepare(
        'SELECT * FROM article_files WHERE article_id=? ORDER BY id ASC'
    );
    $st->execute([$articleId]);
    return $st->fetchAll() ?: [];
}

function clean_author_name(string $name): string {
    return trim(preg_replace('/\s+/', ' ', strip_tags($name)));
}

function initials_from_name(string $name): string {
    $name = trim(preg_replace('/\s+/', ' ', $name));
    if ($name === '') return '';

    $parts = preg_split('/\s+/', $name);
    if (!$parts) return '';

    $last = array_pop($parts);
    $initials = '';

    foreach ($parts as $p) {
        $initials .= mb_strtoupper(mb_substr($p, 0, 1)) . '. ';
    }

    return trim($initials) . ($initials !== '' ? ' ' : '') . $last;
}

function author_apa(string $name): string {
    $name = clean_author_name($name);
    $parts = preg_split('/\s+/', $name);
    if (!$parts || count($parts) === 1) return $name;

    $last = array_pop($parts);
    $initials = '';

    foreach ($parts as $p) {
        $initials .= mb_strtoupper(mb_substr($p, 0, 1)) . '. ';
    }

    return $last . ', ' . trim($initials);
}

function author_vancouver(string $name): string {
    $name = clean_author_name($name);
    $parts = preg_split('/\s+/', $name);
    if (!$parts || count($parts) === 1) return $name;

    $last = array_shift($parts);
    $initials = '';

    foreach ($parts as $p) {
        $initials .= mb_strtoupper(mb_substr($p, 0, 1));
    }

    return $last . ' ' . $initials;
}

function article_author_names(array $authors): array {
    $names = [];
    foreach ($authors as $a) {
        $n = trim((string)($a['author_name'] ?? ''));
        if ($n !== '') $names[] = $n;
    }
    return $names;
}

function article_join_authors(array $items): string {
    $items = array_values(array_filter(array_map('trim', $items)));
    if (!$items) return '';
    if (count($items) === 1) return $items[0];
    if (count($items) === 2) return $items[0] . ' & ' . $items[1];

    $last = array_pop($items);
    return implode(', ', $items) . ', & ' . $last;
}

function article_year(array $a): string {
    $y = trim((string)($a['year'] ?? ''));
    if ($y !== '') return $y;

    $d = trim((string)($a['published_date'] ?? ''));
    if ($d !== '' && $d !== '0000-00-00') return date('Y', strtotime($d));

    return date('Y');
}

function article_pages(array $a): string {
    $start = trim((string)($a['page_start'] ?? ''));
    $end = trim((string)($a['page_end'] ?? ''));
    if ($start !== '' && $end !== '') return $start . '–' . $end;
    return $start !== '' ? $start : '';
}

function article_volume_issue(array $a): string {
    $v = trim((string)($a['volume'] ?? ''));
    $i = trim((string)($a['issue'] ?? ''));

    if ($v !== '' && $i !== '') return 'Vol. ' . $v . ' No. ' . $i;
    if ($v !== '') return 'Vol. ' . $v;
    if ($i !== '') return 'No. ' . $i;
    return '';
}

function article_citation_apa(array $a, array $authors): string {
    $names = [];
    foreach ($authors as $author) {
        $n = trim((string)($author['author_name'] ?? ''));
        if ($n !== '') $names[] = author_apa($n);
    }

    $authorText = article_join_authors($names);
    $year = article_year($a);
    $title = trim((string)$a['title']);
    $journal = 'The American Journal of Science and Medical Research';
    $volume = trim((string)($a['volume'] ?? ''));
    $issue = trim((string)($a['issue'] ?? ''));
    $pages = article_pages($a);
    $doi = trim((string)($a['doi'] ?? ''));

    $citation = $authorText !== '' ? $authorText . ' (' . $year . '). ' : '(' . $year . '). ';
    $citation .= $title . '. ' . $journal;

    if ($volume !== '') $citation .= ', ' . $volume;
    if ($issue !== '') $citation .= '(' . $issue . ')';
    if ($pages !== '') $citation .= ', ' . $pages;
    $citation .= '.';

    if ($doi !== '') {
        $doiUrl = preg_match('~^https?://~i', $doi) ? $doi : 'https://doi.org/' . ltrim($doi, '/');
        $citation .= ' ' . $doiUrl;
    }

    return $citation;
}

function article_citation_vancouver(array $a, array $authors): string {
    $names = [];
    foreach ($authors as $author) {
        $n = trim((string)($author['author_name'] ?? ''));
        if ($n !== '') $names[] = author_vancouver($n);
    }

    $authorText = implode(', ', $names);
    $citation = $authorText !== '' ? $authorText . '. ' : '';

    $citation .= trim((string)$a['title']) . '. ';
    $citation .= 'Am J Sci Med Res. ' . article_year($a);

    $volume = trim((string)($a['volume'] ?? ''));
    $issue = trim((string)($a['issue'] ?? ''));
    $pages = article_pages($a);

    if ($volume !== '') {
        $citation .= ';' . $volume;
        if ($issue !== '') $citation .= '(' . $issue . ')';
    }
    if ($pages !== '') $citation .= ':' . $pages;
    $citation .= '.';

    $doi = trim((string)($a['doi'] ?? ''));
    if ($doi !== '') {
        $doi = preg_replace('~^https?://(dx\.)?doi\.org/~i', '', $doi);
        $citation .= ' doi:' . $doi;
    }

    return $citation;
}

function article_citation_harvard(array $a, array $authors): string {
    $names = [];
    foreach ($authors as $author) {
        $n = trim((string)($author['author_name'] ?? ''));
        if ($n !== '') $names[] = author_apa($n);
    }

    $authorText = article_join_authors($names);
    $year = article_year($a);
    $citation = ($authorText !== '' ? $authorText : 'Unknown author') . ' ' . $year . ', ';
    $citation .= '\'' . trim((string)$a['title']) . '\', ';
    $citation .= '<i>Am J Sci Med Res</i>';

    $volume = trim((string)($a['volume'] ?? ''));
    $issue = trim((string)($a['issue'] ?? ''));
    $pages = article_pages($a);

    if ($volume !== '') $citation .= ', ' . $volume;
    if ($issue !== '') $citation .= '(' . $issue . ')';
    if ($pages !== '') $citation .= ', pp. ' . $pages;
    $citation .= '.';

    $doi = trim((string)($a['doi'] ?? ''));
    if ($doi !== '') {
        $doiUrl = preg_match('~^https?://~i', $doi) ? $doi : 'https://doi.org/' . ltrim($doi, '/');
        $citation .= ' Available at: ' . $doiUrl;
    }

    return $citation;
}

function citation_ris(array $a, array $authors): string {
    $lines = [
        'TY  - JOUR',
        'TI  - ' . trim((string)$a['title']),
        'JO  - The American Journal of Science and Medical Research',
        'JF  - Am J Sci Med Res',
        'PY  - ' . article_year($a)
    ];

    foreach ($authors as $author) {
        $n = trim((string)($author['author_name'] ?? ''));
        if ($n !== '') $lines[] = 'AU  - ' . $n;
    }

    if (trim((string)($a['volume'] ?? '')) !== '') {
        $lines[] = 'VL  - ' . trim((string)$a['volume']);
    }
    if (trim((string)($a['issue'] ?? '')) !== '') {
        $lines[] = 'IS  - ' . trim((string)$a['issue']);
    }
    if (trim((string)($a['page_start'] ?? '')) !== '') {
        $lines[] = 'SP  - ' . trim((string)$a['page_start']);
    }
    if (trim((string)($a['page_end'] ?? '')) !== '') {
        $lines[] = 'EP  - ' . trim((string)$a['page_end']);
    }
    if (trim((string)($a['doi'] ?? '')) !== '') {
        $lines[] = 'DO  - ' . preg_replace('~^https?://(dx\.)?doi\.org/~i', '', trim((string)$a['doi']));
    }

    $lines[] = 'ER  -';
    return implode("\r\n", $lines) . "\r\n";
}

function citation_bibtex(array $a, array $authors): string {
    $key = 'AJSMR' . article_year($a) . preg_replace('/[^A-Za-z0-9]/', '', (string)($a['article_id'] ?? 'Article'));
    $lines = [
        '@article{' . $key . ',',
        '  title = {' . str_replace(['{','}'], ['',''], trim((string)$a['title'])) . '},'
    ];

    $authorNames = [];
    foreach ($authors as $author) {
        $n = trim((string)($author['author_name'] ?? ''));
        if ($n !== '') $authorNames[] = $n;
    }

    if ($authorNames) $lines[] = '  author = {' . implode(' and ', $authorNames) . '},';
    $lines[] = '  journal = {The American Journal of Science and Medical Research},';
    $lines[] = '  year = {' . article_year($a) . '},';

    if (trim((string)($a['volume'] ?? '')) !== '') {
        $lines[] = '  volume = {' . trim((string)$a['volume']) . '},';
    }
    if (trim((string)($a['issue'] ?? '')) !== '') {
        $lines[] = '  number = {' . trim((string)$a['issue']) . '},';
    }
    if (article_pages($a) !== '') {
        $lines[] = '  pages = {' . article_pages($a) . '},';
    }
    if (trim((string)($a['doi'] ?? '')) !== '') {
        $lines[] = '  doi = {' . preg_replace('~^https?://(dx\.)?doi\.org/~i', '', trim((string)$a['doi'])) . '},';
    }

    $last = array_pop($lines);
    $lines[] = rtrim($last, ',');
    $lines[] = '}';
    return implode("\n", $lines) . "\n";
}

/* Citation download endpoints */
$format = strtolower(trim((string)($_GET['format'] ?? '')));
if ($format !== '') {
    $article = article_get_by_request();
    if (!$article) {
        http_response_code(404);
        exit('Article not found.');
    }

    $authors = article_authors_list((int)$article['id']);

    if ($format === 'ris') {
        header('Content-Type: application/x-research-info-systems; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_-]/', '_', $article['article_id']) . '.ris"');
        echo citation_ris($article, $authors);
        exit;
    }

    if ($format === 'bibtex') {
        header('Content-Type: application/x-bibtex; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_-]/', '_', $article['article_id']) . '.bib"');
        echo citation_bibtex($article, $authors);
        exit;
    }
}

$article = article_get_by_request();

if (!$article) {
    http_response_code(404);
    exit('<div style="font-family:Arial;padding:50px"><h1>Article not found</h1><p>The requested article is not available.</p></div>');
}

$authors = article_authors_list((int)$article['id']);
$references = article_references_list((int)$article['id']);
$files = article_file_list((int)$article['id']);

$authorNames = article_author_names($authors);
$authorText = implode(', ', $authorNames);
$year = article_year($article);
$volume = trim((string)($article['volume'] ?? ''));
$issue = trim((string)($article['issue'] ?? ''));
$pages = article_pages($article);
$doi = trim((string)($article['doi'] ?? ''));
$doiUrl = $doi !== '' ? (preg_match('~^https?://~i', $doi) ? $doi : 'https://doi.org/' . ltrim($doi, '/')) : '';

$apa = article_citation_apa($article, $authors);
$vancouver = article_citation_vancouver($article, $authors);
$harvard = article_citation_harvard($article, $authors);

$pdf = trim((string)($article['pdf_file'] ?? ''));
if ($pdf === '') {
    foreach ($files as $f) {
        if (strtolower((string)($f['file_type'] ?? '')) === 'pdf') {
            $pdf = trim((string)($f['stored_path'] ?? ''));
            break;
        }
    }
}

function article_public_path(string $path): string {
    $path = trim(str_replace('\\', '/', $path));
    if ($path === '') return '';
    if (preg_match('~^https?://~i', $path)) return $path;
    while (str_starts_with($path, '../')) $path = substr($path, 3);
    while (str_starts_with($path, './')) $path = substr($path, 2);
    return ltrim($path, '/');
}

$keywords = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', (string)($article['keywords'] ?? '')))));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=article_h($article['title'])?> | AJSMR</title>
<meta name="description" content="<?=article_h(mb_substr(trim(strip_tags((string)$article['abstract'])),0,300))?>">
<meta name="citation_title" content="<?=article_h($article['title'])?>">
<meta name="citation_issn" content="2377-6196">
<?php foreach ($authors as $a): ?><meta name="citation_author" content="<?=article_h($a['author_name'])?>"><?php endforeach; ?>
<?php if($volume!==''): ?><meta name="citation_volume" content="<?=article_h($volume)?>"><?php endif; ?>
<?php if($issue!==''): ?><meta name="citation_issue" content="<?=article_h($issue)?>"><?php endif; ?>
<?php if($doi!==''): ?><meta name="citation_doi" content="<?=article_h($doi)?>"><?php endif; ?>
<?php if($pdf!==''): ?><meta name="citation_pdf_url" content="<?=article_h(article_public_path($pdf))?>"><?php endif; ?>

<link rel="icon" href="../images/favicon.ico">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Playfair+Display:ital,wght@1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../ajsmr-homepage-v5-2.css">
<style>
*{box-sizing:border-box}
body{margin:0;background:#f6f8fb;color:#263238;font-family:Arial,Helvetica,sans-serif;line-height:1.65}
a{color:#0b5fa5}
.wrap{max-width:1240px;margin:32px auto;padding:0 20px}
.article-layout{display:grid;grid-template-columns:minmax(0,1fr) 310px;gap:28px;align-items:start}
.article-card{background:#fff;border:1px solid #e0e7ef;border-radius:12px;padding:34px 40px;box-shadow:0 8px 26px rgba(16,47,77,.06)}
.article-type{
    display:inline-block;
    background:#0b5fa5;
    color:#ffffff;
    font-size:12px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.05em;
    padding:5px 10px;
    border-radius:3px;
    margin-bottom:10px;
}
h1{font-size:30px;line-height:1.28;margin:0 0 18px;color:#182b49}
.authors{font-size:15px;margin-bottom:9px}
.affiliations{font-size:13px;color:#667085;margin-bottom:18px}
.meta{font-size:13px;color:#667085;margin:8px 0}
.meta strong{color:#344054}
.abstract-title{font-size:20px;margin:30px 0 8px;color:#183b67}
.abstract{font-size:14px;text-align:justify}
.keywords{display:flex;flex-wrap:wrap;gap:8px;margin-top:18px}
.keyword{background:#edf0f2;border-radius:18px;padding:5px 13px;font-size:12px;font-weight:700;color:#344054}
.tab-box{margin-top:30px;border:1px solid #dfe5ec;background:#fff;border-radius:8px;overflow:hidden}
.tabs{display:flex;border-bottom:1px solid #d7dee7;padding-left:0;flex-wrap:wrap;background:#f8fafc}
.tab{border:0;border-right:1px solid #d7dee7;background:#f8fafc;padding:12px 18px;color:#2b5f8d;font-size:14px;cursor:pointer}
.tab.active{background:#fff;color:#172b4d;font-weight:700;border-top:2px solid #0b5fa5;margin-top:-1px}
.tab-panel{display:none;padding:24px 20px}
.tab-panel.active{display:block}
.panel-title{font-size:18px;font-weight:700;color:#173a64;margin:0 0 12px}
.info-table{width:100%;border-collapse:collapse;font-size:13px}
.info-table th,.info-table td{padding:9px 5px;border-bottom:1px solid #edf0f4;text-align:left;vertical-align:top}
.info-table th{width:145px;color:#344054}
.citation-box{border:1px solid #d8dee8;border-radius:4px;margin:14px 0;background:#fff}
.citation-head{padding:10px 13px;background:#f8fafc;border-bottom:1px solid #e4e8ed;font-weight:700;color:#315b80}
.citation-text{padding:14px;font-size:13px;line-height:1.7}
.citation-actions{padding:9px 13px;border-top:1px solid #edf0f4}
.small-btn{display:inline-block;border:1px solid #b9c8d8;background:#fff;color:#0b5fa5;border-radius:4px;padding:6px 10px;font-size:12px;cursor:pointer;text-decoration:none;margin-right:6px}
.downloads{margin-top:16px}
.ref-list{max-height:520px;overflow-y:auto;padding-right:12px}
.ref{font-size:13px;margin:0 0 15px;padding-left:3px}
.files{display:flex;flex-wrap:wrap;gap:9px;margin-top:15px}
.file-btn{display:inline-block;background:#0b5fa5;color:#fff;text-decoration:none;border-radius:4px;padding:8px 12px;font-size:12px;font-weight:700}
.policy-sidebar{background:#fff;border:1px solid #e0e7ef;border-radius:12px;box-shadow:0 8px 26px rgba(16,47,77,.06);padding:24px;position:sticky;top:20px}
.policy-sidebar h3{margin:0 0 15px;color:#123d63;font-size:19px;border-bottom:2px solid #eef3f8;padding-bottom:10px}
.policy-sidebar ul{list-style:none;margin:0;padding:0}
.policy-sidebar li{padding:8px 0;border-bottom:1px solid #edf1f5;color:#536579;font-size:13px;line-height:1.45}
.policy-sidebar li:last-child{border-bottom:0}
.policy-sidebar strong{color:#263f58}
.sidebar-pdf-btn{display:flex;align-items:center;justify-content:center;gap:8px;background:#0b5fa5;color:#fff;text-decoration:none;border-radius:6px;padding:11px 14px;font-size:13px;font-weight:700;box-shadow:0 2px 8px rgba(11,95,165,.25);transition:background .15s ease}
.sidebar-pdf-btn:hover{background:#084980;color:#fff}
.policy-nav{margin-top:22px;padding-top:18px;border-top:1px solid #e5ebf1}
.policy-nav-title{font-size:13px;font-weight:800;color:#123d63;margin-bottom:8px}
.policy-nav a{display:block;padding:7px 8px;border-radius:6px;color:#275d82;text-decoration:none;font-size:13px}
.policy-nav a:hover{background:#eef5fb}
.footer{margin-top:35px;background:#092b5f;color:#dbeafe;padding:28px 20px;text-align:center;font-size:12px}
.notice{padding:12px;background:#f8fafc;border-left:3px solid #0b5fa5;font-size:13px}
@media(max-width:960px){
 .article-layout{grid-template-columns:1fr}
 .policy-sidebar{position:static}
 .article-card{padding:26px 20px}
 h1{font-size:25px}
}
</style>
</head>
<body>

<a class="skip" href="#main">Skip to main content</a>

<?php
$ajsmr_active_nav = 'articles';
$ajsmr_root = '../';
require_once __DIR__ . '/../includes/ajsmr_header.php';
?>

<main id="main" class="wrap">
<div class="article-layout">
<article class="article-card">

<div class="article-type"><?=article_h($article['article_type'] ?: 'Research Article')?></div>
<h1><?=article_h($article['title'])?></h1>

<?php if ($authorText !== ''): ?>
<div class="authors"><strong><?=article_h($authorText)?></strong></div>
<?php endif; ?>

<?php
$affiliations = [];
foreach ($authors as $a) {
    $af = trim((string)($a['affiliation'] ?? ''));
    if ($af !== '' && !in_array($af, $affiliations, true)) $affiliations[] = $af;
}
?>
<?php if ($affiliations): ?>
<div class="affiliations"><?=article_h(implode(' · ', $affiliations))?></div>
<?php endif; ?>

<div class="meta">
<strong>Article ID:</strong> <?=article_h($article['article_id'])?>
<?php if($doiUrl!==''): ?> &nbsp; | &nbsp; <strong>DOI:</strong> <a href="<?=article_h($doiUrl)?>" target="_blank" rel="noopener"><?=article_h($doiUrl)?></a><?php endif; ?>
</div>

<?php if($volume!=='' || $issue!=='' || $year!==''): ?>
<div class="meta"><strong>Issue:</strong>
<?=article_h($volume!=='' ? 'Vol. '.$volume : '')?>
<?=($issue!=='' ? ' No. '.article_h($issue) : '')?>
<?=($year!=='' ? ' ('.article_h($year).')' : '')?>
<?php if($pages!==''): ?> &nbsp; | &nbsp; <strong>Pages:</strong> <?=article_h($pages)?><?php endif; ?>
</div>
<?php endif; ?>

<h2 class="abstract-title">Abstract</h2>
<div class="abstract"><?= $article['abstract'] ?? '' ?></div>

<?php if($keywords): ?>
<div class="keywords">
<?php foreach($keywords as $kw): ?><span class="keyword"><?=article_h($kw)?></span><?php endforeach; ?>
</div>
<?php endif; ?>

<section class="tab-box" id="article-tabs">

<div class="tabs">
<button class="tab active" data-tab="about">About this article</button>
<button class="tab" data-tab="cite">How to cite</button>
<button class="tab" data-tab="refs">References</button>
</div>

<div class="tab-panel active" id="tab-about">
<h3 class="panel-title">About this Article</h3>
<table class="info-table">
<tr><th>Article Type</th><td><?=article_h($article['article_type'])?></td></tr>
<tr><th>Article ID</th><td><?=article_h($article['article_id'])?></td></tr>
<tr><th>Issue</th><td><?=article_h(article_volume_issue($article).' ('.$year.')')?></td></tr>
<?php if(($article['section'] ?? '')!==''): ?><tr><th>Section</th><td><?=article_h($article['section'])?></td></tr><?php endif; ?>
<?php if($doiUrl!==''): ?><tr><th>DOI</th><td><a href="<?=article_h($doiUrl)?>" target="_blank" rel="noopener"><?=article_h($doiUrl)?></a></td></tr><?php endif; ?>
<?php if($pages!==''): ?><tr><th>Pages</th><td><?=article_h($pages)?></td></tr><?php endif; ?>
<?php if(!empty($article['published_date']) && $article['published_date']!=='0000-00-00'): ?><tr><th>Published</th><td><?=article_h(date('d F Y',strtotime($article['published_date'])))?></td></tr><?php endif; ?>
<?php if($keywords): ?><tr><th>Keywords</th><td><?=article_h(implode(', ', $keywords))?></td></tr><?php endif; ?>
<tr><th>Access</th><td>Open Access</td></tr>
<?php if(($article['license'] ?? '')!==''): ?><tr><th>License</th><td><?=article_h($article['license'])?></td></tr><?php endif; ?>
</table>

<?php if($pdf!==''): ?>
<div class="files">
<a class="file-btn" href="<?=article_h(article_public_path($pdf))?>" target="_blank" rel="noopener">PDF</a>
<?php endif; ?>

<?php foreach($files as $f):
    $path = article_public_path((string)($f['stored_path'] ?? ''));
    if ($path === '') continue;
    $type = strtoupper(trim((string)($f['file_type'] ?? 'FILE')));
    if (strcasecmp((string)$f['stored_path'], $pdf) === 0) continue;
?>
<a class="file-btn" href="<?=article_h($path)?>" target="_blank" rel="noopener"><?=article_h($type)?></a>
<?php endforeach; ?>
</div>

</div>

<div class="tab-panel" id="tab-cite">
<h3 class="panel-title">How to Cite</h3>

<div class="notice">AJSMR provides three commonly used citation formats below. APA is displayed first as the primary citation format, followed by Vancouver and Harvard styles.</div>

<div class="citation-box">
<div class="citation-head">APA 7th Edition</div>
<div class="citation-text" id="apa-citation"><?=article_h($apa)?></div>
<div class="citation-actions">
<button class="small-btn" type="button" onclick="copyCitation('apa-citation')">Copy APA</button>
</div>
</div>

<div class="citation-box">
<div class="citation-head">Vancouver</div>
<div class="citation-text" id="vancouver-citation"><?=article_h($vancouver)?></div>
<div class="citation-actions">
<button class="small-btn" type="button" onclick="copyCitation('vancouver-citation')">Copy Vancouver</button>
</div>
</div>

<div class="citation-box">
<div class="citation-head">Harvard</div>
<div class="citation-text" id="harvard-citation"><?= $harvard ?></div>
<div class="citation-actions">
<button class="small-btn" type="button" onclick="copyCitation('harvard-citation')">Copy Harvard</button>
</div>
</div>

<div class="downloads">
<a class="small-btn" href="?id=<?=urlencode((string)$article['article_id'])?>&format=ris">Download RIS</a>
<a class="small-btn" href="?id=<?=urlencode((string)$article['article_id'])?>&format=bibtex">Download BibTeX</a>
</div>
</div>

<div class="tab-panel" id="tab-refs">
<h3 class="panel-title">References</h3>
<?php if(!$references): ?>
<div class="notice">No references have been entered for this article.</div>
<?php else: ?>
<div class="ref-list">
<?php foreach($references as $i=>$ref): ?>
<p class="ref"><?=($i+1)?>. <?= $ref['reference_text'] ?? '' ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

</section>

</article>

<?php
$ajsmr_root = '../';
require_once __DIR__ . '/../includes/ajsmr_sidebar.php';
?>
</div>
</main>

<?php
$ajsmr_root = '../';
require_once __DIR__ . '/../includes/ajsmr_footer.php';
?>

<script>
document.querySelectorAll('.tab').forEach(function(btn){
  btn.addEventListener('click',function(){
    document.querySelectorAll('.tab').forEach(function(b){b.classList.remove('active')});
    document.querySelectorAll('.tab-panel').forEach(function(p){p.classList.remove('active')});
    btn.classList.add('active');
    document.getElementById('tab-'+btn.dataset.tab).classList.add('active');
    if(history.replaceState){
      history.replaceState(null,'','#'+btn.dataset.tab);
    }
  });
});

function activateFromHash(){
  var hash = location.hash.replace('#','');
  if(hash === 'cite' || hash === 'refs' || hash === 'about'){
    var b=document.querySelector('.tab[data-tab="'+hash+'"]');
    if(b) b.click();
  }
}
activateFromHash();

function copyCitation(id){
  var el=document.getElementById(id);
  var text=el.innerText || el.textContent;
  navigator.clipboard.writeText(text).then(function(){
    alert('Citation copied to clipboard.');
  }).catch(function(){
    alert('Please select and copy the citation manually.');
  });
}
</script>

</body>
</html>
