<?php

require_once __DIR__ . '/config/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$db = db();

/*
|--------------------------------------------------------------------------
| AJSMR Editorial Decision
|--------------------------------------------------------------------------
*/

/* Check login session */
if (empty($_SESSION['user']['id'])) {
    http_response_code(403);
    exit('Access denied. Please login again.');
}

$userId = (int) $_SESSION['user']['id'];

/* Get current user directly from database */
$stmt = $db->prepare("
    SELECT id, email, role, active
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(403);
    exit('Access denied. User account not found.');
}

if ((int)$user['active'] !== 1) {
    http_response_code(403);
    exit('Access denied. User account is inactive.');
}

/* Management roles */
$allowedRoles = [
    'admin',
    'editor_in_chief',
    'editor'
];

if (!in_array((string)$user['role'], $allowedRoles, true)) {
    http_response_code(403);

    echo '<h1>Access denied</h1>';
    echo '<p>Your current role is: <strong>' .
        htmlspecialchars(
            (string)$user['role'],
            ENT_QUOTES,
            'UTF-8'
        ) .
        '</strong></p>';

    exit;
}


/*
|--------------------------------------------------------------------------
| Manuscript ID
|--------------------------------------------------------------------------
*/

$manuscriptId = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($manuscriptId <= 0) {
    exit('Invalid manuscript ID.');
}


/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

function de_h($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| Get manuscript
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT *
    FROM manuscripts
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$manuscriptId]);

$manuscript = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$manuscript) {
    exit('Manuscript not found.');
}


