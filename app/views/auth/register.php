<?php /** @var array $data */ extract($data, EXTR_SKIP); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Create account') ?> · <?= e(APP_NAME) ?></title>
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

    <h2>Create your account</h2>
    <p class="subtitle">Join the doodle learning community in seconds.</p>

    <?= renderFlash($flash ?? []) ?>

    <form method="post" action="<?= url('auth/register') ?>">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">

      <div class="role-pick">
        <label>
          <input type="radio" name="role" value="student" <?= ($_POST['role'] ?? 'student') === 'student' ? 'checked' : '' ?>>
          <div class="role-card">
            <span class="material-icons">school</span>
            <div class="role-name">Student</div>
            <div class="role-desc">Enroll & learn</div>
          </div>
        </label>
        <label>
          <input type="radio" name="role" value="teacher" <?= ($_POST['role'] ?? '') === 'teacher' ? 'checked' : '' ?>>
          <div class="role-card">
            <span class="material-icons">co_present</span>
            <div class="role-name">Teacher</div>
            <div class="role-desc">Create & teach</div>
          </div>
        </label>
      </div>

      <div class="form-group">
        <label>Full name</label>
        <input type="text" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>">
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" required value="<?= e($_POST['username'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" required>
          <span class="hint">At least <?= PASSWORD_MIN_LENGTH ?> characters.</span>
        </div>
        <div class="form-group">
          <label>Confirm password</label>
          <input type="password" name="password2" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg">Create account</button>
    </form>

    <p class="auth-alt">Already have an account? <a href="<?= url('auth/login') ?>">Sign in</a></p>
    <p class="auth-foot"><?= e(APP_NAME) ?> v<?= APP_VERSION ?> · <?= e(APP_TEAM) ?> · open source under GPL-3.0</p>
  </div>
</div>
</body>
</html>
