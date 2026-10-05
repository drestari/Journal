<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/config.php';

if (user()) { redirect('dashboard.php'); }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        check_csrf();
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($email === '' || $newPassword === '' || $confirmPassword === '') {
            $error = 'Password fields empty';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Passwords do not match';
        } else {
            // Search for existing Author or Reviewer account
            $stmt = db()->prepare(
                'SELECT id, full_name, email, role, active
                 FROM users
                 WHERE LOWER(email) = LOWER(?) AND role IN (\'author\', \'reviewer\')
                 LIMIT 1'
            );
            $stmt->execute([$email]);
            $u = $stmt->fetch();

            if (!$u) {
                $error = 'Email not found';
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $upd = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $res = $upd->execute([$hash, $u['id']]);

                if ($res) {
                    $success = 'Password updated successfully.';
                    audit('PASSWORD_RESET', null, "Password reset for user ID {$u['id']} ({$u['role']})");
                } else {
                    $error = 'Database/update failure';
                }
            }
        }
    } catch (Throwable $e) {
        $error = 'Database/update failure';
    }
}
$csrfToken = csrf();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>AJSMR Editorial Office | Forgot Password</title>
<style>
*{box-sizing:border-box}
body{margin:0;min-height:100vh;font-family:Arial,Helvetica,sans-serif;background:#f5f7fb;color:#263238}
.shell{min-height:100vh;display:grid;grid-template-columns:45% 55%}
.brand{background:linear-gradient(145deg,#092b5f,#0b4d91,#1769aa);color:#fff;padding:56px;display:flex;flex-direction:column;justify-content:space-between}
.logo{width:66px;height:66px;border-radius:14px;background:#fff;color:#0a4079;display:flex;align-items:center;justify-content:center;font-size:25px;font-weight:800;margin-bottom:30px}
.brand h2{font-size:43px;margin:0 0 12px}.sub{font-size:17px;line-height:1.55;max-width:430px;color:#eef5fb}
.features{list-style:none;padding:0;margin:42px 0}.features li{margin:17px 0;font-size:15px;padding-left:27px}.features li:before{content:"✓";margin-left:-27px;margin-right:12px;font-weight:bold}
.brandfoot{font-size:13px;color:#d7e4ef}
.form{background:#fff;display:flex;align-items:center;justify-content:center;padding:42px}.wrap{width:100%;max-width:475px}
.eyebrow{text-transform:uppercase;font-size:12px;font-weight:bold;letter-spacing:1.6px;color:#4776a5;margin-bottom:10px}
h1{font-size:31px;margin:0 0 10px;color:#172b4d}.intro{font-size:15px;color:#697586;line-height:1.55;margin:0 0 28px}
.alert{border-radius:8px;padding:12px 14px;margin-bottom:18px;font-size:14px}
.error{background:#fff1f0;border:1px solid #ffd1cc;color:#a52a20}
.success-msg{background:#edf9f0;border:1px solid #c3e9cb;color:#1e7e34}
.field{margin-bottom:18px}
label{display:block;font-size:13px;font-weight:bold;color:#344054;margin-bottom:7px}
input[type=email],input[type=password]{width:100%;height:48px;border:1px solid #d8dee8;border-radius:7px;padding:0 14px;font-size:15px;outline:none;background:#fff;color:#263238}
input:focus{border-color:#3778b8;box-shadow:0 0 0 3px #3778b81f}
.btn{width:100%;height:49px;border:0;border-radius:7px;background:#0b5fa5;color:#fff;font-weight:800;font-size:15px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;text-decoration:none}
.btn:hover{background:#084d88}
.btn-secondary{background:#6c757d;margin-top:12px}
.btn-secondary:hover{background:#5a6268}
.login-link{text-align:center;margin-top:23px;padding-top:22px;border-top:1px solid #edf0f4;color:#667085;font-size:14px}
.login-link a{color:#0b5fa5;text-decoration:none;font-weight:bold;margin-left:5px}
.copy{text-align:center;margin-top:32px;color:#98a2b3;font-size:11px}
@media(max-width:900px){.shell{grid-template-columns:1fr}.brand{padding:38px 30px;min-height:360px}.brand h2{font-size:36px}.form{padding:38px 24px}}
</style>
</head>
<body>
<div class="shell">
<section class="brand">
  <div>
    <div class="logo">AJ</div>
    <h2>AJSMR</h2>
    <div class="sub">The American Journal of Science and Medical Research</div>
    <ul class="features">
      <li>Secure manuscript submission and tracking</li>
      <li>Editorial and peer-review workflow</li>
      <li>Revision and decision management</li>
      <li>Production and publication tracking</li>
    </ul>
  </div>
  <div class="brandfoot">
    <strong>Advaitha Innovative Research Association (AIRA)</strong><br>Editorial Management System
  </div>
</section>
<main class="form">
  <div class="wrap">
    <div class="eyebrow">Editorial Office</div>
    <h1>Forgot Password</h1>
    <p class="intro">Enter your Author or Reviewer email address and new password below to update your account.</p>

    <?php if($error): ?>
      <div class="alert error"><?=e($error)?></div>
    <?php endif; ?>

    <?php if($success): ?>
      <div class="alert success-msg"><?=e($success)?></div>
      <a href="login.php" class="btn">Return to Login</a>
    <?php else: ?>
      <form method="post" action="forgot_password.php" autocomplete="off">
        <input type="hidden" name="csrf" value="<?=e($csrfToken)?>">
        
        <div class="field">
          <label for="email">Email ID</label>
          <input id="email" name="email" type="email" value="<?=e($_POST['email']??'')?>" autocomplete="email" required>
        </div>
        
        <div class="field">
          <label for="new_password">New Password</label>
          <input id="new_password" name="new_password" type="password" autocomplete="new-password" required>
        </div>
        
        <div class="field">
          <label for="confirm_password">Confirm New Password</label>
          <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required>
        </div>
        
        <button class="btn" type="submit">Update Password</button>
      </form>
      
      <div class="login-link">
        Remembered your password? <a href="login.php">Back to Login</a>
      </div>
    <?php endif; ?>

    <div class="copy">© <?=date('Y')?> The American Journal of Science and Medical Research. All rights reserved.</div>
  </div>
</main>
</div>
</body>
</html>
