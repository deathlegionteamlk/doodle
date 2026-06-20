<?php /** @var array $data */ extract($data, EXTR_SKIP); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Sign in') ?> · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
</head>
<body class="auth-body">
<div class="auth-wrap">
  <div class="auth-card">
    <div class="brand">
      <div class="brand-mark">d</div>
      <div class="brand-text">
        <h1><?= e(APP_NAME) ?></h1>
        <p>by <?= e(APP_TEAM) ?> · GPL-3.0</p>
      </div>
    </div>

    <h2><?= isset($requires2fa) && $requires2fa ? 'Two-factor authentication' : 'Welcome back' ?></h2>
    <p class="subtitle"><?= isset($requires2fa) && $requires2fa ? 'Enter the 6-digit code from your authenticator app.' : 'Sign in to continue your learning journey.' ?></p>

    <?= renderFlash($flash ?? []) ?>

    <?php if (isset($requires2fa) && $requires2fa): ?>
      <form method="post" action="<?= url('auth/login') ?>">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <div class="form-group">
          <label>Authentication code</label>
          <input type="text" name="totp_code" pattern="\d{6}" maxlength="6" required autofocus
                 style="font-size:1.6rem;letter-spacing:.4em;text-align:center;font-family:monospace;"
                 placeholder="000000" inputmode="numeric">
          <span class="hint">Or use one of your backup codes (format: XXXX-XXXX).</span>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg"><span class="material-icons" style="vertical-align:middle;">verified_user</span> Verify & sign in</button>
      </form>
      <p class="auth-alt"><a href="<?= url('auth/logout') ?>">Cancel and sign out</a></p>
    <?php else: ?>
      <form method="post" action="<?= url('auth/login') ?>">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <div class="form-group">
          <label>Username or email</label>
          <input type="text" name="identifier" required autofocus value="<?= e($_POST['identifier'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" required>
        </div>
        <div class="form-group checkbox-group">
          <input type="checkbox" name="remember" id="remember" value="1">
          <label for="remember">Keep me signed in</label>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Sign in</button>
      </form>

      <?php
      // Show OAuth buttons if configured
      $googleId = Database::fetch('SELECT setting_value FROM settings WHERE setting_key = :k', ['k' => 'oauth_google_client_id'])['setting_value'] ?? '';
      $githubId = Database::fetch('SELECT setting_value FROM settings WHERE setting_key = :k', ['k' => 'oauth_github_client_id'])['setting_value'] ?? '';
      if (!empty($googleId) || !empty($githubId)):
      ?>
      <div style="margin:20px 0;text-align:center;position:relative;">
        <hr style="border:0;border-top:1px solid var(--border);margin:0;">
        <span style="position:absolute;top:-9px;left:50%;transform:translateX(-50%);background:#fff;padding:0 12px;font-size:.78rem;color:var(--text-muted);">or continue with</span>
      </div>
      <div class="flex gap-sm" style="justify-content:center;">
        <?php if (!empty($googleId)): ?>
          <a href="<?= url('oauth/google') ?>" class="btn btn-secondary" style="display:inline-flex;align-items:center;gap:8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4v2.5h6.5c-.32 1.85-1.05 3.27-2.2 4.27-1.46 1.32-3.65 2.13-6.3 2.13-4.85 0-8.79-3.94-8.79-8.79S5.15-4.69 10 4.69 14.85 13.58 10 13.58c2.65 0 4.84-.81 6.3-2.13 1.15-1 1.88-2.42 2.2-4.27H12z"/></svg>
            Google
          </a>
        <?php endif; ?>
        <?php if (!empty($githubId)): ?>
          <a href="<?= url('oauth/github') ?>" class="btn btn-secondary" style="display:inline-flex;align-items:center;gap:8px;">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>
            GitHub
          </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <p class="auth-alt">Don't have an account? <a href="<?= url('auth/register') ?>">Create one</a></p>
    <?php endif; ?>
    <p class="auth-foot"><?= e(APP_NAME) ?> v<?= APP_VERSION ?> · <?= e(APP_TEAM) ?> · open source under GPL-3.0</p>
  </div>
</div>
</body>
</html>
