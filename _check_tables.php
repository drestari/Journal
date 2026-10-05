<?php
$l = @mysqli_connect('127.0.0.1','root','','ajsmr_editorial',3307);
if (!$l) $l = @mysqli_connect('localhost','root','','ajsmr_editorial');
$tables = ['ew_copyediting','ew_typesetting','ew_doi_metadata','ew_issue_assignments','ew_publication_records','ew_production','production'];
foreach ($tables as $t) {
    $r = mysqli_query($l, "SHOW TABLES LIKE '$t'");
    echo $t . ': ' . (mysqli_num_rows($r) > 0 ? 'EXISTS' : 'MISSING') . "\n";
}
// Check production columns
$r = mysqli_query($l, 'SHOW COLUMNS FROM production');
echo 'production columns: ';
while ($c = mysqli_fetch_row($r)) echo $c[0].'|';
echo "\n";
