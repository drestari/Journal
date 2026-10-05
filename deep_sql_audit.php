<?php
require_once __DIR__ . '/editorial/config/config.php';
$pdo = db();

// Get full table schema details
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$dbSchema = [];
foreach ($tables as $t) {
    $cols = $pdo->query("SHOW COLUMNS FROM `{$t}`")->fetchAll(PDO::FETCH_COLUMN);
    $dbSchema[$t] = array_map('strtolower', $cols);
}

$authorFiles = [
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
echo "DEEP SQL COLUMN AUDIT AGAINST LIVE AJSRM_EDITORIAL SCHEMA\n";
echo "=========================================================\n\n";

$totalErrors = 0;

foreach ($authorFiles as $relFile) {
    $filePath = __DIR__ . '/' . $relFile;
    if (!file_exists($filePath)) continue;
    
    echo "--- File: {$relFile} ---\n";
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    
    foreach ($lines as $lineNum => $line) {
        $lineNo = $lineNum + 1;

        // Check for created_at on manuscripts table
        if (preg_match('/\bcreated_at\b/i', $line) && (preg_match('/manuscripts/i', $line) || preg_match('/submitted_at/i', $line))) {
            echo "  [ERROR] Line {$lineNo}: Invalid timestamp 'created_at' referenced for manuscripts table!\n";
            echo "          Line text: " . trim($line) . "\n";
            $totalErrors++;
        }

        // Check for abstract_text on manuscripts table
        if (preg_match('/manuscripts\.[`"]?abstract_text/i', $line) || (preg_match('/manuscripts/i', $line) && preg_match('/abstract_text/i', $line) && !preg_match('/abstract\s+AS\s+abstract_text/i', $line))) {
            echo "  [ERROR] Line {$lineNo}: Invalid column 'abstract_text' on manuscripts table!\n";
            echo "          Line text: " . trim($line) . "\n";
            $totalErrors++;
        }

        // Check for manuscript_files table
        if (preg_match('/\bmanuscript_files\b/i', $line) && !preg_match('/\/\//', $line) && !preg_match('/\/\*/', $line)) {
            echo "  [ERROR] Line {$lineNo}: Nonexistent table 'manuscript_files' referenced!\n";
            echo "          Line text: " . trim($line) . "\n";
            $totalErrors++;
        }

        // Check for ew_production table
        if (preg_match('/\bew_production\b/i', $line)) {
            echo "  [ERROR] Line {$lineNo}: Nonexistent table 'ew_production' referenced! (Use 'production')\n";
            echo "          Line text: " . trim($line) . "\n";
            $totalErrors++;
        }

        // Check for full_name on manuscript_authors table
        if (preg_match('/manuscript_authors[^\n]*full_name/i', $line)) {
            echo "  [ERROR] Line {$lineNo}: Invalid column 'full_name' on manuscript_authors! (Use 'author_name')\n";
            echo "          Line text: " . trim($line) . "\n";
            $totalErrors++;
        }

        // Check for author_id on manuscripts table
        if (preg_match('/m(?:anuscripts)?\.author_id/i', $line)) {
            echo "  [ERROR] Line {$lineNo}: Invalid column 'author_id' on manuscripts! (Use 'corresponding_author_id')\n";
            echo "          Line text: " . trim($line) . "\n";
            $totalErrors++;
        }
    }
}

echo "\n=========================================================\n";
echo "Audit Completed. Total Schema Mismatch Errors Found: {$totalErrors}\n";
echo "=========================================================\n";
