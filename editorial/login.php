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
$selectedLoginRole = strtolower((string)($_POST['login_role'] ?? 'author'));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Author / Reviewer Sign In — AJSMR Editorial Management System</title>
<style>
/* ============================================================
   AJSMR INSTITUTIONAL LOGIN — PROFESSIONAL STYLES
   ============================================================ */
*, *::before, *::after {
  box-sizing: border-box;
}

html, body {
  margin: 0;
  padding: 0;
  min-height: 100%;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  background-color: #f5f7fa;
  color: #1e293b;
  line-height: 1.45;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* 1. TOP UTILITY BAR (Institutional Dark Navy) */
.gov-top-bar {
  background-color: #073d72;
  color: #ffffff;
  font-size: 11px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.12);
  flex-shrink: 0;
}

.gov-top-inner {
  max-width: 1240px;
  margin: 0 auto;
  padding: 4px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
}

.gov-top-left {
  display: flex;
  align-items: center;
  gap: 12px;
  letter-spacing: 0.2px;
  font-weight: 500;
}

.gov-top-left span {
  display: inline-flex;
  align-items: center;
}

.bar-sep {
  display: inline-block;
  width: 1px;
  height: 10px;
  background-color: rgba(255, 255, 255, 0.35);
}

.gov-top-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.gov-top-right a {
  color: #dbeafe;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.15s;
}

.gov-top-right a:hover {
  color: #ffffff;
  text-decoration: underline;
}

/* 2. INSTITUTIONAL HEADER */
.inst-header {
  background-color: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
  flex-shrink: 0;
}

.inst-header-inner {
  max-width: 1240px;
  margin: 0 auto;
  padding: 10px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.inst-brand {
  display: flex;
  align-items: center;
  gap: 14px;
  text-decoration: none;
  color: #073d72;
}

.inst-logo {
  width: 56px;
  height: 60px;
  object-fit: contain;
  display: block;
}

.inst-brand-text {
  min-width: 0;
}

.inst-script-title {
  font-family: "Brush Script MT", "Brush Script Std", "Segoe Script", cursive;
  font-style: italic;
  font-size: 21px;
  line-height: 1;
  color: #173c67;
  white-space: nowrap;
}

.inst-stencil-title {
  font-family: Stencil, "Stencil Std", "Impact", fantasy;
  font-size: 19px;
  line-height: 1.15;
  letter-spacing: 0.05em;
  color: #073d72;
  white-space: nowrap;
}

.inst-tagline {
  margin: 2px 0 0 0;
  color: #475569;
  font-size: 10.5px;
  font-weight: 600;
  letter-spacing: 0.25px;
}

.inst-header-badge {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #f8fafc;
  padding: 7px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 7px;
}

.inst-badge-icon {
  font-size: 22px;
  line-height: 1;
  color: #0b5fa5;
}

.inst-badge-text {
  text-align: right;
  line-height: 1.25;
}

.inst-badge-title {
  font-size: 10.5px;
  font-weight: 800;
  letter-spacing: 0.5px;
  color: #092b5f;
  text-transform: uppercase;
}

.inst-badge-desc {
  font-size: 10.5px;
  color: #64748b;
  font-weight: 500;
}

/* 3. MAIN WORKSPACE AREA */
.main-stage {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px 24px;
  box-sizing: border-box;
}

.stage-container {
  max-width: 1140px;
  width: 100%;
  display: grid;
  grid-template-columns: 48% 52%;
  gap: 40px;
  align-items: center;
}

/* LEFT INFORMATION PANEL */
.info-panel {
  padding-right: 8px;
}

.eyebrow-pill {
  display: inline-block;
  background-color: #e0f2fe;
  color: #0369a1;
  font-size: 10.5px;
  font-weight: 800;
  letter-spacing: 0.8px;
  text-transform: uppercase;
  padding: 3px 10px;
  border-radius: 16px;
  margin-bottom: 10px;
  border: 1px solid #bae6fd;
}

.info-title {
  font-size: 26px;
  font-weight: 800;
  color: #092b5f;
  margin: 0 0 10px 0;
  line-height: 1.2;
}

.info-desc {
  font-size: 13.5px;
  color: #475569;
  line-height: 1.5;
  margin: 0 0 14px 0;
}

.info-note {
  font-size: 12.5px;
  color: #64748b;
  line-height: 1.45;
  margin-bottom: 12px;
}

.info-list {
  list-style: none;
  padding: 0;
  margin: 0 0 16px 0;
}

.info-list li {
  position: relative;
  padding-left: 18px;
  margin-bottom: 7px;
  font-size: 12.5px;
  color: #334155;
  line-height: 1.45;
}

.info-list li::before {
  content: "•";
  position: absolute;
  left: 3px;
  top: -1px;
  color: #0b5fa5;
  font-size: 16px;
  font-weight: bold;
}

.info-action-box {
  margin-top: 14px;
  padding-top: 12px;
  border-top: 1px solid #e2e8f0;
}

.info-search-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  background: #ffffff;
  color: #0b5fa5;
  text-decoration: none;
  font-size: 12px;
  font-weight: 700;
  transition: all 0.15s ease;
}

