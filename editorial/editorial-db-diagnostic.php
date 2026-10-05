<?php
declare(strict_types=1);
require_once __DIR__.'/config/config.php';
header('Content-Type:text/plain; charset=utf-8');
try{
 $pdo=db();
 echo "AJSMR EDITORIAL LOCAL DATABASE DIAGNOSTIC\n";
 echo "==========================================\n";
 echo "Database: ".($pdo->query('SELECT DATABASE()')->fetchColumn())."\n";
 echo "PHP: ".PHP_VERSION."\n\n";
 $tables=$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
 echo "Tables: ".count($tables)."\n";
 foreach($tables as $t)echo " - $t\n";
 echo "\nUsers: ".$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()."\n";
 echo "Manuscripts: ".$pdo->query('SELECT COUNT(*) FROM manuscripts')->fetchColumn()."\n";
 echo "Audit records: ".$pdo->query('SELECT COUNT(*) FROM audit_log')->fetchColumn()."\n";
 echo "\nRESULT: Editorial database is ready.\n";
}catch(Throwable $e){http_response_code(500);echo "ERROR: ".$e->getMessage();}
