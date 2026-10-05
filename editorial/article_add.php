<?php
declare(strict_types=1);

require_once __DIR__ . '/article_common.php';
$me = article_eic();

$error = '';
$success = '';

$defaults = [
    'article_id' => 'AJSMR-' . date('Y') . '-' . random_int(10000, 99999),
    'article_type' => 'Research Article',
    'title' => '',
    'running_title' => '',
    'abstract' => '',
    'keywords' => '',
    'volume' => '',
    'issue' => '',
    'year' => date('Y'),
    'received_date' => '',
    'revised_date' => '',
    'accepted_date' => '',
    'published_date' => date('Y-m-d'),
    'page_start' => '',
    'page_end' => '',
    'doi' => '',
    'section' => 'Articles',
    'license' => 'CC BY 4.0',
    'rights_statement' => 'Copyright is retained by the authors. This article is distributed under the Creative Commons Attribution 4.0 International License (CC BY 4.0).',
    'status' => 'DRAFT'
];

$authors = [['author_name'=>'','affiliation'=>'','email'=>'','orcid'=>'','is_corresponding'=>1]];
$references = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        check_csrf();

        foreach ($defaults as $key => $value) {
            $defaults[$key] = trim((string)($_POST[$key] ?? $value));
        }

        $authorNames = $_POST['author_name'] ?? [];
        $affiliations = $_POST['affiliation'] ?? [];
        $emails = $_POST['author_email'] ?? [];
        $orcids = $_POST['orcid'] ?? [];
        $corresponding = $_POST['is_corresponding'] ?? [];

        $authors = [];
        foreach ((array)$authorNames as $i => $name) {
            $name = trim((string)$name);
            if ($name === '') continue;
            $authors[] = [
                'author_name' => $name,
                'affiliation' => trim((string)($affiliations[$i] ?? '')),
                'email' => trim((string)($emails[$i] ?? '')),
                'orcid' => trim((string)($orcids[$i] ?? '')),
                'is_corresponding' => isset($corresponding[$i]) ? 1 : 0
            ];
        }

        if (!$defaults['article_id'] || !$defaults['title'] || !$defaults['abstract'] || !$authors) {
            throw new RuntimeException('Article ID, title, abstract and at least one author are required.');
        }

        if (!in_array($defaults['status'], article_statuses(), true)) {
            $defaults['status'] = 'DRAFT';
        }

        $pdo = db();
        $pdo->beginTransaction();

        $check = $pdo->prepare('SELECT id FROM articles WHERE article_id=? LIMIT 1');
        $check->execute([$defaults['article_id']]);
        if ($check->fetch()) {
            throw new RuntimeException('This Article ID already exists.');
        }

        if ($defaults['doi'] !== '') {
            $check = $pdo->prepare('SELECT id FROM articles WHERE doi=? LIMIT 1');
            $check->execute([$defaults['doi']]);
            if ($check->fetch()) throw new RuntimeException('This DOI already exists.');
        }

        $s = $pdo->prepare(
            'INSERT INTO articles
            (article_id,article_type,title,running_title,abstract,keywords,volume,issue,year,
             received_date,revised_date,accepted_date,published_date,page_start,page_end,doi,section,
             license,rights_statement,status,created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );

        $s->execute([
            $defaults['article_id'], $defaults['article_type'], $defaults['title'], $defaults['running_title'],
            $defaults['abstract'], $defaults['keywords'], $defaults['volume'], $defaults['issue'],
            $defaults['year'] !== '' ? (int)$defaults['year'] : null,
            $defaults['received_date'] ?: null, $defaults['revised_date'] ?: null,
            $defaults['accepted_date'] ?: null, $defaults['published_date'] ?: null,
            $defaults['page_start'], $defaults['page_end'], $defaults['doi'] ?: null, $defaults['section'],
            $defaults['license'], $defaults['rights_statement'], $defaults['status'], (int)$me['id']
        ]);

        $articleDbId = (int)$pdo->lastInsertId();

        $as = $pdo->prepare(
            'INSERT INTO article_authors
             (article_id,author_name,affiliation,email,orcid,author_order,is_corresponding)
             VALUES (?,?,?,?,?,?,?)'
        );
        foreach ($authors as $i => $a) {
            $as->execute([
                $articleDbId, $a['author_name'], $a['affiliation'], $a['email'] ?: null,
                $a['orcid'] ?: null, $i + 1, $a['is_corresponding']
            ]);
        }

        $references = trim((string)($_POST['references'] ?? ''));
        $refs = article_parse_references($references);
        if ($refs) {
            $rs = $pdo->prepare(
                'INSERT INTO article_references(article_id,reference_text,reference_order) VALUES(?,?,?)'
            );
            foreach ($refs as $i => $ref) $rs->execute([$articleDbId, $ref, $i + 1]);
        }

        $pdo->commit();

        foreach (['pdf'=>'pdf_file','supplementary'=>'supplementary_file',
                  'graphical_abstract'=>'graphical_abstract','cover_image'=>'cover_image'] as $type=>$field) {
            if (!empty($_FILES[$field]['name'])) {
                $path = article_upload($_FILES[$field], $type, $articleDbId);
                if ($path) {
                    $column = ($type === 'pdf') ? 'pdf_file' : $type;
                    $up = $pdo->prepare("UPDATE articles SET {$column}=? WHERE id=?");
                    $up->execute([$path, $articleDbId]);
                }
            }
        }

        article_log('ARTICLE_CREATED', $articleDbId, 'Article '.$defaults['article_id']);
        $success = 'Article created successfully. Article ID: '.$defaults['article_id'];
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
        $error = $e instanceof PDOException ? 'Unable to save the article. Please verify the database structure.' : $e->getMessage();
    }
}

