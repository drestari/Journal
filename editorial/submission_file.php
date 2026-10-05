<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/journal_submission_bridge.php';

editorial_gate(['admin','editor','editor_in_chief','editor-in-chief','editor in chief']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('Invalid file ID.'); }

$db = journal_db();
$q = $db->prepare('SELECT * FROM ajsmr_submission_files WHERE id=?');
$q->execute([$id]); $f = $q->fetch();
if (!$f) { http_response_code(404); exit('File not found.'); }

$base = realpath(journal_file_root());
$path = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $f['relative_path']));
if (!$base || !$path || !is_file($path)) { http_response_code(404); exit('Stored file not found.'); }

$prefix = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
if (strncmp($path, $prefix, strlen($prefix)) !== 0) { http_response_code(403); exit('Access denied.'); }

$mime = (string)$f['mime_type'];
$downloadName = basename((string)$f['original_name']);
header('Content-Type: ' . ($mime ?: 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '_', $downloadName) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
