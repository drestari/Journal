<?php
declare(strict_types=1);

/**
 * Shared Persistent Editor-in-Chief Sidebar Component
 * AJSMR Editorial Management System
 * 
 * Modern Admin Portal Style (Dark Navy Sidebar, Clean White/Light Content Canvas)
 * Strictly Top-Level Categories Only (No submenus or dropdowns in sidebar)
 * Selecting a category updates the Content Area to display its actions/options.
 */

if (!function_exists('db')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

// Resolve current manuscript ID if present
$currentMsId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($currentMsId <= 0) {
    if (isset($m['id']) && is_numeric($m['id'])) {
        $currentMsId = (int)$m['id'];
    } elseif (isset($manuscript['id']) && is_numeric($manuscript['id'])) {
        $currentMsId = (int)$manuscript['id'];
    }
}

// Live count of new submissions for badge
if (!isset($countNew)) {
    try {
        $pdoDb = db();
        $countNew = (int)$pdoDb->query("SELECT COUNT(*) FROM manuscripts WHERE status IN ('submitted', 'new_submission', 'SUBMITTED')")->fetchColumn();
    } catch (Throwable $e) {
        $countNew = 0;
    }
}

// Map current page to active category section
$issuePages = [
    'issue_years.php', 'issue_year_add.php', 'issue_year_edit.php',
    'current_issue_list.php', 'current_issue_add.php', 'current_issue_edit.php',
    'eic_issue_years.php', 'issue_assignment.php'
];
$articlePubPages = [
    'article_manage.php', 'article_add.php', 'article_edit.php',
    'production.php', 'typesetting.php', 'galley_proof.php', 'publish.php',
    'doi_metadata.php', 'doi_archive.php', 'doi_verify.php', 'author_proof.php', 'accepted.php'
];
$editorialWorkflowPages = [
    'workflow_v1.php', 'technical_check.php', 'assign_editor.php',
    'reviewers.php', 'reviewer_manage.php', 'decision.php',
    'revision.php', 'accept.php'
];

if (in_array($currentPage, $issuePages, true)) {
    $activeCategory = 'current-issue';
} elseif (in_array($currentPage, $articlePubPages, true)) {
    $activeCategory = 'article-publication';
} elseif (in_array($currentPage, $editorialWorkflowPages, true)) {
    $activeCategory = 'editorial-decision';
} elseif (in_array($currentPage, ['new_submissions.php', 'manuscript_view.php', 'published_manuscripts.php'], true)) {
    $activeCategory = 'manuscripts';
} else {
    $activeCategory = ($currentPage === 'dashboard.php') ? 'overview' : 'manuscripts';
}

$isOnDashboard = ($currentPage === 'dashboard.php');
$basePrefix = defined('BASE_URL') ? BASE_URL : '/editorial/';
?>
<?php if (!defined('EIC_SIDEBAR_CSS_INCLUDED')): define('EIC_SIDEBAR_CSS_INCLUDED', true); ?>
<style id="eic-sidebar-styles">
/* 3-Section Layout Structure */
html, body {
  margin: 0;
  padding: 0;
  min-height: 100%;
}
body {
  padding-top: 132px !important;
  padding-bottom: 38px !important;
  background: #f8fafc;
  color: #073d72;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  box-sizing: border-box;
}

/* Redesigned Fixed EIC Header (Matching AJSMR Public Branding) */
.eic-fixed-header-wrapper {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
  z-index: 1000 !important;
  background: #ffffff !important;
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.08) !important;
}

/* 1. Top Utility Bar (Dark Navy) */
.eic-utility-bar {
  background: #073d72 !important;
  color: #ffffff !important;
  font-size: 11.5px !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}
