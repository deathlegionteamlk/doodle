<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="breadcrumb">
  <a href="<?= url('course/view/' . $course['id']) ?>"><?= e($course['title']) ?></a>
  <span class="material-icons">chevron_right</span>
  <span>Whiteboards</span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1>Whiteboards</h1>
    <p><?= e($course['title']) ?> · collaborative drawing</p>
  </div>
  <div class="actions">
    <form method="post" action="<?= url('whiteboard/create/' . $course['id']) ?>" style="display:inline-flex;gap:8px;">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
      <input type="text" name="name" placeholder="Board name" required style="width:200px;">
      <button class="btn btn-primary"><span class="material-icons">add</span> New</button>
    </form>
  </div>
</div>

<?php if (empty($boards)): ?>
  <div class="card card-pad empty">
    <span class="material-icons">draw</span>
    <h3>No whiteboards yet</h3>
    <p>Whiteboards let students and teachers draw together in near-real-time. Great for math problems, diagrams, and brainstorming.</p>
  </div>
<?php else: ?>
  <div class="grid grid-3">
    <?php foreach ($boards as $b): ?>
      <a href="<?= url('whiteboard/view/' . $b['id']) ?>" class="card card-pad" style="text-decoration:none;color:inherit;">
        <div class="flex-between mb-1">
          <h3 style="font-size:1.05rem;"><?= e($b['name']) ?></h3>
          <span class="material-icons" style="color:var(--text-muted);">draw</span>
        </div>
        <p class="text-muted" style="font-size:.82rem;">by <?= e($b['creator_name']) ?> · <?= $b['stroke_count'] ?> strokes</p>
        <p class="text-muted" style="font-size:.72rem;"><?= timeAgo($b['created_at']) ?></p>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