.info-search-btn:hover {
  background: #f0f7fc;
  border-color: #0b5fa5;
}

.info-switch-text {
  margin-top: 12px;
  font-size: 12px;
  color: #64748b;
}

.info-switch-text a {
  color: #0b5fa5;
  text-decoration: none;
  font-weight: 700;
}

.info-switch-text a:hover {
  text-decoration: underline;
}

/* RIGHT: LOGIN CARD */
.login-card-wrapper {
  display: flex;
  justify-content: flex-start;
}

.login-card {
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 24px 30px;
  width: 100%;
  max-width: 440px;
  box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
}

.card-heading {
  text-align: center;
  margin-bottom: 16px;
}

.card-title {
  font-size: 21px;
  font-weight: 800;
  color: #092b5f;
  margin: 0 0 3px 0;
}

.card-subtitle {
  font-size: 12px;
  color: #64748b;
  margin: 0;
}

/* ALERTS */
.login-alert {
  background-color: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
  font-size: 12.5px;
  padding: 9px 12px;
  border-radius: 6px;
  margin-bottom: 14px;
  line-height: 1.4;
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.login-alert-icon {
  font-size: 14px;
  line-height: 1.2;
}

/* FORM FIELDS */
.form-field {
  margin-bottom: 12px;
}

.field-label {
  display: block;
  font-size: 12.5px;
  font-weight: 700;
  color: #334155;
  margin-bottom: 4px;
}

.field-label .req {
  color: #dc2626;
  margin-left: 2px;
}

.field-hint {
  font-size: 11px;
  color: #64748b;
  margin-top: 3px;
}

.field-input, .field-select {
  width: 100%;
  height: 40px;
  border: 1px solid #d5dde7;
  border-radius: 6px;
  padding: 0 12px;
  font-size: 13.5px;
  color: #1e293b;
  background-color: #ffffff;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.field-input:focus, .field-select:focus {
  border-color: #0b5fa5;
  box-shadow: 0 0 0 3px rgba(11, 95, 165, 0.12);
}

.field-input::placeholder {
  color: #94a3b8;
}

.password-container {
  position: relative;
}

.password-container .field-input {
  padding-right: 60px;
}

.btn-toggle-pass {
  position: absolute;
  right: 0;
  top: 0;
  height: 40px;
  background: none;
  border: none;
  padding: 0 12px;
  color: #0b5fa5;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  letter-spacing: 0.3px;
}

.form-options-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 4px 0 14px;
  font-size: 12px;
}

.remember-label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #64748b;
  cursor: pointer;
}

