<?php
declare(strict_types=1);

/*
 * AJSMR Current Issue Management Module
 * Shared helpers, database connectivity, validation, and file security.
 */

require_once __DIR__ . '/config/config.php';

function current_issue_eic(): array {
    return role_required(['editor_in_chief', 'admin']);
}

function journal_db(): PDO {
    static $jdb = null;
    if ($jdb instanceof PDO) return $jdb;

    $host = getenv('MAIN_DB_HOST') ?: '127.0.0.1';
    $name = getenv('MAIN_DB_NAME') ?: 'ajsmrjournal';
    $user = getenv('MAIN_DB_USER') ?: 'root';
    $pass = getenv('MAIN_DB_PASS') ?: '';

    try {
        $jdb = new PDO(
            "mysql:host={$host};dbname={$name};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $jdb;
    } catch (PDOException $e) {
        try {
            $jdb = new PDO(
                "mysql:host=127.0.0.1;port=3306;dbname=ajsmrjournal;charset=utf8mb4",
                "root",
                getenv('MAIN_DB_PASS') ?: "",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            return $jdb;
        } catch (PDOException $ex) {
            $jdb = new PDO(
                "mysql:host=localhost;dbname=ajsmrjournal;charset=utf8mb4",
                "root",
                getenv('MAIN_DB_PASS') ?: "",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            return $jdb;
        }
    }
}

function get_all_issues(): array {
    $st = journal_db()->query("SELECT catid, catename, eventdate, status FROM ajsmr_issueyears WHERE status=1 ORDER BY catid DESC");
    return $st->fetchAll();
}

function format_issue_label(array $issue): string {
    return trim((string)($issue['catename'] ?? ''));
}

function issue_periods(): array {
    return [
        'January-March',
        'April-June',
        'July-September',
        'October-December',
        'Special Issue',
        'Supplement'
    ];
}

function parse_issue_components(string $catename): array {
    $res = [
        'period' => 'January-March',
        'volume' => '12',
        'issue' => '1',
        'year' => date('Y'),
    ];
    if (preg_match('/^([A-Za-z\-]+)\s+(\d+)\((\d+)\),\s*(\d{4})$/', trim($catename), $m)) {
        $res['period'] = $m[1];
        $res['volume'] = $m[2];
        $res['issue'] = $m[3];
        $res['year'] = $m[4];
    } elseif (preg_match('/Volume\s+(\d+)\s*\|\s*Issue\s+(\d+)\s+(?:Supplement\s+)?([A-Za-z\-]+)\s+(\d{4})/i', trim($catename), $m)) {
        $res['volume'] = $m[1];
        $res['issue'] = $m[2];
        $res['period'] = $m[3];
        $res['year'] = $m[4];
    } elseif (preg_match('/(\d{4})/', $catename, $m)) {
        $res['year'] = $m[1];
    }
    return $res;
}

function build_issue_display_name(string $period, string $volume, string $issue, string $year): string {
    $period = trim($period);
    $volume = trim($volume);
    $issue = trim($issue);
    $year = trim($year);

    if ($period === 'Special Issue') {
        return $volume !== '' ? "Special Issue {$volume}, {$year}" : "Special Issue, {$year}";
    }
    if ($period === 'Supplement') {
        return "Volume {$volume} | Issue {$issue} Supplement {$year}";
    }

    if ($period !== '' && $volume !== '' && $issue !== '' && $year !== '') {
        return "{$period} {$volume}({$issue}), {$year}";
    }
    if ($volume !== '' && $issue !== '' && $year !== '') {
        return "Volume {$volume}, Issue {$issue} ({$year})";
    }
    return $period . ($year !== '' ? " {$year}" : "");
}

function current_issue_article_types(): array {
    return [
        'Research Article',
        'Review Article',
        'Short Communication',
        'Case Report',
        'Editorial',
        'Letter to the Editor',
        'Methods Article',
        'Perspective',
        'Other'
    ];
}

function current_issue_file_url(?string $path): string {
    $s = trim(str_replace('\\', '/', (string)$path));
    if ($s === '') return '';
    if (preg_match('~^https?://~i', $s)) return $s;
    while (str_starts_with($s, '../')) $s = substr($s, 3);
    while (str_starts_with($s, './')) $s = substr($s, 2);
    return '../' . ltrim($s, '/');
}

function handle_secure_upload(array $file, string $type, string $subfolder): ?string {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("File upload failed for {$type} (Error Code: " . (int)$file['error'] . ").");
    }

    $maxSize = ($type === 'image') ? (10 * 1024 * 1024) : (25 * 1024 * 1024);
    if ((int)$file['size'] > $maxSize) {
        $mb = (int)($maxSize / (1024 * 1024));
        throw new RuntimeException("The {$type} file exceeds the maximum limit of {$mb} MB.");
    }

    $origName = (string)$file['name'];
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    if ($type === 'pdf') {
        if ($ext !== 'pdf') {
            throw new RuntimeException("Invalid file extension '.{$ext}' for {$type}. Only '.pdf' is permitted.");
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: '';
        if ($mime !== 'application/pdf') {
            throw new RuntimeException("The uploaded file for {$type} is not a valid PDF document (detected: {$mime}).");
        }
    } elseif ($type === 'image') {
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowedExts, true)) {
            throw new RuntimeException("Invalid image extension '.{$ext}'. Only JPG, JPEG, PNG, or WEBP are permitted.");
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: '';
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes, true)) {
            throw new RuntimeException("The uploaded image MIME type ({$mime}) is not permitted.");
        }
    } else {
        throw new RuntimeException("Unsupported upload file type: {$type}.");
    }

    $safeName = 'article_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

    $projectRoot = dirname(__DIR__);
    $targetDir = $projectRoot . '/' . trim($subfolder, '/');
    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
        throw new RuntimeException("Unable to create or access the directory: {$subfolder}.");
    }

    $targetPath = $targetDir . '/' . $safeName;
    $stored = false;
    if (is_uploaded_file($file['tmp_name'])) {
        $stored = move_uploaded_file($file['tmp_name'], $targetPath);
    } else {
        $stored = copy($file['tmp_name'], $targetPath);
    }

    if (!$stored) {
        throw new RuntimeException("Failed to move the uploaded {$type} to destination.");
    }

    // Return stored path relative to site root matching existing AJSMR convention
    return '../' . trim($subfolder, '/') . '/' . $safeName;
}
