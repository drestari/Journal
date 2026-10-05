<?php
/**
 * typesetting.php
 * Uses production.typesetting_status (ENUM: PENDING, IN_PROGRESS, COMPLETED)
 * ew_typesetting does not exist — all data lives in the production table.
 */
require_once __DIR__ . '/production_common.php';

$db = db();
$u  = prod_user($db);
$mid = (int)($_GET['id'] ?? 0);
if (!$mid) exit('Invalid manuscript ID.');

$m = prod_ms($db, $mid);
prod_ensure($db, $mid, (int)$u['id']);

// Fetch production row (typesetting_status is a column there)
$stmt = $db->prepare("SELECT * FROM production WHERE manuscript_id = ? LIMIT 1");
$stmt->execute([$mid]);
$prod = $stmt->fetch(PDO::FETCH_ASSOC);

// Map production ENUM values to display values
$currentStatus = strtolower($prod['typesetting_status'] ?? 'pending');
// Normalise: PENDING→not_started, IN_PROGRESS→in_progress, COMPLETED→completed
$statusMap = ['pending'=>'not_started','in_progress'=>'in_progress','completed'=>'completed'];
$displayStatus = $statusMap[$currentStatus] ?? 'not_started';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    prod_check_csrf();

    $status = $_POST['status'] ?? 'not_started';
    $notes  = trim($_POST['notes'] ?? '');

    $allowed = ['not_started', 'in_progress', 'completed'];
    if (!in_array($status, $allowed, true)) {
        $error = 'Invalid typesetting status.';
    } else {
        // Map back to production ENUM
        $enumMap = ['not_started'=>'PENDING','in_progress'=>'IN_PROGRESS','completed'=>'COMPLETED'];
        $enumVal = $enumMap[$status];

        $db->prepare("UPDATE production SET typesetting_status = ?, notes = CONCAT(COALESCE(notes,''),' [Typesetting: ',:note,']') WHERE manuscript_id = :mid")
           ->execute([':note'=>$notes, ':mid'=>$mid]);
        // Simpler direct update
        $db->prepare("UPDATE production SET typesetting_status = ? WHERE manuscript_id = ?")
           ->execute([$enumVal, $mid]);

        prod_log($db, $mid, (int)$u['id'], 'typesetting_update', 'Typesetting status: ' . $status);

        $displayStatus = $status;
        $stmt = $db->prepare("SELECT * FROM production WHERE manuscript_id = ? LIMIT 1");
        $stmt->execute([$mid]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<?php prod_header('Typesetting', $u); ?>

<div class="panel">
<h1>Typesetting</h1>
<p><strong><?= prod_h($m['manuscript_no']) ?></strong> — <?= prod_h($m['title']) ?></p>

<?php if ($error): ?>
<div class="err"><?= prod_h($error) ?></div>
<?php endif; ?>

<form method="post">
<input type="hidden" name="csrf" value="<?= prod_h(prod_csrf()) ?>">

<label><strong>Typesetting Status</strong></label>
<select name="status">
<?php foreach(['not_started'=>'Not Started','in_progress'=>'In Progress','completed'=>'Completed'] as $val=>$lbl): ?>
<option value="<?= $val ?>" <?= $displayStatus===$val?'selected':'' ?>><?= $lbl ?></option>
<?php endforeach; ?>
</select>

<label><strong>Typesetting Notes</strong></label>
<textarea name="notes" placeholder="Enter typesetting notes..."></textarea>

<button type="submit">Save Typesetting</button>
</form>
</div>

<p><a class="button" href="production.php">← Production Dashboard</a></p>

<?php prod_footer(); ?>