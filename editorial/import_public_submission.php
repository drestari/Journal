
<?php
/**
 * import_public_submission.php
 * Imports a public submission into the editorial manuscripts table.
 *
 * Fixed:
 *   - manuscripts.abstract_text → manuscripts.abstract (correct column name)
 *   - manuscript_files → manuscript_versions (table that actually exists)
 *   - manuscript_authors INSERT uses correct column names (author_name not full_name)
 */
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/journal_submission_bridge.php';
$u = role_required(['admin','editor_in_chief','editor']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(403); exit('Invalid CSRF token'); }

$id  = (int)($_POST['submission_id'] ?? 0);
$src = get_public_submission($id);
if (!$src) { http_response_code(404); exit('Public submission not found.'); }

$pdo = db();
$pdo->beginTransaction();
try {
    // Check not already imported
    $q = $pdo->prepare('SELECT id FROM manuscripts WHERE manuscript_no=? OR source_public_submission_id=? LIMIT 1');
    $q->execute([$src['manuscript_no'], $id]);
    if ($q->fetchColumn()) {
        $pdo->rollBack();
        header('Location: new_submission_view.php?id='.$id.'&msg=already_imported');
        exit;
    }

    // Resolve user by email
    $uid   = null;
    $email = trim((string)($src['corresponding_email'] ?? ''));
    if ($email !== '') {
        $q = $pdo->prepare('SELECT id FROM users WHERE email=? AND active=1 LIMIT 1');
        $q->execute([$email]);
        $uid = $q->fetchColumn() ?: null;
    }

    $submitted = $src['submitted_at'] ?? date('Y-m-d H:i:s');

    // INSERT into manuscripts — use 'abstract' column (not abstract_text)
    $ins = $pdo->prepare('
        INSERT INTO manuscripts
            (source_public_submission_id, manuscript_no, title, article_type,
             abstract, keywords, corresponding_author_id, corresponding_email,
             status, version_no, submitted_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
    ');
    $ins->execute([
        $id,
        $src['manuscript_no'],
        $src['title'],
        $src['article_type'] ?? null,
        $src['abstract_text'] ?? null,   // public form stores it as abstract_text; map to abstract column
        $src['keywords']      ?? null,
        $uid,
        $email !== '' ? $email : null,
        'SUBMITTED',
        1,
        $submitted,
    ]);
    $mid = (int)$pdo->lastInsertId();

    // Insert authors into manuscript_authors
    // Columns: manuscript_id, author_user_id, author_name, affiliation, email, author_order
    $ia = $pdo->prepare('
        INSERT INTO manuscript_authors
            (manuscript_id, author_order, author_name, email, affiliation)
        VALUES (?,?,?,?,?)
    ');
    foreach (($src['authors'] ?? []) as $i => $a) {
        $full = trim(implode(' ', array_filter([
            $a['given_name']  ?? '',
            $a['middle_name'] ?? '',
            $a['family_name'] ?? '',
        ])));
        $aff = trim(implode(', ', array_filter([
            $a['department']  ?? '',
            $a['institution'] ?? '',
            $a['city']        ?? '',
            $a['state']       ?? '',
            $a['country']     ?? '',
        ])));
        $ia->execute([
            $mid,
            $a['author_order'] ?? ($i + 1),
            $full !== '' ? $full : 'Author ' . ($i + 1),
            $a['email'] ?? null,
            $aff,
        ]);
    }

    // Insert files into manuscript_versions (manuscript_files does not exist)
    // manuscript_versions columns: manuscript_id, version_no, file_path, author_response, uploaded_by
    $if = $pdo->prepare('
        INSERT INTO manuscript_versions
            (manuscript_id, version_no, file_path, uploaded_by)
        VALUES (?,?,?,?)
    ');
    foreach (($src['files'] ?? []) as $f) {
        $stored = (string)($f['stored_name'] ?? '');
        $rel    = (string)($f['relative_path'] ?? ('submissiondocs/' . $src['manuscript_no'] . '/' . $stored));
        $if->execute([$mid, 1, $rel, $uid]);
    }

    // Audit log
    $audit = $pdo->prepare('INSERT INTO audit_log (user_id,manuscript_id,action,details,ip_address) VALUES (?,?,?,?,?)');
    $audit->execute([
        $u['id'], $mid, 'public_submission_imported',
        'Imported public submission ' . $src['manuscript_no'] . ' (source ID ' . $id . ')',
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    $pdo->commit();
    header('Location: new_submission_view.php?id=' . $id . '&msg=imported');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('AJSMR import failed: ' . $e->getMessage());
    http_response_code(500);
    echo 'Import failed: ' . htmlspecialchars($e->getMessage());
}
