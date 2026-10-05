<?php
// Runtime smoke tester for Author Portal pages
require_once __DIR__ . '/editorial/config/config.php';
$pdo = db();

// Fetch an existing manuscript ID for testing
$msRow = $pdo->query("SELECT id, corresponding_author_id FROM manuscripts ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$msId = $msRow ? (int)$msRow['id'] : 1;
$authorId = $msRow ? (int)$msRow['corresponding_author_id'] : 4;

// Fetch user info for author
$uRow = $pdo->query("SELECT id, email, full_name, role FROM users WHERE id = {$authorId}")->fetch(PDO::FETCH_ASSOC);

$_SESSION['user'] = [
    'id' => $uRow['id'] ?? 4,
    'email' => $uRow['email'] ?? 'author@ajsmrjournal.com',
    'full_name' => $uRow['full_name'] ?? 'Author One',
    'role' => 'author'
];

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/editorial/author/index.php';

echo "=========================================================\n";
echo "RUNNING AUTHOR PORTAL RUNTIME SMOKE TEST SUITE\n";
echo "=========================================================\n";

$pages = [
    'Author Dashboard' => __DIR__ . '/editorial/author/index.php',
    'My Submissions' => __DIR__ . '/editorial/author/submissions.php',
    'Submit Manuscript' => __DIR__ . '/editorial/author/submit.php',
];

foreach ($pages as $label => $file) {
    ob_start();
    try {
        include $file;
        $output = ob_get_clean();
        if (strpos($output, 'Fatal error') !== false || strpos($output, 'PDOException') !== false || strpos($output, 'SQLSTATE') !== false) {
            echo "[FAIL] {$label}: Database or Fatal Error detected in output!\n";
        } else {
            echo "[PASS] {$label}: Loaded cleanly (" . strlen($output) . " bytes)\n";
        }
    } catch (Throwable $e) {
        ob_end_clean();
        echo "[FAIL] {$label}: Exception: " . $e->getMessage() . "\n";
    }
}

// Test workflow page for real manuscript ID
$_GET['id'] = $msId;
ob_start();
try {
    include __DIR__ . '/editorial/author/workflow.php';
    $output = ob_get_clean();
    if (strpos($output, 'Fatal error') !== false || strpos($output, 'PDOException') !== false || strpos($output, 'SQLSTATE') !== false) {
        echo "[FAIL] Author Workflow (ID {$msId}): Database or Fatal Error detected in output!\n";
    } else {
        echo "[PASS] Author Workflow (ID {$msId}): Loaded cleanly (" . strlen($output) . " bytes)\n";
    }
} catch (Throwable $e) {
    ob_end_clean();
    echo "[FAIL] Author Workflow (ID {$msId}): Exception: " . $e->getMessage() . "\n";
}

echo "=========================================================\n";
