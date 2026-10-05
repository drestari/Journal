<?php
require_once __DIR__.'/workflow_v1_common.php';
$u=wf_require_roles($db, ['admin','editor_in_chief']);$db=db();$id=(int)($_GET['id']??$_POST['id']??0);$m=wf_ms($db,$id);if(!$m)exit('Manuscript not found.');
if($_SERVER['REQUEST_METHOD']==='POST'){wf_check_csrf();$eid=(int)$_POST['editor_id'];$db->prepare("UPDATE ew_editor_assignments SET status='ended',ended_at=NOW() WHERE manuscript_id=? AND status='active'")->execute([$id]);$s=$db->prepare("INSERT INTO ew_editor_assignments(manuscript_id,editor_id,assigned_by) VALUES(?,?,?)");$s->execute([$id,$eid,$u['id']]);wf_status_update($db,$id,'editor_assigned','editor_assigned_at');wf_log($db,$id,$u['id'],'editor_assigned','Editor user ID '.$eid);sendWorkflowNotification($db,'EDITOR_ASSIGNED',$id,['editor_id'=>$eid]);wf_flash('Editor assigned.');wf_redirect('workflow_v1.php');}
$eds=$db->query("SELECT id,email,role FROM users WHERE active=1 AND role IN ('editor','editor_in_chief') ORDER BY email")->fetchAll(PDO::FETCH_ASSOC);
require_once __DIR__ . '/includes/eic_layout.php';
eic_render_header('Assign Editor — ' . $m['manuscript_no'], 'Editorial Management &amp; Decision System');
?>
<div class="panel">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
    <div>
      <h1 class="title" style="margin:0 0 4px 0;">Assign Section Editor</h1>
      <h2 style="font-size:16px;color:#0b5fa5;margin:0 0 6px 0;"><?=wf_h($m['manuscript_no'])?> &bull; <?=wf_h($m['title'])?></h2>
      <div class="muted">Assign a dedicated editor to oversee peer review and evaluation.</div>
    </div>
    <div>
      <a class="btn light" href="manuscript_view.php?id=<?=$id?>">View Full Manuscript →</a>
    </div>
  </div>

  <form method="post" style="max-width:550px;">
    <input type="hidden" name="id" value="<?=$id?>">
    <input type="hidden" name="csrf" value="<?=wf_h(wf_csrf())?>">
    <div style="margin-bottom:16px;">
      <label style="display:block;font-weight:700;margin-bottom:6px;">Select Editorial Board Member</label>
      <select name="editor_id" required style="width:100%;padding:9px;border:1px solid #cbd5e1;border-radius:6px;">
        <option value="">-- Select editor --</option>
        <?php foreach($eds as $e): ?>
          <option value="<?=$e['id']?>"><?=wf_h($e['email'].' — '.slabel($e['role']))?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
      <button class="btn primary" type="submit">Assign Editor</button>
      <a class="btn light" href="workflow_v1.php">Cancel / Back</a>
    </div>
  </form>
</div>
<?php
eic_render_footer();
