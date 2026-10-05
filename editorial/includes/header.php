<?php
$u = user();
if (in_array(($u['role'] ?? ''), ['editor_in_chief', 'admin'], true)) {
    include __DIR__ . '/eic_header.php';
    return;
}
// Route authors to the premium modern portal layout
if (($u['role'] ?? '') === 'author') {
    include __DIR__ . '/author_header.php';
    return;
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($pageTitle??'AJSMR Editorial System')?></title><link rel="stylesheet" href="<?=BASE_URL?>assets/css/style.css"></head><body>
<header><div><b>AJSMR</b> Editorial Management System V1</div><nav><a href="<?=BASE_URL?>dashboard.php">Dashboard</a><a href="<?=BASE_URL?>logout.php">Logout</a></nav></header>
<div class="layout"><aside><div class="user"><b><?=e($u['full_name'] ?? $u['name'] ?? '')?></b><small><?=e(slabel($u['role']??''))?></small></div>
<a href="<?=BASE_URL?>dashboard.php">Dashboard</a>
<?php if($u['role']==='admin'):?><a href="<?=BASE_URL?>admin/users.php">Users</a><a href="<?=BASE_URL?>admin/manuscripts.php">Manuscripts</a><a href="<?=BASE_URL?>admin/issues.php">Issues</a><a href="<?=BASE_URL?>admin/reports.php">Reports</a><?php endif;?>
<?php if(in_array($u['role'],['editor','editor_in_chief'],true)):?><a href="<?=BASE_URL?>editor/manuscripts.php">Manuscripts</a><a href="<?=BASE_URL?>editor/reviewers.php">Reviewers</a><?php endif;?>
<?php if(in_array($u['role'],['editor_in_chief','admin'],true)):?>
<a href="<?=BASE_URL?>issue_years.php">Issue Years &amp; Issues</a>
<a href="<?=BASE_URL?>current_issue_list.php">Current Issue List</a>
<a href="<?=BASE_URL?>current_issue_add.php">Add Current Issue</a>
<?php endif;?>
<?php if($u['role']==='reviewer'):?><a href="<?=BASE_URL?>reviewer/reviews.php">My Reviews</a><?php endif;?>
<?php if($u['role']==='production'):?><a href="<?=BASE_URL?>production/accepted.php">Accepted/Production</a><a href="<?=BASE_URL?>production/issues.php">Issues</a><?php endif;?>
</aside><main>
