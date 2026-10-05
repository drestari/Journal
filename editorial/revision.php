<?php
require_once __DIR__.'/workflow_v1_common.php';
$u=wf_require_roles($db, ['admin','editor_in_chief','editor']);$db=db();$id=(int)($_GET['id']??$_POST['id']??0);$m=wf_ms($db,$id);if(!$m)exit('Manuscript not found.');
if($_SERVER['REQUEST_METHOD']==='POST'){wf_check_csrf();$type=$_POST['revision_type'];$n=(int)$m['version_no']+1;$s=$db->prepare("INSERT INTO ew_revisions(manuscript_id,version_no,requested_by,response_text,status) VALUES(?,?,?,?, 'requested')");$s->execute([$id,$n,$u['id'],trim($_POST['response_text']??'')]);$db->prepare("UPDATE manuscripts SET version_no=?,status=? WHERE id=?")->execute([$n,$type==='minor'?'minor_revision':'major_revision',$id]);wf_log($db,$id,$u['id'],'revision_requested','Version '.$n);sendWorkflowNotification($db,'REVISION_REQUESTED',$id,['revision_type'=>$type,'comments'=>trim($_POST['response_text']??'')]);wf_flash('Revision requested.');wf_redirect('workflow_v1.php');}
$s=$db->prepare("SELECT r.*,u.email FROM ew_revisions r JOIN users u ON u.id=r.requested_by WHERE r.manuscript_id=? ORDER BY r.version_no DESC");$s->execute([$id]);$rows=$s->fetchAll(PDO::FETCH_ASSOC);
require_once __DIR__ . '/includes/eic_layout.php';
eic_render_header('Revision Management — ' . $m['manuscript_no'], 'Editorial Management &amp; Decision System');
?>
<div class="panel">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
    <div>
      <h1 class="title" style="margin:0 0 4px 0;">Revision Management</h1>
      <h2 style="font-size:16px;color:#0b5fa5;margin:0 0 6px 0;"><?=wf_h($m['manuscript_no'])?> &bull; <?=wf_h($m['title'])?></h2>
      <div class="muted">Request minor or major revision cycles from author. Current version: <strong>Version <?=wf_h($m['version_no'])?></strong></div>
    </div>
    <div>
      <a class="btn light" href="manuscript_view.php?id=<?=$id?>">View Full Manuscript →</a>
    </div>
  </div>

  <form method="post" style="max-width:750px;margin-bottom:28px;">
    <input type="hidden" name="id" value="<?=$id?>">
    <input type="hidden" name="csrf" value="<?=wf_h(wf_csrf())?>">
    <div style="margin-bottom:14px;">
      <label style="display:block;font-weight:700;margin-bottom:6px;">Revision Type</label>
      <select name="revision_type" style="width:100%;max-width:350px;padding:9px;border:1px solid #cbd5e1;border-radius:6px;">
        <option value="minor">Minor revision</option>
        <option value="major">Major revision</option>
      </select>
    </div>
    <div style="margin-bottom:16px;">
      <label style="display:block;font-weight:700;margin-bottom:6px;">Revision Request / Detailed Instructions to Author</label>
      <textarea name="response_text" rows="8" style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:6px;" placeholder="Enter specific points, reviewer remarks, and requested corrections for the author..."></textarea>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
      <button class="btn primary" type="submit">Send Revision Request</button>
      <a class="btn light" href="workflow_v1.php">Cancel / Back</a>
    </div>
  </form>

  <h3 style="margin:0 0 12px 0;font-size:16px;color:#092b5f;">Revision History</h3>
  <table>
    <thead>
      <tr>
        <th>Version</th>
        <th>Status</th>
        <th>Requested Date</th>
        <th>Requested By</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="4" style="text-align:center;color:#64748b;padding:20px;">No previous revision cycles recorded.</td></tr>
      <?php else: ?>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><strong>Version <?=wf_h($r['version_no'])?></strong></td>
            <td><span class="badge" style="background:#ffedd5;color:#9a3412;"><?=wf_h(slabel($r['status']))?></span></td>
            <td><?=wf_h($r['requested_at'])?></td>
            <td><?=wf_h($r['email'])?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php
eic_render_footer();
