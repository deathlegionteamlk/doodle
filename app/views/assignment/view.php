<?php /** @var array $data */
extract($data, EXTR_SKIP);
$a = $assignment;
?>
<div class="breadcrumb">
  <a href="<?= url('course/view/' . $a['course_id']) ?>"><?= e($a['course_title']) ?></a>
  <span class="material-icons">chevron_right</span>
  <span><?= e($a['title']) ?></span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1><?= e($a['title']) ?></h1>
    <p>
      <span class="badge"><?= e($a['course_title']) ?></span>
      <span class="badge badge-info"><?= $a['max_score'] ?> pts</span>
      <?php if ($a['due_date']): ?>
        <span class="badge badge-warning">Due <?= formatDateTime($a['due_date']) ?></span>
      <?php endif; ?>
    </p>
  </div>
  <div class="actions">
    <?php if (Auth::isStudent()): ?>
      <a href="<?= url('student/submitAssignment/' . $a['id']) ?>" class="btn btn-primary">
        <span class="material-icons">edit</span>
        <?= $submission ? 'Update submission' : 'Submit work' ?>
      </a>
    <?php elseif ($isOwner): ?>
      <a href="<?= url('teacher/assignment/' . $a['id']) ?>" class="btn btn-secondary">
        <span class="material-icons">visibility</span> View submissions
      </a>
    <?php endif; ?>
  </div>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px; align-items: flex-start;">
  <div class="card card-pad">
    <h3 style="margin-bottom: 12px;">Instructions</h3>
    <div style="line-height:1.7;"><?= nl2br(e($a['description'] ?? 'No instructions provided.')) ?></div>
  </div>

  <aside>
    <?php if (Auth::isStudent() && $submission): ?>
      <div class="card card-pad">
        <h3 style="margin-bottom: 12px;">Your submission</h3>
        <?php if ($submission['status'] === 'graded'): ?>
          <div style="text-align:center;padding:14px 0;">
            <div style="font-size:2.4rem;font-weight:800;color:var(--primary);">
              <?= $submission['score'] ?><span style="font-size:1.2rem;color:var(--text-muted);">/<?= $a['max_score'] ?></span>
            </div>
            <span class="badge badge-success" style="margin-top:6px;">Graded</span>
          </div>
          <?php if (!empty($submission['feedback'])): ?>
            <div style="padding:12px;background:var(--surface-2);border-radius:var(--radius-sm);font-size:.88rem;line-height:1.6;margin-top:10px;">
              <strong>Feedback:</strong><br>
              <?= nl2br(e($submission['feedback'])) ?>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <span class="badge badge-warning">Awaiting grade</span>
          <p class="text-muted" style="margin-top:10px;font-size:.85rem;">Submitted <?= timeAgo($submission['submitted_at']) ?></p>
        <?php endif; ?>
        <a href="<?= url('student/submitAssignment/' . $a['id']) ?>" class="btn btn-secondary btn-block" style="margin-top:14px;">View / edit submission</a>
      </div>
    <?php elseif (Auth::isStudent()): ?>
      <div class="card card-pad">
        <h3 style="margin-bottom: 12px;">Status</h3>
        <span class="badge badge-info">Not submitted</span>
        <p class="text-muted" style="margin-top:10px;font-size:.85rem;">You haven't submitted this assignment yet.</p>
        <a href="<?= url('student/submitAssignment/' . $a['id']) ?>" class="btn btn-primary btn-block" style="margin-top:14px;">Submit now</a>
      </div>
    <?php endif; ?>
  </aside>
</div>
