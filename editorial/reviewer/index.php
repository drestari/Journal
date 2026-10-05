<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../workflow_v1_common.php';

$u = login_required();
$role = (string)($u['role'] ?? '');

if ($role !== 'reviewer' && !in_array($role, ['admin', 'editor_in_chief'], true)) {
    http_response_code(403);
    exit('Access denied. Reviewer credentials required.');
}

$pdo = db();
$uid = (int)$u['id'];
$userEmail = strtolower(trim((string)($u['email'] ?? '')));

// Ensure reviewer pool entry has user_id linked
$linkStmt = $pdo->prepare("UPDATE ew_reviewer_pool SET user_id = ? WHERE (user_id IS NULL OR user_id = 0) AND LOWER(email) = LOWER(?)");
$linkStmt->execute([$uid, $userEmail]);

// Fetch reviewer profile
$stmtProf = $pdo->prepare("SELECT * FROM ew_reviewer_pool WHERE user_id = ? OR LOWER(email) = LOWER(?) LIMIT 1");
$stmtProf->execute([$uid, $userEmail]);
$reviewerProfile = $stmtProf->fetch(PDO::FETCH_ASSOC);

$reviewerName = (string)($reviewerProfile['full_name'] ?? $u['full_name'] ?? 'Reviewer');
$reviewerAffiliation = (string)($reviewerProfile['affiliation'] ?? '');
$reviewerExpertise = (string)($reviewerProfile['expertise'] ?? '');
$reviewerOrcid = (string)($reviewerProfile['orcid'] ?? '');

