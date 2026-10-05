<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/workflow_v1_common.php';

$u = login_required();
$pdo = db();

$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
if (!$id && isset($_POST['manuscript_id'])) {
    $id = filter_var($_POST['manuscript_id'], FILTER_VALIDATE_INT);
}
if (!$id) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid manuscript ID.']);
        exit;
    }
    http_response_code(400);
    exit('Invalid manuscript ID.');
}

// Fetch manuscript
$stmt = $pdo->prepare('
    SELECT m.*, m.abstract AS abstract_text,
           u.full_name AS author_name, u.email AS author_email, u.email AS corresponding_email,
           u.affiliation AS author_affiliation, u.orcid AS author_orcid
    FROM manuscripts m
    LEFT JOIN users u ON u.id = m.corresponding_author_id
    WHERE m.id = ?
    LIMIT 1
');
$stmt->execute([$id]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$m) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Manuscript not found.']);
        exit;
    }
    http_response_code(404);
    exit('Manuscript not found.');
}

$role = (string)($u['role'] ?? '');
$isStaff = in_array($role, ['admin', 'editor_in_chief', 'editor', 'managing_editor', 'production'], true);
$isAuthorOwner = ($role === 'author' && (int)$m['corresponding_author_id'] === (int)$u['id']);

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
    $revCheck->execute([$id, (int)$u['id'], (string)($u['email'] ?? '')]);
    if ($revCheck->fetch()) {
        $isAssignedReviewer = true;
    }
}

if (!$isStaff && $isAuthorOwner) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header('Location: ' . BASE_URL . 'author/workflow.php?id=' . $id);
        exit;
    }
}

if (!$isStaff && !$isAuthorOwner && !$isAssignedReviewer) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied. You do not have permission to access this manuscript.']);
        exit;
    }
    http_response_code(403);
    exit('Access denied. You do not have permission to view this manuscript.');
}

// -------------------------------------------------------------------------
// Helper: Send Author Notification Email
// -------------------------------------------------------------------------
if (!function_exists('sendAuthorNotification')) {
    function sendAuthorNotification(array $m, string $subject, string $body): void {
        $authorEmail = !empty($m['corresponding_email']) ? (string)$m['corresponding_email'] : (!empty($m['author_email']) ? (string)$m['author_email'] : '');
        if (empty($authorEmail)) {
            return;
        }
        $headers = "From: editorajsmr@gmail.com\r\nReply-To: editorajsmr@gmail.com\r\nX-Mailer: PHP/" . phpversion();
        @mail($authorEmail, $subject, $body, $headers);
    }
}

// -------------------------------------------------------------------------
// Helper: Send Reviewer Notification Email
// -------------------------------------------------------------------------
if (!function_exists('sendReviewerNotification')) {
    function sendReviewerNotification(string $toEmail, string $subject, string $body): void {
        if (empty($toEmail)) {
            return;
        }
        $headers = "From: editorajsmr@gmail.com\r\nReply-To: editorajsmr@gmail.com\r\nX-Mailer: PHP/" . phpversion();
        @mail($toEmail, $subject, $body, $headers);
    }
}

