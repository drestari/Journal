<?php
require_once __DIR__ . '/reviewer_portal_common.php';
$db = db();

echo '<pre>';
echo "AJSMR REVIEWER PORTAL AUTHENTICATION TEST\n\n";

$sessionUser = $_SESSION['user'] ?? null;
echo "Session user:\n";
print_r($sessionUser);

if (is_array($sessionUser) && !empty($sessionUser['id'])) {
    $stmt = $db->prepare("SELECT id,email,role,active FROM users WHERE id=? LIMIT 1");
    $stmt->execute([(int)$sessionUser['id']]);
    echo "\nDatabase user:\n";
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
}

echo "\nExpected:\nrole = reviewer\nactive = 1\n";
echo '</pre>';
?>