// -------------------------------------------------------------------------
// POST Actions: Accept, Decline, Update Profile
// -------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));

    // CSRF Check
    $postedCsrf = (string)($_POST['csrf'] ?? '');
    if (empty($_SESSION['csrf']) || !hash_equals((string)$_SESSION['csrf'], $postedCsrf)) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Security token invalid or expired. Please refresh the page.']);
        exit;
    }

    if ($action === 'accept_invitation') {
        header('Content-Type: application/json; charset=utf-8');
        $aid = (int)($_POST['assignment_id'] ?? 0);

        // IDOR Verification
        $stmtChk = $pdo->prepare("
            SELECT ra.*, m.manuscript_no, m.title
            FROM ew_reviewer_assignments ra
            JOIN ew_reviewer_pool rp ON rp.id = ra.reviewer_id
            JOIN manuscripts m ON m.id = ra.manuscript_id
            WHERE ra.id = ? AND (rp.user_id = ? OR LOWER(rp.email) = LOWER(?))
            LIMIT 1
        ");
        $stmtChk->execute([$aid, $uid, $userEmail]);
        $assignment = $stmtChk->fetch(PDO::FETCH_ASSOC);

        if (!$assignment) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Assignment not found or access denied.']);
            exit;
        }

        if ($assignment['status'] !== 'invited') {
            echo json_encode(['success' => false, 'error' => 'This invitation has already been ' . $assignment['status'] . '.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $pdo->prepare("UPDATE ew_reviewer_assignments SET status = 'accepted', responded_at = NOW() WHERE id = ?")->execute([$aid]);
            $pdo->prepare("UPDATE manuscripts SET status = 'UNDER_REVIEW', updated_at = NOW() WHERE id = ?")->execute([$assignment['manuscript_id']]);

            audit('reviewer_accepted', (int)$assignment['manuscript_id'], "Reviewer {$reviewerName} accepted invitation for assignment $aid.");
            $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, 'reviewer_accepted', ?, ?)")
                ->execute([$assignment['manuscript_id'], $uid, "Reviewer accepted review invitation for {$assignment['manuscript_no']}", $_SERVER['REMOTE_ADDR'] ?? null]);

            $pdo->commit();

            sendWorkflowNotification($pdo, 'REVIEWER_ASSIGNED', (int)$assignment['manuscript_id'], ['reviewer_id' => $assignment['reviewer_id']]);

            echo json_encode([
                'success' => true,
                'message' => 'Review invitation accepted. You may now start your review.',
                'redirect' => 'review_manuscript.php?assignment_id=' . $aid
            ]);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("Error accepting invitation: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error accepting invitation: ' . $e->getMessage()]);
            exit;
        }
    }

    if ($action === 'decline_invitation') {
        header('Content-Type: application/json; charset=utf-8');
        $aid = (int)($_POST['assignment_id'] ?? 0);
        $reason = trim((string)($_POST['decline_reason'] ?? ''));
        $comments = trim((string)($_POST['decline_comments'] ?? ''));

        if ($reason === '') {
            echo json_encode(['success' => false, 'error' => 'Please select a reason for declining this review invitation.']);
            exit;
        }

        // IDOR Verification
        $stmtChk = $pdo->prepare("
            SELECT ra.*, m.manuscript_no, m.title
            FROM ew_reviewer_assignments ra
            JOIN ew_reviewer_pool rp ON rp.id = ra.reviewer_id
            JOIN manuscripts m ON m.id = ra.manuscript_id
            WHERE ra.id = ? AND (rp.user_id = ? OR LOWER(rp.email) = LOWER(?))
            LIMIT 1
        ");
        $stmtChk->execute([$aid, $uid, $userEmail]);
        $assignment = $stmtChk->fetch(PDO::FETCH_ASSOC);

        if (!$assignment) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Assignment not found or access denied.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $pdo->prepare("UPDATE ew_reviewer_assignments SET status = 'declined', responded_at = NOW() WHERE id = ?")->execute([$aid]);

            $logMsg = "Reviewer declined invitation. Reason: " . $reason . ($comments ? " | Comments: " . $comments : "");
            audit('reviewer_declined', (int)$assignment['manuscript_id'], $logMsg);
            $pdo->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, 'reviewer_declined', ?, ?)")
                ->execute([$assignment['manuscript_id'], $uid, $logMsg, $_SERVER['REMOTE_ADDR'] ?? null]);

            $pdo->commit();

            sendWorkflowNotification($pdo, 'REVIEWER_DECLINED', (int)$assignment['manuscript_id'], [
                'reason'        => $reason,
                'comments'      => $comments,
                'reviewer_name' => $reviewerName
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Review invitation declined. The Editor-in-Chief has been notified.'
            ]);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error declining invitation. Please try again.']);
            exit;
        }
    }

    if ($action === 'update_profile') {
        header('Content-Type: application/json; charset=utf-8');
        $name = trim((string)($_POST['full_name'] ?? ''));
        $affiliation = trim((string)($_POST['affiliation'] ?? ''));
        $expertise = trim((string)($_POST['expertise'] ?? ''));
        $orcid = trim((string)($_POST['orcid'] ?? ''));
        $newPw = (string)($_POST['new_password'] ?? '');

        if ($name === '') {
            echo json_encode(['success' => false, 'error' => 'Full name is required.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Update user table
            if ($newPw !== '') {
                if (strlen($newPw) < 6) {
                    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters long.']);
                    exit;
                }
                $pwHash = password_hash($newPw, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET full_name = ?, affiliation = ?, orcid = ?, password_hash = ? WHERE id = ?")->execute([$name, $affiliation, $orcid, $pwHash, $uid]);
            } else {
                $pdo->prepare("UPDATE users SET full_name = ?, affiliation = ?, orcid = ? WHERE id = ?")->execute([$name, $affiliation, $orcid, $uid]);
            }

            // Update reviewer pool
            $pdo->prepare("
                UPDATE ew_reviewer_pool
                SET full_name = ?, affiliation = ?, expertise = ?, orcid = ?
                WHERE user_id = ? OR LOWER(email) = LOWER(?)
            ")->execute([$name, $affiliation, $expertise, $orcid, $uid, $userEmail]);

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Reviewer profile updated successfully.']);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to update profile. Please try again.']);
            exit;
        }
    }
}

// -------------------------------------------------------------------------
// Load Reviewer Assignments Data
// -------------------------------------------------------------------------
$stmtAssignments = $pdo->prepare("
    SELECT
        ra.id AS assignment_id,
        ra.status AS assignment_status,
        ra.due_at,
        ra.invited_at,
        ra.responded_at,
        ra.completed_at,
        m.id AS manuscript_id,
        m.manuscript_no,
        m.title,
        m.article_type,
        m.abstract AS abstract_text,
        m.version_no,
        pr.id AS review_id,
        pr.recommendation,
        pr.submitted_at AS review_submitted_at
    FROM ew_reviewer_assignments ra
    JOIN ew_reviewer_pool rp ON rp.id = ra.reviewer_id
    JOIN manuscripts m ON m.id = ra.manuscript_id
    LEFT JOIN ew_peer_reviews pr ON pr.assignment_id = ra.id
    WHERE (rp.user_id = ? OR LOWER(rp.email) = LOWER(?))
      AND ra.status <> 'cancelled'
    ORDER BY ra.invited_at DESC
");
$stmtAssignments->execute([$uid, $userEmail]);
$assignments = $stmtAssignments->fetchAll(PDO::FETCH_ASSOC);

// Calculate Dynamic Dashboard Counts
$countTotal = count($assignments);
$countPending = 0;    // Invited, not yet accepted/declined
$countInProgress = 0; // Accepted / In review, not yet submitted
$countCompleted = 0;  // Completed / Submitted
$countOverdue = 0;    // Due date passed and not completed
$countDeclined = 0;

$nowTime = time();
$historyList = [];

foreach ($assignments as $a) {
    $st = strtolower((string)$a['assignment_status']);
    $isSubmitted = !empty($a['review_submitted_at']) || $st === 'completed';
    $dueTimestamp = $a['due_at'] ? strtotime((string)$a['due_at']) : 0;
    $isOverdue = (!$isSubmitted && $dueTimestamp > 0 && $dueTimestamp < $nowTime && $st !== 'declined');

    if ($isSubmitted) {
        $countCompleted++;
        $historyList[] = $a;
    } elseif ($st === 'declined') {
        $countDeclined++;
    } elseif ($st === 'invited') {
        $countPending++;
    } else {
        $countInProgress++;
    }

    if ($isOverdue) {
        $countOverdue++;
    }
}

$pageTitle = 'Reviewer Workspace & Dashboard — AJSMR';
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
.top-inner{max-width:1250px;margin:auto;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px}
.brand{font-size:20px;font-weight:800}
.brand small{display:block;font-size:12px;font-weight:400;margin-top:3px;color:#dbeafe}
.nav-links a{color:#fff;text-decoration:none;border:1px solid #ffffff55;border-radius:6px;padding:7px 12px;font-size:13px;margin-left:8px;transition:background 0.2s}
.nav-links a:hover{background:rgba(255,255,255,0.15)}
.wrap{max-width:1250px;margin:26px auto;padding:0 18px}

/* Summary Cards Grid */
.stats-grid{display:grid;grid-template-columns:repeat(5, 1fr);gap:16px;margin-bottom:26px}
@media(max-width:1100px){.stats-grid{grid-template-columns:repeat(3, 1fr)}}
@media(max-width:700px){.stats-grid{grid-template-columns:1fr 1fr}}
@media(max-width:480px){.stats-grid{grid-template-columns:1fr}}

.stat-card{background:#fff;border:1px solid #e3e9f1;border-radius:10px;padding:18px 20px;box-shadow:0 4px 15px rgba(16,32,64,0.04);position:relative;overflow:hidden}
.stat-card:before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:#cbd5e1}
.stat-card.total:before{background:#0b5fa5}
.stat-card.pending:before{background:#f59e0b}
.stat-card.progress:before{background:#6366f1}
.stat-card.completed:before{background:#10b981}
.stat-card.overdue:before{background:#ef4444}
.stat-label{font-size:12px;color:#64748b;font-weight:bold;text-transform:uppercase;letter-spacing:0.5px}
.stat-num{font-size:28px;font-weight:800;color:#0f172a;margin-top:6px}

/* Panel & Table */
.panel{background:#fff;border:1px solid #e3e9f1;border-radius:10px;box-shadow:0 4px 15px rgba(16,32,64,0.05);padding:22px 24px;margin-bottom:24px}
.panel-header{display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #eef2f6;padding-bottom:14px;margin-bottom:18px;flex-wrap:wrap;gap:12px}
.panel-title{font-size:16px;font-weight:800;color:#0f172a;margin:0}

/* Filter Bar */
.filter-bar{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.filter-btn{background:#f1f5f9;border:1px solid #cbd5e1;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:bold;color:#475569;cursor:pointer;transition:all 0.15s}
.filter-btn:hover,.filter-btn.active{background:#0b5fa5;color:#fff;border-color:#0b5fa5}

table{width:100%;border-collapse:collapse}
th,td{padding:12px 14px;border:1px solid #e2e8f0;text-align:left;vertical-align:middle;font-size:13px}
th{background:#f8fafc;color:#475569;font-weight:bold;text-transform:uppercase;letter-spacing:0.4px;font-size:11px}
tr:hover{background:#fbfcfe}

/* Badges */
.badge{display:inline-block;padding:4px 9px;border-radius:12px;font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:0.4px}
.badge.invited{background:#fef3c7;color:#92400e}
.badge.accepted{background:#e0e7ff;color:#3730a3}
.badge.in_review{background:#e0f2fe;color:#0369a1}
.badge.completed{background:#dcfce7;color:#166534}
.badge.declined{background:#f1f5f9;color:#64748b}
.badge.overdue{background:#fee2e2;color:#991b1b}

/* Buttons */
.btn{display:inline-block;background:#0b5fa5;color:#fff;border:0;border-radius:6px;padding:8px 14px;text-decoration:none;font-weight:bold;font-size:12px;cursor:pointer;transition:all 0.15s;line-height:1.2;white-space:nowrap}
.btn:hover{background:#084b84}
.btn.light{background:#eaf2f9;color:#0b5fa5}
.btn.light:hover{background:#d7e8f7}
.btn.success{background:#16a34a;color:#fff}
.btn.success:hover{background:#15803d}
.btn.danger{background:#dc2626;color:#fff}
.btn.danger:hover{background:#b91c1c}
.btn.warning{background:#d97706;color:#fff}
.btn.warning:hover{background:#b45309}

/* Modals */
.editorial-modal{position:fixed;top:0;left:0;right:0;bottom:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px}
.editorial-modal-backdrop{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(2px)}
.editorial-modal-dialog{position:relative;background:#fff;border-radius:12px;box-shadow:0 20px 45px rgba(0,0,0,0.25);max-width:650px;width:100%;max-height:90vh;display:flex;flex-direction:column;z-index:1;overflow:hidden;animation:modalIn 0.2s cubic-bezier(0.16,1,0.3,1)}
@keyframes modalIn{from{opacity:0;transform:translateY(12px) scale(0.98)}to{opacity:1;transform:translateY(0) scale(1)}}
.editorial-modal-header{padding:18px 24px;border-bottom:1px solid #e2e8f0;display:flex;align-items:flex-start;justify-content:space-between;background:#f8fafc}
.modal-close-btn{background:none;border:none;font-size:26px;line-height:1;color:#64748b;cursor:pointer;padding:0}
.modal-close-btn:hover{color:#0f172a}
.editorial-modal-body{padding:22px 24px;overflow-y:auto;font-size:14px;line-height:1.5}
.editorial-modal-footer{padding:16px 24px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px;background:#f8fafc}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-weight:600;font-size:13px;color:#334155;margin-bottom:6px}
.form-control{width:100%;border:1px solid #cbd5e1;border-radius:6px;padding:9px 12px;font-size:14px;background:#fff;font-family:inherit;box-sizing:border-box}
.form-control:focus{outline:none;border-color:#0b5fa5;box-shadow:0 0 0 3px rgba(11,95,165,0.15)}

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
      <small>Reviewer Portal &bull; Peer Review Workspace</small>
    </div>
    <div class="nav-links">
      <span style="font-size:13px;color:#dbeafe;margin-right:8px;">Welcome, <strong><?=e($reviewerName)?></strong></span>
      <a href="index.php">Dashboard</a>
      <a href="#historySection">Review History</a>
      <a href="javascript:void(0)" onclick="openProfileModal()">My Profile</a>
      <a href="../logout.php">Sign Out</a>
    </div>
  </div>
</header>

<main class="wrap">

  <div style="margin-bottom:22px;">
    <h1 style="margin:0 0 6px 0;font-size:24px;color:#0f172a;">Reviewer Workspace</h1>
    <div style="color:#64748b;font-size:14px;">Evaluate assigned manuscripts, accept or decline invitations, and submit confidential peer reviews.</div>
  </div>

  <!-- Summary Statistics Cards -->
  <div class="stats-grid">
    <div class="stat-card total">
      <div class="stat-label">Assigned Reviews</div>
      <div class="stat-num"><?=$countTotal?></div>
    </div>
    <div class="stat-card pending">
      <div class="stat-label">Pending Invitations</div>
      <div class="stat-num"><?=$countPending?></div>
    </div>
    <div class="stat-card progress">
      <div class="stat-label">Reviews In Progress</div>
      <div class="stat-num"><?=$countInProgress?></div>
    </div>
    <div class="stat-card completed">
      <div class="stat-label">Completed Reviews</div>
      <div class="stat-num"><?=$countCompleted?></div>
    </div>
    <div class="stat-card overdue">
      <div class="stat-label">Overdue Reviews</div>
      <div class="stat-num"><?=$countOverdue?></div>
    </div>
  </div>

  <!-- Assigned Manuscripts Panel -->
  <div class="panel">
    <div class="panel-header">
      <div>
        <h2 class="panel-title">Assigned Manuscripts &amp; Review Invitations</h2>
        <span style="font-size:12px;color:#64748b;">Double-blind peer review enabled. Author identities remain confidential.</span>
      </div>

      <div class="filter-bar">
        <button type="button" class="filter-btn active" onclick="filterReviews('all', this)">All (<?=$countTotal?>)</button>
        <button type="button" class="filter-btn" onclick="filterReviews('invited', this)">Pending (<?=$countPending?>)</button>
        <button type="button" class="filter-btn" onclick="filterReviews('in_progress', this)">In Progress (<?=$countInProgress?>)</button>
        <button type="button" class="filter-btn" onclick="filterReviews('completed', this)">Completed (<?=$countCompleted?>)</button>
        <?php if ($countOverdue > 0): ?>
          <button type="button" class="filter-btn" style="color:#dc2626;" onclick="filterReviews('overdue', this)">Overdue (<?=$countOverdue?>)</button>
        <?php endif; ?>
      </div>
    </div>

    <?php if (empty($assignments)): ?>
      <div style="text-align:center;padding:45px 20px;color:#64748b;">
        <p style="font-size:16px;margin-bottom:6px;">No manuscripts currently assigned to your reviewer account.</p>
        <span style="font-size:13px;">When the Editor-in-Chief invites you to review a paper, it will appear here automatically.</span>
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th style="width:140px;">Paper ID</th>
            <th>Manuscript Title</th>
            <th style="width:130px;">Article Type</th>
            <th style="width:110px;">Assigned</th>
            <th style="width:150px;">Due Date &amp; Deadline</th>
            <th style="width:110px;">Status</th>
            <th style="width:170px;text-align:center;">Action</th>
          </tr>
        </thead>
        <tbody id="assignmentsTableBody">
          <?php foreach ($assignments as $a):
            $rawStatus = strtolower((string)$a['assignment_status']);
            $isSubmitted = !empty($a['review_submitted_at']) || $rawStatus === 'completed';
            $dueTimestamp = $a['due_at'] ? strtotime((string)$a['due_at']) : 0;
            $isOverdue = (!$isSubmitted && $dueTimestamp > 0 && $dueTimestamp < $nowTime && $rawStatus !== 'declined');

            // Category tag for client filter
            $catTag = 'invited';
            if ($isSubmitted) $catTag = 'completed';
            elseif ($rawStatus === 'declined') $catTag = 'declined';
            elseif ($rawStatus === 'accepted' || $rawStatus === 'in_review') $catTag = 'in_progress';
            if ($isOverdue) $catTag .= ' overdue';

            // Deadline calculation
            $deadlineHtml = 'Not specified';
            if ($dueTimestamp > 0) {
                $daysDiff = (int)ceil(($dueTimestamp - $nowTime) / 86400);
                if ($isSubmitted) {
                    $deadlineHtml = date('d M Y', $dueTimestamp) . '<br><small style="color:#166534;">Submitted</small>';
                } elseif ($daysDiff < 0) {
                    $deadlineHtml = date('d M Y', $dueTimestamp) . '<br><strong style="color:#dc2626;font-size:11px;">Overdue by ' . abs($daysDiff) . ' day(s)</strong>';
                } elseif ($daysDiff === 0) {
                    $deadlineHtml = date('d M Y', $dueTimestamp) . '<br><strong style="color:#d97706;font-size:11px;">Due Today</strong>';
                } else {
                    $deadlineHtml = date('d M Y', $dueTimestamp) . '<br><small style="color:#0b5fa5;font-size:11px;">' . $daysDiff . ' days remaining</small>';
                }
            }
          ?>
            <tr class="review-row" data-cat="<?=e($catTag)?>">
              <td><strong><?=e($a['manuscript_no'])?></strong></td>
              <td style="font-weight:500;">
                <div><?=e($a['title'])?></div>
                <div style="font-size:11px;color:#64748b;margin-top:2px;">Version: <?=e($a['version_no'])?></div>
              </td>
              <td><?=e($a['article_type'] ?: 'Article')?></td>
              <td><?=date('d M Y', strtotime((string)$a['invited_at']))?></td>
              <td><?=$deadlineHtml?></td>
              <td>
                <?php if ($isSubmitted): ?>
                  <span class="badge completed">Completed</span>
                <?php elseif ($isOverdue): ?>
                  <span class="badge overdue">Overdue</span>
                <?php elseif ($rawStatus === 'invited'): ?>
                  <span class="badge invited">Invited</span>
                <?php elseif ($rawStatus === 'accepted'): ?>
                  <span class="badge accepted">Accepted</span>
                <?php elseif ($rawStatus === 'declined'): ?>
                  <span class="badge declined">Declined</span>
                <?php else: ?>
                  <span class="badge in_review"><?=e(slabel($rawStatus))?></span>
                <?php endif; ?>
              </td>
              <td style="text-align:center;">
                <?php if ($rawStatus === 'invited'): ?>
                  <button type="button" class="btn success" style="padding:5px 9px;margin-bottom:3px;"
                          onclick="openAcceptModal(<?=(int)$a['assignment_id']?>, '<?=e($a['manuscript_no'])?>', '<?=e(addslashes($a['title']))?>', '<?=e($a['due_at'])?>')">
                    ✓ Accept
                  </button>
                  <button type="button" class="btn danger" style="padding:5px 9px;"
                          onclick="openDeclineModal(<?=(int)$a['assignment_id']?>, '<?=e($a['manuscript_no'])?>', '<?=e(addslashes($a['title']))?>')">
                    ✕ Decline
                  </button>
                <?php elseif ($isSubmitted): ?>
                  <a class="btn light" style="padding:5px 11px;" href="review_manuscript.php?assignment_id=<?=(int)$a['assignment_id']?>">
                    View Report ↗
                  </a>
                <?php elseif ($rawStatus === 'declined'): ?>
                  <span style="color:#94a3b8;font-size:12px;">Declined</span>
                <?php else: ?>
                  <a class="btn" style="padding:5px 12px;background:#0b5fa5;" href="review_manuscript.php?assignment_id=<?=(int)$a['assignment_id']?>">
                    <?=$rawStatus === 'accepted' ? 'Start Review &rarr;' : 'Continue Review &rarr;'?>
                  </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- Review History Panel -->
  <div class="panel" id="historySection">
    <div class="panel-header">
      <h2 class="panel-title">Completed Review History (<?=count($historyList)?>)</h2>
      <span style="font-size:12px;color:#64748b;">Record of your submitted peer evaluations</span>
    </div>

    <?php if (empty($historyList)): ?>
      <p style="color:#64748b;font-size:13px;margin:0;">No completed reviews yet.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Paper ID</th>
            <th>Article Type</th>
            <th>Manuscript Title</th>
            <th>Submitted On</th>
            <th>Your Recommendation</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($historyList as $h): ?>
            <tr>
              <td><strong><?=e($h['manuscript_no'])?></strong></td>
              <td><?=e($h['article_type'] ?: 'Article')?></td>
              <td><?=e($h['title'])?></td>
              <td><?=date('d M Y, H:i', strtotime((string)$h['review_submitted_at']))?></td>
              <td><strong style="color:#0b5fa5;"><?=e(slabel($h['recommendation']))?></strong></td>
              <td>
                <a class="btn light" style="padding:4px 10px;font-size:11px;" href="review_manuscript.php?assignment_id=<?=(int)$h['assignment_id']?>">
                  View Review ↗
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</main>

<!-- ========================================================================= -->
<!-- MODAL: Accept Review Invitation                                           -->
<!-- ========================================================================= -->
<div id="acceptModal" class="editorial-modal" style="display:none;" role="dialog" aria-modal="true">
  <div class="editorial-modal-backdrop" onclick="closeModals()"></div>
  <div class="editorial-modal-dialog">
    <div class="editorial-modal-header">
      <div>
        <h3 style="margin:0;font-size:17px;color:#0f172a;">Accept Review Invitation</h3>
        <div style="font-size:12px;color:#64748b;margin-top:2px;" id="acceptModalPaperNo"></div>
      </div>
      <button type="button" class="modal-close-btn" onclick="closeModals()">&times;</button>
    </div>

    <form onsubmit="submitAcceptReview(event)">
      <input type="hidden" name="assignment_id" id="acceptAssignmentId" value="">
      <input type="hidden" name="action" value="accept_invitation">
      <input type="hidden" name="csrf" value="<?=e(csrf())?>">

      <div class="editorial-modal-body">
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px;color:#166534;margin-bottom:14px;">
          <h4 style="margin:0 0 6px 0;font-size:14px;">Are you willing to review this manuscript?</h4>
          <div>Title: <strong id="acceptModalTitle"></strong></div>
          <div style="margin-top:4px;">Review Due Date: <strong id="acceptModalDueDate"></strong></div>
        </div>

        <p style="font-size:13px;color:#475569;margin:0 0 10px 0;">
          By accepting this invitation, you agree to evaluate this manuscript thoroughly and confidentially in accordance with AJSMR ethical guidelines and deliver your report by the scheduled deadline.
        </p>
      </div>

      <div class="editorial-modal-footer">
        <button type="button" class="btn light" onclick="closeModals()">Cancel</button>
        <button type="submit" class="btn success" id="acceptConfirmBtn">✓ Confirm &amp; Accept Review</button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: Decline Review Invitation                                          -->
<!-- ========================================================================= -->
<div id="declineModal" class="editorial-modal" style="display:none;" role="dialog" aria-modal="true">
  <div class="editorial-modal-backdrop" onclick="closeModals()"></div>
  <div class="editorial-modal-dialog">
    <div class="editorial-modal-header">
      <div>
        <h3 style="margin:0;font-size:17px;color:#0f172a;">Decline Review Invitation</h3>
        <div style="font-size:12px;color:#64748b;margin-top:2px;" id="declineModalPaperNo"></div>
      </div>
      <button type="button" class="modal-close-btn" onclick="closeModals()">&times;</button>
    </div>

    <form onsubmit="submitDeclineReview(event)">
      <input type="hidden" name="assignment_id" id="declineAssignmentId" value="">
      <input type="hidden" name="action" value="decline_invitation">
      <input type="hidden" name="csrf" value="<?=e(csrf())?>">

      <div class="editorial-modal-body">
        <div class="form-group">
          <label for="declineReason">Reason for Declining <span style="color:#dc2626;">*</span></label>
          <select name="decline_reason" id="declineReason" class="form-control" required>
            <option value="">-- Please select a reason --</option>
            <option value="conflict_of_interest">Conflict of Interest</option>
            <option value="outside_expertise">Outside My Area of Expertise</option>
            <option value="schedule_constraints">No Availability / High Workload</option>
            <option value="deadline_unfeasible">Unable to Complete Within Deadline</option>
            <option value="other">Other Reason</option>
          </select>
        </div>

        <div class="form-group">
          <label for="declineComments">Additional Comments / Suggested Alternate Reviewers (Optional)</label>
          <textarea name="decline_comments" id="declineComments" class="form-control" rows="3" placeholder="Provide any additional comments or suggest colleagues with expertise in this topic..."></textarea>
          <div class="form-hint" style="font-size:12px;color:#64748b;margin-top:4px;">This notification will be transmitted to the Editor-in-Chief.</div>
        </div>
      </div>

      <div class="editorial-modal-footer">
        <button type="button" class="btn light" onclick="closeModals()">Cancel</button>
        <button type="submit" class="btn danger" id="declineConfirmBtn">✕ Confirm Decline</button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: Reviewer Profile & Settings                                        -->
<!-- ========================================================================= -->
<div id="profileModal" class="editorial-modal" style="display:none;" role="dialog" aria-modal="true">
  <div class="editorial-modal-backdrop" onclick="closeModals()"></div>
  <div class="editorial-modal-dialog">
    <div class="editorial-modal-header">
      <h3 style="margin:0;font-size:17px;color:#0f172a;">Reviewer Profile &amp; Expertise</h3>
      <button type="button" class="modal-close-btn" onclick="closeModals()">&times;</button>
    </div>

    <form onsubmit="submitProfileUpdate(event)">
      <input type="hidden" name="action" value="update_profile">
      <input type="hidden" name="csrf" value="<?=e(csrf())?>">

      <div class="editorial-modal-body">
        <div class="form-group">
          <label for="profName">Full Name <span style="color:#dc2626;">*</span></label>
          <input type="text" name="full_name" id="profName" class="form-control" value="<?=e($reviewerName)?>" required>
        </div>

        <div class="form-group">
          <label>Email Address</label>
          <input type="email" class="form-control" value="<?=e($userEmail)?>" readonly style="background:#f1f5f9;cursor:not-allowed;">
        </div>

        <div class="form-group">
          <label for="profAffiliation">Affiliation / Institution</label>
          <input type="text" name="affiliation" id="profAffiliation" class="form-control" value="<?=e($reviewerAffiliation)?>">
        </div>

        <div class="form-group">
          <label for="profOrcid">ORCID iD</label>
          <input type="text" name="orcid" id="profOrcid" class="form-control" placeholder="0000-0000-0000-0000" value="<?=e($reviewerOrcid)?>">
        </div>

        <div class="form-group">
          <label for="profExpertise">Research Areas &amp; Keywords of Expertise</label>
          <textarea name="expertise" id="profExpertise" class="form-control" rows="3" placeholder="e.g. Molecular Biology, Oncology, Clinical Trials, Virology..."><?=e($reviewerExpertise)?></textarea>
        </div>

        <div class="form-group">
          <label for="profNewPw">Change Password (leave blank to keep current)</label>
          <input type="password" name="new_password" id="profNewPw" class="form-control" placeholder="Enter new password if changing">
        </div>
      </div>

      <div class="editorial-modal-footer">
        <button type="button" class="btn light" onclick="closeModals()">Cancel</button>
        <button type="submit" class="btn" id="profileSaveBtn">Save Profile</button>
      </div>
    </form>
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

function closeModals() {
  document.querySelectorAll('.editorial-modal').forEach(m => m.style.display = 'none');
  document.body.style.overflow = '';
}

function openAcceptModal(aid, paperNo, title, dueDate) {
  document.getElementById('acceptAssignmentId').value = aid;
  document.getElementById('acceptModalPaperNo').textContent = 'Paper ID: ' + paperNo;
  document.getElementById('acceptModalTitle').textContent = title;
  document.getElementById('acceptModalDueDate').textContent = dueDate ? dueDate : '14 days from assignment';
  document.getElementById('acceptModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function openDeclineModal(aid, paperNo, title) {
  document.getElementById('declineAssignmentId').value = aid;
  document.getElementById('declineModalPaperNo').textContent = 'Paper ID: ' + paperNo + ' — ' + title;
  document.getElementById('declineReason').selectedIndex = 0;
  document.getElementById('declineComments').value = '';
  document.getElementById('declineModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function openProfileModal() {
  document.getElementById('profileModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function filterReviews(cat, btn) {
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  const rows = document.querySelectorAll('.review-row');
  rows.forEach(r => {
    const rowCats = (r.getAttribute('data-cat') || '').split(' ');
    if (cat === 'all') {
      r.style.display = '';
    } else if (cat === 'overdue') {
      r.style.display = rowCats.includes('overdue') ? '' : 'none';
    } else {
      r.style.display = rowCats.includes(cat) ? '' : 'none';
    }
  });
}

async function submitAcceptReview(e) {
  e.preventDefault();
  const form = e.target;
  const btn = document.getElementById('acceptConfirmBtn');
  btn.disabled = true;
  btn.textContent = 'Processing...';

  try {
    const res = await fetch(window.location.href, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    if (!data.success) {
      alert(data.error || 'Failed to accept invitation.');
      btn.disabled = false;
      btn.textContent = '✓ Confirm & Accept Review';
      return;
    }
    closeModals();
    showToast(data.message, 'success');
    if (data.redirect) {
      setTimeout(() => { window.location.href = data.redirect; }, 1000);
    } else {
      setTimeout(() => { window.location.reload(); }, 1200);
    }
  } catch(err) {
    alert('Network error. Please try again.');
    btn.disabled = false;
    btn.textContent = '✓ Confirm & Accept Review';
  }
}

async function submitDeclineReview(e) {
  e.preventDefault();
  const form = e.target;
  const btn = document.getElementById('declineConfirmBtn');
  btn.disabled = true;
  btn.textContent = 'Processing...';

  try {
    const res = await fetch(window.location.href, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    if (!data.success) {
      alert(data.error || 'Failed to decline invitation.');
      btn.disabled = false;
      btn.textContent = '✕ Confirm Decline';
      return;
    }
    closeModals();
    showToast(data.message, 'success');
    setTimeout(() => { window.location.reload(); }, 1200);
  } catch(err) {
    alert('Network error. Please try again.');
    btn.disabled = false;
    btn.textContent = '✕ Confirm Decline';
  }
}

async function submitProfileUpdate(e) {
  e.preventDefault();
  const form = e.target;
  const btn = document.getElementById('profileSaveBtn');
  btn.disabled = true;
  btn.textContent = 'Saving...';

  try {
    const res = await fetch(window.location.href, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    if (!data.success) {
      alert(data.error || 'Failed to update profile.');
      btn.disabled = false;
      btn.textContent = 'Save Profile';
      return;
    }
    closeModals();
    showToast(data.message, 'success');
    setTimeout(() => { window.location.reload(); }, 1200);
  } catch(err) {
    alert('Network error. Please try again.');
    btn.disabled = false;
    btn.textContent = 'Save Profile';
  }
}
</script>

<footer style="margin-top:40px;padding:24px 0;border-top:1px solid #e2e8f0;text-align:center;font-size:12px;color:#64748b;">
  &copy; <?=date('Y')?> American Journal of Science and Medical Research (AJSMR). All rights reserved. &bull; Reviewer Portal
</footer>

</body>
</html>
