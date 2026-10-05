<?php
declare(strict_types=1);

/*
 * AJSMR Author Registration - corrected local version.
 * Do NOT call session_start() here because config/config.php already starts
 * the session. This removes the PHP 8.2 "session already active" warning.
 */
require_once __DIR__ . '/config/config.php';

if (user()) { redirect('dashboard.php'); }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        check_csrf();

        // PUBLIC REGISTRATION SECURITY ENFORCEMENT:
        // Public registration MUST create Author accounts only.
        // Ignore any attempted role injection via POST/GET/headers/devtools.
        $role = 'author';

        $name = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $affiliation = trim((string)($_POST['affiliation'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $orcid = trim((string)($_POST['orcid'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if (mb_strlen($name) < 2 || mb_strlen($name) > 180) {
            $error = 'Please enter your full name.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            $error = 'Please enter a valid email address.';
        } elseif ($affiliation === '' || mb_strlen($affiliation) > 255) {
            $error = 'Please enter your affiliation/institution.';
        } elseif ($phone !== '' && mb_strlen($phone) > 50) {
            $error = 'Phone number is too long.';
        } elseif ($orcid !== '' && !preg_match('/^(?:https?:\/\/orcid\.org\/)?\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/i', $orcid)) {
            $error = 'Please enter a valid ORCID iD, for example 0000-0000-0000-0000.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must contain at least 8 characters.';
        } elseif (strlen($password) > 255) {
            $error = 'Password is too long.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (!isset($_POST['agree'])) {
            $error = 'Please accept the journal terms and privacy policy.';
        } else {
            $pdo = db();

            // Confirm the registration is pointed at the intended editorial DB.
            $dbName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();

            $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll(PDO::FETCH_COLUMN);
            if (!$tables) {
                throw new RuntimeException(
                    "The connected editorial database (" . $dbName .
                    ") does not contain the required users table. Run editorial-db-diagnostic.php before registering."
                );
            }

            // Read the actual schema so registration does not silently assume
            // columns that are not present in the installed database.
            $columns = [];
            foreach ($pdo->query('SHOW COLUMNS FROM users')->fetchAll() as $col) {
                $columns[strtolower((string)$col['Field'])] = (string)$col['Field'];
            }

            $required = ['id','full_name','email','password_hash','role','active'];
            $missing = [];
            foreach ($required as $c) {
                if (!isset($columns[$c])) $missing[] = $c;
            }
            if ($missing) {
                throw new RuntimeException(
                    'The users table is missing required column(s): ' . implode(', ', $missing) .
                    '. Run editorial-db-diagnostic.php and compare the schema.'
                );
            }

            $check = $pdo->prepare('SELECT id FROM users WHERE LOWER(email)=LOWER(?) LIMIT 1');
            $check->execute([$email]);

            if ($check->fetch()) {
                $error = 'An account with this email address already exists. Please sign in.';
            } else {
                /*
                 * Build the INSERT from columns that actually exist.
                 * Required V1 columns are enforced above; optional profile
                 * columns are included only when present.
                 */
                $data = [
                    'full_name'     => $name,
                    'email'         => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role'          => 'author',
                    'active'        => 1
                ];

                $optional = [
                    'affiliation' => ($affiliation !== '' ? $affiliation : null),
                    'phone'       => ($phone !== '' ? $phone : null),
                    'orcid'       => ($orcid !== '' ? $orcid : null)
                ];

                foreach ($optional as $field => $value) {
                    if (isset($columns[$field])) $data[$columns[$field]] = $value;
                }

                $insertColumns = array_keys($data);
                $sql = 'INSERT INTO users (' . implode(',', array_map(
                    static fn($x) => '`' . str_replace('`','``',$x) . '`',
                    $insertColumns
                )) . ') VALUES (' . implode(',', array_fill(0, count($insertColumns), '?')) . ')';

                $stmt = $pdo->prepare($sql);
                $stmt->execute(array_values($data));

                $success = 'Registration successful. Your Author account has been created. You can now sign in.';
                $_POST = [];
            }
        }
    } catch (PDOException $e) {
        error_log('AJSMR registration PDO error: ' . $e->getMessage());
        if ((int)($e->errorInfo[1] ?? 0) === 1062) {
            $error = 'An account with this email address already exists. Please sign in.';
        } else {
            $error = 'Registration could not be completed. Please run editorial-db-diagnostic.php to identify the database/schema problem.';
        }
    } catch (Throwable $e) {
        error_log('AJSMR registration error: ' . $e->getMessage());
        $error = $e->getMessage();
    }
}

$csrfToken = csrf();
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>AJSMR | Create Author Account</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f5f7fb;color:#263238;font-family:Arial,Helvetica,sans-serif}.page{min-height:100vh;display:flex;justify-content:center;padding:35px 20px}.card{width:100%;max-width:900px;background:#fff;border:1px solid #e9edf3;border-radius:12px;box-shadow:0 12px 40px #10182817;overflow:hidden}.top{background:linear-gradient(145deg,#092b5f,#0b5fa5);color:#fff;padding:32px 40px}.brand{display:flex;align-items:center;gap:16px}.logo{width:55px;height:55px;border-radius:12px;background:#fff;color:#0a4079;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:20px}h1{margin:0;font-size:27px}.subtitle{margin-top:6px;font-size:14px;color:#e5eef7}.content{padding:34px 40px 38px}.intro{color:#667085;font-size:14px;line-height:1.55;margin:0 0 24px}.alert{border-radius:8px;padding:12px 14px;margin-bottom:20px;font-size:14px}.error{background:#fff1f0;border:1px solid #ffd1cc;color:#a52a20}.success{background:#effaf3;border:1px solid #c9efd7;color:#176b39}.grid{display:grid;grid-template-columns:1fr 1fr;gap:18px 22px}.full{grid-column:1/-1}.field{margin-bottom:2px}label{display:block;font-size:13px;font-weight:bold;color:#344054;margin-bottom:7px}input,select{width:100%;height:45px;border:1px solid #d8dee8;border-radius:7px;padding:0 12px;font-size:14px;outline:none;background:#fff;color:#263238}input:focus,select:focus{border-color:#3778b8;box-shadow:0 0 0 3px #3778b81f}.role-note{font-size:12px;line-height:1.45;color:#7b8794;margin-top:6px}.help{display:block;font-size:11px;color:#98a2b3;margin-top:5px}.pw{position:relative}.pw input{padding-right:60px}.show{position:absolute;right:0;top:0;height:45px;border:0;background:none;padding:0 12px;color:#376b99;font-weight:bold;font-size:11px;cursor:pointer}.agree{display:flex;gap:9px;align-items:flex-start;margin:24px 0;color:#667085;font-size:13px;line-height:1.5}.agree input{width:auto;height:auto;margin-top:3px}.actions{display:flex;justify-content:space-between;align-items:center;border-top:1px solid #edf0f4;padding-top:23px}.back{color:#0b5fa5;text-decoration:none;font-size:13px;font-weight:bold}.btn{height:47px;border:0;border-radius:7px;background:#0b5fa5;color:#fff;font-size:14px;font-weight:800;padding:0 26px;cursor:pointer}.note{text-align:center;margin-top:24px;color:#98a2b3;font-size:11px}@media(max-width:700px){.grid{grid-template-columns:1fr}.full{grid-column:auto}.top{padding:26px}.content{padding:28px 24px}.actions{flex-direction:column-reverse;gap:18px;align-items:stretch}}
</style></head><body><div class="page"><div class="card"><header class="top"><div class="brand"><div class="logo">AJ</div><div><h1>Create Author Account</h1><div class="subtitle">The American Journal of Science and Medical Research (AJSMR)</div></div></div></header>
<main class="content"><p class="intro">Register as an author to submit and manage your manuscripts in the AJSMR editorial management system.</p>
<?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
<?php if($success): ?><div class="alert success"><?=e($success)?> <a href="login.php" style="color:#176b39;font-weight:bold">Proceed to Sign In</a></div><?php endif; ?>
<?php if(!$success): ?><form method="post" action="register.php" autocomplete="on"><input type="hidden" name="csrf" value="<?=e($csrfToken)?>">
<div class="grid">
<div class="field"><label for="name">Full name *</label><input id="name" name="name" type="text" value="<?=e($_POST['name']??'')?>" maxlength="180" required></div>
<div class="field"><label for="email">Email address *</label><input id="email" name="email" type="email" value="<?=e($_POST['email']??'')?>" maxlength="190" required></div>
<div class="field full"><label for="affiliation">Affiliation / Institution *</label><input id="affiliation" name="affiliation" type="text" value="<?=e($_POST['affiliation']??'')?>" maxlength="255" required></div>
<div class="field"><label for="phone">Phone</label><input id="phone" name="phone" type="tel" value="<?=e($_POST['phone']??'')?>" maxlength="50"></div>
<div class="field"><label for="orcid">ORCID iD</label><input id="orcid" name="orcid" type="text" value="<?=e($_POST['orcid']??'')?>" placeholder="0000-0000-0000-0000" maxlength="100"><span class="help">Optional.</span></div>
<div class="field"><label for="password">Password *</label><div class="pw"><input id="password" name="password" type="password" minlength="8" maxlength="255" required><button class="show" type="button" onclick="toggle('password',this)">SHOW</button></div><span class="help">Minimum 8 characters.</span></div>
<div class="field"><label for="confirm_password">Confirm password *</label><div class="pw"><input id="confirm_password" name="confirm_password" type="password" minlength="8" maxlength="255" required><button class="show" type="button" onclick="toggle('confirm_password',this)">SHOW</button></div></div>
</div>
<label class="agree"><input type="checkbox" name="agree" value="1" required><span>I agree to the AJSMR journal terms, privacy policy, publication ethics requirements, and use of my registration information for editorial and manuscript-management purposes.</span></label>
<div class="actions"><a class="back" href="login.php">← Back to Sign In</a><button class="btn" type="submit">CREATE ACCOUNT</button></div></form><?php endif; ?>
<div class="note">© <?=date('Y')?> The American Journal of Science and Medical Research. All rights reserved.</div></main></div></div>
<script>function toggle(id,b){const x=document.getElementById(id);if(x.type==='password'){x.type='text';b.textContent='HIDE'}else{x.type='password';b.textContent='SHOW'}}</script>
</body></html>
