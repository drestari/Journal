<?php

require_once __DIR__ . '/config/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| AJSMR Editorial Workflow V1
| Standalone authorization
|--------------------------------------------------------------------------
*/

$db = db();

/*
 * Get currently logged-in user directly from session + database.
 */
if (empty($_SESSION['user']['id'])) {
    http_response_code(403);
    exit('Access denied. Please login again.');
}

$sessionUserId = (int) $_SESSION['user']['id'];

$stmt = $db->prepare("
    SELECT id, email, role, active
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$sessionUserId]);

$u = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$u) {
    http_response_code(403);
    exit('Access denied. User account not found.');
}

if ((int)$u['active'] !== 1) {
    http_response_code(403);
    exit('Access denied. User account is inactive.');
}

/*
 * Workflow management roles.
 */
$allowedRoles = [
    'admin',
    'editor_in_chief',
    'editor'
];

if (!in_array((string)$u['role'], $allowedRoles, true)) {
    http_response_code(403);

    echo '<h1>Access denied</h1>';
    echo '<p>Your current role is: <strong>';
    echo htmlspecialchars(
        (string)$u['role'],
        ENT_QUOTES,
        'UTF-8'
    );
    echo '</strong></p>';

    exit;
}


/*
|--------------------------------------------------------------------------
| Flash message
|--------------------------------------------------------------------------
*/

if (!empty($_SESSION['wf_flash'])) {

    $flash = $_SESSION['wf_flash'];

    unset($_SESSION['wf_flash']);

} else {

    $flash = '';

}


/*
|--------------------------------------------------------------------------
| Load manuscripts
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        m.*,

        ea.editor_id,

        eu.email AS editor_email,

        (
            SELECT t.result
            FROM ew_technical_checks t
            WHERE t.manuscript_id = m.id
            ORDER BY t.id DESC
            LIMIT 1
        ) AS tech_result,

        (
            SELECT COUNT(*)
            FROM ew_reviewer_assignments ra
            WHERE ra.manuscript_id = m.id
              AND ra.status NOT IN ('cancelled')
        ) AS reviewer_count

    FROM manuscripts m

    LEFT JOIN ew_editor_assignments ea
        ON ea.manuscript_id = m.id
       AND ea.status = 'active'

    LEFT JOIN users eu
        ON eu.id = ea.editor_id

    ORDER BY m.submitted_at DESC
";

$stmt = $db->query($sql);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| HTML
|--------------------------------------------------------------------------
*/

function wf_html($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

require_once __DIR__ . '/includes/eic_layout.php';
eic_render_header('AJSMR Editorial Workflow V1', 'Editorial Management &amp; Decision System');
?>
<style>
.wf-card {
    background: #ffffff;
    border: 1px solid #e3e9f1;
    border-radius: 10px;
    padding: 22px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(16,32,64,0.04);
}
.manuscript-number {
    font-size: 20px;
    font-weight: 800;
    margin-bottom: 6px;
    color: #092b5f;
}
.manuscript-title {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 12px;
    color: #1e293b;
}
.info {
    margin: 8px 0;
    font-size: 13.5px;
    color: #334155;
}
.actions {
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px solid #eef2f6;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.actions a {
    display: inline-block;
    padding: 7px 13px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    background: #f8fafc;
    color: #0b5fa5;
    font-weight: 600;
    font-size: 12.5px;
    text-decoration: none;
    transition: all 0.15s ease;
}
.actions a:hover {
    background: #0b5fa5;
    color: #fff;
    border-color: #0b5fa5;
}
.actions a.primary-act {
    background: #0b5fa5;
    color: #fff;
    border-color: #0b5fa5;
}
.actions a.primary-act:hover {
    background: #084b84;
}
.flash {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
    padding: 12px 16px;
    margin-bottom: 20px;
    border-radius: 8px;
    font-size: 14px;
}
.empty {
    background: #fff;
    border: 1px solid #e3e9f1;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    color: #64748b;
}
</style>

<div class="panel" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
  <div>
    <h1 class="title" style="margin:0 0 4px 0;">AJSMR Editorial Workflow V1</h1>
    <div class="muted">Complete editorial lifecycle: Technical Check, Reviewer Pool, Peer Reviews, Decisions, and Revisions.</div>
  </div>
  <div>
    <a class="btn light" href="reviewer_manage.php">Manage Reviewer Pool →</a>
  </div>
</div>


<?php if ($flash): ?>

<div class="flash">

<?= wf_html($flash) ?>

</div>

<?php endif; ?>


<?php if (!$rows): ?>

<div class="empty">

<strong>
No manuscripts found.
</strong>

<p>
There are currently no manuscripts in the editorial database.
</p>

</div>

<?php else: ?>


<?php foreach ($rows as $r): ?>

<div class="card">


<div class="manuscript-number">

<?= wf_html($r['manuscript_no']) ?>

</div>


<div class="manuscript-title">

<?= wf_html($r['title']) ?>

</div>


<div class="info">

<strong>
Status:
</strong>

<span class="badge">

<?= wf_html($r['status']) ?>

</span>

</div>


<div class="info">

<strong>
Technical Check:
</strong>

<span class="badge">

<?= wf_html(
    $r['tech_result'] ?: 'Not checked'
) ?>

</span>

</div>


<div class="info">

<strong>
Reviewers:
</strong>

<?= wf_html($r['reviewer_count']) ?>

</div>


<div class="info">

<strong>
Assigned Editor:
</strong>

<?php if (!empty($r['editor_email'])): ?>

<?= wf_html($r['editor_email']) ?>

<?php else: ?>

Not assigned

<?php endif; ?>

</div>


<div class="actions">


<a href="technical_check.php?id=<?= (int)$r['id'] ?>">
Technical Check
</a>


<a href="assign_editor.php?id=<?= (int)$r['id'] ?>">
Assign Editor
</a>


<a href="reviewers.php?id=<?= (int)$r['id'] ?>">
Reviewers
</a>


<a href="decision.php?id=<?= (int)$r['id'] ?>">
Decision
</a>


<a href="revision.php?id=<?= (int)$r['id'] ?>">
Revision
</a>


<a href="accept.php?id=<?= (int)$r['id'] ?>">
Acceptance
</a>


</div>


</div>

<?php endforeach; ?>


<?php endif; ?>


<?php
eic_render_footer();