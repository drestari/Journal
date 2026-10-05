<?php

require_once __DIR__ . '/config/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$db = db();

/*
|--------------------------------------------------------------------------
| AJSMR Acceptance Module
|--------------------------------------------------------------------------
*/

/* Check login */
if (empty($_SESSION['user']['id'])) {
    http_response_code(403);
    exit('Access denied. Please login again.');
}

$userId = (int)$_SESSION['user']['id'];

/* Verify user directly */
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

/* Only editorial management roles */
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

function ac_h($value)
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
| Get latest editorial decision
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

    LIMIT 1
");

$stmt->execute([$manuscriptId]);

$decision = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Get reviewer reports
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        ra.id AS assignment_id,
        ra.status,
        ra.completed_at,

        pr.recommendation,
        pr.submitted_at

    FROM ew_reviewer_assignments ra

    LEFT JOIN ew_peer_reviews pr
        ON pr.assignment_id = ra.id

    WHERE ra.manuscript_id = ?

    ORDER BY ra.id ASC
");

$stmt->execute([$manuscriptId]);

$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['accept_csrf'])) {
    $_SESSION['accept_csrf'] =
        bin2hex(random_bytes(32));
}

$csrf = $_SESSION['accept_csrf'];


/*
|--------------------------------------------------------------------------
| Process Acceptance
|--------------------------------------------------------------------------
*/

