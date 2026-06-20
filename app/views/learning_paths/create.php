<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="breadcrumb">
  <a href="<?= url('learning_paths') ?>">Learning Paths</a>
  <span class="material-icons">chevron_right</span>
  <span>Create</span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1>Create Learning Path</h1>
    <p>Combine multiple courses into a structured curriculum.</p>
  </div>
</div>

<div class="card card-pad">
  <form method="post" action="<?= url('learning_paths/create') ?>">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
    <div class="form-grid">
      <div class="form-group">
        <label>Path title</label>
        <input type="text" name="title" required placeholder="e.g., Full Stack Web Developer">
      </div>
      <div class="form-group">
        <label>Estimated hours (optional)</label>
        <input type="number" name="estimated_hours" min="1" placeholder="e.g., 120">
      </div>
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea name="description" rows="4" placeholder="What will students achieve by completing this path?"></textarea>
    </div>

    <h3 style="margin: 24px 0 12px;">Courses in this path (in order)</h3>
    <p class="text-muted" style="font-size:.85rem;margin-bottom:14px;">Select courses in the order students should complete them.</p>
    <div style="max-height:400px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px;">
      <?php if (empty($courses)): ?>
        <p class="text-muted" style="padding:14px;text-align:center;">No published courses available. Publish a course first.</p>
      <?php else: foreach ($courses as $i => $c): ?>
        <label class="checkbox-group" style="padding:10px 0;display:flex;width:100%;border-bottom:1px solid var(--surface-2);">
          <input type="checkbox" name="courses[]" value="<?= $c['id'] ?>" style="margin-right:14px;">
          <div style="flex:1;">
            <strong style="font-size:.92rem;"><?= e($c['title']) ?></strong>
          </div>
        </label>
      <?php endforeach; endif; ?>
    </div>

    <hr style="border:0;border-top:1px solid var(--border);margin:24px 0;">
    <button type="submit" class="btn btn-primary btn-lg"><span class="material-icons">route</span> Create learning path</button>
    <a href="<?= url('learning_paths') ?>" class="btn btn-ghost">Cancel</a>
  </form>
</div>
