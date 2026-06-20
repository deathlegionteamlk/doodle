<?php /** @var array $data */
extract($data, EXTR_SKIP);
$q = $quiz;
?>
<div class="breadcrumb">
  <a href="<?= url('teacher/quizzes') ?>">Quiz bank</a>
  <span class="material-icons">chevron_right</span>
  <span><?= e($q['title']) ?></span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1><?= e($q['title']) ?></h1>
    <p>
      <span class="badge"><?= e($q['course_title']) ?></span>
      <span class="badge badge-info"><?= $q['passing_score'] ?>% to pass</span>
      <span class="badge"><?= $q['max_attempts'] ?> attempt(s)</span>
      <?php if ($q['time_limit']): ?><span class="badge"><?= $q['time_limit'] ?> min limit</span><?php endif; ?>
      <span class="badge"><?= count($questions) ?> question(s)</span>
    </p>
  </div>
  <div class="actions">
    <button class="btn btn-primary" onclick="document.getElementById('newQuestionModal').style.display='flex'"><span class="material-icons">add</span> Add question</button>
  </div>
</div>

<?php if (empty($questions)): ?>
  <div class="card card-pad empty">
    <span class="material-icons">quiz</span>
    <h3>No questions yet</h3>
    <p>Add your first question. You can choose from multiple-choice, true/false, or short-answer formats.</p>
    <button class="btn btn-primary" onclick="document.getElementById('newQuestionModal').style.display='flex'"><span class="material-icons">add</span> Add question</button>
  </div>
