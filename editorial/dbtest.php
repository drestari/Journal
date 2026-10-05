<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';

try {
    $pdo = db();

    echo "<h2>AJSMR Database Connection Successful</h2>";

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<p>Database: " . htmlspecialchars(DB_NAME) . "</p>";
    echo "<h3>Tables found:</h3>";

    echo "<ul>";

    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }

    echo "</ul>";

} catch (Throwable $e) {

    echo "<h2>Database Connection Failed</h2>";

    echo "<pre>";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
}
?>