// -------------------------------------------------------------------------
// POST Handlers (Author Revision Upload or Staff Actions)
// -------------------------------------------------------------------------
$requestMethod = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($requestMethod === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));

    // Handle Author Revision Upload
    if ($action === 'author_upload_revision') {
        if (!$isAuthorOwner && !$isStaff) {
            http_response_code(403);
            exit('Access denied. Only the author or editorial staff can upload revisions.');
        }

        // CSRF check
        check_csrf();

        $authorResponse = trim((string)($_POST['response_to_reviewers'] ?? ''));
        $file = $_FILES['revised_file'] ?? null;

        if (!$file || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'Please select a valid revised manuscript document file.']);
                exit;
            }
            $_SESSION['wf_flash'] = 'Please select a valid revised manuscript file.';
            redirect('manuscript_view.php?id=' . $id);
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_MANUSCRIPT_EXT, true)) {
            $err = 'Invalid file type. Only PDF, DOC, or DOCX files are permitted.';
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => $err]);
                exit;
            }
            $_SESSION['wf_flash'] = $err;
            redirect('manuscript_view.php?id=' . $id);
        }

        if ((int)$file['size'] > MAX_UPLOAD_BYTES) {
            $err = 'File size exceeds maximum limit of 25 MB.';
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => $err]);
                exit;
            }
            $_SESSION['wf_flash'] = $err;
            redirect('manuscript_view.php?id=' . $id);
        }

        if ((int)$file['size'] <= 0) {
            $err = 'Revised manuscript file cannot be empty.';
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => $err]);
                exit;
            }
            $_SESSION['wf_flash'] = $err;
            redirect('manuscript_view.php?id=' . $id);
        }

        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'manuscripts';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            http_response_code(500);
            exit('Failed to initialize storage directory.');
        }

        $targetVersion = (int)$m['version_no'] + 1;
        $storedName = $m['manuscript_no'] . '_v' . $targetVersion . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;

        if (!move_uploaded_file((string)$file['tmp_name'], $destPath)) {
            http_response_code(500);
            exit('Failed to save uploaded revised manuscript.');
        }

        $originalName = basename((string)$file['name']);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($destPath) ?: ($file['type'] ?? 'application/octet-stream');
        $fileSize = filesize($destPath) ?: (int)$file['size'];
        $sha256 = hash_file('sha256', $destPath) ?: null;
        $relativePath = 'uploads/manuscripts/' . $storedName;

        try {
            $pdo->beginTransaction();

            // 1. Insert file into manuscript_versions (manuscript_files table does not exist)
            $stmtMV = $pdo->prepare("
                INSERT INTO manuscript_versions (
                    manuscript_id, version_no, file_path, author_response, uploaded_by, created_at
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmtMV->execute([$id, $targetVersion, $relativePath, $authorResponse, $u['id']]);

            // 2. Update ew_revisions status = 'received' and store author response
            $stmtRev = $pdo->prepare("
                UPDATE ew_revisions
                SET status = 'received', received_at = NOW(), response_text = ?
                WHERE manuscript_id = ? AND status = 'requested'
                ORDER BY version_no DESC LIMIT 1
            ");
            $stmtRev->execute([$authorResponse, $id]);

            // 4. Update manuscript status and version_no
            $newStatus = 'revised_submission';
            $stmtUp = $pdo->prepare("
                UPDATE manuscripts
                SET version_no = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmtUp->execute([$targetVersion, $newStatus, $id]);

            // 5. Audit logs
            audit('revised_document_submitted', $id, "Author submitted revised document Version $targetVersion.");
            $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)")
                ->execute([$id, $u['id'], 'revised_document_submitted', "Revised document Version $targetVersion uploaded: $originalName", $_SERVER['REMOTE_ADDR'] ?? null]);

            $pdo->commit();

            sendWorkflowNotification($pdo, 'REVISION_SUBMITTED', $id, [
                'version_no' => $targetVersion,
                'comments' => $authorResponse
            ]);

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => 'Revised manuscript Version ' . $targetVersion . ' submitted successfully. The Editor-in-Chief has been notified.',
                    'new_status' => $newStatus,
                    'version_no' => $targetVersion
                ]);
                exit;
            }

            $_SESSION['wf_flash'] = 'Revised manuscript Version ' . $targetVersion . ' submitted successfully for EIC review.';
            redirect('manuscript_view.php?id=' . $id);

        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (is_file($destPath)) {
                @unlink($destPath);
            }
            http_response_code(500);
            exit('Server error processing revision submission.');
        }
    }

    // Editorial Staff AJAX POST Actions
    header('Content-Type: application/json; charset=utf-8');

    if (!$isStaff) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied. Editorial staff authorization required.']);
        exit;
    }

    // CSRF check
    $postedCsrf = (string)($_POST['csrf'] ?? '');
    if (empty($_SESSION['csrf']) || !hash_equals((string)$_SESSION['csrf'], $postedCsrf)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Security token invalid or expired. Please refresh the page and try again.']);
        exit;
    }

    try {
        switch ($action) {
            case 'technical_check':
                $allowed = ['passed', 'minor_corrections', 'failed'];
                $result = in_array($_POST['result'] ?? '', $allowed, true) ? $_POST['result'] : '';
                $comments = trim((string)($_POST['comments'] ?? ''));
                $checklist = (array)($_POST['checklist'] ?? []);

                if ($result === '') {
                    echo json_encode(['success' => false, 'error' => 'Please select a valid technical check outcome (Accept, Minor Corrections, or Reject).']);
                    exit;
                }

                // Server-side validation: comments required for minor corrections and reject
                if ($result === 'minor_corrections' && $comments === '') {
                    echo json_encode(['success' => false, 'error' => 'Please provide specific comments detailing the minor corrections required.']);
                    exit;
                }
                if ($result === 'failed' && $comments === '') {
                    echo json_encode(['success' => false, 'error' => 'Please provide the rejection reason in the comments field.']);
                    exit;
                }

                $checklistSummary = '';
                if (!empty($checklist)) {
                    $checklistSummary = "Verified Checks: " . implode(', ', array_map('strip_tags', $checklist));
                }
                $recordedComments = ($checklistSummary !== '')
                    ? ($comments !== '' ? $checklistSummary . "\n\n" . $comments : $checklistSummary)
                    : $comments;

                $pdo->beginTransaction();

                $s = $pdo->prepare("
                    INSERT INTO ew_technical_checks (manuscript_id, checked_by, result, comments, checked_at)
                    VALUES (?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE checked_by = VALUES(checked_by), result = VALUES(result), comments = VALUES(comments), checked_at = NOW()
                ");
                $s->execute([$id, $u['id'], $result, $recordedComments]);

                $newStatus = $m['status'];
                $msg = 'Technical check saved.';

                if ($result === 'passed') {
                    $newStatus = 'TECHNICAL_CHECK';
                    $pdo->prepare("UPDATE manuscripts SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $id]);
                    $msg = 'Technical check passed: Manuscript accepted for peer review.';
                } elseif ($result === 'failed') {
                    $newStatus = 'REJECTED';
                    $pdo->prepare("UPDATE manuscripts SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $id]);

                    $stmtDec = $pdo->prepare("INSERT INTO ew_editorial_decisions (manuscript_id, decided_by, decision, letter) VALUES (?, ?, 'reject', ?)");
                    $stmtDec->execute([$id, $u['id'], $comments]);

                    $msg = 'Technical check failed: Manuscript has been rejected and author notified.';
                } elseif ($result === 'minor_corrections') {
                    $newStatus = 'REVISION_REQUIRED';
                    $pdo->prepare("UPDATE manuscripts SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $id]);
                    $msg = 'Technical check saved: Minor corrections requested from author.';
                }

                audit('technical_check', $id, "Result: $result");
                $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$id, $u['id'], 'technical_check', "Result: $result" . ($recordedComments ? " | $recordedComments" : ""), $_SERVER['REMOTE_ADDR'] ?? null]);

                $pdo->commit();

                if ($result === 'passed') {
                    sendWorkflowNotification($pdo, 'TECHNICAL_CHECK_PASSED', $id);
                } elseif ($result === 'failed') {
                    sendWorkflowNotification($pdo, 'TECHNICAL_CHECK_FAILED', $id, ['comments' => $comments]);
                } elseif ($result === 'minor_corrections') {
                    sendWorkflowNotification($pdo, 'TECHNICAL_CHECK_CORRECTION', $id, ['comments' => $comments]);
                }

                echo json_encode([
                    'success' => true,
                    'message' => $msg,
                    'new_status' => $newStatus,
                    'status_label' => slabel($newStatus)
                ]);
                exit;

            case 'assign_reviewer':
                $rid = (int)($_POST['reviewer_id'] ?? 0);
                $dueRaw = trim((string)($_POST['due_at'] ?? ''));
                $due = ($dueRaw !== '') ? date('Y-m-d H:i:s', strtotime($dueRaw)) : null;
                $instructions = trim((string)($_POST['instructions'] ?? ''));

                if ($rid <= 0) {
                    echo json_encode(['success' => false, 'error' => 'Please select a valid reviewer from the dropdown list.']);
                    exit;
                }

                $rStmt = $pdo->prepare("SELECT * FROM ew_reviewer_pool WHERE id = ? AND active = 1 LIMIT 1");
                $rStmt->execute([$rid]);
                $rev = $rStmt->fetch(PDO::FETCH_ASSOC);
                if (!$rev) {
                    echo json_encode(['success' => false, 'error' => 'Selected reviewer was not found or is inactive.']);
                    exit;
                }

                // Prevent duplicate active assignment
                $chk = $pdo->prepare("SELECT id FROM ew_reviewer_assignments WHERE manuscript_id = ? AND reviewer_id = ? AND status <> 'cancelled' LIMIT 1");
                $chk->execute([$id, $rid]);
                if ($chk->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'This reviewer is already actively assigned to this manuscript.']);
                    exit;
                }

                $pdo->beginTransaction();
                $ins = $pdo->prepare("
                    INSERT INTO ew_reviewer_assignments (manuscript_id, reviewer_id, assigned_by, due_at, status, invited_at)
                    VALUES (?, ?, ?, ?, 'invited', NOW())
                ");
                $ins->execute([$id, $rid, $u['id'], $due]);
                $assignmentId = (int)$pdo->lastInsertId();

                $newStatus = 'UNDER_REVIEW';
                $pdo->prepare("UPDATE manuscripts SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $id]);

                audit('reviewer_assigned', $id, "Reviewer {$rev['full_name']} (ID: $rid) assigned.");
                $logMsg = "Assigned Reviewer {$rev['full_name']} ({$rev['email']})" . ($instructions ? ". Instructions: $instructions" : "");
                $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$id, $u['id'], 'reviewer_assigned', $logMsg, $_SERVER['REMOTE_ADDR'] ?? null]);

                $pdo->commit();

                sendWorkflowNotification($pdo, 'REVIEWER_INVITED', $id, [
                    'reviewer_id'   => $rid,
                    'due_at'        => $dueRaw,
                    'assignment_id' => $assignmentId
                ]);
                sendWorkflowNotification($pdo, 'REVIEW_STARTED', $id);

                echo json_encode([
                    'success' => true,
                    'message' => 'Reviewer assigned successfully. Manuscript status updated to Under Review.',
                    'new_status' => $newStatus,
                    'status_label' => 'Under Review',
                    'reviewer' => [
                        'id' => $assignmentId,
                        'name' => $rev['full_name'],
                        'email' => $rev['email'],
                        'affiliation' => $rev['affiliation'] ?: '—',
                        'due_at' => $dueRaw ?: 'Not specified',
                        'invited_at' => date('Y-m-d H:i:s'),
                        'status' => 'Assigned / Awaiting Report'
                    ]
                ]);
                exit;

            case 'request_revision':
                $revType = trim((string)($_POST['revision_type'] ?? 'minor_revision'));
                if (!in_array($revType, ['minor_revision', 'major_revision'], true)) {
                    $revType = 'minor_revision';
                }
                $responseText = trim((string)($_POST['response_text'] ?? ''));
                if ($responseText === '') {
                    echo json_encode(['success' => false, 'error' => 'Please provide detailed revision comments and instructions for the author.']);
                    exit;
                }
                $dueDate = trim((string)($_POST['due_date'] ?? ''));
                if ($dueDate !== '') {
                    $fullInstructions = $responseText . "\n\nRevision Deadline: " . $dueDate;
                } else {
                    $fullInstructions = $responseText;
                }

                $pdo->beginTransaction();
                $nextVersion = (int)$m['version_no'] + 1;

                $stmt = $pdo->prepare("INSERT INTO ew_revisions (manuscript_id, version_no, requested_by, response_text, status, requested_at) VALUES (?, ?, ?, ?, 'requested', NOW())");
                $stmt->execute([$id, $nextVersion, $u['id'], $fullInstructions]);

                $pdo->prepare("UPDATE manuscripts SET status = 'REVISION_REQUIRED', updated_at = NOW() WHERE id = ?")->execute([$id]);

                audit('revision_requested', $id, "Revision ($revType) Version $nextVersion requested.");
                $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$id, $u['id'], 'revision_requested', "Type: $revType, Target Version: $nextVersion. Instructions: $fullInstructions", $_SERVER['REMOTE_ADDR'] ?? null]);

                $pdo->commit();

                sendWorkflowNotification($pdo, 'REVISION_REQUESTED', $id, [
                    'revision_type' => $revType,
                    'letter'        => $responseText,
                    'due_date'      => $dueDate
                ]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Revision request sent successfully to author.',
                    'new_status' => $revType,
                    'status_label' => slabel($revType),
                    'target_version' => $nextVersion
                ]);
                exit;

            case 'review_revised_document':
                $outcome = trim((string)($_POST['revision_outcome'] ?? ''));
                $comments = trim((string)($_POST['eic_comments'] ?? ''));

                if (!in_array($outcome, ['accept', 'reject', 'further_revision'], true)) {
                    echo json_encode(['success' => false, 'error' => 'Please select an evaluation outcome (Accept, Reject, or Request Further Revision).']);
                    exit;
                }

                if (($outcome === 'reject' || $outcome === 'further_revision') && $comments === '') {
                    echo json_encode(['success' => false, 'error' => 'Please enter comments / feedback explaining your decision.']);
                    exit;
                }

                $pdo->beginTransaction();

                if ($outcome === 'accept') {
                    $newStatus = 'accepted';
                    $pdo->prepare("UPDATE manuscripts SET status = ?, accepted_at = NOW(), updated_at = NOW() WHERE id = ?")->execute([$newStatus, $id]);

                    $stmt = $pdo->prepare("INSERT INTO ew_editorial_decisions (manuscript_id, decided_by, decision, letter, created_at) VALUES (?, ?, 'accept', ?, NOW())");
                    $stmt->execute([$id, $u['id'], $comments]);

                    // Update revision status
                    $pdo->prepare("UPDATE ew_revisions SET status = 'accepted' WHERE manuscript_id = ? ORDER BY version_no DESC LIMIT 1")->execute([$id]);

                    // Update production table
                    try {
                        $pdo->prepare("
                            INSERT INTO production (manuscript_id)
                            VALUES (?)
                            ON DUPLICATE KEY UPDATE manuscript_id = VALUES(manuscript_id)
                        ")->execute([$id]);
                    } catch (Throwable $eProd) {}

                    audit('revised_doc_accepted', $id, "Revised document accepted for publication.");
                    $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)")
                        ->execute([$id, $u['id'], 'revised_doc_accepted', "Revised document accepted. Comments: $comments", $_SERVER['REMOTE_ADDR'] ?? null]);

                    $msg = 'Revised document reviewed and ACCEPTED. Manuscript transitioned to Production.';

                } elseif ($outcome === 'reject') {
                    $newStatus = 'rejected';
                    $pdo->prepare("UPDATE manuscripts SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $id]);

                    $stmt = $pdo->prepare("INSERT INTO ew_editorial_decisions (manuscript_id, decided_by, decision, letter, created_at) VALUES (?, ?, 'reject', ?, NOW())");
                    $stmt->execute([$id, $u['id'], $comments]);

                    $pdo->prepare("UPDATE ew_revisions SET status = 'rejected' WHERE manuscript_id = ? ORDER BY version_no DESC LIMIT 1")->execute([$id]);

                    audit('revised_doc_rejected', $id, "Revised document rejected.");
                    $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)")
                        ->execute([$id, $u['id'], 'revised_doc_rejected', "Revised document rejected. Reason: $comments", $_SERVER['REMOTE_ADDR'] ?? null]);

                    $msg = 'Revised document evaluated and REJECTED. Rejection notification sent to author.';

                } else { // further_revision
                    $newStatus = 'minor_revision';
                    $nextVer = (int)$m['version_no'] + 1;

                    $stmt = $pdo->prepare("INSERT INTO ew_revisions (manuscript_id, version_no, requested_by, response_text, status, requested_at) VALUES (?, ?, ?, ?, 'requested', NOW())");
                    $stmt->execute([$id, $nextVer, $u['id'], $comments]);

                    $pdo->prepare("UPDATE manuscripts SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $id]);

                    audit('further_revision_requested', $id, "Further revision requested (Target Version: $nextVer).");
                    $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)")
                        ->execute([$id, $u['id'], 'further_revision_requested', "Further revision requested: $comments", $_SERVER['REMOTE_ADDR'] ?? null]);

                    $msg = 'Further revision requested from author.';
                }

                $pdo->commit();

                if ($outcome === 'accept') {
                    sendWorkflowNotification($pdo, 'MANUSCRIPT_ACCEPTED', $id, ['letter' => $comments]);
                } elseif ($outcome === 'reject') {
                    sendWorkflowNotification($pdo, 'MANUSCRIPT_REJECTED', $id, ['letter' => $comments]);
                } else {
                    sendWorkflowNotification($pdo, 'REVISION_REQUESTED', $id, ['revision_type' => 'minor_revision', 'letter' => $comments]);
                }

                echo json_encode([
                    'success' => true,
                    'message' => $msg,
                    'new_status' => $newStatus,
                    'status_label' => slabel($newStatus)
                ]);
                exit;

            case 'decision':
            case 'accept':
            case 'reject':
                $decision = ($action === 'accept') ? 'accept' : (($action === 'reject') ? 'reject' : trim((string)($_POST['decision'] ?? '')));
                $letter = trim((string)($_POST['letter'] ?? ''));

                if (!in_array($decision, ['accept', 'minor_revision', 'major_revision', 'reject'], true)) {
                    echo json_encode(['success' => false, 'error' => 'Please select a valid editorial decision.']);
                    exit;
                }

                if ($decision === 'reject' && $letter === '') {
                    echo json_encode(['success' => false, 'error' => 'Rejection reason / comments to the author are strictly required.']);
                    exit;
                }

                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO ew_editorial_decisions (manuscript_id, decided_by, decision, letter, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$id, $u['id'], $decision, $letter]);

                $newStatus = ($decision === 'accept') ? 'accepted' : (($decision === 'reject') ? 'rejected' : $decision);

                if ($decision === 'accept') {
                    $pdo->prepare("UPDATE manuscripts SET status = ?, accepted_at = NOW(), updated_at = NOW() WHERE id = ?")->execute([$newStatus, $id]);

                    try {
                        $pdo->prepare("
                            INSERT INTO production (manuscript_id)
                            VALUES (?)
                            ON DUPLICATE KEY UPDATE manuscript_id = VALUES(manuscript_id)
                        ")->execute([$id]);
                    } catch (Throwable $eProd) {}

                    audit('manuscript_accepted', $id, "Manuscript accepted for publication.");
                    $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)")
                        ->execute([$id, $u['id'], 'manuscript_accepted', "Accepted. Letter: $letter", $_SERVER['REMOTE_ADDR'] ?? null]);

                    $msg = 'Manuscript accepted successfully. Forwarded to Production.';

                } elseif ($decision === 'reject') {
                    $pdo->prepare("UPDATE manuscripts SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $id]);

                    audit('manuscript_rejected', $id, "Manuscript rejected.");
                    $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)")
                        ->execute([$id, $u['id'], 'manuscript_rejected', "Rejected. Reason: $letter", $_SERVER['REMOTE_ADDR'] ?? null]);

                    $msg = 'Manuscript rejected. Rejection notification delivered to author.';

                } else {
                    $pdo->prepare("UPDATE manuscripts SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $id]);
                    audit('editorial_decision', $id, "Decision: $decision");
                    $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)")
                        ->execute([$id, $u['id'], 'editorial_decision', "Decision: $decision. Letter: $letter", $_SERVER['REMOTE_ADDR'] ?? null]);
                    $msg = 'Editorial decision recorded: ' . slabel($decision);
                }

                $pdo->commit();

                if ($decision === 'accept') {
                    sendWorkflowNotification($pdo, 'MANUSCRIPT_ACCEPTED', $id, ['letter' => $letter]);
                } elseif ($decision === 'reject') {
                    sendWorkflowNotification($pdo, 'MANUSCRIPT_REJECTED', $id, ['letter' => $letter]);
                } else {
                    sendWorkflowNotification($pdo, 'EDITORIAL_DECISION', $id, ['decision' => $decision, 'letter' => $letter]);
                }

                echo json_encode([
                    'success' => true,
                    'message' => $msg,
                    'new_status' => $newStatus,
                    'status_label' => slabel($newStatus)
                ]);
                exit;

            case 'assign_editor':
                $eid = (int)($_POST['editor_id'] ?? 0);
                if ($eid <= 0) {
                    echo json_encode(['success' => false, 'error' => 'Please select an editor from the list.']);
                    exit;
                }

                $edStmt = $pdo->prepare("SELECT id, email, full_name, role FROM users WHERE id = ? AND active = 1 AND role IN ('editor', 'editor_in_chief') LIMIT 1");
                $edStmt->execute([$eid]);
                $editorUser = $edStmt->fetch(PDO::FETCH_ASSOC);
                if (!$editorUser) {
                    echo json_encode(['success' => false, 'error' => 'Selected editor account not found or inactive.']);
                    exit;
                }

                $pdo->beginTransaction();
                $pdo->prepare("UPDATE ew_editor_assignments SET status = 'ended', ended_at = NOW() WHERE manuscript_id = ? AND status = 'active'")->execute([$id]);
                $pdo->prepare("INSERT INTO ew_editor_assignments (manuscript_id, editor_id, assigned_by) VALUES (?, ?, ?)")->execute([$id, $eid, $u['id']]);

                $newStatus = 'ASSIGNED_TO_EDITOR';
                $pdo->prepare("UPDATE manuscripts SET status = ?, assigned_editor_id = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $eid, $id]);

                audit('editor_assigned', $id, "Editor {$editorUser['email']} assigned.");
                $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$id, $u['id'], 'editor_assigned', "Editor assigned: {$editorUser['email']}", $_SERVER['REMOTE_ADDR'] ?? null]);

                $pdo->commit();

                sendWorkflowNotification($pdo, 'EDITOR_ASSIGNED', $id, ['editor_id' => $eid]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Editor assigned successfully: ' . ($editorUser['full_name'] ?: $editorUser['email']),
                    'new_status' => $newStatus,
                    'status_label' => 'Editor Assigned'
                ]);
                exit;

            case 'send_email':
                $subject = trim((string)($_POST['subject'] ?? ''));
                $message = trim((string)($_POST['message'] ?? ''));
                if ($subject === '' || $message === '') {
                    echo json_encode(['success' => false, 'error' => 'Subject and Message cannot be empty.']);
                    exit;
                }

                $authorEmail = !empty($m['corresponding_email']) ? (string)$m['corresponding_email'] : (!empty($m['author_email']) ? (string)$m['author_email'] : '');
                if (empty($authorEmail)) {
                    $cStmt = $pdo->prepare('SELECT email FROM manuscript_authors WHERE manuscript_id = ? ORDER BY author_order ASC, id ASC LIMIT 1');
                    $cStmt->execute([$id]);
                    $authorEmail = (string)($cStmt->fetchColumn() ?: '');
                }
                if (empty($authorEmail)) {
                    echo json_encode(['success' => false, 'error' => 'No author email address found for this manuscript.']);
                    exit;
                }

                sendAuthorNotification($m, $subject, $message);

                audit('email_sent', $id, "Email sent to $authorEmail. Subject: $subject");
                $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$id, $u['id'], 'email_sent', "To: $authorEmail | Subject: $subject | Body: $message", $_SERVER['REMOTE_ADDR'] ?? null]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Email sent successfully to author (' . htmlspecialchars($authorEmail, ENT_QUOTES, 'UTF-8') . ').'
                ]);
                exit;

            case 'add_note':
                $note = trim((string)($_POST['note'] ?? ''));
                if ($note === '') {
                    echo json_encode(['success' => false, 'error' => 'Note text cannot be empty.']);
                    exit;
                }

                audit('editorial_note', $id, $note);
                $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$id, $u['id'], 'editorial_note', $note, $_SERVER['REMOTE_ADDR'] ?? null]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Editorial note saved successfully.',
                    'note' => [
                        'text' => $note,
                        'user' => $u['email'] ?? 'Staff',
                        'date' => date('Y-m-d H:i:s')
                    ]
                ]);
                exit;

            case 'gallery_proof':
                $proofComments = trim((string)($_POST['proof_comments'] ?? ''));

                if (empty($_FILES['proof_file']['name']) || (int)($_FILES['proof_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    echo json_encode(['success' => false, 'error' => 'Please select a valid gallery proof document file (PDF, DOC, or DOCX).']);
                    exit;
                }

                $file = $_FILES['proof_file'];
                $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ALLOWED_MANUSCRIPT_EXT, true)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid file format. Allowed formats: PDF, DOC, DOCX.']);
                    exit;
                }

                if ((int)$file['size'] > MAX_UPLOAD_BYTES) {
                    echo json_encode(['success' => false, 'error' => 'File size exceeds 25 MB size limit.']);
                    exit;
                }

                $uploadDir = __DIR__ . '/uploads/proofs';
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                    echo json_encode(['success' => false, 'error' => 'Failed to initialize proof storage directory.']);
                    exit;
                }

                $storedName = 'proof_' . $m['manuscript_no'] . '_v' . $m['version_no'] . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
                $destPath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;

                if (!move_uploaded_file((string)$file['tmp_name'], $destPath)) {
                    echo json_encode(['success' => false, 'error' => 'Failed to save uploaded proof file.']);
                    exit;
                }

                $relativePath = 'uploads/proofs/' . $storedName;

                $pdo->beginTransaction();

                $pCountStmt = $pdo->prepare("SELECT COUNT(*) FROM ew_galley_proofs WHERE manuscript_id = ?");
                $pCountStmt->execute([$id]);
                $proofVer = ((int)$pCountStmt->fetchColumn()) + 1;

                $stmtP = $pdo->prepare("
                    INSERT INTO ew_galley_proofs (
                        manuscript_id, proof_version, file_path, comments, status, created_by, sent_at, created_at
                    ) VALUES (?, ?, ?, ?, 'sent_to_author', ?, NOW(), NOW())
                ");
                $stmtP->execute([$id, $proofVer, $relativePath, $proofComments, $u['id']]);

                $pdo->prepare("UPDATE manuscripts SET status = 'PROOF_SENT', updated_at = NOW() WHERE id = ?")->execute([$id]);

                try {
                    $pdo->prepare("
                        INSERT INTO production (manuscript_id, proof_status)
                        VALUES (?, 'SENT')
                        ON DUPLICATE KEY UPDATE proof_status = 'SENT'
                    ")->execute([$id]);
                } catch (Throwable $eP) {}

                audit('gallery_proof_sent', $id, "Gallery proof version $proofVer sent to author.");
                $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$id, $u['id'], 'gallery_proof_sent', "Gallery proof version $proofVer uploaded ($storedName) and sent to author. Comments: $proofComments", $_SERVER['REMOTE_ADDR'] ?? null]);

                $pdo->commit();

                sendWorkflowNotification($pdo, 'GALLERY_PROOF_SENT', $id, [
                    'proof_version' => $proofVer,
                    'notes'         => $proofComments
                ]);

                echo json_encode(['success' => true, 'message' => 'Gallery proof uploaded and sent to author successfully.']);
                exit;

            // publish_manuscript: now handled by set_publication_status below (reads pub_status radio: IN_PRESS or PUBLISHED)
            case 'publish_manuscript': // falls through to set_publication_status
            case 'set_publication_status':
                if (!$isStaff) {
                    echo json_encode(['success' => false, 'error' => 'Permission denied.']);
                    exit;
                }
                $pubStatus = strtoupper(trim((string)($_POST['pub_status'] ?? '')));
                if (!in_array($pubStatus, ['IN_PRESS', 'PUBLISHED'], true)) {
                    echo json_encode(['success' => false, 'error' => 'Please select a valid publication status (In-Press or Published).']);
                    exit;
                }

                $pdo->beginTransaction();

                if ($pubStatus === 'PUBLISHED') {
                    $pubDate = trim((string)($_POST['publication_date'] ?? date('Y-m-d')));
                    if (empty($pubDate)) $pubDate = date('Y-m-d');
                    $pdo->prepare("UPDATE manuscripts SET status = 'PUBLISHED', published_at = ?, updated_at = NOW() WHERE id = ?")
                        ->execute([$pubDate . ' 00:00:00', $id]);
                } else {
                    // IN_PRESS — clear published_at since not yet fully published
                    $pdo->prepare("UPDATE manuscripts SET status = 'IN_PRESS', published_at = NULL, updated_at = NOW() WHERE id = ?")
                        ->execute([$id]);
                }

                // Update production table
                try {
                    $pdo->prepare("
                        INSERT INTO production (manuscript_id, publication_status)
                        VALUES (?, ?)
                        ON DUPLICATE KEY UPDATE publication_status = VALUES(publication_status)
                    ")->execute([$id, $pubStatus]);
                } catch (Throwable $eProd) {}

                $audit_detail = "Publication status set to: $pubStatus";
                audit('publication_status_changed', $id, $audit_detail);
                $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$id, $u['id'], 'publication_status_changed', $audit_detail, $_SERVER['REMOTE_ADDR'] ?? null]);

                if ($pubStatus === 'PUBLISHED') {
                    // Sync to public articles table
                    try {
                        $prodRow = $pdo->prepare("SELECT * FROM production WHERE manuscript_id = ? LIMIT 1");
                        $prodRow->execute([$id]);
                        $prod = $prodRow->fetch(PDO::FETCH_ASSOC) ?: [];
                        $pdo->prepare("
                            INSERT INTO articles (
                                article_id, article_type, title, abstract, keywords,
                                volume, issue, year, published_date, doi, pdf_file, status, created_at, updated_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published', NOW(), NOW())
                            ON DUPLICATE KEY UPDATE
                                title = VALUES(title), abstract = VALUES(abstract),
                                volume = VALUES(volume), issue = VALUES(issue), year = VALUES(year),
                                published_date = VALUES(published_date), doi = VALUES(doi),
                                pdf_file = COALESCE(VALUES(pdf_file), pdf_file),
                                status = 'published', updated_at = NOW()
                        ")->execute([
                            $m['manuscript_no'],
                            $m['article_type'] ?: 'Research Article',
                            $m['title'],
                            $m['abstract_text'] ?: '',
                            $m['keywords'] ?: '',
                            $prod['volume'] ?? '',
                            $prod['issue'] ?? '',
                            $prod['year'] ?? date('Y'),
                            date('Y-m-d'),
                            $prod['doi'] ?? '',
                            $prod['final_pdf'] ?? ($m['manuscript_file'] ?? '')
                        ]);
                    } catch (Throwable $eArt) {}

                    $msg = 'Manuscript status updated to PUBLISHED. Author notified and article registry synchronized.';
                } else {
                    $msg = 'Manuscript status updated to IN-PRESS.';
                }

                $pdo->commit();

                if ($pubStatus === 'PUBLISHED') {
                    sendWorkflowNotification($pdo, 'PUBLISHED', $id);
                } else {
                    sendWorkflowNotification($pdo, 'IN_PRESS', $id);
                }

                echo json_encode([
                    'success'      => true,
                    'message'      => $msg,
                    'new_status'   => $pubStatus,
                    'status_label' => ($pubStatus === 'IN_PRESS' ? 'In-Press' : 'Published')
                ]);
                exit;

            default:
                echo json_encode(['success' => false, 'error' => 'Unknown action requested.']);
                exit;
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Editorial action failure: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'An error occurred while processing the request: ' . $e->getMessage()]);
        exit;
    }
}

