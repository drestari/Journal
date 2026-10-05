<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (user()) {
    $uRole = strtolower((string)(user()['role'] ?? ''));
    if ($uRole === 'reviewer') {
        redirect('reviewer/index.php');
    } else {
        redirect('dashboard.php');
    }
}

$pdo = db();
$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));

$error = '';
$notice = '';
$success = false;

$poolRow = null;

if ($token !== '') {
    $stmt = $pdo->prepare("SELECT * FROM ew_reviewer_pool WHERE invitation_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $poolRow = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$poolRow) {
    $error = 'Reviewer Invitation Required. Invalid or missing invitation token. Reviewer registration is strictly by invitation only.';
} elseif (!empty($poolRow['token_used_at'])) {
    $notice = 'Invitation Already Used. This reviewer invitation link has already been used to create an account. Please sign in to the Reviewer Portal.';
} elseif (!empty($poolRow['token_expires_at']) && strtotime($poolRow['token_expires_at']) < time()) {
    $expiresFormatted = date('d M Y, H:i', strtotime($poolRow['token_expires_at']));
    $notice = "Reviewer Invitation Expired. This invitation link expired on {$expiresFormatted}. Please contact the AJSMR editorial team for a new invitation.";
}

// Check if user account already exists for this email
if ($poolRow && !$notice && !$error) {
    $emailFromDb = strtolower(trim((string)$poolRow['email']));
    $userCheck = $pdo->prepare("SELECT id, role FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
    $userCheck->execute([$emailFromDb]);
    $existingUser = $userCheck->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        // Link pool if not linked
        if (empty($poolRow['user_id'])) {
            $linkStmt = $pdo->prepare("UPDATE ew_reviewer_pool SET user_id = ?, active = 1, token_used_at = NOW() WHERE id = ?");
            $linkStmt->execute([(int)$existingUser['id'], (int)$poolRow['id']]);
        }
        $notice = 'Account Already Registered. A reviewer account with this email (' . htmlspecialchars($emailFromDb) . ') is already registered. Please sign in to the Reviewer Portal.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $poolRow && !$notice && !$error) {
    try {
        check_csrf();

        // BACKEND EMAIL & ROLE SECURITY ENFORCEMENT:
        // 1. Email is obtained strictly from the DB invitation record. NEVER trust browser-submitted email.
        // 2. Role is hardcoded to 'reviewer'. Never trust browser-submitted role.
        $reviewerEmail = strtolower(trim((string)$poolRow['email']));
        $reviewerRole = 'reviewer';

        $name = trim((string)($_POST['name'] ?? $poolRow['full_name'] ?? ''));
        $affiliation = trim((string)($_POST['affiliation'] ?? $poolRow['affiliation'] ?? ''));
        $orcid = trim((string)($_POST['orcid'] ?? $poolRow['orcid'] ?? ''));
        $expertise = trim((string)($_POST['expertise'] ?? $poolRow['expertise'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if (mb_strlen($name) < 2 || mb_strlen($name) > 180) {
            $error = 'Please enter your full name.';
        } elseif ($affiliation === '' || mb_strlen($affiliation) > 255) {
            $error = 'Please enter your academic affiliation / institution.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must contain at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (!isset($_POST['agree'])) {
            $error = 'Please accept the journal terms and privacy policy.';
        } else {
            // Read actual schema
            $columns = [];
            foreach ($pdo->query('SHOW COLUMNS FROM users')->fetchAll() as $col) {
                $columns[strtolower((string)$col['Field'])] = (string)$col['Field'];
            }

            $pwHash = password_hash($password, PASSWORD_DEFAULT);
            $data = [
                'full_name'     => $name,
                'email'         => $reviewerEmail,
                'password_hash' => $pwHash,
                'role'          => $reviewerRole,
                'active'        => 1
            ];

            $optional = [
                'affiliation' => ($affiliation !== '' ? $affiliation : null),
                'orcid'       => ($orcid !== '' ? $orcid : null),
                'expertise'   => ($expertise !== '' ? $expertise : null)
            ];

            foreach ($optional as $f => $v) {
                if (isset($columns[$f])) $data[$columns[$f]] = $v;
            }

            $insertCols = array_keys($data);
            $sql = 'INSERT INTO users (' . implode(',', array_map(fn($x) => '`' . str_replace('`','``',$x) . '`', $insertCols)) .
                   ') VALUES (' . implode(',', array_fill(0, count($insertCols), '?')) . ')';

            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($data));
            $newUserId = (int)$pdo->lastInsertId();

            // Link reviewer pool record & mark token used
            $upd = $pdo->prepare("UPDATE ew_reviewer_pool SET user_id = ?, full_name = ?, affiliation = ?, expertise = ?, orcid = ?, active = 1, token_used_at = NOW() WHERE id = ?");
            $upd->execute([$newUserId, $name, $affiliation, $expertise, $orcid, (int)$poolRow['id']]);

            $success = true;
        }
    } catch (Throwable $e) {
        error_log('Reviewer registration error: ' . $e->getMessage());
        $error = 'Registration could not be completed. Please contact the editorial office.';
    }
}

$csrfToken = csrf();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>AJSMR | Complete Reviewer Registration</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#f5f7fb;color:#263238;font-family:Arial,Helvetica,sans-serif}
.page{min-height:100vh;display:flex;justify-content:center;padding:35px 20px}
.card{width:100%;max-width:850px;background:#fff;border:1px solid #e9edf3;border-radius:12px;box-shadow:0 12px 40px #10182817;overflow:hidden}
.top{background:linear-gradient(145deg,#092b5f,#0b5fa5);color:#fff;padding:32px 40px}
.brand{display:flex;align-items:center;gap:16px}
.logo{width:55px;height:55px;border-radius:12px;background:#fff;color:#0a4079;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:20px}
h1{margin:0;font-size:26px}
.subtitle{margin-top:6px;font-size:14px;color:#e5eef7}
.content{padding:34px 40px 38px}
.intro{color:#667085;font-size:14px;line-height:1.55;margin:0 0 24px}
.alert{border-radius:8px;padding:14px 16px;margin-bottom:20px;font-size:14px;line-height:1.5}
.error{background:#fff1f0;border:1px solid #ffd1cc;color:#a52a20}
.notice{background:#fefce8;border:1px solid #fef08a;color:#854d0e}
.success-box{background:#effaf3;border:1px solid #c9efd7;color:#176b39;padding:24px;border-radius:8px;text-align:center}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:18px 22px}
.full{grid-column:1/-1}
.field{margin-bottom:2px}
label{display:block;font-size:13px;font-weight:bold;color:#344054;margin-bottom:7px}
input,textarea{width:100%;height:45px;border:1px solid #d8dee8;border-radius:7px;padding:0 12px;font-size:14px;outline:none;background:#fff;color:#263238}
textarea{height:auto;padding:10px}
input:focus,textarea:focus{border-color:#3778b8;box-shadow:0 0 0 3px #3778b81f}
input[readonly]{background:#f1f5f9;color:#64748b;cursor:not-allowed;border-color:#cbd5e1}
.help{display:block;font-size:11px;color:#98a2b3;margin-top:5px}
.pw{position:relative}.pw input{padding-right:60px}
.show{position:absolute;right:0;top:0;height:45px;border:0;background:none;padding:0 12px;color:#376b99;font-weight:bold;font-size:11px;cursor:pointer}
.agree{display:flex;gap:9px;align-items:flex-start;margin:24px 0;color:#667085;font-size:13px;line-height:1.5}
.agree input{width:auto;height:auto;margin-top:3px}
.actions{display:flex;justify-content:space-between;align-items:center;border-top:1px solid #edf0f4;padding-top:23px}
.btn{height:47px;border:0;border-radius:7px;background:#0b5fa5;color:#fff;font-size:14px;font-weight:800;padding:0 26px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center}
.btn:hover{background:#084d88}
</style>
</head>
<body>
<div class="page">
  <div class="card">
    <header class="top">
      <div class="brand">
        <div class="logo">AJ</div>
        <div>
          <h1>Complete Reviewer Registration</h1>
          <div class="subtitle">The American Journal of Science and Medical Research (AJSMR)</div>
        </div>
      </div>
    </header>

    <main class="content">
      <?php if ($success): ?>
        <div class="success-box">
          <h2 style="margin:0 0 10px 0;font-size:22px;">Registration Completed</h2>
          <p style="margin:0 0 20px 0;font-size:15px;color:#2b5338;">Your reviewer account has been created successfully. You may now sign in to access your reviewer workspace and assignments.</p>
          <a href="../login.php" class="btn">Proceed to Sign In</a>
        </div>
      <?php elseif ($notice): ?>
        <div class="alert notice">
          <strong>Notice:</strong> <?=htmlspecialchars($notice)?>
          <div style="margin-top:14px;">
            <a href="../login.php" class="btn" style="height:38px;padding:0 18px;font-size:13px;">Sign In to Reviewer Portal</a>
          </div>
        </div>
      <?php elseif ($error && !$poolRow): ?>
        <div class="alert error">
          <strong>Access Restricted:</strong> <?=htmlspecialchars($error)?>
        </div>
      <?php else: ?>
        <p class="intro">Welcome! Please complete your reviewer profile and set your password to finalize your peer reviewer registration for AJSMR.</p>
        
        <?php if ($error): ?>
          <div class="alert error"><?=htmlspecialchars($error)?></div>
        <?php endif; ?>

        <form method="post" action="register.php?token=<?=urlencode($token)?>" autocomplete="on">
          <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrfToken)?>">
          <input type="hidden" name="token" value="<?=htmlspecialchars($token)?>">

          <div class="grid">
            <div class="field full">
              <label for="email_display">Email Address *</label>
              <input id="email_display" type="email" value="<?=htmlspecialchars((string)$poolRow['email'])?>" readonly disabled>
              <span class="help" style="color:#475569;font-weight:600;">This email address is locked to your reviewer invitation and cannot be changed.</span>
            </div>

            <div class="field">
              <label for="name">Full Name *</label>
              <input id="name" name="name" type="text" value="<?=htmlspecialchars($_POST['name'] ?? $poolRow['full_name'] ?? '')?>" maxlength="180" required>
            </div>

            <div class="field">
              <label for="orcid">ORCID iD</label>
              <input id="orcid" name="orcid" type="text" value="<?=htmlspecialchars($_POST['orcid'] ?? $poolRow['orcid'] ?? '')?>" placeholder="0000-0000-0000-0000" maxlength="100">
              <span class="help">Optional.</span>
            </div>

            <div class="field full">
              <label for="affiliation">Academic Affiliation / Institution *</label>
              <input id="affiliation" name="affiliation" type="text" value="<?=htmlspecialchars($_POST['affiliation'] ?? $poolRow['affiliation'] ?? '')?>" maxlength="255" required>
            </div>

            <div class="field full">
              <label for="expertise">Subject Expertise &amp; Keywords</label>
              <textarea id="expertise" name="expertise" rows="3" placeholder="e.g. Oncology, Clinical Immunology, Pharmacology"><?=htmlspecialchars($_POST['expertise'] ?? $poolRow['expertise'] ?? '')?></textarea>
            </div>

            <div class="field">
              <label for="password">Password *</label>
              <div class="pw">
                <input id="password" name="password" type="password" minlength="8" maxlength="255" required>
                <button class="show" type="button" onclick="toggle('password',this)">SHOW</button>
              </div>
              <span class="help">Minimum 8 characters.</span>
            </div>

            <div class="field">
              <label for="confirm_password">Confirm Password *</label>
              <div class="pw">
                <input id="confirm_password" name="confirm_password" type="password" minlength="8" maxlength="255" required>
                <button class="show" type="button" onclick="toggle('confirm_password',this)">SHOW</button>
              </div>
            </div>
          </div>

          <label class="agree">
            <input type="checkbox" name="agree" value="1" required>
            <span>I agree to serve as a peer reviewer for AJSMR and accept the journal's peer review guidelines, confidentiality policy, and ethical requirements.</span>
          </label>

          <div class="actions">
            <a href="../login.php" style="color:#0b5fa5;text-decoration:none;font-size:13px;font-weight:bold;">← Back to Sign In</a>
            <button class="btn" type="submit">COMPLETE REVIEWER REGISTRATION</button>
          </div>
        </form>
      <?php endif; ?>
    </main>
  </div>
</div>
<script>
function toggle(id,b){const x=document.getElementById(id);if(x.type==='password'){x.type='text';b.textContent='HIDE'}else{x.type='password';b.textContent='SHOW'}}
</script>
</body>
</html>
