<?php
// DB inventory script - dumps all tables and columns for both databases
$dbs = ['ajsmr_editorial', 'ajsmrjournal'];
$links = [];
foreach ($dbs as $db) {
    $l = @mysqli_connect('127.0.0.1','root','', $db, 3307);
    if (!$l) $l = @mysqli_connect('localhost','root','',$db);
    $links[$db] = $l;
}

foreach ($dbs as $db) {
    $l = $links[$db];
    if (!$l) { echo "[$db] CONNECTION FAILED: ".mysqli_connect_error()."\n"; continue; }
    echo "\n=== DATABASE: $db ===\n";
    $tables = mysqli_query($l, "SHOW TABLES");
    while ($t = mysqli_fetch_row($tables)) {
        $tbl = $t[0];
        echo "\nTABLE: $tbl\n";
        $cols = mysqli_query($l, "SHOW COLUMNS FROM `$tbl`");
        while ($c = mysqli_fetch_assoc($cols)) {
            echo "  {$c['Field']} | {$c['Type']} | Null:{$c['Null']} | Key:{$c['Key']} | Default:{$c['Default']}\n";
        }
    }
}
echo "\nDONE\n";
