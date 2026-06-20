<?php /** @var array $data */
extract($data, EXTR_SKIP);
$p = $poll;
?>
<div class="breadcrumb">
  <a href="<?= url('course/view/' . $p['course_id']) ?>"><?= e($p['course_title']) ?></a>
  <span class="material-icons">chevron_right</span>
  <a href="<?= url('poll/course/' . $p['course_id']) ?>">Polls</a>
  <span class="material-icons">chevron_right</span>
  <span><?= e(truncate($p['question'], 30)) ?></span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1><?= e($p['question']) ?></h1>
    <p>
      <span class="badge"><?= e($p['course_title']) ?></span>
      <span class="text-muted">by <?= e($p['author_name']) ?> · <?= timeAgo($p['created_at']) ?></span>
      <?php if ($p['is_anonymous']): ?><span class="badge badge-info">Anonymous</span><?php endif; ?>
      <?php if (!$p['is_active']): ?><span class="badge badge-danger">Closed</span><?php endif; ?>
    </p>
  </div>
  <?php if (Auth::ownsCourse($p['course_id']) || Auth::isAdmin() || $p['user_id'] == Auth::id()): ?>
    <div class="actions">
      <a href="<?= url('poll/toggle/' . $p['id']) ?>" class="btn btn-secondary btn-sm"><?= $p['is_active'] ? 'Close' : 'Reopen' ?></a>
      <a href="<?= url('poll/delete/' . $p['id']) ?>" class="btn btn-danger btn-sm" data-confirm="Delete this poll?"><span class="material-icons">delete</span></a>
    </div>
  <?php endif; ?>
</div>

<?php if (!empty($p['description'])): ?>
  <div class="alert alert-info"><div><?= nl2br(e($p['description'])) ?></div></div>
<?php endif; ?>

<div class="card card-pad">
  <?php if (!$hasVoted && $p['is_active']): ?>
    <h3 style="margin-bottom: 14px;">Cast your vote</h3>
    <form method="post" action="<?= url('poll/view/' . $p['id']) ?>">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
      <?php foreach ($options as $opt): ?>
        <label class="quiz-option">
          <input type="<?= $p['allow_multiple'] ? 'checkbox' : 'radio' ?>" name="option_ids[]" value="<?= $opt['id'] ?>" required>
          <span><?= e($opt['option_text']) ?></span>
        </label>
      <?php endforeach; ?>
      <button type="submit" class="btn btn-primary btn-lg mt-2"><span class="material-icons">how_to_vote</span> Submit vote</button>
    </form>
  <?php else: ?>
    <h3 style="margin-bottom: 14px;">Results · <?= $totalResponses ?> response(s)</h3>
    <?php foreach ($options as $opt): ?>
      <div class="poll-result-row">
        <div class="flex-between mb-1">
          <strong style="font-size:.92rem;">
            <?= e($opt['option_text']) ?>
            <?php if (in_array($opt['id'], $userVotes)): ?>
              <span class="badge badge-primary" style="font-size:.65rem;margin-left:6px;">Your vote</span>
            <?php endif; ?>
          </strong>
          <span class="text-muted" style="font-size:.85rem;"><?= $opt['count'] ?> (<?= $opt['pct'] ?>%)</span>
        </div>
        <div class="progress" style="height:14px;border-radius:7px;">
          <div class="progress-bar" style="width:<?= $opt['pct'] ?>%;"></div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$hasVoted): ?>
      <p class="text-muted mt-2" style="font-size:.85rem;">You haven't voted on this poll.</p>
    <?php endif; ?>
  <?php endif; ?>
</div>
