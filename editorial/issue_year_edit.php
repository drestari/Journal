<?php
declare(strict_types=1);

require_once __DIR__ . '/current_issue_common.php';
$u = current_issue_eic();

$pageTitle = 'Edit Issue Year & Issue';
$error = '';
$jdb = journal_db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: issue_years.php');
    exit;
}

// Fetch issue
$st = $jdb->prepare("SELECT * FROM ajsmr_issueyears WHERE catid = ? LIMIT 1");
$st->execute([$id]);
$issue = $st->fetch();

if (!$issue) {
    header('Location: issue_years.php?msg=' . urlencode('Issue not found.'));
    exit;
}

$periods = issue_periods();
$parsed = parse_issue_components((string)$issue['catename']);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        check_csrf();

        $catename = trim((string)($_POST['catename'] ?? ''));
        $eventdate = trim((string)($_POST['eventdate'] ?? ''));
        $status = (isset($_POST['status']) && (int)$_POST['status'] === 1) ? 1 : 0;

        if ($catename === '') {
            $year = trim((string)($_POST['year'] ?? ''));
            $volume = trim((string)($_POST['volume'] ?? ''));
            $issueNum = trim((string)($_POST['issue'] ?? ''));
            $period = trim((string)($_POST['period'] ?? ''));
            $catename = build_issue_display_name($period, $volume, $issueNum, $year);
        }

        if ($catename === '') {
            throw new RuntimeException('Issue Display Name is required.');
        }

        // Duplicate check excluding current ID
        $dupSt = $jdb->prepare("SELECT catid FROM ajsmr_issueyears WHERE catename = ? AND catid != ? LIMIT 1");
        $dupSt->execute([$catename, $id]);
        if ($dupSt->fetch()) {
            throw new RuntimeException("Another issue with the name '{$catename}' already exists.");
        }

        $cleanEventDate = ($eventdate !== '' && strtotime($eventdate)) ? date('Y-m-d', strtotime($eventdate)) : '0000-00-00';

        $upSt = $jdb->prepare("UPDATE ajsmr_issueyears SET catename = ?, eventdate = ?, status = ? WHERE catid = ?");
        $upSt->execute([$catename, $cleanEventDate, $status, $id]);

        audit('ISSUE_UPDATED', null, "Updated issue #{$id} to '{$catename}'");

        header('Location: issue_years.php?msg=' . urlencode("Issue '{$catename}' updated successfully."));
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

// Check article count for this issue
$countSt = $jdb->prepare("SELECT COUNT(*) FROM ajsmr_issuecontent WHERE catid = ?");
$countSt->execute([$id]);
$artCount = (int)$countSt->fetchColumn();

include __DIR__ . '/includes/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <div>
    <h1 class="title" style="margin-bottom:4px;">Edit Issue Year &amp; Issue</h1>
    <div class="muted">Editing Issue ID #<?=e($issue['catid'])?>: <strong><?=e($issue['catename'])?></strong></div>
  </div>
  <div>
    <a href="issue_years.php" class="btn secondary">← Back to Issue List</a>
    <a href="current_issue_list.php?issue_id=<?=e($issue['catid'])?>" class="btn secondary" style="margin-left:8px;">Manage Articles (<?=$artCount?>)</a>
  </div>
</div>

<?php if ($error !== ''): ?>
  <div class="alert err"><strong>Error:</strong> <?=e($error)?></div>
<?php endif; ?>

<div class="panel">
  <form method="post" id="issueForm">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">

    <div class="grid">
      <div>
        <label>Year</label>
        <input type="number" id="inputYear" name="year" value="<?=e($parsed['year'])?>" min="2000" max="2100">
      </div>

      <div>
        <label>Volume Number</label>
        <input type="text" id="inputVolume" name="volume" value="<?=e($parsed['volume'])?>">
      </div>
    </div>

    <div class="grid" style="margin-top:14px;">
      <div>
        <label>Issue Number / Name</label>
        <input type="text" id="inputIssue" name="issue" value="<?=e($parsed['issue'])?>">
      </div>

      <div>
        <label>Issue Period</label>
        <select id="selectPeriod" name="period">
          <?php foreach ($periods as $p): ?>
            <option value="<?=e($p)?>" <?=( $parsed['period'] === $p ? 'selected' : '' )?>>
              <?=e($p)?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div style="margin-top:16px;">
      <label>Issue Display Name <span style="color:#b42318;">*</span></label>
      <input type="text" id="inputCatename" name="catename" value="<?=e($issue['catename'])?>" required>
      <small class="muted" style="display:block;margin-top:4px;">
        This is the primary label seen across the Current Issue dropdown, archives, and public pages.
      </small>
    </div>

    <div class="grid" style="margin-top:16px;">
      <div>
        <label>Publication Date</label>
        <input type="date" name="eventdate" value="<?=e($issue['eventdate'] !== '0000-00-00' ? $issue['eventdate'] : '')?>">
      </div>

      <div>
        <label>Status <span style="color:#b42318;">*</span></label>
        <select name="status">
          <option value="1" <?=( (int)$issue['status'] === 1 ? 'selected' : '' )?>>Active (Visible in Dropdowns &amp; Public)</option>
          <option value="0" <?=( (int)$issue['status'] === 0 ? 'selected' : '' )?>>Inactive (Hidden)</option>
        </select>
      </div>
    </div>

    <div style="margin-top:20px;padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;color:#475467;">
      <strong>Associated Articles:</strong> This issue currently contains <strong><?=$artCount?></strong> article(s).
      Updating this issue name will preserve all article associations under Issue ID #<?=e($issue['catid'])?>.
    </div>

    <div style="margin-top:24px;display:flex;gap:12px;align-items:center;">
      <button type="submit" class="btn primary" style="padding:11px 22px;font-size:14px;">Update Issue</button>
      <a href="issue_years.php" class="btn secondary">Cancel</a>
    </div>

  </form>
</div>

<script>
(function(){
  var yearEl = document.getElementById('inputYear');
  var volEl = document.getElementById('inputVolume');
  var issEl = document.getElementById('inputIssue');
  var perEl = document.getElementById('selectPeriod');
  var nameEl = document.getElementById('inputCatename');
  var customEdited = false;

  nameEl.addEventListener('input', function(){
    customEdited = true;
  });

  function updateName() {
    if (customEdited) return;
    var y = yearEl.value.trim();
    var v = volEl.value.trim();
    var i = issEl.value.trim();
    var p = perEl.value.trim();

    if (p === 'Special Issue') {
      nameEl.value = v ? ('Special Issue ' + v + ', ' + y) : ('Special Issue, ' + y);
    } else if (p === 'Supplement') {
      nameEl.value = 'Volume ' + v + ' | Issue ' + i + ' Supplement ' + y;
    } else if (p && v && i && y) {
      nameEl.value = p + ' ' + v + '(' + i + '), ' + y;
    }
  }

  yearEl.addEventListener('input', updateName);
  volEl.addEventListener('input', updateName);
  issEl.addEventListener('input', updateName);
  perEl.addEventListener('change', updateName);
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
