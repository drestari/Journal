<?php
declare(strict_types=1);
require_once __DIR__.'/ajsmr_submission_config.php';
header('Content-Type:text/plain; charset=utf-8');
try {
 $pdo=ajsmr_db();
 echo "AJSMR submission database diagnostic\n";
 echo "Database: ajsmrjournal\n\n";
 foreach(['ajsmr_submissions','ajsmr_submission_authors','ajsmr_submission_affiliations','ajsmr_submission_reviewers','ajsmr_submission_files'] as $t){
   $cols=$pdo->query("SHOW COLUMNS FROM `$t`")->fetchAll();
   echo "$t: ".count($cols)." columns\n";
 }
} catch(Throwable $e){ http_response_code(500); echo "ERROR: ".$e->getMessage(); }
