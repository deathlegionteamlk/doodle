<?php /** @var array $data */
extract($data, EXTR_SKIP);
$a = $assignment;
$existing = $existing ?? null;
?>
<div class="breadcrumb">
  <a href="<?= url('course/view/' . $course['id']) ?>"><?= e($course['title']) ?></a>
  <span class="material-icons">chevron_right</span>
  <span>Submit assignment</span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1>Submit: <?= e($a['title']) ?></h1>
    <p>
      <span class="badge badge-info"><?= $a['max_score'] ?> pts</span>
      <?php if ($a['due_date']): ?><span class="badge badge-warning">Due <?= formatDateTime($a['due_date']) ?></span><?php endif; ?>
    </p>
  </div>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px; align-items: flex-start;">
  <div class="card card-pad">
    <h3 style="margin-bottom: 12px;">Instructions</h3>
    <div style="line-height:1.7;margin-bottom:20px;"><?= nl2br(e($a['description'] ?? 'No instructions provided.')) ?></div>

    <h3 style="margin-bottom: 12px;"><?= $existing ? 'Update your submission' : 'Your submission' ?></h3>
    <form method="post" action="<?= url('student/submitAssignment/' . $a['id']) ?>" enctype="multipart/form-data">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
      <div class="form-group">
        <label>Written response</label>
        <textarea name="content" rows="8" placeholder="Type or paste your response here..."><?= e($existing['content'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label>File attachment (optional)</label>
        <input type="file" name="file">
        <span class="hint">Max size: <?= humanFileSize(MAX_UPLOAD_SIZE) ?>. Allowed: <?= implode(', ', array_keys(ALLOWED_UPLOAD_TYPES)) ?></span>
        <?php if (!empty($existing['file_path'])): ?>
          <div class="file-attachment" style="margin-top:8px;">
            <div class="file-icon"><span class="material-icons"><?= fileIcon(pathinfo($existing['file_name'], PATHINFO_EXTENSION)) ?></span></div>
            <div class="file-meta">
              <strong>Current file: <?= e($existing['file_name']) ?></strong>
              <span class="size">Upload a new file to replace it.</span>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <button type="submit" class="btn btn-primary btn-lg">
        <span class="material-icons">send</span> <?= $existing ? 'Update submission' : 'Submit assignment' ?>
      </button>
      <a href="<?= url('course/view/' . $course['id']) ?>" class="btn btn-ghost">Cancel</a>
    </form>
  </div>

  <aside>
    <?php if ($existing): ?>
      <div class="card card-pad">
        <h3 style="margin-bottom: 12px;">Submission status</h3>
        <?php if ($existing['status'] === 'graded'): ?>
          <span class="badge badge-success" style="font-size:.85rem;">Graded</span>
          <div style="margin-top:14px;font-size:1.4rem;font-weight:800;color:var(--primary);">
            <?= $existing['score'] ?> / <?= $a['max_score'] ?>
          </div>
          <?php if (!empty($existing['feedback'])): ?>
            <div style="margin-top:14px;padding:12px;background:var(--surface-2);border-radius:var(--radius-sm);font-size:.88rem;line-height:1.6;">
              <strong>Feedback:</strong><br>
              <?= nl2br(e($existing['feedback'])) ?>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <span class="badge badge-warning" style="font-size:.85rem;">Awaiting grade</span>
          <p class="text-muted" style="margin-top:10px;font-size:.85rem;">Your submission is in the queue. You'll be notified when it's graded.</p>
        <?php endif; ?>
        <p class="text-muted" style="font-size:.78rem;margin-top:14px;">Submitted <?= timeAgo($existing['submitted_at']) ?></p>
      </div>
    <?php endif; ?>
    <div class="card card-pad">
      <h4 style="margin-bottom: 10px;">Tips</h4>
      <ul style="font-size:.85rem;line-height:1.7;color:var(--text-muted);padding-left:18px;">
        <li>You can update your submission any time before grading.</li>
        <li>Both written response and file attachment can be submitted together.</li>
        <li>Make sure to proofread before submitting.</li>
      </ul>
    </div>
  </aside>
</div>
