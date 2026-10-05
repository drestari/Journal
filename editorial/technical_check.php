<?php
require_once __DIR__.'/workflow_v1_common.php';
$u=wf_require_roles($db, ['admin','editor_in_chief','editor']); $db=db(); $id=(int)($_GET['id']??$_POST['id']??0); $m=wf_ms($db,$id); if(!$m) exit('Manuscript not found.');
if($_SERVER['REQUEST_METHOD']==='POST'){wf_check_csrf();$result=$_POST['result']??'pending';$comments=trim($_POST['comments']??'');$s=$db->prepare("INSERT INTO ew_technical_checks(manuscript_id,checked_by,result,comments,checked_at) VALUES(?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE checked_by=VALUES(checked_by),result=VALUES(result),comments=VALUES(comments),checked_at=NOW()");$s->execute([$id,$u['id'],$result,$comments]);if($result==='passed')wf_status_update($db,$id,'technical_check','technical_check_at');elseif($result==='failed')wf_status_update($db,$id,'rejected');else wf_status_update($db,$id,'technical_check');wf_log($db,$id,$u['id'],'technical_check',$result);if($result==='passed'){sendWorkflowNotification($db,'TECHNICAL_CHECK_PASSED',$id);}elseif($result==='minor_corrections'){sendWorkflowNotification($db,'TECHNICAL_CHECK_CORRECTION',$id,['comments'=>$comments]);}elseif($result==='failed'){sendWorkflowNotification($db,'TECHNICAL_CHECK_FAILED',$id,['comments'=>$comments]);}wf_flash('Technical check saved.');wf_redirect('workflow_v1.php');}
$s=$db->prepare("SELECT * FROM ew_technical_checks WHERE manuscript_id=?");$s->execute([$id]);$t=$s->fetch(PDO::FETCH_ASSOC);
require_once __DIR__ . '/includes/eic_layout.php';
eic_render_header('Technical Check — ' . $m['manuscript_no'], 'Editorial Management &amp; Decision System');
?>
<div class="panel">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
    <div>
      <h1 class="title" style="margin:0 0 4px 0;">Technical Check Screening</h1>
      <h2 style="font-size:16px;color:#0b5fa5;margin:0 0 6px 0;"><?=wf_h($m['manuscript_no'])?> &bull; <?=wf_h($m['title'])?></h2>
      <div class="muted">Current Status: <strong><?=wf_h(slabel($m['status']))?></strong></div>
    </div>
    <div>
      <a class="btn light" href="manuscript_view.php?id=<?=$id?>">View Full Manuscript →</a>
    </div>
  </div>

  <form method="post" style="max-width:700px;">
    <input type="hidden" name="id" value="<?=$id?>">
    <input type="hidden" name="csrf" value="<?=wf_h(wf_csrf())?>">
    <div style="margin-bottom:14px;">
      <label style="display:block;font-weight:700;margin-bottom:6px;">Check Result</label>
      <select name="result" style="width:100%;max-width:400px;padding:9px;border:1px solid #cbd5e1;border-radius:6px;">
        <option value="pending" <?=($t['result']??'')==='pending'?'selected':''?>>Pending</option>
        <option value="passed" <?=($t['result']??'')==='passed'?'selected':''?>>Passed</option>
        <option value="minor_corrections" <?=($t['result']??'')==='minor_corrections'?'selected':''?>>Minor corrections required</option>
        <option value="failed" <?=($t['result']??'')==='failed'?'selected':''?>>Failed</option>
      </select>
    </div>
    <div style="margin-bottom:16px;">
      <label style="display:block;font-weight:700;margin-bottom:6px;">Screening Notes &amp; Comments</label>
      <textarea name="comments" rows="6" style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:6px;" placeholder="Technical-check comments or instructions..."><?=wf_h($t['comments']??'')?></textarea>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
      <button class="btn primary" type="submit">Save Technical Check</button>
      <a class="btn light" href="workflow_v1.php">Cancel / Back to Workflow</a>
    </div>
  </form>
</div>
<?php
eic_render_footer();
