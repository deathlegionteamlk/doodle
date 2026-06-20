<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="breadcrumb">
  <a href="<?= url('course/view/' . $course['id']) ?>"><?= e($course['title']) ?></a>
  <span class="material-icons">chevron_right</span>
  <span>Code Exercises</span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1>Code Playground</h1>
    <p><?= e($course['title']) ?> · practice programming with auto-graded test cases</p>
  </div>
  <?php if (Auth::ownsCourse($course['id']) || Auth::isAdmin()): ?>
    <div class="actions"><a href="<?= url('code/manage/' . $course['id']) ?>" class="btn btn-secondary"><span class="material-icons">settings</span> Manage</a></div>
  <?php endif; ?>
</div>

<?php if (empty($exercises)): ?>
  <div class="card card-pad empty">
    <span class="material-icons">code</span>
    <h3>No code exercises yet</h3>
    <p>Teachers can create programming challenges with hidden test cases for students to solve.</p>
  </div>
<?php else: ?>
  <div class="grid grid-2">
    <?php foreach ($exercises as $ex): ?>
      <a href="<?= url('code/exercise/' . $ex['id']) ?>" class="card card-pad" style="text-decoration:none;color:inherit;">
        <div class="flex-between mb-1">
          <h3 style="font-size:1.05rem;"><?= e($ex['title']) ?></h3>
          <span class="badge badge-<?= $ex['difficulty'] === 'easy' ? 'success' : ($ex['difficulty'] === 'medium' ? 'warning' : 'danger') ?>"><?= ucfirst($ex['difficulty']) ?></span>
        </div>
        <p class="text-muted" style="font-size:.85rem;"><?= e(truncate($ex['description'] ?: $ex['problem_statement'], 100)) ?></p>
        <div class="flex gap-lg mt-2" style="font-size:.78rem;color:var(--text-muted);">
          <span><span class="material-icons" style="font-size:14px;vertical-align:middle;">terminal</span> <?= ucfirst($ex['language']) ?></span>
          <span><span class="material-icons" style="font-size:14px;vertical-align:middle;">check_circle</span> <?= $ex['test_count'] ?> tests</span>
          <span><span class="material-icons" style="font-size:14px;vertical-align:middle;">stars</span> <?= $ex['points'] ?> pts</span>
          <?php if ($ex['my_passed'] > 0): ?>
            <span class="badge badge-success">Solved</span>
          <?php endif; ?>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
