<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

$host = getenv('MAIN_DB_HOST') ?: '127.0.0.1';
$user = getenv('MAIN_DB_USER') ?: 'root';
$password = getenv('MAIN_DB_PASS') ?: '';
$database = getenv('MAIN_DB_NAME') ?: 'ajsmrjournal';

echo '<h1>AJSMR HOMEPAGE QUERY DIAGNOSTIC</h1>';

mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die(
        '<h2 style="color:red;">DATABASE CONNECTION FAILED</h2>' .
        '<p>' . htmlspecialchars(mysqli_connect_error()) . '</p>'
    );
}

mysqli_set_charset($conn, 'utf8mb4');

echo '<h2 style="color:green;">DATABASE CONNECTION SUCCESSFUL</h2>';

/* =========================================================
   TEST 1: contentpages
   ========================================================= */

echo '<hr>';
echo '<h2>TEST 1: contentpages</h2>';

$sql1 = "SELECT * 
         FROM contentpages 
         WHERE TRIM(title) = 'Welcome to AJSMR'
         LIMIT 1";

echo '<p><strong>SQL:</strong> ' .
     htmlspecialchars($sql1) . '</p>';

$result1 = mysqli_query($conn, $sql1);

if (!$result1) {

    echo '<p style="color:red;"><strong>QUERY FAILED</strong></p>';
    echo '<p>MySQL error: ' .
         htmlspecialchars(mysqli_error($conn)) . '</p>';

} else {

    $count1 = mysqli_num_rows($result1);

    echo '<p style="color:green;"><strong>QUERY SUCCESSFUL</strong></p>';
    echo '<p>Rows returned: <strong>' . $count1 . '</strong></p>';

    if ($count1 > 0) {

        $row1 = mysqli_fetch_assoc($result1);

        echo '<h3>Returned record:</h3>';
        echo '<pre>';
        print_r($row1);
        echo '</pre>';

    } else {

        echo '<p style="color:orange;">
              No record found with title:
              <strong>Welcome to AJSMR</strong>
              </p>';
    }
}


/* =========================================================
   TEST 2: ajsmr_issuecontent
   ========================================================= */

echo '<hr>';
echo '<h2>TEST 2: ajsmr_issuecontent</h2>';

$sql2 = "SELECT *
         FROM ajsmr_issuecontent
         WHERE status = '1'
         ORDER BY contentid DESC
         LIMIT 7";

echo '<p><strong>SQL:</strong> ' .
     htmlspecialchars($sql2) . '</p>';

$result2 = mysqli_query($conn, $sql2);

if (!$result2) {

    echo '<p style="color:red;"><strong>QUERY FAILED</strong></p>';
    echo '<p>MySQL error: ' .
         htmlspecialchars(mysqli_error($conn)) . '</p>';

} else {

    $count2 = mysqli_num_rows($result2);

    echo '<p style="color:green;"><strong>QUERY SUCCESSFUL</strong></p>';
    echo '<p>Rows returned: <strong>' . $count2 . '</strong></p>';

    if ($count2 > 0) {

        echo '<h3>Records:</h3>';

        echo '<table border="1" cellpadding="8" cellspacing="0">';
        echo '<tr>';

        $fields = mysqli_fetch_fields($result2);

        foreach ($fields as $field) {
            echo '<th>' .
                 htmlspecialchars($field->name) .
                 '</th>';
        }

        echo '</tr>';

        while ($row2 = mysqli_fetch_assoc($result2)) {

            echo '<tr>';

            foreach ($row2 as $value) {

                echo '<td>' .
                     htmlspecialchars((string)$value) .
                     '</td>';
            }

            echo '</tr>';
        }

        echo '</table>';

    } else {

        echo '<p style="color:orange;">
              No records found where status = 1.
              </p>';
    }
}


/* =========================================================
   TEST 3: show contentpages columns
   ========================================================= */

echo '<hr>';
echo '<h2>TEST 3: contentpages structure</h2>';

$result3 = mysqli_query($conn, "DESCRIBE contentpages");

if ($result3) {

    echo '<table border="1" cellpadding="8" cellspacing="0">';
    echo '<tr>
            <th>Field</th>
            <th>Type</th>
            <th>Null</th>
            <th>Key</th>
            <th>Default</th>
          </tr>';

    while ($row3 = mysqli_fetch_assoc($result3)) {

        echo '<tr>';

        foreach ($row3 as $value) {
            echo '<td>' .
                 htmlspecialchars((string)$value) .
                 '</td>';
        }

        echo '</tr>';
    }

    echo '</table>';
}


/* =========================================================
   TEST 4: ajsmr_issuecontent structure
   ========================================================= */

echo '<hr>';
echo '<h2>TEST 4: ajsmr_issuecontent structure</h2>';

$result4 = mysqli_query($conn, "DESCRIBE ajsmr_issuecontent");

if ($result4) {

    echo '<table border="1" cellpadding="8" cellspacing="0">';
    echo '<tr>
            <th>Field</th>
            <th>Type</th>
            <th>Null</th>
            <th>Key</th>
            <th>Default</th>
          </tr>';

    while ($row4 = mysqli_fetch_assoc($result4)) {

        echo '<tr>';

        foreach ($row4 as $value) {
            echo '<td>' .
                 htmlspecialchars((string)$value) .
                 '</td>';
        }

        echo '</tr>';
    }

    echo '</table>';
}

mysqli_close($conn);

echo '<hr>';
echo '<h2>Diagnostic completed.</h2>';

?>