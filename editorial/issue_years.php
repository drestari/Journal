<?php
declare(strict_types=1);

require_once __DIR__ . '/current_issue_common.php';
$u = current_issue_eic();

$pageTitle = 'Issue Years & Issues';
$jdb = journal_db();

$msg = trim((string)($_GET['msg'] ?? ''));
$errMsg = '';

// Handle Delete Request
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    try {
        check_csrf();
        $delId = (int)($_POST['id'] ?? 0);
        if ($delId <= 0) {
            throw new RuntimeException('Invalid issue ID.');
        }

        // Check if issue exists
        $st = $jdb->prepare("SELECT catid, catename FROM ajsmr_issueyears WHERE catid = ? LIMIT 1");
        $st->execute([$delId]);
        $iss = $st->fetch();

        if (!$iss) {
            throw new RuntimeException('The requested issue was not found.');
        }

        // Check if articles are assigned to this issue
        $artCountSt = $jdb->prepare("SELECT COUNT(*) FROM ajsmr_issuecontent WHERE catid = ?");
        $artCountSt->execute([$delId]);
        $artCount = (int)$artCountSt->fetchColumn();

        if ($artCount > 0) {
            throw new RuntimeException("Cannot delete issue '{$iss['catename']}': It currently contains {$artCount} article(s). Please remove or reassign the articles before deleting the issue.");
        }

        // Perform deletion safely
        $delSt = $jdb->prepare("DELETE FROM ajsmr_issueyears WHERE catid = ?");
        $delSt->execute([$delId]);

        audit('ISSUE_DELETED', null, "Deleted issue #{$delId} '{$iss['catename']}'");
        header('Location: issue_years.php?msg=' . urlencode('Issue deleted successfully.'));
        exit;
    } catch (Throwable $e) {
        $errMsg = $e->getMessage();
    }
}

// Search and Filtering
$q = trim((string)($_GET['q'] ?? ''));
$status = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;

$sql = "FROM ajsmr_issueyears y WHERE 1=1";
$params = [];

if ($q !== '') {
    $sql .= " AND y.catename LIKE ?";
    $params[] = '%' . $q . '%';
}

if ($status !== null && in_array($status, [0, 1], true)) {
    $sql .= " AND y.status = ?";
    $params[] = $status;
}

// Count total records
$countSt = $jdb->prepare("SELECT COUNT(*) " . $sql);
$countSt->execute($params);
$totalRecords = (int)$countSt->fetchColumn();