$csrfToken = csrf();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>AJSMR | Add Published Article</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f4f7fb;color:#25344a;font-family:Arial,Helvetica,sans-serif}
.top{background:linear-gradient(135deg,#092b5f,#0b5fa5);color:#fff;padding:22px 30px}.top-inner{max-width:1180px;margin:auto;display:flex;justify-content:space-between;align-items:center}.brand{font-size:20px;font-weight:800}.brand small{display:block;font-size:12px;font-weight:400;margin-top:4px;opacity:.85}.back{color:#fff;text-decoration:none}
.wrap{max-width:1350px;margin:24px auto;padding:0 18px}.card{background:#fff;border:1px solid #e4eaf2;border-radius:12px;box-shadow:0 8px 28px #10204012;margin-bottom:20px}.card h2{margin:0;padding:18px 22px;border-bottom:1px solid #e8edf3;font-size:18px}.body{padding:22px}.grid{display:grid;grid-template-columns:repeat(2,1fr);gap:17px}.full{grid-column:1/-1}.field label{display:block;font-size:12px;font-weight:700;color:#475467;margin-bottom:7px}.field input,.field select,.field textarea{width:100%;border:1px solid #cfd8e3;border-radius:7px;padding:11px 12px;font:inherit;background:#fff}.field textarea{min-height:140px;resize:vertical}.field small{display:block;color:#7a8797;margin-top:5px}.author{border:1px solid #e2e8f0;border-radius:9px;padding:16px;margin-bottom:12px;position:relative}.author-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px}.author .wide{grid-column:1/-1}.author-remove{position:absolute;right:12px;top:10px;border:0;background:#fff;color:#b42318;cursor:pointer}.check{display:flex;gap:7px;align-items:center;font-size:13px}.actions{display:flex;justify-content:space-between;align-items:center;gap:10px}.btn{border:0;border-radius:7px;padding:11px 18px;background:#0b5fa5;color:#fff;font-weight:800;cursor:pointer}.btn.secondary{background:#eaf2f9;color:#0b5fa5}.alert{padding:13px 16px;border-radius:8px;margin-bottom:18px}.error{background:#fff1f0;color:#b42318}.success{background:#ecfdf3;color:#176b39}.required{color:#b42318}@media(max-width:800px){.grid,.author-grid{grid-template-columns:1fr}.full,.author .wide{grid-column:auto}.actions{flex-direction:column;align-items:stretch}}
</style>
</head>
<body>
<header class="top"><div class="top-inner"><div class="brand">AJSMR — Article Publication<span><small>Editor-in-Chief: Dynamic Article Entry</small></span></div><div style="display:flex;gap:12px;"><a class="btn secondary" href="article_manage.php">← Manage Articles</a><a class="btn secondary" href="dashboard.php">EIC Dashboard</a></div></div></header>
<main class="wrap">
<div class="dashboard-layout">
<?php include __DIR__ . '/includes/eic_sidebar.php'; ?>
<div class="right-panel">
<?php if($error): ?><div class="alert error"><?=article_h($error)?></div><?php endif; ?>
<?php if($success): ?><div class="alert success"><?=article_h($success)?> <a href="article_manage.php">Manage Articles →</a></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?=article_h($csrfToken)?>">

<section class="card"><h2>1. Basic Article Information</h2><div class="body"><div class="grid">
<div class="field"><label>Article ID <span class="required">*</span></label><input name="article_id" value="<?=article_h($defaults['article_id'])?>" required></div>
<div class="field"><label>Article Type <span class="required">*</span></label><select name="article_type">
<?php foreach(['Research Article','Review Article','Short Communication','Case Report','Editorial','Letter to the Editor','Method Article','Book Review'] as $t): ?><option <?=($defaults['article_type']===$t?'selected':'')?>><?=article_h($t)?></option><?php endforeach; ?>
</select></div>
<div class="field full"><label>Article Title <span class="required">*</span></label><input name="title" value="<?=article_h($defaults['title'])?>" required></div>
<div class="field full"><label>Running Title</label><input name="running_title" value="<?=article_h($defaults['running_title'])?>"></div>
<div class="field full"><label>Abstract <span class="required">*</span></label><textarea name="abstract" required><?=article_h($defaults['abstract'])?></textarea></div>
<div class="field full"><label>Keywords</label><input name="keywords" value="<?=article_h($defaults['keywords'])?>" placeholder="Separate keywords with semicolons"></div>
</div></div></section>

<section class="card"><h2>2. Authors and Affiliations</h2><div class="body">
<div id="authors">
<?php foreach($authors as $i=>$a): ?><div class="author">
<?php if($i>0): ?><button class="author-remove" type="button" onclick="this.parentElement.remove()">Remove</button><?php endif; ?>
<div class="author-grid">
<div class="field"><label>Author Name <span class="required">*</span></label><input name="author_name[]" value="<?=article_h($a['author_name'])?>" required></div>
<div class="field"><label>Email</label><input type="email" name="author_email[]" value="<?=article_h($a['email'])?>"></div>
<div class="field wide"><label>Affiliation</label><input name="affiliation[]" value="<?=article_h($a['affiliation'])?>"></div>
<div class="field"><label>ORCID iD</label><input name="orcid[]" value="<?=article_h($a['orcid'])?>" placeholder="0000-0000-0000-0000"></div>
<label class="check"><input type="checkbox" name="is_corresponding[<?=$i?>]" value="1" <?=((int)$a['is_corresponding']===1?'checked':'')?>> Corresponding author</label>
</div></div><?php endforeach; ?>
</div>
<button class="btn secondary" type="button" onclick="addAuthor()">+ Add Another Author</button>
</div></section>

<section class="card"><h2>3. Publication Information</h2><div class="body"><div class="grid">
<div class="field"><label>Volume</label><input name="volume" value="<?=article_h($defaults['volume'])?>"></div>
<div class="field"><label>Issue</label><input name="issue" value="<?=article_h($defaults['issue'])?>"></div>
<div class="field"><label>Year</label><input type="number" name="year" value="<?=article_h($defaults['year'])?>"></div>
<div class="field"><label>Section</label><input name="section" value="<?=article_h($defaults['section'])?>"></div>
<div class="field"><label>Received Date</label><input type="date" name="received_date" value="<?=article_h($defaults['received_date'])?>"></div>
<div class="field"><label>Revised Date</label><input type="date" name="revised_date" value="<?=article_h($defaults['revised_date'])?>"></div>
<div class="field"><label>Accepted Date</label><input type="date" name="accepted_date" value="<?=article_h($defaults['accepted_date'])?>"></div>
<div class="field"><label>Published Date</label><input type="date" name="published_date" value="<?=article_h($defaults['published_date'])?>"></div>
<div class="field"><label>Page Start</label><input name="page_start" value="<?=article_h($defaults['page_start'])?>"></div>
<div class="field"><label>Page End</label><input name="page_end" value="<?=article_h($defaults['page_end'])?>"></div>
<div class="field full"><label>DOI</label><input name="doi" value="<?=article_h($defaults['doi'])?>" placeholder="10.xxxx/xxxxx"></div>
<div class="field"><label>License</label><input name="license" value="<?=article_h($defaults['license'])?>"></div>
<div class="field"><label>Status</label><select name="status"><?php foreach(article_statuses() as $s): ?><option <?=($defaults['status']===$s?'selected':'')?>><?=article_h($s)?></option><?php endforeach; ?></select></div>
<div class="field full"><label>Rights / Copyright Statement</label><textarea name="rights_statement"><?=article_h($defaults['rights_statement'])?></textarea></div>
</div></div></section>

<section class="card"><h2>4. Article Files</h2><div class="body"><div class="grid">
<div class="field"><label>Main Article PDF</label><input type="file" name="pdf_file" accept=".pdf,application/pdf"><small>PDF only; maximum 25 MB.</small></div>
<div class="field"><label>Supplementary File</label><input type="file" name="supplementary_file"><small>PDF, DOC/DOCX, XLS/XLSX, ZIP or TXT; maximum 25 MB.</small></div>
<div class="field"><label>Graphical Abstract</label><input type="file" name="graphical_abstract" accept=".jpg,.jpeg,.png,.webp,.svg,.pdf"></div>
<div class="field"><label>Cover Image</label><input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp"></div>
</div></div></section>

<section class="card"><h2>5. References</h2><div class="body"><div class="field"><label>References</label><textarea name="references" style="min-height:220px" placeholder="Enter one reference per line. Numbering is added automatically."><?=article_h($references)?></textarea></div></div></section>

<section class="card"><div class="body actions"><a class="back" href="article_manage.php">Cancel</a><button class="btn" type="submit">SAVE ARTICLE</button></div></section>
</form>
</div><!-- /.right-panel -->
</div><!-- /.dashboard-layout -->
</main>
<script>
function addAuthor(){
  const box=document.getElementById('authors');
  const i=box.querySelectorAll('.author').length;
  const div=document.createElement('div');
  div.className='author';
  div.innerHTML=`<button class="author-remove" type="button" onclick="this.parentElement.remove()">Remove</button>
  <div class="author-grid">
    <div class="field"><label>Author Name <span class="required">*</span></label><input name="author_name[]" required></div>
    <div class="field"><label>Email</label><input type="email" name="author_email[]"></div>
    <div class="field wide"><label>Affiliation</label><input name="affiliation[]"></div>
    <div class="field"><label>ORCID iD</label><input name="orcid[]" placeholder="0000-0000-0000-0000"></div>
    <label class="check"><input type="checkbox" name="is_corresponding[${i}]" value="1"> Corresponding author</label>
  </div>`;
  box.appendChild(div);
}
</script>
</body></html>
