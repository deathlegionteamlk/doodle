<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-head">
    <h1>My Grades</h1>
    <p>Track your performance across all enrolled courses.</p>
  </div>
</div>

<?php if (empty($courses)): ?>
  <div class="card card-pad empty">
    <span class="material-icons">grading</span>
    <h3>No grades yet</h3>
    <p>Once you enroll in a course and submit assignments or take quizzes, your grades will appear here.</p>
    <a href="<?= url('catalog') ?>" class="btn btn-primary">Browse courses</a>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr>
            <th>Course</th>
            <th>Progress</th>
            <th>Graded items</th>
            <th>Score</th>
            <th>%</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($courses as $c):
            $pct = $c['possible'] > 0 ? round(($c['earned'] / $c['possible']) * 100, 1) : 0;
            $color = $pct >= 80 ? 'success' : ($pct >= 60 ? 'warning' : 'danger');
          ?>
            <tr>
              <td><a href="<?= url('course/view/' . $c['id']) ?>"><strong><?= e($c['title']) ?></strong></a></td>
              <td style="min-width:140px;">
                <div class="progress"><div class="progress-bar" style="width:<?= $c['progress'] ?>%;"></div></div>
                <small class="text-muted"><?= $c['progress'] ?>%</small>
              </td>
              <td><?= (int) $c['graded_items'] ?></td>
              <td>
                <?php if ($c['possible'] > 0): ?>
                  <strong><?= $c['earned'] ?></strong> / <?= $c['possible'] ?>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($c['possible'] > 0): ?>
                  <span class="badge badge-<?= $color ?>"><?= $pct ?>%</span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td class="text-right">
                <a href="<?= url('student/grades/' . $c['id']) ?>" class="btn btn-secondary btn-sm">Details</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
