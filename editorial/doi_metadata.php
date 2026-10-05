<?php
/**
 * doi_metadata.php
 * ew_doi_metadata does not exist. DOI, volume, issue, year, pages are columns
 * in the existing `production` table. Abstract/keywords come from `manuscripts`.
 */
require_once __DIR__.'/production_common.php';
$db=db();$u=prod_user($db);
$mid=(int)($_GET['id']??0);
if(!$mid)exit('Invalid manuscript ID.');
$m=prod_ms($db,$mid);
prod_ensure($db,$mid,(int)$u['id']);

// Load production row (contains doi, volume, issue, year, pages)
$s=$db->prepare("SELECT * FROM production WHERE manuscript_id=? LIMIT 1");
$s->execute([$mid]);
$prod=$s->fetch(PDO::FETCH_ASSOC);

// Derive metadata_status from publication_status
$metaStatus='draft';
if(!empty($prod['doi'])) $metaStatus='ready';
if(!empty($prod['publication_status'])) $metaStatus='registered';

$row=[
    'doi'             =>$prod['doi']??'',
    'volume'          =>$prod['volume']??'',
    'issue'           =>$prod['issue']??'',
    'pages'           =>$prod['pages']??'',
    'year'            =>$prod['year']??'',
    'authors_text'    =>'',   // display-only; real data in manuscript_authors
    'abstract_text'   =>$m['abstract']??'',  // from manuscripts.abstract
    'keywords'        =>$m['keywords']??'',
    'metadata_status' =>$metaStatus,
];

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    prod_check_csrf();
    $status=$_POST['metadata_status']??'draft';
    $doi=trim($_POST['doi']??'');
    $volume=trim($_POST['volume']??'');
    $issue=trim($_POST['issue']??'');
    $pages=trim($_POST['pages']??'');
    $year=(int)($_POST['year']??date('Y'));

    if(!in_array($status,['draft','ready','registered'],true)){
        $error='Invalid metadata status.';
    } elseif($status==='registered'&&!$doi){
        $error='A DOI is required before metadata can be marked registered.';
    } else {
        // Update production table with DOI and issue details
        $pubStatus=($status==='registered')?'IN_PRESS':null;
        $stmt=$db->prepare("UPDATE production SET doi=?,volume=?,issue=?,year=?,pages=?,publication_status=COALESCE(?,publication_status) WHERE manuscript_id=?");
        $stmt->execute([$doi,$volume,$issue,$year?:null,$pages,$pubStatus,$mid]);

        // If DOI registered, update manuscript status to IN_PRESS
        if($status==='registered'){
            $db->prepare("UPDATE manuscripts SET status='IN_PRESS', updated_at=NOW() WHERE id=?")->execute([$mid]);
        }

        prod_log($db,$mid,(int)$u['id'],'doi_metadata_update','Metadata status: '.$status.' DOI: '.$doi);

        $row=array_merge($row,['doi'=>$doi,'volume'=>$volume,'issue'=>$issue,'pages'=>$pages,'year'=>$year,'metadata_status'=>$status]);
        $metaStatus=$status;
    }
}

prod_header('DOI / Metadata',$u);
echo '<div class="panel"><h1>DOI / Metadata</h1><p><strong>'.prod_h($m['manuscript_no']).'</strong> — '.prod_h($m['title']).'</p>';
if($error)echo '<div class="err">'.prod_h($error).'</div>';
echo '<form method="post">'.
     '<input type="hidden" name="csrf" value="'.prod_h(prod_csrf()).'">'.
     '<label>DOI</label><input name="doi" value="'.prod_h($row['doi']).'" placeholder="10.xxxx/xxxxx">'.
     '<label>Volume</label><input name="volume" value="'.prod_h($row['volume']).'">'.
     '<label>Issue</label><input name="issue" value="'.prod_h($row['issue']).'">'.
     '<label>Year</label><input name="year" type="number" value="'.prod_h($row['year']?:date('Y')).'">'.
     '<label>Pages / Article number</label><input name="pages" value="'.prod_h($row['pages']).'">'.
     '<label>Abstract (from manuscript)</label><textarea readonly style="background:#f8f9fa">'.prod_h($row['abstract_text']).'</textarea>'.
     '<label>Keywords (from manuscript)</label><input readonly style="background:#f8f9fa" value="'.prod_h($row['keywords']).'">'.
     '<label>Metadata Status</label><select name="metadata_status">';
foreach(['draft','ready','registered'] as $x)
    echo '<option value="'.$x.'" '.($row['metadata_status']===$x?'selected':'').'>'.ucfirst($x).'</option>';
echo '</select><button type="submit">Save Metadata</button></form></div>'.
     '<p><a class="button" href="production.php">← Production Dashboard</a></p>';
prod_footer();
