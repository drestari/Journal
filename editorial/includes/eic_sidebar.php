<?php
declare(strict_types=1);

/**
 * Shared Persistent Editor-in-Chief Sidebar Component
 * AJSMR Editorial Management System
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
    'eic_issue_years.php'
];
$articlePubPages = [
    'article_manage.php', 'article_add.php', 'article_edit.php',
    'production.php', 'typesetting.php', 'galley_proof.php', 'publish.php',
    'issue_assignment.php', 'doi_metadata.php', 'doi_archive.php',
    'doi_verify.php', 'author_proof.php', 'accepted.php'
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
} else {
    $activeCategory = 'manuscripts';
}

$isOnDashboard = ($currentPage === 'dashboard.php');
$basePrefix = defined('BASE_URL') ? BASE_URL : '/editorial/';

$msQuery = $currentMsId > 0 ? ('?id=' . $currentMsId) : '';
?>
<?php if (!defined('EIC_SIDEBAR_CSS_INCLUDED')): define('EIC_SIDEBAR_CSS_INCLUDED', true); ?>
<style id="eic-sidebar-styles">
/* Persistent EIC Sidebar Layout */
.dashboard-layout {
  display: flex;
  gap: 24px;
  align-items: flex-start;
  width: 100%;
}
.left-panel {
  width: 300px;
  flex-shrink: 0;
  position: sticky;
  top: 20px;
  max-height: calc(100vh - 40px);
  overflow-y: auto;
  background: #fff;
  border: 1px solid #e3e9f1;
  border-radius: 12px;
  box-shadow: 0 4px 18px rgba(16,32,64,0.06);
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 #f8fafc;
}
.left-panel::-webkit-scrollbar {
  width: 5px;
}
.left-panel::-webkit-scrollbar-track {
  background: #f8fafc;
}
.left-panel::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
.left-panel .panel-header {
  background: #092b5f;
  color: #fff;
  padding: 16px 18px;
  position: sticky;
  top: 0;
  z-index: 5;
}
.left-panel .panel-header h3 {
  margin: 0;
  font-size: 13px;
  font-weight: 800;
  letter-spacing: 0.8px;
  text-transform: uppercase;
}
.left-panel .panel-header p {
  margin: 4px 0 0;
  font-size: 11px;
  color: #cbd5e1;
}
.left-panel .nav-menu {
  list-style: none;
  margin: 0;
  padding: 10px 0;
}
.left-panel .nav-item {
  margin: 3px 8px;
}
.left-panel .nav-link-row {
  display: flex;
  align-items: center;
  position: relative;
}
.left-panel .nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  color: #334155;
  text-decoration: none;
  border-radius: 8px;
  font-size: 13.5px;
  font-weight: 600;
  line-height: 1.35;
  transition: all 0.15s ease;
  border-left: 4px solid transparent;
  cursor: pointer;
  flex: 1;
}
.left-panel .nav-link:hover {
  background: #f8fafc;
  color: #0b5fa5;
}
.left-panel .nav-link.active,
.left-panel .nav-item.active-section > .nav-link-row > .nav-link {
  background: #eef6fc;
  color: #092b5f;
  border-left-color: #0b5fa5;
  font-weight: 700;
}
.left-panel .nav-icon {
  font-size: 18px;
  line-height: 1;
  flex-shrink: 0;
}
.left-panel .nav-text {
  flex: 1;
}
.left-panel .nav-badge {
  background: #0b5fa5;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 10px;
  margin-left: auto;
}
.left-panel .nav-toggle {
  background: transparent;
  border: 0;
  color: #64748b;
  cursor: pointer;
  padding: 8px 10px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  transition: all 0.15s ease;
}
.left-panel .nav-toggle:hover {
  background: #f1f5f9;
  color: #0b5fa5;
}
.left-panel .nav-submenu {
  list-style: none;
  margin: 0;
  padding: 4px 0 6px 36px;
  display: none;
  flex-direction: column;
  gap: 2px;
}
.left-panel .nav-submenu.is-open {
  display: flex;
}
.left-panel .nav-sublink {
  display: flex;
  align-items: center;
  padding: 6px 10px;
  color: #475569;
  text-decoration: none;
  border-radius: 6px;
  font-size: 12.5px;
  font-weight: 500;
  transition: all 0.15s ease;
  line-height: 1.35;
}
.left-panel .nav-sublink:hover {
  background: #f1f5f9;
  color: #0b5fa5;
}
.left-panel .nav-sublink.active {
  background: #eef6fc;
  color: #092b5f;
  font-weight: 700;
}
.left-panel .nav-sublink.active::before {
  content: "•";
  color: #0b5fa5;
  font-size: 16px;
  margin-right: 6px;
  line-height: 0;
}
.right-panel {
  flex: 1;
  min-width: 0;
}
@media (max-width: 960px) {
  .dashboard-layout {
    flex-direction: column;
    gap: 16px;
  }
  .left-panel {
    width: 100%;
    position: static;
    max-height: none;
    overflow-y: visible;
  }
}
</style>
<script>
function eicToggleSubmenu(subId, btn) {
  var sub = document.getElementById(subId);
  if (!sub) return;
  var isOpen = sub.classList.contains('is-open');
  if (isOpen) {
    sub.classList.remove('is-open');
    if (btn) {
      var icon = btn.querySelector('.nav-toggle-icon');
      if (icon) icon.textContent = '▸';
    }
  } else {
    sub.classList.add('is-open');
    if (btn) {
      var icon = btn.querySelector('.nav-toggle-icon');
      if (icon) icon.textContent = '▾';
    }
  }
}
</script>
<?php endif; ?>