// Pagination (25 per page)
$perPage = 25;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$totalPages = max(1, (int)ceil($totalRecords / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

// Fetch rows with article count
$fetchSql = "SELECT y.*, (SELECT COUNT(*) FROM ajsmr_issuecontent c WHERE c.catid = y.catid) AS article_count "
          . $sql . " ORDER BY y.catid DESC LIMIT {$perPage} OFFSET {$offset}";
$dataSt = $jdb->prepare($fetchSql);
$dataSt->execute($params);
$rows = $dataSt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <div>
    <h1 class="title" style="margin-bottom:4px;">Issue Years &amp; Issues</h1>
    <div class="muted">Manage journal volumes, issue periods and publication years connected directly to Current Issue.</div>
  </div>
  <div>
    <a href="issue_year_add.php" class="btn primary">+ Add Issue</a>
    <a href="current_issue_list.php" class="btn secondary" style="margin-left:8px;">Manage Current Articles</a>
  </div>
</div>

<?php if ($msg !== ''): ?>
  <div class="alert"><strong>Success:</strong> <?=e($msg)?></div>
<?php endif; ?>

<?php if ($errMsg !== ''): ?>
  <div class="alert err"><strong>Notice:</strong> <?=e($errMsg)?></div>
<?php endif; ?>

<div class="panel" style="padding:16px;margin-bottom:20px;">
  <form method="get" action="issue_years.php" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
    <div style="flex:2;min-width:240px;">
      <label style="margin:0 0 5px;font-size:12px;">Search Issues</label>
      <input type="text" name="q" value="<?=e($q)?>" placeholder="Search by year, volume, period or name (e.g. 2026, Vol 12)...">
    </div>

    <div style="flex:1;min-width:140px;">
      <label style="margin:0 0 5px;font-size:12px;">Status</label>
      <select name="status">
        <option value="">All Statuses</option>
        <option value="1" <?=( $status === 1 ? 'selected' : '' )?>>Active</option>
        <option value="0" <?=( $status === 0 ? 'selected' : '' )?>>Inactive</option>
      </select>
    </div>

    <div>
      <button type="submit" class="btn primary" style="padding:9px 18px;">Filter</button>
      <?php if ($q !== '' || $status !== null): ?>
        <a href="issue_years.php" class="btn secondary" style="padding:9px 14px;margin-left:6px;">Reset</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="panel" style="padding:0;overflow:hidden;">
  <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr>
          <th style="width:55px;text-align:center;">S.No</th>
          <th>Issue Display Name</th>
          <th style="width:110px;text-align:center;">Year</th>
          <th style="width:110px;text-align:center;">Status</th>
          <th style="width:110px;text-align:center;">Articles</th>
          <th style="width:280px;text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="6" style="text-align:center;padding:45px 20px;color:#6b7280;">
              No issues found matching your criteria.
              <div style="margin-top:10px;">
                <a href="issue_year_add.php" class="btn primary" style="font-size:12px;">+ Add New Issue</a>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $index => $r):
            $sno = $offset + $index + 1;
            $catid = (int)$r['catid'];
            $artCount = (int)$r['article_count'];
            $parsed = parse_issue_components((string)$r['catename']);
            $dispYear = $parsed['year'] !== '' ? $parsed['year'] : '—';
            $isActive = (int)$r['status'] === 1;
          ?>
            <tr>
              <td style="text-align:center;color:#6b7280;font-weight:bold;"><?=$sno?></td>
              <td>
                <div style="font-weight:bold;color:#1e293b;font-size:14px;line-height:1.4;">
                  <?=e($r['catename'])?>
                </div>
                <div class="muted" style="font-size:11px;margin-top:3px;">
                  Issue ID (catid): #<?=$catid?>
                  <?php if (!empty($r['eventdate']) && $r['eventdate'] !== '0000-00-00'): ?>
                    &middot; Date: <?=e($r['eventdate'])?>
                  <?php endif; ?>
                </div>
              </td>
              <td style="text-align:center;font-weight:bold;color:#475467;">
                <?=e($dispYear)?>
              </td>
              <td style="text-align:center;">
                <?php if ($isActive): ?>
                  <span class="badge" style="background:#dcfce7;color:#15803d;font-weight:bold;">Active</span>
                <?php else: ?>
                  <span class="badge" style="background:#f1f5f9;color:#64748b;font-weight:bold;">Inactive</span>
                <?php endif; ?>
              </td>
              <td style="text-align:center;">
                <a href="current_issue_list.php?issue_id=<?=$catid?>" style="font-weight:bold;color:#0b5fa5;text-decoration:none;">
                  <?=$artCount?> article<?=$artCount === 1 ? '' : 's'?>
                </a>
              </td>
              <td style="text-align:center;">
                <div style="display:flex;gap:5px;justify-content:center;align-items:center;flex-wrap:wrap;">
                  <a href="issue_year_edit.php?id=<?=$catid?>" class="btn primary" style="padding:5px 9px;font-size:11px;">Edit</a>
                  <a href="current_issue_list.php?issue_id=<?=$catid?>" class="btn secondary" style="padding:5px 9px;font-size:11px;">Manage Articles</a>
                  <a href="../issuelist.php?cat_id=<?=$catid?>" target="_blank" class="btn secondary" style="padding:5px 8px;font-size:11px;" title="View on public site">View ↗</a>
                  <form method="post" action="issue_years.php" style="margin:0;display:inline;" onsubmit="return confirm('Are you sure you want to delete this issue?');">
                    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?=$catid?>">
                    <button type="submit" class="btn danger" style="padding:5px 9px;font-size:11px;" <?=( $artCount > 0 ? 'title="Contains articles; remove them first to delete."' : '' )?>>Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <div style="padding:15px;border-top:1px solid #e5e5e5;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
      <div style="font-size:13px;color:#64748b;">
        Showing <strong><?=$offset + 1?></strong> to <strong><?=min($offset + $perPage, $totalRecords)?></strong> of <strong><?=$totalRecords?></strong> issues
      </div>
      <div style="display:flex;gap:5px;">
        <?php
        $queryParams = $_GET;
        unset($queryParams['page']);
        $buildUrl = function(int $p) use ($queryParams): string {
            $params = array_merge($queryParams, ['page' => $p]);
            return 'issue_years.php?' . http_build_query($params);
        };
        ?>
        <?php if ($page > 1): ?>
          <a href="<?=$buildUrl($page - 1)?>" class="btn secondary" style="padding:6px 12px;font-size:12px;">&laquo; Previous</a>
        <?php endif; ?>

        <?php for ($p = max(1, $page - 3); $p <= min($totalPages, $page + 3); $p++): ?>
          <a href="<?=$buildUrl($p)?>" class="btn <?=( $p === $page ? 'primary' : 'secondary' )?>" style="padding:6px 12px;font-size:12px;"><?=$p?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a href="<?=$buildUrl($page + 1)?>" class="btn secondary" style="padding:6px 12px;font-size:12px;">Next &raquo;</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
