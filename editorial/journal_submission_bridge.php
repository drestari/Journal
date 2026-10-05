<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';

function journal_db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $host = 'shareddb-g.hosting.stackcp.net';
    $name = 'ajsmrjournal-3731a6db';
    $user = 'ajsmrjournal-3731a6db';
    $pass = 'DV4z3wDax|=Q';

    try {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$name};charset=utf8mb4",
            $user, $pass,
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
        );
        return $pdo;
    } catch (PDOException $e) {
        try {
            $pdo = new PDO(
                "mysql:host=shareddb-g.hosting.stackcp.net;dbname=ajsmrjournal-3731a6db;charset=utf8mb4",
                "ajsmrjournal-3731a6db", "{7QSSrLm5_Fm",
                [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
            );
            return $pdo;
        } catch (PDOException $ex1) {
            try {
                $pdo = new PDO(
                    "mysql:host=127.0.0.1;port=3306;dbname=ajsmrjournal;charset=utf8mb4",
                    "root", "Srija@2005",
                    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
                );
                return $pdo;
            } catch (PDOException $ex2) {
                $pdo = new PDO(
                    "mysql:host=localhost;dbname=ajsmrjournal;charset=utf8mb4",
                    "root", "Srija@2005",
                    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
                );
                return $pdo;
            }
        }
    }
}
function journal_file_root(): string {
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'submissiondocs';
}
function get_public_submission(int $id): ?array {
    $db=journal_db();
    $q=$db->prepare('SELECT * FROM ajsmr_submissions WHERE id=? LIMIT 1');
    $q->execute([$id]); $r=$q->fetch();
    if (!$r) return null;

    $q=$db->prepare('SELECT * FROM ajsmr_submission_authors WHERE submission_id=? ORDER BY author_order,id');
    $q->execute([$id]); $r['authors']=$q->fetchAll();

    $q=$db->prepare('SELECT * FROM ajsmr_submission_affiliations WHERE submission_id=? ORDER BY affiliation_no,id');
    $q->execute([$id]); $r['affiliations']=$q->fetchAll();

    $q=$db->prepare('SELECT * FROM ajsmr_submission_reviewers WHERE submission_id=? ORDER BY id');
    $q->execute([$id]); $r['reviewers']=$q->fetchAll();

    $q=$db->prepare('SELECT * FROM ajsmr_submission_files WHERE submission_id=? ORDER BY id');
    $q->execute([$id]); $r['files']=$q->fetchAll();

    return $r;
}
function author_full_name(array $a): string {
    return trim(implode(' ', array_filter([
        $a['given_name'] ?? '',
        $a['middle_name'] ?? '',
        $a['family_name'] ?? ''
    ], fn($v)=>trim((string)$v)!=='')));
}
function h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
