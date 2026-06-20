<?php /** @var array $data */
extract($data, EXTR_SKIP);
$s = $sub;
?>
<div class="breadcrumb">
  <a href="<?= url('teacher/assignment/' . $s['assignment_id']) ?>">Assignment</a>
  <span class="material-icons">chevron_right</span>
  <span>Grade submission</span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1>Grade submission</h1>
    <p><?= e($s['student_name']) ?> · <?= e($s['assignment_title']) ?></p>
  </div>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px; align-items: flex-start;">
  <div>
    <div class="card card-pad mb-3">
      <h3 style="margin-bottom: 12px;">Student's submission</h3>
      <?php if (!empty($s['content'])): ?>
        <div style="line-height:1.7;"><?= nl2br(e($s['content'])) ?></div>
      <?php else: ?>
        <p class="text-muted">No written submission.</p>
      <?php endif; ?>

      <?php if (!empty($s['file_path'])): ?>
        <a href="<?= uploadUrl($s['file_path']) ?>" download class="file-attachment" style="text-decoration:none;color:inherit;">
          <div class="file-icon"><span class="material-icons"><?= fileIcon(pathinfo($s['file_name'], PATHINFO_EXTENSION)) ?></span></div>
          <div class="file-meta">
            <strong><?= e($s['file_name']) ?></strong>
            <span class="size">Click to download and review</span>
          </div>
          <span class="material-icons">download</span>
        </a>
      <?php endif; ?>

      <p class="text-muted" style="font-size:.82rem;margin-top:14px;">Submitted <?= timeAgo($s['submitted_at']) ?></p>
    </div>
  </div>

  <div class="card card-pad">
    <h3 style="margin-bottom: 14px;">Grading</h3>
    <form method="post" action="<?= url('teacher/grade/' . $s['id']) ?>">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
      <div class="form-group">
        <label>Score (out of <?= $s['max_score'] ?>)</label>
        <input type="number" name="score" min="0" max="<?= $s['max_score'] ?>" step="0.5" value="<?= e($s['score'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Feedback for student</label>
        <textarea name="feedback" rows="6" placeholder="Provide constructive feedback..."><?= e($s['feedback'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg"><span class="material-icons">grading</span> Save grade</button>
      <a href="<?= url('teacher/assignment/' . $s['assignment_id']) ?>" class="btn btn-ghost btn-block" style="margin-top:6px;">Cancel</a>
    </form>
  </div>
</div>
