<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>Admin Dashboard</h1>
    <p>System overview for <?= e(APP_NAME) ?> v<?= APP_VERSION ?>.</p>
  </div>
  <div class="actions">
    <a href="<?= url('admin/announcements') ?>" class="btn btn-secondary"><span class="material-icons">campaign</span> Announce</a>
    <a href="<?= url('admin/users') ?>" class="btn btn-primary"><span class="material-icons">person_add</span> Add user</a>
  </div>
</div>

<div class="grid grid-4 mb-3">
  <div class="stat">
    <div>
      <div class="stat-label">Total Users</div>
      <div class="stat-value"><?= number_format($stats['users']) ?></div>
      <div class="stat-delta"><?= $stats['students'] ?> students · <?= $stats['teachers'] ?> teachers · <?= $stats['admins'] ?> admins</div>
    </div>
    <div class="stat-icon"><span class="material-icons">group</span></div>
  </div>
  <div class="stat success">
    <div>
      <div class="stat-label">Courses</div>
      <div class="stat-value"><?= number_format($stats['courses']) ?></div>
      <div class="stat-delta"><?= $stats['published'] ?> published · <?= $stats['drafts'] ?> drafts</div>
    </div>
    <div class="stat-icon"><span class="material-icons">school</span></div>
  </div>
  <div class="stat info">
    <div>
      <div class="stat-label">Enrollments</div>
      <div class="stat-value"><?= number_format($stats['enrollments']) ?></div>
      <div class="stat-delta">across all courses</div>
    </div>
    <div class="stat-icon"><span class="material-icons">how_to_reg</span></div>
  </div>
  <div class="stat warning">
    <div>
      <div class="stat-label">Pending Submissions</div>
      <div class="stat-value"><?= number_format($stats['pending']) ?></div>
      <div class="stat-delta">awaiting grading</div>
    </div>
    <div class="stat-icon"><span class="material-icons">pending_actions</span></div>
  </div>
</div>

<div class="grid grid-2 mb-3">
  <div class="card">
    <div class="card-head">
      <h3>Recent Users</h3>
      <a href="<?= url('admin/users') ?>" class="btn btn-ghost btn-sm">View all</a>
    </div>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr><th>User</th><th>Role</th><th>Joined</th></tr>
        </thead>
        <tbody>
          <?php if (empty($recentUsers)): ?>
            <tr><td colspan="3" class="text-muted text-center" style="padding:24px;">No users yet.</td></tr>
          <?php else: foreach ($recentUsers as $u): ?>
            <tr>
              <td>
                <div class="flex gap-sm">
                  <span class="avatar sm" style="background:<?= avatarColor($u['full_name']) ?>;">
                    <?php if (!empty($u['avatar'])): ?><img src="<?= uploadUrl($u['avatar']) ?>" alt=""><?php else: ?><?= initials($u['full_name']) ?><?php endif; ?>
                  </span>
                  <div>
                    <strong><?= e($u['full_name']) ?></strong>
                    <div class="text-muted" style="font-size:.78rem;"><?= e($u['email']) ?></div>
                  </div>
                </div>
              </td>
              <td><span class="badge role-badge <?= e($u['role']) ?>"><?= ucfirst($u['role']) ?></span></td>
              <td class="text-muted" style="font-size:.82rem;"><?= timeAgo($u['created_at']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <h3>Recent Courses</h3>
      <a href="<?= url('admin/courses') ?>" class="btn btn-ghost btn-sm">View all</a>
    </div>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr><th>Course</th><th>Teacher</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php if (empty($recentCourses)): ?>
            <tr><td colspan="3" class="text-muted text-center" style="padding:24px;">No courses yet.</td></tr>
          <?php else: foreach ($recentCourses as $c): ?>
            <tr>
              <td><a href="<?= url('course/view/' . $c['id']) ?>"><strong><?= e($c['title']) ?></strong></a></td>
              <td class="text-muted" style="font-size:.85rem;"><?= e($c['teacher_name']) ?></td>
              <td><span class="badge badge-<?= $c['status'] === 'published' ? 'success' : 'warning' ?>"><?= ucfirst($c['status']) ?></span></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="grid grid-3 mb-3">
  <div class="stat accent">
    <div>
      <div class="stat-label">Forum Topics</div>
      <div class="stat-value"><?= number_format($stats['forum']) ?></div>
      <div class="stat-delta"><?= $stats['posts'] ?> posts total</div>
    </div>
    <div class="stat-icon"><span class="material-icons">forum</span></div>
  </div>
  <div class="stat info">
    <div>
      <div class="stat-label">Total Submissions</div>
      <div class="stat-value"><?= number_format($stats['submissions']) ?></div>
      <div class="stat-delta">all-time</div>
    </div>
    <div class="stat-icon"><span class="material-icons">assignment</span></div>
  </div>
  <div class="stat success">
    <div>
      <div class="stat-label">System Health</div>
      <div class="stat-value" style="color:var(--success);">Healthy</div>
      <div class="stat-delta">v<?= APP_VERSION ?> running</div>
    </div>
    <div class="stat-icon"><span class="material-icons">verified</span></div>
  </div>
</div>

<?php if (!empty($recentActivity)): ?>
<div class="card">
  <div class="card-head"><h3>Recent Activity</h3></div>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>User</th><th>Action</th><th>IP</th><th>Time</th></tr></thead>
      <tbody>
        <?php foreach ($recentActivity as $a): ?>
          <tr>
            <td><?= e($a['user_name'] ?? '—') ?></td>
            <td><?= e($a['action']) ?></td>
            <td class="text-muted" style="font-size:.82rem;"><?= e($a['ip_address']) ?></td>
            <td class="text-muted" style="font-size:.82rem;"><?= timeAgo($a['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
