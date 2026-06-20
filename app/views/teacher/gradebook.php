<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>Gradebook</h1>
    <p>Select a course to view and edit grades for all enrolled students.</p>
  </div>
</div>

<?php if (empty($courses)): ?>
  <div class="card card-pad empty">
    <span class="material-icons">grading</span>
    <h3>No courses yet</h3>
    <p>You need to create a course before you can use the gradebook.</p>
    <a href="<?= url('teacher/courses') ?>" class="btn btn-primary">Create a course</a>
  </div>
<?php else: ?>
  <div class="grid grid-2">
    <?php foreach ($courses as $c): ?>
      <?php
      $students = Database::count('enrollments', "course_id = :cid AND status = 'active'", ['cid' => $c['id']]);
      $assignments = Database::count('assignments', 'course_id = :cid', ['cid' => $c['id']]);
      $pending = Database::count('submissions s JOIN assignments a ON s.assignment_id = a.id', 'a.course_id = :cid AND s.status = :st', ['cid' => $c['id'], 'st' => 'submitted']);
      ?>
      <a href="<?= url('teacher/gradebook/course/' . $c['id']) ?>" class="card card-pad" style="text-decoration:none;color:inherit;">
        <div class="flex-between mb-1">
          <h3 style="font-size:1.1rem;"><?= e($c['title']) ?></h3>
          <span class="material-icons" style="color:var(--text-muted);">chevron_right</span>
        </div>
        <div class="flex gap-lg" style="font-size:.85rem;color:var(--text-muted);">
          <span><span class="material-icons" style="font-size:14px;vertical-align:middle;">group</span> <?= $students ?> students</span>
          <span><span class="material-icons" style="font-size:14px;vertical-align:middle;">assignment</span> <?= $assignments ?> items</span>
          <?php if ($pending > 0): ?>
            <span class="badge badge-warning"><?= $pending ?> pending</span>
          <?php endif; ?>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
