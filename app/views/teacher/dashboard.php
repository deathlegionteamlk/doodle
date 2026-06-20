<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>Teacher Studio</h1>
    <p>Welcome back, <?= e(Auth::user()['full_name']) ?>. Here's your teaching overview.</p>
  </div>
  <div class="actions">
    <a href="<?= url('teacher/courses') ?>" class="btn btn-secondary"><span class="material-icons">school</span> My courses</a>
    <a href="<?= url('teacher/quizzes') ?>" class="btn btn-primary"><span class="material-icons">quiz</span> Quiz bank</a>
  </div>
</div>

<div class="grid grid-4 mb-3">
  <div class="stat">
    <div><div class="stat-label">My Courses</div><div class="stat-value"><?= $stats['courses'] ?></div><div class="stat-delta"><?= $stats['published'] ?> published</div></div>
    <div class="stat-icon"><span class="material-icons">school</span></div>
  </div>
  <div class="stat success">
    <div><div class="stat-label">Active Students</div><div class="stat-value"><?= $stats['students'] ?></div><div class="stat-delta">across all courses</div></div>
    <div class="stat-icon"><span class="material-icons">group</span></div>
  </div>
  <div class="stat warning">
    <div><div class="stat-label">Pending Grades</div><div class="stat-value"><?= $stats['pending'] ?></div><div class="stat-delta">submissions awaiting</div></div>
    <div class="stat-icon"><span class="material-icons">pending_actions</span></div>
  </div>
  <div class="stat info">
    <div><div class="stat-label">Lessons Created</div><div class="stat-value"><?= $stats['lessons'] ?? '—' ?></div><div class="stat-delta"><?= $stats['quizzes'] ?? 0 ?> quizzes</div></div>
    <div class="stat-icon"><span class="material-icons">menu_book</span></div>
  </div>
</div>

<div class="grid grid-2 mb-3" style="grid-template-columns: 2fr 1fr; align-items: flex-start;">
  <div class="card">
    <div class="card-head">
      <h3>My courses</h3>
      <a href="<?= url('teacher/courses') ?>" class="btn btn-ghost btn-sm">Manage all</a>
    </div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Course</th><th>Status</th><th>Students</th><th>Lessons</th><th class="text-right">Actions</th></tr></thead>
        <tbody>
          <?php if (empty($courses)): ?>
            <tr><td colspan="5" class="text-center text-muted" style="padding:30px;">You haven't created any courses yet.</td></tr>
          <?php else: foreach ($courses as $c): ?>
            <tr>
              <td><a href="<?= url('teacher/courses/edit/' . $c['id']) ?>"><strong><?= e($c['title']) ?></strong></a></td>
              <td><span class="badge badge-<?= $c['status'] === 'published' ? 'success' : 'warning' ?>"><?= ucfirst($c['status']) ?></span></td>
              <td><?= (int) $c['student_count'] ?></td>
              <td><?= (int) $c['lesson_count'] ?></td>
              <td class="text-right">
                <a href="<?= url('teacher/courses/edit/' . $c['id']) ?>" class="btn btn-secondary btn-sm"><span class="material-icons">edit</span></a>
                <a href="<?= url('course/view/' . $c['id']) ?>" class="btn btn-secondary btn-sm"><span class="material-icons">visibility</span></a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Pending submissions</h3></div>
    <?php if (empty($pending)): ?>
      <div class="empty" style="padding:30px;">
        <span class="material-icons" style="font-size:36px;">inbox</span>
        <p style="font-size:.9rem;">Nothing to grade right now.</p>
      </div>
    <?php else: ?>
      <div>
        <?php foreach ($pending as $p): ?>
          <div style="padding:12px 18px;border-bottom:1px solid var(--border);">
            <div class="flex-between">
              <strong style="font-size:.88rem;"><?= e($p['student_name']) ?></strong>
              <span class="text-muted" style="font-size:.72rem;"><?= timeAgo($p['submitted_at']) ?></span>
            </div>
            <div class="text-muted" style="font-size:.8rem;"><?= e($p['assignment_title']) ?></div>
            <div class="text-muted" style="font-size:.72rem;"><?= e($p['course_title']) ?></div>
            <a href="<?= url('teacher/grade/' . $p['id']) ?>" class="btn btn-primary btn-sm" style="margin-top:6px;">Grade now</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
