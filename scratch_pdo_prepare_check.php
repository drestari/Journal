<?php
require_once __DIR__ . '/editorial/config/config.php';
$pdo = db();

// Get full DB schema mapping
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$schema = [];
foreach ($tables as $t) {
    $cols = $pdo->query("SHOW COLUMNS FROM `{$t}`")->fetchAll(PDO::FETCH_COLUMN);
    $schema[$t] = array_map('strtolower', $cols);
}

$files = [
    'editorial/author/index.php',
    'editorial/author/submit.php',
    'editorial/author/submissions.php',
    'editorial/author/workflow.php',
    'editorial/author_proof.php',
    'editorial/includes/header.php',
    'editorial/includes/footer.php',
    'editorial/download_manuscript_file.php',
    'editorial/workflow_v1_common.php'
];

echo "=========================================================\n";
echo "PARSING ALL SQL STATEMENTS AND VALIDATING EVERY COLUMN\n";
echo "=========================================================\n";

foreach ($files as $file) {
    $filePath = __DIR__ . '/' . $file;
    if (!file_exists($filePath)) continue;
    $content = file_get_contents($filePath);
    
    // Extract SQL strings
    preg_match_all('/"(SELECT|INSERT|UPDATE|DELETE)[^"]+"|\'(SELECT|INSERT|UPDATE|DELETE)[^\']+\'/i', $content, $matches);
    foreach ($matches[0] as $q) {
        $qClean = trim($q, '"\'');
        echo "File {$file} -> Query: {$qClean}\n";
        try {
            $stmt = $pdo->prepare($qClean);
            echo "  ✓ PREPARE SUCCESSFUL\n";
        } catch (PDOException $e) {
            echo "  ✕ PREPARE FAILED: " . $e->getMessage() . "\n";
        }
    }
}
