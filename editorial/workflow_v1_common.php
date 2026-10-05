<?php
require_once __DIR__ . '/config/config.php';

function wf_user(array $roles = []) {
    return role_required($roles);
}
function wf_require_roles($db, array $roles = []) {
    return role_required($roles);
}
function wf_csrf() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['wf_csrf'])) $_SESSION['wf_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['wf_csrf'];
}
function wf_check_csrf() {
    if (empty($_POST['csrf']) || empty($_SESSION['wf_csrf']) || !hash_equals($_SESSION['wf_csrf'], $_POST['csrf'])) {
        http_response_code(403); exit('Invalid CSRF token.');
    }
}
function wf_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function wf_ms(PDO $db, int $id) {
    $s=$db->prepare("SELECT * FROM manuscripts WHERE id=?"); $s->execute([$id]);
    return $s->fetch(PDO::FETCH_ASSOC);
}
function wf_log(PDO $db, ?int $mid, ?int $uid, string $action, string $details='') {
    $s=$db->prepare("INSERT INTO ew_audit_log(manuscript_id,user_id,action,details,ip_address) VALUES(?,?,?,?,?)");
    $s->execute([$mid,$uid,$action,$details,$_SERVER['REMOTE_ADDR'] ?? null]);
}
function wf_redirect($url) { header('Location: '.$url); exit; }
function wf_flash($msg) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION['wf_flash']=$msg;
}
function wf_flash_show() {
    if (!empty($_SESSION['wf_flash'])) { echo '<div style="padding:10px;margin:10px 0;border:1px solid #ccc;background:#f7f7f7">'.wf_h($_SESSION['wf_flash']).'</div>'; unset($_SESSION['wf_flash']); }
}
function wf_status_update(PDO $db, int $mid, string $status, ?string $timestampColumn = null) {
    $allowed = ['submitted','technical_check','editor_assigned','review','under_review','minor_revision','major_revision','accepted','rejected',
                'SUBMITTED','TECHNICAL_CHECK','ASSIGNED_TO_EDITOR','UNDER_REVIEW','REVISION_REQUIRED','ACCEPTED','REJECTED'];
    if (!in_array($status, $allowed, true)) throw new RuntimeException('Invalid status: ' . $status);

    $map = [
        'submitted' => 'SUBMITTED',
        'technical_check' => 'TECHNICAL_CHECK',
        'editor_assigned' => 'ASSIGNED_TO_EDITOR',
        'review' => 'UNDER_REVIEW',
        'under_review' => 'UNDER_REVIEW',
        'minor_revision' => 'REVISION_REQUIRED',
        'major_revision' => 'REVISION_REQUIRED',
        'accepted' => 'ACCEPTED',
        'rejected' => 'REJECTED'
    ];
    $dbStatus = $map[strtolower($status)] ?? strtoupper($status);
    $validTimestamps = ['submitted_at', 'updated_at', 'accepted_at', 'published_at'];

    if ($timestampColumn && in_array($timestampColumn, $validTimestamps, true)) {
        $sql = "UPDATE manuscripts SET status = ?, {$timestampColumn} = NOW(), updated_at = NOW() WHERE id = ?";
        $db->prepare($sql)->execute([$dbStatus, $mid]);
    } else {
        $db->prepare("UPDATE manuscripts SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$dbStatus, $mid]);
    }
}
