<?php
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/journal_submission_bridge.php';
$u=role_required(['admin','editor_in_chief','editor']);
$id=(int)($_GET['id']??0);
$s=get_public_submission($id);
if(!$s){http_response_code(404);exit('Submission not found.');}
if(!isset($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
$q=db()->prepare('SELECT id, manuscript_no FROM manuscripts WHERE manuscript_no=? OR source_public_submission_id=? LIMIT 1');
$q->execute([$s['manuscript_no'],$id]); $existing=$q->fetch();
function val(array $a,string $k): string { return h($a[$k]??''); }
require_once __DIR__ . '/includes/eic_layout.php';
eic_render_header('Submission ' . $s['manuscript_no'] . ' — AJSMR', 'Submissions Queue &bull; External Submission Review');
?>
<div style="margin-bottom:14px;"><a class="btn light" href="new_submissions.php">← Back to New Submissions</a></div>
<div class="panel">
<h1><?=h($s['manuscript_no'])?></h1>
<h2><?=h($s['title'])?></h2>
<div class="meta"><b>Type:</b> <?=val($s,'article_type')?><br>
<b>Corresponding author:</b> <?=val($s,'corresponding_name')?> — <?=val($s,'corresponding_email')?><br>
<b>Institution:</b> <?=val($s,'corresponding_institution')?><br>
<b>Submission date:</b> <?=val($s,'submitted_at')?></div>
</div>

<div class="panel"><h3>Abstract</h3><p><?=nl2br(val($s,'abstract_text'))?></p>
<?php if(!empty($s['keywords'])):?><p><b>Keywords:</b> <?=val($s,'keywords')?></p><?php endif;?></div>

<div class="panel"><h3>Authors</h3>
<table><tr><th>#</th><th>Name</th><th>Email</th><th>ORCID</th><th>Affiliation</th><th>Contribution</th><th>Corresponding</th></tr>
<?php foreach(($s['authors']??[]) as $i=>$a): ?>
<tr><td><?=intval($a['author_order']??$i+1)?></td><td><?=h(author_full_name($a))?></td><td><?=val($a,'email')?></td><td><?=val($a,'orcid')?></td>
<td><?=h(implode(', ',array_filter([$a['department']??'', $a['institution']??'', $a['city']??'', $a['state']??'', $a['country']??''])))?></td>
<td><?=nl2br(val($a,'contribution'))?></td><td><?=!empty($a['is_corresponding'])?'Yes':'No'?></td></tr>
<?php endforeach;?></table></div>

<div class="panel"><h3>Affiliations</h3>
<?php if(empty($s['affiliations'])):?><p class="muted">No separate affiliation records.</p><?php else:?><table><tr><th>#</th><th>Department</th><th>Institution</th><th>City</th><th>State</th><th>Country</th></tr>
<?php foreach($s['affiliations'] as $a):?><tr><td><?=intval($a['affiliation_no']??0)?></td><td><?=val($a,'department')?></td><td><?=val($a,'institution')?></td><td><?=val($a,'city')?></td><td><?=val($a,'state')?></td><td><?=val($a,'country')?></td></tr><?php endforeach;?></table><?php endif;?></div>

<div class="panel"><h3>Declarations</h3>
<table>
<tr><th>Funding</th><td><?=val($s,'funding_status')?><?=!empty($s['funding_statement'])?' — '.val($s,'funding_statement'):''?></td></tr>
<tr><th>Conflict of Interest</th><td><?=val($s,'conflict_status')?><?=!empty($s['conflict_statement'])?' — '.val($s,'conflict_statement'):''?></td></tr>
<tr><th>Research Ethics</th><td><?=val($s,'ethics_status')?><?=!empty($s['ethics_statement'])?' — '.val($s,'ethics_statement'):''?></td></tr>
<tr><th>Consent</th><td><?=val($s,'consent_status')?></td></tr>
<tr><th>Clinical Trial</th><td><?=val($s,'trial_registry')?> <?=val($s,'trial_number')?></td></tr>
<tr><th>Data Availability</th><td><?=val($s,'data_status')?> <?=val($s,'data_repository')?> <?=val($s,'data_identifier')?></td></tr>
<tr><th>AI / Generative AI</th><td><?=val($s,'ai_status')?><?=!empty($s['ai_statement'])?' — '.val($s,'ai_statement'):''?></td></tr>
</table></div>

<div class="panel"><h3>Suggested / Excluded Reviewers</h3>
<?php if(empty($s['reviewers'])):?><p class="muted">No reviewer information supplied.</p><?php else:?><table><tr><th>Type</th><th>Name</th><th>Email</th><th>Institution</th><th>Country</th><th>Expertise</th><th>Exclusion reason</th></tr>
<?php foreach($s['reviewers'] as $r):?><tr><td><?=val($r,'reviewer_type')?></td><td><?=val($r,'full_name')?></td><td><?=val($r,'email')?></td><td><?=val($r,'institution')?></td><td><?=val($r,'country')?></td><td><?=val($r,'expertise')?></td><td><?=val($r,'exclusion_reason')?></td></tr><?php endforeach;?></table><?php endif;?></div>

<div class="panel"><h3>Files</h3><table><tr><th>Type</th><th>Original filename</th><th>Size</th></tr>
<?php foreach(($s['files']??[]) as $f):?><tr><td><?=val($f,'file_type')?></td><td><?=val($f,'original_name')?></td><td><?=number_format((int)($f['file_size']??0))?> bytes</td></tr><?php endforeach;?></table></div>

<div class="panel">
<?php if($existing): ?>
<div class="ok"><b>Already imported.</b> Editorial manuscript ID <?=intval($existing['id'])?> (<?=h($existing['manuscript_no'])?>).</div>
<?php else: ?>
<form method="post" action="import_public_submission.php" onsubmit="return confirm('Import this submission into the editorial workflow?');">
<input type="hidden" name="submission_id" value="<?=intval($id)?>">
<input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>">
<button class="btn" type="submit">Import to Editorial System</button>
</form>
<?php endif;?>
</div>
<?php
eic_render_footer();

