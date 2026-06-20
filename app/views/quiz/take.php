<?php /** @var array $data */
extract($data, EXTR_SKIP);
$q = $quiz;
?>
<div class="breadcrumb">
  <a href="<?= url('course/view/' . $q['course_id']) ?>"><?= e($q['course_title']) ?></a>
  <span class="material-icons">chevron_right</span>
  <span><?= e($q['title']) ?></span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1><?= e($q['title']) ?></h1>
    <p>
      <span class="badge"><?= e($q['course_title']) ?></span>
      <span class="badge badge-info"><?= $q['passing_score'] ?>% to pass</span>
      <span class="badge"><?= count($questions) ?> questions</span>
      <?php if ($q['time_limit']): ?><span class="badge badge-warning"><span class="material-icons" style="font-size:12px;vertical-align:middle;">timer</span> <?= $q['time_limit'] ?> min</span><?php endif; ?>
      <?php if ($attemptNumber > 0): ?><span class="badge">Attempt <?= $attemptNumber ?> of <?= $q['max_attempts'] ?></span><?php endif; ?>
    </p>
  </div>
</div>

<?php if (!empty($q['instructions'])): ?>
  <div class="alert alert-info">
    <span class="material-icons">info</span>
    <div><?= nl2br(e($q['instructions'])) ?></div>
  </div>
<?php endif; ?>

<form method="post" action="<?= url('quiz/submit/' . $q['id']) ?>" id="quizForm">
  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
  <?php foreach ($questions as $i => $qq):
    $opts = $qq['type'] === 'multiple_choice' ? (json_decode($qq['options'] ?? '[]', true) ?: []) : [];
  ?>
    <div class="quiz-question">
      <div class="q-title">
        Question <?= $i + 1 ?> of <?= count($questions) ?>: <?= e($qq['question']) ?>
        <span class="q-points">(<?= $qq['points'] ?> pts)</span>
      </div>

      <?php if ($qq['type'] === 'multiple_choice'): ?>
        <?php foreach ($opts as $idx => $opt): ?>
          <label class="quiz-option">
            <input type="radio" name="q_<?= $qq['id'] ?>" value="<?= $idx ?>" required>
            <span><?= e($opt) ?></span>
          </label>
        <?php endforeach; ?>
      <?php elseif ($qq['type'] === 'true_false'): ?>
        <label class="quiz-option">
          <input type="radio" name="q_<?= $qq['id'] ?>" value="true" required>
          <span>True</span>
        </label>
        <label class="quiz-option">
          <input type="radio" name="q_<?= $qq['id'] ?>" value="false" required>
          <span>False</span>
        </label>
      <?php elseif ($qq['type'] === 'short_answer'): ?>
        <input type="text" name="q_<?= $qq['id'] ?>" class="form-control" placeholder="Type your answer...">
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <div class="card card-pad" style="text-align:center;">
    <p>Once you submit, your answers cannot be changed. The quiz will be auto-graded.</p>
    <button type="submit" class="btn btn-primary btn-lg"><span class="material-icons">send</span> Submit quiz</button>
    <a href="<?= url('course/view/' . $q['course_id']) ?>" class="btn btn-ghost" style="margin-left:8px;">Cancel</a>
  </div>
</form>

<?php if ($q['time_limit']): ?>
<script>
let timeLeft = <?= $q['time_limit'] ?> * 60;
const timer = setInterval(() => {
  timeLeft--;
  if (timeLeft <= 0) {
    clearInterval(timer);
    alert('Time is up! Submitting your quiz.');
    document.getElementById('quizForm').submit();
  }
}, 1000);
</script>
<?php endif; ?>
