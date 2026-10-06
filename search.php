<?php
declare(strict_types=1);

/**
 * AJSMR — Central Public Search Handler & Results Page
 * ====================================================
 * Unified, secure, modern search endpoint for published AJSMR articles.
 * Rebuilt to match the AJSMR Homepage V5 design system.
 */

// If included as a widget within legacy sidebars (e.g., abstracts_details.php)
$is_direct_request = (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'search.php');
if (!$is_direct_request) {
    // Render compact sidebar widget pointing to search.php
    ?>
    <div class="manubox mar-b-30" style="background:#fff;border:1px solid #d7e2ec;border-radius:6px;padding:18px;margin-bottom:24px;">
        <div class="content">
            <form method="get" action="search.php">
                <div class="form-group" style="margin-bottom:12px;">
                    <label style="font-weight:700;color:#06346d;display:block;margin-bottom:8px;font-size:14px;">Find Published Articles</label>
                    <input name="q" placeholder="Title, author, keyword, DOI..." type="search" class="inputsty" style="width:100%;height:40px;padding:0 12px;border:1px solid #c9d6e2;border-radius:4px;box-sizing:border-box;font-size:13px;outline:none;" required>
                </div>
                <button type="submit" class="btn btn-style-sixteen" style="width:100%;height:40px;background:#075fa8;color:#fff;border:0;border-radius:4px;font-weight:700;font-size:13px;cursor:pointer;">Search</button>
            </form>
        </div>
    </div>
    <?php
    return;
}

require_once __DIR__ . '/config.inc.php';
$db = $Config['link'] ?? null;

// Escaping and path cleaning helper functions
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

if (!function_exists('ajsmr_get_snippet')) {
    function ajsmr_get_snippet(array $art, int $max_len = 220): string {
        $text = trim((string)($art['abstractsdesc'] ?? ($art['description'] ?? '')));
        if ($text === '') return '';
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
        if (mb_strlen($clean) <= $max_len) return $clean;
        $cut = mb_substr($clean, 0, $max_len);
        $last_space = mb_strrpos($cut, ' ');
        if ($last_space !== false && $last_space > 140) {
            $cut = mb_substr($cut, 0, $last_space);
        }
        return $cut . '...';
    }
}

// Read and sanitize search query
$raw_q = (string)($_GET['q'] ?? ($_POST['searchkey'] ?? ($_POST['searchkey1'] ?? ($_GET['searchkey'] ?? ''))));
$raw_q = str_replace("\0", '', $raw_q);
$q = trim(preg_replace('/\s+/', ' ', $raw_q));
if (mb_strlen($q) > 150) {
    $q = mb_substr($q, 0, 150);
}

// Pagination setup
$per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$articles = [];
$total_count = 0;
$total_pages = 0;
$is_empty_query = ($q === '');
$search_executed = false;

