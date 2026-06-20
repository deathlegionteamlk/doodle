<?php
/**
 * doodle - Installer
 *
 * Sets up the database, creates the admin account, and writes the .installed flag.
 *
 * @package doodle
 * @author  Death Legion Team
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers/functions.php';

Session::start();
$errors = [];
$step   = $_POST['step'] ?? 'welcome';
$done   = false;

// Already installed?
if (IS_INSTALLED) {
    header('Location: ' . BASE_URL);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['_csrf']) || !hash_equals(Session::get('_install_csrf', ''), $_POST['_csrf'])) {
        $errors[] = 'Invalid CSRF token. Please refresh the page.';
        $step = 'welcome';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors) && $step === 'install') {
    // Validate admin input
    $adminUser  = trim($_POST['admin_user'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPass  = $_POST['admin_pass'] ?? '';
    $adminPass2 = $_POST['admin_pass2'] ?? '';
    $adminName  = trim($_POST['admin_name'] ?? '');

    if (strlen($adminUser) < 3) $errors[] = 'Admin username must be at least 3 characters.';
    if (!preg_match('/^[A-Za-z0-9_\.]+$/', $adminUser)) $errors[] = 'Admin username may only contain letters, numbers, dots and underscores.';
    if (!isValidEmail($adminEmail)) $errors[] = 'Admin email is invalid.';
    if (strlen($adminPass) < PASSWORD_MIN_LENGTH) $errors[] = 'Admin password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
    if ($adminPass !== $adminPass2) $errors[] = 'Admin passwords do not match.';
    if (empty($adminName)) $errors[] = 'Admin full name is required.';

    if (empty($errors)) {
        try {
            // Connect to DB
            if (DB_TYPE === 'sqlite') {
                if (!file_exists(dirname(DB_PATH))) mkdir(dirname(DB_PATH), 0755, true);
                $dsn = 'sqlite:' . DB_PATH;
                $db = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $db->exec('PRAGMA foreign_keys = ON');
            } else {
                $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                $db = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            }

            // Create schema
            require_once DATABASE_DIR . '/schema.php';
            require_once DATABASE_DIR . '/schema_v2.php';
            require_once DATABASE_DIR . '/schema_v3.php';
            Schema::install($db, DB_TYPE);
            SchemaV2::install($db);
            SchemaV3::install($db);
            Schema::seed($db);

            // Create admin
            $hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
            $now  = date('Y-m-d H:i:s');
            $stmt = $db->prepare('INSERT INTO users (username, email, password, full_name, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$adminUser, $adminEmail, $hash, $adminName, 'admin', 'active', $now]);

            // Persist site settings
            $settings = [
                'site_name'     => trim($_POST['site_name'] ?? 'doodle'),
                'site_tagline'  => trim($_POST['site_tagline'] ?? 'Personalized learning, by Death Legion Team'),
                'contact_email' => $adminEmail,
                'installed_at'  => $now,
                'installed_by'  => $adminUser,
            ];
            // Portable: insert or update each setting (SQLite + MySQL compatible)
            $checkStmt = $db->prepare('SELECT setting_key FROM settings WHERE setting_key = ?');
            $insStmt   = $db->prepare('INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, ?)');
            $updStmt   = $db->prepare('UPDATE settings SET setting_value = ?, updated_at = ? WHERE setting_key = ?');
            foreach ($settings as $k => $v) {
                $checkStmt->execute([$k]);
                if ($checkStmt->fetch()) {
                    $updStmt->execute([$v, $now, $k]);
                } else {
                    $insStmt->execute([$k, $v, $now]);
                }
            }

            // Write installed flag
            file_put_contents(INSTALLED_FILE, json_encode([
                'version' => APP_VERSION,
                'installed_at' => $now,
                'admin' => $adminUser,
            ], JSON_PRETTY_PRINT));

            $done = true;
        } catch (Throwable $e) {
            $errors[] = 'Installation failed: ' . $e->getMessage();
        }
    }
}

// CSRF token for installer
if (!Session::has('_install_csrf')) {
    Session::set('_install_csrf', bin2hex(random_bytes(32)));
}
$csrf = Session::get('_install_csrf');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Install · doodle</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
</head>
<body class="auth-body">
<div class="auth-wrap">
  <div class="auth-card installer">
    <div class="brand">
      <div class="brand-mark">d</div>
      <div class="brand-text">
        <h1>doodle</h1>
        <p>by Death Legion Team · GPL-3.0</p>
      </div>
    </div>

    <?php if ($done): ?>
      <div class="install-success">
        <span class="material-icons">check_circle</span>
        <h2>Installation Complete</h2>
        <p>Your doodle LMS is ready. Log in with your administrator account to begin creating courses.</p>
        <a class="btn btn-primary btn-block" href="<?= BASE_URL ?>">Go to Login</a>
      </div>
    <?php else: ?>

      <?php if ($errors): ?>
        <div class="alert alert-error">
          <ul style="margin:0;padding-left:1.2em;">
            <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($step === 'welcome'): ?>
        <div class="install-welcome">
          <h2>Welcome to the installer</h2>
          <p>This wizard will set up the database and your administrator account. The whole process takes less than a minute.</p>
          <ul class="checks">
            <li class="ok"><span class="material-icons">check</span> Database type: <strong><?= e(strtoupper(DB_TYPE)) ?></strong></li>
            <li class="ok"><span class="material-icons">check</span> PHP version: <strong><?= e(PHP_VERSION) ?></strong></li>
            <?php if (DB_TYPE === 'sqlite'): ?>
              <li class="<?= is_writable(dirname(DB_PATH)) ? 'ok' : 'bad' ?>"><span class="material-icons"><?= is_writable(dirname(DB_PATH)) ? 'check' : 'close' ?></span> Database directory writable</li>
            <?php endif; ?>
            <li class="<?= is_writable(ROOT_DIR) ? 'ok' : 'bad' ?>"><span class="material-icons"><?= is_writable(ROOT_DIR) ? 'check' : 'close' ?></span> Root directory writable (for .installed flag)</li>
            <li class="<?= is_writable(UPLOAD_DIR) ? 'ok' : 'bad' ?>"><span class="material-icons"><?= is_writable(UPLOAD_DIR) ? 'check' : 'close' ?></span> Upload directory writable</li>
          </ul>
          <form method="post" action="">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="step" value="install">
            <h3 style="margin-top:24px;">Site information</h3>
            <div class="form-grid">
              <div class="form-group">
                <label>Site name</label>
                <input type="text" name="site_name" value="doodle" required>
              </div>
              <div class="form-group">
                <label>Site tagline</label>
                <input type="text" name="site_tagline" value="Personalized learning, by Death Legion Team">
              </div>
            </div>
            <h3 style="margin-top:24px;">Administrator account</h3>
            <div class="form-grid">
              <div class="form-group">
                <label>Full name</label>
                <input type="text" name="admin_name" required>
              </div>
              <div class="form-group">
                <label>Username</label>
                <input type="text" name="admin_user" required>
              </div>
              <div class="form-group">
                <label>Email</label>
                <input type="email" name="admin_email" required>
              </div>
              <div class="form-group"></div>
              <div class="form-group">
                <label>Password</label>
                <input type="password" name="admin_pass" required>
              </div>
              <div class="form-group">
                <label>Confirm password</label>
                <input type="password" name="admin_pass2" required>
              </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Install doodle</button>
          </form>
        </div>
      <?php endif; ?>

    <?php endif; ?>
    <p class="auth-foot">doodle v<?= APP_VERSION ?> · GPL-3.0 · Death Legion Team</p>
  </div>
</div>
</body>
</html>
