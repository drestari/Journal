<?php
declare(strict_types=1);

/*
 * AJSMR Editorial Management — LOCAL XAMPP configuration.
 * This file is for E:\ajsmr_testing only.
 *
 * Database:
 *   ajsmr_editorial
 * User:
 *   root
 * Password:
 *   blank
 *
 * Do NOT use these credentials on the production server.
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
date_default_timezone_set('Asia/Kolkata');

const DB_HOST = 'sdb-63.hosting.stackcp.net';
const DB_NAME = 'ajsmr_editorial-353033357d46';
const DB_USER = 'ajsmr_editorial-353033357d46';
const DB_PASS = 'ulUeW5%sGy7v';

$basePath = (isset($_SERVER['REQUEST_URI']) && str_starts_with($_SERVER['REQUEST_URI'], '/ajsmr_testing'))
    ? '/ajsmr_testing/editorial/'
    : '/editorial/';
define('BASE_URL', $basePath);
const MAX_UPLOAD_BYTES = 26214400;
const ALLOWED_MANUSCRIPT_EXT = ['pdf','doc','docx'];

function db(): PDO {
    static $p = null;
    if ($p instanceof PDO) return $p;

    $host = DB_HOST;
    $name = DB_NAME;
    $user = DB_USER;
    $pass = DB_PASS;

    try {
        $p = new PDO(
            "mysql:host={$host};dbname={$name};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $p;
    } catch (PDOException $e) {
        try {
            $p = new PDO(
                "mysql:host=sdb-63.hosting.stackcp.net;dbname=ajsmr_editorial-353033357d46;charset=utf8mb4",
                "ajsmr_editorial-353033357d46",
                "Qi\$KwgV=>hc~",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            return $p;
        } catch (PDOException $ex1) {
            try {
                $p = new PDO(
                    "mysql:host=127.0.0.1;port=3306;dbname=ajsmr_editorial;charset=utf8mb4",
                    "root",
                    "Srija@2005",
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
                return $p;
            } catch (PDOException $ex2) {
                $p = new PDO(
                    "mysql:host=localhost;dbname=ajsmr_editorial;charset=utf8mb4",
                    "root",
                    "Srija@2005",
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
                return $p;
            }
        }
    }
}

function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect($p): never {
    header('Location: ' . BASE_URL . ltrim($p, '/'));
    exit;
}

function csrf(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function check_csrf(): void {
    if (!hash_equals((string)($_SESSION['csrf'] ?? ''), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('Invalid request token.');
    }
}

function user(): ?array {
    return $_SESSION['user'] ?? null;
}

function login_required(): array {
    $u = user();
    if (!$u) redirect('login.php');
    return $u;
}

function role_required($roles): array {
    $u = login_required();
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        exit('Access denied.');
    }
    return $u;
}

function audit($action, $mid=null, $details=null): void {
    $u = user();
    $s = db()->prepare(
        'INSERT INTO audit_log(user_id,manuscript_id,action,details,ip_address)
         VALUES(?,?,?,?,?)'
    );
    $s->execute([
        $u['id'] ?? null,
        $mid,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);
}

function slabel($s): string {
    return ucwords(strtolower(str_replace('_',' ',(string)$s)));
}

function ajsmr_provision_eic_account(PDO $db, string $fullName, string $email, string $password): array {
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'editor_in_chief'");
    $count = (int)$stmt->fetchColumn();
    if ($count >= 1) {
        return ['success' => false, 'message' => 'An Editor-in-Chief account already exists.'];
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmtIns = $db->prepare("INSERT INTO users (full_name, email, password_hash, role, active) VALUES (?, ?, ?, 'editor_in_chief', 1)");
    $stmtIns->execute([$fullName, $email, $hash]);
    return ['success' => true, 'message' => 'Editor-in-Chief account created successfully.'];
}

require_once __DIR__ . '/../services/notification_service.php';