if (!$is_empty_query && $db) {
    $search_executed = true;

    // Tokenize query for multi-word matching
    $raw_tokens = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);
    $tokens = [];
    $stopwords = ['and', 'or', 'the', 'of', 'in', 'on', 'at', 'to', 'for', 'with', 'by', 'an', 'as', 'is', 'a'];
    foreach ($raw_tokens as $t) {
        $t_clean = trim(preg_replace('/[^\p{L}\p{N}\.\-\/]/u', '', $t));
        if (mb_strlen($t_clean) >= 2 && !in_array(mb_strtolower($t_clean), $stopwords, true)) {
            $tokens[] = $t_clean;
        }
    }
    if (empty($tokens) && !empty($raw_tokens)) {
        $tokens = array_slice($raw_tokens, 0, 5);
    }

    // Build conditions
    $phrase_like = '%' . $q . '%';
    $phrase_cond = "(ic.conttitle LIKE ? OR ic.authors LIKE ? OR ic.doi LIKE ? OR ab.keywords LIKE ? OR ab.abstractsdesc LIKE ? OR ic.description LIKE ?)";
    $phrase_params = [$phrase_like, $phrase_like, $phrase_like, $phrase_like, $phrase_like, $phrase_like];
    $phrase_types = 'ssssss';

    $is_numeric_id = (is_numeric($q) && (int)$q > 0 && (int)$q < 100000);
    if ($is_numeric_id) {
        $phrase_cond .= " OR ic.contentid = ?";
        $phrase_params[] = (int)$q;
        $phrase_types .= 'i';
    }

    if (count($tokens) > 1) {
        $token_conds = [];
        $token_params = [];
        $token_types = '';
        foreach ($tokens as $token) {
            $t_like = '%' . $token . '%';
            $token_conds[] = "(ic.conttitle LIKE ? OR ic.authors LIKE ? OR ic.doi LIKE ? OR ab.keywords LIKE ? OR ab.abstractsdesc LIKE ? OR ic.description LIKE ?)";
            for ($k = 0; $k < 6; $k++) {
                $token_params[] = $t_like;
                $token_types .= 's';
            }
        }
        $where_sql = "({$phrase_cond} OR (" . implode(' AND ', $token_conds) . "))";
        $where_params = array_merge($phrase_params, $token_params);
        $where_types = $phrase_types . $token_types;
    } else {
        $where_sql = $phrase_cond;
        $where_params = $phrase_params;
        $where_types = $phrase_types;
    }

    // Ranking order:
    // 1. Exact title match: 100
    // 2. Exact ID match: 95
    // 3. Title contains full query: 80
    // 4. Authors contains full query: 60
    // 5. DOI contains full query: 50
    // 6. Keywords contains full query: 40
    // 7. Abstract / description contains full query: 30
    // 8. Multi-word dispersed match: 10
    $order_sql = "CASE
        WHEN LOWER(TRIM(ic.conttitle)) = LOWER(?) THEN 100
        WHEN ic.contentid = ? THEN 95
        WHEN LOWER(ic.conttitle) LIKE LOWER(?) THEN 80
        WHEN LOWER(ic.authors) LIKE LOWER(?) THEN 60
        WHEN LOWER(ic.doi) LIKE LOWER(?) THEN 50
        WHEN LOWER(COALESCE(ab.keywords, '')) LIKE LOWER(?) THEN 40
        WHEN LOWER(COALESCE(ab.abstractsdesc, '')) LIKE LOWER(?) OR LOWER(COALESCE(ic.description, '')) LIKE LOWER(?) THEN 30
        ELSE 10
    END DESC, ic.contentid DESC";

    $order_id_param = $is_numeric_id ? (int)$q : 0;
    $order_params = [$q, $order_id_param, $phrase_like, $phrase_like, $phrase_like, $phrase_like, $phrase_like, $phrase_like];
    $order_types = 'sissssss';

    // Count Query
    $count_sql = "SELECT COUNT(DISTINCT ic.contentid) AS total
                  FROM ajsmr_issuecontent ic
                  LEFT JOIN ajsmr_abstracts ab ON ic.contentid = ab.contentid AND ab.status = 1
                  WHERE ic.status = 1 AND {$where_sql}";

    $count_stmt = mysqli_prepare($db, $count_sql);
    if ($count_stmt) {
        mysqli_stmt_bind_param($count_stmt, $where_types, ...$where_params);
        mysqli_stmt_execute($count_stmt);
        $count_res = mysqli_stmt_get_result($count_stmt);
        if ($count_row = mysqli_fetch_assoc($count_res)) {
            $total_count = (int)($count_row['total'] ?? 0);
        }
        mysqli_stmt_close($count_stmt);
    }

    $total_pages = (int)ceil($total_count / $per_page);
    if ($page > $total_pages && $total_pages > 0) {
        $page = $total_pages;
        $offset = ($page - 1) * $per_page;
    }

    // Results Query
    if ($total_count > 0) {
        $data_sql = "SELECT ic.contentid, ic.catid, ic.type, ic.conttitle, ic.authors, ic.journal, ic.doi,
                            ic.abstract, ic.fullpaper, ic.photopath, ic.description,
                            ab.issuedetails, ab.abstractsdesc, ab.keywords
                     FROM ajsmr_issuecontent ic
                     LEFT JOIN ajsmr_abstracts ab ON ic.contentid = ab.contentid AND ab.status = 1
                     WHERE ic.status = 1 AND {$where_sql}
                     ORDER BY {$order_sql}
                     LIMIT ? OFFSET ?";

        $data_params = array_merge($where_params, $order_params, [$per_page, $offset]);
        $data_types = $where_types . $order_types . 'ii';

        $data_stmt = mysqli_prepare($db, $data_sql);
        if ($data_stmt) {
            mysqli_stmt_bind_param($data_stmt, $data_types, ...$data_params);
            mysqli_stmt_execute($data_stmt);
            $data_res = mysqli_stmt_get_result($data_stmt);
            while ($row = mysqli_fetch_assoc($data_res)) {
                $articles[] = $row;
            }
            mysqli_stmt_close($data_stmt);
        }
    }
}

