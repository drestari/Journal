<?php
/**
 * production.php — Production Dashboard
 * Uses only existing tables:
 *   manuscripts, production, ew_galley_proofs, ew_author_proof_approval, ew_audit_log
 * All ew_copyediting, ew_typesetting, ew_doi_metadata, ew_issue_assignments,
 * ew_publication_records, ew_production DO NOT EXIST.
 */
require_once __DIR__.'/production_common.php';
$db  = db();
$u   = prod_user($db);

$rows = $db->query("
    SELECT
        m.id, m.manuscript_no, m.title, m.status AS ms_status,
        p.copyediting_status,
        p.typesetting_status,
        p.proof_status,
        p.publication_status,
        p.doi,
        p.volume, p.issue, p.year, p.pages,
        gp.status          AS galley_status,
        ap.decision        AS author_proof_decision
    FROM manuscripts m
    LEFT JOIN production p ON p.manuscript_id = m.id
    LEFT JOIN ew_galley_proofs gp
           ON gp.manuscript_id = m.id
          AND gp.id = (SELECT MAX(x.id) FROM ew_galley_proofs x WHERE x.manuscript_id = m.id)
    LEFT JOIN ew_author_proof_approval ap
           ON ap.manuscript_id = m.id
          AND ap.id = (SELECT MAX(x.id) FROM ew_author_proof_approval x WHERE x.manuscript_id = m.id)
    WHERE m.status IN ('ACCEPTED','PRODUCTION','PROOF_SENT','PROOF_APPROVED','IN_PRESS','PUBLISHED')
       OR p.manuscript_id IS NOT NULL
    ORDER BY m.submitted_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

prod_header('Production Dashboard', $u);
echo '<div class="panel"><h1>Production Dashboard</h1><p>Accepted manuscripts and their production stages.</p></div>';

foreach ($rows as $r) {
    echo '<div class="panel">'.
         '<h2>'.prod_h($r['manuscript_no']).'</h2>'.
         '<p><strong>'.prod_h($r['title']).'</strong></p>'.
         '<p>Editorial status: <span class="badge">'.prod_h($r['ms_status']).'</span></p>'.
         '<table style="width:auto;margin-bottom:12px">'.
         '<tr><th>Stage</th><th>Status</th></tr>'.
         '<tr><td>Copyediting</td><td>'.prod_h($r['copyediting_status']?:'PENDING').'</td></tr>'.
         '<tr><td>Typesetting</td><td>'.prod_h($r['typesetting_status']?:'PENDING').'</td></tr>'.
         '<tr><td>Galley Proof</td><td>'.prod_h($r['galley_status']?:'not created').'</td></tr>'.
         '<tr><td>Proof Status</td><td>'.prod_h($r['proof_status']?:'PENDING').'</td></tr>'.
         '<tr><td>Author Proof</td><td>'.prod_h($r['author_proof_decision']?:'pending').'</td></tr>'.
         '<tr><td>DOI</td><td>'.prod_h($r['doi']?:'not set').'</td></tr>'.
         '<tr><td>Volume / Issue</td><td>'.prod_h($r['volume']&&$r['issue']?'Vol '.$r['volume'].' No '.$r['issue']:'not assigned').'</td></tr>'.
         '<tr><td>Publication</td><td>'.prod_h($r['publication_status']?:'not published').'</td></tr>'.
         '</table>'.
         '<p>';
    foreach ([
        ['typesetting.php','Typesetting'],
        ['galley_proof.php','Galley Proof'],
        ['author_proof.php','Author Proof Approval'],
        ['doi_metadata.php','DOI / Metadata'],
        ['issue_assignment.php','Issue Assignment'],
        ['publish.php','Publish'],
    ] as $a) {
        echo '<a class="button" href="'.$a[0].'?id='.(int)$r['id'].'">'.$a[1].'</a> ';
    }
    echo '</p></div>';
}

if (!$rows) {
    echo '<div class="panel">No accepted manuscripts are currently available for production.</div>';
}
prod_footer();
