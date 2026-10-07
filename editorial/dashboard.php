<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$u = login_required();
$role = (string)($u['role'] ?? '');

$routes = [
    'admin' => 'dashboard.php',
    'editor_in_chief' => 'dashboard.php',
    'editor' => 'dashboard.php',
    'managing_editor' => 'dashboard.php',
    'reviewer' => 'reviewer/index.php',
    'author' => 'author/index.php',
    'production' => 'dashboard.php'
];

if ($role !== 'editor_in_chief') {
    redirect($routes[$role] ?? 'author/index.php');
}

$pdo = db();

// Centralized status mapping for EIC dashboard
$statusMapping = [
    'new_submissions'   => ['submitted', 'new_submission', 'SUBMITTED'],
    'under_review'      => ['review', 'under_review', 'UNDER_REVIEW', 'second_review', 'SECOND_REVIEW'],
    'pending_decisions' => ['editorial_screening', 'EDITORIAL_SCREENING', 'reviewer_invitation', 'REVIEWER_INVITATION', 'decision', 'DECISION'],
    'revisions'         => ['minor_revision', 'major_revision', 'revision', 'REVISION_REQUIRED', 'revision_required', 'REVISED_SUBMISSION', 'revised_submission'],
    'accepted'          => ['accepted', 'ACCEPTED'],
    'published'         => ['published', 'PUBLISHED']
];

