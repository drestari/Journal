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
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in — AJSMR Editorial Management System</title>
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
  line-height: 1.5;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* 1. TOP UTILITY BAR (Institutional Dark Navy) */
.gov-top-bar {
  background-color: #073d72;
  color: #ffffff;
  font-size: 11.5px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.gov-top-inner {
  max-width: 1240px;
  margin: 0 auto;
  padding: 6px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
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
  height: 11px;
  background-color: rgba(255, 255, 255, 0.35);
}

.gov-top-right {
  display: flex;
  align-items: center;
  gap: 14px;
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
}

.inst-header-inner {
  max-width: 1240px;
  margin: 0 auto;
  padding: 16px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
}

.inst-brand {
  display: flex;
  align-items: center;
  gap: 18px;
  text-decoration: none;
  color: #073d72;
}

.inst-logo {
  width: 68px;
  height: 72px;
  object-fit: contain;
  display: block;
}

.inst-brand-text {
  min-width: 0;
}

.inst-script-title {
  font-family: "Brush Script MT", "Brush Script Std", "Segoe Script", cursive;
  font-style: italic;
  font-size: 24px;
  line-height: 1;
  color: #173c67;
  white-space: nowrap;
}

.inst-stencil-title {
  font-family: Stencil, "Stencil Std", "Impact", fantasy;
  font-size: 21px;
  line-height: 1.15;
  letter-spacing: 0.05em;
  color: #073d72;
  white-space: nowrap;
}

.inst-tagline {
  margin: 4px 0 0 0;
  color: #475569;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.3px;
}

.inst-header-badge {
  display: flex;
  align-items: center;
  gap: 14px;
  background: #f8fafc;
  padding: 10px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}

.inst-badge-icon {
  font-size: 26px;
  line-height: 1;
  color: #0b5fa5;
}

.inst-badge-text {
  text-align: right;
  line-height: 1.3;
}

.inst-badge-title {
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.5px;
  color: #092b5f;
  text-transform: uppercase;
}

.inst-badge-desc {
  font-size: 11px;
  color: #64748b;
  font-weight: 500;
}

/* 3. MAIN WORKSPACE AREA */
.main-stage {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px 24px;
}

.stage-container {
  max-width: 1180px;
  width: 100%;
  display: grid;
  grid-template-columns: 46% 54%;
  gap: 56px;
  align-items: center;
}

/* LEFT INFORMATION PANEL */
.info-panel {
  padding-right: 12px;
}

.eyebrow-pill {
  display: inline-block;
  background-color: #e0f2fe;
  color: #0369a1;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 1px;
  text-transform: uppercase;
  padding: 5px 12px;
  border-radius: 20px;
  margin-bottom: 16px;
  border: 1px solid #bae6fd;
}

.info-title {
  font-size: 32px;
  font-weight: 800;
  color: #092b5f;
  margin: 0 0 16px 0;
  line-height: 1.2;
}

.info-desc {
  font-size: 15px;
  color: #475569;
  line-height: 1.6;
  margin: 0 0 24px 0;
}

.info-note {
  font-size: 13.5px;
  color: #64748b;
  line-height: 1.55;
  margin-bottom: 22px;
}

.info-list {
  list-style: none;
  padding: 0;
  margin: 0 0 28px 0;
}

.info-list li {
  position: relative;
  padding-left: 20px;
  margin-bottom: 10px;
  font-size: 13.5px;
  color: #334155;
  line-height: 1.5;
}

.info-list li::before {
  content: "•";
  position: absolute;
  left: 4px;
  top: -1px;
  color: #0b5fa5;
  font-size: 18px;
  font-weight: bold;
}

.info-action-box {
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.info-search-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  background: #ffffff;
  color: #0b5fa5;
  text-decoration: none;
  font-size: 13px;
  font-weight: 700;
  transition: all 0.15s ease;
}

.info-search-btn:hover {
  background: #f0f7fc;
  border-color: #0b5fa5;
}

.info-switch-text {
  margin-top: 18px;
  font-size: 13px;
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
  border-radius: 14px;
  padding: 40px 38px;
  width: 100%;
  max-width: 470px;
  box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05);
}

.card-heading {
  text-align: center;
  margin-bottom: 26px;
}

.card-title {
  font-size: 24px;
  font-weight: 800;
  color: #092b5f;
  margin: 0 0 6px 0;
}

.card-subtitle {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

/* ALERTS */
.login-alert {
  background-color: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
  font-size: 13px;
  padding: 12px 14px;
  border-radius: 7px;
  margin-bottom: 22px;
  line-height: 1.45;
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.login-alert-icon {
  font-size: 15px;
  line-height: 1.2;
}

/* FORM FIELDS */
.form-field {
  margin-bottom: 20px;
}

.field-label {
  display: block;
  font-size: 13px;
  font-weight: 700;
  color: #334155;
  margin-bottom: 7px;
}

.field-label .req {
  color: #dc2626;
  margin-left: 2px;
}

.field-input, .field-select {
  width: 100%;
  height: 46px;
  border: 1px solid #d5dde7;
  border-radius: 7px;
  padding: 0 14px;
  font-size: 14.5px;
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
  padding-right: 64px;
}

.btn-toggle-pass {
  position: absolute;
  right: 0;
  top: 0;
  height: 46px;
  background: none;
  border: none;
  padding: 0 14px;
  color: #0b5fa5;
  font-size: 11.5px;
  font-weight: 700;
  cursor: pointer;
  letter-spacing: 0.3px;
}

.form-options-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 6px 0 22px;
  font-size: 13px;
}

.remember-label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #64748b;
  cursor: pointer;
}