// Variables consumed by shared header and footer
$ajsmr_active_nav = 'articles';
$ajsmr_root = '';
$journal = 'The American Journal of Science and Medical Research';
$issn = '2377-6196';
$frequency = 'Quarterly';
$publisher = 'Advaitha Innovative Research Association (AIRA)';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $q !== '' ? 'Search: ' . ajsmr_h($q) . ' | ' : 'Search Articles | ' ?><?= ajsmr_h($journal) ?> (AJSMR)</title>
<meta name="description" content="Search published articles in The American Journal of Science and Medical Research (AJSMR), ISSN 2377-6196.">
<meta name="robots" content="noindex,follow">
<link rel="icon" href="images/favicon.ico">
<link rel="stylesheet" href="ajsmr-homepage-v5-2.css">

<style>
/* Page-level search results styling consistent with AJSMR V5 */
.search-hero {
  background: #f2f7fb;
  border-bottom: 1px solid #dbe6ef;
  padding: 30px 0 34px;
}
.search-breadcrumb {
  margin-bottom: 14px;
  color: #64748b;
  font-size: 13px;
}
.search-breadcrumb a {
  color: #075fa8;
  text-decoration: none;
}
.search-breadcrumb a:hover {
  text-decoration: underline;
}
.search-breadcrumb span {
  color: #172f4a;
  font-weight: 600;
}
.search-hero h1 {
  font-family: Georgia, serif;
  color: #063b70;
  font-size: 28px;
  margin: 0 0 16px;
  line-height: 1.25;
}
.search-bar-wrap {
  max-width: 820px;
}
.search-form-hero {
  display: flex;
  gap: 8px;
  width: 100%;
}
.search-input-hero {
  flex: 1;
  min-width: 0;
  height: 48px;
  border: 1px solid #c9d6e2;
  border-radius: 4px;
  padding: 0 16px;
  font-size: 15px;
  outline: none;
  background: #fff;
  color: #172f4a;
  box-sizing: border-box;
}
.search-input-hero:focus {
  border-color: #075fa8;
  box-shadow: 0 0 0 3px rgba(7,95,168,.12);
}
.search-btn-hero {
  height: 48px;
  padding: 0 24px;
  background: #075fa8;
  color: #fff;
  border: 0;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.search-btn-hero:hover {
  background: #063b70;
}
.search-hero-hint {
  margin-top: 8px;
  font-size: 12px;
  color: #64748b;
}

.search-main-section {
  padding: 40px 0 60px;
  background: #fff;
}
.search-layout-grid {
  display: grid;
  grid-template-columns: minmax(0, 2.05fr) minmax(270px, .85fr);
  gap: 32px;
  align-items: start;
}
@media (max-width: 900px) {
  .search-layout-grid {
    grid-template-columns: 1fr;
    gap: 28px;
  }
}

.search-meta-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  padding-bottom: 16px;
  margin-bottom: 16px;
  border-bottom: 2px solid #e2e8f0;
}
.search-meta-count {
  font-size: 15px;
  color: #172f4a;
}
.search-meta-count strong {
  color: #063b70;
}
.search-meta-query {
  display: inline-block;
  background: #e5f1fa;
  color: #075fa8;
  padding: 2px 8px;
  border-radius: 4px;
  font-weight: 700;
}
.search-clear-link {
  font-size: 12px;
  color: #64748b;
  text-decoration: none;
}
.search-clear-link:hover {
  color: #075fa8;
  text-decoration: underline;
}

/* Article Card in Search */
.search-article-card {
  display: grid;
  grid-template-columns: 160px minmax(0, 1fr);
  gap: 20px;
  padding: 20px 0;
  border-bottom: 1px solid #d2dde6;
  align-items: start;
}
@media (max-width: 600px) {
  .search-article-card {
    grid-template-columns: 1fr;
    gap: 14px;
  }
}
.search-card-image {
  width: 160px;
  height: 125px;
  border: 1px solid #d6e1ea;
  background: #f4f8fb;
  border-radius: 4px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}
