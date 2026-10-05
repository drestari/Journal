<?php
declare(strict_types=1);

require_once __DIR__ . '/current_issue_common.php';
$u = current_issue_eic();

$pageTitle = 'Current Issue Articles';
$jdb = journal_db();

// Handle deletion
$msg = trim((string)($_GET['msg'] ?? ''));
$errMsg = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    try {
        check_csrf();
        $delId = (int)($_POST['id'] ?? 0);
        if ($delId <= 0) {
            throw new RuntimeException('Invalid article ID for deletion.');
        }

        // Fetch details before delete for audit
        $st = $jdb->prepare("SELECT contentid, conttitle, catid FROM ajsmr_issuecontent WHERE contentid = ? LIMIT 1");
        $st->execute([$delId]);
        $row = $st->fetch();

        if ($row) {
            $delSt = $jdb->prepare("DELETE FROM ajsmr_issuecontent WHERE contentid = ?");
            $delSt->execute([$delId]);

            // Also clean from abstracts table
            try {
                $delAbs = $jdb->prepare("DELETE FROM ajsmr_abstracts WHERE contentid = ?");
                $delAbs->execute([$delId]);
            } catch (Throwable $ignored) {}

            audit('CURRENT_ISSUE_ARTICLE_DELETED', null, "Deleted article #{$delId} '{$row['conttitle']}' from issue #{$row['catid']}");
            header('Location: current_issue_list.php?msg=' . urlencode('Current Issue article deleted successfully.'));
            exit;
        } else {
            throw new RuntimeException('The requested article was not found.');
        }
    } catch (Throwable $e) {
        $errMsg = $e->getMessage();
    }
}

// Filters
$q       = trim((string)($_GET['q'] ?? ''));
$issueId = isset($_GET['issue_id']) && $_GET['issue_id'] !== '' ? (int)$_GET['issue_id'] : 0;
$type    = trim((string)($_GET['type'] ?? ''));
$status  = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;

$issues = get_all_issues();
$articleTypes = current_issue_article_types();

// Build Query
$sql = "FROM ajsmr_issuecontent c
        LEFT JOIN ajsmr_issueyears y ON y.catid = c.catid
        WHERE 1=1";
$params = [];

if ($q !== '') {
    $sql .= " AND (c.conttitle LIKE ? OR c.authors LIKE ? OR c.doi LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($issueId > 0) {
    $sql .= " AND c.catid = ?";
    $params[] = $issueId;
}

if ($type !== '') {
    $sql .= " AND c.type = ?";
    $params[] = $type;
}

if ($status !== null && in_array($status, [0, 1], true)) {
    $sql .= " AND c.status = ?";
    $params[] = $status;
}

// Count total
$countSt = $jdb->prepare("SELECT COUNT(*) " . $sql);
$countSt->execute($params);
$totalRecords = (int)$countSt->fetchColumn();