.remember-label input[type="checkbox"] {
  accent-color: #0b5fa5;
  width: 15px;
  height: 15px;
  margin: 0;
}

.forgot-link {
  color: #0b5fa5;
  text-decoration: none;
  font-weight: 600;
  font-size: 13px;
  transition: color 0.15s;
}

.forgot-link:hover {
  text-decoration: underline;
  color: #084980;
}

/* SUBMIT BUTTON */
.btn-login {
  width: 100%;
  height: 46px;
  background-color: #0b5fa5;
  color: #ffffff;
  border: none;
  border-radius: 7px;
  font-size: 14px;
  font-weight: 700;
  letter-spacing: 0.5px;
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
  margin-top: 22px;
  padding-top: 18px;
  border-top: 1px solid #f1f5f9;
  font-size: 13px;
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

/* 4. FOOTER */
.inst-footer {
  background-color: #071e42;
  color: #cbd5e1;
  font-size: 12px;
  padding: 18px 24px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.inst-footer-inner {
  max-width: 1240px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.footer-copy {
  color: #94a3b8;
}

.footer-nav {
  display: flex;
  align-items: center;
  gap: 16px;
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
  height: 11px;
  background-color: rgba(255, 255, 255, 0.25);
}

/* 5. RESPONSIVE DESIGN */
@media (max-width: 1024px) {
  .stage-container {
    grid-template-columns: 1fr 1fr;
    gap: 36px;
  }
  .info-title {
    font-size: 28px;
  }
}

@media (max-width: 860px) {
  .stage-container {
    grid-template-columns: 1fr;
    gap: 36px;
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
    padding: 12px 16px;
  }
  .inst-logo {
    width: 52px;
    height: 56px;
  }
  .inst-script-title {
    font-size: 20px;
  }
  .inst-stencil-title {
    font-size: 16px;
  }
  .inst-tagline {
    font-size: 9.5px;
  }
  .main-stage {
    padding: 24px 16px;
  }
  .login-card {
    padding: 28px 20px;
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
      <div class="inst-badge-icon">🏛</div>
      <div class="inst-badge-text">
        <div class="inst-badge-title">Executive Editorial Office</div>
        <div class="inst-badge-desc">Editor-in-Chief Management Portal</div>
      </div>
    </div>
  </div>
</header>

<!-- 3. MAIN WORKSPACE AREA -->
<main class="main-stage">
  <div class="stage-container">

    <!-- LEFT SIDE: Welcome / Information Panel -->
    <section class="info-panel" aria-labelledby="welcome-title">
      <span class="eyebrow-pill">Executive Editorial Office</span>
      <h1 class="info-title" id="welcome-title">Login to AJSMR Editorial Portal</h1>
      <p class="info-desc">
        Sign in to access the AJSMR Editorial Management Control Center. Manage manuscripts, coordinate peer review workflows, issue editorial decisions, and oversee journal issue publications from one secure platform.
      </p>

      <p class="info-note">
        For designated editorial board members and executive editors, use your official editorial credentials to access the manuscript queue:
      </p>

      <ul class="info-list">
        <li>Use the official Editor-in-Chief email address assigned to your account.</li>
        <li>Passwords are case-sensitive and verified against secure credentials.</li>
        <li>For security, administrative sessions expire automatically upon inactivity.</li>
        <li>Full audit trail logging is enabled for all editorial determinations.</li>
      </ul>

      <div class="info-action-box">
        <a class="info-search-btn" href="../search.php" target="_blank" rel="noopener">
          🔍 &nbsp;Search Public Journal Archive ↗
        </a>

        <div class="info-switch-text">
          Contributing author or peer reviewer? <a href="login.php">Public Author &amp; Reviewer Sign In &rarr;</a>
        </div>
      </div>
    </section>

    <!-- RIGHT SIDE: Login Card -->
    <div class="login-card-wrapper">
      <section class="login-card" aria-labelledby="card-title">
        <div class="card-heading">
          <h2 class="card-title" id="card-title">Editor-in-Chief Sign In</h2>
          <p class="card-subtitle">Access the AJSMR Editorial Management System</p>
        </div>

        <?php if ($error !== ''): ?>
          <div class="login-alert" role="alert">
            <span class="login-alert-icon">&#9888;</span>
            <div><?=htmlspecialchars($error, ENT_QUOTES, 'UTF-8')?></div>
          </div>
        <?php endif; ?>

        <form method="post" action="eic-login.php" autocomplete="on">
          <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8')?>">

          <!-- Email / User ID Field -->
          <div class="form-field">
            <label class="field-label" for="email">
              User ID / Email <span class="req">*</span>
            </label>
            <input class="field-input" id="email" name="email" type="email"
                   value="<?=htmlspecialchars((string)($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8')?>"
                   autocomplete="username" required
                   placeholder="Enter User ID or Email">
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
            <a class="forgot-link" href="mailto:editorajsmr@gmail.com?subject=AJSMR%20EIC%20Password%20Reset">Forgot Password?</a>
          </div>

          <!-- Submit Button -->
          <button class="btn-login" type="submit">LOG IN</button>
        </form>

        <div class="card-footer-links">
          Public portal account? <a href="login.php">Author / Reviewer Sign In</a>
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
