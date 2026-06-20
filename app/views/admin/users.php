<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>Users</h1>
    <p><?= number_format($total) ?> users registered.</p>
  </div>
  <div class="actions">
    <button class="btn btn-primary" onclick="document.getElementById('addUserModal').style.display='flex'">
      <span class="material-icons">person_add</span> Add user
    </button>
  </div>
</div>

<div class="card card-pad mb-3">
  <form method="get" action="<?= url('admin/users') ?>">
    <input type="hidden" name="r" value="admin/users">
    <div class="form-grid">
      <div class="form-group">
        <label>Search</label>
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Name, username, or email...">
      </div>
      <div class="form-group">
        <label>Role</label>
        <select name="role">
          <option value="">All roles</option>
          <option value="admin"   <?= $role === 'admin' ? 'selected' : '' ?>>Admins</option>
          <option value="teacher" <?= $role === 'teacher' ? 'selected' : '' ?>>Teachers</option>
          <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Students</option>
        </select>
      </div>
    </div>
    <button class="btn btn-primary" type="submit">Filter</button>
    <a href="<?= url('admin/users') ?>" class="btn btn-ghost">Reset</a>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th>User</th>
          <th>Role</th>
          <th>Status</th>
          <th>Activity</th>
          <th>Joined</th>
          <th>Last login</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="7" class="text-center text-muted" style="padding:30px;">No users found.</td></tr>
        <?php else: foreach ($users as $u): ?>
          <tr>
            <td>
              <div class="flex gap-sm">
                <span class="avatar sm" style="background:<?= avatarColor($u['full_name']) ?>;">
                  <?php if (!empty($u['avatar'])): ?><img src="<?= uploadUrl($u['avatar']) ?>" alt=""><?php else: ?><?= initials($u['full_name']) ?><?php endif; ?>
                </span>
                <div>
                  <strong><?= e($u['full_name']) ?></strong>
                  <div class="text-muted" style="font-size:.78rem;"><?= e($u['username']) ?> · <?= e($u['email']) ?></div>
                </div>
              </div>
            </td>
            <td><span class="badge role-badge <?= e($u['role']) ?>"><?= ucfirst($u['role']) ?></span></td>
            <td>
              <?php if ($u['status'] === 'active'): ?>
                <span class="badge badge-success">Active</span>
              <?php elseif ($u['status'] === 'suspended'): ?>
                <span class="badge badge-danger">Suspended</span>
              <?php else: ?>
                <span class="badge badge-warning">Pending</span>
              <?php endif; ?>
            </td>
            <td style="font-size:.82rem;">
              <?php if ($u['role'] === 'teacher'): ?>
                <?= $u['course_count'] ?> courses
              <?php elseif ($u['role'] === 'student'): ?>
                <?= $u['enroll_count'] ?> enrollments
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
            <td class="text-muted" style="font-size:.82rem;"><?= timeAgo($u['created_at']) ?></td>
            <td class="text-muted" style="font-size:.82rem;"><?= $u['last_login'] ? timeAgo($u['last_login']) : '—' ?></td>
            <td class="text-right">
              <div class="actions">
                <a href="<?= url('admin/editUser/' . $u['id']) ?>" class="btn btn-secondary btn-sm"><span class="material-icons">edit</span></a>
                <?php if ($u['id'] != Auth::id()): ?>
                  <a href="<?= url('admin/deleteUser/' . $u['id']) ?>" class="btn btn-danger btn-sm" data-confirm="Permanently delete user <?= e($u['username']) ?>? This will also delete their courses and submissions.">
                    <span class="material-icons">delete</span>
                  </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $pagination ?>

<!-- Add user modal -->
<div id="addUserModal" class="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px;">
  <div class="card" style="max-width:520px;width:100%;">
    <div class="card-head">
      <h3>Add new user</h3>
      <button class="btn btn-ghost btn-sm" onclick="document.getElementById('addUserModal').style.display='none'"><span class="material-icons">close</span></button>
    </div>
    <div class="card-body">
      <form method="post" action="<?= url('admin/createUser') ?>">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <div class="form-group">
          <label>Full name</label>
          <input type="text" name="full_name" required>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
          </div>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Role</label>
            <select name="role">
              <option value="student">Student</option>
              <option value="teacher">Teacher</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Create user</button>
      </form>
    </div>
  </div>
</div>