.eic-utility-inner {
  padding: 6px 28px !important;
  display: flex !important;
  justify-content: space-between !important;
  align-items: center !important;
  min-height: 30px !important;
  box-sizing: border-box !important;
}
.eic-utility-left, .eic-utility-right {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  flex-wrap: wrap !important;
}
.eic-bar-sep {
  display: inline-block !important;
  width: 1px !important;
  height: 12px !important;
  background: rgba(255, 255, 255, 0.35) !important;
}
.eic-utility-bar a {
  color: #dbeafe !important;
  text-decoration: none !important;
  font-weight: 500 !important;
}
.eic-utility-bar a:hover {
  color: #ffffff !important;
  text-decoration: underline !important;
}
.eic-portal-tag {
  color: #93c5fd !important;
  font-weight: 600 !important;
  letter-spacing: 0.3px !important;
}

/* 2. Main Identity Header Area (White Background) */
.eic-main-header {
  background: #ffffff !important;
  border-bottom: 1px solid #dce6ef !important;
  padding: 10px 28px !important;
  box-sizing: border-box !important;
}
.eic-header-inner {
  display: flex !important;
  justify-content: space-between !important;
  align-items: center !important;
  min-height: 80px !important;
  gap: 20px !important;
}

/* Existing Journal Brand Styling */
.eic-journal-brand {
  display: flex !important;
  align-items: center !important;
  gap: 18px !important;
  text-decoration: none !important;
  color: #063b70 !important;
}
.eic-brand-logo-image {
  width: 78px !important;
  height: 82px !important;
  object-fit: contain !important;
  display: block !important;
}
.eic-brand-copy {
  min-width: 0 !important;
}
.eic-script-title {
  font-family: "Brush Script MT", "Brush Script Std", "Segoe Script", cursive !important;
  font-style: italic !important;
  font-size: 28px !important;
  line-height: 0.95 !important;
  color: #173c67 !important;
  white-space: nowrap !important;
}
.eic-stencil-title {
  font-family: Stencil, "Stencil Std", "Impact", fantasy !important;
  font-size: 23px !important;
  line-height: 1.05 !important;
  letter-spacing: 0.055em !important;
  color: #073d72 !important;
  white-space: nowrap !important;
}
.eic-brand-tagline {
  margin: 5px 0 0 !important;
  color: #41627e !important;
  font-size: 11px !important;
  font-weight: 700 !important;
  letter-spacing: 0.2px !important;
}

/* EIC Controls Cluster */
.eic-header-controls {
  display: flex !important;
  align-items: center !important;
  gap: 16px !important;
  flex-wrap: wrap !important;
}
.eic-user-pill {
  display: flex !important;
  flex-direction: column !important;
  align-items: flex-end !important;
  gap: 2px !important;
  padding-right: 14px !important;
  border-right: 1px solid #e2e8f0 !important;
}
.eic-role-badge {
  font-size: 10px !important;
  font-weight: 800 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.6px !important;
  color: #0b5fa5 !important;
  background: #eef6fc !important;
  padding: 2px 7px !important;
  border-radius: 4px !important;
}
.eic-user-name {
  font-size: 12.5px !important;
  font-weight: 600 !important;
  color: #1e293b !important;
}
.eic-nav-actions {
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
}
.eic-action-btn {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  padding: 8px 14px !important;
  border-radius: 6px !important;
  font-size: 12px !important;
  font-weight: 700 !important;
  text-decoration: none !important;
  transition: all 0.15s ease !important;
  line-height: 1.2 !important;
  cursor: pointer !important;
}
.eic-btn-primary {
  background: #0b5fa5 !important;
  color: #ffffff !important;
  border: 1px solid #084980 !important;
}
.eic-btn-primary:hover {
  background: #084980 !important;
}
.eic-btn-secondary {
  background: #f1f5f9 !important;
  color: #334155 !important;
  border: 1px solid #cbd5e1 !important;
}
.eic-btn-secondary:hover {
  background: #e2e8f0 !important;
  color: #0f172a !important;
}
.eic-btn-danger {
  background: #fff1f2 !important;
  color: #be123c !important;
  border: 1px solid #fecdd3 !important;
}
.eic-btn-danger:hover {
  background: #ffe4e6 !important;
  color: #9f1239 !important;
}

