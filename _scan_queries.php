<?php
/**
 * PHP Scanner - finds all SQL table/column references in PHP files
 * Outputs structured list of files + queries for analysis
 */

$root = __DIR__;
$files = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($iterator as $file) {
    if ($file->getExtension() === 'php' && strpos($file->getPathname(), '_db_inventory') === false && strpos($file->getPathname(), '_scan') === false) {
        $files[] = $file->getPathname();
    }
}
sort($files);

// Known bad tables (do not exist in DB)
$bad_tables = [
    'manuscript_files',
    'ew_production',
    'author_gallery_updates',
    'author_workflow',
    'publication_status',
    'proof_responses',
    'galley_proofs',       // correct is ew_galley_proofs
    'editorial_decisions', // correct is ew_editorial_decisions
    'technical_checks',    // correct is ew_technical_checks
    'reviewer_assignments',// correct is ew_reviewer_assignments
    'reviewer_pool',       // correct is ew_reviewer_pool
    'peer_reviews',        // correct is ew_peer_reviews
    'revisions',           // correct is ew_revisions
    'editor_assignments',  // correct is ew_editor_assignments
];

// Known bad columns by table
$bad_columns = [
    'manuscripts' => ['abstract_text'],  // correct is 'abstract'
];

// Regex patterns
$table_pattern = '/(?:FROM|JOIN|INTO|UPDATE|TABLE)\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i';
$col_pattern = '/[`\s](?:m|ms)\.(abstract_text)\b/i';

$results = [];
foreach ($files as $fpath) {
    $rel = str_replace($root . DIRECTORY_SEPARATOR, '', $fpath);
    $content = file_get_contents($fpath);
    if (stripos($content, 'SELECT') === false && stripos($content, 'INSERT') === false &&
        stripos($content, 'UPDATE') === false && stripos($content, 'DELETE') === false &&
        stripos($content, 'FROM') === false) {
        continue;
    }
    $issues = [];
    // Check for bad table names
    preg_match_all($table_pattern, $content, $matches);
    foreach ($matches[1] as $tbl) {
        if (in_array(strtolower($tbl), array_map('strtolower', $bad_tables))) {
            $issues[] = "BAD_TABLE: $tbl";
        }
    }
    // Check for abstract_text on manuscripts alias
    if (preg_match('/abstract_text/i', $content)) {
        // Find context
        preg_match_all('/[^\n]*abstract_text[^\n]*/i', $content, $ctxm);
        foreach ($ctxm[0] as $line) {
            $issues[] = "BAD_COLUMN: abstract_text => " . trim($line);
        }
    }
    if (!empty($issues)) {
        $results[$rel] = $issues;
    }
}

echo "=== SCAN RESULTS ===\n";
foreach ($results as $file => $issues) {
    echo "\nFILE: $file\n";
    foreach ($issues as $issue) {
        echo "  $issue\n";
    }
}
echo "\nTotal files with issues: " . count($results) . "\n";
echo "Total PHP files scanned: " . count($files) . "\n";
