<?php
declare(strict_types=1);

require_once __DIR__ . '/workflow_v1_common.php';

$db = db();
$u = wf_require_roles($db, ['admin', 'editor_in_chief', 'editor', 'managing_editor']);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Invalid manuscript ID.');
}

$m = wf_ms($db, $id);
if (!$m) {
    http_response_code(404);
    exit('Manuscript not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    wf_check_csrf();
    $rid = (int)($_POST['reviewer_id'] ?? 0);
    $dueRaw = trim((string)($_POST['due_at'] ?? ''));
    $due = ($dueRaw !== '') ? date('Y-m-d H:i:s', strtotime($dueRaw)) : null;

    if ($rid <= 0) {
        wf_flash('Please select a valid reviewer.');
    } else {
        $s = $db->prepare("INSERT INTO ew_reviewer_assignments(manuscript_id, reviewer_id, assigned_by, due_at) VALUES(?, ?, ?, ?)");
        try {
            $s->execute([$id, $rid, $u['id'], $due]);
            wf_status_update($db, $id, 'review');
            wf_log($db, $id, (int)$u['id'], 'reviewer_assigned', 'Assigned Reviewer ID ' . $rid);

            sendWorkflowNotification($db, 'REVIEWER_INVITED', $id, ['reviewer_id' => $rid, 'due_at' => $dueRaw]);
            sendWorkflowNotification($db, 'REVIEW_STARTED', $id);

            wf_flash('Reviewer successfully assigned! Manuscript status updated to Under Review.');
        } catch (PDOException $ex) {
            wf_flash('Reviewer could not be assigned: this reviewer is already assigned or a database constraint occurred.');
        }
    }
    wf_redirect('reviewers.php?id=' . $id);
}

// Fetch active reviewers pool
$rv = $db->query("SELECT * FROM ew_reviewer_pool WHERE active = 1 ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch currently assigned reviewers
$s = $db->prepare("
    SELECT ra.*, r.full_name, r.email, r.affiliation, r.expertise,
           pr.recommendation, pr.submitted_at AS review_submitted_at
    FROM ew_reviewer_assignments ra
    JOIN ew_reviewer_pool r ON r.id = ra.reviewer_id
    LEFT JOIN ew_peer_reviews pr ON pr.assignment_id = ra.id
    WHERE ra.manuscript_id = ?
    ORDER BY ra.id DESC
");
$s->execute([$id]);
$assigned = $s->fetchAll(PDO::FETCH_ASSOC);
require_once __DIR__ . '/includes/eic_layout.php';
eic_render_header('Assign Reviewers — ' . $m['manuscript_no'], 'Peer Review Coordination');
?>
<style>
.grid2{display:grid;grid-template-columns:2fr 1fr;gap:16px}
@media(max-width:750px){.grid2{grid-template-columns:1fr}}
</style>
<div style="margin-bottom:14px;">
  <a class="btn light" href="manuscript_view.php?id=<?=$id?>">← Back to Manuscript View</a>
  <a class="btn light" href="reviewer_manage.php" style="margin-left:8px;">Manage Reviewer Pool →</a>
</div>
  <?php wf_flash_show(); ?>

  <div class="panel">
    <div style="font-size:12px;font-weight:bold;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Assign Peer Reviewer</div>
    <h1 style="margin:6px 0 10px 0;font-size:22px;color:#0f172a;"><?=wf_h($m['manuscript_no'])?></h1>
    <h2 style="margin:0 0 14px 0;font-size:16px;font-weight:normal;color:#475569;line-height:1.4;"><?=wf_h($m['title'])?></h2>
    <div style="display:flex;gap:12px;align-items:center;">
      <span class="badge" style="background:#e0f2fe;color:#0369a1;"><?=wf_h($m['article_type'] ?: 'Article')?></span>
      <span class="badge" style="background:#f1f5f9;color:#334155;">Current Status: <?=wf_h(slabel($m['status']))?></span>
    </div>
  </div>

  <div class="panel">
    <h3 style="margin-top:0;font-size:16px;border-bottom:1px solid #edf2f7;padding-bottom:10px;">Select &amp; Assign Reviewer</h3>
    <form method="post">
      <input type="hidden" name="id" value="<?=$id?>">
      <input type="hidden" name="csrf" value="<?=wf_h(wf_csrf())?>">

      <div class="grid2">
        <div>
          <label>Select Reviewer from Reviewer Pool *</label>
          <select name="reviewer_id" required>
            <option value="">-- Choose active reviewer --</option>
            <?php foreach ($rv as $r): ?>
              <option value="<?=$r['id']?>">
                <?=wf_h($r['full_name'])?> &mdash; <?=wf_h($r['email'])?> <?=!empty($r['affiliation']) ? '('.wf_h($r['affiliation']).')' : ''?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Review Due Date (Optional)</label>
          <input type="datetime-local" name="due_at" value="<?=date('Y-m-d\TH:i', strtotime('+14 days'))?>">
        </div>
      </div>

      <div style="display:flex;gap:12px;align-items:center;margin-top:10px;">
        <button type="submit" class="btn">Assign Reviewer</button>
        <a href="reviewer_manage.php" target="_blank" class="btn light" style="font-size:12px;">+ Add New Reviewer to Pool ↗</a>
      </div>
    </form>
  </div>

  <div class="panel">
    <h3 style="margin-top:0;font-size:16px;border-bottom:1px solid #edf2f7;padding-bottom:10px;">Currently Assigned Reviewers</h3>
    <?php if (empty($assigned)): ?>
      <div style="color:#64748b;padding:12px 0;">No reviewers have been assigned to this manuscript yet.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Reviewer Name</th>
            <th>Email</th>
            <th>Affiliation</th>
            <th>Status</th>
            <th>Assigned Date</th>
            <th>Due Date</th>
            <th>Recommendation</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($assigned as $r): ?>
            <tr>
              <td><strong><?=wf_h($r['full_name'])?></strong></td>
              <td><?=wf_h($r['email'])?></td>
              <td><?=wf_h($r['affiliation'] ?? '—')?></td>
              <td><span class="badge <?=wf_h(strtolower($r['status']))?>"><?=wf_h(slabel($r['status']))?></span></td>
              <td><?=wf_h($r['invited_at'])?></td>
              <td><?=wf_h($r['due_at'] ?: 'Not specified')?></td>
              <td>
                <?php if (!empty($r['recommendation'])): ?>
                  <strong><?=wf_h(slabel($r['recommendation']))?></strong>
                <?php else: ?>
                  <span style="color:#94a3b8;">Pending Review</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
<?php
eic_render_footer();

