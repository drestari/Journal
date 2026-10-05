<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$u = role_required(['admin', 'editor_in_chief', 'editor', 'managing_editor']);
$pageTitle = 'New Journal Submissions';

$pdo = db();
$stmt = $pdo->query("
    SELECT m.*, u.full_name AS author_name, u.email AS author_email,
           (SELECT COUNT(*) FROM ew_reviewer_assignments ra WHERE ra.manuscript_id = m.id AND ra.status <> 'cancelled') AS reviewer_count
    FROM manuscripts m
    LEFT JOIN users u ON u.id = m.corresponding_author_id
    ORDER BY m.submitted_at DESC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($pageTitle)?></title>
<style>
*{box-sizing:border-box}
body{font-family:Arial,Helvetica,sans-serif;background:#f5f7fa;margin:0;color:#25344a}
.top{background:linear-gradient(135deg,#092b5f,#0b5fa5);color:#fff;padding:20px 30px}
.top-inner{max-width:1250px;margin:auto;display:flex;justify-content:space-between;align-items:center}
.brand{font-size:20px;font-weight:800}
.brand small{display:block;font-size:12px;font-weight:400;margin-top:3px;color:#dbeafe}
.nav-links a{color:#fff;text-decoration:none;border:1px solid #ffffff55;border-radius:6px;padding:7px 12px;font-size:13px;margin-left:8px}
.wrap{max-width:1350px;margin:24px auto;padding:0 20px}
.panel{background:#fff;border:1px solid #e3e9f1;border-radius:10px;padding:22px;box-shadow:0 4px 15px #10204008}
h1{margin:0 0 16px 0;font-size:24px}
table{width:100%;border-collapse:collapse}
th,td{padding:12px;border:1px solid #e2e8f0;text-align:left;vertical-align:middle;font-size:13px}
th{background:#f8fafc;color:#475569;font-weight:bold;text-transform:uppercase;font-size:12px}
.btn{display:inline-block;padding:7px 13px;background:#0b5fa5;color:#fff;text-decoration:none;border-radius:5px;font-weight:bold;font-size:12px}
.btn.light{background:#eaf2f9;color:#0b5fa5}
.badge{display:inline-block;padding:4px 9px;border-radius:12px;font-size:11px;font-weight:bold;background:#eef2f6;color:#334155}
.badge.submitted{background:#e0f2fe;color:#0369a1}
.badge.review,.badge.under_review{background:#fef3c7;color:#92400e}
.badge.accepted{background:#dcfce7;color:#166534}
.empty{padding:30px;background:#fff;border:1px solid #e3e9f1;border-radius:10px;text-align:center;color:#64748b}
</style>
</head>
<body>
<header class="top">
  <div class="top-inner">
    <div class="brand">AJSMR — Editorial Management System<small>Submissions Queue</small></div>
    <div class="nav-links">
      <a href="dashboard.php">← EIC Dashboard</a>
      <a href="workflow_v1.php">Workflow V1</a>
      <a href="logout.php">Sign Out</a>
    </div>
  </div>
</header>

<div class="wrap">
  <div class="dashboard-layout">
    <?php include __DIR__ . '/includes/eic_sidebar.php'; ?>
    <div class="right-panel">
      <h1>New Journal Submissions</h1>
  <?php if (empty($rows)): ?>
    <div class="empty">No manuscript submissions found in the editorial queue.</div>
  <?php else: ?>
    <div class="panel">
      <table>
        <thead>
          <tr>
            <th>Manuscript ID</th>
            <th>Title</th>
            <th>Article Type</th>
            <th>Corresponding Author</th>
            <th>Submitted Date</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><strong><?=e($r['manuscript_no'])?></strong></td>
              <td><?=e($r['title'])?></td>
              <td><?=e($r['article_type'] ?: 'Article')?></td>
              <td>
                <strong><?=e($r['author_name'] ?: 'Author')?></strong><br>
                <small style="color:#64748b;"><?=e($r['author_email'] ?: $r['corresponding_email'])?></small>
              </td>
              <td><?=e($r['submitted_at'])?></td>
              <td><span class="badge <?=e(strtolower($r['status']))?>"><?=e(slabel($r['status']))?></span></td>
              <td>
                <a class="btn" href="manuscript_view.php?id=<?=(int)$r['id']?>">View</a>
                <a class="btn light" href="reviewers.php?id=<?=(int)$r['id']?>" style="margin-left:4px;">Reviewers</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
    </div><!-- /.right-panel -->
  </div><!-- /.dashboard-layout -->
</div>
</body>
</html>
