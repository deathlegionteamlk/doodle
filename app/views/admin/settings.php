<?php /** @var array $data */
extract($data, EXTR_SKIP);
$s = $settings;
?>
<div class="page-head">
  <div class="title-block">
    <h1>System Settings</h1>
    <p>Configure the platform's global behavior and branding.</p>
  </div>
</div>

<div class="card card-pad">
  <form method="post" action="<?= url('admin/settings') ?>">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
    <h3 style="margin-bottom: 14px;">Site branding</h3>
    <div class="form-grid">
      <div class="form-group">
        <label>Site name</label>
        <input type="text" name="site_name" value="<?= e($s['site_name'] ?? 'doodle') ?>">
      </div>
      <div class="form-group">
        <label>Site tagline</label>
        <input type="text" name="site_tagline" value="<?= e($s['site_tagline'] ?? '') ?>">
      </div>
    </div>
    <div class="form-group">
      <label>Site description (for SEO / meta)</label>
      <textarea name="site_description" rows="2"><?= e($s['site_description'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
      <label>Footer text</label>
      <input type="text" name="footer_text" value="<?= e($s['footer_text'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>Theme primary color (hex)</label>
      <input type="text" name="theme_primary" value="<?= e($s['theme_primary'] ?? '#4F46E5') ?>">
    </div>

    <hr style="border:0;border-top:1px solid var(--border);margin:24px 0;">

    <h3 style="margin-bottom: 14px;">Registration & accounts</h3>
    <div class="form-grid">
      <div class="form-group">
        <label>Allow public registration?</label>
        <select name="allow_registration">
          <option value="1" <?= ($s['allow_registration'] ?? '1') === '1' ? 'selected' : '' ?>>Yes — anyone can register</option>
          <option value="0" <?= ($s['allow_registration'] ?? '1') === '0' ? 'selected' : '' ?>>No — admins must create accounts</option>
        </select>
      </div>
      <div class="form-group">
        <label>Default role for new users</label>
        <select name="default_role">
          <option value="student" <?= ($s['default_role'] ?? 'student') === 'student' ? 'selected' : '' ?>>Student</option>
          <option value="teacher" <?= ($s['default_role'] ?? 'student') === 'teacher' ? 'selected' : '' ?>>Teacher</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label>Contact email (for support inquiries)</label>
      <input type="email" name="contact_email" value="<?= e($s['contact_email'] ?? '') ?>">
    </div>

    <hr style="border:0;border-top:1px solid var(--border);margin:24px 0;">

    <h3 style="margin-bottom: 14px;">System information</h3>
    <div style="font-size:.85rem;line-height:1.9;color:var(--text-muted);">
      <div class="flex-between"><span>doodle version</span><strong><?= APP_VERSION ?></strong></div>
      <div class="flex-between"><span>PHP version</span><strong><?= PHP_VERSION ?></strong></div>
      <div class="flex-between"><span>Database</span><strong><?= e(strtoupper(DB_TYPE)) ?></strong></div>
      <div class="flex-between"><span>License</span><strong>GPL-3.0</strong></div>
      <div class="flex-between"><span>Built by</span><strong><?= e(APP_TEAM) ?></strong></div>
    </div>

    <hr style="border:0;border-top:1px solid var(--border);margin:24px 0;">

    <button type="submit" class="btn btn-primary btn-lg"><span class="material-icons">save</span> Save settings</button>
  </form>
</div>
