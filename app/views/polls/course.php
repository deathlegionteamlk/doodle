<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="breadcrumb">
  <a href="<?= url('course/view/' . $course['id']) ?>"><?= e($course['title']) ?></a>
  <span class="material-icons">chevron_right</span>
  <span>Polls</span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1>Polls &amp; Surveys</h1>
    <p><?= e($course['title']) ?> · <?= count($polls) ?> poll(s)</p>
  </div>
  <?php if (Auth::ownsCourse($course['id']) || Auth::isAdmin()): ?>
    <div class="actions">
      <button class="btn btn-primary" onclick="document.getElementById('newPollModal').style.display='flex'"><span class="material-icons">add</span> New poll</button>
    </div>
  <?php endif; ?>
</div>

<?php if (empty($polls)): ?>
  <div class="card card-pad empty">
    <span class="material-icons">poll</span>
    <h3>No polls yet</h3>
    <p>Teachers can create quick polls to gather feedback from students.</p>
  </div>
<?php else: ?>
  <div class="grid grid-2">
    <?php foreach ($polls as $p): ?>
      <a href="<?= url('poll/view/' . $p['id']) ?>" class="card card-pad" style="text-decoration:none;color:inherit;">
        <div class="flex-between mb-1">
          <h3 style="font-size:1rem;"><?= e($p['question']) ?></h3>
          <?php if (!$p['is_active']): ?><span class="badge">Closed</span><?php endif; ?>
        </div>
        <p class="text-muted" style="font-size:.82rem;"><?= e(truncate($p['description'] ?? '', 80)) ?></p>
        <div class="flex gap-lg mt-2" style="font-size:.78rem;color:var(--text-muted);">
          <span><span class="material-icons" style="font-size:14px;vertical-align:middle;">how_to_vote</span> <?= $p['response_count'] ?> responses</span>
          <span><span class="material-icons" style="font-size:14px;vertical-align:middle;">list</span> <?= $p['option_count'] ?> options</span>
          <span><?= timeAgo($p['created_at']) ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (Auth::ownsCourse($course['id']) || Auth::isAdmin()): ?>
<div id="newPollModal" class="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px;">
  <div class="card" style="max-width:580px;width:100%;max-height:90vh;overflow-y:auto;">
    <div class="card-head">
      <h3>New poll</h3>
      <button class="btn btn-ghost btn-sm" onclick="document.getElementById('newPollModal').style.display='none'"><span class="material-icons">close</span></button>
    </div>
    <div class="card-body">
      <form method="post" action="<?= url('poll/create/' . $course['id']) ?>">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <div class="form-group">
          <label>Question</label>
          <input type="text" name="question" required placeholder="e.g., Which topic should we cover next?">
        </div>
        <div class="form-group">
          <label>Description (optional)</label>
          <textarea name="description" rows="2"></textarea>
        </div>
        <div class="form-group">
          <label>Options (one per line, at least 2)</label>
          <textarea name="options[]" rows="5" required placeholder="Option 1&#10;Option 2&#10;Option 3" style="white-space:pre;"></textarea>
          <span class="hint">Note: this field accepts one option per line.</span>
        </div>
        <div class="form-grid">
          <div class="form-group checkbox-group">
            <input type="checkbox" name="allow_multiple" id="allowMult" value="1">
            <label for="allowMult">Allow multiple selections</label>
          </div>
          <div class="form-group checkbox-group">
            <input type="checkbox" name="is_anonymous" id="isAnon" value="1">
            <label for="isAnon">Anonymous responses</label>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Create poll</button>
      </form>
    </div>
  </div>
</div>
<script>
// Convert options textarea to array on submit
document.querySelector('#newPollModal form').addEventListener('submit', e => {
  const ta = e.target.querySelector('textarea[name="options[]"]');
  const lines = ta.value.split('\n').map(l => l.trim()).filter(l => l);
  ta.outerHTML = lines.map(l => `<input type="hidden" name="options[]" value="${l.replace(/"/g,'&quot;')}">`).join('');
});
</script>
<?php endif; ?>