.remember-label input[type="checkbox"] {
  accent-color: #0b5fa5;
  width: 14px;
  height: 14px;
  margin: 0;
}

.forgot-link {
  color: #0b5fa5;
  text-decoration: none;
  font-weight: 600;
  font-size: 12px;
  transition: color 0.15s;
}

.forgot-link:hover {
  text-decoration: underline;
  color: #084980;
}

/* SUBMIT BUTTON */
.btn-login {
  width: 100%;
  height: 42px;
  background-color: #0b5fa5;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  font-size: 13.5px;
  font-weight: 700;
  letter-spacing: 0.4px;
  cursor: pointer;
  transition: background-color 0.15s ease, transform 0.05s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-login:hover {
  background-color: #084980;
}

.btn-login:active {
  transform: translateY(1px);
}

.card-footer-links {
  text-align: center;
  margin-top: 14px;
  padding-top: 12px;
  border-top: 1px solid #f1f5f9;
  font-size: 12px;
  color: #64748b;
}

.card-footer-links a {
  color: #0b5fa5;
  text-decoration: none;
  font-weight: 700;
  margin-left: 4px;
}

.card-footer-links a:hover {
  text-decoration: underline;
}

.card-eic-link {
  text-align: center;
  margin-top: 8px;
  font-size: 11.5px;
  color: #64748b;
}

.card-eic-link a {
  color: #073d72;
  text-decoration: none;
  font-weight: 700;
}

.card-eic-link a:hover {
  text-decoration: underline;
}

/* 4. FOOTER */
.inst-footer {
  background-color: #071e42;
  color: #cbd5e1;
  font-size: 11.5px;
  padding: 10px 24px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  flex-shrink: 0;
}

.inst-footer-inner {
  max-width: 1240px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.footer-copy {
  color: #94a3b8;
}

.footer-nav {
  display: flex;
  align-items: center;
  gap: 14px;
}

.footer-nav a {
  color: #cbd5e1;
  text-decoration: none;
  transition: color 0.15s;
}

.footer-nav a:hover {
  color: #ffffff;
  text-decoration: underline;
}

.footer-sep {
  display: inline-block;
  width: 1px;
  height: 10px;
  background-color: rgba(255, 255, 255, 0.25);
}

/* 5. RESPONSIVE DESIGN */
@media (min-height: 800px) {
  .main-stage {
    padding: 32px 24px;
  }
  .login-card {
    padding: 30px 34px;
  }
}

@media (max-height: 680px) and (min-width: 861px) {
  .gov-top-bar {
    display: none;
  }
  .inst-header-inner {
    padding: 6px 24px;
  }
  .main-stage {
    padding: 10px 24px;
  }
  .form-field {
    margin-bottom: 8px;
  }
  .card-heading {
    margin-bottom: 10px;
  }
}

@media (max-width: 1024px) {
  .stage-container {
    grid-template-columns: 1fr 1fr;
    gap: 28px;
  }
  .info-title {
    font-size: 24px;
  }
}

@media (max-width: 860px) {
  body {
    overflow-y: auto;
  }
  .main-stage {
    padding: 32px 20px;
  }
  .stage-container {
    grid-template-columns: 1fr;
    gap: 32px;
  }
  .info-panel {
    padding-right: 0;
    text-align: center;
  }
  .info-list {
    text-align: left;
    display: inline-block;
  }
  .login-card-wrapper {
    justify-content: center;
  }
  .inst-header-badge {
    display: none;
  }
}

@media (max-width: 580px) {
  .gov-top-inner {
    flex-direction: column;
    align-items: flex-start;
  }
  .gov-top-left {
    flex-wrap: wrap;
    gap: 6px;
  }
  .inst-header-inner {
    padding: 10px 16px;
  }
  .inst-logo {
    width: 48px;
    height: 52px;
  }
  .inst-script-title {
    font-size: 18px;
  }
  .inst-stencil-title {
    font-size: 15px;
  }
  .inst-tagline {
    font-size: 9px;
  }
  .main-stage {
    padding: 20px 16px;
  }
  .login-card {
    padding: 24px 18px;
  }
  .inst-footer-inner {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }
}
</style>
</head>
<body>

<!-- 1. TOP UTILITY BAR (Institutional Dark Navy) -->
<div class="gov-top-bar" role="region" aria-label="Official Publication Details">
  <div class="gov-top-inner">
    <div class="gov-top-left">
      <span>▥ &nbsp;ISSN (Online): 2377-6196</span>
      <i class="bar-sep"></i>
      <span>♙ &nbsp;Open Access</span>
      <i class="bar-sep"></i>
      <span>▣ &nbsp;Quarterly Journal</span>
    </div>
    <div class="gov-top-right">
      <span>AIRA Publisher &bull; Peer Reviewed</span>
      <i class="bar-sep"></i>
      <a href="../index.php" target="_blank" rel="noopener">🌐 &nbsp;Main Journal Website ↗</a>
    </div>
  </div>
</div>

<!-- 2. INSTITUTIONAL HEADER -->
<header class="inst-header" role="banner">
  <div class="inst-header-inner">
    <a class="inst-brand" href="../index.php" title="The American Journal of Science and Medical Research">
      <img class="inst-logo" src="../images/ajsmr-logo.png" onerror="this.src='images/ajsmr-logo.png'" alt="AJSMR Official Seal">
      <div class="inst-brand-text">
        <div class="inst-script-title">The American Journal of</div>
        <div class="inst-stencil-title">SCIENCE AND MEDICAL RESEARCH</div>
        <p class="inst-tagline">Open Access &nbsp; | &nbsp; Peer Reviewed &nbsp; | &nbsp; AIRA Publisher</p>
      </div>
    </a>

    <div class="inst-header-badge" aria-hidden="true">
      <div class="inst-badge-icon">📖</div>
      <div class="inst-badge-text">
        <div class="inst-badge-title">Editorial Management</div>
        <div class="inst-badge-desc">Author &amp; Reviewer Portal</div>
      </div>
    </div>
  </div>
</header>

<!-- 3. MAIN WORKSPACE AREA -->
<main class="main-stage">
  <div class="stage-container">

    <!-- LEFT SIDE: Welcome / Information Panel -->
    <section class="info-panel" aria-labelledby="welcome-title">
      <span class="eyebrow-pill">Editorial Management System</span>
      <h1 class="info-title" id="welcome-title">Author &amp; Reviewer Portal</h1>
      <p class="info-desc">
        Welcome to the AJSMR online submission and peer review portal. Authors can submit new manuscripts, track review progress, and submit revisions. Invited peer reviewers can access evaluation assignments and submit feedback reports.
      </p>

      <p class="info-note">
        Select your account type and enter your credentials to access your dashboard:
      </p>

      <ul class="info-list">
        <li><strong>Authors:</strong> Track manuscript status, reviewer feedback, and production stages.</li>
        <li><strong>Reviewers:</strong> Access assigned manuscripts and submit structured peer review reports.</li>
        <li>Passwords are case-sensitive. Sessions expire automatically after inactivity.</li>
      </ul>

      <div class="info-action-box">
        <a class="info-search-btn" href="../search.php" target="_blank" rel="noopener">
          🔍 &nbsp;Search Public Journal Archive ↗
        </a>

        <div class="info-switch-text">
          New to AJSMR? <a href="register.php">Register as an Author &rarr;</a>
        </div>
      </div>
    </section>

    <!-- RIGHT SIDE: Login Card -->
    <div class="login-card-wrapper">
      <section class="login-card" aria-labelledby="card-title">
        <div class="card-heading">
          <h2 class="card-title" id="card-title">Author / Reviewer Sign In</h2>
          <p class="card-subtitle">Access the AJSMR Editorial Management System</p>
        </div>

        <?php if ($error !== ''): ?>
          <div class="login-alert" role="alert">
            <span class="login-alert-icon">&#9888;</span>
            <div><?=htmlspecialchars($error, ENT_QUOTES, 'UTF-8')?></div>
          </div>
        <?php endif; ?>

        <form method="post" action="login.php" autocomplete="on">
          <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8')?>">

          <!-- Login Role Field (Backend Required for Author/Reviewer routing) -->
          <div class="form-field">
            <label class="field-label" for="login_role">
              Login As <span class="req">*</span>
            </label>
            <select class="field-select" id="login_role" name="login_role" required>
              <option value="author" <?=$selectedLoginRole === 'author' ? 'selected' : ''?>>Author</option>
              <option value="reviewer" <?=$selectedLoginRole === 'reviewer' ? 'selected' : ''?>>Reviewer</option>
            </select>
            <div class="field-hint">Select <strong>Author</strong> or <strong>Reviewer</strong> according to your account type.</div>
          </div>

          <!-- Email / User ID Field -->
          <div class="form-field">
            <label class="field-label" for="email">
              Email Address <span class="req">*</span>
            </label>
            <input class="field-input" id="email" name="email" type="email"
                   value="<?=htmlspecialchars((string)($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8')?>"
                   autocomplete="username" required
                   placeholder="Enter your registered email address">
          </div>

          <!-- Password Field -->
          <div class="form-field">
            <label class="field-label" for="password">
              Password <span class="req">*</span>
            </label>
            <div class="password-container">
              <input class="field-input" id="password" name="password" type="password"
                     autocomplete="current-password" required
                     placeholder="Enter Password">
              <button class="btn-toggle-pass" type="button" onclick="togglePassword()" aria-label="Toggle password visibility">SHOW</button>
            </div>
          </div>

          <!-- Form Options: Remember & Forgot Password -->
          <div class="form-options-row">
            <label class="remember-label">
              <input type="checkbox" name="remember" value="1">
              <span>Remember me</span>
            </label>
            <a class="forgot-link" href="forgot_password.php">Forgot password?</a>
          </div>

          <!-- Submit Button -->
          <button class="btn-login" type="submit">SIGN IN</button>
        </form>

        <div class="card-footer-links">
          Don't have an account? <a href="register.php">Register as Author</a>
        </div>

        <div class="card-eic-link">
          Editor-in-Chief? <a href="eic-login.php">Sign in to EIC Portal &rarr;</a>
        </div>
      </section>
    </div>

  </div>
</main>

<!-- 4. INSTITUTIONAL FOOTER -->
<footer class="inst-footer" role="contentinfo">
  <div class="inst-footer-inner">
    <div class="footer-copy">
      &copy; <?=date('Y')?> The American Journal of Science and Medical Research (AJSMR). All rights reserved.
    </div>
    <nav class="footer-nav" aria-label="Footer Navigation">
      <a href="../openaccesscopyrightpolicy.php" target="_blank" rel="noopener">Privacy</a>
      <i class="footer-sep"></i>
      <a href="../publicationethics.php" target="_blank" rel="noopener">Terms of Use</a>
      <i class="footer-sep"></i>
      <a href="../contactus.php" target="_blank" rel="noopener">Contact</a>
      <i class="footer-sep"></i>
      <a href="../peerreviewpolicy.php" target="_blank" rel="noopener">Accessibility</a>
    </nav>
  </div>
</footer>

<script>
function togglePassword() {
  const pwd = document.getElementById('password');
  const btn = document.querySelector('.btn-toggle-pass');
  if (!pwd || !btn) return;
  if (pwd.type === 'password') {
    pwd.type = 'text';
    btn.textContent = 'HIDE';
  } else {
    pwd.type = 'password';
    btn.textContent = 'SHOW';
  }
}
</script>
</body>
</html>
