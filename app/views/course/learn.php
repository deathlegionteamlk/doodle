<?php /** @var array $data */
extract($data, EXTR_SKIP);
$c = $course;
$lesson = $currentLesson;
?>
<div class="breadcrumb">
  <a href="<?= url('course/view/' . $c['id']) ?>"><?= e($c['title']) ?></a>
  <span class="material-icons">chevron_right</span>
  <span><?= $lesson ? e($lesson['title']) : 'Curriculum' ?></span>
</div>

<div class="grid" style="grid-template-columns: 320px 1fr; gap: 24px; align-items: flex-start;">
  <!-- Sidebar: module navigation -->
  <aside>
    <div class="card" style="position:sticky;top:84px;">
      <div class="card-head">
        <h3>Curriculum</h3>
      </div>
      <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
        <div class="flex-between mb-1">
          <span class="text-muted" style="font-size:.8rem;">Progress</span>
          <strong style="font-size:.85rem;"><?= $progress ?>%</strong>
        </div>
        <div class="progress"><div class="progress-bar" style="width:<?= $progress ?>%;"></div></div>
        <small class="text-muted" style="font-size:.75rem;"><?= $completedCount ?>/<?= count($allLessons) ?> lessons done</small>
      </div>
      <div style="max-height:520px;overflow-y:auto;">
        <?php foreach ($modules as $m): ?>
          <div class="module-block" style="margin:0;border:0;border-radius:0;border-bottom:1px solid var(--border);">
            <div style="padding:10px 16px;background:var(--surface-2);font-weight:700;font-size:.85rem;">
              <?= e($m['title']) ?>
              <span class="text-muted" style="font-weight:400;font-size:.72rem;float:right;"><?= count($m['lessons']) ?> lessons</span>
            </div>
            <?php foreach ($m['lessons'] as $l): ?>
              <a href="<?= url('course/learn/' . $c['id'] . '/' . $l['id']) ?>" class="lesson-item" style="text-decoration:none;color:inherit;">
                <span class="material-icons icon <?= !empty($l['is_completed']) ? 'done' : '' ?>"><?= !empty($l['is_completed']) ? 'check_circle' : ($l['content_type'] === 'video' ? 'play_circle' : ($l['content_type'] === 'file' ? 'attachment' : 'article')) ?></span>
                <span class="title" style="font-size:.85rem;"><?= e($l['title']) ?></span>
                <?php if ($lesson && $l['id'] == $lesson['id']): ?>
                  <span class="badge badge-primary">Now</span>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </aside>

  <!-- Lesson content -->
  <div>
    <?php if (!$lesson): ?>
      <div class="card card-pad empty">
        <span class="material-icons">menu_book</span>
        <h3>No lessons yet</h3>
        <p>This course doesn't have any published content yet.</p>
      </div>
    <?php else: ?>
      <div class="lesson-content">
        <div class="flex-between mb-2">
          <h1 style="margin:0;font-size:1.5rem;"><?= e($lesson['title']) ?></h1>
          <?php if (Auth::isStudent() && $lesson['content_type'] === 'video'): ?>
            <button class="btn btn-success btn-sm" id="markDoneBtn"
                    onclick="markDone(<?= $lesson['id'] ?>)">
              <span class="material-icons">check_circle</span> Mark complete
            </button>
          <?php elseif (!empty($lesson['is_completed'])): ?>
            <span class="badge badge-success"><span class="material-icons" style="font-size:14px;vertical-align:middle;">check</span> Completed</span>
          <?php endif; ?>
        </div>

        <?php if ($lesson['content_type'] === 'video'): ?>
          <?php if (!empty($lesson['external_url'])): ?>
            <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:var(--radius-sm);margin-bottom:18px;">
              <iframe src="<?= e($lesson['external_url']) ?>" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen></iframe>
            </div>
          <?php elseif (!empty($lesson['file_path'])): ?>
            <video controls style="width:100%;border-radius:var(--radius-sm);margin-bottom:18px;">
              <source src="<?= uploadUrl($lesson['file_path']) ?>" type="video/mp4">
              Your browser does not support the video tag.
            </video>
          <?php endif; ?>
        <?php elseif ($lesson['content_type'] === 'file' && !empty($lesson['file_path'])): ?>
          <a href="<?= uploadUrl($lesson['file_path']) ?>" download class="file-attachment" style="text-decoration:none;color:inherit;">
            <div class="file-icon"><span class="material-icons"><?= fileIcon(pathinfo($lesson['file_name'], PATHINFO_EXTENSION)) ?></span></div>
            <div class="file-meta">
              <strong><?= e($lesson['file_name']) ?></strong>
              <span class="size"><?= humanFileSize((int) $lesson['file_size']) ?> · Click to download</span>
            </div>
            <span class="material-icons">download</span>
          </a>
        <?php elseif ($lesson['content_type'] === 'url' && !empty($lesson['external_url'])): ?>
          <p><a href="<?= e($lesson['external_url']) ?>" target="_blank" rel="noopener" class="btn btn-secondary"><span class="material-icons">open_in_new</span> Open external resource</a></p>
        <?php endif; ?>

        <?php if (!empty($lesson['content'])): ?>
          <div class="lesson-text"><?= $lesson['content'] /* already HTML */ ?></div>
        <?php endif; ?>
      </div>

      <div class="flex-between mt-2">
        <?php if ($prev): ?>
          <a href="<?= url('course/learn/' . $c['id'] . '/' . $prev['id']) ?>" class="btn btn-secondary">
            <span class="material-icons">arrow_back</span> Previous
          </a>
        <?php else: ?>
          <span></span>
        <?php endif; ?>
        <?php if ($next): ?>
          <a href="<?= url('course/learn/' . $c['id'] . '/' . $next['id']) ?>" class="btn btn-primary">
            Next lesson <span class="material-icons">arrow_forward</span>
          </a>
        <?php elseif (Auth::isStudent()): ?>
          <span class="badge badge-success" style="padding:8px 14px;font-size:.85rem;">
            <span class="material-icons" style="font-size:16px;vertical-align:middle;">emoji_events</span> Course complete!
          </span>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
function markDone(lessonId) {
  fetch('<?= url('course/completeLesson/') ?>' + lessonId, { method: 'POST', credentials: 'same-origin' })
    .then(r => r.json())
    .then(d => {
      if (d.ok) {
        const btn = document.getElementById('markDoneBtn');
        btn.innerHTML = '<span class="material-icons">check_circle</span> Completed';
        btn.disabled = true;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-secondary');
      }
    });
}
</script>
