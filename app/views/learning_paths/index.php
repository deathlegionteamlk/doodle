<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>Learning Paths</h1>
    <p>Sequences of courses designed to take you from beginner to expert.</p>
  </div>
  <?php if (Auth::isTeacher() || Auth::isAdmin()): ?>
    <div class="actions">
      <a href="<?= url('learning_paths/create') ?>" class="btn btn-primary"><span class="material-icons">add</span> Create path</a>
    </div>
  <?php endif; ?>
</div>

<?php if (empty($paths)): ?>
  <div class="card card-pad empty">
    <span class="material-icons">route</span>
    <h3>No learning paths yet</h3>
    <p>Learning paths combine multiple courses into a structured curriculum. Teachers and admins can create them.</p>
    <?php if (Auth::isTeacher() || Auth::isAdmin()): ?>
      <a href="<?= url('learning_paths/create') ?>" class="btn btn-primary">Create the first path</a>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="grid grid-2">
    <?php foreach ($paths as $p): ?>
      <a href="<?= url('learning_paths/view/' . $p['id']) ?>" class="card card-pad" style="text-decoration:none;color:inherit;">
        <div class="flex-between mb-1">
          <h3 style="font-size:1.1rem;"><?= e($p['title']) ?></h3>
          <?php if (!$p['is_published']): ?><span class="badge badge-warning">Draft</span><?php endif; ?>
        </div>
        <p class="text-muted" style="font-size:.88rem;line-height:1.6;"><?= e(truncate($p['description'] ?? '', 140)) ?></p>
        <div class="flex gap-lg mt-2" style="font-size:.82rem;color:var(--text-muted);">
          <span><span class="material-icons" style="font-size:14px;vertical-align:middle;">school</span> <?= $p['course_count'] ?> courses</span>
          <span><span class="material-icons" style="font-size:14px;vertical-align:middle;">group</span> <?= $p['enroll_count'] ?> enrolled</span>
          <?php if ($p['estimated_hours']): ?>
            <span><span class="material-icons" style="font-size:14px;vertical-align:middle;">schedule</span> <?= $p['estimated_hours'] ?>h</span>
          <?php endif; ?>
        </div>
        <p class="text-muted" style="font-size:.72rem;margin-top:8px;">Created by <?= e($p['creator_name']) ?> · <?= timeAgo($p['created_at']) ?></p>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
