<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$u = login_required();

$fileId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$proofId = filter_input(INPUT_GET, 'proof_id', FILTER_VALIDATE_INT);

if (!$fileId && !$proofId) {
    http_response_code(400);
    exit('Invalid file ID parameter.');
}

$pdo = db();
$file = null;

// 1. Check if proof_id was passed directly
if ($proofId) {
    try {
        $stmtG = $pdo->prepare('
            SELECT g.id, g.manuscript_id, g.proof_version AS version_no, g.file_path AS relative_path,
                   "galley_proof" AS file_type,
                   SUBSTRING_INDEX(g.file_path, "/", -1) AS original_name,
                   "application/pdf" AS mime_type,
                   m.corresponding_author_id, m.manuscript_no
            FROM ew_galley_proofs g
            JOIN manuscripts m ON m.id = g.manuscript_id
            WHERE g.id = ?
            LIMIT 1
        ');
        $stmtG->execute([$proofId]);
        $file = $stmtG->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $eG) {
        $file = null;
    }
}

// 2. manuscript_files table does not exist — skipped.
// Lookup falls through to manuscript_versions (step 3) below.

// 3. Check manuscript_versions
if (!$file && $fileId) {
    try {
        $stmtV = $pdo->prepare('
            SELECT v.id, v.manuscript_id, v.version_no, v.file_path AS relative_path,
                   COALESCE(NULLIF(v.author_response, \'\'), \'main_manuscript\') AS file_type,
                   SUBSTRING_INDEX(v.file_path, \'/\', -1) AS original_name,
                   \'\' AS mime_type,
                   m.corresponding_author_id, m.manuscript_no
            FROM manuscript_versions v
            JOIN manuscripts m ON m.id = v.manuscript_id
            WHERE v.id = ?
            LIMIT 1
        ');
        $stmtV->execute([$fileId]);
        $file = $stmtV->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e2) {
        $file = null;
    }
}

// 4. Check manuscripts table directly
if (!$file && $fileId) {
    try {
        $stmtM = $pdo->prepare('
            SELECT 1 AS id, m.id AS manuscript_id, m.version_no, m.manuscript_file AS relative_path,
                   "main_manuscript" AS file_type,
                   SUBSTRING_INDEX(m.manuscript_file, "/", -1) AS original_name,
                   "" AS mime_type,
                   m.corresponding_author_id, m.manuscript_no
            FROM manuscripts m
            WHERE m.id = ? AND m.manuscript_file IS NOT NULL AND m.manuscript_file != ""
            LIMIT 1
        ');
        $stmtM->execute([$fileId]);
        $file = $stmtM->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e3) {
        $file = null;
    }
}

// 5. Fallback check ew_galley_proofs if fileId was passed (e.g. download_manuscript_file.php?id=123)
if (!$file && $fileId) {
    try {
        $stmtG2 = $pdo->prepare('
            SELECT g.id, g.manuscript_id, g.proof_version AS version_no, g.file_path AS relative_path,
                   "galley_proof" AS file_type,
                   SUBSTRING_INDEX(g.file_path, "/", -1) AS original_name,
                   "application/pdf" AS mime_type,
                   m.corresponding_author_id, m.manuscript_no
            FROM ew_galley_proofs g
            JOIN manuscripts m ON m.id = g.manuscript_id
            WHERE g.id = ?
            LIMIT 1
        ');
        $stmtG2->execute([$fileId]);
        $file = $stmtG2->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $eG2) {
        $file = null;
    }
}

if (!$file) {
    http_response_code(404);
    exit('Manuscript file record not found.');
}

$role = (string)($u['role'] ?? '');
$isStaff = in_array($role, ['admin', 'editor_in_chief', 'editor', 'managing_editor', 'production'], true);

$isAuthorOwner = false;
if ($role === 'author') {
    if ((int)($file['corresponding_author_id'] ?? 0) === (int)$u['id'] || (int)($file['author_id'] ?? 0) === (int)$u['id']) {
        $isAuthorOwner = true;
    } else {
        // Check manuscript_authors table as fallback
        try {
            $maCheck = $pdo->prepare("SELECT id FROM manuscript_authors WHERE manuscript_id = ? AND LOWER(email) = LOWER(?) LIMIT 1");
            $maCheck->execute([(int)$file['manuscript_id'], (string)($u['email'] ?? '')]);
            if ($maCheck->fetch()) {
                $isAuthorOwner = true;
            }
        } catch (Throwable $eMA) {}
    }
}

$isAssignedReviewer = false;
if ($role === 'reviewer') {
    $revCheck = $pdo->prepare(
        "SELECT ra.id
         FROM ew_reviewer_assignments ra
         JOIN ew_reviewer_pool rp ON rp.id = ra.reviewer_id
         WHERE ra.manuscript_id = ?
           AND (rp.user_id = ? OR LOWER(rp.email) = LOWER(?))
           AND ra.status <> 'cancelled'
         LIMIT 1"
    );
    $revCheck->execute([(int)$file['manuscript_id'], (int)$u['id'], (string)($u['email'] ?? '')]);
    if ($revCheck->fetch()) {
        $isAssignedReviewer = true;
    }
}

if (!$isStaff && !$isAuthorOwner && !$isAssignedReviewer) {
    http_response_code(403);
    exit('Access denied. You do not have authorization to access this file.');
}

// Confidentiality: Authors must NOT access reviewer annotated files
if ($isAuthorOwner && in_array((string)$file['file_type'], ['reviewer_annotated', 'annotated_review'], true)) {
    http_response_code(403);
    exit('Access denied. Reviewer annotations are confidential to the editorial office.');
}

$base = realpath(__DIR__);
$targetPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$file['relative_path']));

if (!$targetPath || !is_file($targetPath)) {
    http_response_code(404);
    exit('The requested manuscript file was not found on disk.');
}

// Strict directory traversal prevention
$prefix = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
if (strncmp($targetPath, $prefix, strlen($prefix)) !== 0) {
    http_response_code(403);
    exit('Access forbidden.');
}

$mime = (string)($file['mime_type'] ?: '');
if ($mime === '') {
    $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
    $mime = match($ext) {
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        default => 'application/octet-stream',
    };
}
$downloadName = basename((string)$file['original_name']);

// Double-Blind Review protection: Do not expose author names in filename to reviewer
if ($isAssignedReviewer && !$isStaff) {
    $ext = pathinfo((string)$file['original_name'], PATHINFO_EXTENSION);
    $typeLabel = str_replace([' ', '_'], '-', (string)$file['file_type']);
    $downloadName = $file['manuscript_no'] . '_' . $typeLabel . ($ext ? '.' . $ext : '');
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($targetPath));
header('Content-Disposition: inline; filename="' . str_replace(['"', "\r", "\n"], '_', $downloadName) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($targetPath);
exit;

