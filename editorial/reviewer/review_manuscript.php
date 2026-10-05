<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../workflow_v1_common.php';

$u = login_required();
$role = (string)($u['role'] ?? '');

if ($role !== 'reviewer' && !in_array($role, ['admin', 'editor_in_chief'], true)) {
    http_response_code(403);
    exit('Access denied. Reviewer authorization required.');
}

$pdo = db();
$uid = (int)$u['id'];
$userEmail = strtolower(trim((string)($u['email'] ?? '')));

$aid = filter_input(INPUT_GET, 'assignment_id', FILTER_VALIDATE_INT);
if (!$aid && isset($_GET['assignment_id'])) {
    $aid = filter_var($_GET['assignment_id'], FILTER_VALIDATE_INT);
}
if (!$aid && isset($_POST['assignment_id'])) {
    $aid = filter_var($_POST['assignment_id'], FILTER_VALIDATE_INT);
}
if (!$aid) {
    http_response_code(400);
    exit('Invalid review assignment ID.');
}

// -------------------------------------------------------------------------
// Strict IDOR Protection & Verification
// -------------------------------------------------------------------------
$stmtAssignment = $pdo->prepare("
    SELECT
        ra.*,
        rp.id AS pool_reviewer_id,
        rp.full_name AS reviewer_name,
        rp.email AS reviewer_email,
        m.id AS manuscript_id,
        m.manuscript_no,
        m.title,
        m.article_type,
        m.abstract AS abstract_text,
        m.version_no,
        m.manuscript_file,
        m.submitted_at,
        pr.id AS review_id,
        pr.recommendation,
        pr.comments_to_editor,
        pr.comments_to_author,
        pr.confidential_comments,
        pr.submitted_at AS review_submitted_at,
        pr.updated_at AS review_last_saved
    FROM ew_reviewer_assignments ra
    JOIN ew_reviewer_pool rp ON rp.id = ra.reviewer_id
    JOIN manuscripts m ON m.id = ra.manuscript_id
    LEFT JOIN ew_peer_reviews pr ON pr.assignment_id = ra.id
    WHERE ra.id = ?
    LIMIT 1
");
$stmtAssignment->execute([$aid]);
$a = $stmtAssignment->fetch(PDO::FETCH_ASSOC);

if (!$a) {
    http_response_code(404);
    exit('Review assignment record not found.');
}

// Ensure logged-in reviewer owns this assignment (or is EIC/admin for inspection)
$isOwnerReviewer = ($role === 'reviewer' && ((int)$a['reviewer_id'] === $uid || (int)$a['pool_reviewer_id'] === $uid || strtolower((string)$a['reviewer_email']) === $userEmail));
$isStaff = in_array($role, ['admin', 'editor_in_chief'], true);

if (!$isOwnerReviewer && !$isStaff) {
    http_response_code(403);
    exit('Access denied. You are not authorized to view or review this assignment.');
}

if ($a['status'] === 'declined' || $a['status'] === 'cancelled') {
    http_response_code(403);
    exit('This review invitation was ' . $a['status'] . '. You cannot submit a review for this assignment.');
}

if ($a['status'] === 'invited') {
    // If not accepted yet, redirect to dashboard with prompt to accept
    $_SESSION['wf_flash'] = 'Please accept the review invitation before opening the review workspace.';
    redirect('reviewer/index.php');
}

$isSubmitted = !empty($a['review_submitted_at']) || $a['status'] === 'completed';

// -------------------------------------------------------------------------
// POST Handlers: Save Draft or Submit Final Review
// -------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // Prevent modifying already submitted reviews
    if ($isSubmitted) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'This review has already been submitted and is locked for editing.']);
            exit;
        }
        $_SESSION['wf_flash'] = 'This review has already been submitted.';
        redirect('reviewer/review_manuscript.php?assignment_id=' . $aid);
    }

    // CSRF Check
    $postedCsrf = (string)($_POST['csrf'] ?? '');
    if (empty($_SESSION['csrf']) || !hash_equals((string)$_SESSION['csrf'], $postedCsrf)) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Security token invalid or expired. Please refresh the page and try again.']);
        exit;
    }

    $isDraft = isset($_POST['save_draft']);
    $recommendation = trim((string)($_POST['recommendation'] ?? ''));
    $majorComments = trim((string)($_POST['major_comments'] ?? ''));
    $minorComments = trim((string)($_POST['minor_comments'] ?? ''));
    $confidentialComments = trim((string)($_POST['confidential_comments'] ?? ''));
    $coi = trim((string)($_POST['conflict_of_interest'] ?? 'no'));
    $coiDetails = trim((string)($_POST['conflict_details'] ?? ''));

    // Ratings
    $ratings = [
        'originality' => trim((string)($_POST['rating_originality'] ?? '')),
        'methodology' => trim((string)($_POST['rating_methodology'] ?? '')),
        'scientific_quality' => trim((string)($_POST['rating_quality'] ?? '')),
        'clarity' => trim((string)($_POST['rating_clarity'] ?? '')),
        'relevance' => trim((string)($_POST['rating_relevance'] ?? '')),
        'references' => trim((string)($_POST['rating_references'] ?? ''))
    ];

    // Format author-facing comments
    $formattedAuthorComments = "=== MAJOR COMMENTS ===\n"
        . ($majorComments !== '' ? $majorComments : 'None')
        . "\n\n=== MINOR COMMENTS ===\n"
        . ($minorComments !== '' ? $minorComments : 'None');

    // Format editor-facing comments (ratings + COI + notes)
    $formattedEditorComments = "=== GENERAL ASSESSMENT RATINGS ===\n"
        . "Originality: " . ($ratings['originality'] ?: 'Not rated') . "\n"
        . "Scientific Quality: " . ($ratings['scientific_quality'] ?: 'Not rated') . "\n"
        . "Methodology: " . ($ratings['methodology'] ?: 'Not rated') . "\n"
        . "Clarity & Presentation: " . ($ratings['clarity'] ?: 'Not rated') . "\n"
        . "Relevance to Field: " . ($ratings['relevance'] ?: 'Not rated') . "\n"
        . "References: " . ($ratings['references'] ?: 'Not rated') . "\n\n"
        . "=== CONFLICT OF INTEREST ===\n"
        . ($coi === 'yes' ? "Conflict Declared: " . ($coiDetails ?: 'Details omitted') : "No conflict of interest declared.");

    // Validation for final submission
    if (!$isDraft) {
        $allowedRec = ['accept', 'minor_revision', 'major_revision', 'reject'];
        if (!in_array($recommendation, $allowedRec, true)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Please select a Reviewer Recommendation before final submission.']);
            exit;
        }

        if ($majorComments === '' && $minorComments === '') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Please provide evaluation comments (Major or Minor comments).']);
            exit;
        }

        // Declaration checkboxes
        if (empty($_POST['decl_independent']) || empty($_POST['decl_coi']) || empty($_POST['decl_confidential'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Please confirm all three Reviewer Declaration checkboxes before final submission.']);
            exit;
        }
    }

    try {
        $pdo->beginTransaction();

        // Handle Optional Annotated Manuscript Upload
        $annotatedFile = $_FILES['annotated_file'] ?? null;
        if ($annotatedFile && !empty($annotatedFile['name']) && (int)($annotatedFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo((string)$annotatedFile['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) {
                throw new RuntimeException('Annotated file must be a PDF, DOC, or DOCX document.');
            }
            if ((int)$annotatedFile['size'] > MAX_UPLOAD_BYTES) {
                throw new RuntimeException('Annotated file exceeds 25 MB size limit.');
            }

            $revUploadDir = __DIR__ . '/../uploads/reviews';
            if (!is_dir($revUploadDir) && !mkdir($revUploadDir, 0755, true)) {
                throw new RuntimeException('Failed to initialize review upload storage.');
            }

            $storedName = 'review_' . $a['manuscript_no'] . '_ra' . $aid . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
            $destPath = $revUploadDir . DIRECTORY_SEPARATOR . $storedName;

            if (move_uploaded_file((string)$annotatedFile['tmp_name'], $destPath)) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($destPath) ?: 'application/octet-stream';
                $fileSize = filesize($destPath) ?: (int)$annotatedFile['size'];
                $sha256 = hash_file('sha256', $destPath) ?: null;

                // Store annotated file in manuscript_versions (manuscript_files does not exist)
                try {
                    $stmtF = $pdo->prepare("
                        INSERT INTO manuscript_versions (
                            manuscript_id, version_no, file_path, author_response, uploaded_by, created_at
                        ) VALUES (?, ?, ?, 'reviewer_annotated', ?, NOW())
                    ");
                    $stmtF->execute([
                        $a['manuscript_id'],
                        $a['version_no'],
                        'uploads/reviews/' . $storedName,
                        $uid,
                    ]);
                } catch (Throwable $eF) {
                    // Non-fatal: annotated file metadata could not be saved
                }
            }
        }

        // Save into ew_peer_reviews
        if ($isDraft) {
            $stmtPr = $pdo->prepare("
                INSERT INTO ew_peer_reviews (
                    assignment_id, recommendation, comments_to_editor, comments_to_author, confidential_comments, updated_at
                ) VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    recommendation = VALUES(recommendation),
                    comments_to_editor = VALUES(comments_to_editor),
                    comments_to_author = VALUES(comments_to_author),
                    confidential_comments = VALUES(confidential_comments),
                    updated_at = NOW()
            ");
            $stmtPr->execute([
                $aid,
                $recommendation !== '' ? $recommendation : null,
                $formattedEditorComments,
                $formattedAuthorComments,
                $confidentialComments
            ]);

            // Update assignment status to in_review
            $pdo->prepare("UPDATE ew_reviewer_assignments SET status = 'in_review' WHERE id = ? AND status = 'accepted'")->execute([$aid]);

            audit('review_draft_saved', (int)$a['manuscript_id'], "Reviewer saved draft review for assignment $aid.");

            $pdo->commit();

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'is_draft' => true,
                    'message' => 'Review draft saved successfully. You can return at any time to continue.'
                ]);
                exit;
            }

            $_SESSION['wf_flash'] = 'Review draft saved successfully.';
            redirect('reviewer/review_manuscript.php?assignment_id=' . $aid);

        } else {
            // Final Submission
            $stmtPr = $pdo->prepare("
                INSERT INTO ew_peer_reviews (
                    assignment_id, recommendation, comments_to_editor, comments_to_author, confidential_comments, submitted_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    recommendation = VALUES(recommendation),
                    comments_to_editor = VALUES(comments_to_editor),
                    comments_to_author = VALUES(comments_to_author),
                    confidential_comments = VALUES(confidential_comments),
                    submitted_at = NOW(),
                    updated_at = NOW()
            ");
            $stmtPr->execute([
                $aid,
                $recommendation,
                $formattedEditorComments,
                $formattedAuthorComments,
                $confidentialComments
            ]);

            // Update assignment status to completed
            $pdo->prepare("UPDATE ew_reviewer_assignments SET status = 'completed', completed_at = NOW() WHERE id = ?")->execute([$aid]);

            audit('review_submitted', (int)$a['manuscript_id'], "Reviewer {$a['reviewer_name']} submitted final report. Rec: $recommendation.");
            $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, 'review_submitted', ?, ?)")
                ->execute([$a['manuscript_id'], $uid, "Peer review submitted for assignment $aid. Recommendation: $recommendation", $_SERVER['REMOTE_ADDR'] ?? null]);

            $pdo->commit();

            sendWorkflowNotification($pdo, 'REVIEW_SUBMITTED', (int)$a['manuscript_id'], [
                'reviewer_id'    => $a['reviewer_id'],
                'assignment_id'  => $aid,
                'recommendation' => $recommendation
            ]);

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'is_submitted' => true,
                    'message' => 'Thank you! Your peer review has been submitted successfully to the Editor-in-Chief.'
                ]);
                exit;
            }

            $_SESSION['wf_flash'] = 'Your peer review has been submitted successfully.';
            redirect('reviewer/review_manuscript.php?assignment_id=' . $aid);
        }

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage() ?: 'An error occurred while saving the review.']);
            exit;
        }
        $_SESSION['wf_flash'] = 'Error: ' . $e->getMessage();
        redirect('reviewer/review_manuscript.php?assignment_id=' . $aid);
    }
}

