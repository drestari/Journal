<?php
require_once __DIR__.'/production_common.php';
$db=db();$u=prod_user($db);$mid=(int)($_GET['id']??0);
if(!$mid)exit('Invalid manuscript ID.');
$m=prod_ms($db,$mid);
prod_ensure($db,$mid,(int)$u['id']);

// Load galley proofs
$s=$db->prepare("SELECT * FROM ew_galley_proofs WHERE manuscript_id=? ORDER BY id DESC");
$s->execute([$mid]);$proofs=$s->fetchAll(PDO::FETCH_ASSOC);$error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    prod_check_csrf();
    $action=$_POST['action']??'';
    $status=$_POST['status']??'generated';
    $corrections=trim($_POST['corrections']??'');

    if($action==='create'){
        $version=count($proofs)+1;
        $db->prepare("INSERT INTO ew_galley_proofs(manuscript_id,proof_version,status,created_by) VALUES(?,?,?,?)")
           ->execute([$mid,$version,'generated',(int)$u['id']]);
        // Update production proof_status to PENDING (proof exists, awaiting review)
        $db->prepare("UPDATE production SET proof_status='PENDING' WHERE manuscript_id=?")
           ->execute([$mid]);
        prod_log($db,$mid,(int)$u['id'],'galley_proof_created','Proof version '.$version);
        header("Location: galley_proof.php?id=$mid");exit;
    }

    if($action==='update'){
        $pid=(int)$_POST['proof_id'];
        $allowed=['generated','sent_to_author','corrections_received','approved'];
        if(!in_array($status,$allowed,true)){
            $error='Invalid proof status.';
        } else {
            $db->prepare("UPDATE ew_galley_proofs SET status=?,sent_at=?,corrections=?,approved_at=? WHERE id=? AND manuscript_id=?")
               ->execute([
                   $status,
                   $status==='sent_to_author'?date('Y-m-d H:i:s'):null,
                   $corrections,
                   $status==='approved'?date('Y-m-d H:i:s'):null,
                   $pid,$mid
               ]);
            // Sync production.proof_status
            $proofStatusMap=[
                'generated'           =>'PENDING',
                'sent_to_author'      =>'SENT',
                'corrections_received'=>'SENT',
                'approved'            =>'APPROVED'
            ];
            $prodProofStatus=$proofStatusMap[$status]??'PENDING';
            $db->prepare("UPDATE production SET proof_status=? WHERE manuscript_id=?")
               ->execute([$prodProofStatus,$mid]);
            prod_log($db,$mid,(int)$u['id'],'galley_proof_update','Proof '.$pid.' status: '.$status);
            if ($status === 'sent_to_author') {
                sendWorkflowNotification($db, 'GALLERY_PROOF_SENT', $mid, ['proof_version' => $pid, 'notes' => $corrections]);
            }
            header("Location: galley_proof.php?id=$mid");exit;
        }
    }
}

prod_header('Galley Proof',$u);
echo '<div class="panel"><h1>Galley Proof</h1><p><strong>'.prod_h($m['manuscript_no']).'</strong> — '.prod_h($m['title']).'</p>';
if($error)echo '<div class="err">'.prod_h($error).'</div>';
echo '<form method="post"><input type="hidden" name="csrf" value="'.prod_h(prod_csrf()).'">'.
     '<input type="hidden" name="action" value="create"><button type="submit">Create New Proof Version</button></form></div>';

foreach($proofs as $p){
    echo '<div class="panel"><h2>Proof Version '.prod_h($p['proof_version']).'</h2>'.
         '<p>Status: <span class="badge">'.prod_h($p['status']).'</span></p>'.
         '<form method="post">'.
         '<input type="hidden" name="csrf" value="'.prod_h(prod_csrf()).'">'.
         '<input type="hidden" name="action" value="update">'.
         '<input type="hidden" name="proof_id" value="'.$p['id'].'">'.
         '<label>Status</label><select name="status">';
    foreach(['generated','sent_to_author','corrections_received','approved'] as $x)
        echo '<option value="'.$x.'" '.($p['status']===$x?'selected':'').'>'.ucwords(str_replace('_',' ',$x)).'</option>';
    echo '</select><label>Corrections / Notes</label>'.
         '<textarea name="corrections">'.prod_h($p['corrections']??'').'</textarea>'.
         '<button type="submit">Update Proof</button></form></div>';
}
echo '<p><a class="button" href="production.php">← Production Dashboard</a></p>';
prod_footer();
