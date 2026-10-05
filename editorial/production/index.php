<?php require_once __DIR__.'/../config/config.php';$u=role_required(['production']);$pageTitle='Production Dashboard';$pdo=db();$c=[];$c[]=['Accepted',(int)$pdo->query("SELECT COUNT(*) FROM manuscripts WHERE status IN ('ACCEPTED','PRODUCTION','PROOF_SENT','PROOF_APPROVED')")->fetchColumn()];
$c[]=['Production',(int)$pdo->query("SELECT COUNT(*) FROM manuscripts WHERE status='PRODUCTION'")->fetchColumn()];
$c[]=['Proofs',(int)$pdo->query("SELECT COUNT(*) FROM manuscripts WHERE status='PROOF_SENT'")->fetchColumn()];
$c[]=['Published',(int)$pdo->query("SELECT COUNT(*) FROM manuscripts WHERE status='PUBLISHED'")->fetchColumn()];include __DIR__.'/../includes/header.php';?>
<h1 class="title">Production Dashboard</h1><div class="cards"><?php foreach($c as $x):?><div class="card"><div class="muted"><?=e($x[0])?></div><div class="n"><?=$x[1]?></div></div><?php endforeach;?></div>
<div class="panel"><h3>Editorial Workflow</h3><p>SUBMITTED → TECHNICAL CHECK → PLAGIARISM CHECK → EDITORIAL SCREENING → PEER REVIEW → REVISION → ACCEPTED → PRODUCTION → PROOF → PUBLISHED</p></div>
<?php include __DIR__.'/../includes/footer.php';?>
