<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block"><h1>My Notes</h1><p>Private notes you've taken on lessons.</p></div>
</div>
<?php if (empty($notes)): ?>
  <div class="card card-pad empty"><span class="material-icons">sticky_note_2</span><h3>No notes yet</h3><p>Open any lesson to take notes while you study.</p></div>
<?php else: ?>
  <div class="grid grid-2">
    <?php foreach ($notes as $n): ?>
      <div class="card card-pad">
        <div class="flex-between mb-1">
          <a href="<?= url('course/learn/' . $n['course_id'] . '/' . $n['lesson_id']) ?>"><strong><?= e($n['lesson_title']) ?></strong></a>
          <span class="text-muted" style="font-size:.72rem;"><?= timeAgo($n['updated_at'] ?: $n['created_at']) ?></span>
        </div>
        <p class="text-muted" style="font-size:.82rem;margin-bottom:8px;"><?= e($n['course_title']) ?></p>
        <div style="font-size:.9rem;line-height:1.6;background:var(--surface-2);padding:10px;border-radius:6px;"><?= nl2br(e(truncate($n['content'], 300))) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