@media (max-width: 600px) {
  .search-card-image {
    width: 100%;
    height: 160px;
  }
}
.search-card-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.search-card-content {
  min-width: 0;
}
.search-card-type {
  display: inline-block;
  background: #075fa8;
  color: #fff;
  padding: 3px 9px;
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
  border-radius: 3px;
  margin-bottom: 6px;
}
.search-card-title {
  font-family: Arial, sans-serif;
  font-size: 17px;
  line-height: 1.4;
  margin: 4px 0 6px;
}
.search-card-title a {
  color: #064bb5;
  text-decoration: none;
}
.search-card-title a:hover {
  text-decoration: underline;
}
.search-card-authors {
  font-size: 12px;
  color: #475569;
  font-style: italic;
  margin: 3px 0;
}
.search-card-citation {
  font-size: 12px;
  color: #64748b;
  margin: 3px 0;
}
.search-card-doi {
  font-size: 12px;
  color: #53697e;
  margin: 3px 0;
}
.search-card-doi a {
  color: #075fa8;
  text-decoration: none;
}
.search-card-doi a:hover {
  text-decoration: underline;
}
.search-card-snippet {
  font-size: 13px;
  line-height: 1.5;
  color: #334155;
  margin: 8px 0 10px;
  text-align: justify;
}
.search-card-links {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
  margin-top: 10px;
  font-size: 12px;
  font-weight: 700;
}
.btn-card-action {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 12px;
  border-radius: 4px;
  text-decoration: none;
  font-size: 12px;
  font-weight: 700;
}
.btn-card-action.primary {
  background: #075fa8;
  color: #fff;
}
.btn-card-action.primary:hover {
  background: #063b70;
}
.btn-card-action.outline {
  border: 1px solid #075fa8;
  color: #075fa8;
  background: #fff;
}
.btn-card-action.outline:hover {
  background: #f0f7fc;
}
.btn-card-action.pdf {
  background: #b91c1c;
  color: #fff;
}
.btn-card-action.pdf:hover {
  background: #991b1b;
}

/* Empty State / Prompt Box */
.search-status-box {
  padding: 40px 24px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  text-align: center;
  margin: 20px 0;
}
.search-status-icon {
  font-size: 42px;
  color: #94a3b8;
  margin-bottom: 12px;
}
.search-status-title {
  font-family: Georgia, serif;
  font-size: 20px;
  color: #063b70;
  margin: 0 0 8px;
}
.search-status-msg {
  font-size: 14px;
  color: #64748b;
  max-width: 520px;
  margin: 0 auto 20px;
  line-height: 1.5;
}
.search-tips-box {
  text-align: left;
  max-width: 480px;
  margin: 0 auto;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 16px 20px;
  font-size: 13px;
  color: #334155;
}
.search-tips-box h4 {
  margin: 0 0 8px;
  font-size: 13px;
  color: #063b70;
}
.search-tips-box ul {
  margin: 0;
  padding-left: 18px;
}
.search-tips-box li {
  margin-bottom: 4px;
}

/* Pagination */
.search-pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 6px;
  margin-top: 36px;
  flex-wrap: wrap;
}
.search-pagination a,
.search-pagination span {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 38px;
  height: 38px;
  padding: 0 12px;
  border-radius: 4px;
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
  box-sizing: border-box;
}
.search-pagination a {
  background: #fff;
  border: 1px solid #cbd5e1;
  color: #172f4a;
}
.search-pagination a:hover {
  background: #f1f5f9;
  border-color: #075fa8;
  color: #075fa8;
}
.search-pagination .current-page {
  background: #075fa8;
  border: 1px solid #075fa8;
  color: #fff;
}
.search-pagination .disabled {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  color: #94a3b8;
  cursor: not-allowed;
}
</style>
</head>

<body>
<a class="skip" href="#main">Skip to main content</a>

<?php
// Shared Master Header
require_once __DIR__ . '/includes/ajsmr_header.php';
?>

<!-- Search Hero Bar -->
<section class="search-hero" aria-label="Search Bar">
  <div class="container">
    <div class="search-breadcrumb">
      <a href="index.php">Home</a> &nbsp;/&nbsp; <span>Search Articles</span>
    </div>
    <h1>Search Published Articles</h1>
    <div class="search-bar-wrap">
      <form class="search-form-hero" action="search.php" method="get">
        <input type="search" name="q" class="search-input-hero" placeholder="Search by title, author, keyword, DOI, or article ID..." aria-label="Search articles" value="<?= ajsmr_h($q) ?>" autofocus>
        <button type="submit" class="search-btn-hero" aria-label="Search">
          <span>Search Articles</span> <span>⌕</span>
        </button>
      </form>
      <div class="search-hero-hint">Tip: Enter keywords, full or partial title, author surname, DOI (e.g. 10.17812), or article ID.</div>
    </div>
  </div>
