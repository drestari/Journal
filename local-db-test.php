<?php

mysqli_report(MYSQLI_REPORT_OFF);

$host = "localhost";
$user = "root";
$password = "";
$database = "ajsmrjournal";

$link = mysqli_connect($host, $user, $password, $database);

echo "<h2>AJSMR Local Database Test</h2>";

if (!$link) {
    echo "<p style='color:red;'><strong>Connection FAILED</strong></p>";
    echo "<p>MySQL error: " . htmlspecialchars(mysqli_connect_error()) . "</p>";
    exit;
}

echo "<p style='color:green;'><strong>Connection SUCCESSFUL</strong></p>";

echo "<p>Database: " . htmlspecialchars($database) . "</p>";

$result = mysqli_query($link, "SHOW TABLES");

if (!$result) {
    echo "<p style='color:red;'>Could not read tables.</p>";
    echo "<p>" . htmlspecialchars(mysqli_error($link)) . "</p>";
    exit;
}

echo "<h3>Tables found:</h3>";
echo "<ol>";

while ($row = mysqli_fetch_array($result)) {
    echo "<li>" . htmlspecialchars($row[0]) . "</li>";
}

echo "</ol>";

mysqli_close($link);
?>