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
    $userName = $u['email'] ?? $u['full_name'] ?? $u['name'] ?? 'Editor-in-Chief';
    $basePrefix = defined('BASE_URL') ? BASE_URL : '/editorial/';
    ?>
    <header class="eic-fixed-header-wrapper" role="banner">
      <!-- 1. Top Utility Bar (Dark Navy, matching public AJSMR style) -->
      <div class="eic-utility-bar">
        <div class="eic-utility-inner">
          <div class="eic-utility-left">
            <span>▥ &nbsp;ISSN (Online): 2377-6196</span>
            <i class="eic-bar-sep"></i>
            <span>♙ &nbsp;Open Access</span>
            <i class="eic-bar-sep"></i>
            <span>▣ &nbsp;Quarterly</span>
          </div>
          <div class="eic-utility-right">
            <span class="eic-portal-tag"><?=htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8')?></span>
            <i class="eic-bar-sep"></i>
            <a href="../index.php" target="_blank" rel="noopener">🌐 &nbsp;Main Journal Website ↗</a>
          </div>
        </div>
      </div>

      <!-- 2. Main Identity Header Area (White Background, matching public AJSMR branding) -->
      <div class="eic-main-header">
        <div class="eic-header-inner">
          <a class="eic-journal-brand" href="<?=$basePrefix?>dashboard.php">
            <img class="eic-brand-logo-image" src="../images/ajsmr-logo.png" onerror="this.src='images/ajsmr-logo.png'" alt="AJSMR logo">
            <div class="eic-brand-copy">
              <div class="eic-script-title">The American Journal of</div>
              <div class="eic-stencil-title">SCIENCE AND MEDICAL RESEARCH</div>
              <p class="eic-brand-tagline">Open Access &nbsp; | &nbsp; Peer Reviewed &nbsp; | &nbsp; AIRA Publisher</p>
            </div>
          </a>

          <!-- EIC Administration Controls -->
          <div class="eic-header-controls">
            <div class="eic-user-pill">
              <span class="eic-role-badge">Editor-in-Chief</span>
              <span class="eic-user-name">&#128100; <?=htmlspecialchars((string)$userName, ENT_QUOTES, 'UTF-8')?></span>
            </div>
            <div class="eic-nav-actions">
              <a class="eic-action-btn eic-btn-primary" href="<?=$basePrefix?>dashboard.php">EIC Dashboard</a>
              <a class="eic-action-btn eic-btn-secondary" href="<?=$basePrefix?>workflow_v1.php">Workflow V1</a>
              <a class="eic-action-btn eic-btn-danger" href="<?=$basePrefix?>logout.php">Sign Out</a>
            </div>
          </div>
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
    body{margin:0;padding-top:132px;padding-bottom:38px;background:#f8fafc;color:#1e293b;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}
    .wrap{max-width:100%;margin:0;padding:0;width:100%}
    /* Common UI components for EIC workflow pages */
    .title{font-size:24px;margin:0 0 16px 0;color:#092b5f;font-weight:800}
    .panel,.card{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:22px;margin-bottom:20px;box-shadow:0 1px 3px rgba(15,23,42,0.04)}
    .btn{display:inline-block;background:#0b5fa5;color:#fff;border:0;border-radius:6px;padding:9px 15px;text-decoration:none;font-weight:bold;font-size:12.5px;cursor:pointer;transition:background 0.15s;line-height:1.2}
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
    <footer class="eic-fixed-footer">
      AJSMR Editorial Management System V1 &copy; <?=date('Y')?> &bull; Editor-in-Chief Administration Portal
    </footer>
    </body>
    </html>
    <?php
}
