<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>All Courses</h1>
    <p><?= number_format($total) ?> courses across all teachers.</p>
  </div>
</div>

<div class="card card-pad mb-3">
  <form method="get" action="<?= url('admin/courses') ?>" class="flex gap-sm">
    <input type="hidden" name="r" value="admin/courses">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search courses..." class="form-control" style="flex:1;">
    <button class="btn btn-primary" type="submit">Search</button>
    <a href="<?= url('admin/courses') ?>" class="btn btn-ghost">Reset</a>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th>Course</th>
          <th>Teacher</th>
          <th>Status</th>
          <th>Enrollments</th>
          <th>Created</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($courses)): ?>
          <tr><td colspan="6" class="text-center text-muted" style="padding:30px;">No courses found.</td></tr>
        <?php else: foreach ($courses as $c): ?>
          <tr>
            <td>
              <a href="<?= url('course/view/' . $c['id']) ?>"><strong><?= e($c['title']) ?></strong></a>
              <div class="text-muted" style="font-size:.78rem;"><?= e(ucfirst($c['level'])) ?> · <?= e($c['visibility']) ?></div>
            </td>
            <td><?= e($c['teacher_name']) ?></td>
            <td><span class="badge badge-<?= $c['status'] === 'published' ? 'success' : 'warning' ?>"><?= ucfirst($c['status']) ?></span></td>
            <td><?= (int) $c['enroll_count'] ?></td>
            <td class="text-muted" style="font-size:.82rem;"><?= timeAgo($c['created_at']) ?></td>
            <td class="text-right">
              <a href="<?= url('course/view/' . $c['id']) ?>" class="btn btn-secondary btn-sm"><span class="material-icons">visibility</span></a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $pagination ?>
