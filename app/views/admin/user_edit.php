<?php /** @var array $data */
extract($data, EXTR_SKIP);
$u = $user;
?>
<div class="breadcrumb">
  <a href="<?= url('admin/users') ?>">Users</a>
  <span class="material-icons">chevron_right</span>
  <span>Edit user</span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1>Edit user</h1>
    <p>Update profile, role, and status for <?= e($u['full_name']) ?>.</p>
  </div>
</div>

<div class="grid" style="grid-template-columns: 1fr 280px; gap: 24px; align-items: flex-start;">
  <div class="card card-pad">
    <form method="post" action="<?= url('admin/editUser/' . $u['id']) ?>">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
      <div class="form-grid">
        <div class="form-group">
          <label>Full name</label>
          <input type="text" name="full_name" value="<?= e($u['full_name']) ?>" required>
        </div>
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" value="<?= e($u['username']) ?>" required>
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" value="<?= e($u['email']) ?>" required>
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="text" name="phone" value="<?= e($u['phone']) ?>">
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Role</label>
          <select name="role">
            <option value="student" <?= $u['role'] === 'student' ? 'selected' : '' ?>>Student</option>
            <option value="teacher" <?= $u['role'] === 'teacher' ? 'selected' : '' ?>>Teacher</option>
            <option value="admin"   <?= $u['role'] === 'admin'   ? 'selected' : '' ?>>Admin</option>
          </select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status">
            <option value="active"    <?= $u['status'] === 'active'    ? 'selected' : '' ?>>Active</option>
            <option value="suspended" <?= $u['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
            <option value="pending"   <?= $u['status'] === 'pending'   ? 'selected' : '' ?>>Pending</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Bio</label>
        <textarea name="bio" rows="4"><?= e($u['bio']) ?></textarea>
      </div>
      <div class="form-group">
        <label>New password <span class="text-muted" style="font-weight:400;font-size:.78rem;">(leave blank to keep current)</span></label>
        <input type="password" name="password">
      </div>
      <button type="submit" class="btn btn-primary">Save changes</button>
      <a href="<?= url('admin/users') ?>" class="btn btn-ghost">Cancel</a>
    </form>
  </div>

  <aside>
    <div class="card card-pad text-center">
      <span class="avatar lg" style="margin:0 auto 12px;background:<?= avatarColor($u['full_name']) ?>;width:90px;height:90px;font-size:2rem;">
        <?php if (!empty($u['avatar'])): ?><img src="<?= uploadUrl($u['avatar']) ?>" alt=""><?php else: ?><?= initials($u['full_name']) ?><?php endif; ?>
      </span>
      <h3><?= e($u['full_name']) ?></h3>
      <p class="text-muted" style="font-size:.85rem;"><?= e($u['username']) ?></p>
      <p class="text-muted" style="font-size:.78rem;"><?= e($u['email']) ?></p>
      <span class="badge role-badge <?= e($u['role']) ?>" style="margin-top:10px;"><?= ucfirst($u['role']) ?></span>
    </div>
    <div class="card card-pad">
      <h4 style="margin-bottom:10px;">Account info</h4>
      <div style="font-size:.85rem;line-height:1.8;">
        <div class="flex-between"><span class="text-muted">Joined</span><strong><?= formatDate($u['created_at']) ?></strong></div>
        <div class="flex-between"><span class="text-muted">Last login</span><strong><?= $u['last_login'] ? formatDate($u['last_login'], 'M j, Y g:i A') : 'Never' ?></strong></div>
        <div class="flex-between"><span class="text-muted">User ID</span><strong>#<?= $u['id'] ?></strong></div>
      </div>
    </div>
  </aside>
</div>
