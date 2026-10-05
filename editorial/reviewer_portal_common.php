<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function rp_h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function reviewer_session_user(PDO $db): array {
    $sessionUser = $_SESSION['user'] ?? null;

    if (!is_array($sessionUser) || empty($sessionUser['id'])) {
        http_response_code(403);
        exit('Access denied. Please log in as a reviewer.');
    }

    $uid = (int)$sessionUser['id'];

    $stmt = $db->prepare("SELECT id, email, role, active FROM users WHERE id=? LIMIT 1");
    $stmt->execute([$uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || (int)$user['active'] !== 1 || $user['role'] !== 'reviewer') {
        http_response_code(403);
        exit('Access denied. Please log in as a reviewer.');
    }

    return $user;
}

function rp_csrf(): string {
    if (empty($_SESSION['reviewer_csrf'])) {
        $_SESSION['reviewer_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['reviewer_csrf'];
}

function rp_check_csrf(): void {
    if (empty($_POST['csrf']) || empty($_SESSION['reviewer_csrf']) ||
        !hash_equals($_SESSION['reviewer_csrf'], $_POST['csrf'])) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}
