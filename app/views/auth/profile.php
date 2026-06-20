<?php /** @var array $data */
extract($data, EXTR_SKIP);
$u = $user;
?>
<div class="page-head">
  <div class="title-block">
    <h1>My Profile</h1>
    <p>Update your personal information, avatar, and password.</p>
  </div>
</div>

<div class="grid" style="grid-template-columns: 1fr 280px; gap: 24px; align-items: flex-start;">
  <div class="card card-pad">
    <form method="post" action="<?= url('auth/profile') ?>" enctype="multipart/form-data">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
      <div class="form-grid">
        <div class="form-group">
          <label>Full name</label>
          <input type="text" name="full_name" value="<?= e($u['full_name']) ?>" required>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" value="<?= e($u['email']) ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label>Phone</label>
        <input type="text" name="phone" value="<?= e($u['phone']) ?>">
      </div>
      <div class="form-group">
        <label>Bio</label>
        <textarea name="bio" rows="4" placeholder="Tell others about yourself..."><?= e($u['bio']) ?></textarea>
      </div>
      <div class="form-group">
        <label>Avatar</label>
        <input type="file" name="avatar" accept="image/*">
        <span class="hint">JPG, PNG, GIF, or WebP. Max 5 MB.</span>
      </div>
      <hr style="border:0;border-top:1px solid var(--border);margin:18px 0;">
      <div class="form-group">
        <label>New password <span class="text-muted" style="font-weight:400;font-size:.78rem;">(leave blank to keep current)</span></label>
        <input type="password" name="password">
      </div>
      <button type="submit" class="btn btn-primary btn-lg"><span class="material-icons">save</span> Save changes</button>
    </form>
  </div>

  <aside>
    <div class="card card-pad text-center">
      <span class="avatar lg" style="margin:0 auto 12px;background:<?= avatarColor($u['full_name']) ?>;width:96px;height:96px;font-size:2.2rem;">
        <?php if (!empty($u['avatar'])): ?><img src="<?= uploadUrl($u['avatar']) ?>" alt=""><?php else: ?><?= initials($u['full_name']) ?><?php endif; ?>
      </span>
      <h3><?= e($u['full_name']) ?></h3>
      <p class="text-muted" style="font-size:.85rem;"><?= e($u['username']) ?></p>
      <span class="badge role-badge <?= e($u['role']) ?>" style="margin-top:8px;"><?= ucfirst($u['role']) ?></span>
    </div>
    <div class="card card-pad">
      <h4 style="margin-bottom:10px;">Account info</h4>
      <div style="font-size:.85rem;line-height:1.8;">
        <div class="flex-between"><span class="text-muted">Member since</span><strong><?= formatDate($u['created_at']) ?></strong></div>
        <div class="flex-between"><span class="text-muted">Last login</span><strong><?= $u['last_login'] ? formatDate($u['last_login'], 'M j, Y g:i A') : 'Never' ?></strong></div>
        <div class="flex-between"><span class="text-muted">User ID</span><strong>#<?= $u['id'] ?></strong></div>
        <div class="flex-between"><span class="text-muted">Status</span>
          <?php if ($u['status'] === 'active'): ?>
            <span class="badge badge-success">Active</span>
          <?php else: ?>
            <span class="badge badge-warning"><?= ucfirst($u['status']) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </aside>
</div>
