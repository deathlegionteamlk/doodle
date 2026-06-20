<?php /** @var array $data */
extract($data, EXTR_SKIP);
$c = $course;
?>
<div class="breadcrumb">
  <a href="<?= url('teacher/courses') ?>">My courses</a>
  <span class="material-icons">chevron_right</span>
  <a href="<?= url('teacher/courses/edit/' . $c['id']) ?>"><?= e($c['title']) ?></a>
  <span class="material-icons">chevron_right</span>
  <span>Students</span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1>Enrolled Students</h1>
    <p><?= count($students) ?> student(s) enrolled in <?= e($c['title']) ?>.</p>
  </div>
  <div class="actions">
    <a href="<?= url('teacher/gradebook/course/' . $c['id']) ?>" class="btn btn-secondary"><span class="material-icons">grading</span> View gradebook</a>
  </div>
</div>

<?php if (empty($students)): ?>
  <div class="card card-pad empty">
    <span class="material-icons">group</span>
    <h3>No students enrolled yet</h3>
    <p>When students enroll in this course, they will appear here.</p>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr>
            <th>Student</th>
            <th>Email</th>
            <th>Progress</th>
            <th>Lessons done</th>
            <th>Enrolled</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $s): ?>
            <tr>
              <td>
                <div class="flex gap-sm">
                  <span class="avatar sm" style="background:<?= avatarColor($s['full_name']) ?>">
                    <?php if (!empty($s['avatar'])): ?><img src="<?= uploadUrl($s['avatar']) ?>" alt=""><?php else: ?><?= initials($s['full_name']) ?><?php endif; ?>
                  </span>
                  <div>
                    <strong><?= e($s['full_name']) ?></strong>
                  </div>
                </div>
              </td>
              <td class="text-muted" style="font-size:.85rem;"><?= e($s['email']) ?></td>
              <td style="min-width:160px;">
                <div class="flex-between mb-1" style="font-size:.78rem;">
                  <span><?= $s['progress'] ?>%</span>
                </div>
                <div class="progress"><div class="progress-bar" style="width:<?= $s['progress'] ?>%;"></div></div>
              </td>
              <td><?= (int) $s['lessons_done'] ?></td>
              <td class="text-muted" style="font-size:.82rem;"><?= timeAgo($s['enrolled_at']) ?></td>
              <td>
                <?php if (!empty($s['completed_at'])): ?>
                  <span class="badge badge-success">Completed</span>
                <?php else: ?>
                  <span class="badge badge-info">In progress</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
