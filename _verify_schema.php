<?php
$l = mysqli_connect('127.0.0.1','root','','ajsmr_editorial',3307);
$r = mysqli_query($l, 'SHOW COLUMNS FROM manuscript_authors');
echo 'manuscript_authors: ';
while ($c = mysqli_fetch_row($r)) echo $c[0].'|';
echo "\n";
$r = mysqli_query($l, 'SHOW COLUMNS FROM manuscript_versions');
echo 'manuscript_versions: ';
while ($c = mysqli_fetch_row($r)) echo $c[0].'|';
echo "\n";
$r = mysqli_query($l, "SHOW COLUMNS FROM manuscripts LIKE 'abstract%'");
echo 'manuscripts abstract cols: ';
while ($c = mysqli_fetch_row($r)) echo $c[0].'|';
echo "\n";
$r = mysqli_query($l, 'SHOW COLUMNS FROM production');
echo 'production: ';
while ($c = mysqli_fetch_row($r)) echo $c[0].'|';
echo "\n";
