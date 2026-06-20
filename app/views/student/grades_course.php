<?php /** @var array $data */
extract($data, EXTR_SKIP);
$c = $course;
?>
<div class="breadcrumb">
  <a href="<?= url('student/grades') ?>">My grades</a>
  <span class="material-icons">chevron_right</span>
  <span><?= e($c['title']) ?></span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1>My Grades · <?= e($c['title']) ?></h1>
    <p>Detailed breakdown of your performance in this course.</p>
  </div>
  <div class="actions">
    <a href="<?= url('course/view/' . $c['id']) ?>" class="btn btn-secondary"><span class="material-icons">school</span> Back to course</a>
  </div>
</div>

<div class="grid grid-2" style="align-items:flex-start;">
  <div class="card">
    <div class="card-head"><h3>Assignments & graded items</h3></div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Item</th><th>Score</th><th>Max</th><th>%</th><th>Graded</th></tr></thead>
        <tbody>
          <?php if (empty($grades)): ?>
            <tr><td colspan="5" class="text-center text-muted" style="padding:24px;">No grades recorded yet.</td></tr>
          <?php else: foreach ($grades as $g):
            $pct = $g['max_score'] > 0 ? round(($g['score'] / $g['max_score']) * 100, 1) : 0;
            $color = $pct >= 80 ? 'success' : ($pct >= 60 ? 'warning' : 'danger');
          ?>
            <tr>
              <td>
                <strong><?= e($g['item_name']) ?></strong>
                <span class="badge" style="margin-left:6px;"><?= ucfirst($g['item_type']) ?></span>
                <?php if (!empty($g['feedback'])): ?>
                  <div class="text-muted" style="font-size:.78rem;margin-top:4px;font-style:italic;">"<?= e($g['feedback']) ?>"</div>
                <?php endif; ?>
              </td>
              <td><strong><?= $g['score'] ?></strong></td>
              <td class="text-muted"><?= $g['max_score'] ?></td>
              <td><span class="badge badge-<?= $color ?>"><?= $pct ?>%</span></td>
              <td class="text-muted" style="font-size:.82rem;"><?= timeAgo($g['graded_at']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Quiz attempts</h3></div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Quiz</th><th>Score</th><th>Result</th><th>Submitted</th></tr></thead>
        <tbody>
          <?php if (empty($attempts)): ?>
            <tr><td colspan="4" class="text-center text-muted" style="padding:24px;">No quiz attempts yet.</td></tr>
          <?php else: foreach ($attempts as $a): ?>
            <tr>
              <td><strong><?= e($a['quiz_title']) ?></strong></td>
              <td><strong><?= $a['score'] ?></strong> / <?= $a['max_score'] ?> (<?= $a['percentage'] ?>%)</td>
              <td>
                <?php if ($a['passed'] === 1): ?>
                  <span class="badge badge-success">Passed</span>
                <?php elseif ($a['passed'] === 0): ?>
                  <span class="badge badge-danger">Failed</span>
                <?php else: ?>
                  <span class="badge">In progress</span>
                <?php endif; ?>
              </td>
              <td class="text-muted" style="font-size:.82rem;"><?= $a['submitted_at'] ? timeAgo($a['submitted_at']) : '—' ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
