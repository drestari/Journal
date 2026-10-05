<?php
/**
 * crossref_xml.php
 * Generates Crossref XML for DOI registration.
 * ew_doi_metadata, ew_issue_assignments, ew_publication_records do not exist.
 * All data is in the `production` table (doi, volume, issue, year, pages)
 * and in `manuscripts` (title, abstract) and `manuscript_authors` (author names).
 */
require_once __DIR__ . '/production_common.php';

$db = db();
$u  = prod_user($db);

$mid = (int)($_GET['id'] ?? 0);
if (!$mid) exit('Invalid manuscript ID.');

$m = prod_ms($db, $mid);

// Load production row (doi, volume, issue, year, pages, publication_status)
$s = $db->prepare("SELECT * FROM production WHERE manuscript_id=? LIMIT 1");
$s->execute([$mid]);
$prod = $s->fetch(PDO::FETCH_ASSOC);

if (!$prod || !$prod['doi']) exit('A DOI is required before Crossref XML can be generated.');

// Load authors from manuscript_authors
$authStmt = $db->prepare("SELECT author_name, email FROM manuscript_authors WHERE manuscript_id=? ORDER BY author_order ASC");
$authStmt->execute([$mid]);
$authorRows = $authStmt->fetchAll(PDO::FETCH_ASSOC);

function x($v) {
    return htmlspecialchars((string)$v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

$doi = trim($prod['doi']);
$url = 'https://ajsmrjournal.com/articles/' . urlencode($m['manuscript_no']);

$authorXml = '';
foreach ($authorRows as $i => $a) {
    $name = trim($a['author_name']);
    $parts = preg_split('/\s+/', $name);
    $family = array_pop($parts);
    $given = trim(implode(' ', $parts));
    if ($given === '') { $given = $family; $family = ''; }

    $seq = $i === 0 ? 'first' : 'additional';
    $authorXml .= '<person_name sequence="'.$seq.'" contributor_role="author">';
    $authorXml .= '<given_name>'.x($given).'</given_name>';
    if ($family !== '') $authorXml .= '<surname>'.x($family).'</surname>';
    $authorXml .= '</person_name>';
}

if ($authorXml === '') {
    $authorXml = '<person_name sequence="first" contributor_role="author"><given_name>Author</given_name><surname>Unknown</surname></person_name>';
}

$year   = $prod['year'] ?: date('Y');
$title  = $m['title'];
$volume = $prod['volume'] ?? '';
$issue  = $prod['issue'] ?? '';
$pages  = (string)($prod['pages'] ?? '');

$pagesXml = '';
if ($pages !== '') {
    if (strpos($pages, '-') !== false) {
        [$ps, $pe] = explode('-', $pages, 2);
        $pagesXml = '<pages><first_page>'.x(trim($ps)).'</first_page><last_page>'.x(trim($pe)).'</last_page></pages>';
    } else {
        $pagesXml = '<pages><first_page>'.x($pages).'</first_page></pages>';
    }
}

$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<doi_batch version="5.5.0" xmlns="http://www.crossref.org/schema/5.5.0"';
$xml .= ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"';
$xml .= ' xsi:schemaLocation="http://www.crossref.org/schema/5.5.0 https://www.crossref.org/schemas/crossref5.5.0.xsd">' . "\n";
$xml .= '<head>';
$xml .= '<doi_batch_id>AJSMR-'.$mid.'-'.date('YmdHis').'</doi_batch_id>';
$xml .= '<timestamp>'.date('YmdHis').'</timestamp>';
$xml .= '<depositor><depositor_name>AJSMR</depositor_name><email_address>editorial@ajsmrjournal.com</email_address></depositor>';
$xml .= '<registrant>Asian Journal of Science, Management and Research</registrant>';
$xml .= '</head>';
$xml .= '<body><journal><journal_metadata>';
$xml .= '<full_title>Asian Journal of Science, Management and Research</full_title>';
$xml .= '<issn media_type="electronic">REPLACE_WITH_AJSMR_ISSN</issn>';
$xml .= '</journal_metadata><journal_article publication_type="full_text">';
$xml .= '<titles><title>'.x($title).'</title></titles>';
$xml .= '<contributors>'.$authorXml.'</contributors>';
$xml .= '<publication_date media_type="online"><year>'.x($year).'</year></publication_date>';
if ($volume !== '') $xml .= '<volume>'.x($volume).'</volume>';
if ($issue !== '')  $xml .= '<issue>'.x($issue).'</issue>';
$xml .= $pagesXml;
$xml .= '<doi_data><doi>'.x($doi).'</doi><resource>'.x($url).'</resource></doi_data>';
$xml .= '</journal_article></journal></body></doi_batch>';

header('Content-Type: application/xml; charset=UTF-8');
header('Content-Disposition: attachment; filename="AJSMR-'.$mid.'-crossref.xml"');
echo $xml;
