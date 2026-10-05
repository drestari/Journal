<?php require_once __DIR__.'/../config/config.php';$u=role_required(['admin']);$pageTitle='Administrator Dashboard';$pdo=db();$c=[];$c[]=['Users',(int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()];
$c[]=['Manuscripts',(int)$pdo->query("SELECT COUNT(*) FROM manuscripts")->fetchColumn()];
$c[]=['Under Review',(int)$pdo->query("SELECT COUNT(*) FROM manuscripts WHERE status IN ('UNDER_REVIEW','SECOND_REVIEW')")->fetchColumn()];
$c[]=['Published',(int)$pdo->query("SELECT COUNT(*) FROM manuscripts WHERE status='PUBLISHED'")->fetchColumn()];include __DIR__.'/../includes/header.php';?>
<h1 class="title">Administrator Dashboard</h1><div class="cards"><?php foreach($c as $x):?><div class="card"><div class="muted"><?=e($x[0])?></div><div class="n"><?=$x[1]?></div></div><?php endforeach;?></div>
<div class="panel"><h3>Editorial Workflow</h3><p>SUBMITTED → TECHNICAL CHECK → PLAGIARISM CHECK → EDITORIAL SCREENING → PEER REVIEW → REVISION → ACCEPTED → PRODUCTION → PROOF → PUBLISHED</p></div>
<?php include __DIR__.'/../includes/footer.php';?>
