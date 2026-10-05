<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$host = getenv('MAIN_DB_HOST') ?: '127.0.0.1';
$user = getenv('MAIN_DB_USER') ?: 'root';
$password = getenv('MAIN_DB_PASS') ?: '';
$database = getenv('MAIN_DB_NAME') ?: 'ajsmrjournal';

echo '<h1>AJSMR DATABASE DIAGNOSTIC</h1>';

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die(
        '<h2 style="color:red;">DATABASE CONNECTION FAILED</h2>' .
        '<p>Error: ' . htmlspecialchars(mysqli_connect_error()) . '</p>'
    );
}

echo '<h2 style="color:green;">DATABASE CONNECTION SUCCESSFUL</h2>';

echo '<p><strong>Database:</strong> ' .
     htmlspecialchars($database) . '</p>';

mysqli_set_charset($conn, 'utf8mb4');

$result = mysqli_query($conn, "SHOW TABLES");

if (!$result) {
    die(
        '<h2>SHOW TABLES FAILED</h2>' .
        '<p>' . htmlspecialchars(mysqli_error($conn)) . '</p>'
    );
}

$count = mysqli_num_rows($result);

echo '<h2>Tables found: ' . $count . '</h2>';

if ($count == 0) {
    echo '<p style="color:red;"><strong>NO TABLES FOUND</strong></p>';
} else {
    echo '<ol>';

    while ($row = mysqli_fetch_row($result)) {
        echo '<li><strong>' .
             htmlspecialchars($row[0]) .
             '</strong></li>';
    }

    echo '</ol>';
}

mysqli_close($conn);
?>