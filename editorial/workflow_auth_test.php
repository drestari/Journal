<?php

require_once __DIR__ . '/config/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

echo '<h1>AJSMR Workflow Authentication Test</h1>';

echo '<h2>1. Session information</h2>';

echo '<pre>';
print_r($_SESSION);
echo '</pre>';

echo '<h2>2. Database user information</h2>';

try {

    $db = db();

    echo '<p>Database connection: <strong>OK</strong></p>';

    $rows = $db->query("
        SELECT id, email, role, active
        FROM users
        ORDER BY id
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo '<pre>';
    print_r($rows);
    echo '</pre>';

    echo '<h2>3. Current logged-in user</h2>';

    if (empty($_SESSION['user']['id'])) {

        echo '<p style="color:red;font-weight:bold">
            NO SESSION USER FOUND
        </p>';

    } else {

        $uid = (int)$_SESSION['user']['id'];

        $stmt = $db->prepare("
            SELECT id, email, role, active
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$uid]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        echo '<pre>';
        print_r($user);
        echo '</pre>';

        if (!$user) {

            echo '<p style="color:red;font-weight:bold">
                SESSION USER ID DOES NOT EXIST IN DATABASE
            </p>';

        } elseif ((int)$user['active'] !== 1) {

            echo '<p style="color:red;font-weight:bold">
                USER ACCOUNT IS INACTIVE
            </p>';

        } elseif (
            in_array(
                $user['role'],
                ['admin', 'editor_in_chief', 'editor'],
                true
            )
        ) {

            echo '<p style="color:green;font-weight:bold">
                WORKFLOW AUTHORIZATION: PASSED
            </p>';

        } else {

            echo '<p style="color:red;font-weight:bold">
                WORKFLOW AUTHORIZATION: FAILED
            </p>';

            echo '<p>
                Current database role:
                <strong>' .
                htmlspecialchars($user['role']) .
                '</strong>
            </p>';
        }
    }

} catch (Throwable $e) {

    echo '<h2 style="color:red">ERROR</h2>';

    echo '<pre>';
    echo htmlspecialchars($e->getMessage());
    echo '</pre>';
}