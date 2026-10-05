<?php
require_once __DIR__ . '/production_common.php';

$db = db();
$u = prod_user($db);

$mid = (int)($_GET['id'] ?? $_POST['manuscript_id'] ?? 0);
if (!$mid) {
    exit('Invalid manuscript ID.');
}

$m = prod_ms($db, $mid);
prod_ensure($db, $mid, (int)$u['id']);

// Load latest galley proof
$stmt = $db->prepare("SELECT * FROM ew_galley_proofs WHERE manuscript_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$mid]);
$proof = $stmt->fetch(PDO::FETCH_ASSOC);

// Load author proof approval
$stmt = $db->prepare("SELECT * FROM ew_author_proof_approval WHERE manuscript_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$mid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    $stmt = $db->prepare("INSERT INTO ew_author_proof_approval (manuscript_id, proof_id, decision) VALUES (?, ?, 'pending')");
    $stmt->execute([$mid, $proof['id'] ?? null]);
    $stmt = $db->prepare("SELECT * FROM ew_author_proof_approval WHERE manuscript_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$mid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
}

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    prod_check_csrf();

    $decision = $_POST['decision'] ?? 'pending';
    $comments = trim($_POST['comments'] ?? '');

    if (!$proof) {
        $error = 'No galley proof exists for this manuscript.';
    } elseif (!in_array($decision, ['pending', 'approved', 'corrections_requested'], true)) {
        $error = 'Invalid author proof decision.';
    } else {
        $approvedAt = ($decision === 'approved') ? date('Y-m-d H:i:s') : null;

        $stmt = $db->prepare("
            UPDATE ew_author_proof_approval
            SET proof_id = ?, author_id = ?, decision = ?, comments = ?, approved_at = ?
            WHERE id = ?
        ");
        $stmt->execute([$proof['id'], (int)$u['id'], $decision, $comments, $approvedAt, $row['id']]);

        // Sync production.proof_status
        // approved → APPROVED, corrections_requested → SENT (back for review)
        $prodProofStatus = ($decision === 'approved') ? 'APPROVED' : 'SENT';
        $stmt = $db->prepare("UPDATE production SET proof_status = ? WHERE manuscript_id = ?");
        $stmt->execute([$prodProofStatus, $mid]);

        prod_log($db, $mid, (int)$u['id'], 'author_proof_update', 'Decision: ' . $decision);

        sendWorkflowNotification($db, 'GALLERY_PROOF_RESPONSE', $mid, ['decision' => $decision, 'comments' => $comments]);

        $row['decision'] = $decision;
        $row['comments'] = $comments;
        $row['approved_at'] = $approvedAt;

        $success = ($decision === 'approved')
            ? 'Author proof approval saved successfully.'
            : 'Author proof decision saved successfully.';
    }
}

prod_header('Author Proof Approval', $u);
?>

<div class="panel">
    <h1>Author Proof Approval</h1>

    <p>
        <strong><?= prod_h($m['manuscript_no']) ?></strong>
        — <?= prod_h($m['title']) ?>
    </p>

    <p>
        Latest proof:
        <?php if ($proof): ?>
            <strong>Version <?= prod_h($proof['proof_version']) ?></strong>
            —
            <span class="badge"><?= prod_h($proof['status']) ?></span>
        <?php else: ?>
            No galley proof created.
        <?php endif; ?>
    </p>

    <?php if ($success): ?>
        <div class="ok"><?= prod_h($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="err"><?= prod_h($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= prod_h(prod_csrf()) ?>">
        <input type="hidden" name="manuscript_id" value="<?= (int)$mid ?>">

        <label for="decision">Author Proof Decision</label>
        <select name="decision" id="decision">
            <?php foreach (['pending', 'approved', 'corrections_requested'] as $x): ?>
                <option value="<?= prod_h($x) ?>"
                    <?= (($row['decision'] ?? 'pending') === $x) ? 'selected' : '' ?>>
                    <?= prod_h(ucwords(str_replace('_', ' ', $x))) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="comments">Comments</label>
        <textarea name="comments" id="comments"><?= prod_h($row['comments'] ?? '') ?></textarea>

        <button type="submit">Save Proof Approval</button>
    </form>
</div>

<p>
    <a class="button" href="production.php">← Production Dashboard</a>
</p>

<?php prod_footer(); ?>