/*
|--------------------------------------------------------------------------
| Get reviewer assignments and reviews
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        ra.id AS assignment_id,
        ra.status AS reviewer_status,
        ra.due_at,
        ra.completed_at,

        rp.full_name,
        rp.email,
        rp.affiliation,
        rp.expertise,

        pr.recommendation,
        pr.comments_to_editor,
        pr.comments_to_author,
        pr.confidential_comments,
        pr.submitted_at

    FROM ew_reviewer_assignments ra

    LEFT JOIN ew_reviewer_pool rp
        ON rp.id = ra.reviewer_id

    LEFT JOIN ew_peer_reviews pr
        ON pr.assignment_id = ra.id

    WHERE ra.manuscript_id = ?

    ORDER BY ra.id ASC
");

$stmt->execute([$manuscriptId]);

$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Existing editorial decisions
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        d.*,
        u.email AS decision_editor_email

    FROM ew_editorial_decisions d

    LEFT JOIN users u
        ON u.id = d.decided_by

    WHERE d.manuscript_id = ?

    ORDER BY d.id DESC
");

$stmt->execute([$manuscriptId]);

$decisions = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['decision_csrf'])) {
    $_SESSION['decision_csrf'] =
        bin2hex(random_bytes(32));
}

$csrf = $_SESSION['decision_csrf'];


/*
|--------------------------------------------------------------------------
| Process decision
|--------------------------------------------------------------------------
*/

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        empty($_POST['csrf']) ||
        empty($_SESSION['decision_csrf']) ||
        !hash_equals(
            $_SESSION['decision_csrf'],
            $_POST['csrf']
        )
    ) {

        $error = 'Invalid CSRF token. Please try again.';

    } else {

        $decision = trim(
            $_POST['decision'] ?? ''
        );

        $letter = trim(
            $_POST['letter'] ?? ''
        );

        $allowedDecisions = [
            'minor_revision',
            'major_revision',
            'accept',
            'reject'
        ];

        if (!in_array(
            $decision,
            $allowedDecisions,
            true
        )) {

            $error = 'Please select a valid editorial decision.';

        } else {

            try {

                $db->beginTransaction();


                /*
                 * Save editorial decision
                 */
                $stmt = $db->prepare("
                    INSERT INTO ew_editorial_decisions
                    (
                        manuscript_id,
                        decided_by,
                        decision,
                        letter
                    )
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->execute([
                    $manuscriptId,
                    $user['id'],
                    $decision,
                    $letter
                ]);


                /*
                 * Update manuscript status
                 */
                if ($decision === 'minor_revision') {

                    $newStatus = 'minor_revision';

                } elseif ($decision === 'major_revision') {

                    $newStatus = 'major_revision';

                } elseif ($decision === 'accept') {

                    $newStatus = 'accepted';

                } else {

                    $newStatus = 'rejected';

                }


                $stmt = $db->prepare("
                    UPDATE manuscripts
                    SET status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $newStatus,
                    $manuscriptId
                ]);


                /*
                 * Audit log
                 */
                $stmt = $db->prepare("
                    INSERT INTO ew_audit_log
                    (
                        manuscript_id,
                        user_id,
                        action,
                        details,
                        ip_address
                    )
                    VALUES (?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $manuscriptId,
                    $user['id'],
                    'editorial_decision',
                    'Decision: ' . $decision,
                    $_SERVER['REMOTE_ADDR'] ?? null
                ]);


                $db->commit();

                sendWorkflowNotification($db, 'EDITORIAL_DECISION', $manuscriptId, [
                    'decision' => $decision,
                    'letter'   => $letter
                ]);

                header(
                    'Location: decision.php?id=' .
                    $manuscriptId .
                    '&saved=1'
                );

                exit;

            } catch (Throwable $e) {

                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                $error = $e->getMessage();
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Refresh manuscript after possible decision
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT *
    FROM manuscripts
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$manuscriptId]);

$manuscript = $stmt->fetch(PDO::FETCH_ASSOC);

?>

require_once __DIR__ . '/includes/eic_layout.php';
eic_render_header('Editorial Decision — ' . $manuscript['manuscript_no'], 'Editorial Management &amp; Decision System');
?>
<div style="margin-bottom:14px;">
  <a class="btn light" href="manuscript_view.php?id=<?=$manuscriptId?>">← Back to Manuscript View</a>
  <a class="btn light" href="workflow_v1.php" style="margin-left:8px;">← Back to Workflow V1</a>
</div>

<style>
.review {
    border-top: 1px solid #ddd;
    padding-top: 18px;
    margin-top: 18px;
}
.decision-record {
    border-top: 1px solid #ddd;
    padding: 15px 0;
}
</style>


<div class="panel">

<h1>
Editorial Decision
</h1>

<p>
Logged in as:
<strong><?= de_h($user['email']) ?></strong>
</p>

<p>
Role:
<strong><?= de_h($user['role']) ?></strong>
</p>

</div>


<?php if (isset($_GET['saved'])): ?>

<div class="success">
Editorial decision saved successfully.
</div>

<?php endif; ?>


<?php if ($error): ?>

<div class="error">

<?= de_h($error) ?>

</div>

<?php endif; ?>


<div class="panel">

<h2>
<?= de_h($manuscript['manuscript_no']) ?>
</h2>

<p>
<strong>
<?= de_h($manuscript['title']) ?>
</strong>
</p>

<div class="info">

<strong>Status:</strong>

<span class="badge">
<?= de_h($manuscript['status']) ?>
</span>

</div>

</div>


<div class="panel">

<h2>
Reviewer Reports
</h2>


<?php if (!$reviews): ?>

<p>
No reviewer reports found.
</p>

<?php else: ?>


<?php foreach ($reviews as $review): ?>

<div class="review">

<h3>
Reviewer Report
</h3>

<div class="info">

<strong>Status:</strong>

<?= de_h($review['reviewer_status']) ?>

</div>


<?php if (!empty($review['recommendation'])): ?>

<div class="info">

<strong>
Recommendation:
</strong>

<span class="badge">

<?= de_h(
    ucwords(
        str_replace(
            '_',
            ' ',
            $review['recommendation']
        )
    )
) ?>

</span>

</div>

<?php endif; ?>


<?php if (!empty($review['comments_to_editor'])): ?>

<h4>
Comments to Editor
</h4>

<div>
<?= nl2br(
    de_h($review['comments_to_editor'])
) ?>
</div>

<?php endif; ?>


<?php if (!empty($review['comments_to_author'])): ?>

<h4>
Comments to Author
</h4>

<div>
<?= nl2br(
    de_h($review['comments_to_author'])
) ?>
</div>

<?php endif; ?>


<?php if (!empty($review['confidential_comments'])): ?>

<h4>
Confidential Comments
</h4>

<div>
<?= nl2br(
    de_h($review['confidential_comments'])
) ?>
</div>

<?php endif; ?>


<?php if (!empty($review['submitted_at'])): ?>

<p>
<strong>
Submitted:
</strong>

<?= de_h($review['submitted_at']) ?>

</p>

<?php endif; ?>

</div>

<?php endforeach; ?>

<?php endif; ?>

</div>


<div class="panel">

<h2>
Editorial Decision
</h2>

<form method="post">

<input
    type="hidden"
    name="csrf"
    value="<?= de_h($csrf) ?>"
>


<p>

<label>
<strong>
Decision
</strong>
</label>

</p>

<p>

<select name="decision" required>

<option value="">
-- Select Decision --
</option>

<option value="minor_revision">
Minor Revision
</option>

<option value="major_revision">
Major Revision
</option>

<option value="accept">
Accept
</option>

<option value="reject">
Reject
</option>

</select>

</p>


<p>

<label>
<strong>
Editorial Letter / Comments
</strong>
</label>

</p>

<p>

<textarea
    name="letter"
    placeholder="Enter the editorial decision letter or comments for the author..."
></textarea>

</p>


<p>

<button type="submit">
Save Editorial Decision
</button>

</p>

</form>

</div>


<?php if ($decisions): ?>

<div class="panel">

<h2>
Previous Editorial Decisions
</h2>


<?php foreach ($decisions as $d): ?>

<div class="decision-record">

<p>

<strong>
Decision:
</strong>

<?= de_h(
    ucwords(
        str_replace(
            '_',
            ' ',
            $d['decision']
        )
    )
) ?>

</p>

<p>

<strong>
Decided by:
</strong>

<?= de_h(
    $d['decision_editor_email']
    ?? ''
) ?>

</p>

<p>

<strong>
Date:
</strong>

<?= de_h(
    $d['created_at']
) ?>

</p>

<?php if (!empty($d['letter'])): ?>

<p>

<strong>
Editorial Letter:
</strong>

</p>

<div>

<?= nl2br(
    de_h($d['letter'])
) ?>

</div>

<?php endif; ?>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>


<p>

<a href="workflow_v1.php">
← Back to Workflow
</a>

</p>


<?php
eic_render_footer();