/* Fixed Footer */
.eic-fixed-footer, footer.eic-fixed-footer {
  position: fixed !important;
  bottom: 0 !important;
  left: 0 !important;
  right: 0 !important;
  height: 38px !important;
  z-index: 1000 !important;
  background: #ffffff !important;
  color: #64748b !important;
  border-top: 1px solid #e2e8f0 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  font-size: 12px !important;
  padding: 0 16px !important;
  margin: 0 !important;
  box-sizing: border-box !important;
}

/* Container & Layout */
.wrap {
  max-width: 100% !important;
  margin: 0 !important;
  padding: 0 !important;
  width: 100% !important;
  box-sizing: border-box !important;
}
.dashboard-layout {
  display: flex !important;
  width: 100% !important;
  min-height: calc(100vh - 170px) !important;
  box-sizing: border-box !important;
}

/* Dark Navy Left Sidebar (AIRA Modern Portal Inspired) */
.left-panel.eic-persistent-sidebar {
  width: 275px !important;
  flex-shrink: 0 !important;
  position: fixed !important;
  top: 132px !important;
  bottom: 38px !important;
  left: 0 !important;
  overflow-y: auto !important;
  background: #09192f !important;
  border-right: 1px solid #172a45 !important;
  border-left: 0 !important;
  border-top: 0 !important;
  border-bottom: 0 !important;
  border-radius: 0 !important;
  box-shadow: 2px 0 12px rgba(0, 0, 0, 0.15) !important;
  z-index: 900 !important;
  box-sizing: border-box !important;
  scrollbar-width: thin;
  scrollbar-color: #1e293b #09192f;
}
  box-sizing: border-box !important;
  scrollbar-width: thin;
  scrollbar-color: #1e293b #09192f;
}
.left-panel.eic-persistent-sidebar::-webkit-scrollbar {
  width: 5px;
}
.left-panel.eic-persistent-sidebar::-webkit-scrollbar-track {
  background: #09192f;
}
.left-panel.eic-persistent-sidebar::-webkit-scrollbar-thumb {
  background: #1e293b;
  border-radius: 4px;
}
.left-panel .panel-header {
  background: rgba(255, 255, 255, 0.03);
  color: #fff;
  padding: 16px 18px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.left-panel .panel-header h3 {
  margin: 0;
  font-size: 11.5px;
  font-weight: 800;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: #94a3b8;
}
.left-panel .panel-header p {
  margin: 4px 0 0;
  font-size: 11px;
  color: #64748b;
}

/* Nav Menu (Strictly Top-Level Categories Only) */
.left-panel .nav-menu {
  list-style: none;
  margin: 0;
  padding: 10px 0;
}
.left-panel .nav-item {
  margin: 4px 8px;
}
.left-panel .nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  color: #cbd5e1;
  text-decoration: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  line-height: 1.35;
  transition: all 0.15s ease;
  border-left: 3px solid transparent;
  cursor: pointer;
  box-sizing: border-box;
}
.left-panel .nav-link:hover {
  background: rgba(255, 255, 255, 0.07);
  color: #ffffff;
}
.left-panel .nav-link.active,
.left-panel .nav-item.active-section > .nav-link {
  background: rgba(14, 165, 233, 0.15);
  color: #ffffff;
  border-left-color: #38bdf8;
  font-weight: 700;
}
.left-panel .nav-icon {
  font-size: 16px;
  line-height: 1;
  flex-shrink: 0;
  opacity: 0.95;
}
.left-panel .nav-text {
  flex: 1;
}
.left-panel .nav-badge {
  background: #0284c7;
  color: #fff;
  font-size: 10.5px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 10px;
  margin-left: auto;
}

