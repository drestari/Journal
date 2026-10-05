<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../workflow_v1_common.php';

$u = login_required();
$pdo = db();

$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
if (!$id && isset($_POST['manuscript_id'])) {
    $id = filter_var($_POST['manuscript_id'], FILTER_VALIDATE_INT);
}
if (!$id) {
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
    http_response_code(404);
    exit('Manuscript not found.');
}

$role = (string)($u['role'] ?? '');
$isStaff = in_array($role, ['admin', 'editor_in_chief', 'editor', 'managing_editor', 'production'], true);

// Verify Ownership for Author
$isOwner = false;
if ((int)$m['corresponding_author_id'] === (int)$u['id']) {
    $isOwner = true;
} elseif (!empty($m['author_email']) && strtolower((string)$m['author_email']) === strtolower((string)$u['email'])) {
    $isOwner = true;
} else {
    // Check manuscript_authors table
    try {
        $maCheck = $pdo->prepare("SELECT id FROM manuscript_authors WHERE manuscript_id = ? AND LOWER(email) = LOWER(?) LIMIT 1");
        $maCheck->execute([$id, (string)$u['email']]);
        if ($maCheck->fetch()) {
            $isOwner = true;
        }
    } catch (Throwable $e) {}
}

if (!$isStaff && !$isOwner) {
    http_response_code(403);
    exit('Access denied. You are only authorized to view workflow progress for your own manuscripts.');
}

