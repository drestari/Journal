<?php
declare(strict_types=1);
require_once __DIR__ . '/article_common.php';
$me=article_eic();

$id=(int)($_GET['id'] ?? $_POST['id'] ?? 0);
if($id<1) { http_response_code(400); exit('Invalid article ID.'); }

$st=db()->prepare('SELECT * FROM articles WHERE id=? LIMIT 1'); $st->execute([$id]); $article=$st->fetch();
if(!$article){http_response_code(404);exit('Article not found.');}
$as=db()->prepare('SELECT * FROM article_authors WHERE article_id=? ORDER BY author_order,id');$as->execute([$id]);$authors=$as->fetchAll();
$rs=db()->prepare('SELECT reference_text FROM article_references WHERE article_id=? ORDER BY reference_order,id');$rs->execute([$id]);$references=implode("\n",array_column($rs->fetchAll(),'reference_text'));

$error='';$success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    check_csrf();
    $fields=['article_id','article_type','title','running_title','abstract','keywords','volume','issue','year','received_date','revised_date','accepted_date','published_date','page_start','page_end','doi','section','license','rights_statement','status'];
    foreach($fields as $f){$article[$f]=trim((string)($_POST[$f]??''));}
    if(!$article['article_id']||!$article['title']||!$article['abstract'])throw new RuntimeException('Article ID, title and abstract are required.');
    if(!in_array($article['status'],article_statuses(),true))$article['status']='DRAFT';

    $pdo=db();$pdo->beginTransaction();
    $chk=$pdo->prepare('SELECT id FROM articles WHERE article_id=? AND id<>? LIMIT 1');$chk->execute([$article['article_id'],$id]);if($chk->fetch())throw new RuntimeException('Article ID already exists.');
    if($article['doi']!==''){ $chk=$pdo->prepare('SELECT id FROM articles WHERE doi=? AND id<>? LIMIT 1');$chk->execute([$article['doi'],$id]);if($chk->fetch())throw new RuntimeException('DOI already exists.'); }

    $s=$pdo->prepare('UPDATE articles SET article_id=?,article_type=?,title=?,running_title=?,abstract=?,keywords=?,volume=?,issue=?,year=?,received_date=?,revised_date=?,accepted_date=?,published_date=?,page_start=?,page_end=?,doi=?,section=?,license=?,rights_statement=?,status=? WHERE id=?');
    $s->execute([$article['article_id'],$article['article_type'],$article['title'],$article['running_title'],$article['abstract'],$article['keywords'],$article['volume'],$article['issue'],$article['year']!==''?(int)$article['year']:null,$article['received_date']?:null,$article['revised_date']?:null,$article['accepted_date']?:null,$article['published_date']?:null,$article['page_start'],$article['page_end'],$article['doi']?:null,$article['section'],$article['license'],$article['rights_statement'],$article['status'],$id]);

    $pdo->prepare('DELETE FROM article_authors WHERE article_id=?')->execute([$id]);
    $names=$_POST['author_name']??[];$aff=$_POST['affiliation']??[];$emails=$_POST['author_email']??[];$orcids=$_POST['orcid']??[];$corr=$_POST['is_corresponding']??[];
    $ins=$pdo->prepare('INSERT INTO article_authors(article_id,author_name,affiliation,email,orcid,author_order,is_corresponding) VALUES(?,?,?,?,?,?,?)');
    foreach((array)$names as $i=>$name){$name=trim((string)$name);if($name==='')continue;$ins->execute([$id,$name,trim((string)($aff[$i]??'')),trim((string)($emails[$i]??''))?:null,trim((string)($orcids[$i]??''))?:null,$i+1,isset($corr[$i])?1:0]);}

    $pdo->prepare('DELETE FROM article_references WHERE article_id=?')->execute([$id]);
    $refs=article_parse_references((string)($_POST['references']??''));$ri=$pdo->prepare('INSERT INTO article_references(article_id,reference_text,reference_order) VALUES(?,?,?)');
    foreach($refs as $i=>$ref)$ri->execute([$id,$ref,$i+1]);
    $pdo->commit();

    foreach(['pdf'=>'pdf_file','supplementary'=>'supplementary_file','graphical_abstract'=>'graphical_abstract','cover_image'=>'cover_image'] as $type=>$field){
      if(!empty($_FILES[$field]['name'])){
        $path=article_upload($_FILES[$field],$type,$id);
        if($path){$col=$type==='pdf'?'pdf_file':$type;$u=db()->prepare("UPDATE articles SET {$col}=? WHERE id=?");$u->execute([$path,$id]);}
      }
    }
    article_log('ARTICLE_UPDATED',$id,'Article '.$article['article_id']);
    $success='Article updated successfully.';
    $as=db()->prepare('SELECT * FROM article_authors WHERE article_id=? ORDER BY author_order,id');$as->execute([$id]);$authors=$as->fetchAll();
    $rs=db()->prepare('SELECT reference_text FROM article_references WHERE article_id=? ORDER BY reference_order,id');$rs->execute([$id]);$references=implode("\n",array_column($rs->fetchAll(),'reference_text'));
  }catch(Throwable $e){if(isset($pdo)&&$pdo instanceof PDO&&$pdo->inTransaction())$pdo->rollBack();$error=$e->getMessage();}
}
$csrfToken=csrf();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>AJSMR | Edit Article</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f4f7fb;color:#25344a;font-family:Arial,Helvetica,sans-serif}.top{background:linear-gradient(135deg,#092b5f,#0b5fa5);color:#fff;padding:22px 30px}.top-in{max-width:1180px;margin:auto;display:flex;justify-content:space-between}.top a{color:#fff;text-decoration:none}.wrap{max-width:1350px;margin:24px auto;padding:0 18px}.card{background:#fff;border:1px solid #e4eaf2;border-radius:12px;margin-bottom:18px}.card h2{font-size:18px;padding:17px 22px;margin:0;border-bottom:1px solid #e8edf3}.body{padding:22px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.full{grid-column:1/-1}.field label{display:block;font-size:12px;font-weight:700;margin-bottom:6px;color:#475467}.field input,.field select,.field textarea{width:100%;padding:10px 11px;border:1px solid #cbd5e1;border-radius:7px;font:inherit}.field textarea{min-height:130px}.author{border:1px solid #e1e7ef;padding:15px;border-radius:8px;margin-bottom:10px}.author-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.wide{grid-column:1/-1}.btn{background:#0b5fa5;color:#fff;border:0;border-radius:7px;padding:11px 17px;font-weight:800;text-decoration:none;cursor:pointer}.btn.light{background:#eaf2f9;color:#0b5fa5}.alert{padding:12px;border-radius:7px;margin-bottom:15px}.error{background:#fff1f0;color:#b42318}.success{background:#ecfdf3;color:#176b39}.actions{display:flex;justify-content:space-between;align-items:center}@media(max-width:800px){.grid,.author-grid{grid-template-columns:1fr}.full,.wide{grid-column:auto}.actions{gap:10px;flex-direction:column;align-items:stretch}}
</style></head><body>
<header class="top"><div class="top-in"><strong>AJSMR — Edit Published Article</strong><div style="display:flex;gap:12px;"><a class="btn light" href="article_manage.php">← Manage Articles</a><a class="btn light" href="dashboard.php">EIC Dashboard</a></div></div></header>
<main class="wrap">
<div class="dashboard-layout">
<?php include __DIR__ . '/includes/eic_sidebar.php'; ?>
<div class="right-panel">
<?php if($error):?><div class="alert error"><?=article_h($error)?></div><?php endif;?>
<?php if($success):?><div class="alert success"><?=article_h($success)?> <a href="<?=article_h(article_public_url($article['article_id']))?>" target="_blank">View Article →</a></div><?php endif;?>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=article_h($csrfToken)?>"><input type="hidden" name="id" value="<?=$id?>">
<section class="card"><h2>Article Information</h2><div class="body"><div class="grid">
<?php foreach(['article_id'=>'Article ID','article_type'=>'Article Type','title'=>'Title','running_title'=>'Running Title','abstract'=>'Abstract','keywords'=>'Keywords','volume'=>'Volume','issue'=>'Issue','year'=>'Year','received_date'=>'Received Date','revised_date'=>'Revised Date','accepted_date'=>'Accepted Date','published_date'=>'Published Date','page_start'=>'Page Start','page_end'=>'Page End','doi'=>'DOI','section'=>'Section','license'=>'License','rights_statement'=>'Rights / Copyright Statement'] as $f=>$label): ?>
<div class="field <?=in_array($f,['title','abstract','keywords','rights_statement'])?'full':'' ?>"><label><?=article_h($label)?></label><?php if(in_array($f,['abstract','rights_statement'])):?><textarea name="<?=article_h($f)?>"><?=article_h($article[$f])?></textarea><?php elseif($f==='article_type'):?><select name="article_type"><?php foreach(['Research Article','Review Article','Short Communication','Case Report','Editorial','Letter to the Editor','Method Article','Book Review'] as $t):?><option <?=($article[$f]===$t?'selected':'')?>><?=article_h($t)?></option><?php endforeach;?></select><?php elseif(in_array($f,['received_date','revised_date','accepted_date','published_date'])):?><input type="date" name="<?=article_h($f)?>" value="<?=article_h($article[$f])?>"><?php else:?><input name="<?=article_h($f)?>" value="<?=article_h($article[$f])?>"><?php endif;?></div>
<?php endforeach;?>
<div class="field"><label>Status</label><select name="status"><?php foreach(article_statuses() as $s):?><option <?=($article['status']===$s?'selected':'')?>><?=article_h($s)?></option><?php endforeach;?></select></div>
</div></div></section>
<section class="card"><h2>Authors</h2><div class="body"><div id="authors"><?php foreach($authors as $i=>$a):?><div class="author"><div class="author-grid">
<div class="field"><label>Name</label><input name="author_name[]" value="<?=article_h($a['author_name'])?>" required></div><div class="field"><label>Email</label><input name="author_email[]" value="<?=article_h($a['email']??'')?>"></div><div class="field wide"><label>Affiliation</label><input name="affiliation[]" value="<?=article_h($a['affiliation']??'')?>"></div><div class="field"><label>ORCID</label><input name="orcid[]" value="<?=article_h($a['orcid']??'')?>"></div><label><input type="checkbox" name="is_corresponding[<?=$i?>]" value="1" <?=((int)$a['is_corresponding']===1?'checked':'')?>> Corresponding author</label>
</div></div><?php endforeach;?></div><button class="btn light" type="button" onclick="addAuthor()">+ Add Author</button></div></section>
<section class="card"><h2>Files</h2><div class="body"><div class="grid">
<div class="field"><label>Replace Main PDF</label><input type="file" name="pdf_file" accept=".pdf"><small><?=article_h($article['pdf_file']?'Current: '.$article['pdf_file']:'No PDF uploaded')?></small></div>
<div class="field"><label>Replace Supplementary File</label><input type="file" name="supplementary_file"><small><?=article_h($article['supplementary_file']?'Current: '.$article['supplementary_file']:'None')?></small></div>
<div class="field"><label>Replace Graphical Abstract</label><input type="file" name="graphical_abstract"></div>
<div class="field"><label>Replace Cover Image</label><input type="file" name="cover_image"></div>
</div></div></section>
<section class="card"><h2>References</h2><div class="body"><textarea style="width:100%;min-height:220px;padding:11px;border:1px solid #cbd5e1;border-radius:7px" name="references"><?=article_h($references)?></textarea></div></section>
<section class="card"><div class="body actions"><a class="btn light" href="article_manage.php">Cancel</a><button class="btn" type="submit">UPDATE ARTICLE</button></div></section>
</form>
</div><!-- /.right-panel -->
</div><!-- /.dashboard-layout -->
</main>
<script>
function addAuthor(){const box=document.getElementById('authors'),i=box.children.length,d=document.createElement('div');d.className='author';d.innerHTML=`<div class="author-grid"><div class="field"><label>Name</label><input name="author_name[]" required></div><div class="field"><label>Email</label><input name="author_email[]"></div><div class="field wide"><label>Affiliation</label><input name="affiliation[]"></div><div class="field"><label>ORCID</label><input name="orcid[]"></div><label><input type="checkbox" name="is_corresponding[${i}]" value="1"> Corresponding author</label></div>`;box.appendChild(d);}
</script></body></html>
