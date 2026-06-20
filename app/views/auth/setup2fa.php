<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>Two-Factor Authentication</h1>
    <p>Add an extra layer of security to your account with TOTP (Google Authenticator, Authy, 1Password, etc.).</p>
  </div>
</div>

<?php if ($enabled): ?>
  <div class="alert alert-success">
    <span class="material-icons">verified_user</span>
    <div><strong>2FA is enabled.</strong> You'll need a 6-digit code from your authenticator app every time you sign in.</div>
  </div>

  <?php if (!empty($backupCodes)): ?>
    <div class="card card-pad mb-3">
      <h3 style="margin-bottom:10px;color:var(--warning);"><span class="material-icons" style="vertical-align:middle;">warning</span> Your backup codes</h3>
      <p class="text-muted" style="font-size:.85rem;margin-bottom:14px;">Save these in a safe place. Each can be used <strong>once</strong> if you lose your authenticator device.</p>
      <div class="grid grid-4" style="gap:8px;">
        <?php foreach ($backupCodes as $code): ?>
          <code style="background:var(--surface-2);padding:10px;text-align:center;border-radius:6px;font-size:1rem;letter-spacing:.05em;<?= in_array($code, []) ? 'opacity:.4;text-decoration:line-through;' : '' ?>"><?= e($code) ?></code>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="card card-pad">
    <h3 style="margin-bottom:14px;">Disable 2FA</h3>
    <p class="text-muted" style="font-size:.88rem;margin-bottom:14px;">If you turn off 2FA, your account will only be protected by your password.</p>
    <form method="post" action="<?= url('auth/setup2fa') ?>">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
      <input type="hidden" name="action" value="disable">
      <button type="submit" class="btn btn-danger" data-confirm="Disable two-factor authentication?"><span class="material-icons">lock_open</span> Disable 2FA</button>
    </form>
  </div>

<?php else: ?>
  <div class="grid" style="grid-template-columns: 1fr 1fr; gap:24px; align-items:flex-start;">
    <div class="card card-pad">
      <h3 style="margin-bottom:14px;">1. Scan this QR code</h3>
      <p class="text-muted" style="font-size:.85rem;margin-bottom:14px;">Open your authenticator app (Google Authenticator, Authy, etc.) and scan this code:</p>
      <div style="text-align:center;">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=<?= urlencode($qrUri) ?>" alt="2FA QR" style="border:1px solid var(--border);border-radius:8px;background:#fff;padding:10px;">
        <p class="text-muted" style="font-size:.78rem;margin-top:8px;">Or enter this code manually:</p>
        <code style="background:var(--surface-2);padding:8px 12px;border-radius:6px;font-size:.85rem;word-break:break-all;display:inline-block;max-width:100%;"><?= e($pendingSecret) ?></code>
      </div>
    </div>

    <div class="card card-pad">
      <h3 style="margin-bottom:14px;">2. Verify the code</h3>
      <p class="text-muted" style="font-size:.85rem;margin-bottom:14px;">Enter the 6-digit code from your app to confirm and enable 2FA.</p>
      <form method="post" action="<?= url('auth/setup2fa') ?>">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <input type="hidden" name="action" value="enable">
        <input type="hidden" name="secret" value="<?= e($pendingSecret) ?>">
        <div class="form-group">
          <label>6-digit verification code</label>
          <input type="text" name="code" pattern="\d{6}" maxlength="6" required style="font-size:1.4rem;letter-spacing:.4em;text-align:center;font-family:monospace;" placeholder="000000">
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg"><span class="material-icons">verified_user</span> Enable 2FA</button>
      </form>
    </div>
  </div>
<?php endif; ?>
