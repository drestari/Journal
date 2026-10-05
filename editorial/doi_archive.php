<?php
/**
 * doi_archive.php
 * Shows published manuscripts with DOI and issue information.
 * ew_publication_records, ew_doi_metadata, ew_issue_assignments do NOT exist.
 * All data is in the `production` table.
 */
require_once __DIR__ . '/production_common.php';

$db = db();
$u  = prod_user($db);

// Query published manuscripts using the `production` table (single source of truth)
$sql = "
    SELECT m.id, m.manuscript_no, m.title,
           p.doi, p.volume, p.issue, p.year AS issue_year,
           p.pages, p.publication_status, p.final_pdf
    FROM manuscripts m
    JOIN production p ON p.manuscript_id = m.id
    WHERE p.publication_status IN ('IN_PRESS','PUBLISHED')
    ORDER BY p.year DESC, p.volume DESC, p.issue DESC, m.id DESC
";
$rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

prod_header('DOI / Archive Management', $u);
?>
<div class="panel">
<h1>DOI / Archive Management</h1>
<p>Published manuscripts, DOI records, issue assignment and archive information.</p>

<?php if(!$rows): ?>
<p>No published manuscripts are currently recorded. Use the <a href="production.php">Production Dashboard</a> to publish manuscripts.</p>
<?php else: ?>
<table>
<tr><th>Manuscript</th><th>Article</th><th>Issue</th><th>DOI</th><th>Status</th><th>Actions</th></tr>
<?php foreach($rows as $r): ?>
<tr>
<td><strong><?=prod_h($r['manuscript_no'])?></strong></td>
<td><?=prod_h($r['title'])?></td>
<td><?=prod_h(($r['issue_year']??'').' / Vol. '.($r['volume']??'').' / Issue '.($r['issue']??''))?></td>
<td><?=prod_h($r['doi']?:'Not assigned')?></td>
<td><?=prod_h($r['publication_status']??'')?></td>
<td>
<a class="button" href="crossref_xml.php?id=<?=$r['id']?>">Crossref XML</a>
<a class="button" href="doi_verify.php?id=<?=$r['id']?>">Verify DOI</a>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>
<p><a class="button" href="production.php">← Production Dashboard</a></p>
<?php prod_footer(); ?>