</section>

<!-- Search Results & Sidebar Section -->
<main id="main" class="search-main-section">
  <div class="container">
    <div class="search-layout-grid">
      
      <!-- Left Column: Search Results List -->
      <section class="search-results-col" aria-label="Search Results">
        
        <?php if ($is_empty_query): ?>
          <!-- State: Empty Query -->
          <div class="search-status-box">
            <div class="search-status-icon">🔍</div>
            <h2 class="search-status-title">Please enter a search term</h2>
            <p class="search-status-msg">Enter an article title, author name, keyword, DOI, or topic in the search box above to explore published articles in AJSMR.</p>
            <div class="search-tips-box">
              <h4>Suggested Search Tips:</h4>
              <ul>
                <li>Search by topic: <em>"diabetes"</em>, <em>"molecular docking"</em>, <em>"cancer"</em></li>
                <li>Search by author: <em>"Estari Mamidala"</em>, <em>"Nirmala"</em></li>
                <li>Search by DOI: <em>"10.17812"</em></li>
                <li>Search by exact phrase: <em>"Pedagogical Innovation"</em></li>
              </ul>
            </div>
          </div>

        <?php elseif ($total_count === 0): ?>
          <!-- State: No Results Found -->
          <div class="search-status-box">
            <div class="search-status-icon">∅</div>
            <h2 class="search-status-title">No articles found</h2>
            <p class="search-status-msg">We couldn't find any published articles matching <span class="search-meta-query">"<?= ajsmr_h($q) ?>"</span>.</p>
            <div class="search-tips-box">
              <h4>Suggestions to modify your search:</h4>
              <ul>
                <li>Check the spelling of your search terms.</li>
                <li>Try fewer or more general keywords (e.g., <em>"learning"</em> instead of <em>"machine learning"</em>).</li>
                <li>Search by author last name or main research topic.</li>
                <li>Try searching by DOI or numerical article ID.</li>
              </ul>
            </div>
          </div>

        <?php else: ?>
          <!-- State: Results Found -->
          <div class="search-meta-bar">
            <div class="search-meta-count">
              Found <strong><?= (int)$total_count ?></strong> published article<?= $total_count === 1 ? '' : 's' ?> matching <span class="search-meta-query">"<?= ajsmr_h($q) ?>"</span>
              <?php if ($total_pages > 1): ?>
                <span style="color:#64748b;font-size:13px;"> (Page <?= (int)$page ?> of <?= (int)$total_pages ?>)</span>
              <?php endif; ?>
            </div>
            <div>
              <a href="search.php" class="search-clear-link">✕ Clear Search</a>
            </div>
          </div>

          <div class="search-results-list">
            <?php foreach ($articles as $art): ?>
              <?php
                $art_id       = (int)($art['contentid'] ?? 0);
                $art_title    = $art['conttitle'] ?? '';
                $art_authors  = $art['authors'] ?? '';
                $art_citation = $art['issuedetails'] ?? ($art['journal'] ?? 'The American Journal of Science and Medical Research');
                $art_doi      = trim((string)($art['doi'] ?? ''));
                $art_pdf      = ajsmr_clean_pdf_path($art);
                $art_img      = ajsmr_clean_image_path($art);
                $art_type     = trim((string)($art['type'] ?? 'Research Article'));
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

                $snippet = ajsmr_get_snippet($art);
              ?>
              <article class="search-article-card">
                <div class="search-card-image">
                  <?php if ($art_img !== ''): ?>
                    <img src="<?= ajsmr_h($art_img) ?>" alt="<?= ajsmr_h($art_title) ?>" loading="lazy">
                  <?php else: ?>
                    <img src="images/logo-icon.png" alt="<?= ajsmr_h($art_title) ?>" loading="lazy">
                  <?php endif; ?>
                </div>
                
                <div class="search-card-content">
                  <div class="search-card-type"><?= ajsmr_h($art_type) ?></div>
                  <h3 class="search-card-title">
                    <a href="abstracts_details.php?id=<?= $art_id ?>"><?= ajsmr_h($art_title) ?></a>
                  </h3>
                  
                  <?php if ($art_authors !== ''): ?>
                    <p class="search-card-authors"><strong>Author(s):</strong> <?= ajsmr_h($art_authors) ?></p>
                  <?php endif; ?>
                  
                  <?php if ($art_citation !== ''): ?>
                    <p class="search-card-citation"><?= ajsmr_h($art_citation) ?></p>
                  <?php endif; ?>
                  
                  <?php if ($art_doi !== ''): ?>
                    <p class="search-card-doi"><strong>DOI:</strong>
                      <a href="<?= ajsmr_h($doi_url) ?>" target="_blank" rel="noopener noreferrer"><?= ajsmr_h($art_doi) ?></a>
                    </p>
                  <?php endif; ?>
                  
                  <?php if ($snippet !== ''): ?>
                    <p class="search-card-snippet"><?= ajsmr_h($snippet) ?></p>
                  <?php endif; ?>
                  
                  <div class="search-card-links">
                    <a href="abstracts_details.php?id=<?= $art_id ?>" class="btn-card-action primary">View Article</a>
                    <a href="abstracts_details.php?id=<?= $art_id ?>" class="btn-card-action outline">Abstract</a>
                    <?php if ($art_pdf !== ''): ?>
                      <a href="<?= ajsmr_h($art_pdf) ?>" target="_blank" rel="noopener" class="btn-card-action pdf">Full Paper (PDF) ⤓</a>
                    <?php endif; ?>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>

          <!-- Pagination Component -->
          <?php if ($total_pages > 1): ?>
            <nav class="search-pagination" aria-label="Search results pagination">
              <?php if ($page > 1): ?>
                <a href="search.php?q=<?= urlencode($q) ?>&page=<?= $page - 1 ?>" aria-label="Previous Page">« Prev</a>
              <?php else: ?>
                <span class="disabled">« Prev</span>
              <?php endif; ?>

              <?php
                $start_p = max(1, $page - 2);
                $end_p   = min($total_pages, $page + 2);

                if ($start_p > 1) {
                    echo '<a href="search.php?q=' . urlencode($q) . '&page=1">1</a>';
                    if ($start_p > 2) {
                        echo '<span class="disabled">…</span>';
                    }
                }

                for ($p = $start_p; $p <= $end_p; $p++) {
                    if ($p === $page) {
                        echo '<span class="current-page" aria-current="page">' . $p . '</span>';
                    } else {
                        echo '<a href="search.php?q=' . urlencode($q) . '&page=' . $p . '">' . $p . '</a>';
                    }
                }

                if ($end_p < $total_pages) {
                    if ($end_p < $total_pages - 1) {
                        echo '<span class="disabled">…</span>';
                    }
                    echo '<a href="search.php?q=' . urlencode($q) . '&page=' . $total_pages . '">' . $total_pages . '</a>';
                }
              ?>

              <?php if ($page < $total_pages): ?>
                <a href="search.php?q=<?= urlencode($q) ?>&page=<?= $page + 1 ?>" aria-label="Next Page">Next »</a>
              <?php else: ?>
                <span class="disabled">Next »</span>
              <?php endif; ?>
            </nav>
          <?php endif; ?>

        <?php endif; ?>

      </section>

      <!-- Right Column: Sidebar Resources -->
      <aside class="sidebar" aria-label="Journal Information Sidebar">
        <div class="side-card">
          <div class="side-title">JOURNAL INFORMATION</div>
          <div class="side-row"><span>Title</span><strong><?= ajsmr_h($journal) ?></strong></div>
          <div class="side-row"><span>Abbreviation</span><strong>AJSMR</strong></div>
          <div class="side-row"><span>ISSN</span><strong><?= ajsmr_h($issn) ?></strong></div>
          <div class="side-row"><span>Starting Year</span><strong>2014</strong></div>
          <div class="side-row"><span>Frequency</span><strong>Quarterly</strong></div>
          <div class="side-row"><span>Access</span><strong>Open Access</strong></div>
          <div class="side-row"><span>Publisher</span><strong>AIRA</strong></div>
        </div>

        <div class="side-card">
          <div class="side-title">AUTHOR RESOURCES</div>
          <a class="side-link" href="authorguidelines.php">Author Guidelines →</a>
          <a class="side-link" href="editorial/login.php">Submit Your Research →</a>
          <a class="side-link" href="currentissue.php">Current Issue →</a>
          <a class="side-link" href="archives.php">Journal Archives →</a>
          <a class="side-link" href="policies.php">Policy Hub →</a>
        </div>
      </aside>

    </div>
  </div>
</main>

<?php
// Shared Master Footer
require_once __DIR__ . '/includes/ajsmr_footer.php';
?>

</body>
</html>