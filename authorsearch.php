<?php
declare(strict_types=1);

/**
 * AJSMR Author Search Widget / Compatibility Redirect
 * ===================================================
 * Safely redirects direct requests or renders a modern GET widget pointing to search.php.
 */

$raw_q = (string)($_REQUEST['searchkey1'] ?? ($_REQUEST['searchkey'] ?? ($_REQUEST['q'] ?? '')));
$raw_q = str_replace("\0", '', $raw_q);
$q = trim(preg_replace('/\s+/', ' ', $raw_q));

$is_direct_request = (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'authorsearch.php');

if ($is_direct_request || $q !== '') {
    $target = 'search.php';
    if ($q !== '') {
        $target .= '?q=' . urlencode($q);
    }
    if (!headers_sent()) {
        header('Location: ' . $target, true, 301);
        exit;
    }
    $safe_target = htmlspecialchars($target, ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta http-equiv="refresh" content="0;url=' . $safe_target . '">';
    echo '<script>window.location.href=' . json_encode($target) . ';</script></head><body>';
    echo '<p>Redirecting to <a href="' . $safe_target . '">Search</a>...</p></body></html>';
    exit;
}
?>
<div class="manubox mar-b-30" style="background:#fff;border:1px solid #d7e2ec;border-radius:6px;padding:18px;margin-bottom:24px;">
    <div class="content">
        <form name="fsearch1" method="get" action="search.php">
            <div class="form-group" style="margin-bottom:12px;">
                <label style="font-weight:700;color:#06346d;display:block;margin-bottom:8px;font-size:14px;">Search by Author Name</label>
                <input name="q" value="" placeholder="Author Name..." id="searchkey1" type="search" class="inputsty" style="width:100%;height:40px;padding:0 12px;border:1px solid #c9d6e2;border-radius:4px;box-sizing:border-box;font-size:13px;outline:none;" required>
            </div>
            <button name="button" class="btn btn-style-sixteen" type="submit" id="button" style="width:100%;height:40px;background:#075fa8;color:#fff;border:0;border-radius:4px;font-weight:700;font-size:13px;cursor:pointer;">Search</button>
        </form>
    </div>
</div>