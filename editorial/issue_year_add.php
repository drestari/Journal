<?php
declare(strict_types=1);

require_once __DIR__ . '/current_issue_common.php';
$u = current_issue_eic();

$pageTitle = 'Add Issue Year & Issue';
$error = '';
$success = '';

$periods = issue_periods();
$jdb = journal_db();

$defaults = [
    'year' => date('Y'),
    'volume' => '12',
    'issue' => '1',
    'period' => 'January-March',
    'catename' => 'January-March 12(1), ' . date('Y'),
    'eventdate' => date('Y-m-d'),
    'status' => '1'
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        check_csrf();

        $year = trim((string)($_POST['year'] ?? date('Y')));
        $volume = trim((string)($_POST['volume'] ?? ''));
        $issueNum = trim((string)($_POST['issue'] ?? ''));
        $period = trim((string)($_POST['period'] ?? 'January-March'));
        $catename = trim((string)($_POST['catename'] ?? ''));
        $eventdate = trim((string)($_POST['eventdate'] ?? ''));
        $status = (isset($_POST['status']) && (int)$_POST['status'] === 1) ? 1 : 0;

        if ($catename === '') {
            $catename = build_issue_display_name($period, $volume, $issueNum, $year);
        }

        if ($catename === '') {
            throw new RuntimeException('Issue Display Name is required.');
        }

        // Duplicate check
        $dupSt = $jdb->prepare("SELECT catid FROM ajsmr_issueyears WHERE catename = ? LIMIT 1");
        $dupSt->execute([$catename]);
        if ($dupSt->fetch()) {
            throw new RuntimeException("An issue with the name '{$catename}' already exists.");
        }

        $cleanEventDate = ($eventdate !== '' && strtotime($eventdate)) ? date('Y-m-d', strtotime($eventdate)) : '0000-00-00';

        // Insert into ajsmr_issueyears
        $insSt = $jdb->prepare("INSERT INTO ajsmr_issueyears (catename, eventdate, status) VALUES (?, ?, ?)");
        $insSt->execute([$catename, $cleanEventDate, $status]);
        $newId = (int)$jdb->lastInsertId();

        audit('ISSUE_CREATED', null, "Created issue #{$newId} '{$catename}'");

        header('Location: issue_years.php?msg=' . urlencode("Issue '{$catename}' created successfully."));
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

include __DIR__ . '/includes/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <div>
    <h1 class="title" style="margin-bottom:4px;">Add Issue Year &amp; Issue</h1>
    <div class="muted">Register a new publication volume and issue for the journal archive and current issue stream.</div>
  </div>
  <div>
    <a href="issue_years.php" class="btn secondary">← Back to Issue List</a>
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
        <label>Year <span style="color:#b42318;">*</span></label>
        <input type="number" id="inputYear" name="year" value="<?=e($defaults['year'])?>" min="2000" max="2100" required>
        <small class="muted" style="display:block;margin-top:4px;">Four-digit calendar year (e.g. 2026).</small>
      </div>

      <div>
        <label>Volume Number <span style="color:#b42318;">*</span></label>
        <input type="text" id="inputVolume" name="volume" value="<?=e($defaults['volume'])?>" placeholder="e.g. 12" required>
        <small class="muted" style="display:block;margin-top:4px;">Journal volume number.</small>
      </div>
    </div>

    <div class="grid" style="margin-top:14px;">
      <div>
        <label>Issue Number / Name <span style="color:#b42318;">*</span></label>
        <input type="text" id="inputIssue" name="issue" value="<?=e($defaults['issue'])?>" placeholder="e.g. 1, 2, 3, 4" required>
        <small class="muted" style="display:block;margin-top:4px;">Numeric issue number (or 'Special Issue').</small>
      </div>

      <div>
        <label>Issue Period <span style="color:#b42318;">*</span></label>
        <select id="selectPeriod" name="period" required>
          <?php foreach ($periods as $p): ?>
            <option value="<?=e($p)?>" <?=( $defaults['period'] === $p ? 'selected' : '' )?>>
              <?=e($p)?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div style="margin-top:16px;">
      <label>Issue Display Name <span style="color:#b42318;">*</span></label>
      <input type="text" id="inputCatename" name="catename" value="<?=e($defaults['catename'])?>" required>
      <small class="muted" style="display:block;margin-top:4px;">
        Auto-formatted standard display name. Example: <code>January-March 12(1), 2026</code>. You can customize if needed.
      </small>
    </div>

    <div class="grid" style="margin-top:16px;">
      <div>
        <label>Publication Date (Optional)</label>
        <input type="date" name="eventdate" value="<?=e($defaults['eventdate'])?>">
        <small class="muted" style="display:block;margin-top:4px;">Official publication release date.</small>
      </div>

      <div>
        <label>Status <span style="color:#b42318;">*</span></label>
        <select name="status">
          <option value="1" <?=( $defaults['status'] === '1' ? 'selected' : '' )?>>Active (Visible in Dropdowns &amp; Public)</option>
          <option value="0" <?=( $defaults['status'] === '0' ? 'selected' : '' )?>>Inactive (Hidden)</option>
        </select>
      </div>
    </div>

    <div style="margin-top:24px;display:flex;gap:12px;align-items:center;">
      <button type="submit" class="btn primary" style="padding:11px 22px;font-size:14px;">Create Issue</button>
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
    } else if (p && y) {
      nameEl.value = p + ' ' + y;
    }
  }

  yearEl.addEventListener('input', updateName);
  volEl.addEventListener('input', updateName);
  issEl.addEventListener('input', updateName);
  perEl.addEventListener('change', updateName);
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
