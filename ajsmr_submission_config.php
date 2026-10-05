<?php
declare(strict_types=1);
function ajsmr_db(): PDO {
 static $pdo;
 if ($pdo instanceof PDO) return $pdo;

 $host = 'shareddb-g.hosting.stackcp.net';
 $name = 'ajsmrjournal-3731a6db';
 $user = 'ajsmrjournal-3731a6db';
 $pass = 'DV4z3wDax|=Q';

 try {
  $pdo = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, [
   PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
   PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
   PDO::ATTR_EMULATE_PREPARES=>false
  ]);
  return $pdo;
 } catch (PDOException $e) {
  try {
   $pdo = new PDO("mysql:host=shareddb-g.hosting.stackcp.net;dbname=ajsmrjournal-3731a6db;charset=utf8mb4", "ajsmrjournal-3731a6db", "{7QSSrLm5_Fm", [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false
   ]);
   return $pdo;
  } catch (PDOException $ex1) {
   try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=ajsmrjournal;charset=utf8mb4", "root", "Srija@2005", [
     PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
     PDO::ATTR_EMULATE_PREPARES=>false
    ]);
    return $pdo;
   } catch (PDOException $ex2) {
    $pdo = new PDO("mysql:host=localhost;dbname=ajsmrjournal;charset=utf8mb4", "root", "Srija@2005", [
     PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
     PDO::ATTR_EMULATE_PREPARES=>false
    ]);
    return $pdo;
   }
  }
 }
}
function h(string $v): string { return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function csrf(): string {
 if(empty($_SESSION['ajsmr_csrf'])) $_SESSION['ajsmr_csrf']=bin2hex(random_bytes(32));
 return $_SESSION['ajsmr_csrf'];
}
function check_csrf(string $v): void {
 if(!hash_equals((string)($_SESSION['ajsmr_csrf']??''),$v)) throw new RuntimeException('Invalid or expired security token. Reload the form.');
}
function orcid_ok(string $v): bool {
 return $v==='' || (bool)preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/i',$v);
}
function client_hashes(): array {
 $salt='AJSMR-LOCAL-TEST-ROTATE-BEFORE-PRODUCTION';
 return ['ip_hash'=>hash('sha256',$salt.'|'.($_SERVER['REMOTE_ADDR']??'')),
         'user_agent_hash'=>hash('sha256',$salt.'|'.($_SERVER['HTTP_USER_AGENT']??''))];
}
function manuscript_no(PDO $pdo): string {
 for($x=0;$x<20;$x++){
  $n='AJSMR-'.date('Y').'-'.str_pad((string)random_int(1,99999),5,'0',STR_PAD_LEFT);
  $q=$pdo->prepare('SELECT 1 FROM ajsmr_submissions WHERE manuscript_no=?');
  $q->execute([$n]); if(!$q->fetchColumn()) return $n;
 }
 throw new RuntimeException('Unable to generate manuscript number.');
}