<!-- LEFT PANEL: Editor-in-Chief Shared Navigation -->
<aside class="left-panel eic-persistent-sidebar" aria-label="Editor-in-Chief Menu">
  <div class="panel-header">
    <h3>EDITOR-IN-CHIEF MENU</h3>
    <p>AJSMR Editorial Navigation</p>
  </div>
  <ul class="nav-menu" role="tablist">

    <!-- CATEGORY 1: Manuscript Queue & New Submissions -->
    <li class="nav-item <?= $activeCategory === 'manuscripts' ? 'active-section' : '' ?>" id="item-manuscripts">
      <div class="nav-link-row">
        <a class="nav-link <?= $activeCategory === 'manuscripts' ? 'active' : '' ?>"
           id="tab-manuscripts"
           role="tab"
           aria-selected="<?= $activeCategory === 'manuscripts' ? 'true' : 'false' ?>"
           aria-controls="sec-manuscripts"
           href="<?= $isOnDashboard ? '#manuscripts' : $basePrefix . 'dashboard.php#manuscripts' ?>"
           <?= $isOnDashboard ? 'onclick="switchCategory(\'manuscripts\', event)"' : '' ?>>
          <span class="nav-icon">📥</span>
          <span class="nav-text">Manuscript Queue &amp; New Submissions</span>
          <?php if ((int)$countNew > 0): ?>
            <span class="nav-badge"><?=(int)$countNew?></span>
          <?php endif; ?>
        </a>
        <button type="button" class="nav-toggle" onclick="eicToggleSubmenu('sub-manuscripts', this)" aria-label="Toggle submenu">
          <span class="nav-toggle-icon"><?= $activeCategory === 'manuscripts' ? '▾' : '▸' ?></span>
        </button>
      </div>
      <ul class="nav-submenu <?= $activeCategory === 'manuscripts' ? 'is-open' : '' ?>" id="sub-manuscripts">
        <li>
          <a class="nav-sublink <?= $isOnDashboard ? 'active' : '' ?>"
             href="<?= $basePrefix ?>dashboard.php#manuscripts"
             <?= $isOnDashboard ? 'onclick="switchCategory(\'manuscripts\', event)"' : '' ?>>
            Queue Overview
          </a>
        </li>
        <li>
          <a class="nav-sublink <?= $currentPage === 'new_submissions.php' ? 'active' : '' ?>"
             href="<?= $basePrefix ?>new_submissions.php">
            All New Submissions
          </a>
        </li>
        <?php if ($currentPage === 'manuscript_view.php'): ?>
          <li>
            <a class="nav-sublink active" href="<?= $basePrefix ?>manuscript_view.php<?= $msQuery ?>">
              Current Manuscript View
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </li>

    <!-- CATEGORY 2: Current Issue Management -->
    <li class="nav-item <?= $activeCategory === 'current-issue' ? 'active-section' : '' ?>" id="item-current-issue">
      <div class="nav-link-row">
        <a class="nav-link <?= $activeCategory === 'current-issue' ? 'active' : '' ?>"
           id="tab-current-issue"
           role="tab"
           aria-selected="<?= $activeCategory === 'current-issue' ? 'true' : 'false' ?>"
           aria-controls="sec-current-issue"
           href="<?= $isOnDashboard ? '#current-issue' : $basePrefix . 'issue_years.php' ?>"
           <?= $isOnDashboard ? 'onclick="switchCategory(\'current-issue\', event)"' : '' ?>>
          <span class="nav-icon">📚</span>
          <span class="nav-text">Current Issue Management</span>
        </a>
        <button type="button" class="nav-toggle" onclick="eicToggleSubmenu('sub-current-issue', this)" aria-label="Toggle submenu">
          <span class="nav-toggle-icon"><?= $activeCategory === 'current-issue' ? '▾' : '▸' ?></span>
        </button>
      </div>
      <ul class="nav-submenu <?= $activeCategory === 'current-issue' ? 'is-open' : '' ?>" id="sub-current-issue">
        <li>
          <a class="nav-sublink <?= in_array($currentPage, ['issue_years.php', 'issue_year_add.php', 'issue_year_edit.php'], true) ? 'active' : '' ?>"
             href="<?= $basePrefix ?>issue_years.php">
            Issue Years &amp; Issues
          </a>
        </li>
        <li>
          <a class="nav-sublink <?= in_array($currentPage, ['current_issue_list.php', 'current_issue_edit.php'], true) ? 'active' : '' ?>"
             href="<?= $basePrefix ?>current_issue_list.php">
            Current Issue List
          </a>
        </li>
        <li>
          <a class="nav-sublink <?= $currentPage === 'current_issue_add.php' ? 'active' : '' ?>"
             href="<?= $basePrefix ?>current_issue_add.php">
            Add Current Issue
          </a>
        </li>
        <li>
          <a class="nav-sublink <?= $currentPage === 'issue_assignment.php' ? 'active' : '' ?>"
             href="<?= $basePrefix ?>issue_assignment.php<?= $msQuery ?>">
            Issue Article Management
          </a>
        </li>
        <li>
          <a class="nav-sublink" href="../currentissue.php" target="_blank" rel="noopener">
            Public Current Issue ↗
          </a>
        </li>
      </ul>
    </li>

    <!-- CATEGORY 3: Article Publication -->
    <li class="nav-item <?= $activeCategory === 'article-publication' ? 'active-section' : '' ?>" id="item-article-publication">
      <div class="nav-link-row">
        <a class="nav-link <?= $activeCategory === 'article-publication' ? 'active' : '' ?>"
           id="tab-article-publication"
           role="tab"
           aria-selected="<?= $activeCategory === 'article-publication' ? 'true' : 'false' ?>"
           aria-controls="sec-article-publication"
           href="<?= $isOnDashboard ? '#article-publication' : $basePrefix . 'article_manage.php' ?>"
           <?= $isOnDashboard ? 'onclick="switchCategory(\'article-publication\', event)"' : '' ?>>
          <span class="nav-icon">📄</span>
          <span class="nav-text">Article Publication</span>
        </a>
        <button type="button" class="nav-toggle" onclick="eicToggleSubmenu('sub-article-publication', this)" aria-label="Toggle submenu">
          <span class="nav-toggle-icon"><?= $activeCategory === 'article-publication' ? '▾' : '▸' ?></span>
        </button>
      </div>
      <ul class="nav-submenu <?= $activeCategory === 'article-publication' ? 'is-open' : '' ?>" id="sub-article-publication">
        <li>
          <a class="nav-sublink <?= $currentPage === 'article_manage.php' && empty($_GET['status']) ? 'active' : '' ?>"
             href="<?= $basePrefix ?>article_manage.php">
            Article Management
          </a>
        </li>
        <li>
          <a class="nav-sublink <?= $currentPage === 'article_add.php' ? 'active' : '' ?>"
             href="<?= $basePrefix ?>article_add.php">
            Add New Article
          </a>
        </li>
        <li>
          <a class="nav-sublink <?= $currentPage === 'article_manage.php' && ($_GET['status'] ?? '') === 'PUBLISHED' ? 'active' : '' ?>"
             href="<?= $basePrefix ?>article_manage.php?status=PUBLISHED">
            Published Articles
          </a>
        </li>
        <li>
          <a class="nav-sublink <?= $currentPage === 'production.php' ? 'active' : '' ?>"
             href="<?= $basePrefix ?>production.php">
            Production Queue
          </a>
        </li>
        <li>
          <a class="nav-sublink <?= $currentPage === 'typesetting.php' ? 'active' : '' ?>"
             href="<?= $basePrefix ?>typesetting.php<?= $msQuery ?>">
            Typesetting
          </a>
        </li>
        <li>
          <a class="nav-sublink <?= $currentPage === 'galley_proof.php' ? 'active' : '' ?>"
             href="<?= $basePrefix ?>galley_proof.php<?= $msQuery ?>">
            Galley Proof
          </a>
        </li>
        <li>
          <a class="nav-sublink <?= $currentPage === 'publish.php' ? 'active' : '' ?>"
             href="<?= $basePrefix ?>publish.php<?= $msQuery ?>">
            Publish Article
          </a>
        </li>
      </ul>
    </li>

    <!-- CATEGORY 4: Editorial Management & Decision System -->
    <li class="nav-item <?= $activeCategory === 'editorial-decision' ? 'active-section' : '' ?>" id="item-editorial-decision">
      <div class="nav-link-row">
        <a class="nav-link <?= $activeCategory === 'editorial-decision' ? 'active' : '' ?>"
           id="tab-editorial-decision"
           role="tab"
           aria-selected="<?= $activeCategory === 'editorial-decision' ? 'true' : 'false' ?>"
           aria-controls="sec-editorial-decision"
           href="<?= $isOnDashboard ? '#editorial-decision' : $basePrefix . 'workflow_v1.php' ?>"
           <?= $isOnDashboard ? 'onclick="switchCategory(\'editorial-decision\', event)"' : '' ?>>
          <span class="nav-icon">⚙</span>
          <span class="nav-text">Editorial Management &amp; Decision System</span>
        </a>
        <button type="button" class="nav-toggle" onclick="eicToggleSubmenu('sub-editorial-decision', this)" aria-label="Toggle submenu">
          <span class="nav-toggle-icon"><?= $activeCategory === 'editorial-decision' ? '▾' : '▸' ?></span>
        </button>
      </div>
      <ul class="nav-submenu <?= $activeCategory === 'editorial-decision' ? 'is-open' : '' ?>" id="sub-editorial-decision">
        <li>
          <a class="nav-sublink <?= $currentPage === 'workflow_v1.php' ? 'active' : '' ?>"
             href="<?= $basePrefix ?>workflow_v1.php">
            Workflow V1 System
          </a>
        </li>
        <li>
          <a class="nav-sublink <?= $currentPage === 'reviewer_manage.php' ? 'active' : '' ?>"
             href="<?= $basePrefix ?>reviewer_manage.php">
            Reviewer Pool Management
          </a>
        </li>
        <li>
          <a class="nav-sublink <?= $currentPage === 'technical_check.php' ? 'active' : '' ?>"
             href="<?= $basePrefix ?>technical_check.php<?= $msQuery ?>">
            Technical Check
          </a>
        </li>
        <li>
          <a class="nav-sublink <?= $currentPage === 'assign_editor.php' ? 'active' : '' ?>"
             href="<?= $basePrefix ?>assign_editor.php<?= $msQuery ?>">
            Editor Assignment
          </a>
        </li>
        <li>
          <a class="nav-sublink <?= $currentPage === 'reviewers.php' ? 'active' : '' ?>"
             href="<?= $basePrefix ?>reviewers.php<?= $msQuery ?>">
            Reviewer Assignment
          </a>
        </li>
        <li>
          <a class="nav-sublink <?= $currentPage === 'decision.php' ? 'active' : '' ?>"
             href="<?= $basePrefix ?>decision.php<?= $msQuery ?>">
            Editorial Decision
          </a>
        </li>
        <li>
          <a class="nav-sublink <?= $currentPage === 'revision.php' ? 'active' : '' ?>"
             href="<?= $basePrefix ?>revision.php<?= $msQuery ?>">
            Revision Handling
          </a>
        </li>
        <li>
          <a class="nav-sublink <?= $currentPage === 'accept.php' ? 'active' : '' ?>"
             href="<?= $basePrefix ?>accept.php<?= $msQuery ?>">
            Final Acceptance
          </a>
        </li>
      </ul>
    </li>

  </ul>
</aside>
