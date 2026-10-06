<?php
declare(strict_types=1);

/**
 * AJSMR Legacy Author Search Compatibility Redirect
 * =================================================
 * Safely redirects legacy author search requests to the centralized search.php endpoint.
 * Protects against SQL injection and insecure parameters from older forms.
 */

$raw_q = (string)($_REQUEST['searchkey1'] ?? ($_REQUEST['searchkey'] ?? ($_REQUEST['q'] ?? '')));
$raw_q = str_replace("\0", '', $raw_q);
$q = trim(preg_replace('/\s+/', ' ', $raw_q));

$target = 'search.php';
if ($q !== '') {
    $target .= '?q=' . urlencode($q);
}

if (!headers_sent()) {
    header('Location: ' . $target, true, 301);
    exit;
}

$safe_target = htmlspecialchars($target, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="refresh" content="0;url=<?= $safe_target ?>">
<title>Redirecting to Search...</title>
<script>window.location.href = <?= json_encode($target) ?>;</script>
</head>
<body>
<p>Redirecting to <a href="<?= $safe_target ?>">Search Results</a>...</p>
</body>
</html>