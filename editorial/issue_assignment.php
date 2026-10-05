<?php
/**
 * issue_assignment.php
 * ew_issue_assignments does not exist. Volume/issue/year/pages are columns
 * in the existing `production` table.
 */
require_once __DIR__ . '/production_common.php';

$db = db();
$u  = prod_user($db);
$mid = (int)($_GET['id'] ?? $_POST['manuscript_id'] ?? 0);
if (!$mid) exit('Invalid manuscript ID.');

$m = prod_ms($db, $mid);
prod_ensure($db, $mid, (int)$u['id']);

// Load production row (volume, issue, year, pages columns)
$stmt = $db->prepare("SELECT * FROM production WHERE manuscript_id = ? LIMIT 1");
$stmt->execute([$mid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    prod_check_csrf();

    $year    = trim($_POST['issue_year'] ?? '');
    $volume  = trim($_POST['volume'] ?? '');
    $issueNo = trim($_POST['issue'] ?? '');
    $start   = trim($_POST['page_start'] ?? '');
    $end     = trim($_POST['page_end'] ?? '');

    if (!preg_match('/^\d{4}$/', $year)) {
        $error = 'Please enter a valid four-digit issue year.';
    } elseif ($volume === '' || $issueNo === '') {
        $error = 'Issue year, volume and issue are required.';
    } else {
        // Compose pages string if provided
        $pages = ($start !== '' || $end !== '') ? trim($start . ($end !== '' ? '-'.$end : '')) : ($row['pages'] ?? '');

        $stmt = $db->prepare("UPDATE production SET volume=?, issue=?, year=?, pages=? WHERE manuscript_id=?");
        $stmt->execute([$volume, $issueNo, (int)$year, $pages, $mid]);

        prod_log($db, $mid, (int)$u['id'], 'issue_assignment_update',
            'Issue '.$year.' / Volume '.$volume.' / Issue '.$issueNo);

        $stmt = $db->prepare("SELECT * FROM production WHERE manuscript_id = ? LIMIT 1");
        $stmt->execute([$mid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $success = 'Issue assignment saved successfully.';
    }
}

// Parse existing pages into start/end for display
$pageStart = $row['pages'] ?? '';
$pageEnd   = '';
if (strpos((string)$row['pages'], '-') !== false) {
    [$pageStart, $pageEnd] = explode('-', (string)$row['pages'], 2);
}

prod_header('Issue Assignment', $u);
?>
<div class="panel">
<h1>Issue Assignment</h1>
<p><strong><?=prod_h($m['manuscript_no'])?></strong> — <?=prod_h($m['title'])?></p>

<?php if($success): ?><div class="ok"><?=prod_h($success)?></div><?php endif; ?>
<?php if($error):   ?><div class="err"><?=prod_h($error)?></div><?php endif; ?>

<form method="post">
<input type="hidden" name="csrf" value="<?=prod_h(prod_csrf())?>">
<input type="hidden" name="manuscript_id" value="<?=$mid?>">

<div class="form-row"><label>Issue Year</label>
<input type="number" name="issue_year" value="<?=prod_h($row['year']??date('Y'))?>" required></div>

<div class="form-row"><label>Volume</label>
<input type="text" name="volume" value="<?=prod_h($row['volume']??'')?>" required></div>

<div class="form-row"><label>Issue</label>
<input type="text" name="issue" value="<?=prod_h($row['issue']??'')?>" required></div>

<div class="form-row"><label>Page Start</label>
<input type="text" name="page_start" value="<?=prod_h($pageStart)?>"></div>

<div class="form-row"><label>Page End</label>
<input type="text" name="page_end" value="<?=prod_h($pageEnd)?>"></div>

<button type="submit">Save Issue Assignment</button>
</form>
</div>
<p><a class="button" href="production.php">← Production Dashboard</a></p>
<style>.form-row{margin-bottom:16px}.form-row label{display:block;font-weight:600;margin-bottom:6px}
.form-row input{width:100%;max-width:700px;box-sizing:border-box;padding:10px}</style>
<?php prod_footer(); ?>