/* Right Content Canvas */
.right-panel {
  flex: 1 !important;
  min-width: 0 !important;
  margin-left: 275px !important;
  padding: 24px 32px 36px 32px !important;
  box-sizing: border-box !important;
  background: #f8fafc;
}

/* Modern Card Layouts */
.category-options-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 18px;
  margin-bottom: 24px;
}
.nav-action-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
  border-top: 3px solid #0b5fa5;
}
.nav-action-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
  border-color: #cbd5e1;
  border-top-color: #0284c7;
}
.nav-action-card-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 10px;
}
.nav-action-card-icon {
  font-size: 22px;
  line-height: 1;
  flex-shrink: 0;
  padding: 6px;
  background: #f1f5f9;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
}
.nav-action-card-title {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: #092b5f;
  line-height: 1.3;
}
.nav-action-card-desc {
  margin: 0 0 16px 0;
  font-size: 12.5px;
  color: #64748b;
  line-height: 1.5;
}
.nav-action-card-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  background: #0b5fa5;
  color: #ffffff !important;
  text-decoration: none;
  font-size: 12px;
  font-weight: 700;
  padding: 9px 14px;
  border-radius: 6px;
  border: 0;
  cursor: pointer;
  transition: background 0.15s ease;
  width: 100%;
  box-sizing: border-box;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}
.nav-action-card-btn:hover {
  background: #084980;
}
.nav-action-card-btn.outline {
  background: #f8fafc;
  color: #0b5fa5 !important;
  border: 1px solid #cbd5e1;
}
.nav-action-card-btn.outline:hover {
  background: #eef6fc;
  border-color: #0b5fa5;
}

/* Responsive Viewports */
@media (max-width: 960px) {
  body {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
  }
  .eic-fixed-header-wrapper {
    position: static !important;
  }
  .eic-header-inner {
    flex-direction: column !important;
    gap: 14px !important;
    align-items: flex-start !important;
    padding: 8px 0 !important;
  }
  .eic-user-pill {
    align-items: flex-start !important;
    border-right: 0 !important;
    padding-right: 0 !important;
  }
  .eic-utility-inner {
    flex-direction: column !important;
    gap: 6px !important;
    align-items: flex-start !important;
    padding: 8px 16px !important;
  }
  .eic-journal-brand {
    flex-wrap: wrap !important;
  }
  .eic-fixed-footer, footer.eic-fixed-footer {
    position: static !important;
  }
  .dashboard-layout {
    flex-direction: column !important;
  }
  .left-panel.eic-persistent-sidebar {
    position: static !important;
    width: 100% !important;
    max-height: none !important;
    box-shadow: none !important;
    border-right: 0 !important;
    border-bottom: 1px solid #172a45 !important;
  }
  .left-panel .nav-menu {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 4px;
    padding: 8px;
  }
  .left-panel .nav-item {
    margin: 0;
  }
  .right-panel {
    margin-left: 0 !important;
    padding: 16px !important;
  }
  .category-options-grid {
    grid-template-columns: 1fr;
  }
}
</style>
<?php endif; ?>