<?php else: ?>
  <?php $total = 0; foreach ($questions as $qq) $total += (float)$qq['points']; ?>
  <div class="card card-pad mb-3" style="background:var(--primary-light);color:var(--primary);">
    <div class="flex-between">
      <strong>Total points: <?= $total ?></strong>
      <span><?= count($questions) ?> question(s)</span>
    </div>
  </div>
  <?php foreach ($questions as $i => $qq): ?>
    <div class="quiz-question">
      <div class="flex-between mb-1">
        <div class="q-title">Question <?= $i + 1 ?>: <?= e($qq['question']) ?>
          <span class="q-points">(<?= $qq['points'] ?> pts)</span>
        </div>
        <div class="flex gap-sm">
          <span class="badge"><?= ucfirst(str_replace('_',' ', $qq['type'])) ?></span>
          <a href="<?= url('teacher/deleteQuestion/' . $qq['id']) ?>" class="btn btn-danger btn-sm" data-confirm="Delete this question?"><span class="material-icons">delete</span></a>
        </div>
      </div>
      <?php if ($qq['type'] === 'multiple_choice'):
        $opts = json_decode($qq['options'] ?? '[]', true) ?: [];
        $correctIdx = (int) $qq['correct_answer'];
        foreach ($opts as $idx => $opt): ?>
          <div class="quiz-option <?= $idx === $correctIdx ? 'correct' : '' ?>" style="cursor:default;">
            <span class="material-icons" style="font-size:18px;"><?= $idx === $correctIdx ? 'check_circle' : 'radio_button_unchecked' ?></span>
            <span><?= e($opt) ?></span>
          </div>
        <?php endforeach; ?>
      <?php elseif ($qq['type'] === 'true_false'): ?>
        <div class="quiz-option <?= $qq['correct_answer'] === 'true' ? 'correct' : '' ?>" style="cursor:default;">
          <span class="material-icons" style="font-size:18px;"><?= $qq['correct_answer'] === 'true' ? 'check_circle' : 'radio_button_unchecked' ?></span>
          <span>True</span>
        </div>
        <div class="quiz-option <?= $qq['correct_answer'] === 'false' ? 'correct' : '' ?>" style="cursor:default;">
          <span class="material-icons" style="font-size:18px;"><?= $qq['correct_answer'] === 'false' ? 'check_circle' : 'radio_button_unchecked' ?></span>
          <span>False</span>
        </div>
      <?php elseif ($qq['type'] === 'short_answer'): ?>
        <div class="quiz-option" style="cursor:default;background:var(--success-light);border-color:var(--success);">
          <span class="material-icons" style="font-size:18px;color:var(--success);">check</span>
          <span>Accepted answer: <strong><?= e($qq['correct_answer']) ?></strong></span>
        </div>
      <?php endif; ?>
      <?php if (!empty($qq['explanation'])): ?>
        <p style="margin-top:10px;font-size:.85rem;color:var(--text-muted);font-style:italic;">Explanation: <?= e($qq['explanation']) ?></p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<div id="newQuestionModal" class="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px;">
  <div class="card" style="max-width:680px;width:100%;max-height:90vh;overflow-y:auto;">
    <div class="card-head">
      <h3>Add question</h3>
      <button class="btn btn-ghost btn-sm" onclick="document.getElementById('newQuestionModal').style.display='none'"><span class="material-icons">close</span></button>
    </div>
    <div class="card-body">
      <form method="post" action="<?= url('teacher/addQuestion/' . $q['id']) ?>">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <div class="form-grid">
          <div class="form-group">
            <label>Question type</label>
            <select name="type" id="qType" onchange="toggleQuestionFields()">
              <option value="multiple_choice">Multiple choice</option>
              <option value="true_false">True / False</option>
              <option value="short_answer">Short answer</option>
            </select>
          </div>
          <div class="form-group">
            <label>Points</label>
            <input type="number" name="points" value="1" min="0.5" step="0.5">
          </div>
        </div>
        <div class="form-group">
          <label>Question text</label>
          <textarea name="question" rows="3" required></textarea>
        </div>

        <div id="mcFields">
          <label>Answer options (mark the correct one)</label>
          <div id="optionsList">
            <div class="flex gap-sm mb-1">
              <input type="radio" name="correct" value="0" checked style="margin-top:12px;">
              <input type="text" name="options[]" placeholder="Option 1" class="form-control">
              <button type="button" class="btn btn-ghost btn-sm" onclick="this.parentElement.remove()"><span class="material-icons">close</span></button>
            </div>
            <div class="flex gap-sm mb-1">
              <input type="radio" name="correct" value="1" style="margin-top:12px;">
              <input type="text" name="options[]" placeholder="Option 2" class="form-control">
              <button type="button" class="btn btn-ghost btn-sm" onclick="this.parentElement.remove()"><span class="material-icons">close</span></button>
            </div>
          </div>
          <button type="button" class="btn btn-secondary btn-sm" onclick="addOption()"><span class="material-icons">add</span> Add option</button>
        </div>

        <div id="tfFields" style="display:none;">
          <div class="form-group">
            <label>Correct answer</label>
            <select name="tf_correct">
              <option value="true">True</option>
              <option value="false">False</option>
            </select>
          </div>
        </div>

        <div id="saFields" style="display:none;">
          <div class="form-group">
            <label>Accepted answer (case-insensitive)</label>
            <input type="text" name="short_answer" placeholder="e.g., Paris">
            <span class="hint">Student's answer must match this exactly (case-insensitive).</span>
          </div>
        </div>

        <div class="form-group">
          <label>Explanation (optional, shown after grading)</label>
          <textarea name="explanation" rows="2"></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Add question</button>
      </form>
    </div>
  </div>
</div>

<script>
function toggleQuestionFields() {
  const t = document.getElementById('qType').value;
  document.getElementById('mcFields').style.display = (t === 'multiple_choice') ? 'block' : 'none';
  document.getElementById('tfFields').style.display = (t === 'true_false') ? 'block' : 'none';
  document.getElementById('saFields').style.display = (t === 'short_answer') ? 'block' : 'none';
}
let optCount = 2;
function addOption() {
  optCount++;
  const div = document.createElement('div');
  div.className = 'flex gap-sm mb-1';
  div.innerHTML = '<input type="radio" name="correct" value="' + optCount + '" style="margin-top:12px;"><input type="text" name="options[]" placeholder="Option ' + optCount + '" class="form-control"><button type="button" class="btn btn-ghost btn-sm" onclick="this.parentElement.remove()"><span class="material-icons">close</span></button>';
  document.getElementById('optionsList').appendChild(div);
}
</script>
