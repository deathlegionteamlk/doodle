<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="breadcrumb">
  <a href="<?= url('course/view/' . $course['id']) ?>"><?= e($course['title']) ?></a>
  <span class="material-icons">chevron_right</span>
  <span>AI Practice Quiz</span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1><span class="material-icons" style="vertical-align:middle;color:var(--primary);">smart_toy</span> AI-Generated Practice Quiz</h1>
    <p><?= count($questions) ?> questions · auto-generated from <?= e($course['title']) ?> materials</p>
  </div>
</div>

<div class="alert alert-info">
  <span class="material-icons">info</span>
  <div>This is a preview-only quiz. To take it for credit, ask your teacher to publish it as a real quiz.</div>
</div>

<?php foreach ($questions as $i => $q): ?>
  <div class="quiz-question">
    <div class="q-title">
      Question <?= $i + 1 ?> of <?= count($questions) ?>: <?= e($q['question']) ?>
    </div>
    <?php foreach ($q['options'] as $j => $opt): ?>
      <div class="quiz-option <?= $j === $q['correct'] ? 'correct' : '' ?>" style="cursor:default;">
        <span class="material-icons" style="font-size:18px;"><?= $j === $q['correct'] ? 'check_circle' : 'radio_button_unchecked' ?></span>
        <span><?= e($opt) ?></span>
        <?php if ($j === $q['correct']): ?>
          <span class="badge badge-success" style="margin-left:auto;">Correct answer</span>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if (!empty($q['explanation'])): ?>
      <p style="margin-top:10px;font-size:.85rem;color:var(--text-muted);font-style:italic;">
        <strong>Explanation:</strong> <?= e($q['explanation']) ?>
      </p>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<div class="card card-pad text-center mt-3">
  <p>Want to study this material differently?</p>
  <div class="flex" style="justify-content:center;gap:10px;flex-wrap:wrap;">
    <a href="<?= url('flashcards/generateFromCourse/' . $course['id']) ?>" class="btn btn-primary"><span class="material-icons">style</span> Generate flashcards</a>
    <a href="<?= url('ai/new?course_id=' . $course['id']) ?>" class="btn btn-secondary"><span class="material-icons">chat</span> Ask AI about this course</a>
    <a href="<?= url('course/view/' . $course['id']) ?>" class="btn btn-ghost">Back to course</a>
  </div>
</div>
