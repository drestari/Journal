<?php
/* AJSMR Author Portal — Modern Sidebar Header
 * Included by header.php when role === 'author'.
 * Reads $pageTitle and $u from calling scope.
 */
$u  = $u ?? user();
$pt = $pageTitle ?? 'Author Portal — AJSMR';

// Detect active page for nav highlighting
$curFile = basename($_SERVER['PHP_SELF'] ?? '');
$curDir  = basename(dirname($_SERVER['PHP_SELF'] ?? ''));

function ap_nav_active(string $file, string $dir = 'author'): string {
    global $curFile, $curDir;
    return ($curFile === $file && $curDir === $dir) ? ' active' : '';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?=htmlspecialchars($pt, ENT_QUOTES, 'UTF-8')?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="<?=BASE_URL?>assets/css/author.css">
</head>
<body class="author-portal">
<div class="ap-shell">

  <!-- ===== Sidebar ===== -->
  <aside class="ap-sidebar" id="apSidebar">

    <!-- Logo -->
    <div class="ap-sidebar-logo">
      <div class="ap-sidebar-logo-mark">AJ</div>
      <div class="ap-sidebar-logo-text">
        <strong>AJSMR</strong>
        <span>Author Portal</span>
      </div>
    </div>

    <!-- User pill -->
    <div class="ap-sidebar-user">
      <span class="ap-sidebar-user-name"><?=htmlspecialchars($u['full_name'] ?? $u['name'] ?? 'Author', ENT_QUOTES, 'UTF-8')?></span>
      <span class="ap-sidebar-user-role">Author</span>
    </div>

    <!-- Nav -->
    <nav class="ap-nav">
      <div class="ap-nav-section">Overview</div>

      <a href="<?=BASE_URL?>author/index.php" class="ap-nav-item<?=ap_nav_active('index.php')?>">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
          <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
        </svg>
        Dashboard
      </a>

      <div class="ap-nav-section">Manuscripts</div>

      <a href="<?=BASE_URL?>author/submit.php" class="ap-nav-item<?=ap_nav_active('submit.php')?>">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="12" y1="18" x2="12" y2="12"/>
          <line x1="9" y1="15" x2="15" y2="15"/>
        </svg>
        Submit Manuscript
      </a>

      <a href="<?=BASE_URL?>author/submissions.php" class="ap-nav-item<?=ap_nav_active('submissions.php')?>">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/>
          <line x1="16" y1="17" x2="8" y2="17"/>
          <polyline points="10 9 9 9 8 9"/>
        </svg>
        My Submissions
      </a>

    </nav>

    <!-- Sidebar footer -->
    <div class="ap-sidebar-footer">
      <a href="<?=BASE_URL?>logout.php" class="ap-nav-item">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
          <polyline points="16 17 21 12 16 7"/>
          <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        Sign Out
      </a>
    </div>

  </aside><!-- /.ap-sidebar -->

  <!-- ===== Main ===== -->
  <div class="ap-main">

    <!-- Topbar -->
    <header class="ap-topbar">
      <div class="ap-topbar-breadcrumb">
        AJSMR &rsaquo; <strong><?=htmlspecialchars($pt, ENT_QUOTES, 'UTF-8')?></strong>
      </div>
      <div class="ap-topbar-actions">
        <a href="<?=BASE_URL?>author/submit.php" class="ap-btn ap-btn-primary ap-btn-sm">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          New Submission
        </a>
      </div>
    </header>

    <!-- Content wrapper -->
    <div class="ap-content">