// -------------------------------------------------------------------------
// GET: Load Comprehensive Manuscript & Workflow Data
// -------------------------------------------------------------------------

// Refresh manuscript status
$stmt->execute([$id]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all authors
$authorsStmt = $pdo->prepare('SELECT ma.*, ma.author_name AS full_name FROM manuscript_authors ma WHERE ma.manuscript_id = ? ORDER BY ma.author_order, ma.id');
$authorsStmt->execute([$id]);
$authors = $authorsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all files from manuscript_versions (manuscript_files table does not exist)
$files = [];
try {
    $filesStmt = $pdo->prepare('
        SELECT id, manuscript_id, version_no, file_path, file_path AS relative_path,
               COALESCE(NULLIF(author_response, \'\'), \'main_manuscript\') AS file_type,
               SUBSTRING_INDEX(file_path, \'/\', -1) AS original_name,
               0 AS file_size, created_at AS uploaded_at
        FROM manuscript_versions
        WHERE manuscript_id = ?
        ORDER BY version_no ASC, id ASC
    ');
    $filesStmt->execute([$id]);
    $files = $filesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $eFiles) {
    $files = [];
}

// Fallback: use manuscript_file column from manuscript record if versions list is empty
$mfile = isset($m['manuscript_file']) ? (string)$m['manuscript_file'] : '';
if (empty($files) && $mfile !== '') {
    $files[] = [
        'id'            => 1,
        'manuscript_id' => $id,
        'version_no'    => (int)(isset($m['version_no']) ? $m['version_no'] : 1),
        'file_path'     => $mfile,
        'relative_path' => $mfile,
        'file_type'     => 'main_manuscript',
        'original_name' => basename($mfile),
        'file_size'     => 0,
        'uploaded_at'   => isset($m['submitted_at']) ? $m['submitted_at'] : date('Y-m-d H:i:s'),
    ];
}

// Resolve real file size and original name from disk if missing
foreach ($files as &$f) {
    $relPath = (string)($f['relative_path'] ?? ($f['file_path'] ?? ''));
    if (((int)($f['file_size'] ?? 0)) <= 0 && $relPath !== '') {
        $diskPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relPath, '/\\'));
        if (is_file($diskPath)) {
            $f['file_size'] = (int)filesize($diskPath);
        }
    }
    if (empty($f['original_name']) && $relPath !== '') {
        $f['original_name'] = basename($relPath);
    }
}
unset($f);

// Separate original vs revised files
$originalFiles = [];
$revisedFiles = [];
foreach ($files as $f) {
    if ((int)($f['version_no'] ?? 1) <= 1 && ($f['file_type'] ?? '') !== 'revised_manuscript') {
        $originalFiles[] = $f;
    } else {
        $revisedFiles[] = $f;
    }
}

// Technical Check record
$tcStmt = $pdo->prepare('SELECT * FROM ew_technical_checks WHERE manuscript_id = ? ORDER BY id DESC LIMIT 1');
$tcStmt->execute([$id]);
$existingTechCheck = $tcStmt->fetch(PDO::FETCH_ASSOC) ?: ['result' => 'pending', 'comments' => '', 'checked_at' => null];

// Revisions history
$revStmt = $pdo->prepare('
    SELECT r.*, u.full_name AS requester_name, u.email AS requester_email,
           r.response_text AS response_to_reviewers, r.received_at AS response_submitted_at
    FROM ew_revisions r
    LEFT JOIN users u ON u.id = r.requested_by
    WHERE r.manuscript_id = ?
    ORDER BY r.version_no DESC
');
$revStmt->execute([$id]);
$revisionsList = $revStmt->fetchAll(PDO::FETCH_ASSOC);
$latestRevision = !empty($revisionsList) ? $revisionsList[0] : null;

// Editorial Decisions history
$decStmt = $pdo->prepare('
    SELECT d.*, u.full_name AS decider_name, u.email AS decider_email
    FROM ew_editorial_decisions d
    LEFT JOIN users u ON u.id = d.decided_by
    WHERE d.manuscript_id = ?
    ORDER BY d.id DESC
');
$decStmt->execute([$id]);
$editorialDecisions = $decStmt->fetchAll(PDO::FETCH_ASSOC);
$latestDecision = !empty($editorialDecisions) ? $editorialDecisions[0] : null;

// Reviewers & Reviews (Staff only or reviewer)
$reviewers = [];
$allReviewers = [];
$allEditors = [];
$editorialNotes = [];
$completedReviews = [];

if ($isStaff) {
    $rStmt = $pdo->prepare('
        SELECT ra.*, rp.full_name AS reviewer_name, rp.email AS reviewer_email, rp.affiliation AS reviewer_affiliation, rp.expertise AS reviewer_expertise,
               pr.recommendation, pr.comments_to_editor, pr.comments_to_author, pr.confidential_comments, pr.submitted_at AS review_submitted_at
        FROM ew_reviewer_assignments ra
        JOIN ew_reviewer_pool rp ON rp.id = ra.reviewer_id
        LEFT JOIN ew_peer_reviews pr ON pr.assignment_id = ra.id
        WHERE ra.manuscript_id = ?
        ORDER BY ra.invited_at DESC
    ');
    $rStmt->execute([$id]);
    $reviewers = $rStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($reviewers as $rv) {
        if (!empty($rv['review_submitted_at']) || !empty($rv['recommendation'])) {
            $completedReviews[] = $rv;
        }
    }

    $allReviewers = $pdo->query('
        SELECT id, full_name, email, affiliation, expertise
        FROM ew_reviewer_pool
        WHERE active = 1
        ORDER BY full_name ASC
    ')->fetchAll(PDO::FETCH_ASSOC);

    $allEditors = $pdo->query("
        SELECT id, email, full_name, role
        FROM users
        WHERE active = 1 AND role IN ('editor', 'editor_in_chief')
        ORDER BY email ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $notesStmt = $pdo->prepare("
        SELECT a.*, u.email, u.full_name
        FROM ew_audit_log a
        LEFT JOIN users u ON u.id = a.user_id
        WHERE a.manuscript_id = ? AND a.action = 'editorial_note'
        ORDER BY a.id DESC
    ");
    $notesStmt->execute([$id]);
    $editorialNotes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);
}

// -------------------------------------------------------------------------
// Sequential Workflow State Evaluation
// -------------------------------------------------------------------------
$currentStatus = strtolower((string)$m['status']);

// Step 1: Submission
$step1Done = true;

// Step 2: Technical Check
$step2State = 'pending'; // pending | passed | minor_corrections | failed
if (!empty($existingTechCheck['result']) && $existingTechCheck['result'] !== 'pending') {
    $step2State = $existingTechCheck['result'];
}

// Step 3: Reviewer Assignment
$step3Done = false;
$activeReviewerCount = count($reviewers);
if ($activeReviewerCount > 0) {
    $step3Done = true;
}

// Step 4: Reviewer Report
$step4Done = false;
$step4Count = count($completedReviews);
if ($step4Count > 0) {
    $step4Done = true;
}

// Step 5: Revision
$step5State = 'not_required'; // not_required | requested | received
if (!empty($latestRevision)) {
    if (in_array($latestRevision['status'], ['received', 'under_review', 'accepted', 'rejected'], true) || count($revisedFiles) > 0) {
        $step5State = 'received';
    } elseif ($latestRevision['status'] === 'requested') {
        $step5State = 'requested';
    }
}

// Step 6: Revised Document Review
$step6Done = false;
if ($step5State === 'received') {
    if ($currentStatus === 'accepted' || $currentStatus === 'rejected' || ($latestRevision && in_array($latestRevision['status'], ['accepted', 'rejected'], true))) {
        $step6Done = true;
    }
}

// Step 7: Final Decision
$step7State = 'pending'; // pending | accepted | rejected
if ($currentStatus === 'accepted' || (!empty($latestDecision) && $latestDecision['decision'] === 'accept')) {
    $step7State = 'accepted';
} elseif ($currentStatus === 'rejected' || (!empty($latestDecision) && $latestDecision['decision'] === 'reject')) {
    $step7State = 'rejected';
}

// Button Enablement Conditions (Sequential Workflow)
$canDoTechnicalCheck = ($step7State === 'pending');
$canAssignReviewer = ($step2State === 'passed' && $step7State === 'pending');
$canViewReviewerReport = ($step4Done);
$canRequestRevision = ($step2State === 'passed' && $step7State === 'pending');
$canReviewRevisedDoc = ($step5State === 'received' && $step7State === 'pending');
$canFinalDecision = ($step2State === 'passed' && ($step4Done || $step5State === 'received' || $step3Done) && $step7State === 'pending');

// Proof & Publishing Stage Check
$latestProof = null;
try {
    $stmtP = $pdo->prepare("SELECT * FROM ew_galley_proofs WHERE manuscript_id = ? ORDER BY id DESC LIMIT 1");
    $stmtP->execute([$id]);
    $latestProof = $stmtP->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $eP) {}

$latestAuthorProofAppr = null;
try {
    $stmtAp = $pdo->prepare("SELECT * FROM ew_author_proof_approval WHERE manuscript_id = ? ORDER BY id DESC LIMIT 1");
    $stmtAp->execute([$id]);
    $latestAuthorProofAppr = $stmtAp->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $eAp) {}

// All author proof approvals — for EIC display panel (newest first)
$allAuthorProofApprovals = [];
try {
    $stmtAllAp = $pdo->prepare("
        SELECT apa.*,
               gp.proof_version,
               gp.file_path   AS proof_file_path,
               gp.comments    AS proof_eic_comments,
               u.full_name    AS author_full_name,
               u.email        AS author_email
        FROM ew_author_proof_approval apa
        LEFT JOIN ew_galley_proofs gp ON gp.id = apa.proof_id
        LEFT JOIN users u ON u.id = apa.author_id
        WHERE apa.manuscript_id = ?
        ORDER BY apa.id DESC
    ");
    $stmtAllAp->execute([$id]);
    $allAuthorProofApprovals = $stmtAllAp->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $eAllAp) {}

$step8State = 'pending';
if ($latestProof) {
    if (!empty($latestAuthorProofAppr['approved_at']) || ($latestAuthorProofAppr['decision'] ?? '') === 'approved' || ($latestProof['status'] ?? '') === 'approved' || $currentStatus === 'PROOF_APPROVED') {
        $step8State = 'approved';
    } elseif (!empty($latestProof['sent_at']) || ($latestProof['status'] ?? '') === 'sent_to_author' || ($latestAuthorProofAppr['decision'] ?? '') === 'corrections_requested' || $currentStatus === 'PROOF_SENT' || $currentStatus === 'PROOF_RESPONSE_RECEIVED') {
        $step8State = 'sent';
    }
}

$step9State = 'pending';
if ($currentStatus === 'IN_PRESS' || $currentStatus === 'in_press') {
    $step9State = 'in_press';
} elseif ($currentStatus === 'PUBLISHED' || $currentStatus === 'published') {
    $step9State = 'published';
}

// Fetch current publication_status from production table for the modal default
$currentPubStatus = null;
try {
    $pubStmt = $pdo->prepare("SELECT publication_status FROM production WHERE manuscript_id = ? LIMIT 1");
    $pubStmt->execute([$id]);
    $currentPubStatus = $pubStmt->fetchColumn() ?: null;
} catch (Throwable $ePub) {}
// Fall back to manuscripts.status for the display default
if ($currentPubStatus === null) {
    if ($step9State === 'in_press')  $currentPubStatus = 'IN_PRESS';
    elseif ($step9State === 'published') $currentPubStatus = 'PUBLISHED';
}

$canGalleryProof = ($step7State === 'accepted' && !in_array($step9State, ['in_press', 'published'], true));
// Printing/Publishing button: active once proof has been sent AND manuscript is accepted; also stays active for IN_PRESS and PUBLISHED
$canPublish = ($step7State === 'accepted' && (
    $step8State === 'approved' || $step8State === 'sent' ||
    in_array($currentStatus, ['PROOF_APPROVED','PROOF_SENT','PROOF_RESPONSE_RECEIVED','IN_PRESS','PUBLISHED'], true)
));
$isPublished = ($step9State === 'published');

$authorEmailDisplay = !empty($m['corresponding_email'])
    ? (string)$m['corresponding_email']
    : (!empty($m['author_email'])
        ? (string)$m['author_email']
        : (!empty($authors[0]['email']) ? (string)$authors[0]['email'] : ''));
$pageTitle = 'Manuscript ' . $m['manuscript_no'] . ' — AJSMR Editorial';

// =========================================================================
// RENDER VIEW
// =========================================================================
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($pageTitle)?></title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#f4f7fb;color:#25344a;font-family:Arial,Helvetica,sans-serif}
.top{background:linear-gradient(135deg,#092b5f,#0b5fa5);color:#fff;padding:20px 30px}
.top-inner{max-width:1250px;margin:auto;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
.brand{font-size:20px;font-weight:800}
.brand small{display:block;font-size:12px;font-weight:400;margin-top:3px;color:#dbeafe}
.nav-links a{color:#fff;text-decoration:none;border:1px solid #ffffff55;border-radius:6px;padding:7px 12px;font-size:13px;margin-left:8px}
.nav-links a:hover{background:rgba(255,255,255,0.15)}
.wrap{max-width:1350px;margin:24px auto;padding:0 18px}
.panel{background:#fff;border:1px solid #e3e9f1;border-radius:10px;box-shadow:0 4px 15px rgba(16,32,64,0.05);padding:22px 24px;margin-bottom:22px}
.panel-title{font-size:16px;font-weight:800;color:#0f172a;margin:0 0 16px 0;padding-bottom:10px;border-bottom:1px solid #eef2f6;display:flex;justify-content:space-between;align-items:center}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.grid-main{display:grid;grid-template-columns:2fr 1fr;gap:22px}
@media(max-width:960px){.grid2,.grid-main{grid-template-columns:1fr}}

.badge{display:inline-block;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:0.5px}
.badge.submitted{background:#e0f2fe;color:#0369a1}
.badge.technical_check,.badge.technical_check_passed{background:#e0e7ff;color:#3730a3}
.badge.review,.badge.under_review{background:#fef3c7;color:#92400e}
.badge.minor_revision,.badge.major_revision,.badge.revision_requested{background:#ffedd5;color:#9a3412}
.badge.revised_submission,.badge.revised_received{background:#f3e8ff;color:#6b21a8}
.badge.accepted{background:#dcfce7;color:#166534}
.badge.rejected{background:#fee2e2;color:#991b1b}

.meta-item{margin-bottom:12px}
.meta-label{font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;margin-bottom:3px}
.meta-val{font-size:14px;color:#1e293b}

table{width:100%;border-collapse:collapse;margin-top:8px}
th,td{padding:10px 12px;border:1px solid #e2e8f0;text-align:left;vertical-align:middle;font-size:13px}
th{background:#f8fafc;color:#475569;font-weight:bold;text-transform:uppercase;letter-spacing:0.4px;font-size:11px}
tr:hover{background:#fbfcfe}

.btn{display:inline-block;background:#0b5fa5;color:#fff;border:0;border-radius:6px;padding:8px 14px;text-decoration:none;font-weight:bold;font-size:13px;cursor:pointer;transition:all 0.15s;line-height:1.2}
.btn:hover{background:#084b84}
.btn:disabled,.btn.disabled{background:#cbd5e1!important;color:#94a3b8!important;cursor:not-allowed!important;box-shadow:none!important}
.btn.light{background:#eaf2f9;color:#0b5fa5}
.btn.light:hover{background:#d7e8f7}
.btn.success{background:#16a34a;color:#fff}
.btn.success:hover{background:#15803d}
.btn.danger{background:#dc2626;color:#fff}
.btn.danger:hover{background:#b91c1c}
.btn.warning{background:#d97706;color:#fff}
.btn.warning:hover{background:#b45309}
.btn.purple{background:#7c3aed;color:#fff}
.btn.purple:hover{background:#6d28d9}

/* Visual Workflow Timeline */
.workflow-timeline{background:#fff;border:1px solid #e3e9f1;border-radius:10px;padding:20px 24px;margin-bottom:22px;box-shadow:0 4px 15px rgba(16,32,64,0.05)}
.timeline-title{font-size:14px;font-weight:800;text-transform:uppercase;letter-spacing:0.5px;color:#475569;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center}
.timeline-track{display:flex;justify-content:space-between;align-items:flex-start;position:relative;gap:10px;overflow-x:auto;padding-bottom:10px}
.timeline-step{flex:1;min-width:130px;display:flex;flex-direction:column;align-items:center;text-align:center;position:relative}
.timeline-step:not(:last-child):after{content:'';position:absolute;top:16px;left:50%;width:100%;height:3px;background:#e2e8f0;z-index:1}
.timeline-step.completed:not(:last-child):after{background:#16a34a}
.timeline-step.in-progress:not(:last-child):after{background:linear-gradient(to right, #0b5fa5, #e2e8f0)}
.timeline-step.rejected:not(:last-child):after{background:#dc2626}
.step-circle{width:34px;height:34px;border-radius:50%;background:#e2e8f0;color:#64748b;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:13px;margin-bottom:8px;position:relative;z-index:2;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.08)}
.timeline-step.completed .step-circle{background:#16a34a;color:#fff}
.timeline-step.in-progress .step-circle{background:#0b5fa5;color:#fff;box-shadow:0 0 0 4px rgba(11,95,165,0.2)}
.timeline-step.rejected .step-circle{background:#dc2626;color:#fff}
.timeline-step.attention .step-circle{background:#d97706;color:#fff}
.step-name{font-size:12px;font-weight:bold;color:#1e293b;margin-bottom:3px}
.step-status{font-size:11px;font-weight:600;padding:2px 7px;border-radius:10px;background:#f1f5f9;color:#64748b;display:inline-block}
.timeline-step.completed .step-status{background:#dcfce7;color:#166534}
.timeline-step.in-progress .step-status{background:#e0f2fe;color:#0369a1}
.timeline-step.rejected .step-status{background:#fee2e2;color:#991b1b}
.timeline-step.attention .step-status{background:#ffedd5;color:#9a3412}

/* Editorial Actions Toolbar */
.actions-toolbar{background:#f8fafc;border:1px solid #cbd5e1;border-radius:10px;padding:16px 20px;margin-bottom:24px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px}
.actions-buttons{display:flex;flex-wrap:wrap;gap:8px}

/* Alerts */
.alert-box{padding:14px 18px;border-radius:8px;font-size:14px;line-height:1.5;margin-bottom:20px;display:flex;gap:12px;align-items:flex-start}
.alert-warning{background:#fffbeb;border:1px solid #fef3c7;color:#92400e}
.alert-danger{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
.alert-success{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
.alert-info{background:#f0f9ff;border:1px solid #e0f2fe;color:#0369a1}

/* Modal System */
body.modal-open{overflow:hidden}
.editorial-modal{position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;overflow:hidden}
.editorial-modal-backdrop{position:fixed;inset:0;background:rgba(15,23,42,0.65);backdrop-filter:blur(3px)}
.editorial-modal-dialog{
  position:relative;
  background:#fff;
  border-radius:12px;
  box-shadow:0 20px 50px rgba(0,0,0,0.3);
  width:min(760px, calc(100vw - 32px));
  max-height:calc(100vh - 32px);
  display:flex;
  flex-direction:column;
  z-index:1;
  overflow:hidden;
  animation:modalIn 0.2s cubic-bezier(0.16,1,0.3,1);
}
.editorial-modal-dialog.modal-wide{
  width:min(920px, calc(100vw - 32px));
}
@keyframes modalIn{from{opacity:0;transform:translateY(12px) scale(0.98)}to{opacity:1;transform:translateY(0) scale(1)}}

.editorial-modal-header{
  flex:0 0 auto;
  padding:16px 22px;
  border-bottom:1px solid #e2e8f0;
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  background:#f8fafc;
  z-index:10;
}
.modal-close-btn{
  background:none;
  border:none;
  font-size:26px;
  line-height:1;
  color:#64748b;
  cursor:pointer;
  padding:0;
  transition:color 0.15s;
}
.modal-close-btn:hover{color:#0f172a}

#editorialActionForm{
  display:flex;
  flex-direction:column;
  flex:1 1 auto;
  min-height:0;
  overflow:hidden;
  margin:0;
}

.editorial-modal-body{
  flex:1 1 auto;
  min-height:0;
  overflow-y:auto;
  overflow-x:hidden;
  overscroll-behavior:contain;
  padding:20px 24px;
  font-size:14px;
  line-height:1.5;
}
.editorial-modal-body::-webkit-scrollbar{
  width:7px;
}
.editorial-modal-body::-webkit-scrollbar-track{
  background:#f1f5f9;
}
.editorial-modal-body::-webkit-scrollbar-thumb{
  background:#cbd5e1;
  border-radius:10px;
}
.editorial-modal-body::-webkit-scrollbar-thumb:hover{
  background:#94a3b8;
}

.editorial-modal-footer{
  flex:0 0 auto;
  padding:14px 22px;
  border-top:1px solid #e2e8f0;
  display:flex;
  justify-content:flex-end;
  gap:10px;
  background:#f8fafc;
  z-index:10;
}

.modal-alert{padding:12px 16px;border-radius:6px;font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.modal-alert-error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-weight:600;font-size:13px;color:#334155;margin-bottom:6px}
.form-group .form-hint{font-size:12px;color:#64748b;margin-top:4px}
.form-control{width:100%;border:1px solid #cbd5e1;border-radius:6px;padding:9px 12px;font-size:14px;background:#fff;font-family:inherit;box-sizing:border-box}
.form-control:focus{outline:none;border-color:#0b5fa5;box-shadow:0 0 0 3px rgba(11,95,165,0.15)}

.editorial-modal textarea.form-control,
textarea.form-control{
  min-height:90px;
  max-height:220px;
  resize:vertical;
  overflow-y:auto;
  box-sizing:border-box;
}

.modal-scroll-block{
  max-height:220px;
  overflow-y:auto;
  overflow-wrap:anywhere;
  word-break:break-word;
}
.modal-scroll-block::-webkit-scrollbar{
  width:6px;
}
.modal-scroll-block::-webkit-scrollbar-track{
  background:#f8fafc;
}
.modal-scroll-block::-webkit-scrollbar-thumb{
  background:#cbd5e1;
  border-radius:8px;
}
.modal-scroll-block::-webkit-scrollbar-thumb:hover{
  background:#94a3b8;
}

.checklist-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;background:#f8fafc;padding:12px;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:14px}
.checklist-item{display:flex;align-items:center;gap:8px;font-size:13px;color:#334155;cursor:pointer}
.checklist-item input[type=checkbox]{width:16px;height:16px;cursor:pointer}

@media(max-width:768px){
  .editorial-modal{padding:8px}
  .editorial-modal-dialog{width:100%;max-height:calc(100vh - 16px);border-radius:8px}
  .editorial-modal-header{padding:12px 16px}
  .editorial-modal-body{padding:14px 16px}
  .editorial-modal-footer{padding:12px 16px}
}

/* Non-intrusive Toast */
#toastNotification{position:fixed;top:20px;right:20px;z-index:10000;background:#0f172a;color:#fff;padding:14px 20px;border-radius:8px;box-shadow:0 10px 25px rgba(0,0,0,0.25);display:flex;align-items:center;gap:12px;font-size:14px;font-weight:500;transition:opacity 0.3s, transform 0.3s;transform:translateY(-10px);opacity:0;pointer-events:none}
#toastNotification.show{transform:translateY(0);opacity:1;pointer-events:auto}
#toastNotification.toast-success{background:#15803d;color:#fff}
#toastNotification.toast-error{background:#b91c1c;color:#fff}
</style>
</head>
<body>

<?php if ($isStaff): ?>
  <!-- Staff / EIC Header -->
  <header class="top">
    <div class="top-inner">
      <div class="brand">
        AJSMR — Editorial Management System
        <small>Editor-in-Chief &bull; Manuscript Workflow &amp; Decision Central</small>
      </div>
      <div class="nav-links">
        <a href="dashboard.php">← EIC Dashboard</a>
        <a href="new_submissions.php">Submissions Queue</a>
        <a href="workflow_v1.php">Workflow V1</a>
        <a href="logout.php">Sign Out</a>
      </div>
    </div>
  </header>
<?php else: ?>
  <!-- Author / Reviewer Header -->
  <header class="top">
    <div class="top-inner">
      <div class="brand">
        AJSMR — Editorial System
        <small>Author Manuscript Portal</small>
      </div>
      <div class="nav-links">
        <a href="author/submissions.php">← My Submissions</a>
        <a href="author/submit.php">+ Submit Manuscript</a>
        <a href="logout.php">Sign Out</a>
      </div>
    </div>
  </header>
<?php endif; ?>

<main class="wrap">
  <?php if ($isStaff): ?>
    <div class="dashboard-layout">
      <?php include __DIR__ . '/includes/eic_sidebar.php'; ?>
      <div class="right-panel">
  <?php endif; ?>

  <!-- Flash Message -->
  <?php if (!empty($_SESSION['wf_flash'])): ?>
    <div class="alert-box alert-info">
      <div><?=e($_SESSION['wf_flash'])?></div>
    </div>
    <?php unset($_SESSION['wf_flash']); ?>
  <?php endif; ?>

  <!-- Title & Status Header -->
  <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;gap:20px;flex-wrap:wrap;">
    <div style="flex:1;min-width:300px;">
      <div style="font-size:13px;font-weight:bold;color:#0b5fa5;letter-spacing:0.5px;margin-bottom:4px;">
        <?=e($m['article_type'] ?: 'Research Article')?>
      </div>
      <h1 style="margin:0 0 8px 0;font-size:24px;line-height:1.3;color:#0f172a;"><?=e($m['title'])?></h1>
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <span style="font-size:15px;font-weight:bold;color:#334155;"><?=e($m['manuscript_no'])?></span>
        <span class="badge lifecycle-status-badge <?=e(strtolower($m['status']))?>"><?=e(slabel($m['status']))?></span>
        <span style="color:#64748b;font-size:13px;">Submitted: <?=date('d M Y, H:i', strtotime((string)$m['submitted_at']))?></span>
        <span style="color:#64748b;font-size:13px;">&bull; Current Version: <strong id="currentVersionHeader"><?=e($m['version_no'])?></strong></span>
      </div>
    </div>
  </div>

  <!-- ======================================================================= -->
  <!-- EDITORIAL WORKFLOW / PROGRESS TIMELINE                                  -->
  <!-- ======================================================================= -->
  <div class="workflow-timeline">
    <div class="timeline-title">
      <span>Editorial Workflow &amp; Progress Timeline</span>
      <span style="font-size:12px;font-weight:normal;color:#64748b;">
        Sequential EIC Evaluation &bull; Current Stage: <strong><?=e(slabel($m['status']))?></strong>
      </span>
    </div>

    <div class="timeline-track">
      <!-- 1. Submission -->
      <div class="timeline-step completed">
        <div class="step-circle">✓</div>
        <div class="step-name">1. Submission</div>
        <div class="step-status">Completed</div>
        <small style="color:#64748b;font-size:11px;margin-top:2px;"><?=date('d M Y', strtotime((string)$m['submitted_at']))?></small>
      </div>

      <!-- 2. Technical Check -->
      <?php
      $s2Class = 'pending';
      $s2Label = 'Pending';
      if ($step2State === 'passed') {
          $s2Class = 'completed';
          $s2Label = 'Accepted';
      } elseif ($step2State === 'failed') {
          $s2Class = 'rejected';
          $s2Label = 'Rejected';
      } elseif ($step2State === 'minor_corrections') {
          $s2Class = 'attention';
          $s2Label = 'Corrections Req.';
      }
      ?>
      <div class="timeline-step <?=$s2Class?>">
        <div class="step-circle"><?=$step2State === 'passed' ? '✓' : ($step2State === 'failed' ? '✕' : ($step2State === 'minor_corrections' ? '✎' : '2'))?></div>
        <div class="step-name">2. Technical Check</div>
        <div class="step-status"><?=$s2Label?></div>
        <?php if (!empty($existingTechCheck['checked_at'])): ?>
          <small style="color:#64748b;font-size:11px;margin-top:2px;"><?=date('d M Y', strtotime((string)$existingTechCheck['checked_at']))?></small>
        <?php endif; ?>
      </div>

      <!-- 3. Reviewer Assignment -->
      <?php
      $s3Class = $step3Done ? 'completed' : ($step2State === 'passed' ? 'in-progress' : 'pending');
      $s3Label = $step3Done ? ($activeReviewerCount . ' Assigned') : 'Pending';
      ?>
      <div class="timeline-step <?=$s3Class?>">
        <div class="step-circle"><?=$step3Done ? '✓' : '3'?></div>
        <div class="step-name">3. Reviewer Assign</div>
        <div class="step-status"><?=$s3Label?></div>
      </div>

      <!-- 4. Reviewer Report -->
      <?php
      $s4Class = $step4Done ? 'completed' : ($step3Done ? 'in-progress' : 'pending');
      $s4Label = $step4Done ? ($step4Count . ' Received') : ($step3Done ? 'Awaiting Report' : 'Pending');
      ?>
      <div class="timeline-step <?=$s4Class?>">
        <div class="step-circle"><?=$step4Done ? '✓' : '4'?></div>
        <div class="step-name">4. Reviewer Report</div>
        <div class="step-status"><?=$s4Label?></div>
      </div>

      <!-- 5. Revision -->
      <?php
      $s5Class = 'pending';
      $s5Label = 'Not Required';
      if ($step5State === 'received') {
          $s5Class = 'completed';
          $s5Label = 'Received';
      } elseif ($step5State === 'requested') {
          $s5Class = 'attention';
          $s5Label = 'Requested';
      }
      ?>
      <div class="timeline-step <?=$s5Class?>">
        <div class="step-circle"><?=$step5State === 'received' ? '✓' : ($step5State === 'requested' ? '●' : '5')?></div>
        <div class="step-name">5. Revision</div>
        <div class="step-status"><?=$s5Label?></div>
      </div>

      <!-- 6. Revised Document Review -->
      <?php
      $s6Class = 'pending';
      $s6Label = 'Pending';
      if ($step6Done) {
          $s6Class = 'completed';
          $s6Label = 'Completed';
      } elseif ($step5State === 'received') {
          $s6Class = 'in-progress';
          $s6Label = 'EIC Review';
      }
      ?>
      <div class="timeline-step <?=$s6Class?>">
        <div class="step-circle"><?=$step6Done ? '✓' : ($step5State === 'received' ? '●' : '6')?></div>
        <div class="step-name">6. Revised Review</div>
        <div class="step-status"><?=$s6Label?></div>
      </div>

      <!-- 7. Final Decision -->
      <?php
      $s7Class = 'pending';
      $s7Label = 'Pending';
      if ($step7State === 'accepted') {
          $s7Class = 'completed';
          $s7Label = 'Accepted';
      } elseif ($step7State === 'rejected') {
          $s7Class = 'rejected';
          $s7Label = 'Rejected';
      }
      ?>
      <div class="timeline-step <?=$s7Class?>">
        <div class="step-circle"><?=$step7State === 'accepted' ? '✓' : ($step7State === 'rejected' ? '✕' : '7')?></div>
        <div class="step-name">7. Final Decision</div>
        <div class="step-status"><?=$s7Label?></div>
      </div>
    </div>
  </div>

  <?php if ($isStaff): ?>
  <!-- ======================================================================= -->
  <!-- EDITORIAL ACTIONS TOOLBAR (STAFF / EIC ONLY)                            -->
  <!-- ======================================================================= -->
  <div class="actions-toolbar">
    <div>
      <strong style="color:#0f172a;font-size:14px;">Editorial Workflow Actions</strong>
      <div style="font-size:12px;color:#64748b;">Actions dynamically enable in sequence according to manuscript lifecycle progress.</div>
    </div>
    <div class="actions-buttons">
      <!-- 1. Technical Check -->
      <button type="button" class="btn <?=$canDoTechnicalCheck ? 'btn' : 'disabled'?>"
              <?=$canDoTechnicalCheck ? '' : 'disabled title="Technical check is finalized or manuscript closed"'?>
              onclick="openActionModal('technical_check')">
        📋 Technical Check
      </button>

      <!-- 2. Assign Reviewer -->
      <button type="button" class="btn <?=$canAssignReviewer ? 'btn' : 'disabled'?>"
              <?=$canAssignReviewer ? '' : 'disabled title="Pass technical check first to assign reviewers"'?>
              onclick="openActionModal('assign_reviewer')">
        👤 Assign Reviewer
      </button>

      <!-- 3. Reviewer Report -->
      <button type="button" class="btn <?=$canViewReviewerReport ? 'btn warning' : 'disabled'?>"
              <?=$canViewReviewerReport ? '' : 'disabled title="No reviewer reports submitted yet"'?>
              onclick="openActionModal('reviewer_report')">
        📑 Reviewer Report (<?=$step4Count?>)
      </button>

      <!-- 4. Request Revised Document -->
      <button type="button" class="btn <?=$canRequestRevision ? 'btn' : 'disabled'?>"
              <?=$canRequestRevision ? '' : 'disabled title="Pass technical check or complete review first"'?>
              onclick="openActionModal('request_revision')">
        ✎ Request Revised Document
      </button>

      <!-- 5. Review Revised Document -->
      <button type="button" class="btn <?=$canReviewRevisedDoc ? 'btn purple' : 'disabled'?>"
              <?=$canReviewRevisedDoc ? '' : 'disabled title="Awaiting author revised manuscript upload"'?>
              onclick="openActionModal('review_revised_document')">
        🔍 Review Revised Document
      </button>

      <!-- 6. Final Decision -->
      <button type="button" class="btn <?=$canFinalDecision ? 'btn success' : 'disabled'?>"
              <?=$canFinalDecision ? '' : 'disabled title="Complete technical check and review/revision first"'?>
              onclick="openActionModal('decision')">
        ⚖️ Final Decision
      </button>

      <!-- 7. Gallery Proof -->
      <button type="button" class="btn <?=$canGalleryProof ? 'btn success' : 'disabled'?>"
              <?=$canGalleryProof ? '' : 'disabled title="Accept manuscript first to upload gallery proof"'?>
              onclick="openActionModal('gallery_proof')">
        📄 Gallery Proof
      </button>

      <!-- 8. Printing / Publishing -->
      <button type="button" class="btn <?=$canPublish ? 'btn purple' : 'disabled'?>"
              <?=$canPublish ? '' : 'disabled title="Send gallery proof and receive author response first"'?>
              onclick="openActionModal('publish_manuscript')">
        🖨️ <?=in_array($step9State, ['in_press','published']) ? 'Printing / Publishing (' . ($step9State === 'in_press' ? 'In-Press' : 'Published') . ')' : 'Printing / Publishing'?>
      </button>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($isAuthorOwner): ?>
    <!-- ======================================================================= -->
    <!-- AUTHOR-FACING NOTIFICATIONS & REVISION UPLOAD                           -->
    <!-- ======================================================================= -->

    <!-- Technical Check Rejected Notice -->
    <?php if ($step2State === 'failed'): ?>
      <div class="alert-box alert-danger">
        <div style="font-size:24px;line-height:1;">✕</div>
        <div>
          <h4 style="margin:0 0 6px 0;font-size:16px;">Technical Check: Rejected</h4>
          <p style="margin:0 0 8px 0;">Your manuscript did not meet the required technical criteria or formatting standards.</p>
          <?php if (!empty($existingTechCheck['comments'])): ?>
            <div style="background:#fff;padding:12px 14px;border-radius:6px;border:1px solid #fecaca;margin-top:8px;">
              <strong>Reason / Feedback from Editorial Office:</strong>
              <div style="white-space:pre-wrap;margin-top:4px;color:#334155;"><?=e($existingTechCheck['comments'])?></div>
            </div>
          <?php endif; ?>
          <small style="display:block;margin-top:8px;color:#7f1d1d;">Date: <?=e($existingTechCheck['checked_at'] ?? 'Recently')?></small>
        </div>
      </div>
    <?php endif; ?>

    <!-- Revision Required Form -->
    <?php if ($step5State === 'requested' || $currentStatus === 'minor_revision' || $currentStatus === 'major_revision'): ?>
      <div class="panel" style="border-left:5px solid #d97706;">
        <h3 style="margin-top:0;font-size:18px;color:#9a3412;display:flex;align-items:center;gap:8px;">
          <span>✎ Revision Required</span>
        </h3>
        <p style="color:#475569;margin-bottom:14px;">The Editor-in-Chief has requested revisions for your manuscript. Please review the instructions below and upload your revised document.</p>

        <?php if (!empty($latestRevision['response_text'])): ?>
          <div style="background:#fffbeb;border:1px solid #fed7aa;border-radius:8px;padding:16px;margin-bottom:20px;">
            <div style="font-weight:bold;color:#9a3412;margin-bottom:6px;">EIC Revision Instructions &amp; Required Corrections:</div>
            <div style="white-space:pre-wrap;font-size:14px;color:#334155;"><?=e($latestRevision['response_text'])?></div>
          </div>
        <?php endif; ?>

        <form method="post" action="manuscript_view.php?id=<?=$id?>" enctype="multipart/form-data" style="background:#f8fafc;padding:18px;border:1px solid #e2e8f0;border-radius:8px;">
          <input type="hidden" name="action" value="author_upload_revision">
          <input type="hidden" name="manuscript_id" value="<?=$id?>">
          <input type="hidden" name="csrf" value="<?=e(csrf())?>">

          <div class="form-group">
            <label for="revisedFile">Select Revised Manuscript File (.pdf, .doc, .docx) <span style="color:#dc2626;">*</span></label>
            <input type="file" name="revised_file" id="revisedFile" class="form-control" accept=".pdf,.doc,.docx" required>
            <div class="form-hint">Do not overwrite original file. This upload will be preserved as Version <?=(int)$m['version_no'] + 1?>.</div>
          </div>

          <div class="form-group">
            <label for="authorRespText">Author Response to Reviewers &amp; Editor (Optional)</label>
            <textarea name="response_to_reviewers" id="authorRespText" class="form-control" rows="4" placeholder="Detail point-by-point changes made in response to editorial/reviewer comments..."></textarea>
          </div>

          <button type="submit" class="btn success" style="font-size:14px;padding:10px 20px;">
            ✓ Upload Revised Manuscript (Version <?=(int)$m['version_no'] + 1?>)
          </button>
        </form>
      </div>
    <?php endif; ?>

    <!-- Final Accepted Banner -->
    <?php if ($step7State === 'accepted'): ?>
      <div class="alert-box alert-success">
        <div style="font-size:26px;line-height:1;">✓</div>
        <div>
          <h4 style="margin:0 0 4px 0;font-size:17px;">Congratulations! Manuscript Accepted for Publication</h4>
          <p style="margin:0 0 6px 0;">Your manuscript has completed peer review and editorial evaluation, and has been officially accepted for publication in AJSMR.</p>
          <?php if (!empty($latestDecision['letter'])): ?>
            <div style="background:#fff;padding:12px 14px;border-radius:6px;border:1px solid #bbf7d0;margin-top:8px;">
              <strong>Editorial Decision Letter:</strong>
              <div style="white-space:pre-wrap;margin-top:4px;color:#1e293b;"><?=e($latestDecision['letter'])?></div>
            </div>
          <?php endif; ?>
          <small style="color:#14532d;display:block;margin-top:8px;">Accepted Date: <?=e($m['accepted_at'] ?: date('d M Y'))?></small>
        </div>
      </div>
    <?php endif; ?>

    <!-- Final Rejected Banner -->
    <?php if ($step7State === 'rejected' && $step2State !== 'failed'): ?>
      <div class="alert-box alert-danger">
        <div style="font-size:26px;line-height:1;">✕</div>
        <div>
          <h4 style="margin:0 0 4px 0;font-size:17px;">Editorial Decision: Rejected</h4>
          <p style="margin:0 0 6px 0;">Following editorial review, your manuscript has not been accepted for publication.</p>
          <?php if (!empty($latestDecision['letter'])): ?>
            <div style="background:#fff;padding:12px 14px;border-radius:6px;border:1px solid #fecaca;margin-top:8px;">
              <strong>Editorial Comments / Decision Letter:</strong>
              <div style="white-space:pre-wrap;margin-top:4px;color:#334155;"><?=e($latestDecision['letter'])?></div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <!-- Main Grid: Manuscript Details & Submitted Documents -->
  <div class="grid-main">
    <div>
      <!-- 1. MANUSCRIPT DETAILS -->
      <div class="panel">
        <div class="panel-title">
          <span>Manuscript Details</span>
          <span style="font-size:12px;font-weight:normal;color:#64748b;">ID: <strong><?=e($m['manuscript_no'])?></strong></span>
        </div>

        <div class="grid2">
          <div>
            <div class="meta-item">
              <div class="meta-label">Paper ID</div>
              <div class="meta-val"><strong><?=e($m['manuscript_no'])?></strong></div>
            </div>
            <div class="meta-item">
              <div class="meta-label">Article Type</div>
              <div class="meta-val"><?=e($m['article_type'] ?: 'Research Article')?></div>
            </div>
            <div class="meta-item">
              <div class="meta-label">Corresponding Author</div>
              <div class="meta-val">
                <strong><?=e(!empty($m['author_name']) ? $m['author_name'] : (!empty($authors[0]['author_name']) ? $authors[0]['author_name'] : (!empty($authors[0]['full_name']) ? $authors[0]['full_name'] : 'Not specified')))?></strong><br>
                <span style="color:#64748b;font-size:13px;"><?=e($authorEmailDisplay)?></span>
              </div>
            </div>
            <div class="meta-item">
              <div class="meta-label">Affiliation</div>
              <div class="meta-val"><?=e(!empty($m['author_affiliation']) ? $m['author_affiliation'] : (!empty($authors[0]['affiliation']) ? $authors[0]['affiliation'] : 'Not specified'))?></div>
            </div>
          </div>

          <div>
            <div class="meta-item">
              <div class="meta-label">Current Status</div>
              <div class="meta-val">
                <span class="badge lifecycle-status-badge <?=e(strtolower($m['status']))?>"><?=e(slabel($m['status']))?></span>
              </div>
            </div>
            <div class="meta-item">
              <div class="meta-label">Current Version</div>
              <div class="meta-val" id="manuscriptVersionVal">Version <?=e($m['version_no'])?></div>
            </div>
            <div class="meta-item">
              <div class="meta-label">Submission Date</div>
              <div class="meta-val"><?=!empty($m['submitted_at']) ? date('d M Y, H:i', strtotime((string)$m['submitted_at'])) : '—'?></div>
            </div>
            <?php if (!empty($m['author_orcid']) || !empty($authors[0]['orcid'])): ?>
            <div class="meta-item">
              <div class="meta-label">ORCID iD</div>
              <div class="meta-val"><?=e(!empty($m['author_orcid']) ? $m['author_orcid'] : ($authors[0]['orcid'] ?? '—'))?></div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Authors Table -->
      <?php if (!empty($authors)): ?>
      <div class="panel">
        <div class="panel-title">Authors List</div>
        <table>
          <thead>
            <tr>
              <th style="width:35px;">#</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Affiliation</th>
              <th>ORCID</th>
              <th>Role</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($authors as $i => $a): ?>
              <?php
                $aName = !empty($a['full_name']) ? $a['full_name'] : (!empty($a['author_name']) ? $a['author_name'] : ($a['name'] ?? '—'));
                $isCorr = !empty($a['is_corresponding'])
                    || (!empty($m['corresponding_author_id']) && !empty($a['author_user_id']) && (int)$a['author_user_id'] === (int)$m['corresponding_author_id'])
                    || (!empty($authorEmailDisplay) && !empty($a['email']) && strcasecmp((string)$a['email'], (string)$authorEmailDisplay) === 0)
                    || ((int)($a['author_order'] ?? ($i + 1)) === 1 && count($authors) === 1);
              ?>
              <tr>
                <td><?=e($a['author_order'] ?? ($i + 1))?></td>
                <td><strong><?=e($aName)?></strong></td>
                <td><?=e(!empty($a['email']) ? $a['email'] : '—')?></td>
                <td><?=e(!empty($a['affiliation']) ? $a['affiliation'] : '—')?></td>
                <td><?=e(!empty($a['orcid']) ? $a['orcid'] : '—')?></td>
                <td><?= $isCorr ? '<span style="color:#0b5fa5;font-weight:bold;">Corresponding Author</span>' : (count($authors) > 1 ? 'Co-Author' : 'Author') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <!-- Abstract Panel -->
      <div class="panel">
        <div class="panel-title">Abstract</div>
        <?php
          $abstractText = !empty($m['abstract']) ? $m['abstract'] : (!empty($m['abstract_text']) ? $m['abstract_text'] : '');
        ?>
        <div style="line-height:1.65;font-size:14px;color:#334155;white-space:pre-wrap;"><?=e($abstractText !== '' ? $abstractText : 'No abstract text supplied.')?></div>
      </div>

      <!-- 2. SUBMITTED DOCUMENTS (Version Control & Revisions) -->
      <div class="panel">
        <div class="panel-title">
          <span>Submitted Documents &amp; Version History</span>
          <span style="font-size:12px;font-weight:normal;color:#64748b;">All versions preserved</span>
        </div>

        <!-- Original Submission (Version 1) -->
        <h4 style="margin:12px 0 6px 0;font-size:14px;color:#0b5fa5;">Original Submission (Version 1)</h4>
        <?php if (empty($originalFiles)): ?>
          <p style="color:#64748b;font-size:13px;">No original manuscript files found.</p>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>Document Type</th>
                <th>Original File Name</th>
                <th>Size</th>
                <th>Uploaded</th>
                <th style="width:140px;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($originalFiles as $f): ?>
                <tr>
                  <td><strong><?=e(ucwords(str_replace('_', ' ', (string)($f['file_type'] ?? 'main_manuscript'))))?></strong></td>
                  <td><?=e($f['original_name'] ?? basename((string)($f['file_path'] ?? 'manuscript.pdf')))?></td>
                  <td><?=number_format(((int)($f['file_size'] ?? 0)) / 1024, 1)?> KB</td>
                  <td><?=!empty($f['uploaded_at']) ? date('d M Y', strtotime((string)$f['uploaded_at'])) : '—'?></td>
                  <td>
                    <a class="btn light" style="padding:4px 10px;font-size:12px;" href="download_manuscript_file.php?id=<?=(int)$f['id']?>" target="_blank">View / Download ↗</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>

        <!-- Revisions (Version 2+) -->
        <?php if (!empty($revisedFiles)): ?>
          <h4 style="margin:22px 0 6px 0;font-size:14px;color:#7c3aed;">Revised Manuscripts &amp; Response Documents</h4>
          <table>
            <thead>
              <tr>
                <th>Version</th>
                <th>Document Type</th>
                <th>Original File Name</th>
                <th>Size</th>
                <th>Uploaded</th>
                <th style="width:140px;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($revisedFiles as $rf): ?>
                <tr style="background:#faf5ff;">
                  <td><strong style="color:#7c3aed;">Version <?=(int)($rf['version_no'] ?? 1)?></strong></td>
                  <td><strong><?=e(ucwords(str_replace('_', ' ', (string)($rf['file_type'] ?? 'revised_manuscript'))))?></strong></td>
                  <td><?=e($rf['original_name'] ?? basename((string)($rf['file_path'] ?? 'manuscript.pdf')))?></td>
                  <td><?=number_format(((int)($rf['file_size'] ?? 0)) / 1024, 1)?> KB</td>
                  <td><?=!empty($rf['uploaded_at']) ? date('d M Y', strtotime((string)$rf['uploaded_at'])) : '—'?></td>
                  <td>
                    <a class="btn light" style="padding:4px 10px;font-size:12px;background:#ede9fe;color:#6d28d9;" href="download_manuscript_file.php?id=<?=(int)$rf['id']?>" target="_blank">View / Download ↗</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- Sidebar / Additional Panels -->
    <div>
      <?php if ($isStaff): ?>
        <!-- Assigned Reviewers Panel -->
        <div class="panel">
          <div class="panel-title">
            <span>Assigned Reviewers (<?=count($reviewers)?>)</span>
            <?php if ($canAssignReviewer): ?>
              <button type="button" class="btn light" style="font-size:11px;padding:4px 8px;" onclick="openActionModal('assign_reviewer')">+ Assign</button>
            <?php endif; ?>
          </div>

          <?php if (empty($reviewers)): ?>
            <p style="color:#64748b;font-size:13px;margin:0;">No reviewers assigned yet.</p>
          <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:12px;">
              <?php foreach ($reviewers as $idx => $r): ?>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;">
                  <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:4px;">
                    <strong style="font-size:13px;color:#0f172a;">Reviewer <?=($idx + 1)?>: <?=e($r['reviewer_name'])?></strong>
                    <span class="badge"><?=e(slabel($r['status']))?></span>
                  </div>
                  <div style="font-size:12px;color:#64748b;margin-bottom:6px;"><?=e($r['reviewer_email'])?> &bull; <?=e($r['reviewer_affiliation'] ?: 'General')?></div>
                  <div style="font-size:12px;margin-bottom:6px;">
                    Due: <strong><?=e($r['due_at'] ? date('d M Y', strtotime((string)$r['due_at'])) : 'Not specified')?></strong>
                  </div>

                  <?php if (!empty($r['recommendation'])): ?>
                    <div style="background:#fff;border:1px solid #cbd5e1;border-radius:6px;padding:8px;font-size:12px;margin-top:6px;">
                      Recommendation: <strong style="color:#0b5fa5;"><?=e(slabel($r['recommendation']))?></strong>
                      <div style="color:#64748b;margin-top:2px;">Submitted: <?=date('d M Y, H:i', strtotime((string)$r['review_submitted_at']))?></div>
                      <button type="button" class="btn light" style="font-size:11px;padding:3px 8px;margin-top:6px;" onclick="openActionModal('reviewer_report')">View Full Report</button>
                    </div>
                  <?php else: ?>
                    <span style="font-size:12px;color:#94a3b8;">Awaiting reviewer submission</span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- ============================================================ -->
        <!-- AUTHOR GALLERY PROOF UPDATES                                 -->
        <!-- ============================================================ -->
        <div class="panel">
          <div class="panel-title" style="border-left:4px solid #7c3aed;padding-left:10px;">
            <span>📋 Author Gallery Proof Updates</span>
            <span style="font-size:12px;color:#64748b;font-weight:normal;">
              <?=count($allAuthorProofApprovals)?> response(s)
            </span>
          </div>

          <?php if (empty($allAuthorProofApprovals)): ?>
            <p style="color:#64748b;font-size:13px;margin:0;">
              No author Gallery Proof updates yet.
            </p>
          <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:14px;">
              <?php foreach ($allAuthorProofApprovals as $apa): ?>
                <?php
                  $apaDecision  = (string)($apa['decision'] ?? '');
                  $apaComments  = (string)($apa['comments'] ?? '');
                  $apaCreated   = (string)($apa['created_at'] ?? '');
                  $apaApprovedAt= (string)($apa['approved_at'] ?? '');
                  $apaVer       = (int)($apa['proof_version'] ?? 0);
                  $apaAuthor    = (string)($apa['author_full_name'] ?? ($apa['author_email'] ?? 'Author'));
                  $apaProofFile = (string)($apa['proof_file_path'] ?? '');

                  // Decision badge styling
                  if ($apaDecision === 'approved') {
                      $decBg    = '#f0fdf4'; $decBorder = '#bbf7d0';
                      $decColor = '#166534'; $decIcon   = '✓';
                      $decLabel = 'Approved';
                  } elseif ($apaDecision === 'corrections_requested') {
                      $decBg    = '#fff7ed'; $decBorder = '#fed7aa';
                      $decColor = '#9a3412'; $decIcon   = '✎';
                      $decLabel = 'Corrections Requested';
                  } else {
                      $decBg    = '#f8fafc'; $decBorder = '#cbd5e1';
                      $decColor = '#475569'; $decIcon   = '?';
                      $decLabel = ucwords(str_replace('_',' ',$apaDecision)) ?: 'No decision';
                  }

                  $displayDate = $apaApprovedAt ?: $apaCreated;
                ?>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;position:relative;">

                  <!-- Proof version badge -->
                  <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
                    <div>
                      <span style="background:#ede9fe;color:#5b21b6;border-radius:12px;padding:2px 10px;font-size:11px;font-weight:bold;display:inline-block;">
                        Gallery Proof — Version <?=$apaVer ?: '?'?>
                      </span>
                    </div>
                    <span style="font-size:11px;color:#64748b;">
                      <?=$displayDate ? date('d M Y, H:i', strtotime($displayDate)) : '—'?>
                    </span>
                  </div>

                  <!-- Author name -->
                  <div style="font-size:12px;color:#64748b;margin-bottom:8px;">
                    👤 <strong><?=e($apaAuthor)?></strong>
                  </div>

                  <!-- Decision card -->
                  <div style="background:<?=$decBg?>;border:1px solid <?=$decBorder?>;border-radius:6px;padding:8px 12px;margin-bottom:<?=$apaComments ? '8px' : '0'?>;">
                    <span style="color:<?=$decColor?>;font-weight:bold;font-size:13px;">
                      <?=$decIcon?> <?=e($decLabel)?>
                    </span>
                  </div>

                  <!-- Author comments -->
                  <?php if ($apaComments !== ''): ?>
                    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:10px 12px;font-size:13px;color:#334155;white-space:pre-wrap;line-height:1.5;margin-bottom:<?=$apaProofFile ? '8px' : '0'?>;">
                      <div style="font-size:11px;color:#64748b;font-weight:bold;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:4px;">Author Comments</div>
                      <?=e($apaComments)?>
                    </div>
                  <?php endif; ?>

                  <!-- Linked proof document -->
                  <?php if ($apaProofFile !== ''): ?>
                    <?php
                      $proofFname = basename($apaProofFile);
                      // Build secure download URL via existing download_manuscript_file.php using proof_id
                      $dlProofId  = (int)($apa['proof_id'] ?? 0);
                      $dlUrl = $dlProofId > 0
                          ? BASE_URL . 'download_manuscript_file.php?proof_id=' . $dlProofId
                          : '';
                    ?>
                    <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:6px;padding:8px 12px;font-size:12px;display:flex;align-items:center;justify-content:space-between;gap:8px;">
                      <div style="color:#0369a1;">
                        📄 <strong>Related Proof:</strong> <?=e($proofFname)?>
                      </div>
                      <?php if ($dlUrl !== ''): ?>
                        <a href="<?=e($dlUrl)?>" target="_blank"
                           style="background:#0b5fa5;color:#fff;padding:4px 10px;border-radius:5px;font-size:11px;text-decoration:none;white-space:nowrap;"
                           title="Download proof document">
                          ⬇ Download
                        </a>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>

                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        <!-- /AUTHOR GALLERY PROOF UPDATES -->

        <!-- Revision History Panel (Staff) -->
        <div class="panel">
          <div class="panel-title">
            <span>Revision History</span>
            <span style="font-size:12px;color:#64748b;"><?=count($revisionsList)?> cycles</span>
          </div>
          <?php if (empty($revisionsList)): ?>
            <p style="color:#64748b;font-size:13px;margin:0;">No revision rounds requested.</p>
          <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:10px;">
              <?php foreach ($revisionsList as $rev): ?>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:10px;font-size:13px;">
                  <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                    <strong>Version <?=(int)$rev['version_no']?></strong>
                    <span class="badge"><?=e(slabel($rev['status']))?></span>
                  </div>
                  <div style="font-size:11px;color:#64748b;margin-bottom:4px;">
                    Requested: <?=date('d M Y', strtotime((string)$rev['requested_at']))?> by <?=e($rev['requester_name'] ?: 'EIC')?>
                  </div>
                  <?php if (!empty($rev['received_at'])): ?>
                    <div style="font-size:11px;color:#166534;margin-bottom:4px;">
                      Received: <?=date('d M Y, H:i', strtotime((string)$rev['received_at']))?>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($rev['response_to_reviewers'])): ?>
                    <div style="font-size:12px;color:#334155;background:#fff;padding:6px;border-radius:4px;border:1px solid #e2e8f0;margin-top:4px;">
                      <strong>Author Response:</strong>
                      <div style="white-space:pre-wrap;margin-top:2px;"><?=e(mb_strimwidth((string)$rev['response_to_reviewers'], 0, 150, '...'))?></div>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Editorial Decisions Panel -->
        <div class="panel">
          <div class="panel-title">Editorial Decisions</div>
          <?php if (empty($editorialDecisions)): ?>
            <p style="color:#64748b;font-size:13px;margin:0;">No decisions rendered yet.</p>
          <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:10px;">
              <?php foreach ($editorialDecisions as $dec): ?>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:10px;font-size:13px;">
                  <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                    <strong style="color:<?=$dec['decision'] === 'accept' ? '#166534' : ($dec['decision'] === 'reject' ? '#991b1b' : '#9a3412')?>;">
                      <?=e(slabel($dec['decision']))?>
                    </strong>
                    <span style="font-size:11px;color:#64748b;"><?=date('d M Y', strtotime((string)$dec['created_at']))?></span>
                  </div>
                  <?php if (!empty($dec['letter'])): ?>
                    <div style="font-size:12px;color:#334155;white-space:pre-wrap;margin-top:4px;"><?=e(mb_strimwidth((string)$dec['letter'], 0, 180, '...'))?></div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Internal Editorial Notes (Confidential) -->
        <div class="panel" id="editorialNotesPanel">
          <div class="panel-title">
            <span>Confidential Staff Notes</span>
            <button type="button" class="btn light" style="font-size:11px;padding:4px 8px;" onclick="openActionModal('add_note')">+ Add Note</button>
          </div>
          <div id="editorialNotesList" style="display:flex;flex-direction:column;gap:8px;">
            <?php if (empty($editorialNotes)): ?>
              <p id="noNotesNotice" style="color:#64748b;font-size:13px;margin:0;">No internal editorial notes.</p>
            <?php else: ?>
              <?php foreach ($editorialNotes as $note): ?>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:10px;font-size:13px;">
                  <div style="font-size:11px;color:#64748b;margin-bottom:3px;display:flex;justify-content:space-between;">
                    <strong><?=e($note['full_name'] ?: ($note['email'] ?: 'Editorial Staff'))?></strong>
                    <span><?=date('d M Y, H:i', strtotime((string)$note['created_at']))?></span>
                  </div>
                  <div style="color:#334155;white-space:pre-wrap;font-size:13px;"><?=e($note['details'])?></div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Quick Communication -->
        <div class="panel">
          <div class="panel-title">Communication &amp; Board</div>
          <button type="button" class="btn light" style="width:100%;margin-bottom:8px;text-align:left;" onclick="openActionModal('send_email')">✉️ Send Email to Author</button>
          <button type="button" class="btn light" style="width:100%;text-align:left;" onclick="openActionModal('assign_editor')">👤 Assign Section Editor</button>
        </div>
      <?php else: ?>
        <!-- Author Sidebar Info -->
        <div class="panel">
          <div class="panel-title">Author Assistance</div>
          <p style="font-size:13px;line-height:1.5;color:#475569;margin:0 0 10px 0;">
            If you have questions regarding the review process or need assistance with your submission, please contact the editorial office.
          </p>
          <div style="font-size:13px;color:#0b5fa5;font-weight:bold;">editorajsmr@gmail.com</div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($isStaff): ?>
      </div><!-- /.right-panel -->
    </div><!-- /.dashboard-layout -->
  <?php endif; ?>

</main>

<?php if ($isStaff): ?>
<!-- ========================================================================= -->
<!-- MODAL CONTAINER & TEMPLATES (STAFF / EIC ONLY)                            -->
<!-- ========================================================================= -->
<div id="editorialActionModal" class="editorial-modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" style="display:none;">
  <div class="editorial-modal-backdrop" onclick="closeModal()"></div>
  <div class="editorial-modal-dialog">
    <div class="editorial-modal-header">
      <div>
        <h3 id="modalTitle" style="margin:0;font-size:18px;color:#0f172a;">Editorial Action</h3>
        <div style="font-size:12px;color:#64748b;margin-top:4px;">
          Paper ID: <strong style="color:#0f172a;"><?=e($m['manuscript_no'])?></strong> &bull;
          <span><?=e(mb_strimwidth((string)$m['title'], 0, 50, '...'))?></span>
        </div>
      </div>
      <button type="button" class="modal-close-btn" onclick="closeModal()" aria-label="Close modal">&times;</button>
    </div>

    <form id="editorialActionForm" onsubmit="submitEditorialAction(event)">
      <input type="hidden" name="action" id="modalActionType" value="">
      <input type="hidden" name="manuscript_id" value="<?=(int)$m['id']?>">
      <input type="hidden" name="csrf" value="<?=e(csrf())?>">

      <div class="editorial-modal-body">
        <div id="modalAlertError" class="modal-alert modal-alert-error" style="display:none;"></div>
        <div id="modalBodyContent"></div>
      </div>

      <div class="editorial-modal-footer">
        <button type="button" class="btn light" onclick="closeModal()">Cancel</button>
        <button type="submit" id="modalSubmitBtn" class="btn">Confirm Action</button>
      </div>
    </form>
  </div>
</div>

<!-- Hidden Action Templates -->
<div style="display:none;">

  <!-- 1. Technical Check Template -->
  <div id="tmpl-technical_check">
    <div style="background:#f8fafc;padding:12px 14px;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:14px;font-size:13px;">
      <div>Paper ID: <strong><?=e($m['manuscript_no'])?></strong></div>
      <div>Title: <strong><?=e($m['title'])?></strong></div>
      <div>Author: <strong><?=e(!empty($m['author_name']) ? $m['author_name'] : (!empty($authors[0]['author_name']) ? $authors[0]['author_name'] : 'Corresponding Author'))?></strong></div>
      <div>Current Version: <strong>Version <?=e($m['version_no'])?></strong></div>
    </div>

    <div class="form-group">
      <label>Initial Verification Checklist</label>
      <div class="checklist-grid">
        <label class="checklist-item"><input type="checkbox" name="checklist[]" value="Manuscript file available" checked> Manuscript file available</label>
        <label class="checklist-item"><input type="checkbox" name="checklist[]" value="Article type appropriate" checked> Article type appropriate</label>
        <label class="checklist-item"><input type="checkbox" name="checklist[]" value="Title present" checked> Title present</label>
        <label class="checklist-item"><input type="checkbox" name="checklist[]" value="Authors & affiliations present" checked> Authors &amp; affiliations</label>
        <label class="checklist-item"><input type="checkbox" name="checklist[]" value="References present" checked> References present</label>
        <label class="checklist-item"><input type="checkbox" name="checklist[]" value="Figures/tables formatted" checked> Figures &amp; tables</label>
        <label class="checklist-item"><input type="checkbox" name="checklist[]" value="Required declarations present" checked> Required declarations</label>
        <label class="checklist-item"><input type="checkbox" name="checklist[]" value="Formatting acceptable" checked> Formatting acceptable</label>
      </div>
    </div>

    <div class="form-group">
      <label style="margin-bottom:8px;display:block;">Technical Check Outcome <span style="color:#dc2626;">*</span></label>
      <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:10px;margin-bottom:12px;">
        <label style="border:2px solid #bbf7d0;background:#f0fdf4;padding:12px;border-radius:8px;cursor:pointer;display:flex;flex-direction:column;gap:4px;">
          <div style="display:flex;align-items:center;gap:6px;">
            <input type="radio" name="result" value="passed" <?= ($existingTechCheck['result'] === 'passed') ? 'checked' : '' ?> onchange="onTcResultChange(this.value)">
            <strong style="color:#166534;font-size:13px;">✓ Accept</strong>
          </div>
          <small style="color:#15803d;font-size:11px;line-height:1.3;">Passes technical requirements. Enables reviewer assignment.</small>
        </label>
        <label style="border:2px solid #fed7aa;background:#fff7ed;padding:12px;border-radius:8px;cursor:pointer;display:flex;flex-direction:column;gap:4px;">
          <div style="display:flex;align-items:center;gap:6px;">
            <input type="radio" name="result" value="minor_corrections" <?= ($existingTechCheck['result'] === 'minor_corrections') ? 'checked' : '' ?> onchange="onTcResultChange(this.value)">
            <strong style="color:#9a3412;font-size:13px;">✎ Minor Corrections</strong>
          </div>
          <small style="color:#c2410c;font-size:11px;line-height:1.3;">Requires minor corrections before peer review. Comments required.</small>
        </label>
        <label style="border:2px solid #fecaca;background:#fef2f2;padding:12px;border-radius:8px;cursor:pointer;display:flex;flex-direction:column;gap:4px;">
          <div style="display:flex;align-items:center;gap:6px;">
            <input type="radio" name="result" value="failed" <?= ($existingTechCheck['result'] === 'failed') ? 'checked' : '' ?> onchange="onTcResultChange(this.value)">
            <strong style="color:#991b1b;font-size:13px;">✕ Reject</strong>
          </div>
          <small style="color:#b91c1c;font-size:11px;line-height:1.3;">Fails technical requirements. Rejection comments required.</small>
        </label>
      </div>
    </div>

    <div class="form-group">
      <label for="tcComments">Comments / Technical Check Notes <span id="tcCommentReq" style="color:#dc2626;display:none;">*</span></label>
      <textarea name="comments" id="tcComments" class="form-control" rows="4" placeholder="Enter comments, corrections required, or rejection reason..."><?=e($existingTechCheck['comments'])?></textarea>
      <div class="form-hint" id="tcHintText">Comments are optional for Accept, but required for Minor Corrections and Reject.</div>
    </div>
  </div>

  <!-- 2. Assign Reviewer Template -->
  <div id="tmpl-assign_reviewer">
    <div class="form-group">
      <label for="assignReviewerSelect">Select Reviewer <span style="color:#dc2626;">*</span></label>
      <select name="reviewer_id" id="assignReviewerSelect" class="form-control" required onchange="onReviewerSelected(this)">
        <option value="">-- Choose Reviewer from Pool --</option>
        <?php foreach ($allReviewers as $rv): ?>
          <option value="<?=(int)$rv['id']?>"
                  data-name="<?=e($rv['full_name'])?>"
                  data-email="<?=e($rv['email'])?>"
                  data-affiliation="<?=e($rv['affiliation'])?>"
                  data-expertise="<?=e($rv['expertise'])?>">
            <?=e($rv['full_name'])?> — <?=e($rv['email'])?> (<?=e($rv['expertise'] ?: ($rv['affiliation'] ?: 'General'))?>)
          </option>
        <?php endforeach; ?>
      </select>
      <div class="form-hint">Populated dynamically from active reviewer database. Reviewer identity remains confidential from authors.</div>
    </div>

    <div id="reviewerDetailBox" style="display:none;background:#f8fafc;border:1px solid #cbd5e1;border-radius:6px;padding:12px;margin-bottom:14px;font-size:13px;">
      <div><strong>Name:</strong> <span id="rvDetailName"></span></div>
      <div><strong>Email:</strong> <span id="rvDetailEmail"></span></div>
      <div><strong>Affiliation:</strong> <span id="rvDetailAffiliation"></span></div>
      <div><strong>Expertise:</strong> <span id="rvDetailExpertise"></span></div>
    </div>

    <div class="form-group">
      <label for="assignReviewerDue">Review Due Date <span style="color:#dc2626;">*</span></label>
      <input type="date" name="due_at" id="assignReviewerDue" class="form-control" value="<?=date('Y-m-d', strtotime('+14 days'))?>" required>
    </div>

    <div class="form-group">
      <label for="assignReviewerInstructions">Special Instructions for Reviewer (Optional)</label>
      <textarea name="instructions" id="assignReviewerInstructions" class="form-control" rows="3" placeholder="Focus areas, specific questions, or confidentiality instructions..."></textarea>
    </div>
  </div>

  <!-- 3. Reviewer Report Modal Template -->
  <div id="tmpl-reviewer_report">
    <?php if (empty($completedReviews)): ?>
      <div style="padding:20px;text-align:center;color:#64748b;">
        <p>No reviewer reports have been submitted yet for this manuscript.</p>
        <?php if (!empty($reviewers)): ?>
          <p style="font-size:13px;"><?=count($reviewers)?> reviewer(s) currently assigned. Awaiting report completion.</p>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:18px;">
        <?php foreach ($completedReviews as $idx => $cr): ?>
          <div style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:8px;padding:16px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid #e2e8f0;padding-bottom:8px;margin-bottom:12px;">
              <div>
                <strong style="font-size:15px;color:#0f172a;">Review Report #<?=($idx + 1)?></strong>
                <div style="font-size:12px;color:#64748b;">Reviewer: <?=e($cr['reviewer_name'])?> &bull; <?=e($cr['reviewer_email'])?></div>
              </div>
              <div style="text-align:right;">
                <span class="badge" style="background:#e0f2fe;color:#0369a1;font-size:12px;">
                  Recommendation: <strong><?=e(slabel($cr['recommendation']))?></strong>
                </span>
                <div style="font-size:11px;color:#64748b;margin-top:2px;">Submitted: <?=date('d M Y, H:i', strtotime((string)$cr['review_submitted_at']))?></div>
              </div>
            </div>

            <div style="margin-bottom:12px;">
              <div style="font-weight:bold;font-size:12px;text-transform:uppercase;color:#475569;margin-bottom:4px;">Comments to Author (Author-Visible):</div>
              <div class="modal-scroll-block" style="background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:10px;font-size:13px;color:#334155;white-space:pre-wrap;"><?=e($cr['comments_to_author'] ?: 'No separate comments to author provided.')?></div>
            </div>

            <div style="margin-bottom:12px;">
              <div style="font-weight:bold;font-size:12px;text-transform:uppercase;color:#475569;margin-bottom:4px;">Comments to Editor (Confidential):</div>
              <div class="modal-scroll-block" style="background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:10px;font-size:13px;color:#334155;white-space:pre-wrap;"><?=e($cr['comments_to_editor'] ?: 'No comments to editor provided.')?></div>
            </div>

            <?php if (!empty($cr['confidential_comments'])): ?>
            <div>
              <div style="font-weight:bold;font-size:12px;text-transform:uppercase;color:#991b1b;margin-bottom:4px;">Confidential Comments to EIC:</div>
              <div class="modal-scroll-block" style="background:#fef2f2;border:1px solid #fecaca;border-radius:6px;padding:10px;font-size:13px;color:#7f1d1d;white-space:pre-wrap;"><?=e($cr['confidential_comments'])?></div>
            </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <!-- Post-Report Actions Shortcut -->
        <div style="background:#f1f5f9;border:1px solid #cbd5e1;border-radius:8px;padding:14px;">
          <strong style="display:block;font-size:13px;color:#0f172a;margin-bottom:8px;">EIC Actions Following Review Evaluation:</strong>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button type="button" class="btn" style="font-size:12px;padding:6px 12px;" onclick="closeModal(); setTimeout(() => openActionModal('request_revision'), 200);">✎ Request Revision</button>
            <button type="button" class="btn light" style="font-size:12px;padding:6px 12px;" onclick="closeModal(); setTimeout(() => openActionModal('assign_reviewer'), 200);">👤 Assign Another Reviewer</button>
            <button type="button" class="btn success" style="font-size:12px;padding:6px 12px;" onclick="closeModal(); setTimeout(() => openActionModal('accept'), 200);">✓ Accept Manuscript</button>
            <button type="button" class="btn danger" style="font-size:12px;padding:6px 12px;" onclick="closeModal(); setTimeout(() => openActionModal('reject'), 200);">✕ Reject Manuscript</button>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- 4. Request Revision Template -->
  <div id="tmpl-request_revision">
    <div class="form-group">
      <label>Revision Type <span style="color:#dc2626;">*</span></label>
      <div style="display:flex;gap:20px;padding:4px 0;">
        <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
          <input type="radio" name="revision_type" value="minor_revision" checked>
          <strong>Minor Revision</strong>
        </label>
        <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
          <input type="radio" name="revision_type" value="major_revision">
          <strong>Major Revision</strong>
        </label>
      </div>
    </div>

    <div class="form-group">
      <label for="revisionDueDate">Revision Deadline</label>
      <input type="date" name="due_date" id="revisionDueDate" class="form-control" value="<?=date('Y-m-d', strtotime('+21 days'))?>">
    </div>

    <div class="form-group">
      <label for="revisionComments">Comments &amp; Instructions to Author <span style="color:#dc2626;">*</span></label>
      <textarea name="response_text" id="revisionComments" class="form-control" rows="5" placeholder="Specify precisely what corrections, responses, or additional experiments/analyses are required from the author..." required></textarea>
      <div class="form-hint">These instructions will be emailed to the corresponding author and displayed in their Author Dashboard.</div>
    </div>
  </div>

  <!-- 5. Review Revised Document Template -->
  <div id="tmpl-review_revised_document">
    <div style="background:#f8fafc;padding:12px 14px;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:14px;font-size:13px;">
      <div>Paper ID: <strong><?=e($m['manuscript_no'])?></strong> &bull; Title: <strong><?=e($m['title'])?></strong></div>
      <div>Current Version: <strong>Version <?=e($m['version_no'])?></strong></div>
    </div>

    <!-- Revised Files List -->
    <div class="form-group">
      <label>Revised Document</label>
      <?php if (!empty($revisedFiles)): ?>
        <table style="margin-top:4px;">
          <thead>
            <tr>
              <th>Version</th>
              <th>Document</th>
              <th>Uploaded</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($revisedFiles as $rf): ?>
              <tr>
                <td><strong>v<?=(int)$rf['version_no']?></strong></td>
                <td><?=e($rf['original_name'])?></td>
                <td><?=date('d M Y', strtotime((string)$rf['uploaded_at']))?></td>
                <td>
                  <a class="btn light" style="padding:3px 8px;font-size:11px;" href="download_manuscript_file.php?id=<?=(int)$rf['id']?>" target="_blank">View / Download ↗</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p style="color:#64748b;font-size:13px;">No revised documents recorded yet.</p>
      <?php endif; ?>
    </div>

    <!-- Previous EIC Comments & Reviewer Summary -->
    <?php if (!empty($latestRevision['response_text'])): ?>
      <div class="form-group">
        <label>Previous EIC Revision Instructions</label>
        <div style="background:#fffbeb;border:1px solid #fef3c7;border-radius:6px;padding:10px;font-size:13px;white-space:pre-wrap;color:#334155;"><?=e($latestRevision['response_text'])?></div>
      </div>
    <?php endif; ?>

    <?php if (!empty($latestRevision['response_to_reviewers'])): ?>
      <div class="form-group">
        <label>Author Response to Revisions</label>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;padding:10px;font-size:13px;white-space:pre-wrap;color:#166534;"><?=e($latestRevision['response_to_reviewers'])?></div>
      </div>
    <?php endif; ?>

    <div class="form-group">
      <label>EIC Revised Document Decision <span style="color:#dc2626;">*</span></label>
      <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:10px;margin-bottom:12px;">
        <label style="border:2px solid #bbf7d0;background:#f0fdf4;padding:10px;border-radius:8px;cursor:pointer;">
          <input type="radio" name="revision_outcome" value="accept" checked onchange="onRevOutcomeChange(this.value)">
          <strong style="color:#166534;display:block;margin-top:2px;">✓ Accept</strong>
          <small style="color:#15803d;font-size:11px;">Accept manuscript for publication</small>
        </label>
        <label style="border:2px solid #fed7aa;background:#fff7ed;padding:10px;border-radius:8px;cursor:pointer;">
          <input type="radio" name="revision_outcome" value="further_revision" onchange="onRevOutcomeChange(this.value)">
          <strong style="color:#9a3412;display:block;margin-top:2px;">✎ Further Revision</strong>
          <small style="color:#c2410c;font-size:11px;">Request another revision cycle</small>
        </label>
        <label style="border:2px solid #fecaca;background:#fef2f2;padding:10px;border-radius:8px;cursor:pointer;">
          <input type="radio" name="revision_outcome" value="reject" onchange="onRevOutcomeChange(this.value)">
          <strong style="color:#991b1b;display:block;margin-top:2px;">✕ Reject</strong>
          <small style="color:#b91c1c;font-size:11px;">Reject manuscript</small>
        </label>
      </div>
    </div>

    <div class="form-group">
      <label for="eicRevComments">EIC Revised Document Comments / Remarks <span id="revCommentReq" style="color:#dc2626;display:none;">*</span></label>
      <textarea name="eic_comments" id="eicRevComments" class="form-control" rows="4" placeholder="Enter evaluation comments or instructions for the author..."></textarea>
    </div>
  </div>

  <!-- 6. Final Decision Template -->
  <div id="tmpl-decision">
    <div class="form-group">
      <label>Select Final Decision <span style="color:#dc2626;">*</span></label>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
        <label style="border:2px solid #bbf7d0;background:#f0fdf4;padding:12px;border-radius:8px;cursor:pointer;">
          <input type="radio" name="decision" value="accept" checked onchange="onDecisionChange(this.value)">
          <strong style="color:#166534;font-size:14px;display:block;margin-top:2px;">✓ ACCEPT MANUSCRIPT</strong>
          <small style="color:#15803d;font-size:12px;">Transitions manuscript to Accepted status and forwards to Production stream.</small>
        </label>
        <label style="border:2px solid #fecaca;background:#fef2f2;padding:12px;border-radius:8px;cursor:pointer;">
          <input type="radio" name="decision" value="reject" onchange="onDecisionChange(this.value)">
          <strong style="color:#991b1b;font-size:14px;display:block;margin-top:2px;">✕ REJECT MANUSCRIPT</strong>
          <small style="color:#b91c1c;font-size:12px;">Transitions manuscript to Rejected status. Reason required.</small>
        </label>
      </div>
    </div>

    <div id="decisionConfirmBox" style="padding:12px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;color:#166534;margin-bottom:14px;font-size:13px;">
      <strong>Acceptance Confirmation:</strong>
      <div>Paper ID: <strong><?=e($m['manuscript_no'])?></strong> &bull; Version: <strong><?=e($m['version_no'])?></strong></div>
      <div>Title: <em><?=e($m['title'])?></em></div>
    </div>

    <div class="form-group">
      <label for="decisionLetter">Decision Letter / Comments to Author <span id="decisionReqStar" style="color:#dc2626;display:none;">*</span></label>
      <textarea name="letter" id="decisionLetter" class="form-control" rows="5" placeholder="Enter decision comments or congratulatory acceptance letter..."></textarea>
      <div class="form-hint" id="decisionHintText">Comments will be delivered to the corresponding author via email and their dashboard.</div>
    </div>
  </div>

  <!-- Accept Shortcut Template -->
  <div id="tmpl-accept">
    <input type="hidden" name="decision" value="accept">
    <div style="padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;color:#166534;margin-bottom:16px;">
      <h4 style="margin:0 0 6px 0;font-size:15px;">Confirm Manuscript Acceptance</h4>
      Are you sure you want to accept this manuscript for publication in AJSMR?
      <div style="margin-top:6px;font-size:13px;">
        Paper ID: <strong><?=e($m['manuscript_no'])?></strong><br>
        Title: <em><?=e($m['title'])?></em><br>
        Version: <strong>Version <?=e($m['version_no'])?></strong>
      </div>
    </div>
    <div class="form-group">
      <label for="acceptLetter">Acceptance Comments / Remarks to Author (Optional)</label>
      <textarea name="letter" id="acceptLetter" class="form-control" rows="4" placeholder="Optional congratulatory message or publication instructions..."></textarea>
    </div>
  </div>

  <!-- Reject Shortcut Template -->
  <div id="tmpl-reject">
    <input type="hidden" name="decision" value="reject">
    <div style="padding:14px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;color:#991b1b;margin-bottom:16px;">
      <h4 style="margin:0 0 6px 0;font-size:15px;">Confirm Manuscript Rejection</h4>
      Are you sure you want to reject this manuscript? This will close the submission and record the final editorial decision.
    </div>
    <div class="form-group">
      <label for="rejectLetter">Rejection Reason / Comments to Author <span style="color:#dc2626;">*</span></label>
      <textarea name="letter" id="rejectLetter" class="form-control" rows="4" placeholder="State the reasons for rejection to be delivered to the author..." required></textarea>
    </div>
  </div>

  <!-- Assign Editor Template -->
  <div id="tmpl-assign_editor">
    <div class="form-group">
      <label for="assignEditorSelect">Select Editor <span style="color:#dc2626;">*</span></label>
      <select name="editor_id" id="assignEditorSelect" class="form-control" required>
        <option value="">-- Choose Editorial Board Member --</option>
        <?php foreach ($allEditors as $ed): ?>
          <option value="<?=(int)$ed['id']?>">
            <?=e($ed['full_name'] ?: $ed['email'])?> (<?=e(slabel($ed['role']))?>) — <?=e($ed['email'])?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="form-hint">Assigns a section editor to coordinate review.</div>
    </div>
  </div>

  <!-- Send Email Template -->
  <div id="tmpl-send_email">
    <div class="form-group">
      <label>Author Recipient</label>
      <input type="text" class="form-control" value="<?=e($authorEmailDisplay)?>" readonly style="background:#f1f5f9;cursor:not-allowed;">
      <div class="form-hint">Recipient is securely fetched from the manuscript record.</div>
    </div>
    <div class="form-group">
      <label for="emailSubject">Subject <span style="color:#dc2626;">*</span></label>
      <input type="text" name="subject" id="emailSubject" class="form-control" value="Regarding Manuscript <?=e($m['manuscript_no'])?> — AJSMR" required>
    </div>
    <div class="form-group">
      <label for="emailMessage">Message <span style="color:#dc2626;">*</span></label>
      <textarea name="message" id="emailMessage" class="form-control" rows="6" placeholder="Write your email message..." required></textarea>
    </div>
  </div>

  <!-- Add Note Template -->
  <div id="tmpl-add_note">
    <div style="font-size:12px;color:#64748b;margin-bottom:12px;background:#f8fafc;border:1px solid #e2e8f0;padding:10px;border-radius:6px;">
      <strong>Confidential Internal Note:</strong> Notes are strictly confidential and visible only to editorial staff. Authors never see these notes.
    </div>
    <div class="form-group">
      <label for="editorialNoteText">Editorial Note <span style="color:#dc2626;">*</span></label>
      <textarea name="note" id="editorialNoteText" class="form-control" rows="5" placeholder="Enter confidential editorial assessment, background notes, or instructions..." required></textarea>
    </div>
  </div>

  <!-- 7. Gallery Proof Template -->
  <div id="tmpl-gallery_proof">
    <div style="background:#f8fafc;padding:12px 14px;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:14px;font-size:13px;">
      <div>Paper ID: <strong><?=e($m['manuscript_no'])?></strong></div>
      <div>Title: <strong><?=e($m['title'])?></strong></div>
      <div>Current Version: <strong>Version <?=e($m['version_no'])?></strong></div>
      <?php if ($latestProof): ?>
        <div style="margin-top:4px;">Latest Proof Status: <span class="badge"><?=e(slabel($latestProof['status']))?></span> (Sent: <?=date('d M Y', strtotime((string)($latestProof['sent_at'] ?? $latestProof['created_at'])))?>) </div>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label>Select Gallery Proof Document <span style="color:#dc2626;">*</span></label>
      <input type="file" name="proof_file" class="form-control" accept=".pdf,.doc,.docx" required>
      <small style="color:#64748b;margin-top:4px;display:block;">Upload formatted proof document for author verification (PDF, DOC, or DOCX up to 25 MB).</small>
    </div>

    <div class="form-group">
      <label>Instructions / Comments for Author</label>
      <textarea name="proof_comments" class="form-control" rows="4" placeholder="Provide specific instructions or guidance for the author regarding proof review..."></textarea>
    </div>

    <div class="alert-box alert-success" style="background:#f0fdf4;color:#166534;border-color:#bbf7d0;margin-top:12px;">
      ✓ Sending this proof will notify the author and open the proof review workflow.
    </div>
  </div>

  <!-- 8. Printing / Publishing Template -->
  <div id="tmpl-publish_manuscript">
    <!-- Paper summary -->
    <div style="background:#f8fafc;padding:12px 16px;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:18px;font-size:13px;line-height:1.7;">
      <div>Paper ID: <strong><?=e($m['manuscript_no'])?></strong></div>
      <div>Title: <strong><?=e($m['title'])?></strong></div>
      <div>Current Publication Status:
        <?php
          if ($step9State === 'in_press') {
            echo '<strong style="color:#b45309;">In-Press</strong>';
          } elseif ($step9State === 'published') {
            echo '<strong style="color:#166534;">Published</strong>';
          } else {
            echo '<strong style="color:#64748b;">Not yet set</strong>';
          }
        ?>
      </div>
    </div>

    <!-- Status selector -->
    <div style="margin-bottom:18px;">
      <label style="display:block;font-size:13px;font-weight:700;margin-bottom:10px;">Select Publication Status:</label>

      <label id="pubOptInPress" for="pub_status_inpress"
             style="display:flex;align-items:center;gap:12px;padding:14px 16px;
                    border:2px solid #e2e8f0;border-radius:8px;cursor:pointer;
                    margin-bottom:8px;transition:border-color 0.15s,background 0.15s;">
        <input type="radio" id="pub_status_inpress" name="pub_status" value="IN_PRESS"
               onchange="onPubStatusChange(this.value)"
               <?=($currentPubStatus === 'IN_PRESS' || ($currentPubStatus === null && $step9State !== 'published')) ? 'checked' : ''?>>
        <span>
          <strong style="display:block;font-size:14px;color:#1e293b;">🖨️ In-Press</strong>
          <span style="font-size:12px;color:#64748b;">Manuscript is in production. Article is forthcoming — not yet publicly published.</span>
        </span>
      </label>

      <label id="pubOptPublished" for="pub_status_published"
             style="display:flex;align-items:center;gap:12px;padding:14px 16px;
                    border:2px solid #e2e8f0;border-radius:8px;cursor:pointer;
                    transition:border-color 0.15s,background 0.15s;">
        <input type="radio" id="pub_status_published" name="pub_status" value="PUBLISHED"
               onchange="onPubStatusChange(this.value)"
               <?=($currentPubStatus === 'PUBLISHED') ? 'checked' : ''?>>
        <span>
          <strong style="display:block;font-size:14px;color:#1e293b;">✅ Published</strong>
          <span style="font-size:12px;color:#64748b;">Article is officially published. Author will be notified and article registry updated.</span>
        </span>
      </label>
    </div>

    <!-- Publication date (shown only when Published is selected) -->
    <div id="pubDateRow" style="margin-bottom:14px;<?=($currentPubStatus === 'PUBLISHED') ? '' : 'display:none;'?>">
      <label style="font-size:13px;font-weight:600;">Publication Date</label>
      <input type="date" name="publication_date" class="form-control"
             value="<?=!empty($m['published_at']) ? date('Y-m-d', strtotime($m['published_at'])) : date('Y-m-d')?>">
    </div>

    <!-- Status info box -->
    <div id="pubStatusInfo" class="alert-box"
         style="background:#eff6ff;color:#1e40af;border-color:#bfdbfe;margin-top:4px;">
      <div id="pubStatusInfoText">
        <?php if ($step9State === 'published'): ?>
          ✅ This manuscript is currently <strong>Published</strong>. You may change it to In-Press if needed.
        <?php elseif ($step9State === 'in_press'): ?>
          🖨️ This manuscript is currently <strong>In-Press</strong>. Select Published when the article is live.
        <?php else: ?>
          Select <strong>In-Press</strong> to mark as forthcoming, or <strong>Published</strong> when the article is live.
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<!-- Non-intrusive Toast Notification -->
<div id="toastNotification" role="alert" aria-live="assertive"></div>

<script>
const ACTION_CONFIG = {
  technical_check: {
    title: 'Technical Check',
    submitText: 'Save Technical Check',
    btnClass: 'btn success'
  },
  assign_reviewer: {
    title: 'Assign Reviewer',
    submitText: 'Assign Reviewer',
    btnClass: 'btn'
  },
  reviewer_report: {
    title: 'Reviewer Reports',
    submitText: 'Close',
    btnClass: 'btn light'
  },
  request_revision: {
    title: 'Request Revised Document',
    submitText: 'Send Revision Request to Author',
    btnClass: 'btn warning'
  },
  review_revised_document: {
    title: 'Review Revised Manuscript',
    submitText: 'Save Revised Evaluation',
    btnClass: 'btn purple'
  },
  decision: {
    title: 'Final Editorial Decision',
    submitText: 'Save Final Decision',
    btnClass: 'btn success'
  },
  gallery_proof: {
    title: 'Send Gallery Proof to Author',
    submitText: 'Send Proof to Author',
    btnClass: 'btn success'
  },
  publish_manuscript: {
    title: 'Printing / Publishing',
    submitText: 'Save Status',
    btnClass: 'btn purple'
  },
  accept: {
    title: 'Accept Manuscript',
    submitText: '✓ Confirm Manuscript Acceptance',
    btnClass: 'btn success'
  },
  reject: {
    title: 'Reject Manuscript',
    submitText: '✕ Confirm Manuscript Rejection',
    btnClass: 'btn danger'
  },
  assign_editor: {
    title: 'Assign Section Editor',
    submitText: 'Assign Editor',
    btnClass: 'btn'
  },
  send_email: {
    title: 'Send Email to Author',
    submitText: 'Send Email',
    btnClass: 'btn'
  },
  add_note: {
    title: 'Add Confidential Internal Note',
    submitText: 'Save Note',
    btnClass: 'btn'
  }
};

function onTcResultChange(val) {
  const submitBtn = document.getElementById('modalSubmitBtn');
  const reqStar = document.getElementById('tcCommentReq');
  const hint = document.getElementById('tcHintText');
  const textarea = document.getElementById('tcComments');

  if (!submitBtn) return;
  if (val === 'passed') {
    submitBtn.textContent = '✓ Accept & Pass Check';
    submitBtn.className = 'btn success';
    if (reqStar) reqStar.style.display = 'none';
    if (textarea) textarea.required = false;
    if (hint) hint.textContent = 'Comments are optional for Accept.';
  } else if (val === 'failed') {
    submitBtn.textContent = '✕ Reject Manuscript';
    submitBtn.className = 'btn danger';
    if (reqStar) reqStar.style.display = 'inline';
    if (textarea) textarea.required = true;
    if (hint) hint.textContent = 'Rejection comments are required and will be delivered to the author.';
  } else if (val === 'minor_corrections') {
    submitBtn.textContent = 'Request Minor Corrections';
    submitBtn.className = 'btn warning';
    if (reqStar) reqStar.style.display = 'inline';
    if (textarea) textarea.required = true;
    if (hint) hint.textContent = 'Comments detailing minor corrections are required.';
  }
}

function onDecisionChange(val) {
  const submitBtn = document.getElementById('modalSubmitBtn');
  const confirmBox = document.getElementById('decisionConfirmBox');
  const reqStar = document.getElementById('decisionReqStar');
  const textarea = document.getElementById('decisionLetter');
  const hint = document.getElementById('decisionHintText');

  if (!submitBtn) return;
  if (val === 'accept') {
    submitBtn.textContent = '✓ Confirm Acceptance';
    submitBtn.className = 'btn success';
    if (confirmBox) {
      confirmBox.className = 'alert-box alert-success';
      confirmBox.style.background = '#f0fdf4';
      confirmBox.style.color = '#166534';
      confirmBox.style.borderColor = '#bbf7d0';
    }
    if (reqStar) reqStar.style.display = 'none';
    if (textarea) textarea.required = false;
    if (hint) hint.textContent = 'Acceptance comments or remarks are optional.';
  } else {
    submitBtn.textContent = '✕ Confirm Rejection';
    submitBtn.className = 'btn danger';
    if (confirmBox) {
      confirmBox.className = 'alert-box alert-danger';
      confirmBox.style.background = '#fef2f2';
      confirmBox.style.color = '#991b1b';
      confirmBox.style.borderColor = '#fecaca';
    }
    if (reqStar) reqStar.style.display = 'inline';
    if (textarea) textarea.required = true;
    if (hint) hint.textContent = 'Rejection reason is strictly required.';
  }
}

function onRevOutcomeChange(val) {
  const submitBtn = document.getElementById('modalSubmitBtn');
  const reqStar = document.getElementById('revCommentReq');
  const textarea = document.getElementById('eicRevComments');

  if (!submitBtn) return;
  if (val === 'accept') {
    submitBtn.textContent = '✓ Accept Revised Document';
    submitBtn.className = 'btn success';
    if (reqStar) reqStar.style.display = 'none';
    if (textarea) textarea.required = false;
  } else if (val === 'reject') {
    submitBtn.textContent = '✕ Reject Revised Document';
    submitBtn.className = 'btn danger';
    if (reqStar) reqStar.style.display = 'inline';
    if (textarea) textarea.required = true;
  } else {
    submitBtn.textContent = 'Request Further Revision';
    submitBtn.className = 'btn warning';
    if (reqStar) reqStar.style.display = 'inline';
    if (textarea) textarea.required = true;
  }
}

function onReviewerSelected(sel) {
  const opt = sel.options[sel.selectedIndex];
  const box = document.getElementById('reviewerDetailBox');
  if (!opt || !opt.value) {
    if (box) box.style.display = 'none';
    return;
  }
  document.getElementById('rvDetailName').textContent = opt.getAttribute('data-name') || '—';
  document.getElementById('rvDetailEmail').textContent = opt.getAttribute('data-email') || '—';
  document.getElementById('rvDetailAffiliation').textContent = opt.getAttribute('data-affiliation') || '—';
  document.getElementById('rvDetailExpertise').textContent = opt.getAttribute('data-expertise') || 'General';
  if (box) box.style.display = 'block';
}

function onPubStatusChange(val) {
  const dateRow = document.getElementById('pubDateRow');
  const infoText = document.getElementById('pubStatusInfoText');
  const submitBtn = document.getElementById('modalSubmitBtn');
  if (dateRow) dateRow.style.display = (val === 'PUBLISHED') ? 'block' : 'none';
  if (submitBtn) {
    if (val === 'PUBLISHED') {
      submitBtn.textContent = '✅ Set as Published';
      submitBtn.className = 'btn success';
    } else {
      submitBtn.textContent = '🖨️ Set as In-Press';
      submitBtn.className = 'btn warning';
    }
  }
  if (infoText) {
    if (val === 'PUBLISHED') {
      infoText.innerHTML = '✅ Saving will set manuscript status to <strong>Published</strong>. The author will be notified.';
    } else {
      infoText.innerHTML = '🖨️ Saving will set manuscript status to <strong>In-Press</strong>. Article is forthcoming.';
    }
  }
}

function openActionModal(actionKey) {
  const config = ACTION_CONFIG[actionKey];
  if (!config) return;

  const tmpl = document.getElementById('tmpl-' + actionKey);
  if (!tmpl) return;

  document.getElementById('modalTitle').textContent = config.title;
  document.getElementById('modalActionType').value = actionKey;

  const submitBtn = document.getElementById('modalSubmitBtn');
  submitBtn.textContent = config.submitText;
  submitBtn.className = config.btnClass || 'btn';
  submitBtn.disabled = false;

  // Reviewer report is a view-only modal by default
  if (actionKey === 'reviewer_report') {
    submitBtn.style.display = 'none';
  } else {
    submitBtn.style.display = 'inline-block';
  }

  const modalBody = document.getElementById('modalBodyContent');
  modalBody.innerHTML = tmpl.innerHTML;

  if (actionKey === 'technical_check') {
    const checked = modalBody.querySelector('input[name="result"]:checked');
    if (checked) onTcResultChange(checked.value);
  }
  if (actionKey === 'decision') {
    const checked = modalBody.querySelector('input[name="decision"]:checked');
    if (checked) onDecisionChange(checked.value);
  }
  if (actionKey === 'review_revised_document') {
    const checked = modalBody.querySelector('input[name="revision_outcome"]:checked');
    if (checked) onRevOutcomeChange(checked.value);
  }
  if (actionKey === 'publish_manuscript') {
    const checked = modalBody.querySelector('input[name="pub_status"]:checked');
    if (checked) onPubStatusChange(checked.value);
    // Apply border highlight to selected option
    modalBody.querySelectorAll('input[name="pub_status"]').forEach(r => {
      const lbl = r.closest('label');
      if (lbl) {
        lbl.style.borderColor = r.checked ? '#7c3aed' : '#e2e8f0';
        lbl.style.background  = r.checked ? '#faf5ff' : '';
      }
      r.addEventListener('change', function() {
        modalBody.querySelectorAll('input[name="pub_status"]').forEach(rb => {
          const l = rb.closest('label');
          if (l) {
            l.style.borderColor = rb.checked ? '#7c3aed' : '#e2e8f0';
            l.style.background  = rb.checked ? '#faf5ff' : '';
          }
        });
      });
    });
  }

  const errBox = document.getElementById('modalAlertError');
  errBox.style.display = 'none';
  errBox.textContent = '';

  const modal = document.getElementById('editorialActionModal');
  const dialog = modal ? modal.querySelector('.editorial-modal-dialog') : null;
  if (dialog) {
    if (actionKey === 'reviewer_report') {
      dialog.classList.add('modal-wide');
    } else {
      dialog.classList.remove('modal-wide');
    }
  }

  modal.style.display = 'flex';
  document.body.classList.add('modal-open');
  document.body.style.overflow = 'hidden';

  setTimeout(() => {
    const firstInput = modalBody.querySelector('select, input:not([type=hidden]):not([readonly]), textarea');
    if (firstInput) firstInput.focus();
  }, 50);
}

function closeModal() {
  const modal = document.getElementById('editorialActionModal');
  if (modal) {
    modal.style.display = 'none';
    const dialog = modal.querySelector('.editorial-modal-dialog');
    if (dialog) dialog.classList.remove('modal-wide');
  }
  document.body.classList.remove('modal-open');
  document.body.style.overflow = '';
  const bodyContent = document.getElementById('modalBodyContent');
  if (bodyContent) bodyContent.innerHTML = '';
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    const modal = document.getElementById('editorialActionModal');
    if (modal && modal.style.display !== 'none') closeModal();
  }
});

function showToast(message, type = 'success') {
  const toast = document.getElementById('toastNotification');
  if (!toast) return;
  toast.textContent = (type === 'success' ? '✓ ' : '✕ ') + message;
  toast.className = type === 'success' ? 'toast-success show' : 'toast-error show';
  setTimeout(() => {
    toast.className = toast.className.replace('show', '').trim();
  }, 4500);
}

async function submitEditorialAction(event) {
  event.preventDefault();
  const form = document.getElementById('editorialActionForm');
  const submitBtn = document.getElementById('modalSubmitBtn');
  const errBox = document.getElementById('modalAlertError');

  errBox.style.display = 'none';
  errBox.textContent = '';

  const originalBtnText = submitBtn.textContent;
  submitBtn.disabled = true;
  submitBtn.textContent = 'Processing...';

  try {
    const formData = new FormData(form);
    const response = await fetch(window.location.href, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    const data = await response.json();

    if (!data.success) {
      errBox.textContent = data.error || 'Operation failed. Please check your inputs and try again.';
      errBox.style.display = 'block';
      submitBtn.disabled = false;
      submitBtn.textContent = originalBtnText;
      return;
    }

    closeModal();
    showToast(data.message, 'success');

    // Reload page after a brief moment to update all sequential workflow stages and buttons
    setTimeout(() => {
      window.location.reload();
    }, 1200);

  } catch (err) {
    errBox.textContent = 'Network or server communication error. Please try again.';
    errBox.style.display = 'block';
    submitBtn.disabled = false;
    submitBtn.textContent = originalBtnText;
  }
}

function escapeHtml(text) {
  if (!text) return '';
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
</script>
<?php endif; ?>

<footer style="margin-top:40px;padding:24px 0;border-top:1px solid #e2e8f0;text-align:center;font-size:12px;color:#64748b;">
  &copy; <?=date('Y')?> American Journal of Science and Medical Research (AJSMR). All rights reserved. &bull; Editorial Management System
</footer>

</body>
</html>
