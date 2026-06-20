<?php /** @var array $data */
extract($data, EXTR_SKIP);
$q = $quiz;
$showAnswers = (bool) $q['show_answers'];
$color = $passed ? 'success' : 'danger';
?>
<div class="page-head" style="text-align:center;">
  <h1>Quiz Result</h1>
  <p><?= e($q['title']) ?></p>
</div>

<div class="card card-pad" style="text-align:center; max-width:600px; margin:0 auto 24px;">
  <span class="material-icons" style="font-size:64px;color:<?= $passed ? 'var(--success)' : 'var(--danger)' ?>;">
    <?= $passed ? 'emoji_events' : 'cancel' ?>
  </span>
  <h1 style="font-size:3rem;color:<?= $passed ? 'var(--success)' : 'var(--danger)' ?>;margin:8px 0;">
    <?= $percentage ?>%
  </h1>
  <p style="font-size:1.1rem;">
    <strong><?= $score ?></strong> / <?= $maxScore ?> points
  </p>
  <span class="badge badge-<?= $color ?>" style="font-size:.95rem;padding:6px 16px;">
    <?= $passed ? 'Passed' : 'Did not pass' ?>
  </span>
  <div style="margin-top:24px;">
    <a href="<?= url('course/view/' . $q['course_id']) ?>" class="btn btn-secondary">Back to course</a>
    <?php if (Auth::isStudent()): ?>
      <a href="<?= url('student/grades/' . $q['course_id']) ?>" class="btn btn-primary">View my grades</a>
    <?php endif; ?>
  </div>
</div>

<?php if ($showAnswers): ?>
<h3 style="margin: 30px 0 16px;">Detailed breakdown</h3>
<?php foreach ($results as $i => $r):
  $qq = $r['question'];
  $ua = $r['user_answer'];
?>
  <div class="quiz-question">
    <div class="q-title">
      Question <?= $i + 1 ?>: <?= e($qq['question']) ?>
      <span class="q-points">(<?= $qq['points'] ?> pts)</span>
    </div>
    <div class="flex gap-sm mb-1">
      <span class="badge badge-<?= $ua['is_correct'] ? 'success' : 'danger' ?>">
        <?= $ua['is_correct'] ? 'Correct' : 'Incorrect' ?>
      </span>
      <span class="badge">Your answer: <?= e($ua['user']) ?></span>
      <?php if (!$ua['is_correct']): ?>
        <span class="badge badge-success">Correct: <?= e($ua['correct']) ?></span>
      <?php endif; ?>
    </div>
    <?php if (!empty($qq['explanation'])): ?>
      <p style="margin-top:8px;font-size:.85rem;color:var(--text-muted);font-style:italic;">
        <strong>Explanation:</strong> <?= e($qq['explanation']) ?>
      </p>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
<?php else: ?>
<div class="alert alert-info" style="max-width:600px;margin:24px auto;">
  <span class="material-icons">info</span>
  <div>The instructor has chosen not to show detailed answers. You can see your score above.</div>
</div>
<?php endif; ?>