<!-- LEFT PANEL: Editor-in-Chief Shared Navigation (Dark Navy, Top-Level Categories Only) -->
<aside class="left-panel eic-persistent-sidebar" aria-label="Editor-in-Chief Menu">
  <div class="">
    
  </div>
  <ul class="nav-menu" role="tablist">

    <!-- CATEGORY 0: Dashboard -->
    <li class="nav-item <?= in_array($activeCategory, ['overview', 'dashboard', ''], true) ? 'active-section' : '' ?>" id="item-overview">
      <a class="nav-link <?= in_array($activeCategory, ['overview', 'dashboard', ''], true) ? 'active' : '' ?>"
         id="tab-overview"
         role="tab"
         aria-selected="<?= in_array($activeCategory, ['overview', 'dashboard', ''], true) ? 'true' : 'false' ?>"
         aria-controls="sec-overview"
         href="<?= $isOnDashboard ? '#overview' : $basePrefix . 'dashboard.php#overview' ?>"
         <?= $isOnDashboard ? 'onclick="switchCategory(\'overview\', event)"' : '' ?>>
        <span class="nav-icon">📊</span>
        <span class="nav-text">Dashboard</span>
      </a>
    </li>

    <!-- CATEGORY 1: Manuscript Queue & New Submissions -->
    <li class="nav-item <?= $activeCategory === 'manuscripts' ? 'active-section' : '' ?>" id="item-manuscripts">
      <a class="nav-link <?= $activeCategory === 'manuscripts' ? 'active' : '' ?>"
         id="tab-manuscripts"
         role="tab"
         aria-selected="<?= $activeCategory === 'manuscripts' ? 'true' : 'false' ?>"
         aria-controls="sec-manuscripts"
         href="<?= $isOnDashboard ? '#manuscripts' : $basePrefix . 'dashboard.php#manuscripts' ?>"
         <?= $isOnDashboard ? 'onclick="switchCategory(\'manuscripts\', event)"' : '' ?>>
        <span class="nav-icon">📥</span>
        <span class="nav-text">Manuscripts </span>
        <?php if ((int)$countNew > 0): ?>
          <span class="nav-badge"><?=(int)$countNew?></span>
        <?php endif; ?>
      </a>
    </li>

    <!-- CATEGORY 2: Current Issue Management -->
    <li class="nav-item <?= $activeCategory === 'current-issue' ? 'active-section' : '' ?>" id="item-current-issue">
      <a class="nav-link <?= $activeCategory === 'current-issue' ? 'active' : '' ?>"
         id="tab-current-issue"
         role="tab"
         aria-selected="<?= $activeCategory === 'current-issue' ? 'true' : 'false' ?>"
         aria-controls="sec-current-issue"
         href="<?= $isOnDashboard ? '#current-issue' : $basePrefix . 'dashboard.php#current-issue' ?>"
         <?= $isOnDashboard ? 'onclick="switchCategory(\'current-issue\', event)"' : '' ?>>
        <span class="nav-icon">📚</span>
        <span class="nav-text">Current Issue Management</span>
      </a>
    </li>

    <!-- CATEGORY 3: Article Publication -->
    <li class="nav-item <?= $activeCategory === 'article-publication' ? 'active-section' : '' ?>" id="item-article-publication">
      <a class="nav-link <?= $activeCategory === 'article-publication' ? 'active' : '' ?>"
         id="tab-article-publication"
         role="tab"
         aria-selected="<?= $activeCategory === 'article-publication' ? 'true' : 'false' ?>"
         aria-controls="sec-article-publication"
         href="<?= $isOnDashboard ? '#article-publication' : $basePrefix . 'dashboard.php#article-publication' ?>"
         <?= $isOnDashboard ? 'onclick="switchCategory(\'article-publication\', event)"' : '' ?>>
        <span class="nav-icon">📄</span>
        <span class="nav-text">Article Publication</span>
      </a>
    </li>

    <!-- CATEGORY 4: Editorial Management & Decision System -->
    <li class="nav-item <?= $activeCategory === 'editorial-decision' ? 'active-section' : '' ?>" id="item-editorial-decision">
      <a class="nav-link <?= $activeCategory === 'editorial-decision' ? 'active' : '' ?>"
         id="tab-editorial-decision"
         role="tab"
         aria-selected="<?= $activeCategory === 'editorial-decision' ? 'true' : 'false' ?>"
         aria-controls="sec-editorial-decision"
         href="<?= $isOnDashboard ? '#editorial-decision' : $basePrefix . 'dashboard.php#editorial-decision' ?>"
         <?= $isOnDashboard ? 'onclick="switchCategory(\'editorial-decision\', event)"' : '' ?>>
        <span class="nav-icon">⚙</span>
        <span class="nav-text">Reviewer Management</span>
      </a>
    </li>

  </ul>
</aside>
