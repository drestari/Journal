<?php
$u = function_exists('user') ? user() : ($_SESSION['user'] ?? null);
if (in_array(($u['role'] ?? ''), ['editor_in_chief', 'admin'], true)) {
    include __DIR__ . '/eic_footer.php';
    return;
}
if (($u['role'] ?? '') === 'author') {
    include __DIR__ . '/author_footer.php';
    return;
}
?>
</main></div><footer>AJSMR Editorial Management System V1 &copy; <?=date('Y')?> · AIRA</footer></body></html>