// Safe query counter with fallback to 0
function eic_safe_count(PDO $db, string $sql, array $params = []): int {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

// 8 Primary KPI Metric Cards
$countNew              = eic_safe_count($pdo, "SELECT COUNT(*) FROM manuscripts WHERE status IN ('submitted', 'new_submission', 'SUBMITTED')");
$countReview           = eic_safe_count($pdo, "SELECT COUNT(*) FROM manuscripts WHERE status IN ('review', 'under_review', 'UNDER_REVIEW', 'second_review', 'SECOND_REVIEW')");
$countPendingDecisions = eic_safe_count($pdo, "SELECT COUNT(*) FROM manuscripts WHERE status IN ('editorial_screening', 'EDITORIAL_SCREENING', 'reviewer_invitation', 'REVIEWER_INVITATION', 'decision', 'DECISION')");
$countRevisions        = eic_safe_count($pdo, "SELECT COUNT(*) FROM manuscripts WHERE status IN ('minor_revision', 'major_revision', 'revision', 'REVISION_REQUIRED', 'revision_required', 'REVISED_SUBMISSION', 'revised_submission')");
$countAccepted         = eic_safe_count($pdo, "SELECT COUNT(*) FROM manuscripts WHERE status IN ('accepted', 'ACCEPTED')");
$countPublished        = eic_safe_count($pdo, "SELECT COUNT(*) FROM manuscripts WHERE status IN ('published', 'PUBLISHED')");
$countCurrentIssues    = eic_safe_count($pdo, "SELECT COUNT(*) FROM issues");
$countActiveReviewers  = eic_safe_count($pdo, "SELECT COUNT(DISTINCT reviewer_id) FROM ew_reviewer_assignments WHERE status IN ('accepted', 'in_review')");

// Reviewer Activity Section Metrics
$countInvitationsSent     = eic_safe_count($pdo, "SELECT COUNT(*) FROM ew_reviewer_assignments WHERE status IN ('invited', 'accepted', 'declined', 'in_review', 'completed')");
$countInvitationsAccepted = eic_safe_count($pdo, "SELECT COUNT(*) FROM ew_reviewer_assignments WHERE status IN ('accepted', 'in_review', 'completed')");
$countReviewsSubmitted    = eic_safe_count($pdo, "SELECT COUNT(*) FROM ew_peer_reviews WHERE submitted_at IS NOT NULL");
$countReviewsPending      = eic_safe_count($pdo, "SELECT COUNT(*) FROM ew_reviewer_assignments WHERE status IN ('accepted', 'in_review')");

// Current Issue data
$currentIssueData = null;
try {
    $stmtIssue = $pdo->query("SELECT * FROM issues ORDER BY id DESC LIMIT 1");
    $currentIssueData = $stmtIssue->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) {
    $currentIssueData = null;
}

// Recent Activity data (meaningful editorial audit logs)
$recentActivityList = [];
try {
    $stmtAct = $pdo->query("
        SELECT action, details, created_at, user_id, manuscript_id 
        FROM audit_log 
        WHERE action NOT IN ('LOGIN', 'LOGOUT') 
        ORDER BY id DESC 
        LIMIT 8
    ");
    $recentActivityList = $stmtAct->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $recentActivityList = [];
}

// Fetch non-published manuscripts list (Full Manuscript Queue: all active/non-published manuscripts)
$manuscripts = [];
try {
    $stmtMs = $pdo->query("
        SELECT m.*, u.full_name AS author_name, u.email AS author_email,
               (SELECT COUNT(*) FROM ew_reviewer_assignments ra WHERE ra.manuscript_id = m.id AND ra.status <> 'cancelled') AS reviewer_count
        FROM manuscripts m
        LEFT JOIN users u ON u.id = m.corresponding_author_id
        WHERE m.status NOT IN ('PUBLISHED', 'published')
        ORDER BY m.submitted_at DESC
    ");
    $manuscripts = $stmtMs->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $manuscripts = [];
}
$basePrefix = defined('BASE_URL') ? BASE_URL : '/editorial/';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>AJSMR | Editor-in-Chief Dashboard</title>
<style>
*{box-sizing:border-box}
body{
  margin:0;
  padding-top:132px;
  padding-bottom:38px;
  background:#f8fafc;
  color:#1e293b;
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
  min-height:100vh;
}

/* Fixed Footer */
.eic-fixed-footer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 38px;
  z-index: 1000;
  background: #ffffff;
  color: #64748b;
  border-top: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  padding: 0 16px;
  box-sizing: border-box;
}

/* Two-Panel Layout */
.dashboard-layout {
  display: flex;
  min-height: calc(100vh - 170px);
  width: 100%;
  box-sizing: border-box;
}

/* Right Content Area */
.right-panel {
  flex: 1;
  min-width: 0;
  margin-left: 275px;
  padding: 24px 32px 36px 32px;
  box-sizing: border-box;
  background: #f8fafc;
}

.content-section {
  display: none;
}
.content-section.active {
  display: block;
  animation: panelFade 0.2s ease-in-out;
}
@keyframes panelFade {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Hero / Welcome Banner */
.welcome-hero {
  background: linear-gradient(135deg, #ffffff 0%, #f0f6fc 100%);
  border: 1px solid #dce7f3;
  border-radius: 12px;
  padding: 24px 28px;
  margin-bottom: 24px;
  box-shadow: 0 4px 18px rgba(16, 32, 64, 0.04);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}
.welcome-hero h1 {
  margin: 0 0 6px 0;
  font-size: 22px;
  color: #092b5f;
  font-weight: 800;
}
.welcome-hero p {
  margin: 0;
  color: #475569;
  font-size: 13.5px;
  line-height: 1.5;
}

/* Stats Summary */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 26px;
}
.stat-card {
  background: #fff;
  border: 1px solid #e3e9f1;
  border-radius: 10px;
  padding: 16px 20px;
  box-shadow: 0 4px 15px rgba(16, 32, 64, 0.04);
  border-left: 4px solid #0b5fa5;
}
.stat-label {
  font-size: 11.5px;
  color: #64748b;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.stat-num {
  font-size: 28px;
  font-weight: 800;
  color: #092b5f;
  margin-top: 4px;
}

/* Section Headings */
.section-banner {
  background: #fff;
  border: 1px solid #e3e9f1;
  border-radius: 10px;
  padding: 18px 24px;
  margin-bottom: 20px;
  box-shadow: 0 3px 12px rgba(16, 32, 64, 0.04);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  border-left: 4px solid #0b5fa5;
}
.section-banner-title {
  font-size: 19px;
  font-weight: 800;
  color: #092b5f;
  margin: 0 0 4px;
}
.section-banner-desc {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

/* Category Options Grid */
.category-options-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 18px;
  margin-bottom: 26px;
}
.nav-action-card {
  background: #ffffff;
  border: 1px solid #e3e9f1;
  border-radius: 11px;
  padding: 20px;
  box-shadow: 0 4px 16px rgba(16, 32, 64, 0.04);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
  border-top: 3px solid #0b5fa5;
}
.nav-action-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(16, 32, 64, 0.08);
  border-color: #cbd5e1;
  border-top-color: #092b5f;
}
.nav-action-card-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 10px;
}
.nav-action-card-icon {
  font-size: 24px;
  line-height: 1;
  flex-shrink: 0;
  padding: 6px;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}
.nav-action-card-title {
  margin: 0;
  font-size: 15.5px;
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
  font-size: 12.5px;
  font-weight: 700;
  padding: 9px 14px;
  border-radius: 6px;
  border: 0;
  cursor: pointer;
  transition: background 0.15s ease;
  width: 100%;
  box-sizing: border-box;
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

/* Category Hub Card (Overview State) */
.hub-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 18px;
  margin-bottom: 28px;
}
.hub-card {
  background: #ffffff;
  border: 1px solid #e3e9f1;
  border-radius: 11px;
  padding: 22px;
  box-shadow: 0 4px 16px rgba(16, 32, 64, 0.04);
  border-left: 4px solid #0b5fa5;
  cursor: pointer;
  transition: all 0.15s ease;
}
.hub-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(16, 32, 64, 0.08);
  border-left-color: #092b5f;
}
.hub-card-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}
.hub-card-icon {
  font-size: 26px;
  line-height: 1;
}
.hub-card-title {
  margin: 0;
  font-size: 16.5px;
  font-weight: 800;
  color: #092b5f;
}
.hub-card-desc {
  margin: 0 0 14px 0;
  font-size: 13px;
  color: #64748b;
  line-height: 1.5;
}
.hub-card-action {
  font-size: 12.5px;
  font-weight: 700;
  color: #0b5fa5;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

/* Table Panel */
.table-panel {
  background: #fff;
  border: 1px solid #e3e9f1;
  border-radius: 11px;
  box-shadow: 0 4px 16px rgba(16, 32, 64, 0.04);
  overflow-x: auto;
  margin-bottom: 24px;
}
.table-panel-header {
  padding: 16px 20px;
  border-bottom: 1px solid #eef2f6;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.table-panel-header h3 {
  margin: 0;
  font-size: 15px;
  font-weight: 800;
  color: #092b5f;
}
table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}
th, td {
  padding: 12px 14px;
  border-bottom: 1px solid #eef2f6;
  font-size: 13px;
  vertical-align: middle;
}
th {
  background: #f8fafc;
  color: #475569;
  font-weight: 700;
  font-size: 11.5px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
tr:hover {
  background: #f8fafc;
}
.badge {
  display: inline-block;
  padding: 4px 9px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 700;
  background: #eef2f6;
  color: #334155;
}
.badge.submitted { background: #e0f2fe; color: #0369a1; }
.badge.review, .badge.under_review { background: #fef3c7; color: #92400e; }
.badge.accepted { background: #dcfce7; color: #166534; }
.badge.rejected { background: #fee2e2; color: #991b1b; }
.act-btn {
  display: inline-block;
  padding: 5px 10px;
  border-radius: 5px;
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
  background: #eaf2f9;
  color: #0b5fa5;
}
.act-btn:hover { background: #d4e6f6; }

/* Buttons */
.btn {
  display: inline-block;
  background: #0b5fa5;
  color: #fff;
  border: 0;
  border-radius: 7px;
  padding: 9px 15px;
  text-decoration: none;
  font-weight: 700;
  font-size: 12.5px;
  letter-spacing: 0.3px;
  transition: background 0.15s;
  text-align: center;
  cursor: pointer;
}
.btn:hover { background: #084980; }
.btn.light { background: #eaf2f9; color: #0b5fa5; border: 1px solid #c8dff0; }
.btn.light:hover { background: #d5e7f5; }

/* 8 Dashboard KPI Cards */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 26px;
}
.kpi-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 16px 18px;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(11, 95, 165, 0.08);
  border-color: #0b5fa5;
}
.kpi-card-top {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
}
.kpi-card-icon {
  font-size: 18px;
  line-height: 1;
}
.kpi-card-title {
  font-size: 13px;
  font-weight: 700;
  color: #0f284e;
  line-height: 1.2;
}
.kpi-card-val {
  font-size: 28px;
  font-weight: 800;
  color: #0b5fa5;
  margin: 3px 0 2px 0;
  line-height: 1.1;
}
.kpi-card-desc {
  font-size: 11.5px;
  color: #64748b;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Dashboard Section Container Blocks */
.dashboard-block {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 11px;
  padding: 22px 24px;
  margin-bottom: 24px;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
}
.dashboard-block-head {
  margin-bottom: 16px;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 12px;
}
.dashboard-block-title {
  font-size: 16px;
  font-weight: 800;
  color: #092b5f;
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}
.dashboard-block-subtitle {
  font-size: 12.5px;
  color: #64748b;
  display: block;
}
.block-icon {
  font-size: 18px;
  line-height: 1;
}

/* 5-Column Workflow & Publication Action Grids */
.workflow-actions-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 14px;
}
.workflow-action-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 9px;
  padding: 16px 14px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: border-color 0.15s, background-color 0.15s;
}
.workflow-action-card:hover {
  border-color: #0b5fa5;
  background: #ffffff;
}
.workflow-action-icon {
  font-size: 22px;
  margin-bottom: 8px;
  line-height: 1;
}
.workflow-action-title {
  font-size: 13.5px;
  font-weight: 700;
  color: #092b5f;
  margin: 0 0 6px 0;
  line-height: 1.25;
}
.workflow-action-desc {
  font-size: 11.5px;
  color: #64748b;
  margin: 0 0 14px 0;
  line-height: 1.45;
  min-height: 48px;
}
.workflow-action-btn {
  display: inline-block;
  background: #0b5fa5;
  color: #ffffff !important;
  font-size: 11px;
  font-weight: 700;
  text-decoration: none;
  text-align: center;
  padding: 8px 10px;
  border-radius: 5px;
  letter-spacing: 0.3px;
  transition: background 0.15s;
  width: 100%;
  box-sizing: border-box;
}
.workflow-action-btn:hover {
  background: #084980;
}

/* Reviewer Activity Grid (5 Cards) */
.reviewer-activity-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 14px;
}
.activity-metric-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 9px;
  padding: 16px 14px;
  text-align: center;
  transition: border-color 0.15s, background 0.15s;
}
.activity-metric-card:hover {
  border-color: #cbd5e1;
  background: #ffffff;
}
.activity-metric-label {
  font-size: 11.5px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-bottom: 4px;
}
.activity-metric-val {
  font-size: 26px;
  font-weight: 800;
  color: #092b5f;
  margin: 4px 0;
  line-height: 1.1;
}
.activity-metric-sub {
  font-size: 11px;
  color: #94a3b8;
}

/* Bottom Dual Grid (Current Issue & Recent Activity) */
.bottom-dual-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 24px;
}
.bottom-card-panel {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 11px;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
  display: flex;
  flex-direction: column;
}
.bottom-card-header {
  padding: 14px 20px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.bottom-card-title {
  font-size: 15px;
  font-weight: 800;
  color: #092b5f;
  display: flex;
  align-items: center;
  gap: 8px;
}
.bottom-card-body {
  padding: 20px;
  flex: 1;
}
.empty-state-box {
  padding: 28px 16px;
  text-align: center;
  color: #64748b;
}
.empty-state-icon {
  font-size: 28px;
  margin-bottom: 8px;
  opacity: 0.6;
}
.empty-state-title {
  font-size: 14.5px;
  font-weight: 700;
  color: #334155;
  margin: 0 0 6px 0;
}
.empty-state-desc {
  font-size: 12px;
  color: #64748b;
  margin: 0;
  line-height: 1.45;
  max-width: 320px;
  margin: auto;
}

/* Activity Timeline */
.activity-timeline {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.activity-item {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  font-size: 12.5px;
}
.activity-bullet {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #0b5fa5;
  margin-top: 5px;
  flex-shrink: 0;
}
.activity-action-text {
  font-weight: 700;
  color: #0f284e;
}
.activity-details-text {
  color: #64748b;
  font-size: 11.5px;
  margin-top: 2px;
}
.activity-time-text {
  color: #94a3b8;
  font-size: 10.5px;
  margin-top: 2px;
}

/* Responsive */
@media (max-width: 1200px) {
  .workflow-actions-grid {
    grid-template-columns: repeat(3, 1fr);
  }
  .reviewer-activity-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}
@media (max-width: 1100px) {
  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
@media (max-width: 960px) {
  body {
    padding-bottom: 0;
  }
  .eic-fixed-footer {
    position: static;
  }
  .dashboard-layout {
    flex-direction: column;
  }
  .right-panel {
    margin-left: 0;
    padding: 16px;
  }
  .bottom-dual-grid {
    grid-template-columns: 1fr;
  }
  .workflow-actions-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .reviewer-activity-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .category-options-grid {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 600px) {
  .kpi-grid {
    grid-template-columns: 1fr;
  }
  .workflow-actions-grid {
    grid-template-columns: 1fr;
  }
  .reviewer-activity-grid {
    grid-template-columns: 1fr;
  }
}
</style>
</head>
<body>

<!-- Fixed Redesigned EIC Top Header (AJSMR Public Journal Branding) -->
<?php
require_once __DIR__ . '/includes/eic_layout.php';
eic_render_top_bar('Editor-in-Chief Administration Portal');
?>

<!-- Main Layout Shell -->
<div class="dashboard-layout">

  <!-- LEFT PANEL: EIC Shared Navigation (Top-Level Categories Only) -->
  <?php include __DIR__ . '/includes/eic_sidebar.php'; ?>

  <!-- RIGHT PANEL: Content Area -->
  <main class="right-panel" id="main-content">

    <!-- ========================================== -->
    <!-- INITIAL CONTENT: Clean Welcome / Dashboard -->
    <!-- ========================================== -->
    <section id="sec-overview" class="content-section active" role="tabpanel" aria-labelledby="tab-overview">
      <div class="welcome-hero">
        <div>
          <h1>Editor-in-Chief Dashboard</h1>
          <p>AJSMR Editorial Management Control Center &bull; Live operational metrics and publication workflow</p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
          <a class="btn light" href="workflow_v1.php">Workflow V1 Engine →</a>
          <a class="btn" href="new_submissions.php">Manuscript Queue →</a>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- SECTION 3: 8 Primary KPI Cards                                 -->
      <!-- ============================================================== -->
      <div class="kpi-grid">
        <!-- A. New Submissions -->
        <div class="kpi-card" onclick="switchCategory('manuscripts', event)" role="button" tabindex="0">
          <div class="kpi-card-top">
            <span class="kpi-card-icon">📥</span>
            <span class="kpi-card-title">New Submissions</span>
          </div>
          <div class="kpi-card-val"><?= $countNew ?></div>
          <div class="kpi-card-desc">Awaiting editorial processing</div>
        </div>

        <!-- B. Under Review -->
        <div class="kpi-card" onclick="location.href='workflow_v1.php'" role="button" tabindex="0">
          <div class="kpi-card-top">
            <span class="kpi-card-icon">🔍</span>
            <span class="kpi-card-title">Under Review</span>
          </div>
          <div class="kpi-card-val"><?= $countReview ?></div>
          <div class="kpi-card-desc">Active peer review workflows</div>
        </div>

        <!-- C. Pending Decisions -->
        <div class="kpi-card" onclick="location.href='workflow_v1.php'" role="button" tabindex="0">
          <div class="kpi-card-top">
            <span class="kpi-card-icon">⚖</span>
            <span class="kpi-card-title">Pending Decisions</span>
          </div>
          <div class="kpi-card-val"><?= $countPendingDecisions ?></div>
          <div class="kpi-card-desc">Awaiting editorial determination</div>
        </div>

        <!-- D. Revisions -->
        <div class="kpi-card" onclick="location.href='workflow_v1.php'" role="button" tabindex="0">
          <div class="kpi-card-top">
            <span class="kpi-card-icon">🔄</span>
            <span class="kpi-card-title">Revisions</span>
          </div>
          <div class="kpi-card-val"><?= $countRevisions ?></div>
          <div class="kpi-card-desc">Author revisions pending</div>
        </div>

        <!-- E. Accepted -->
        <div class="kpi-card" onclick="location.href='workflow_v1.php'" role="button" tabindex="0">
          <div class="kpi-card-top">
            <span class="kpi-card-icon">✅</span>
            <span class="kpi-card-title">Accepted</span>
          </div>
          <div class="kpi-card-val"><?= $countAccepted ?></div>
          <div class="kpi-card-desc">Approved for publication</div>
        </div>

        <!-- F. Published Articles -->
        <div class="kpi-card" style="cursor:default;">
          <div class="kpi-card-top">
            <span class="kpi-card-icon">📰</span>
            <span class="kpi-card-title">Published Articles</span>
          </div>
          <div class="kpi-card-val"><?= $countPublished ?></div>
          <div class="kpi-card-desc">Live in journal archives</div>
        </div>

        <!-- G. Current Issues -->
        <div class="kpi-card" onclick="location.href='issue_years.php'" role="button" tabindex="0">
          <div class="kpi-card-top">
            <span class="kpi-card-icon">📚</span>
            <span class="kpi-card-title">Current Issues</span>
          </div>
          <div class="kpi-card-val"><?= $countCurrentIssues ?></div>
          <div class="kpi-card-desc">Configured volume &amp; issues</div>
        </div>

        <!-- H. Active Reviewers -->
        <div class="kpi-card" onclick="location.href='reviewer_manage.php'" role="button" tabindex="0">
          <div class="kpi-card-top">
            <span class="kpi-card-icon">👥</span>
            <span class="kpi-card-title">Active Reviewers</span>
          </div>
          <div class="kpi-card-val"><?= $countActiveReviewers ?></div>
          <div class="kpi-card-desc">Engaged in active reviews</div>
        </div>
      </div>

    </section>


    <!-- ============================================================== -->
    <!-- CATEGORY 1: Manuscript Queue & New Submissions                 -->
    <!-- ============================================================== -->
    <section id="sec-manuscripts" class="content-section" role="tabpanel" aria-labelledby="tab-manuscripts">
      <div class="section-banner">
        <div>
          <h2 class="section-banner-title">Manuscript Queue &amp; New Submissions</h2>
          <p class="section-banner-desc">Showing active submissions from author portal with live review and screening status.</p>
        </div>
      </div>

      <!-- Navigation Options / Cards -->
      <div class="category-options-grid">
        <!-- 1. Queue Overview -->
        <div class="nav-action-card">
          <div class="nav-action-card-header">
            <span class="nav-action-card-icon">📋</span>
            <div>
              <h3 class="nav-action-card-title">Queue Overview</h3>
            </div>
          </div>
          <p class="nav-action-card-desc">Review the full editorial queue summary, live manuscript stages, and tracking status.</p>
          <button type="button" class="nav-action-card-btn" onclick="document.getElementById('queue-table-section').scrollIntoView({behavior:'smooth'})">VIEW QUEUE OVERVIEW ↓</button>
        </div>

        <!-- 2. All Published Manuscripts -->
        <div class="nav-action-card" onclick="location.href='published_manuscripts.php'" style="cursor:pointer;">
          <div class="nav-action-card-header">
            <span class="nav-action-card-icon">📚</span>
            <div>
              <h3 class="nav-action-card-title">All Published Manuscripts</h3>
            </div>
          </div>
          <p class="nav-action-card-desc">View all manuscripts that have been officially published.</p>
          <a class="nav-action-card-btn" href="published_manuscripts.php">ALL PUBLISHED MANUSCRIPTS →</a>
        </div>
      </div>

      <!-- Live Queue Table -->
      <div class="table-panel" id="queue-table-section">
        <div class="table-panel-header">
          <h3>Full Manuscript Queue</h3>
          <span style="font-size:13px;color:#64748b;">Total: <?=count($manuscripts)?> manuscripts</span>
        </div>
        <?php if (empty($manuscripts)): ?>
          <div style="padding:40px 20px;text-align:center;color:#64748b;">
            <p style="font-size:15px;margin:0 0 8px 0;font-weight:600;">No manuscripts found in the editorial queue.</p>
            <span style="font-size:13px;">New manuscripts submitted by authors will automatically appear here.</span>
          </div>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th style="width:45px;">S.No</th>
                <th>Paper ID</th>
                <th>Title</th>
                <th>Author</th>
                <th>Article Type</th>
                <th>Submitted Date</th>
                <th>Status</th>
                <th>Reviewers</th>
                <th style="min-width:80px;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($manuscripts as $idx => $r): ?>
                <tr>
                  <td><?=($idx + 1)?></td>
                  <td><strong><?=e($r['manuscript_no'])?></strong></td>
                  <td style="max-width:280px;font-weight:500;">
                    <a href="manuscript_view.php?id=<?=(int)$r['id']?>" style="color:#0f172a;text-decoration:none;">
                      <?=e($r['title'])?>
                    </a>
                  </td>
                  <td>
                    <strong><?=e($r['author_name'] ?: 'Author')?></strong><br>
                    <small style="color:#64748b;"><?=e($r['author_email'] ?: $r['corresponding_email'])?></small>
                  </td>
                  <td><?=e($r['article_type'] ?: 'Article')?></td>
                  <td><?=date('d M Y', strtotime((string)$r['submitted_at']))?></td>
                  <td>
                    <span class="badge <?=e(strtolower($r['status']))?>"><?=e(slabel($r['status']))?></span>
                  </td>
                  <td>
                    <?php if ((int)$r['reviewer_count'] > 0): ?>
                      <span class="badge" style="background:#e0e7ff;color:#3730a3;"><?=(int)$r['reviewer_count']?> Assigned</span>
                    <?php else: ?>
                      <span style="color:#94a3b8;font-size:12px;">None</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a class="act-btn" href="manuscript_view.php?id=<?=(int)$r['id']?>">View</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </section>

    <!-- ============================================================== -->
    <!-- CATEGORY 2: Current Issue Management                           -->
    <!-- ============================================================== -->
    <section id="sec-current-issue" class="content-section" role="tabpanel" aria-labelledby="tab-current-issue">
      <div class="section-banner">
        <div>
          <h2 class="section-banner-title">Current Issue Management</h2>
          <p class="section-banner-desc">Manage AJSMR issues, volumes, published current issue content, and public presentation.</p>
        </div>
      </div>

      <!-- Navigation Options / Cards -->
      <div class="category-options-grid">
        <!-- 1. Issue Years & Issues -->
        <div class="nav-action-card">
          <div class="nav-action-card-header">
            <span class="nav-action-card-icon">📅</span>
            <div>
              <h3 class="nav-action-card-title">Issue Years &amp; Issues</h3>
            </div>
          </div>
          <p class="nav-action-card-desc">Create, organize and manage journal issues, volumes and publishing periods for archive and current stream.</p>
          <a class="nav-action-card-btn" href="issue_years.php">MANAGE ISSUES →</a>
        </div>

        <!-- 2. Current Issue List -->
        <div class="nav-action-card">
          <div class="nav-action-card-header">
            <span class="nav-action-card-icon">📚</span>
            <div>
              <h3 class="nav-action-card-title">Current Issue List</h3>
            </div>
          </div>
          <p class="nav-action-card-desc">View, filter, search, edit and manage articles currently published in journal issues.</p>
          <a class="nav-action-card-btn" href="current_issue_list.php">CURRENT ISSUE LIST →</a>
        </div>

        <!-- 3. Add Current Issue -->
        <div class="nav-action-card">
          <div class="nav-action-card-header">
            <span class="nav-action-card-icon">➕</span>
            <div>
              <h3 class="nav-action-card-title">Add Current Issue</h3>
            </div>
          </div>
          <p class="nav-action-card-desc">Upload and publish articles into current or selected issue with full PDF, abstract PDF, and metadata.</p>
          <a class="nav-action-card-btn" href="current_issue_add.php">ADD CURRENT ISSUE →</a>
        </div>
      </div>
    </section>

    <!-- ============================================================== -->
    <!-- CATEGORY 3: Article Publication                                -->
    <!-- ============================================================== -->
    <section id="sec-article-publication" class="content-section" role="tabpanel" aria-labelledby="tab-article-publication">
      <div class="section-banner">
        <div>
          <h2 class="section-banner-title">Article Publication</h2>
          <p class="section-banner-desc">Manage published AJSMR articles, metadata, and journal publication catalog.</p>
        </div>
      </div>

      <!-- Navigation Options / Cards -->
      <div class="category-options-grid">
        <!-- 1. Article Management -->
        <div class="nav-action-card">
          <div class="nav-action-card-header">
            <span class="nav-action-card-icon">📋</span>
            <div>
              <h3 class="nav-action-card-title">Article Management</h3>
            </div>
          </div>
          <p class="nav-action-card-desc">Search articles, check their status, edit article information and open published articles.</p>
          <a class="nav-action-card-btn" href="article_manage.php">MANAGE ARTICLES →</a>
        </div>

        <!-- 2. Add New Article -->
        <div class="nav-action-card">
          <div class="nav-action-card-header">
            <span class="nav-action-card-icon">➕</span>
            <div>
              <h3 class="nav-action-card-title">Add New Article</h3>
            </div>
          </div>
          <p class="nav-action-card-desc">Enter article details, authors, publication information, references and publication files.</p>
          <a class="nav-action-card-btn" href="article_add.php">ADD ARTICLE →</a>
        </div>
      </div>
    </section>

    <!-- ============================================================== -->
    <!-- CATEGORY 4: Editorial Management & Decision System             -->
    <!-- ============================================================== -->
    <section id="sec-editorial-decision" class="content-section" role="tabpanel" aria-labelledby="tab-editorial-decision">
      <div class="section-banner">
        <div>
          <h2 class="section-banner-title">Editorial Management &amp; Decision System</h2>
          <p class="section-banner-desc">Manage peer review workflows, reviewer pools, reviewer reports, and editorial decisions.</p>
        </div>
      </div>

      <!-- Navigation Options / Cards -->
      <div class="category-options-grid">
        <!-- Reviewer Pool Management -->
        <div class="nav-action-card">
          <div class="nav-action-card-header">
            <span class="nav-action-card-icon">👥</span>
            <div>
              <h3 class="nav-action-card-title">Reviewer Management</h3>
            </div>
          </div>
          <p class="nav-action-card-desc">Search and manage qualified peer reviewers, review assignments, and subject specializations.</p>
          <a class="nav-action-card-btn" href="reviewer_manage.php">MANAGE REVIEWERS →</a>
        </div>
      </div>
    </section>

  </main>
</div>

<!-- Fixed Bottom Footer -->
<footer class="eic-fixed-footer">
  AJSMR Editorial Management System V1 &copy; <?=date('Y')?> &bull; Editor-in-Chief Administration Portal
</footer>

<script>
/**
 * Switch active category in the EIC 3-section layout.
 * Highlights the category in the left sidebar and displays its options/cards in the content area.
 */
function switchCategory(catId, event) {
  if (event) {
    event.preventDefault();
  }

  var validCats = ['overview', 'manuscripts', 'current-issue', 'article-publication', 'editorial-decision'];
  if (!catId || validCats.indexOf(catId) === -1) {
    catId = 'overview';
  }

  var categories = ['overview', 'manuscripts', 'current-issue', 'article-publication', 'editorial-decision'];

  // Deactivate all sidebar items
  categories.forEach(function(key) {
    var itemEl = document.getElementById('item-' + key);
    var tabEl = document.getElementById('tab-' + key);
    if (itemEl) itemEl.classList.remove('active-section');
    if (tabEl) {
      tabEl.classList.remove('active');
      tabEl.setAttribute('aria-selected', 'false');
    }
  });

  // Hide all content sections
  var allSections = document.querySelectorAll('.content-section');
  allSections.forEach(function(sec) {
    sec.classList.remove('active');
  });

  // Activate selected sidebar category
  var activeItem = document.getElementById('item-' + catId);
  var activeTab = document.getElementById('tab-' + catId);
  if (activeItem) activeItem.classList.add('active-section');
  if (activeTab) {
    activeTab.classList.add('active');
    activeTab.setAttribute('aria-selected', 'true');
  }

  // Activate corresponding content section
  var targetSec = document.getElementById('sec-' + catId);
  if (targetSec) {
    targetSec.classList.add('active');
  }

  // Update URL hash cleanly
  if (catId === 'overview') {
    if (history.replaceState) {
      history.replaceState(null, null, window.location.pathname);
    }
  } else {
    if (history.replaceState) {
      history.replaceState(null, null, '#' + catId);
    } else {
      location.hash = '#' + catId;
    }
  }

  // Smoothly scroll content area to top
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Support direct loading via URL hash (e.g. dashboard.php#current-issue)
window.addEventListener('DOMContentLoaded', function() {
  var hash = window.location.hash.replace('#', '');
  if (hash && ['manuscripts', 'current-issue', 'article-publication', 'editorial-decision'].indexOf(hash) !== -1) {
    switchCategory(hash);
  } else {
    switchCategory('overview');
  }
});

// Support back/forward browser navigation
window.addEventListener('hashchange', function() {
  var hash = window.location.hash.replace('#', '');
  if (hash && ['manuscripts', 'current-issue', 'article-publication', 'editorial-decision'].indexOf(hash) !== -1) {
    switchCategory(hash);
  } else {
    switchCategory('overview');
  }
});
</script>
</body>
</html>