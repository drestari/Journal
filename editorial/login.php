<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/config.php';

if (user()) { redirect('dashboard.php'); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        check_csrf();
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $loginRole = strtolower(trim((string)($_POST['login_role'] ?? 'author')));

        if (!in_array($loginRole, ['author', 'reviewer'], true)) {
            $error = 'Please select your account type: Author or Reviewer.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif ($password === '') {
            $error = 'Please enter your password.';
        } else {
            $stmt = db()->prepare(
                'SELECT id, full_name, email, password_hash, role, active
                 FROM users
                 WHERE LOWER(email)=LOWER(?)
                 LIMIT 1'
            );
            $stmt->execute([$email]);
            $u = $stmt->fetch();

            $actualRole = strtolower((string)($u['role'] ?? ''));

            // LOGIN ROLE SECURITY ENFORCEMENT:
            // 1. Authenticate user credentials.
            // 2. Ensure actual DB role strictly matches the selected public login role (Author or Reviewer).
            // 3. EIC uses dedicated eic-login.php.
            if (!$u || !password_verify($password, (string)$u['password_hash']) || $actualRole !== $loginRole) {
                $error = 'Invalid email, password, or selected account type.';
            } elseif ((int)$u['active'] !== 1) {
                $error = 'Your account is not active. Please contact the editorial office.';
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
                    'role' => $actualRole,
                    'login_as' => $actualRole
                ];

                audit('LOGIN');

                if ($actualRole === 'author') {
                    redirect('author/index.php');
                } elseif ($actualRole === 'reviewer') {
                    redirect('reviewer/index.php');
                } else {
                    redirect('dashboard.php');
                }
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
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>AJSMR Editorial Office | Sign In</title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Arial,Helvetica,sans-serif;background:#f5f7fb;color:#263238}
.shell{min-height:100vh;display:grid;grid-template-columns:45% 55%}.brand{background:linear-gradient(145deg,#092b5f,#0b4d91,#1769aa);color:#fff;padding:56px;display:flex;flex-direction:column;justify-content:space-between}
.logo{width:66px;height:66px;border-radius:14px;background:#fff;color:#0a4079;display:flex;align-items:center;justify-content:center;font-size:25px;font-weight:800;margin-bottom:30px}
.brand h2{font-size:43px;margin:0 0 12px}.sub{font-size:17px;line-height:1.55;max-width:430px;color:#eef5fb}
.features{list-style:none;padding:0;margin:42px 0}.features li{margin:17px 0;font-size:15px;padding-left:27px}.features li:before{content:"✓";margin-left:-27px;margin-right:12px;font-weight:bold}
.brandfoot{font-size:13px;color:#d7e4ef}.form{background:#fff;display:flex;align-items:center;justify-content:center;padding:42px}.wrap{width:100%;max-width:475px}
.eyebrow{text-transform:uppercase;font-size:12px;font-weight:bold;letter-spacing:1.6px;color:#4776a5;margin-bottom:10px}
h1{font-size:31px;margin:0 0 10px;color:#172b4d}.intro{font-size:15px;color:#697586;line-height:1.55;margin:0 0 28px}
.alert{border-radius:8px;padding:12px 14px;margin-bottom:18px;font-size:14px}.error{background:#fff1f0;border:1px solid #ffd1cc;color:#a52a20}
.field{margin-bottom:18px}label{display:block;font-size:13px;font-weight:bold;color:#344054;margin-bottom:7px}.role-note{font-size:12px;line-height:1.45;color:#7b8794;margin-top:7px}
input[type=email],input[type=password],select[name=login_role]{width:100%;height:48px;border:1px solid #d8dee8;border-radius:7px;padding:0 14px;font-size:15px;outline:none;background:#fff;color:#263238}
input:focus{border-color:#3778b8;box-shadow:0 0 0 3px #3778b81f}.pass{position:relative}.pass input{padding-right:65px}.toggle{position:absolute;right:0;top:0;height:48px;border:0;background:none;padding:0 14px;color:#376b99;font-weight:bold;font-size:12px;cursor:pointer}
.options{display:flex;justify-content:space-between;align-items:center;margin:2px 0 22px;font-size:13px}.remember{display:flex;gap:8px;align-items:center;color:#667085}.remember input{margin:0}.forgot,.register a{color:#0b5fa5;text-decoration:none;font-weight:bold}.btn{width:100%;height:49px;border:0;border-radius:7px;background:#0b5fa5;color:#fff;font-weight:800;cursor:pointer}.btn:hover{background:#084d88}
.register{text-align:center;margin-top:23px;padding-top:22px;border-top:1px solid #edf0f4;color:#667085;font-size:14px}.register a{margin-left:5px}.contact{text-align:center;margin-top:20px;font-size:13px;color:#98a2b3}.contact a{color:#667085}.copy{text-align:center;margin-top:32px;color:#98a2b3;font-size:11px}
@media(max-width:900px){.shell{grid-template-columns:1fr}.brand{padding:38px 30px;min-height:360px}.brand h2{font-size:36px}.form{padding:38px 24px}}
</style></head>
<body><div class="shell">
<section class="brand"><div><div class="logo">AJ</div><h2>AJSMR</h2><div class="sub">The American Journal of Science and Medical Research</div>
<ul class="features"><li>Secure manuscript submission and tracking</li><li>Editorial and peer-review workflow</li><li>Revision and decision management</li><li>Production and publication tracking</li></ul></div>
<div class="brandfoot"><strong>Advaitha Innovative Research Association (AIRA)</strong><br>Editorial Management System</div></section>
<main class="form"><div class="wrap"><div class="eyebrow">Editorial Office</div><h1>Sign in to your account</h1>
<p class="intro">Select Author or Reviewer, then sign in to your AJSMR account.</p>
<?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
<form method="post" action="login.php" autocomplete="on"><input type="hidden" name="csrf" value="<?=e($csrfToken)?>">
<div class="field">
<label for="login_role">Login As</label>
<select id="login_role" name="login_role" required>
<option value="author" <?php if (strtolower((string)($_POST['login_role'] ?? 'author')) === 'author') echo 'selected'; ?>>Author</option>
<option value="reviewer" <?php if (strtolower((string)($_POST['login_role'] ?? '')) === 'reviewer') echo 'selected'; ?>>Reviewer</option>
</select>
<div class="role-note">Select <strong>Author</strong> or <strong>Reviewer</strong> according to your account type.</div>
</div>
<div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" value="<?=e($_POST['email']??'')?>" autocomplete="username" required></div>
<div class="field"><label for="password">Password</label><div class="pass"><input id="password" name="password" type="password" autocomplete="current-password" required><button class="toggle" type="button" onclick="togglePassword()">SHOW</button></div></div>
<div class="options"><label class="remember"><input type="checkbox" name="remember" value="1"><span>Remember me</span></label><a class="forgot" href="mailto:editorajsmr@gmail.com?subject=AJSMR%20Password%20Reset">Forgot password?</a></div>
<button class="btn" type="submit">SIGN IN</button></form>
<div class="register">Don't have an account?<a href="register.php">Register as Author</a></div>
<div class="contact">Editor-in-Chief? <a href="eic-login.php" style="font-weight:bold;color:#0b5fa5;">Sign in to EIC Portal</a> &bull; Need assistance? <a href="mailto:editorajsmr@gmail.com">Contact Office</a></div>
<div class="copy">© <?=date('Y')?> The American Journal of Science and Medical Research. All rights reserved.</div>
</div></main></div>
<script>function togglePassword(){const x=document.getElementById('password'),b=document.querySelector('.toggle');if(x.type==='password'){x.type='text';b.textContent='HIDE'}else{x.type='password';b.textContent='SHOW'}}</script>
</body></html>
