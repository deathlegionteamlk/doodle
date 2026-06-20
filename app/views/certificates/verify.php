<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Certificate · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
</head>
<body class="auth-body">
<div class="auth-wrap" style="max-width: 540px;">
  <div class="auth-card">
    <div class="brand" style="margin-bottom:24px;">
      <div class="brand-mark">d</div>
      <div class="brand-text">
        <h1><?= e(APP_NAME) ?></h1>
        <p>Certificate Verification</p>
      </div>
    </div>

    <h2>Verify a certificate</h2>
    <p class="subtitle">Enter the verification code from a certificate to confirm its authenticity.</p>

    <form method="get" action="<?= url('certificates/verify') ?>">
      <div class="form-group">
        <label>Verification code</label>
        <input type="text" name="code" value="<?= e($code) ?>" placeholder="e.g., A1B2C3D4E5F6G7H8" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg"><span class="material-icons">verified</span> Verify</button>
    </form>

    <?php if ($code && !$cert): ?>
      <div class="alert alert-error" style="margin-top:18px;">
        <span class="material-icons">error</span>
        <div>No certificate found with that code. Please check and try again.</div>
      </div>
    <?php elseif ($cert): ?>
      <div class="alert alert-success" style="margin-top:18px;">
        <span class="material-icons">verified</span>
        <div><strong>Certificate verified!</strong> This is a legitimate <?= e(APP_NAME) ?> certificate.</div>
      </div>
      <div class="card card-pad" style="margin-top:14px;">
        <div class="flex" style="gap:14px;align-items:center;margin-bottom:14px;">
          <div style="width:50px;height:50px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;">
            <span class="material-icons">workspace_premium</span>
          </div>
          <div>
            <strong style="display:block;"><?= e($cert['user_name']) ?></strong>
            <span class="text-muted" style="font-size:.82rem;"><?= e($cert['user_email']) ?></span>
          </div>
        </div>
        <h3 style="margin-bottom:10px;"><?= e($cert['course_title']) ?></h3>
        <div class="flex-between" style="font-size:.88rem;padding:8px 0;border-top:1px solid var(--border);">
          <span class="text-muted">Final score</span>
          <strong style="color:var(--primary);"><?= $cert['final_score'] ?>%</strong>
        </div>
        <div class="flex-between" style="font-size:.88rem;padding:8px 0;border-top:1px solid var(--border);">
          <span class="text-muted">Issued on</span>
          <strong><?= formatDate($cert['issued_at']) ?></strong>
        </div>
        <div class="flex-between" style="font-size:.88rem;padding:8px 0;border-top:1px solid var(--border);">
          <span class="text-muted">Verification code</span>
          <code style="background:var(--surface-2);padding:2px 6px;border-radius:4px;"><?= e($cert['verify_code']) ?></code>
        </div>
      </div>
    <?php endif; ?>
    <p class="auth-foot"><?= e(APP_NAME) ?> v<?= APP_VERSION ?> · <?= e(APP_TEAM) ?></p>
  </div>
</div>
</body>
</html>
