<?php
declare(strict_types=1);
require_once __DIR__ . '/article_common.php';
article_eic();

$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));

$sql = 'SELECT a.*, (SELECT COUNT(*) FROM article_authors aa WHERE aa.article_id=a.id) author_count
        FROM articles a WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (a.article_id LIKE ? OR a.title LIKE ? OR a.doi LIKE ?)';
    $like='%'.$q.'%'; $params=[$like,$like,$like];
}
if ($status !== '' && in_array($status, article_statuses(), true)) {
    $sql .= ' AND a.status=?'; $params[]=$status;
}
$sql .= ' ORDER BY a.updated_at DESC, a.id DESC';
$st=db()->prepare($sql); $st->execute($params); $rows=$st->fetchAll();
$csrfToken=csrf();
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>AJSMR | Manage Articles</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f4f7fb;color:#25344a;font-family:Arial,Helvetica,sans-serif}.top{background:linear-gradient(135deg,#092b5f,#0b5fa5);color:#fff;padding:22px 30px}.top-inner{max-width:1200px;margin:auto;display:flex;justify-content:space-between;align-items:center}.brand{font-weight:800;font-size:20px}.brand small{display:block;font-size:12px;font-weight:400;margin-top:4px}.top a{color:#fff;text-decoration:none}.wrap{max-width:1350px;margin:24px auto;padding:0 18px}
.bar,.card{background:#fff;border:1px solid #e3e9f1;border-radius:11px;box-shadow:0 7px 25px #10204010}.bar{padding:16px;display:flex;gap:10px;align-items:center;justify-content:space-between;margin-bottom:18px}.filters{display:flex;gap:8px;flex:1}.filters input,.filters select{padding:10px;border:1px solid #ccd6e2;border-radius:7px}.btn{background:#0b5fa5;color:#fff;border:0;border-radius:7px;padding:10px 15px;text-decoration:none;font-weight:800;cursor:pointer}.btn.light{background:#eaf2f9;color:#0b5fa5}.table-wrap{overflow:auto}.table{width:100%;border-collapse:collapse;min-width:900px}.table th,.table td{padding:13px;border-bottom:1px solid #edf0f4;text-align:left;font-size:13px;vertical-align:top}.table th{background:#f8fafc;color:#475467}.id{font-weight:800;color:#0b5fa5}.title{font-weight:700;color:#243b53}.meta{font-size:12px;color:#667085;margin-top:4px}.badge{display:inline-block;padding:5px 8px;border-radius:20px;background:#eef2f6;font-size:11px;font-weight:800}.published{background:#ecfdf3;color:#176b39}.draft{background:#fff7e6;color:#8a5a00}.ready{background:#eaf2ff;color:#175cd3}.actions{display:flex;gap:6px}.empty{padding:45px;text-align:center;color:#667085}@media(max-width:750px){.bar{flex-direction:column;align-items:stretch}.filters{flex-direction:column}}
</style></head><body>
<header class="top"><div class="top-inner"><div class="brand">AJSMR — Article Management<small>Editor-in-Chief &bull; Publication Catalog</small></div><div style="display:flex;gap:12px;"><a class="btn light" href="dashboard.php">← EIC Dashboard</a><a class="btn light" href="logout.php">Sign Out</a></div></div></header>
<main class="wrap">
<div class="dashboard-layout">
<?php include __DIR__ . '/includes/eic_sidebar.php'; ?>
<div class="right-panel">
<div class="bar">
<form class="filters" method="get">
<input name="q" value="<?=article_h($q)?>" placeholder="Search Article ID, title or DOI">
<select name="status"><option value="">All statuses</option><?php foreach(article_statuses() as $s): ?><option value="<?=article_h($s)?>" <?=($status===$s?'selected':'')?>><?=article_h($s)?></option><?php endforeach; ?></select>
<button class="btn" type="submit">SEARCH</button>
</form>
<a class="btn" href="article_add.php">+ ADD ARTICLE</a>
</div>
<div class="card table-wrap"><table class="table"><thead><tr><th>Article</th><th>Authors</th><th>Publication</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody>
<?php if(!$rows): ?><tr><td colspan="6" class="empty">No articles found.</td></tr><?php endif; ?>
<?php foreach($rows as $r): ?>
<tr>
<td><div class="id"><?=article_h($r['article_id'])?></div><div class="title"><?=article_h($r['title'])?></div><div class="meta"><?=article_h($r['article_type'])?></div></td>
<td><?=number_format((int)$r['author_count'])?></td>
<td><?=article_h(trim(($r['volume']?'Vol. '.$r['volume'].' ':'').($r['issue']?'Issue '.$r['issue'].' ':'').($r['year']??'')))?><br><span class="meta"><?=article_h($r['page_start'] && $r['page_end'] ? $r['page_start'].'–'.$r['page_end'] : '')?></span></td>
<td><span class="badge <?=stripos($r['status'],'PUBLISHED')!==false||$r['status']==='UPDATED'?'published':($r['status']==='READY FOR PUBLICATION'?'ready':'draft')?>"><?=article_h($r['status'])?></span></td>
<td><?=article_h(date('d M Y H:i',strtotime($r['updated_at'])))?></td>
<td><div class="actions"><a class="btn light" href="article_edit.php?id=<?=$r['id']?>">Edit</a><?php if($r['status']==='PUBLISHED'||$r['status']==='UPDATED'): ?><a class="btn light" href="<?=article_h(article_public_url($r['article_id']))?>" target="_blank">View</a><?php endif; ?></div></td>
</tr>
<?php endforeach; ?></tbody></table></div>
</div><!-- /.right-panel -->
</div><!-- /.dashboard-layout -->
</main></body></html>