$error = '';
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        empty($_POST['csrf']) ||
        empty($_SESSION['accept_csrf']) ||
        !hash_equals(
            $_SESSION['accept_csrf'],
            $_POST['csrf']
        )
    ) {

        $error = 'Invalid CSRF token. Please try again.';

    } else {

        try {

            /*
             * Re-read the latest editorial decision.
             */
            $stmt = $db->prepare("
                SELECT *
                FROM ew_editorial_decisions
                WHERE manuscript_id = ?
                ORDER BY id DESC
                LIMIT 1
            ");

            $stmt->execute([$manuscriptId]);

            $latestDecision =
                $stmt->fetch(PDO::FETCH_ASSOC);


            /*
             * Acceptance is allowed only when
             * an editorial Accept decision exists.
             */
            if (
                !$latestDecision ||
                $latestDecision['decision'] !== 'accept'
            ) {

                throw new RuntimeException(
                    'The manuscript does not have an editorial Accept decision.'
                );
            }


            $db->beginTransaction();


            /*
             * Update manuscript status.
             */
            $stmt = $db->prepare("
                UPDATE manuscripts
                SET status = 'accepted'
                WHERE id = ?
            ");

            $stmt->execute([
                $manuscriptId
            ]);


            /*
             * Create audit record.
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
                'manuscript_accepted',
                'Manuscript formally accepted for publication.',
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);


            $db->commit();

            sendWorkflowNotification($db, 'MANUSCRIPT_ACCEPTED', $manuscriptId);

            $saved = true;


            /*
             * Refresh manuscript.
             */
            $stmt = $db->prepare("
                SELECT *
                FROM manuscripts
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([
                $manuscriptId
            ]);

            $manuscript =
                $stmt->fetch(PDO::FETCH_ASSOC);


        } catch (Throwable $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $error = $e->getMessage();
        }
    }
}

?>

require_once __DIR__ . '/includes/eic_layout.php';
eic_render_header('Acceptance — ' . $manuscript['manuscript_no'], 'Editorial Management &amp; Decision System');
?>
<style>
.warning {
    padding: 14px;
    margin-bottom: 18px;
    background: #fffaf0;
    border: 1px solid #ccb477;
    border-radius: 6px;
    color: #92400e;
}
</style>
<div style="margin-bottom:14px;">
  <a class="btn light" href="manuscript_view.php?id=<?=$manuscriptId?>">← Back to Manuscript View</a>
  <a class="btn light" href="workflow_v1.php" style="margin-left:8px;">← Back to Workflow V1</a>
</div>


<div class="panel">

<h1>
Manuscript Acceptance
</h1>

<p>

Logged in as:

<strong>
<?= ac_h($user['email']) ?>
</strong>

&nbsp; | &nbsp;

Role:

<strong>
<?= ac_h($user['role']) ?>
</strong>

</p>

</div>


<?php if ($saved): ?>

<div class="success">

<strong>
Manuscript accepted successfully.
</strong>

<p>
The manuscript has been marked as
<strong>accepted</strong>
in the editorial database.
</p>

</div>

<?php endif; ?>


<?php if ($error): ?>

<div class="error">

<strong>
Error:
</strong>

<?= ac_h($error) ?>

</div>

<?php endif; ?>


<div class="panel">

<h2>
<?= ac_h($manuscript['manuscript_no']) ?>
</h2>

<p>

<strong>
<?= ac_h($manuscript['title']) ?>
</strong>

</p>


<div class="info">

<strong>
Current Status:
</strong>

<span class="badge">

<?= ac_h($manuscript['status']) ?>

</span>

</div>

</div>


<div class="panel">

<h2>
Editorial Decision
</h2>


<?php if (!$decision): ?>

<div class="warning">

No editorial decision has been recorded.

</div>

<?php else: ?>

<div class="info">

<strong>
Decision:
</strong>

<span class="badge">

<?= ac_h(
    ucwords(
        str_replace(
            '_',
            ' ',
            $decision['decision']
        )
    )
) ?>

</span>

</div>


<div class="info">

<strong>
Decided by:
</strong>

<?= ac_h(
    $decision['decision_editor_email']
    ?? ''
) ?>

</div>


<div class="info">

<strong>
Date:
</strong>

<?= ac_h(
    $decision['created_at']
) ?>

</div>


<?php if (!empty($decision['letter'])): ?>

<div class="info">

<strong>
Editorial Letter:
</strong>

<p>

<?= nl2br(
    ac_h($decision['letter'])
) ?>

</p>

</div>

<?php endif; ?>

<?php endif; ?>

</div>


<div class="panel">

<h2>
Reviewer Summary
</h2>


<?php if (!$reviews): ?>

<p>
No reviewer assignments found.
</p>

<?php else: ?>

<table>

<tr>

<th>
Status
</th>

<th>
Recommendation
</th>

<th>
Submitted
</th>

</tr>


<?php foreach ($reviews as $review): ?>

<tr>

<td>
<?= ac_h($review['status']) ?>
</td>

<td>

<?php

$rec = $review['recommendation']
    ? ucwords(
        str_replace(
            '_',
            ' ',
            $review['recommendation']
        )
    )
    : 'Not submitted';

?>

<?= ac_h($rec) ?>

</td>

<td>
<?= ac_h(
    $review['submitted_at']
    ?? ''
) ?>
</td>

</tr>

<?php endforeach; ?>

</table>

<?php endif; ?>

</div>


<div class="panel">

<h2>
Formal Acceptance
</h2>


<?php if (
    $decision &&
    $decision['decision'] === 'accept'
): ?>

<?php if ($manuscript['status'] === 'accepted'): ?>

<div class="success">

This manuscript is already marked as
<strong>accepted</strong>.

</div>

<?php else: ?>

<p>
The editorial decision is <strong>Accept</strong>.
You can now formally record the manuscript as accepted
for publication.
</p>

<form method="post">

<input
    type="hidden"
    name="csrf"
    value="<?= ac_h($csrf) ?>"
>

<button type="submit">
Confirm Acceptance
</button>

</form>

<?php endif; ?>

<?php else: ?>

<div class="warning">

Formal acceptance is available only after an
editorial decision of <strong>Accept</strong>.

</div>

<?php endif; ?>

</div>


<p>

<a href="workflow_v1.php">
← Back to Workflow
</a>

</p>


<?php
eic_render_footer();

