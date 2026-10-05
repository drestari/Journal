<?php
declare(strict_types=1);

/*
 * AJSMR Public Article Route
 * Redirects root-level article requests to editorial/article.php preserving query parameters
 */
$qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: editorial/article.php' . $qs, true, 302);
exit;
