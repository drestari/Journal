<?php
require_once __DIR__ . '/workflow_v1_common.php';
require_once __DIR__ . '/services/notification_service.php';

$db = db();
$u = wf_require_roles($db, ['admin', 'editor_in_chief', 'editor']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    wf_check_csrf();
    $action = trim((string)($_POST['action'] ?? 'add_reviewer'));

    if ($action === 'resend_invitation') {
        $rid = (int)($_POST['reviewer_id'] ?? 0);
        if ($rid > 0) {
            $token = bin2hex(random_bytes(32));
            $s = $db->prepare("UPDATE ew_reviewer_pool SET invitation_token = ?, token_expires_at = DATE_ADD(NOW(), INTERVAL 72 HOUR), token_used_at = NULL WHERE id = ?");
            $s->execute([$token, $rid]);
            ajsmr_send_reviewer_registration_invitation($db, $rid);
            wf_flash('New reviewer registration invitation email sent.');
        }
        wf_redirect('reviewer_manage.php');
    } else {
        $name = trim((string)($_POST['full_name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $aff = trim((string)($_POST['affiliation'] ?? ''));
        $exp = trim((string)($_POST['expertise'] ?? ''));
        $orcid = trim((string)($_POST['orcid'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            wf_flash('Invalid email address.');
            wf_redirect('reviewer_manage.php');
        }

        $check = $db->prepare("SELECT id, user_id FROM ew_reviewer_pool WHERE LOWER(email) = LOWER(?) LIMIT 1");
        $check->execute([$email]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $rid = (int)$existing['id'];
            if (!empty($existing['user_id'])) {
                wf_flash('A reviewer account with this email is already registered and active.');
            } else {
                $token = bin2hex(random_bytes(32));
                $s = $db->prepare("UPDATE ew_reviewer_pool SET full_name = ?, affiliation = ?, expertise = ?, orcid = ?, invitation_token = ?, token_expires_at = DATE_ADD(NOW(), INTERVAL 72 HOUR), token_used_at = NULL WHERE id = ?");
                $s->execute([$name, $aff, $exp, $orcid, $token, $rid]);
                ajsmr_send_reviewer_registration_invitation($db, $rid);
                wf_flash('Reviewer invitation resent to existing reviewer email.');
            }
        } else {
            $token = bin2hex(random_bytes(32));
            $s = $db->prepare("INSERT INTO ew_reviewer_pool(full_name, email, affiliation, expertise, orcid, invitation_token, token_expires_at) VALUES(?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 72 HOUR))");
            try {
                $s->execute([$name, $email, $aff, $exp, $orcid, $token]);
                $newId = (int)$db->lastInsertId();
                ajsmr_send_reviewer_registration_invitation($db, $newId);
                wf_flash('Reviewer added to pool and registration invitation email sent.');
            } catch (PDOException $e) {
                wf_flash('Reviewer email already exists in database.');
            }
        }
        wf_redirect('reviewer_manage.php');
    }
}

$rows = $db->query("SELECT * FROM ew_reviewer_pool ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
require_once __DIR__ . '/includes/eic_layout.php';
eic_render_header('Reviewer Pool Management', 'Editorial Management &amp; Decision System');
?>
<div class="panel">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
    <div>
      <h1 class="title" style="margin:0 0 4px 0;">Reviewer Pool Management</h1>
      <div class="muted">Search and manage qualified peer reviewers, contact details, affiliations, and subject specializations.</div>
    </div>
    <div>
      <a class="btn light" href="workflow_v1.php">← Back to Workflow V1</a>
    </div>
  </div>

  <?php wf_flash_show(); ?>

  <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:18px;margin-bottom:24px;">
    <h3 style="margin:0 0 14px 0;font-size:15px;color:#092b5f;">+ Invite New Expert Reviewer</h3>
    <form method="post" style="max-width:800px;">
      <input type="hidden" name="csrf" value="<?=wf_h(wf_csrf())?>">
      <input type="hidden" name="action" value="add_reviewer">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
        <div>
          <label style="display:block;font-size:12px;font-weight:700;margin-bottom:5px;">Full Name *</label>
          <input name="full_name" placeholder="e.g. Dr. Jane Smith" required style="width:100%;padding:9px;border:1px solid #cbd5e1;border-radius:6px;">
        </div>
        <div>
          <label style="display:block;font-size:12px;font-weight:700;margin-bottom:5px;">Email Address *</label>
          <input name="email" type="email" placeholder="e.g. jsmith@university.edu" required style="width:100%;padding:9px;border:1px solid #cbd5e1;border-radius:6px;">
        </div>
        <div>
          <label style="display:block;font-size:12px;font-weight:700;margin-bottom:5px;">Academic Affiliation / Institution</label>
          <input name="affiliation" placeholder="e.g. Department of Medicine, University X" style="width:100%;padding:9px;border:1px solid #cbd5e1;border-radius:6px;">
        </div>
        <div>
          <label style="display:block;font-size:12px;font-weight:700;margin-bottom:5px;">ORCID iD</label>
          <input name="orcid" placeholder="0000-0000-0000-0000" style="width:100%;padding:9px;border:1px solid #cbd5e1;border-radius:6px;">
        </div>
      </div>
      <div style="margin-bottom:16px;">
        <label style="display:block;font-size:12px;font-weight:700;margin-bottom:5px;">Subject Expertise &amp; Keywords</label>
        <textarea name="expertise" rows="3" placeholder="e.g. Oncology, Immunology, Clinical Pharmacology" style="width:100%;padding:9px;border:1px solid #cbd5e1;border-radius:6px;"></textarea>
      </div>
      <button class="btn primary" type="submit">Send Reviewer Invitation</button>
    </form>
  </div>

  <h3 style="margin:0 0 12px 0;font-size:16px;color:#092b5f;">Registered Reviewers (<?=count($rows)?>)</h3>
  <table>
    <thead>
      <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Affiliation</th>
        <th>Expertise</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="6" style="text-align:center;color:#64748b;padding:24px;">No peer reviewers registered yet.</td></tr>
      <?php else: ?>
        <?php foreach($rows as $r): ?>
          <?php
            $isRegistered = !empty($r['user_id']);
            $isUsed = !empty($r['token_used_at']);
            $isExpired = (!empty($r['token_expires_at']) && strtotime($r['token_expires_at']) < time());
          ?>
          <tr>
            <td><strong><?=wf_h($r['full_name'])?></strong></td>
            <td><?=wf_h($r['email'])?></td>
            <td><?=wf_h($r['affiliation'] ?: '—')?></td>
            <td><?=wf_h($r['expertise'] ?: '—')?></td>
            <td>
              <?php if ($isRegistered || $isUsed): ?>
                <span class="badge" style="background:#dcfce7;color:#166534;">Registered</span>
              <?php elseif ($isExpired): ?>
                <span class="badge" style="background:#fef3c7;color:#92400e;">Invitation Expired</span>
              <?php else: ?>
                <span class="badge" style="background:#e0f2fe;color:#075985;">Invitation Sent</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!$isRegistered): ?>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="csrf" value="<?=wf_h(wf_csrf())?>">
                  <input type="hidden" name="action" value="resend_invitation">
                  <input type="hidden" name="reviewer_id" value="<?=(int)$r['id']?>">
                  <button type="submit" class="btn light" style="padding:4px 8px;font-size:11px;">Resend Invitation</button>
                </form>
              <?php else: ?>
                <span style="font-size:12px;color:#64748b;">Active</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php
eic_render_footer();