// -------------------------------------------------------------------------
// Load Manuscript Documents for Reviewer (Fallback resolution)
// -------------------------------------------------------------------------
$files = [];
try {
    // manuscript_files does not exist — use manuscript_versions
    $stmtFiles = $pdo->prepare('
        SELECT id, manuscript_id, version_no, file_path, file_path AS relative_path,
               "main_manuscript" AS file_type,
               SUBSTRING_INDEX(file_path, "/", -1) AS original_name,
               0 AS file_size, created_at AS uploaded_at
        FROM manuscript_versions
        WHERE manuscript_id = ? AND (author_response IS NULL OR author_response NOT IN ("reviewer_annotated"))
        ORDER BY version_no DESC, id ASC
    ');
    $stmtFiles->execute([(int)$a['manuscript_id']]);
    $files = $stmtFiles->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $eFiles) {
    $files = [];
}

if (empty($files) && !empty($a['manuscript_file'])) {
    $rel = (string)$a['manuscript_file'];
    $files[] = [
        'id' => (int)$a['manuscript_id'],
        'manuscript_id' => (int)$a['manuscript_id'],
        'version_no' => (int)($a['version_no'] ?? 1),
        'file_path' => $rel,
        'relative_path' => $rel,
        'file_type' => 'main_manuscript',
        'original_name' => basename($rel),
        'file_size' => 0,
        'uploaded_at' => $a['submitted_at'] ?? date('Y-m-d H:i:s'),
    ];
}

foreach ($files as &$f) {
    $relPath = (string)($f['relative_path'] ?? ($f['file_path'] ?? ''));
    if (((int)($f['file_size'] ?? 0)) <= 0 && $relPath !== '') {
        $diskPath = __DIR__ . '/../' . ltrim(str_replace(['/', '\\'], '/', $relPath), '/');
        if (is_file($diskPath)) {
            $f['file_size'] = (int)filesize($diskPath);
        }
    }
    if (empty($f['original_name']) && $relPath !== '') {
        $f['original_name'] = basename($relPath);
    }
}
unset($f);

// Check if reviewer has already uploaded an annotated review file
$myAnnotatedFile = null;
try {
    // manuscript_files does not exist; check manuscript_versions where author_response = 'reviewer_annotated'
    $stmtMyAnnotated = $pdo->prepare("
        SELECT id, SUBSTRING_INDEX(file_path,'/',-1) AS original_name, 0 AS file_size, created_at AS uploaded_at
        FROM manuscript_versions
        WHERE manuscript_id = ? AND author_response = 'reviewer_annotated'
        ORDER BY id DESC LIMIT 1
    ");
    $stmtMyAnnotated->execute([(int)$a['manuscript_id']]);
    $myAnnotatedFile = $stmtMyAnnotated->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $eAnnotated) {
    $myAnnotatedFile = null;
}

// Parse existing draft comments if available
$existingAuthorComments = (string)($a['comments_to_author'] ?? '');
$existingMajor = '';
$existingMinor = '';

if (strpos($existingAuthorComments, '=== MAJOR COMMENTS ===') !== false) {
    $parts = explode('=== MINOR COMMENTS ===', $existingAuthorComments);
    $existingMajor = trim(str_replace('=== MAJOR COMMENTS ===', '', $parts[0] ?? ''));
    if ($existingMajor === 'None') $existingMajor = '';
    $existingMinor = trim($parts[1] ?? '');
    if ($existingMinor === 'None') $existingMinor = '';
} else {
    $existingMajor = $existingAuthorComments;
}

$existingConfidential = (string)($a['confidential_comments'] ?? '');
$existingRecommendation = (string)($a['recommendation'] ?? '');

// Parse Ratings from comments_to_editor if present
$editorText = (string)($a['comments_to_editor'] ?? '');
function extractRating(string $text, string $key): string {
    if (preg_match('/' . preg_quote($key, '/') . ':\s*([^\n\r]+)/i', $text, $m)) {
        return trim($m[1]);
    }
    return '';
}
$savedRatings = [
    'originality' => extractRating($editorText, 'Originality'),
    'scientific_quality' => extractRating($editorText, 'Scientific Quality'),
    'methodology' => extractRating($editorText, 'Methodology'),
    'clarity' => extractRating($editorText, 'Clarity & Presentation'),
    'relevance' => extractRating($editorText, 'Relevance to Field'),
    'references' => extractRating($editorText, 'References')
];

// Deadline & Countdown Calculation
$nowTime = time();
$dueTimestamp = $a['due_at'] ? strtotime((string)$a['due_at']) : 0;
$daysDiff = $dueTimestamp > 0 ? (int)ceil(($dueTimestamp - $nowTime) / 86400) : null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manuscript Review: <?=e($a['manuscript_no'])?> — AJSMR</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#f4f7fb;color:#25344a;font-family:Arial,Helvetica,sans-serif}
.top{background:linear-gradient(135deg,#092b5f,#0b5fa5);color:#fff;padding:20px 30px}
.top-inner{max-width:1250px;margin:auto;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
.brand{font-size:20px;font-weight:800}
.brand small{display:block;font-size:12px;font-weight:400;margin-top:3px;color:#dbeafe}
.nav-links a{color:#fff;text-decoration:none;border:1px solid #ffffff55;border-radius:6px;padding:7px 12px;font-size:13px;margin-left:8px}
.nav-links a:hover{background:rgba(255,255,255,0.15)}
.wrap{max-width:1200px;margin:24px auto;padding:0 18px}

.panel{background:#fff;border:1px solid #e3e9f1;border-radius:10px;box-shadow:0 4px 15px rgba(16,32,64,0.05);padding:22px 24px;margin-bottom:22px}
.panel-title{font-size:16px;font-weight:800;color:#0f172a;margin:0 0 14px 0;padding-bottom:10px;border-bottom:1px solid #eef2f6;display:flex;justify-content:space-between;align-items:center}

.badge{display:inline-block;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:bold;text-transform:uppercase}
.badge.completed{background:#dcfce7;color:#166534}
.badge.in_review{background:#e0f2fe;color:#0369a1}
.badge.overdue{background:#fee2e2;color:#991b1b}

table{width:100%;border-collapse:collapse;margin-top:6px}
th,td{padding:10px 12px;border:1px solid #e2e8f0;text-align:left;vertical-align:middle;font-size:13px}
th{background:#f8fafc;color:#475569;font-weight:bold;font-size:11px;text-transform:uppercase}

.btn{display:inline-block;background:#0b5fa5;color:#fff;border:0;border-radius:6px;padding:9px 16px;text-decoration:none;font-weight:bold;font-size:13px;cursor:pointer;transition:all 0.15s;line-height:1.2}
.btn:hover{background:#084b84}
.btn.light{background:#eaf2f9;color:#0b5fa5}
.btn.light:hover{background:#d7e8f7}
.btn.success{background:#16a34a;color:#fff}
.btn.success:hover{background:#15803d}
.btn.danger{background:#dc2626;color:#fff}
.btn.danger:hover{background:#b91c1c}

.form-group{margin-bottom:20px}
.form-group label{display:block;font-weight:700;font-size:13px;color:#334155;margin-bottom:6px}
.form-hint{font-size:12px;color:#64748b;margin-top:4px;line-height:1.4}
.form-control{width:100%;border:1px solid #cbd5e1;border-radius:6px;padding:9px 12px;font-size:14px;background:#fff;font-family:inherit;box-sizing:border-box}
.form-control:focus{outline:none;border-color:#0b5fa5;box-shadow:0 0 0 3px rgba(11,95,165,0.15)}
textarea.form-control{min-height:110px;resize:vertical}

.rating-grid{display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin-bottom:14px}
.rating-item{background:#fff;border:1px solid #cbd5e1;border-radius:6px;padding:10px 12px}
.rating-item label{font-size:12px;font-weight:700;color:#1e293b;display:block;margin-bottom:6px}

.declaration-box{background:#f8fafc;border:1px solid #cbd5e1;border-radius:8px;padding:16px;margin-bottom:20px}
.declaration-item{display:flex;align-items:flex-start;gap:10px;margin-bottom:10px;cursor:pointer;font-size:13px;color:#334155}
.declaration-item input[type=checkbox]{width:17px;height:17px;margin-top:1px;cursor:pointer}

/* Recommendations Selection */
.rec-cards{display:grid;grid-template-columns:repeat(4, 1fr);gap:12px;margin-bottom:20px}
@media(max-width:800px){.rec-cards{grid-template-columns:1fr 1fr}}
@media(max-width:480px){.rec-cards{grid-template-columns:1fr}}

.rec-card{border:2px solid #cbd5e1;background:#fff;border-radius:8px;padding:14px;cursor:pointer;transition:all 0.15s;display:flex;flex-direction:column;gap:4px}
.rec-card:hover{border-color:#0b5fa5;background:#f8fafc}
.rec-card input[type=radio]{margin:0 6px 0 0}
.rec-card.accept-card{border-color:#bbf7d0;background:#f0fdf4}
.rec-card.minor-card{border-color:#fed7aa;background:#fff7ed}
.rec-card.major-card{border-color:#fed7aa;background:#fff7ed}
.rec-card.reject-card{border-color:#fecaca;background:#fef2f2}

.confidential-badge{display:inline-block;padding:3px 8px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:4px;font-size:11px;font-weight:bold;margin-left:6px}

/* Modals */
.editorial-modal{position:fixed;top:0;left:0;right:0;bottom:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px}
.editorial-modal-backdrop{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(2px)}
.editorial-modal-dialog{position:relative;background:#fff;border-radius:12px;box-shadow:0 20px 45px rgba(0,0,0,0.25);max-width:600px;width:100%;display:flex;flex-direction:column;z-index:1;overflow:hidden;animation:modalIn 0.2s cubic-bezier(0.16,1,0.3,1)}
@keyframes modalIn{from{opacity:0;transform:translateY(12px) scale(0.98)}to{opacity:1;transform:translateY(0) scale(1)}}
.editorial-modal-header{padding:18px 24px;border-bottom:1px solid #e2e8f0;display:flex;align-items:flex-start;justify-content:space-between;background:#f8fafc}
.modal-close-btn{background:none;border:none;font-size:26px;line-height:1;color:#64748b;cursor:pointer;padding:0}
.editorial-modal-body{padding:22px 24px;font-size:14px;line-height:1.5}
.editorial-modal-footer{padding:16px 24px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px;background:#f8fafc}

/* Toast */
#toastNotification{position:fixed;top:20px;right:20px;z-index:10000;background:#0f172a;color:#fff;padding:14px 20px;border-radius:8px;box-shadow:0 10px 25px rgba(0,0,0,0.25);display:flex;align-items:center;gap:12px;font-size:14px;font-weight:500;transition:opacity 0.3s, transform 0.3s;transform:translateY(-10px);opacity:0;pointer-events:none}
#toastNotification.show{transform:translateY(0);opacity:1;pointer-events:auto}
#toastNotification.toast-success{background:#15803d;color:#fff}
#toastNotification.toast-error{background:#b91c1c;color:#fff}
</style>
</head>
<body>

<header class="top">
  <div class="top-inner">
    <div class="brand">
      AJSMR — Editorial Management System
      <small>Reviewer Workspace &bull; Confidential Peer Review</small>
    </div>
    <div class="nav-links">
      <a href="index.php">← Back to Dashboard</a>
      <a href="../logout.php">Sign Out</a>
    </div>
  </div>
</header>

<main class="wrap">

  <!-- Flash Message -->
  <?php if (!empty($_SESSION['wf_flash'])): ?>
    <div style="background:#e0f2fe;border:1px solid #bae6fd;color:#0369a1;padding:12px 18px;border-radius:8px;margin-bottom:20px;font-size:14px;">
      <?=e($_SESSION['wf_flash'])?>
    </div>
    <?php unset($_SESSION['wf_flash']); ?>
  <?php endif; ?>

  <!-- Submitted Read-Only Banner -->
  <?php if ($isSubmitted): ?>
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-left:5px solid #16a34a;border-radius:8px;padding:16px 20px;color:#166534;margin-bottom:22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
      <div>
        <h3 style="margin:0 0 4px 0;font-size:17px;">✓ Review Submitted Successfully</h3>
        <p style="margin:0;font-size:13px;">Your evaluation and recommendations were recorded on <?=date('d M Y, H:i', strtotime((string)$a['review_submitted_at']))?> and delivered to the Editor-in-Chief.</p>
      </div>
      <div>
        <a class="btn light" style="background:#dcfce7;color:#166534;" href="index.php">Return to Dashboard &rarr;</a>
      </div>
    </div>
  <?php endif; ?>

  <!-- Header Card: Manuscript Information (DOUBLE-BLIND SAFE: NO AUTHOR INFO) -->
  <div class="panel">
    <div class="panel-title">
      <span>Manuscript Details (Blind Review)</span>
      <span style="font-size:12px;font-weight:normal;color:#64748b;">Assignment #<?=(int)$a['id']?></span>
    </div>

    <div style="font-size:13px;font-weight:bold;color:#0b5fa5;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
      <?=e($a['article_type'] ?: 'Research Article')?>
    </div>
    <h1 style="margin:0 0 14px 0;font-size:22px;color:#0f172a;line-height:1.3;"><?=e($a['title'])?></h1>

    <div style="display:flex;gap:18px;flex-wrap:wrap;font-size:13px;color:#475569;background:#f8fafc;padding:12px 16px;border-radius:8px;border:1px solid #e2e8f0;">
      <div>Paper ID: <strong style="color:#0f172a;"><?=e($a['manuscript_no'])?></strong></div>
      <div>Version: <strong style="color:#0f172a;">Version <?=e($a['version_no'])?></strong></div>
      <div>Review Deadline: <strong style="color:#0f172a;"><?=date('d M Y', strtotime((string)$a['due_at']))?></strong></div>
      <div>
        Status:
        <?php if ($isSubmitted): ?>
          <span class="badge completed">Submitted</span>
        <?php elseif ($daysDiff !== null && $daysDiff < 0): ?>
          <span class="badge overdue">Overdue by <?=abs($daysDiff)?> day(s)</span>
        <?php else: ?>
          <span class="badge in_review"><?=e(slabel($a['status']))?> (<?=$daysDiff?> days remaining)</span>
        <?php endif; ?>
      </div>
    </div>

    <div style="margin-top:18px;">
      <h4 style="margin:0 0 6px 0;font-size:14px;color:#334155;">Abstract</h4>
      <div style="font-size:14px;line-height:1.6;color:#334155;white-space:pre-wrap;background:#fff;border:1px solid #edf2f7;border-radius:6px;padding:14px;">
        <?=e($a['abstract_text'] ?: 'No abstract text supplied.')?>
      </div>
    </div>
  </div>

  <!-- Documents Panel -->
  <div class="panel">
    <div class="panel-title">
      <span>Manuscript Documents for Evaluation</span>
      <span style="font-size:12px;font-weight:normal;color:#64748b;">Secure access via AJSMR file handler</span>
    </div>

    <?php if (empty($files)): ?>
      <p style="color:#64748b;font-size:13px;margin:0;">No document files attached.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Document Type</th>
            <th>File Description</th>
            <th>Size</th>
            <th>Uploaded Date</th>
            <th style="width:140px;text-align:center;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($files as $f): ?>
            <tr>
              <td><strong><?=e(ucwords(str_replace('_', ' ', (string)$f['file_type'])))?></strong></td>
              <td><?=e($a['manuscript_no']) . ' (Version ' . (int)$f['version_no'] . ')'?></td>
              <td><?=number_format(((int)$f['file_size']) / 1024, 1)?> KB</td>
              <td><?=date('d M Y', strtotime((string)$f['uploaded_at']))?></td>
              <td style="text-align:center;">
                <a class="btn light" style="padding:5px 12px;font-size:12px;" href="../download_manuscript_file.php?id=<?=(int)$f['id']?>" target="_blank">
                  View / Download ↗
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- Peer Review Form (Active or Read-Only) -->
  <div class="panel">
    <div class="panel-title">
      <span>Peer Review Evaluation Form</span>
      <?php if (!empty($a['review_last_saved'])): ?>
        <span style="font-size:12px;font-weight:normal;color:#64748b;">Last saved: <?=date('d M Y, H:i', strtotime((string)$a['review_last_saved']))?></span>
      <?php endif; ?>
    </div>

    <form id="peerReviewForm" method="post" action="review_manuscript.php?assignment_id=<?=$aid?>" enctype="multipart/form-data">
      <input type="hidden" name="assignment_id" value="<?=$aid?>">
      <input type="hidden" name="csrf" value="<?=e(csrf())?>">

      <!-- 1. General Assessment Ratings -->
      <div class="form-group">
        <label>1. General Scientific Assessment</label>
        <div class="form-hint" style="margin-bottom:10px;">Please rate the manuscript across key scientific criteria.</div>

        <div class="rating-grid">
          <?php
          $scale = ['Excellent', 'Good', 'Adequate', 'Needs Improvement', 'Poor'];
          $criteria = [
              'rating_originality' => ['Originality & Novelty', 'originality'],
              'rating_quality' => ['Scientific Quality & Rigor', 'scientific_quality'],
              'rating_methodology' => ['Methodology & Design', 'methodology'],
              'rating_clarity' => ['Clarity & Presentation', 'clarity'],
              'rating_relevance' => ['Relevance to Journal Scope', 'relevance'],
              'rating_references' => ['References & Literature Context', 'references']
          ];
          foreach ($criteria as $field => $c):
              $saved = $savedRatings[$c[1]] ?? '';
          ?>
            <div class="rating-item">
              <label for="<?=$field?>"><?=e($c[0])?></label>
              <select name="<?=$field?>" id="<?=$field?>" class="form-control" style="padding:6px 10px;font-size:13px;" <?=$isSubmitted ? 'disabled' : ''?>>
                <option value="">-- Rate Criterion --</option>
                <?php foreach ($scale as $s): ?>
                  <option value="<?=$s?>" <?= (stripos($saved, $s) !== false) ? 'selected' : '' ?>><?=e($s)?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- 2. Major Comments (Author-Visible) -->
      <div class="form-group">
        <label for="majorComments">2. Major Comments (Visible to Author) <span style="color:#dc2626;">*</span></label>
        <div class="form-hint">Provide detailed evaluation regarding major scientific, methodological, data interpretation, or structural issues.</div>
        <textarea name="major_comments" id="majorComments" class="form-control" rows="6" placeholder="Enter major comments and substantive issues for the author..." <?=$isSubmitted ? 'readonly' : ''?>><?=e($existingMajor)?></textarea>
      </div>

      <!-- 3. Minor Comments (Author-Visible) -->
      <div class="form-group">
        <label for="minorComments">3. Minor Comments (Visible to Author)</label>
        <div class="form-hint">Address grammar, phrasing, formatting, typographical errors, figures/tables clarity, and reference adjustments.</div>
        <textarea name="minor_comments" id="minorComments" class="form-control" rows="4" placeholder="Enter minor corrections or clarifications..." <?=$isSubmitted ? 'readonly' : ''?>><?=e($existingMinor)?></textarea>
      </div>

      <!-- 4. Confidential Comments to Editor-in-Chief -->
      <div class="form-group">
        <label for="confidentialComments">
          4. Confidential Comments to Editor-in-Chief
          <span class="confidential-badge">CONFIDENTIAL &bull; EIC ONLY</span>
        </label>
        <div class="form-hint">Strictly confidential to the Editor-in-Chief. These comments will NEVER be shown or released to the authors.</div>
        <textarea name="confidential_comments" id="confidentialComments" class="form-control" rows="4" placeholder="Confidential remarks regarding scientific integrity, novelty, or editorial suggestions..." <?=$isSubmitted ? 'readonly' : ''?>><?=e($existingConfidential)?></textarea>
      </div>

      <!-- 5. Conflict of Interest Declaration -->
      <div class="form-group">
        <label>5. Conflict of Interest Declaration</label>
        <div style="display:flex;gap:20px;margin-bottom:8px;padding:4px 0;">
          <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
            <input type="radio" name="conflict_of_interest" value="no" checked <?=$isSubmitted ? 'disabled' : ''?> onchange="toggleCoiBox(false)">
            <strong>No conflict of interest</strong>
          </label>
          <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
            <input type="radio" name="conflict_of_interest" value="yes" <?=$isSubmitted ? 'disabled' : ''?> onchange="toggleCoiBox(true)">
            <strong style="color:#d97706;">I have a potential conflict of interest</strong>
          </label>
        </div>
        <div id="coiDetailsBox" style="display:none;margin-top:8px;">
          <textarea name="conflict_details" class="form-control" rows="2" placeholder="Please provide specific details regarding your declared conflict of interest..." <?=$isSubmitted ? 'readonly' : ''?>></textarea>
        </div>
      </div>

      <!-- 6. Upload Annotated Manuscript (Optional) -->
      <?php if (!$isSubmitted): ?>
        <div class="form-group">
          <label for="annotatedFile">6. Upload Annotated Manuscript (Optional)</label>
          <div class="form-hint">If you made comments or markups directly in the PDF or Word document, upload it here. Kept confidential to EIC.</div>
          <input type="file" name="annotated_file" id="annotatedFile" class="form-control" accept=".pdf,.doc,.docx" style="padding:6px;">
        </div>
      <?php elseif (!empty($myAnnotatedFile)): ?>
        <div class="form-group">
          <label>6. Uploaded Annotated Manuscript</label>
          <div>
            <a class="btn light" style="font-size:12px;" href="../download_manuscript_file.php?id=<?=(int)$myAnnotatedFile['id']?>" target="_blank">
              View Uploaded Annotation (<?=e($myAnnotatedFile['original_name'])?>) ↗
            </a>
          </div>
        </div>
      <?php endif; ?>

      <!-- 7. Reviewer Declarations -->
      <div class="declaration-box">
        <label style="display:block;font-weight:700;font-size:13px;color:#1e293b;margin-bottom:8px;">
          7. Reviewer Declarations (Required for Final Submission)
        </label>
        <label class="declaration-item">
          <input type="checkbox" name="decl_independent" value="1" <?=$isSubmitted ? 'checked disabled' : 'required'?>>
          <span>I confirm that I have evaluated this manuscript independently and that my comments reflect my objective assessment.</span>
        </label>
        <label class="declaration-item">
          <input type="checkbox" name="decl_coi" value="1" <?=$isSubmitted ? 'checked disabled' : 'required'?>>
          <span>I confirm that I have no undisclosed commercial, financial, or personal conflicts of interest with this work.</span>
        </label>
        <label class="declaration-item">
          <input type="checkbox" name="decl_confidential" value="1" <?=$isSubmitted ? 'checked disabled' : 'required'?>>
          <span>I understand that this manuscript and review are strictly confidential and must not be shared or used prior to publication.</span>
        </label>
      </div>

      <!-- 8. Reviewer Recommendation -->
      <div class="form-group">
        <label style="font-size:14px;color:#0f172a;">
          8. Reviewer Recommendation <span style="color:#dc2626;">*</span>
        </label>
        <div class="form-hint" style="margin-bottom:12px;">This is your confidential recommendation to the Editor-in-Chief. The final editorial decision will be made by the EIC.</div>

        <div class="rec-cards">
          <label class="rec-card accept-card">
            <div>
              <input type="radio" name="recommendation" value="accept" <?= ($existingRecommendation === 'accept') ? 'checked' : '' ?> <?=$isSubmitted ? 'disabled' : ''?>>
              <strong style="color:#166534;font-size:14px;">Accept</strong>
            </div>
            <small style="color:#15803d;font-size:11px;">Publish as is or with negligible proof corrections.</small>
          </label>

          <label class="rec-card minor-card">
            <div>
              <input type="radio" name="recommendation" value="minor_revision" <?= ($existingRecommendation === 'minor_revision') ? 'checked' : '' ?> <?=$isSubmitted ? 'disabled' : ''?>>
              <strong style="color:#9a3412;font-size:14px;">Minor Revision</strong>
            </div>
            <small style="color:#c2410c;font-size:11px;">Requires minor clarifications, references, or text improvements.</small>
          </label>

          <label class="rec-card major-card">
            <div>
              <input type="radio" name="recommendation" value="major_revision" <?= ($existingRecommendation === 'major_revision') ? 'checked' : '' ?> <?=$isSubmitted ? 'disabled' : ''?>>
              <strong style="color:#9a3412;font-size:14px;">Major Revision</strong>
            </div>
            <small style="color:#c2410c;font-size:11px;">Substantive methodological, experimental, or analysis revisions required.</small>
          </label>

          <label class="rec-card reject-card">
            <div>
              <input type="radio" name="recommendation" value="reject" <?= ($existingRecommendation === 'reject') ? 'checked' : '' ?> <?=$isSubmitted ? 'disabled' : ''?>>
              <strong style="color:#991b1b;font-size:14px;">Reject</strong>
            </div>
            <small style="color:#b91c1c;font-size:11px;">Fundamental flaws, out of scope, or insufficient scientific novelty.</small>
          </label>
        </div>
      </div>

      <!-- Action Buttons -->
      <?php if (!$isSubmitted): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #edf2f7;padding-top:18px;flex-wrap:wrap;gap:12px;">
          <div>
            <button type="submit" name="save_draft" value="1" class="btn light" id="saveDraftBtn">
              💾 Save Draft
            </button>
            <span style="font-size:12px;color:#64748b;margin-left:8px;">You can save and resume your review at any time.</span>
          </div>

          <div>
            <button type="button" class="btn success" style="padding:11px 22px;font-size:14px;" onclick="openSubmitConfirmModal()">
              ✓ Submit Final Review
            </button>
          </div>
        </div>
      <?php endif; ?>
    </form>
  </div>

</main>

<!-- ========================================================================= -->
<!-- MODAL: Confirm Final Review Submission                                    -->
<!-- ========================================================================= -->
<div id="submitConfirmModal" class="editorial-modal" style="display:none;" role="dialog" aria-modal="true">
  <div class="editorial-modal-backdrop" onclick="closeConfirmModal()"></div>
  <div class="editorial-modal-dialog">
    <div class="editorial-modal-header">
      <h3 style="margin:0;font-size:17px;color:#0f172a;">Confirm Final Review Submission</h3>
      <button type="button" class="modal-close-btn" onclick="closeConfirmModal()">&times;</button>
    </div>

    <div class="editorial-modal-body">
      <div style="padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;color:#166534;margin-bottom:14px;">
        <h4 style="margin:0 0 6px 0;font-size:15px;">Ready to transmit your peer review?</h4>
        <div>Paper ID: <strong><?=e($a['manuscript_no'])?></strong></div>
        <div style="margin-top:2px;">Title: <em><?=e($a['title'])?></em></div>
        <div style="margin-top:4px;">Your Selected Recommendation: <strong id="modalConfirmRec" style="text-transform:uppercase;"></strong></div>
      </div>

      <p style="font-size:13px;color:#475569;margin:0;">
        Once submitted, your review is officially recorded and forwarded to the Editor-in-Chief. You will not be able to edit your evaluation after final submission.
      </p>
    </div>

    <div class="editorial-modal-footer">
      <button type="button" class="btn light" onclick="closeConfirmModal()">Cancel &amp; Continue Editing</button>
      <button type="button" class="btn success" id="finalConfirmSubmitBtn" onclick="doFinalSubmit()">
        ✓ Yes, Submit Final Review
      </button>
    </div>
  </div>
</div>

<div id="toastNotification" role="alert" aria-live="assertive"></div>

<script>
function showToast(msg, type = 'success') {
  const t = document.getElementById('toastNotification');
  if (!t) return;
  t.textContent = (type === 'success' ? '✓ ' : '✕ ') + msg;
  t.className = type === 'success' ? 'toast-success show' : 'toast-error show';
  setTimeout(() => { t.className = t.className.replace('show', '').trim(); }, 4000);
}

function toggleCoiBox(show) {
  const box = document.getElementById('coiDetailsBox');
  if (box) box.style.display = show ? 'block' : 'none';
}

function closeConfirmModal() {
  document.getElementById('submitConfirmModal').style.display = 'none';
  document.body.style.overflow = '';
}

function openSubmitConfirmModal() {
  const recInput = document.querySelector('input[name="recommendation"]:checked');
  if (!recInput) {
    alert('Please select a Reviewer Recommendation before submitting.');
    return;
  }

  const major = document.getElementById('majorComments').value.trim();
  const minor = document.getElementById('minorComments').value.trim();
  if (!major && !minor) {
    alert('Please enter your evaluation comments (Major or Minor comments).');
    return;
  }

  const d1 = document.querySelector('input[name="decl_independent"]').checked;
  const d2 = document.querySelector('input[name="decl_coi"]').checked;
  const d3 = document.querySelector('input[name="decl_confidential"]').checked;
  if (!d1 || !d2 || !d3) {
    alert('Please confirm all three Reviewer Declaration checkboxes.');
    return;
  }

  const recLabels = {
    'accept': 'Accept',
    'minor_revision': 'Minor Revision',
    'major_revision': 'Major Revision',
    'reject': 'Reject'
  };
  document.getElementById('modalConfirmRec').textContent = recLabels[recInput.value] || recInput.value;
  document.getElementById('submitConfirmModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function doFinalSubmit() {
  closeConfirmModal();
  const form = document.getElementById('peerReviewForm');
  const btn = document.getElementById('finalConfirmSubmitBtn');
  btn.disabled = true;
  form.submit();
}

// Intercept draft save to make it smooth
document.getElementById('peerReviewForm').addEventListener('submit', function(e) {
  // If saving draft, let it submit normally or via fetch
});
</script>

<footer style="margin-top:40px;padding:24px 0;border-top:1px solid #e2e8f0;text-align:center;font-size:12px;color:#64748b;">
  &copy; <?=date('Y')?> American Journal of Science and Medical Research (AJSMR). All rights reserved. &bull; Reviewer Portal
</footer>

</body>
</html>
