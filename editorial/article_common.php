<?php
declare(strict_types=1);

/*
 * AJSMR Dynamic Article Publication Module V1
 * Shared helpers. Requires the existing editorial/config/config.php.
 */

require_once __DIR__ . '/config/config.php';

function article_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function article_eic(): array {
    return role_required(['editor_in_chief']);
}

function article_slug(string $value): string {
    $value = trim($value);
    $value = preg_replace('/[^\pL\pN]+/u', '-', $value) ?? '';
    $value = trim($value, '-');
    return strtolower($value);
}

function article_statuses(): array {
    return ['DRAFT', 'READY FOR PUBLICATION', 'PUBLISHED', 'UPDATED'];
}

function article_file_url(?string $path): string {
    if (!$path) return '';
    return ltrim(str_replace('\\', '/', $path), '/');
}

function article_public_url(string $articleId): string {
    return 'article.php?id=' . rawurlencode($articleId);
}

function article_log(string $action, ?int $articleId, string $details = ''): void {
    try {
        $u = user();
        $s = db()->prepare(
            'INSERT INTO audit_log(user_id, manuscript_id, action, details, ip_address)
             VALUES(?,?,?,?,?)'
        );
        $s->execute([
            $u['id'] ?? null,
            null,
            $action,
            ($articleId !== null ? 'article_id='.$articleId.'; ' : '') . $details,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (Throwable $ignored) {
        // Publication-module audit logging must not break an article save.
    }
}

function article_upload(array $file, string $type, int $articleDbId): ?string {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload failed for '.$type.'.');
    }

    $max = 25 * 1024 * 1024;
    if ((int)$file['size'] > $max) {
        throw new RuntimeException('The '.$type.' file is larger than 25 MB.');
    }

    $allowed = [
        'pdf' => ['application/pdf'],
        'supplementary' => [
            'application/pdf','application/zip','application/x-zip-compressed',
            'application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain'
        ],
        'graphical_abstract' => ['image/jpeg','image/png','image/webp','image/svg+xml','application/pdf'],
        'cover_image' => ['image/jpeg','image/png','image/webp']
    ];

    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    $allowedExt = [
        'pdf' => ['pdf'],
        'supplementary' => ['pdf','zip','doc','docx','xls','xlsx','txt'],
        'graphical_abstract' => ['jpg','jpeg','png','webp','svg','pdf'],
        'cover_image' => ['jpg','jpeg','png','webp']
    ];

    if (!in_array($ext, $allowedExt[$type] ?? [], true)) {
        throw new RuntimeException('Invalid file type for '.$type.'.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
    if (!in_array($mime, $allowed[$type] ?? [], true)) {
        throw new RuntimeException('The uploaded '.$type.' file type is not permitted.');
    }

    $root = __DIR__ . '/uploads/articles';
    if (!is_dir($root) && !mkdir($root, 0750, true)) {
        throw new RuntimeException('Unable to create the article upload directory.');
    }

    $safe = $articleDbId . '_' . $type . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destination = $root . '/' . $safe;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Unable to store the '.$type.' file.');
    }

    $relative = 'uploads/articles/' . $safe;

    $s = db()->prepare(
        'INSERT INTO article_files
         (article_id,file_type,original_name,stored_path,mime_type,file_size)
         VALUES (?,?,?,?,?,?)'
    );
    $s->execute([
        $articleDbId,
        $type,
        (string)$file['name'],
        $relative,
        $mime,
        (int)$file['size']
    ]);

    return $relative;
}

function article_parse_references(string $text): array {
    $lines = preg_split('/\R+/', trim($text)) ?: [];
    $out = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $line = preg_replace('/^\s*\[?\d+\]?[.)]?\s*/', '', $line) ?? $line;
        $out[] = $line;
    }
    return $out;
}
