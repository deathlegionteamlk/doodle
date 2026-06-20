<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>Categories</h1>
    <p>Organize the course catalog by topic.</p>
  </div>
</div>

<div class="grid" style="grid-template-columns: 1fr 320px; gap: 24px; align-items: flex-start;">
  <div class="card">
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr><th>Name</th><th>Slug</th><th>Courses</th><th>Created</th><th class="text-right">Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($categories)): ?>
            <tr><td colspan="5" class="text-center text-muted" style="padding:30px;">No categories yet.</td></tr>
          <?php else: foreach ($categories as $c): ?>
            <tr>
              <td><strong><?= e($c['name']) ?></strong>
                <?php if (!empty($c['description'])): ?>
                  <div class="text-muted" style="font-size:.78rem;"><?= e($c['description']) ?></div>
                <?php endif; ?>
              </td>
              <td><code style="font-size:.8rem;background:var(--surface-2);padding:2px 6px;border-radius:4px;"><?= e($c['slug']) ?></code></td>
              <td><?= (int) $c['course_count'] ?></td>
              <td class="text-muted" style="font-size:.82rem;"><?= formatDate($c['created_at']) ?></td>
              <td class="text-right">
                <a href="<?= url('catalog?cat=' . $c['id']) ?>" class="btn btn-secondary btn-sm"><span class="material-icons">visibility</span></a>
                <a href="<?= url('admin/deleteCategory/' . $c['id']) ?>" class="btn btn-danger btn-sm" data-confirm="Delete category? Courses will be uncategorized, not deleted.">
                  <span class="material-icons">delete</span>
                </a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <aside class="card card-pad">
    <h3 style="margin-bottom: 14px;">Add new category</h3>
    <form method="post" action="<?= url('admin/categories') ?>">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
      <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" required>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="3"></textarea>
      </div>
      <div class="form-group">
        <label>Parent category (optional)</label>
        <select name="parent_id">
          <option value="0">— None (top level) —</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Create category</button>
    </form>
  </aside>
</div>
