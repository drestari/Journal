<?php
require_once __DIR__ . '/workflow_v1_common.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
echo '<pre>';
echo "AJSMR Workflow V1 Session Diagnostic\n\n";
echo "Session keys:\n";
foreach ($_SESSION as $k => $v) {
    if (is_array($v)) echo $k . ' = ' . print_r($v, true) . "\n";
    else echo $k . ' = ' . (is_scalar($v) ? (string)$v : gettype($v)) . "\n";
}
echo "\nDatabase users:\n";
try {
    $db = db();
    $rows = $db->query("SELECT id,email,role,active FROM users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
} catch (Throwable $e) {
    echo "DB error: ".$e->getMessage();
}
echo '</pre>';
?>
