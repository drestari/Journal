<?php
declare(strict_types=1);

/**
 * Shared Persistent Layout Shell for Editor-in-Chief Administration
 * AJSMR Editorial Management System
 */

if (!function_exists('db')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

function eic_render_top_bar(string $subtitle = 'Editor-in-Chief Administration Portal'): void {
    $u = function_exists('user') ? user() : ($_SESSION['user'] ?? null);
    $userName = $u['name'] ?? $u['full_name'] ?? $u['email'] ?? 'Editor-in-Chief';
    $basePrefix = defined('BASE_URL') ? BASE_URL : '/editorial/';
    ?>
    <header class="top">
      <div class="top-inner">
        <div class="brand">
          AJSMR &mdash; Editorial Management System
          <small><?=htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8')?></small>
        </div>
        <div class="top-actions">
          <span class="user-badge">&#128100; <?=htmlspecialchars((string)$userName, ENT_QUOTES, 'UTF-8')?></span>
          <a class="header-btn" href="<?=$basePrefix?>dashboard.php">EIC Dashboard</a>
          <a class="header-btn" href="<?=$basePrefix?>workflow_v1.php">Workflow V1</a>
          <a class="header-btn" href="<?=$basePrefix?>logout.php">Sign Out</a>
        </div>
      </div>
    </header>
    <?php
}

function eic_render_header(string $pageTitle = 'AJSMR | Editorial Management System', string $subtitle = 'Editor-in-Chief Administration Portal'): void {
    $u = function_exists('user') ? user() : ($_SESSION['user'] ?? null);
    $userName = $u['name'] ?? $u['full_name'] ?? $u['email'] ?? 'Editor-in-Chief';
    $basePrefix = defined('BASE_URL') ? BASE_URL : '/editorial/';
    ?>
    <!doctype html>
    <html lang="en">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?=htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8')?></title>
    <style>
    *{box-sizing:border-box}
    body{margin:0;background:#f4f7fb;color:#25344a;font-family:Arial,Helvetica,sans-serif}
    .top{background:linear-gradient(135deg,#092b5f,#0b5fa5);color:#fff;padding:20px 30px}
    .top-inner{max-width:1350px;margin:auto;display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap}
    .brand{font-size:22px;font-weight:800}
    .brand small{display:block;font-size:12px;font-weight:400;margin-top:4px;color:#dbeafe}
    .top-actions{display:flex;align-items:center;gap:12px}
    .user-badge{font-size:13px;color:#dbeafe;background:rgba(255,255,255,0.12);padding:6px 12px;border-radius:6px;border:1px solid rgba(255,255,255,0.2)}
    .header-btn{color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,0.35);border-radius:7px;padding:8px 14px;font-size:13px;font-weight:600;transition:background 0.2s}
    .header-btn:hover{background:rgba(255,255,255,0.15)}
    .wrap{max-width:1350px;margin:24px auto;padding:0 20px}
    /* Common UI components for EIC workflow pages */
    .title{font-size:24px;margin:0 0 16px 0;color:#092b5f;font-weight:800}
    .panel,.card{background:#fff;border:1px solid #e3e9f1;border-radius:10px;padding:22px;margin-bottom:20px;box-shadow:0 4px 15px rgba(16,32,64,0.04)}
    .btn{display:inline-block;background:#0b5fa5;color:#fff;border:0;border-radius:6px;padding:9px 15px;text-decoration:none;font-weight:bold;font-size:13px;cursor:pointer;transition:background 0.15s;line-height:1.2}
    .btn:hover{background:#084b84}
    .btn.light{background:#eaf2f9;color:#0b5fa5}
    .btn.primary{background:#0b5fa5;color:#fff}
    .btn.secondary{background:#e2e8f0;color:#334155}
    .btn.danger{background:#dc2626;color:#fff}
    .btn.success{background:#16a34a;color:#fff}
    .badge{display:inline-block;padding:4px 9px;border-radius:12px;font-size:11px;font-weight:bold;background:#eef2f6;color:#334155}
    table{width:100%;border-collapse:collapse}
    th,td{padding:11px 12px;border:1px solid #e2e8f0;text-align:left;vertical-align:middle;font-size:13px}
    th{background:#f8fafc;color:#475569;font-weight:bold;text-transform:uppercase;font-size:11.5px;letter-spacing:0.4px}
    input,textarea,select{box-sizing:border-box;width:100%;padding:9px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;color:#1e293b}
    .muted{color:#64748b;font-size:13px}
    .alert{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13.5px}
    .alert.success,.alert-success,.ok{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
    .alert.err,.alert.danger,.alert-danger,.err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
    .alert.info,.alert-info{background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe}
    </style>
    </head>
    <body>
    <?php eic_render_top_bar($subtitle); ?>
    <main class="wrap">
      <div class="dashboard-layout">
        <?php include __DIR__ . '/eic_sidebar.php'; ?>
        <div class="right-panel">
    <?php
}

function eic_render_footer(): void {
    ?>
        </div><!-- /.right-panel -->
      </div><!-- /.dashboard-layout -->
    </main>
    <footer style="margin-top:40px;padding:24px 0;border-top:1px solid #e2e8f0;text-align:center;font-size:12px;color:#64748b;">
      AJSMR Editorial Management System V1 &copy; <?=date('Y')?> &bull; Editor-in-Chief Workflow
    </footer>
    </body>
    </html>
    <?php
}