// Pagination
$perPage = 15;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$totalPages = max(1, (int)ceil($totalRecords / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

// Fetch rows
$fetchSql = "SELECT c.*, y.catename " . $sql . " ORDER BY c.contentid DESC LIMIT {$perPage} OFFSET {$offset}";
$dataSt = $jdb->prepare($fetchSql);
$dataSt->execute($params);
$rows = $dataSt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <div>
    <h1 class="title" style="margin-bottom:4px;">Current Issue Articles</h1>
    <div class="muted">Manage, edit, publish and organize articles for the current and archived journal issues.</div>
  </div>
  <div>
    <a href="current_issue_add.php" class="btn primary">+ Add Current Issue</a>
    <a href="../currentissue.php" target="_blank" class="btn secondary" style="margin-left:8px;">View Public Current Issue ↗</a>
  </div>
</div>

<?php if ($msg !== ''): ?>
  <div class="alert"><strong>Success:</strong> <?=e($msg)?></div>
<?php endif; ?>

<?php if ($errMsg !== ''): ?>
  <div class="alert err"><strong>Error:</strong> <?=e($errMsg)?></div>
<?php endif; ?>

<div class="panel" style="padding:16px;margin-bottom:20px;">
  <form method="get" action="current_issue_list.php" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));gap:10px;align-items:end;">
    <div>
      <label style="margin:0 0 5px;font-size:12px;">Search Articles</label>
      <input type="text" name="q" value="<?=e($q)?>" placeholder="Search by title, author or DOI...">
    </div>

    <div>
      <label style="margin:0 0 5px;font-size:12px;">Filter by Issue</label>
      <select name="issue_id">
        <option value="">All Issues</option>
        <?php foreach ($issues as $iss): ?>
          <option value="<?=e($iss['catid'])?>" <?=( $issueId === (int)$iss['catid'] ? 'selected' : '' )?>>
            <?=e(format_issue_label($iss))?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label style="margin:0 0 5px;font-size:12px;">Article Type</label>
      <select name="type">
        <option value="">All Types</option>
        <?php foreach ($articleTypes as $t): ?>
          <option value="<?=e($t)?>" <?=( $type === $t ? 'selected' : '' )?>><?=e($t)?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label style="margin:0 0 5px;font-size:12px;">Status</label>
      <select name="status">
        <option value="">All Statuses</option>
        <option value="1" <?=( $status === 1 ? 'selected' : '' )?>>Published</option>
        <option value="0" <?=( $status === 0 ? 'selected' : '' )?>>Draft</option>
      </select>
    </div>

    <div>
      <button type="submit" class="btn primary" style="padding:9px 16px;">Filter</button>
    </div>

    <div>
      <?php if ($q !== '' || $issueId > 0 || $type !== '' || $status !== null): ?>
        <a href="current_issue_list.php" class="btn secondary" style="padding:9px 14px;">Reset</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="panel" style="padding:0;overflow:hidden;">
  <div style="width:100%;overflow-x:auto;">
    <table style="width:100%;table-layout:fixed;border-collapse:collapse;font-size:13px;">
      <thead>
        <tr>
          <th style="width:40px;text-align:center;padding:10px 6px;">S.No</th>
          <th style="width:14%;padding:10px 8px;">Issue</th>
          <th style="width:12%;padding:10px 8px;">Article Type</th>
          <th style="padding:10px 8px;">Article Title</th>
          <th style="width:15%;padding:10px 8px;">Authors</th>
          <th style="width:90px;padding:10px 6px;">Published</th>
          <th style="width:75px;text-align:center;padding:10px 4px;">Status</th>
          <th style="width:85px;text-align:center;padding:10px 4px;">PDF / Media</th>
          <th style="width:85px;text-align:center;padding:10px 4px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="9" style="text-align:center;padding:45px 20px;color:#6b7280;">
              No articles found matching your criteria.
              <div style="margin-top:10px;">
                <a href="current_issue_add.php" class="btn primary" style="font-size:12px;">+ Add an Article</a>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $index => $r):
            $sno = $offset + $index + 1;
            $cid = (int)$r['contentid'];
            $fullPdf = trim((string)($r['fullpaper'] ?? ''));
            $absPdf  = trim((string)($r['abstract'] ?? ''));
            $absUrl  = trim((string)($r['absurl'] ?? ''));
            $isPub   = (int)$r['status'] === 1;
          ?>
            <tr style="border-bottom:1px solid #f1f5f9;">
              <td style="text-align:center;color:#6b7280;font-weight:bold;padding:10px 4px;vertical-align:top;"><?=$sno?></td>
              <td style="padding:10px 8px;vertical-align:top;word-wrap:break-word;word-break:break-word;">
                <strong style="font-size:12px;color:#334155;"><?=e($r['catename'] ?: ('Issue #' . $r['catid']))?></strong>
              </td>
              <td style="padding:10px 8px;vertical-align:top;word-wrap:break-word;">
                <span class="badge" style="background:#eef2f6;color:#334155;font-size:11px;padding:3px 6px;white-space:normal;display:inline-block;"><?=e($r['type'] ?: 'Research Article')?></span>
              </td>
              <td style="padding:10px 8px;vertical-align:top;word-wrap:break-word;word-break:break-word;">
                <div style="font-weight:600;color:#0f172a;line-height:1.4;margin-bottom:4px;font-size:13px;">
                  <?=e($r['conttitle'])?>
                </div>
                <?php if (!empty($r['doi'])): ?>
                  <div style="font-size:11px;color:#0b5fa5;word-break:break-all;">
                    DOI: <a href="<?=e($r['doi'])?>" target="_blank" rel="noopener"><?=e($r['doi'])?></a>
                  </div>
                <?php endif; ?>
              </td>
              <td style="font-size:12px;color:#475467;padding:10px 8px;vertical-align:top;word-wrap:break-word;word-break:break-word;">
                <?=e($r['authors'])?>
              </td>
              <td style="font-size:11px;color:#64748b;padding:10px 6px;vertical-align:top;word-wrap:break-word;">
                <?=e($r['published'] ?: ($r['programdate'] ?: '—'))?>
              </td>
              <td style="text-align:center;padding:10px 4px;vertical-align:top;">
                <?php if ($isPub): ?>
                  <span class="badge" style="background:#dcfce7;color:#15803d;font-weight:bold;font-size:10px;padding:3px 6px;">Published</span>
                <?php else: ?>
                  <span class="badge" style="background:#fef3c7;color:#b45309;font-weight:bold;font-size:10px;padding:3px 6px;">Draft</span>
                <?php endif; ?>
              </td>
              <td style="text-align:center;font-size:11px;padding:10px 4px;vertical-align:top;">
                <div style="display:flex;flex-direction:column;gap:4px;align-items:stretch;max-width:75px;margin:0 auto;">
                  <?php if ($fullPdf !== ''): ?>
                    <a href="<?=e(current_issue_file_url($fullPdf))?>" target="_blank" class="btn secondary" style="padding:3px 6px;font-size:10px;text-align:center;width:100%;box-sizing:border-box;" title="Full Article PDF">Full PDF</a>
                  <?php endif; ?>
                  <?php if ($absPdf !== ''): ?>
                    <a href="<?=e(current_issue_file_url($absPdf))?>" target="_blank" class="btn secondary" style="padding:3px 6px;font-size:10px;text-align:center;width:100%;box-sizing:border-box;" title="Abstract PDF">Abs PDF</a>
                  <?php elseif ($absUrl !== ''): ?>
                    <a href="<?=e($absUrl)?>" target="_blank" class="btn secondary" style="padding:3px 6px;font-size:10px;text-align:center;width:100%;box-sizing:border-box;" title="Abstract Link">Abs Link</a>
                  <?php endif; ?>
                  <?php if ($fullPdf === '' && $absPdf === '' && $absUrl === ''): ?>
                    <span class="muted" style="font-size:10px;">No files</span>
                  <?php endif; ?>
                </div>
              </td>
              <td style="text-align:center;padding:10px 4px;vertical-align:top;">
                <div style="display:flex;flex-direction:column;gap:5px;align-items:stretch;max-width:75px;margin:0 auto;">
                  <?php if ($isPub): ?>
                    <a href="../abstracts_details.php?id=<?=$cid?>" target="_blank" class="btn secondary" style="padding:4px 6px;font-size:11px;text-align:center;width:100%;box-sizing:border-box;" title="View public article">View</a>
                  <?php endif; ?>
                  <a href="current_issue_edit.php?id=<?=$cid?>" class="btn primary" style="padding:4px 6px;font-size:11px;text-align:center;width:100%;box-sizing:border-box;">Edit</a>
                  <form method="post" action="current_issue_list.php" style="margin:0;width:100%;" onsubmit="return confirm('Are you sure you want to delete this article from the current issue?');">
                    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?=$cid?>">
                    <button type="submit" class="btn danger" style="padding:4px 6px;font-size:11px;text-align:center;width:100%;box-sizing:border-box;">Delete</button>
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
        Showing <strong><?=$offset + 1?></strong> to <strong><?=min($offset + $perPage, $totalRecords)?></strong> of <strong><?=$totalRecords?></strong> records
      </div>
      <div style="display:flex;gap:5px;">
        <?php
        $queryParams = $_GET;
        unset($queryParams['page']);
        $buildUrl = function(int $p) use ($queryParams): string {
            $params = array_merge($queryParams, ['page' => $p]);
            return 'current_issue_list.php?' . http_build_query($params);
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