// Handle Author Proof Response POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_author_proof_response') {
    check_csrf();
    $decision = trim((string)($_POST['proof_decision'] ?? 'approved'));
    $comments = trim((string)($_POST['author_comments'] ?? ''));

    if (!in_array($decision, ['approved', 'corrections_requested'], true)) {
        wf_flash('Please select a valid proof decision (Approve or Request Corrections).');
        header('Location: ' . BASE_URL . 'author/workflow.php?id=' . $id);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $pStmt = $pdo->prepare("SELECT * FROM ew_galley_proofs WHERE manuscript_id = ? ORDER BY id DESC LIMIT 1");
        $pStmt->execute([$id]);
        $proof = $pStmt->fetch(PDO::FETCH_ASSOC);

        $proofId = $proof['id'] ?? null;
        $approvedAt = ($decision === 'approved') ? date('Y-m-d H:i:s') : null;

        $apCheck = $pdo->prepare("SELECT id FROM ew_author_proof_approval WHERE manuscript_id = ? ORDER BY id DESC LIMIT 1");
        $apCheck->execute([$id]);
        $existingAp = $apCheck->fetch(PDO::FETCH_ASSOC);

        if ($existingAp) {
            $stmtUp = $pdo->prepare("
                UPDATE ew_author_proof_approval
                SET proof_id = ?, author_id = ?, decision = ?, comments = ?, approved_at = ?
                WHERE id = ?
            ");
            $stmtUp->execute([$proofId, $u['id'], $decision, $comments, $approvedAt, $existingAp['id']]);
        } else {
            $stmtIns = $pdo->prepare("
                INSERT INTO ew_author_proof_approval (manuscript_id, proof_id, author_id, decision, comments, approved_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtIns->execute([$id, $proofId, $u['id'], $decision, $comments, $approvedAt]);
        }

        if ($proofId) {
            if ($decision === 'approved') {
                $pdo->prepare("UPDATE ew_galley_proofs SET status = 'approved', approved_at = NOW() WHERE id = ?")->execute([$proofId]);
            } else {
                $pdo->prepare("UPDATE ew_galley_proofs SET status = 'corrections_received', corrections = ? WHERE id = ?")->execute([$comments, $proofId]);
            }
        }

        $newMsStatus = ($decision === 'approved') ? 'PROOF_APPROVED' : 'PROOF_RESPONSE_RECEIVED';
        $pdo->prepare("UPDATE manuscripts SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newMsStatus, $id]);

        try {
            $prodStatus = ($decision === 'approved') ? 'APPROVED' : 'SENT';
            $pdo->prepare("
                INSERT INTO production (manuscript_id, proof_status)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE proof_status = VALUES(proof_status)
            ")->execute([$id, $prodStatus]);
        } catch (Throwable $eP) {}

        audit('author_proof_response_submitted', $id, "Author submitted proof response: $decision.");
        $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)")
            ->execute([$id, $u['id'], 'author_proof_response_submitted', "Author proof decision: $decision. Comments: $comments", $_SERVER['REMOTE_ADDR'] ?? null]);

        $pdo->commit();

        wf_flash($decision === 'approved' ? 'Thank you! Your gallery proof approval has been submitted to the editorial team.' : 'Your gallery proof corrections have been submitted to the editorial team.');
        header('Location: ' . BASE_URL . 'author/workflow.php?id=' . $id);
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        wf_flash('Failed to submit proof response: ' . $e->getMessage());
        header('Location: ' . BASE_URL . 'author/workflow.php?id=' . $id);
        exit;
    }
}

// Handle Revision Upload POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_revision') {
    check_csrf();
    $authorResponse = trim((string)($_POST['author_response'] ?? ''));

    if (empty($_FILES['revised_file']['name'])) {
        wf_flash('Please select a revised manuscript file to upload.');
        header('Location: ' . BASE_URL . 'author/workflow.php?id=' . $id);
        exit;
    }

    $file = $_FILES['revised_file'];
    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_MANUSCRIPT_EXT, true)) {
        wf_flash('Invalid file format. Allowed formats: PDF, DOC, DOCX.');
        header('Location: ' . BASE_URL . 'author/workflow.php?id=' . $id);
        exit;
    }

    if ((int)$file['size'] > MAX_UPLOAD_BYTES) {
        wf_flash('File size exceeds maximum limit of 25 MB.');
        header('Location: ' . BASE_URL . 'author/workflow.php?id=' . $id);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/manuscripts';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        exit('Failed to initialize storage directory.');
    }

    $targetVersion = (int)$m['version_no'] + 1;
    $storedName = $m['manuscript_no'] . '_v' . $targetVersion . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $destPath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;

    if (!move_uploaded_file((string)$file['tmp_name'], $destPath)) {
        http_response_code(500);
        exit('Failed to save uploaded file.');
    }

    $originalName = basename((string)$file['name']);
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($destPath) ?: ($file['type'] ?? 'application/octet-stream');
    $fileSize = filesize($destPath) ?: (int)$file['size'];
    $sha256 = hash_file('sha256', $destPath) ?: null;
    $relativePath = 'uploads/manuscripts/' . $storedName;

    try {
        $pdo->beginTransaction();

        // Insert into manuscript_versions (manuscript_files table does not exist)
        $stmtMV = $pdo->prepare("
            INSERT INTO manuscript_versions (
                manuscript_id, version_no, file_path, author_response, uploaded_by, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmtMV->execute([$id, $targetVersion, $relativePath, $authorResponse, $u['id']]);

        $stmtRev = $pdo->prepare("
            UPDATE ew_revisions
            SET status = 'received', received_at = NOW(), response_text = ?
            WHERE manuscript_id = ? AND status = 'requested'
            ORDER BY version_no DESC LIMIT 1
        ");
        $stmtRev->execute([$authorResponse, $id]);

        $stmtUp = $pdo->prepare("
            UPDATE manuscripts
            SET version_no = ?, status = 'REVISED_SUBMISSION', updated_at = NOW()
            WHERE id = ?
        ");
        $stmtUp->execute([$targetVersion, $id]);

        audit('revised_document_submitted', $id, "Author submitted revised document Version $targetVersion.");
        $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)")
            ->execute([$id, $u['id'], 'revised_document_submitted', "Revised document Version $targetVersion uploaded: $originalName", $_SERVER['REMOTE_ADDR'] ?? null]);

        $pdo->commit();

        wf_flash("Revised manuscript (Version $targetVersion) uploaded successfully. The editorial office has been notified.");
        header('Location: ' . BASE_URL . 'author/workflow.php?id=' . $id);
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        wf_flash("Failed to process revision upload: " . $e->getMessage());
        header('Location: ' . BASE_URL . 'author/workflow.php?id=' . $id);
        exit;
    }
}

// Fetch Workflow Records
$tcStmt = $pdo->prepare('SELECT * FROM ew_technical_checks WHERE manuscript_id = ? ORDER BY id DESC LIMIT 1');
$tcStmt->execute([$id]);
$techCheck = $tcStmt->fetch(PDO::FETCH_ASSOC);

$revAssStmt = $pdo->prepare("SELECT COUNT(*) FROM ew_reviewer_assignments WHERE manuscript_id = ? AND status <> 'cancelled'");
$revAssStmt->execute([$id]);
$reviewerCount = (int)$revAssStmt->fetchColumn();

$revRepStmt = $pdo->prepare("SELECT COUNT(*) FROM ew_peer_reviews pr JOIN ew_reviewer_assignments ra ON ra.id = pr.assignment_id WHERE ra.manuscript_id = ? AND pr.submitted_at IS NOT NULL");
$revRepStmt->execute([$id]);
$reportCount = (int)$revRepStmt->fetchColumn();

$revisionsStmt = $pdo->prepare('SELECT * FROM ew_revisions WHERE manuscript_id = ? ORDER BY version_no DESC');
$revisionsStmt->execute([$id]);
$revisions = $revisionsStmt->fetchAll(PDO::FETCH_ASSOC);
$latestRevision = $revisions[0] ?? null;

$decStmt = $pdo->prepare('SELECT * FROM ew_editorial_decisions WHERE manuscript_id = ? ORDER BY id DESC LIMIT 1');
$decStmt->execute([$id]);
$latestDecision = $decStmt->fetch(PDO::FETCH_ASSOC);

// Fetch Galley Proof and Author Proof Approval Records
$galleyProofStmt = $pdo->prepare('SELECT * FROM ew_galley_proofs WHERE manuscript_id = ? ORDER BY id DESC LIMIT 1');
$galleyProofStmt->execute([$id]);
$latestProof = $galleyProofStmt->fetch(PDO::FETCH_ASSOC);

$authorProofStmt = $pdo->prepare('SELECT * FROM ew_author_proof_approval WHERE manuscript_id = ? ORDER BY id DESC LIMIT 1');
$authorProofStmt->execute([$id]);
$latestAuthorProofAppr = $authorProofStmt->fetch(PDO::FETCH_ASSOC);

$needsProofReview = false;
if ($latestProof) {
    if (($latestProof['status'] ?? '') === 'sent_to_author' || strtoupper((string)$m['status']) === 'PROOF_SENT') {
        if (empty($latestAuthorProofAppr['approved_at']) && ($latestAuthorProofAppr['decision'] ?? '') !== 'approved') {
            $needsProofReview = true;
        }
    }
}

// Fetch files from manuscript_versions (manuscript_files table does not exist)
$files = [];
try {
    $mvStmt = $pdo->prepare('SELECT id, manuscript_id, version_no, file_path AS relative_path, author_response, uploaded_by, created_at, "main_manuscript" AS file_type, SUBSTRING_INDEX(file_path, "/", -1) AS original_name FROM manuscript_versions WHERE manuscript_id = ? ORDER BY version_no ASC');
    $mvStmt->execute([$id]);
    $files = $mvStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $eMV) { $files = []; }

// Compute Workflow Stages for Author View
$currentStatus = strtoupper((string)$m['status']);

// Technical Check Stage
$tcState = 'pending';
$tcLabel = 'Under Screening';
if (!empty($techCheck['result'])) {
    if ($techCheck['result'] === 'passed') {
        $tcState = 'passed';
        $tcLabel = 'Passed';
    } elseif ($techCheck['result'] === 'minor_corrections') {
        $tcState = 'minor_corrections';
        $tcLabel = 'Corrections Required';
    } elseif ($techCheck['result'] === 'failed') {
        $tcState = 'failed';
        $tcLabel = 'Failed / Rejected';
    }
}

// Peer Review Stage
$reviewState = 'pending';
$reviewLabel = 'Awaiting Peer Review';
if ($reportCount > 0) {
    $reviewState = 'completed';
    $reviewLabel = 'Reports Received';
} elseif ($reviewerCount > 0) {
    $reviewState = 'in_progress';
    $reviewLabel = 'Under Review';
} elseif ($tcState === 'passed') {
    $reviewState = 'pending';
    $reviewLabel = 'Reviewers Being Assigned';
}

// Revision Stage
$revisionState = 'not_required';
$revisionLabel = 'Not Required';
$needsAuthorUpload = false;
if ($latestRevision) {
    if ($latestRevision['status'] === 'requested') {
        $revisionState = 'requested';
        $revisionLabel = 'Revision Requested';
        $needsAuthorUpload = true;
    } elseif ($latestRevision['status'] === 'received') {
        $revisionState = 'received';
        $revisionLabel = 'Revised Document Submitted';
    } elseif ($latestRevision['status'] === 'accepted') {
        $revisionState = 'accepted';
        $revisionLabel = 'Revision Accepted';
    }
}
if ($currentStatus === 'REVISION_REQUIRED' || $tcState === 'minor_corrections') {
    if ($revisionState === 'not_required') {
        $revisionState = 'requested';
        $revisionLabel = 'Revision Requested';
    }
    $needsAuthorUpload = true;
}

// Final Decision Stage
$decState = 'pending';
$decLabel = 'Awaiting Decision';
if ($currentStatus === 'ACCEPTED' || (!empty($latestDecision) && $latestDecision['decision'] === 'accept')) {
    $decState = 'accepted';
    $decLabel = 'Accepted';
} elseif ($currentStatus === 'REJECTED' || (!empty($latestDecision) && $latestDecision['decision'] === 'reject')) {
    $decState = 'rejected';
    $decLabel = 'Rejected';
} elseif ($currentStatus === 'REVISION_REQUIRED') {
    $decState = 'revision';
    $decLabel = 'Revision Required';
}

// Gallery Proof Stage
$proofState = 'pending';
$proofLabel = 'Awaiting Proof';
$proofStatusText = 'Proof Pending';

if ($latestProof) {
    if (!empty($latestAuthorProofAppr['approved_at']) || ($latestAuthorProofAppr['decision'] ?? '') === 'approved' || ($latestProof['status'] ?? '') === 'approved' || $currentStatus === 'PROOF_APPROVED') {
        $proofState = 'approved';
        $proofLabel = 'Proof Approved';
        $proofStatusText = 'Proof Approved';
    } elseif (($latestAuthorProofAppr['decision'] ?? '') === 'corrections_requested' || ($latestProof['status'] ?? '') === 'corrections_received' || $currentStatus === 'PROOF_RESPONSE_RECEIVED') {
        $proofState = 'corrections';
        $proofLabel = 'Corrections Requested';
        $proofStatusText = 'Corrections Requested';
    } elseif (($latestProof['status'] ?? '') === 'sent_to_author' || $currentStatus === 'PROOF_SENT') {
        $proofState = 'sent';
        $proofLabel = 'Proof Sent to Author';
        $proofStatusText = 'Proof Sent to Author';
    }
}

// Author Proof Response Stage
$respState = 'pending';
$respLabel = 'Pending';
if ($latestAuthorProofAppr) {
    if (($latestAuthorProofAppr['decision'] ?? '') === 'approved') {
        $respState = 'approved';
        $respLabel = 'Approved';
    } else {
        $respState = 'corrections';
        $respLabel = 'Corrections Requested';
    }
} elseif ($proofState === 'sent' || $needsProofReview) {
    $respState = 'in_progress';
    $respLabel = 'Awaiting Response';
}

// Printing / Publication Stage
$pubStageState = 'pending';
$pubStageLabel = 'Pending';
if ($currentStatus === 'PUBLISHED' || $currentStatus === 'published') {
    $pubStageState = 'published';
    $pubStageLabel = 'Published';
} elseif ($currentStatus === 'IN_PRESS' || $currentStatus === 'in_press') {
    $pubStageState = 'in_progress';
    $pubStageLabel = 'In-Press';
} elseif ($currentStatus === 'PRINTING' || $currentStatus === 'PUBLISHING' || ($proofState === 'approved' && $respState === 'approved')) {
    $pubStageState = 'in_progress';
    $pubStageLabel = 'In Progress';
}

// Published Stage
$publishedState = 'pending';
$publishedLabel = 'Not Published';
if ($currentStatus === 'PUBLISHED' || $currentStatus === 'published') {
    $publishedState = 'published';
    $publishedLabel = 'Published';
} elseif ($currentStatus === 'IN_PRESS' || $currentStatus === 'in_press') {
    // In-Press = forthcoming, not yet published
    $publishedState = 'in_press';
    $publishedLabel = 'In-Press (Forthcoming)';
}

$pageTitle = 'Workflow Progress — ' . $m['manuscript_no'] . ' — AJSMR Author';
include __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
  <div>
    <h1 class="title" style="margin:0 0 4px 0;">Manuscript Workflow Progress</h1>
    <div style="font-size:15px;color:#0b5fa5;font-weight:bold;margin-bottom:4px;"><?=e($m['manuscript_no'])?> &bull; <?=e($m['title'])?></div>
    <div class="muted">Submitted on <?=date('d M Y', strtotime((string)$m['submitted_at']))?> &bull; Version v<?=e($m['version_no'])?></div>
  </div>
  <div>
    <a class="btn secondary" href="<?=BASE_URL?>author/submissions.php">&larr; Back to My Submissions</a>
  </div>
</div>

<?php wf_flash_show(); ?>

<?php if ($needsAuthorUpload): ?>
  <div style="background:#fffbeb;border:2px solid #fde68a;border-radius:10px;padding:20px;margin-bottom:24px;box-shadow:0 4px 12px rgba(245,158,11,0.15);">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
      <span style="font-size:22px;">✎</span>
      <h3 style="margin:0;color:#92400e;font-size:18px;">Action Required: Submit Revised Manuscript</h3>
    </div>
    <p style="margin:0 0 14px 0;color:#78350f;font-size:14px;line-height:1.5;">
      The Editor-in-Chief has requested a revised version of your manuscript based on technical or peer review evaluations.
    </p>
    <?php if ($latestRevision && !empty($latestRevision['response_text'])): ?>
      <div style="background:#fff;border:1px solid #fcd34d;padding:14px;border-radius:8px;margin-bottom:16px;">
        <strong style="display:block;color:#92400e;font-size:13px;margin-bottom:6px;">Instructions / Editorial Comments to Author:</strong>
        <div style="white-space:pre-wrap;font-size:13px;color:#334155;"><?=e($latestRevision['response_text'])?></div>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" style="background:#fff;padding:16px;border-radius:8px;border:1px solid #e2e8f0;">
      <input type="hidden" name="csrf" value="<?=e(csrf())?>">
      <input type="hidden" name="action" value="upload_revision">
      <input type="hidden" name="manuscript_id" value="<?=$id?>">

      <div style="margin-bottom:14px;">
        <label style="display:block;font-weight:bold;margin-bottom:6px;font-size:13px;color:#1e293b;">Select Revised Manuscript File <span style="color:#dc2626;">*</span></label>
        <input type="file" name="revised_file" accept=".pdf,.doc,.docx" required style="width:100%;max-width:500px;padding:8px;border:1px solid #cbd5e1;border-radius:6px;background:#f8fafc;">
        <small style="display:block;color:#64748b;margin-top:4px;">Accepted formats: PDF, DOC, DOCX. Max file size: 25 MB.</small>
      </div>

      <div style="margin-bottom:16px;">
        <label style="display:block;font-weight:bold;margin-bottom:6px;font-size:13px;color:#1e293b;">Author Response / Rebuttal Comments (Optional)</label>
        <textarea name="author_response" rows="4" style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:6px;" placeholder="Provide a summary of changes made in response to editorial feedback..."></textarea>
      </div>

      <button class="btn primary" type="submit" style="padding:10px 20px;font-size:14px;">Submit Revised Manuscript (v<?=(int)$m['version_no']+1?>)</button>
    </form>
  </div>
<?php endif; ?>

<?php if ($latestProof): ?>
  <div style="background:#f0fdf4;border:2px solid #bbf7d0;border-radius:10px;padding:20px;margin-bottom:24px;box-shadow:0 4px 12px rgba(22,163,74,0.15);">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:12px;">
      <div style="display:flex;align-items:center;gap:10px;">
        <span style="font-size:22px;color:#166534;">📄</span>
        <h3 style="margin:0;color:#166534;font-size:18px;">GALLERY PROOF</h3>
      </div>
      <div>
        <span class="badge" style="background:<?=$proofState==='approved'?'#dcfce7':($proofState==='sent'?'#fef3c7':'#dbeafe')?>;color:<?=$proofState==='approved'?'#166534':($proofState==='sent'?'#92400e':'#1e40af')?>;font-size:13px;padding:6px 12px;border-radius:20px;font-weight:bold;">
          Status: <?=e($proofStatusText)?>
        </span>
      </div>
    </div>

    <div style="background:#fff;border:1px solid #bbf7d0;padding:16px;border-radius:8px;margin-bottom:16px;">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:10px;">
        <div>
          <strong style="color:#166534;font-size:15px;display:block;">Gallery Proof Document (Version <?=(int)($latestProof['proof_version'] ?? 1)?>)</strong>
          <small style="color:#64748b;font-size:12px;">Sent Date: <?=date('d M Y, H:i', strtotime((string)($latestProof['sent_at'] ?? $latestProof['created_at'])))?></small>
        </div>
        <?php if (!empty($latestProof['id'])): ?>
          <a class="btn primary" style="padding:8px 16px;font-size:13px;" href="<?=BASE_URL?>download_manuscript_file.php?proof_id=<?=(int)$latestProof['id']?>" target="_blank">View / Download Gallery Proof ↗</a>
        <?php endif; ?>
      </div>

      <?php if (!empty($latestProof['comments'])): ?>
        <div style="margin-top:12px;padding-top:12px;border-top:1px solid #e2e8f0;">
          <strong style="display:block;color:#1e293b;font-size:13px;margin-bottom:4px;">EIC Instructions / Comments:</strong>
          <div style="white-space:pre-wrap;font-size:13px;color:#334155;background:#f8fafc;padding:10px;border-radius:6px;border:1px solid #e2e8f0;"><?=e($latestProof['comments'])?></div>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($needsProofReview): ?>
      <form method="post" style="background:#fff;padding:18px;border-radius:8px;border:1px solid #e2e8f0;">
        <input type="hidden" name="csrf" value="<?=e(csrf())?>">
        <input type="hidden" name="action" value="submit_author_proof_response">
        <input type="hidden" name="manuscript_id" value="<?=$id?>">

        <h4 style="margin:0 0 12px 0;font-size:15px;color:#0f172a;">Review &amp; Submit Proof Response</h4>

        <div style="margin-bottom:14px;">
          <label style="display:block;font-weight:bold;margin-bottom:6px;font-size:13px;color:#1e293b;">Proof Review Decision <span style="color:#dc2626;">*</span></label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <label style="border:2px solid #bbf7d0;background:#f0fdf4;padding:12px;border-radius:8px;cursor:pointer;">
              <input type="radio" name="proof_decision" value="approved" checked>
              <strong style="color:#166534;display:block;margin-top:2px;">✓ Approve Proof for Printing</strong>
              <small style="color:#15803d;font-size:11px;">I confirm the proof is correct and ready for publication.</small>
            </label>
            <label style="border:2px solid #fed7aa;background:#fff7ed;padding:12px;border-radius:8px;cursor:pointer;">
              <input type="radio" name="proof_decision" value="corrections_requested">
              <strong style="color:#9a3412;display:block;margin-top:2px;">✎ Request Proof Corrections</strong>
              <small style="color:#c2410c;font-size:11px;">I need minor corrections before printing.</small>
            </label>
          </div>
        </div>

        <div style="margin-bottom:16px;">
          <label style="display:block;font-weight:bold;margin-bottom:6px;font-size:13px;color:#1e293b;">Author Response / Correction Notes</label>
          <textarea name="author_comments" rows="4" style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:6px;" placeholder="Enter any typographical or layout correction notes (if requesting corrections) or general remarks..."></textarea>
        </div>

        <button class="btn primary" type="submit" style="padding:10px 20px;font-size:14px;">Submit Proof Response</button>
      </form>
    <?php elseif ($latestAuthorProofAppr): ?>
      <div style="background:#fff;padding:16px;border-radius:8px;border:1px solid #e2e8f0;">
        <h4 style="margin:0 0 8px 0;font-size:14px;color:#0f172a;">Your Submitted Proof Response</h4>
        <div style="font-size:13px;margin-bottom:6px;">
          Decision: <strong style="color:<?=$latestAuthorProofAppr['decision']==='approved'?'#166534':'#92400e'?>;"><?=e(ucwords((string)$latestAuthorProofAppr['decision']))?></strong>
          <?php if (!empty($latestAuthorProofAppr['approved_at'])): ?>
            &bull; Date: <?=date('d M Y, H:i', strtotime((string)$latestAuthorProofAppr['approved_at']))?>
          <?php endif; ?>
        </div>
        <?php if (!empty($latestAuthorProofAppr['comments'])): ?>
          <div style="white-space:pre-wrap;font-size:13px;color:#475569;background:#f8fafc;padding:10px;border-radius:6px;border:1px solid #e2e8f0;"><?=e($latestAuthorProofAppr['comments'])?></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<!-- Workflow Timeline Card -->
<div class="panel" style="margin-bottom:24px;">
  <h2 style="font-size:18px;margin:0 0 18px 0;padding-bottom:12px;border-bottom:1px solid #e2e8f0;">Evaluation Workflow Stage Timeline</h2>
  
  <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(130px, 1fr));gap:10px;">
    <!-- Step 1: Submission -->
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:9px;padding:12px;text-align:center;">
      <div style="font-size:22px;color:#166534;margin-bottom:4px;">✓</div>
      <div style="font-weight:bold;font-size:13px;color:#166534;">1. Submission</div>
      <div style="font-size:11px;color:#15803d;margin-top:2px;">Submitted</div>
      <small style="color:#64748b;font-size:10px;display:block;margin-top:2px;"><?=date('d M Y', strtotime((string)$m['submitted_at']))?></small>
    </div>

    <!-- Step 2: Technical Check -->
    <div style="background:<?=$tcState==='passed'?'#f0fdf4':($tcState==='minor_corrections'?'#fff7ed':($tcState==='failed'?'#fef2f2':'#f8fafc'))?>;border:1px solid <?=$tcState==='passed'?'#bbf7d0':($tcState==='minor_corrections'?'#fed7aa':($tcState==='failed'?'#fecaca':'#cbd5e1'))?>;border-radius:9px;padding:12px;text-align:center;">
      <div style="font-size:22px;color:<?=$tcState==='passed'?'#166534':($tcState==='minor_corrections'?'#c2410c':($tcState==='failed'?'#991b1b':'#64748b'))?>;margin-bottom:4px;">
        <?=$tcState==='passed'?'✓':($tcState==='minor_corrections'?'✎':($tcState==='failed'?'✕':'●'))?>
      </div>
      <div style="font-weight:bold;font-size:13px;color:<?=$tcState==='passed'?'#166534':($tcState==='minor_corrections'?'#c2410c':($tcState==='failed'?'#991b1b':'#1e293b'))?>;">2. Tech Check</div>
      <div style="font-size:11px;color:<?=$tcState==='passed'?'#15803d':($tcState==='minor_corrections'?'#c2410c':($tcState==='failed'?'#b91c1c':'#64748b'))?>;margin-top:2px;"><?=e($tcLabel)?></div>
    </div>

    <!-- Step 3: Peer Review -->
    <div style="background:<?=$reviewState==='completed'?'#f0fdf4':($reviewState==='in_progress'?'#eff6ff':'#f8fafc')?>;border:1px solid <?=$reviewState==='completed'?'#bbf7d0':($reviewState==='in_progress'?'#bfdbfe':'#cbd5e1')?>;border-radius:9px;padding:12px;text-align:center;">
      <div style="font-size:22px;color:<?=$reviewState==='completed'?'#166534':($reviewState==='in_progress'?'#1d4ed8':'#64748b')?>;margin-bottom:4px;">
        <?=$reviewState==='completed'?'✓':($reviewState==='in_progress'?'●':'○')?>
      </div>
      <div style="font-weight:bold;font-size:13px;color:<?=$reviewState==='completed'?'#166534':($reviewState==='in_progress'?'#1e40af':'#1e293b')?>;">3. Peer Review</div>
      <div style="font-size:11px;color:<?=$reviewState==='completed'?'#15803d':($reviewState==='in_progress'?'#1d4ed8':'#64748b')?>;margin-top:2px;"><?=e($reviewLabel)?></div>
    </div>

    <!-- Step 4: Revision -->
    <div style="background:<?=$revisionState==='accepted'?'#f0fdf4':($revisionState==='received'?'#eff6ff':($revisionState==='requested'?'#fff7ed':'#f8fafc'))?>;border:1px solid <?=$revisionState==='accepted'?'#bbf7d0':($revisionState==='received'?'#bfdbfe':($revisionState==='requested'?'#fed7aa':'#cbd5e1'))?>;border-radius:9px;padding:12px;text-align:center;">
      <div style="font-size:22px;color:<?=$revisionState==='accepted'?'#166534':($revisionState==='received'?'#1d4ed8':($revisionState==='requested'?'#c2410c':'#64748b'))?>;margin-bottom:4px;">
        <?=$revisionState==='accepted'?'✓':($revisionState==='received'?'●':($revisionState==='requested'?'✎':'○'))?>
      </div>
      <div style="font-weight:bold;font-size:13px;color:<?=$revisionState==='accepted'?'#166534':($revisionState==='received'?'#1e40af':($revisionState==='requested'?'#c2410c':'#1e293b'))?>;">4. Revision</div>
      <div style="font-size:11px;color:<?=$revisionState==='accepted'?'#15803d':($revisionState==='received'?'#1d4ed8':($revisionState==='requested'?'#c2410c':'#64748b'))?>;margin-top:2px;"><?=e($revisionLabel)?></div>
    </div>

    <!-- Step 5: Editorial Decision -->
    <div style="background:<?=$decState==='accepted'?'#f0fdf4':($decState==='rejected'?'#fef2f2':'#f8fafc')?>;border:1px solid <?=$decState==='accepted'?'#bbf7d0':($decState==='rejected'?'#fecaca':'#cbd5e1')?>;border-radius:9px;padding:12px;text-align:center;">
      <div style="font-size:22px;color:<?=$decState==='accepted'?'#166534':($decState==='rejected'?'#991b1b':'#64748b')?>;margin-bottom:4px;">
        <?=$decState==='accepted'?'✓':($decState==='rejected'?'✕':'○')?>
      </div>
      <div style="font-weight:bold;font-size:13px;color:<?=$decState==='accepted'?'#166534':($decState==='rejected'?'#991b1b':'#1e293b')?>;">5. Decision</div>
      <div style="font-size:11px;color:<?=$decState==='accepted'?'#15803d':($decState==='rejected'?'#b91c1c':'#64748b')?>;margin-top:2px;"><?=e($decLabel)?></div>
    </div>

    <!-- Step 6: Gallery Proof -->
    <div style="background:<?=$proofState==='approved'?'#f0fdf4':($proofState==='sent'?'#fff7ed':($proofState==='corrections'?'#eff6ff':'#f8fafc'))?>;border:1px solid <?=$proofState==='approved'?'#bbf7d0':($proofState==='sent'?'#fed7aa':($proofState==='corrections'?'#bfdbfe':'#cbd5e1'))?>;border-radius:9px;padding:12px;text-align:center;">
      <div style="font-size:22px;color:<?=$proofState==='approved'?'#166534':($proofState==='sent'?'#c2410c':($proofState==='corrections'?'#1d4ed8':'#64748b'))?>;margin-bottom:4px;">
        <?=$proofState==='approved'?'✓':($proofState==='sent'?'●':($proofState==='corrections'?'✎':'○'))?>
      </div>
      <div style="font-weight:bold;font-size:13px;color:<?=$proofState==='approved'?'#166534':($proofState==='sent'?'#c2410c':($proofState==='corrections'?'#1e40af':'#1e293b'))?>;">6. Gallery Proof</div>
      <div style="font-size:11px;color:<?=$proofState==='approved'?'#15803d':($proofState==='sent'?'#c2410c':($proofState==='corrections'?'#1d4ed8':'#64748b'))?>;margin-top:2px;"><?=e($proofLabel)?></div>
    </div>

    <!-- Step 7: Author Response -->
    <div style="background:<?=$respState==='approved'?'#f0fdf4':($respState==='in_progress'?'#fff7ed':($respState==='corrections'?'#eff6ff':'#f8fafc'))?>;border:1px solid <?=$respState==='approved'?'#bbf7d0':($respState==='in_progress'?'#fed7aa':($respState==='corrections'?'#bfdbfe':'#cbd5e1'))?>;border-radius:9px;padding:12px;text-align:center;">
      <div style="font-size:22px;color:<?=$respState==='approved'?'#166534':($respState==='in_progress'?'#c2410c':($respState==='corrections'?'#1d4ed8':'#64748b'))?>;margin-bottom:4px;">
        <?=$respState==='approved'?'✓':($respState==='in_progress'?'●':($respState==='corrections'?'✎':'○'))?>
      </div>
      <div style="font-weight:bold;font-size:13px;color:<?=$respState==='approved'?'#166534':($respState==='in_progress'?'#c2410c':($respState==='corrections'?'#1e40af':'#1e293b'))?>;">7. Author Response</div>
      <div style="font-size:11px;color:<?=$respState==='approved'?'#15803d':($respState==='in_progress'?'#c2410c':($respState==='corrections'?'#1d4ed8':'#64748b'))?>;margin-top:2px;"><?=e($respLabel)?></div>
    </div>

    <!-- Step 8: Printing / Publishing -->
    <div style="background:<?=$pubStageState==='published'?'#f0fdf4':($pubStageState==='in_progress'?'#f3e8ff':'#f8fafc')?>;border:1px solid <?=$pubStageState==='published'?'#bbf7d0':($pubStageState==='in_progress'?'#d8b4fe':'#cbd5e1')?>;border-radius:9px;padding:12px;text-align:center;">
      <div style="font-size:22px;color:<?=$pubStageState==='published'?'#166534':($pubStageState==='in_progress'?'#7e22ce':'#64748b')?>;margin-bottom:4px;">
        <?=$pubStageState==='published'?'✓':($pubStageState==='in_progress'?'🖨️':'○')?>
      </div>
      <div style="font-weight:bold;font-size:13px;color:<?=$pubStageState==='published'?'#166534':($pubStageState==='in_progress'?'#6b21a8':'#1e293b')?>;">8. Printing / Publishing</div>
      <div style="font-size:11px;color:<?=$pubStageState==='published'?'#15803d':($pubStageState==='in_progress'?'#7e22ce':'#64748b')?>;margin-top:2px;"><?=e($pubStageLabel)?></div>
    </div>

    <!-- Step 9: Published -->
    <?php
      $pub9Bg     = $publishedState==='published' ? '#f0fdf4' : ($publishedState==='in_press' ? '#fffbeb' : '#f8fafc');
      $pub9Border = $publishedState==='published' ? '#bbf7d0' : ($publishedState==='in_press' ? '#fde68a' : '#cbd5e1');
      $pub9Icon   = $publishedState==='published' ? '✓'        : ($publishedState==='in_press' ? '⏳'       : '○');
      $pub9Color  = $publishedState==='published' ? '#166534'  : ($publishedState==='in_press' ? '#92400e'  : '#64748b');
      $pub9TColor = $publishedState==='published' ? '#166534'  : ($publishedState==='in_press' ? '#78350f'  : '#1e293b');
      $pub9SColor = $publishedState==='published' ? '#15803d'  : ($publishedState==='in_press' ? '#b45309'  : '#64748b');
    ?>
    <div style="background:<?=$pub9Bg?>;border:1px solid <?=$pub9Border?>;border-radius:9px;padding:12px;text-align:center;">
      <div style="font-size:22px;color:<?=$pub9Color?>;margin-bottom:4px;">
        <?=$pub9Icon?>
      </div>
      <div style="font-weight:bold;font-size:13px;color:<?=$pub9TColor?>;">9. Published</div>
      <div style="font-size:11px;color:<?=$pub9SColor?>;margin-top:2px;"><?=e($publishedLabel)?></div>
    </div>
  </div>
</div>

<!-- Detailed Workflow Updates & Author Communications -->
<div class="panel" style="margin-bottom:24px;">
  <h2 style="font-size:18px;margin:0 0 14px 0;padding-bottom:10px;border-bottom:1px solid #e2e8f0;">Official Editorial Status &amp; Communications</h2>

  <!-- Technical Check Remarks -->
  <?php if (!empty($techCheck['comments'])): ?>
    <div style="border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin-bottom:14px;background:#f8fafc;">
      <div style="font-weight:bold;color:#1e293b;font-size:14px;margin-bottom:6px;">📋 Technical Check Remarks</div>
      <div style="white-space:pre-wrap;font-size:13px;color:#334155;"><?=e($techCheck['comments'])?></div>
      <small style="color:#64748b;font-size:11px;display:block;margin-top:8px;">Evaluated on <?=date('d M Y', strtotime((string)$techCheck['checked_at']))?></small>
    </div>
  <?php endif; ?>

  <!-- Decision Letter -->
  <?php if (!empty($latestDecision['letter'])): ?>
    <div style="border:1px solid <?=$decState==='accepted'?'#bbf7d0':($decState==='rejected'?'#fecaca':'#fed7aa')?>;border-radius:8px;padding:14px;margin-bottom:14px;background:<?=$decState==='accepted'?'#f0fdf4':($decState==='rejected'?'#fef2f2':'#fff7ed')?>;">
      <div style="font-weight:bold;color:<?=$decState==='accepted'?'#166534':($decState==='rejected'?'#991b1b':'#9a3412')?>;font-size:14px;margin-bottom:6px;">
        ✉ Editorial Decision Letter — <?=e(slabel($latestDecision['decision']))?>
      </div>
      <div style="white-space:pre-wrap;font-size:13px;color:#334155;"><?=e($latestDecision['letter'])?></div>
      <small style="color:#64748b;font-size:11px;display:block;margin-top:8px;">Delivered on <?=date('d M Y', strtotime((string)$latestDecision['created_at']))?></small>
    </div>
  <?php endif; ?>

  <?php if (empty($techCheck['comments']) && empty($latestDecision['letter'])): ?>
    <div style="color:#64748b;font-size:13px;font-style:italic;">No additional editorial notes or decision letters recorded yet.</div>
  <?php endif; ?>
</div>

<!-- Manuscript Details & Submitted Files -->
<div class="panel">
  <h2 style="font-size:18px;margin:0 0 14px 0;padding-bottom:10px;border-bottom:1px solid #e2e8f0;">Submitted Manuscript Details &amp; Files</h2>

  <div style="display:grid;grid-template-columns:1fr;gap:14px;margin-bottom:18px;">
    <div>
      <strong style="display:block;font-size:12px;color:#64748b;margin-bottom:4px;">ABSTRACT</strong>
      <div style="font-size:13px;line-height:1.5;color:#334155;background:#f8fafc;padding:12px;border-radius:6px;border:1px solid #e2e8f0;">
        <?=e($m['abstract_text'] ?: 'No abstract text supplied.')?>
      </div>
    </div>
    <?php if (!empty($m['keywords'])): ?>
      <div>
        <strong style="display:block;font-size:12px;color:#64748b;margin-bottom:4px;">KEYWORDS</strong>
        <div style="font-size:13px;color:#334155;"><?=e($m['keywords'])?></div>
      </div>
    <?php endif; ?>
  </div>

  <h3 style="font-size:15px;margin:16px 0 10px 0;color:#1e293b;">Uploaded Manuscript Files</h3>
  <?php if (empty($files)): ?>
    <div style="font-size:13px;color:#64748b;font-style:italic;">No files uploaded for this manuscript.</div>
  <?php else: ?>
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
      <thead>
        <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
          <th style="padding:8px;text-align:left;">Version</th>
          <th style="padding:8px;text-align:left;">File Name</th>
          <th style="padding:8px;text-align:left;">File Type</th>
          <th style="padding:8px;text-align:left;">Uploaded Date</th>
          <th style="padding:8px;text-align:right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($files as $f):
          $rel = $f['relative_path'] ?? $f['file_path'] ?? '';
          $orig = $f['original_name'] ?? basename($rel);
          $fId = (int)($f['id'] ?? 0);
        ?>
          <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:8px;">v<?=e($f['version_no'] ?? 1)?></td>
            <td style="padding:8px;font-weight:bold;"><?=e($orig)?></td>
            <td style="padding:8px;"><?=e(ucwords(str_replace('_',' ',(string)($f['file_type'] ?? 'Manuscript File'))))?></td>
            <td style="padding:8px;"><?=date('d M Y', strtotime((string)($f['created_at'] ?? $m['submitted_at'] ?? $m['updated_at'])))?></td>
            <td style="padding:8px;text-align:right;">
              <?php if ($fId > 0): ?>
                <a class="btn secondary" style="padding:4px 10px;font-size:11px;" href="<?=BASE_URL?>download_manuscript_file.php?id=<?=$fId?>" target="_blank">Download ↓</a>
              <?php elseif ($rel): ?>
                <a class="btn secondary" style="padding:4px 10px;font-size:11px;" href="<?=BASE_URL?>download_manuscript_file.php?id=<?=(int)$id?>" target="_blank">Download ↓</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

