<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/config.php';

if (user()) {
    $uRole = strtolower((string)(user()['role'] ?? ''));
    if ($uRole === 'editor_in_chief') {
        redirect('dashboard.php');
    } else {
        redirect('login.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        check_csrf();
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif ($password === '') {
            $error = 'Please enter your password.';
        } else {
            $stmt = db()->prepare(
                'SELECT id, full_name, email, password_hash, role, active
                 FROM users
                 WHERE LOWER(email) = LOWER(?)
                 LIMIT 1'
            );
            $stmt->execute([$email]);
            $u = $stmt->fetch();

            $actualRole = strtolower((string)($u['role'] ?? ''));

            // STRICT EIC ACCESS ENFORCEMENT:
            // 1. Password verification
            // 2. User must exist and active == 1
            // 3. User actual DB role MUST BE 'editor_in_chief'. Rejects authors, reviewers, or any other role.
            if (!$u || !password_verify($password, (string)$u['password_hash']) || $actualRole !== 'editor_in_chief') {
                $error = 'Invalid email, password, or account type for Editor-in-Chief access.';
            } elseif ((int)$u['active'] !== 1) {
                $error = 'Your Editor-in-Chief account is not active. Please contact the editorial office.';
            } else {
                if (password_needs_rehash((string)$u['password_hash'], PASSWORD_DEFAULT)) {
                    $rh = db()->prepare('UPDATE users SET password_hash=? WHERE id=?');
                    $rh->execute([password_hash($password, PASSWORD_DEFAULT), $u['id']]);
                }

                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id' => (int)$u['id'],
                    'name' => (string)$u['full_name'],
                    'email' => (string)$u['email'],
                    'role' => 'editor_in_chief',
                    'login_as' => 'editor_in_chief'
                ];

                audit('LOGIN');

                redirect('dashboard.php');
            }
        }
    } catch (Throwable $e) {
        $error = 'Unable to sign in at this time. Please contact the editorial office.';
    }
}
$csrfToken = csrf();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>AJSMR | Editor-in-Chief Sign In</title>
<style>
*{box-sizing:border-box}
body{margin:0;min-height:100vh;font-family:Arial,Helvetica,sans-serif;background:#f5f7fb;color:#263238}
.shell{min-height:100vh;display:grid;grid-template-columns:45% 55%}
.brand{background:linear-gradient(145deg,#071e42,#092b5f,#0b4d91);color:#fff;padding:56px;display:flex;flex-direction:column;justify-content:space-between}
.logo{width:66px;height:66px;border-radius:14px;background:#fff;color:#0a4079;display:flex;align-items:center;justify-content:center;font-size:25px;font-weight:800;margin-bottom:30px}
.brand h2{font-size:41px;margin:0 0 12px}
.sub{font-size:16px;line-height:1.55;max-width:430px;color:#eef5fb}
.features{list-style:none;padding:0;margin:42px 0}
.features li{margin:17px 0;font-size:15px;padding-left:27px}
.features li:before{content:"✓";margin-left:-27px;margin-right:12px;font-weight:bold;color:#60a5fa}
.brandfoot{font-size:13px;color:#d7e4ef}
.form{background:#fff;display:flex;align-items:center;justify-content:center;padding:42px}
.wrap{width:100%;max-width:475px}
.eyebrow{text-transform:uppercase;font-size:12px;font-weight:800;letter-spacing:1.6px;color:#0b5fa5;margin-bottom:10px}
h1{font-size:29px;margin:0 0 10px;color:#092b5f}
.intro{font-size:15px;color:#697586;line-height:1.55;margin:0 0 28px}
.alert{border-radius:8px;padding:14px;margin-bottom:20px;font-size:14px;line-height:1.5}
.error{background:#fff1f0;border:1px solid #ffd1cc;color:#a52a20}
.field{margin-bottom:20px}
label{display:block;font-size:13px;font-weight:bold;color:#344054;margin-bottom:7px}
input[type=email],input[type=password]{width:100%;height:48px;border:1px solid #d8dee8;border-radius:7px;padding:0 14px;font-size:15px;outline:none;background:#fff;color:#263238}
input:focus{border-color:#0b5fa5;box-shadow:0 0 0 3px rgba(11,95,165,0.12)}
.pass{position:relative}
.pass input{padding-right:65px}
.toggle{position:absolute;right:0;top:0;height:48px;border:0;background:none;padding:0 14px;color:#0b5fa5;font-weight:bold;font-size:12px;cursor:pointer}
.options{display:flex;justify-content:space-between;align-items:center;margin:2px 0 24px;font-size:13px}
.remember{display:flex;gap:8px;align-items:center;color:#667085}
.remember input{margin:0}
.forgot{color:#0b5fa5;text-decoration:none;font-weight:bold}
.btn{width:100%;height:50px;border:0;border-radius:7px;background:#092b5f;color:#fff;font-weight:800;font-size:15px;cursor:pointer;transition:background 0.2s}
.btn:hover{background:#071e42}
.public-link{text-align:center;margin-top:26px;padding-top:22px;border-top:1px solid #edf0f4;color:#667085;font-size:14px}
.public-link a{color:#0b5fa5;text-decoration:none;font-weight:bold;margin-left:5px}
.copy{text-align:center;margin-top:32px;color:#98a2b3;font-size:11px}
@media(max-width:900px){.shell{grid-template-columns:1fr}.brand{padding:38px 30px;min-height:340px}.brand h2{font-size:34px}.form{padding:38px 24px}}
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
        <li>Executive Editorial Control Panel</li>
        <li>Manuscript screening &amp; decision management</li>
        <li>Peer review oversight &amp; reviewer assignment</li>
        <li>Issue publication &amp; catalog management</li>
      </ul>
    </div>
    <div class="brandfoot">
      <strong>Advaitha Innovative Research Association (AIRA)</strong><br>
      Editor-in-Chief Access Portal
    </div>
  </section>

  <main class="form">
    <div class="wrap">
      <div class="eyebrow">EXECUTIVE EDITORIAL OFFICE</div>
      <h1>Editor-in-Chief Sign In</h1>
      <p class="intro">Enter your credentials to access the Editor-in-Chief Management Portal.</p>

      <?php if ($error): ?>
        <div class="alert error"><?=htmlspecialchars($error)?></div>
      <?php endif; ?>

      <form method="post" action="eic-login.php" autocomplete="on">
        <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrfToken)?>">

        <div class="field">
          <label for="email">Email address</label>
          <input id="email" name="email" type="email" value="<?=htmlspecialchars($_POST['email'] ?? '')?>" autocomplete="username" required placeholder="eic@ajsmrjournal.com">
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="pass">
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button class="toggle" type="button" onclick="togglePassword()">SHOW</button>
          </div>
        </div>

        <div class="options">
          <label class="remember">
            <input type="checkbox" name="remember" value="1">
            <span>Remember me</span>
          </label>
          <a class="forgot" href="mailto:editorajsmr@gmail.com?subject=AJSMR%20EIC%20Password%20Reset">Forgot password?</a>
        </div>

        <button class="btn" type="submit">SIGN IN AS EDITOR-IN-CHIEF</button>
      </form>

      <div class="public-link">
        Author or Reviewer? <a href="login.php">Go to Public Sign In</a>
      </div>

      <div class="copy">
        &copy; <?=date('Y')?> The American Journal of Science and Medical Research. All rights reserved.
      </div>
    </div>
  </main>
</div>
<script>
function togglePassword(){const x=document.getElementById('password'),b=document.querySelector('.toggle');if(x.type==='password'){x.type='text';b.textContent='HIDE'}else{x.type='password';b.textContent='SHOW'}}
</script>
</body>
</html>
