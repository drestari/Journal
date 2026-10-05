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

// Dynamic live counts from manuscripts table
$countNew = (int)$pdo->query("SELECT COUNT(*) FROM manuscripts WHERE status IN ('submitted', 'new_submission', 'SUBMITTED')")->fetchColumn();
$countReview = (int)$pdo->query("SELECT COUNT(*) FROM manuscripts WHERE status IN ('review', 'under_review', 'UNDER_REVIEW')")->fetchColumn();
$countRevisions = (int)$pdo->query("SELECT COUNT(*) FROM manuscripts WHERE status IN ('minor_revision', 'major_revision', 'revision', 'REVISION_REQUIRED')")->fetchColumn();
$countTotal = (int)$pdo->query("SELECT COUNT(*) FROM manuscripts")->fetchColumn();

// Fetch manuscripts list (most recent first)
$stmtMs = $pdo->query("
    SELECT m.*, u.full_name AS author_name, u.email AS author_email,
           (SELECT COUNT(*) FROM ew_reviewer_assignments ra WHERE ra.manuscript_id = m.id AND ra.status <> 'cancelled') AS reviewer_count
    FROM manuscripts m
    LEFT JOIN users u ON u.id = m.corresponding_author_id
    ORDER BY m.submitted_at DESC
");
$manuscripts = $stmtMs->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>AJSMR | Editor-in-Chief Dashboard</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#f4f7fb;color:#25344a;font-family:Arial,Helvetica,sans-serif}

/* Top Header */
.top{background:linear-gradient(135deg,#092b5f,#0b5fa5);color:#fff;padding:20px 30px}
.top-inner{max-width:1350px;margin:auto;display:flex;justify-content:space-between;align-items:center;gap:20px}
.brand{font-size:22px;font-weight:800}
.brand small{display:block;font-size:12px;font-weight:400;margin-top:4px;color:#dbeafe}
.top-actions{display:flex;align-items:center;gap:12px}
.user-badge{font-size:13px;color:#dbeafe;background:rgba(255,255,255,0.12);padding:6px 12px;border-radius:6px;border:1px solid rgba(255,255,255,0.2)}
.header-btn{color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,0.35);border-radius:7px;padding:8px 14px;font-size:13px;font-weight:600;transition:background 0.2s}
.header-btn:hover{background:rgba(255,255,255,0.15)}

/* Main Container */
.wrap{max-width:1350px;margin:24px auto;padding:0 20px}

/* Welcome Section */
.welcome{margin-bottom:20px}
.welcome h1{margin:0 0 6px;font-size:26px;color:#092b5f}
.welcome p{margin:0;color:#64748b;font-size:14px}

/* Stats Summary */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
.stat-card{background:#fff;border:1px solid #e3e9f1;border-radius:10px;padding:16px 20px;box-shadow:0 4px 15px rgba(16,32,64,0.04)}
.stat-label{font-size:12px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:0.5px}
.stat-num{font-size:28px;font-weight:800;color:#0b5fa5;margin-top:4px}

/* Two-Panel Layout */
.dashboard-layout{display:flex;gap:24px;align-items:flex-start}

/* Left Panel */
.left-panel{width:300px;flex-shrink:0;position:sticky;top:20px;background:#fff;border:1px solid #e3e9f1;border-radius:12px;box-shadow:0 4px 18px rgba(16,32,64,0.06);overflow:hidden}
.panel-header{background:#092b5f;color:#fff;padding:16px 18px}
.panel-header h3{margin:0;font-size:13px;font-weight:800;letter-spacing:0.8px;text-transform:uppercase}
.panel-header p{margin:4px 0 0;font-size:11px;color:#cbd5e1}

.nav-menu{list-style:none;margin:0;padding:10px 0}
.nav-item{margin:2px 8px}
.nav-link{display:flex;align-items:center;gap:12px;padding:14px 14px;color:#334155;text-decoration:none;border-radius:8px;font-size:13.5px;font-weight:600;line-height:1.35;transition:all 0.15s ease;border-left:4px solid transparent;cursor:pointer}
.nav-link:hover{background:#f8fafc;color:#0b5fa5}
.nav-link.active{background:#eef6fc;color:#092b5f;border-left-color:#0b5fa5;font-weight:700}
.nav-icon{font-size:18px;line-height:1;flex-shrink:0}
.nav-text{flex:1}
.nav-badge{background:#0b5fa5;color:#fff;font-size:11px;font-weight:700;padding:2px 7px;border-radius:10px;margin-left:auto}

/* Right Panel */
.right-panel{flex:1;min-width:0}
.content-section{display:none}
.content-section.active{display:block;animation:panelFade 0.2s ease-in-out}
@keyframes panelFade{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}

/* Section Headings */
.section-banner{background:#fff;border:1px solid #e3e9f1;border-radius:10px;padding:18px 22px;margin-bottom:18px;box-shadow:0 3px 12px rgba(16,32,64,0.04);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
.section-banner-title{font-size:19px;font-weight:800;color:#092b5f;margin:0 0 4px}
.section-banner-desc{font-size:13px;color:#64748b;margin:0}

/* Cards & Grid */
.grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.card{background:#fff;border:1px solid #e3e9f1;border-radius:11px;box-shadow:0 6px 20px rgba(16,32,64,0.06);padding:22px;display:flex;flex-direction:column;justify-content:space-between}
.card-top{margin-bottom:16px}
.card h2{margin:0 0 8px;font-size:16.5px;color:#092b5f;display:flex;align-items:center;gap:8px}
.card p{margin:0;color:#64748b;font-size:13px;line-height:1.55}
.card-actions{margin-top:auto}

.article-card{border-top:4px solid #0b5fa5}
.article-card.accent{border-top:4px solid #092b5f}

/* Buttons */
.btn{display:inline-block;background:#0b5fa5;color:#fff;border:0;border-radius:7px;padding:10px 16px;text-decoration:none;font-weight:700;font-size:12.5px;letter-spacing:0.3px;transition:background 0.15s;text-align:center}
.btn:hover{background:#084980}
.btn.light{background:#eaf2f9;color:#0b5fa5;border:1px solid #c8dff0}
.btn.light:hover{background:#d5e7f5}

/* Table */
.table-panel{background:#fff;border:1px solid #e3e9f1;border-radius:11px;box-shadow:0 6px 20px rgba(16,32,64,0.06);overflow-x:auto}
table{width:100%;border-collapse:collapse;text-align:left}
th,td{padding:12px 14px;border-bottom:1px solid #eef2f6;font-size:13px;vertical-align:middle}
th{background:#f8fafc;color:#475569;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:0.5px}
tr:hover{background:#f8fafc}
.badge{display:inline-block;padding:4px 9px;border-radius:12px;font-size:11px;font-weight:700;background:#eef2f6;color:#334155}
.badge.submitted{background:#e0f2fe;color:#0369a1}
.badge.review,.badge.under_review{background:#fef3c7;color:#92400e}
.badge.accepted{background:#dcfce7;color:#166534}
.badge.rejected{background:#fee2e2;color:#991b1b}
.act-btn{display:inline-block;padding:6px 11px;border-radius:5px;font-size:12px;font-weight:700;text-decoration:none;margin-right:4px}
.act-view{background:#eaf2f9;color:#0b5fa5}
.act-view:hover{background:#d4e6f6}
.act-assign{background:#0b5fa5;color:#fff}
.act-assign:hover{background:#084980}
.act-tech{background:#f1f5f9;color:#334155;border:1px solid #cbd5e1}
.act-tech:hover{background:#e2e8f0}

/* Responsive */
@media(max-width:1050px){
  .dashboard-layout{flex-direction:column}
  .left-panel{width:100%;position:static}
  .nav-menu{display:grid;grid-template-columns:1fr 1fr;gap:6px;padding:10px}
  .nav-item{margin:0}
  .nav-link{border-left:none;border-bottom:3px solid transparent}
  .nav-link.active{border-left:none;border-bottom-color:#0b5fa5}
  .grid-3{grid-template-columns:1fr 1fr}
}
@media(max-width:768px){
  .stats-grid{grid-template-columns:1fr 1fr}
  .grid{grid-template-columns:1fr}
  .grid-3{grid-template-columns:1fr}
  .nav-menu{grid-template-columns:1fr}
}
@media(max-width:650px){
  .top-inner{align-items:flex-start;flex-direction:column}
  .top-actions{flex-wrap:wrap}
  .stats-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<!-- Header -->
<header class="top">
  <div class="top-inner">
    <div class="brand">
      AJSMR — Editorial Management System
      <small>Editor-in-Chief Dashboard</small>
    </div>
    <div class="top-actions">
      <span class="user-badge"><?=e($u['email'] ?? 'Editor-in-Chief')?></span>
      <a class="header-btn" href="workflow_v1.php">Workflow V1</a>
      <a class="header-btn" href="logout.php">Sign Out</a>
    </div>
  </div>
</header>

<main class="wrap">
  <!-- Welcome Header -->
  <section class="welcome">
    <h1>Welcome, Editor-in-Chief</h1>
    <p>Monitor author submissions, coordinate peer reviews, manage journal issues, and publish articles.</p>
  </section>

  <!-- Summary Statistics Cards -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-label">New Submissions</div>
      <div class="stat-num"><?=$countNew?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Under Review</div>
      <div class="stat-num"><?=$countReview?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Revisions Pending</div>
      <div class="stat-num"><?=$countRevisions?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total Manuscripts</div>
      <div class="stat-num"><?=$countTotal?></div>
    </div>
  </div>

  <!-- Two-Panel Interface -->
  <div class="dashboard-layout">

    <!-- LEFT PANEL: Main Dashboard Categories (Shared Component) -->
    <?php include __DIR__ . '/includes/eic_sidebar.php'; ?>

    <!-- RIGHT PANEL: Category Details Area -->
    <div class="right-panel">

      <!-- CATEGORY 1: Manuscript Queue & New Submissions -->
      <section id="sec-manuscripts" class="content-section active" role="tabpanel" aria-labelledby="tab-manuscripts">
        <div class="section-banner">
          <div>
            <h2 class="section-banner-title">Manuscript Queue &amp; New Submissions</h2>
            <p class="section-banner-desc">Showing active submissions from author portal with live review and screening status.</p>
          </div>
          <div>
            <a class="btn light" href="new_submissions.php">All New Submissions View →</a>
          </div>
        </div>

        <div class="table-panel">
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
                  <th style="min-width:90px;">Action</th>
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
                      <a class="act-btn act-view" href="manuscript_view.php?id=<?=(int)$r['id']?>">View</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </section>

      <!-- CATEGORY 2: Current Issue Management -->
      <section id="sec-current-issue" class="content-section" role="tabpanel" aria-labelledby="tab-current-issue">
        <div class="section-banner">
          <div>
            <h2 class="section-banner-title">Current Issue Management</h2>
            <p class="section-banner-desc">Manage AJSMR issues, volumes, published current issue content, and public presentation.</p>
          </div>
        </div>

        <div class="grid">
          <!-- 1. Issue Years & Issues -->
          <div class="card article-card">
            <div class="card-top">
              <h2><span>📅</span> Issue Years &amp; Issues</h2>
              <p>Create, organize and manage journal issues, volumes and publishing periods for the journal archive and current stream.</p>
            </div>
            <div class="card-actions">
              <a class="btn" href="issue_years.php">MANAGE ISSUES →</a>
            </div>
          </div>

          <!-- 2. Add Current Issue -->
          <div class="card article-card">
            <div class="card-top">
              <h2><span>➕</span> Add Current Issue</h2>
              <p>Upload and publish articles into the current or selected issue with full PDF, abstract PDF, image and metadata.</p>
            </div>
            <div class="card-actions">
              <a class="btn" href="current_issue_add.php">ADD CURRENT ISSUE →</a>
            </div>
          </div>

          <!-- 3. Current Issue List -->
          <div class="card article-card">
            <div class="card-top">
              <h2><span>📚</span> Current Issue List</h2>
              <p>View, filter, search, edit and manage articles currently in journal issues.</p>
            </div>
            <div class="card-actions">
              <a class="btn" href="current_issue_list.php">CURRENT ISSUE LIST →</a>
            </div>
          </div>

          <!-- 4. Public Current Issue -->
          <div class="card article-card">
            <div class="card-top">
              <h2><span>🌐</span> Public Current Issue</h2>
              <p>Preview the live public Current Issue page as seen by readers and authors.</p>
            </div>
            <div class="card-actions">
              <a class="btn light" href="../currentissue.php" target="_blank" rel="noopener">VIEW PUBLIC ISSUE ↗</a>
            </div>
          </div>
        </div>
      </section>

      <!-- CATEGORY 3: Article Publication -->
      <section id="sec-article-publication" class="content-section" role="tabpanel" aria-labelledby="tab-article-publication">
        <div class="section-banner">
          <div>
            <h2 class="section-banner-title">Article Publication</h2>
            <p class="section-banner-desc">Manage published AJSMR articles, metadata, and journal publication catalog.</p>
          </div>
        </div>

        <div class="grid-3">
          <!-- 1. Add New Article -->
          <div class="card article-card">
            <div class="card-top">
              <h2><span>➕</span> Add New Article</h2>
              <p>Enter article details, authors, publication information, references and publication files.</p>
            </div>
            <div class="card-actions">
              <a class="btn" href="article_add.php">ADD ARTICLE →</a>
            </div>
          </div>

          <!-- 2. Article Management -->
          <div class="card article-card">
            <div class="card-top">
              <h2><span>📋</span> Article Management</h2>
              <p>Search articles, check their status, edit article information and open published articles.</p>
            </div>
            <div class="card-actions">
              <a class="btn" href="article_manage.php">MANAGE ARTICLES →</a>
            </div>
          </div>

          <!-- 3. Published Articles -->
          <div class="card article-card">
            <div class="card-top">
              <h2><span>📄</span> Published Articles</h2>
              <p>Open the article management list and filter articles by PUBLISHED or UPDATED status.</p>
            </div>
            <div class="card-actions">
              <a class="btn light" href="article_manage.php?status=PUBLISHED">PUBLISHED ARTICLES →</a>
            </div>
          </div>
        </div>
      </section>

      <!-- CATEGORY 4: Editorial Management & Decision System -->
      <section id="sec-editorial-decision" class="content-section" role="tabpanel" aria-labelledby="tab-editorial-decision">
        <div class="section-banner">
          <div>
            <h2 class="section-banner-title">Editorial Management &amp; Decision System</h2>
            <p class="section-banner-desc">Manage peer review workflows, reviewer pools, reviewer reports, and editorial decisions.</p>
          </div>
          <div>
            <a class="btn" href="workflow_v1.php">Open Workflow V1 Engine →</a>
          </div>
        </div>

        <!-- Workflow Hero Card -->
        <div class="card article-card accent" style="margin-bottom:20px;">
          <div class="card-top">
            <h2><span>⚙</span> Workflow V1 System</h2>
            <p style="font-size:14px;line-height:1.6;margin-top:6px;">
              Manage complete editorial lifecycle: Technical Check, Reviewer Pool, Peer Reviews, Decisions, and Revisions.
            </p>
          </div>
          <div class="card-actions" style="display:flex;gap:12px;flex-wrap:wrap;">
            <a class="btn light" href="reviewer_manage.php">MANAGE REVIEWER POOL</a>
            <a class="btn" href="workflow_v1.php">OPEN WORKFLOW V1 →</a>
          </div>
        </div>

        <div class="grid">
          <!-- Reviewer Pool Card -->
          <div class="card article-card">
            <div class="card-top">
              <h2><span>👥</span> Reviewer Pool Management</h2>
              <p>Search and manage qualified peer reviewers, review assignments, invited reviewer statuses, and subject specializations.</p>
            </div>
            <div class="card-actions">
              <a class="btn" href="reviewer_manage.php">MANAGE REVIEWERS →</a>
            </div>
          </div>

          <!-- Workflow Stages Overview -->
          <div class="card article-card">
            <div class="card-top">
              <h2><span>📋</span> Editorial Stages &amp; Actions</h2>
              <p style="margin-bottom:12px;">Each submitted manuscript passes through standard scholarly peer review stages:</p>
              <div style="font-size:12.5px;color:#334155;line-height:1.8;background:#f8fafc;padding:12px 14px;border-radius:8px;border:1px solid #e2e8f0;">
                <div><strong>1. Technical Check:</strong> Scope, formatting, and submission integrity</div>
                <div><strong>2. Reviewer Assignment:</strong> Assign expert peer reviewers</div>
                <div><strong>3. Editorial Decision:</strong> Accept, Revision Required, or Reject</div>
                <div><strong>4. Revision Handling:</strong> Review revised manuscripts &amp; responses</div>
              </div>
            </div>
            <div class="card-actions" style="margin-top:14px;">
              <a class="btn light" href="workflow_v1.php">VIEW ALL STAGES IN WORKFLOW V1 →</a>
            </div>
          </div>
        </div>
      </section>

    </div><!-- /right-panel -->

  </div><!-- /dashboard-layout -->
</main>

<script>
/**
 * Switch active category in the two-panel interface.
 * Updates the left menu active state and displays the matching right panel.
 * Updates URL hash without scrolling.
 */
function switchCategory(catId, event) {
  if (event) {
    event.preventDefault();
  }

  var tabs = {
    'manuscripts': { tab: 'tab-manuscripts', sec: 'sec-manuscripts' },
    'current-issue': { tab: 'tab-current-issue', sec: 'sec-current-issue' },
    'article-publication': { tab: 'tab-article-publication', sec: 'sec-article-publication' },
    'editorial-decision': { tab: 'tab-editorial-decision', sec: 'sec-editorial-decision' }
  };

  if (!tabs[catId]) {
    catId = 'manuscripts';
  }

  // Deactivate all nav links and content sections
  for (var key in tabs) {
    if (tabs.hasOwnProperty(key)) {
      var tabEl = document.getElementById(tabs[key].tab);
      var secEl = document.getElementById(tabs[key].sec);
      if (tabEl) {
        tabEl.classList.remove('active');
        tabEl.setAttribute('aria-selected', 'false');
      }
      var itemEl = document.getElementById('item-' + key);
      var subEl = document.getElementById('sub-' + key);
      if (itemEl) itemEl.classList.remove('active-section');
      if (subEl) subEl.classList.remove('is-open');
    }
  }

  // Activate selected tab, section, and sidebar submenu
  var activeTab = document.getElementById(tabs[catId].tab);
  var activeSec = document.getElementById(tabs[catId].sec);
  var activeItem = document.getElementById('item-' + catId);
  var activeSub = document.getElementById('sub-' + catId);
  if (activeTab) {
    activeTab.classList.add('active');
    activeTab.setAttribute('aria-selected', 'true');
  }
  if (activeSec) {
    activeSec.classList.add('active');
  }
  if (activeItem) {
    activeItem.classList.add('active-section');
  }
  if (activeSub) {
    activeSub.classList.add('is-open');
  }

  // Update URL hash without causing a page jump
  if (history.replaceState) {
    history.replaceState(null, null, '#' + catId);
  } else {
    location.hash = '#' + catId;
  }
}

// Support direct loading via URL hash (e.g. dashboard.php#current-issue)
window.addEventListener('DOMContentLoaded', function() {
  var hash = window.location.hash.replace('#', '');
  if (hash && (hash === 'manuscripts' || hash === 'current-issue' || hash === 'article-publication' || hash === 'editorial-decision')) {
    switchCategory(hash);
  }
});
</script>
</body>
</html>