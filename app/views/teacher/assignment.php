<?php /** @var array $data */
extract($data, EXTR_SKIP);
$a = $assignment;
?>
<div class="breadcrumb">
  <a href="<?= url('teacher/courses/edit/' . $a['course_id']) ?>">Course</a>
  <span class="material-icons">chevron_right</span>
  <span><?= e($a['title']) ?></span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1><?= e($a['title']) ?></h1>
    <p>
      <span class="badge"><?= e($a['course_title']) ?></span>
      <span class="badge badge-info"><?= $a['max_score'] ?> pts</span>
      <?php if ($a['due_date']): ?><span class="badge badge-warning">Due <?= formatDate($a['due_date']) ?></span><?php endif; ?>
      <span class="badge"><?= count($submissions) ?> submission(s)</span>
    </p>
  </div>
</div>

<div class="card card-pad mb-3">
  <h3 style="margin-bottom:8px;">Instructions</h3>
  <div style="line-height:1.7;"><?= nl2br(e($a['description'] ?? 'No instructions provided.')) ?></div>
</div>

<div class="card">
  <div class="card-head"><h3>Submissions</h3></div>
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th>Student</th>
          <th>Submitted</th>
          <th>Status</th>
          <th>Score</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($submissions)): ?>
          <tr><td colspan="5" class="text-center text-muted" style="padding:30px;">No submissions yet.</td></tr>
        <?php else: foreach ($submissions as $s): ?>
          <tr>
            <td>
              <div class="flex gap-sm">
                <span class="avatar sm" style="background:<?= avatarColor($s['student_name']) ?>"><?= initials($s['student_name']) ?></span>
                <div>
                  <strong><?= e($s['student_name']) ?></strong>
                  <div class="text-muted" style="font-size:.78rem;"><?= e($s['student_email']) ?></div>
                </div>
              </div>
            </td>
            <td class="text-muted" style="font-size:.82rem;"><?= timeAgo($s['submitted_at']) ?></td>
            <td>
              <?php if ($s['status'] === 'graded'): ?>
                <span class="badge badge-success">Graded</span>
              <?php else: ?>
                <span class="badge badge-warning">Pending</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($s['score'] !== null): ?>
                <strong><?= $s['score'] ?></strong> / <?= $a['max_score'] ?>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="text-right">
              <a href="<?= url('teacher/grade/' . $s['id']) ?>" class="btn btn-primary btn-sm">
                <?= $s['status'] === 'graded' ? 'View' : 'Grade' ?>
              </a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
