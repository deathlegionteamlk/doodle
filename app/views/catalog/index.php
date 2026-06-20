<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>Course Catalog</h1>
    <p><?= number_format($total) ?> courses available — explore and enroll.</p>
  </div>
</div>

<div class="grid" style="grid-template-columns: 260px 1fr; gap: 24px; align-items: flex-start;">
  <aside class="card card-pad">
    <h3 style="margin-bottom: 16px;">Filters</h3>
    <form method="get" action="<?= url('catalog') ?>">
      <input type="hidden" name="r" value="catalog">
      <div class="form-group">
        <label>Search</label>
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Keywords...">
      </div>
      <div class="form-group">
        <label>Category</label>
        <select name="cat">
          <option value="0">All categories</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $categoryId == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Level</label>
        <select name="level">
          <option value="">All levels</option>
          <option value="beginner"     <?= $level === 'beginner'     ? 'selected' : '' ?>>Beginner</option>
          <option value="intermediate" <?= $level === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
          <option value="advanced"     <?= $level === 'advanced'     ? 'selected' : '' ?>>Advanced</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Apply</button>
      <a href="<?= url('catalog') ?>" class="btn btn-ghost btn-block" style="margin-top:6px;">Reset</a>
    </form>
  </aside>

  <div>
    <?php if (empty($courses)): ?>
      <div class="card card-pad empty">
        <span class="material-icons">search_off</span>
        <h3>No courses match your filters</h3>
        <p>Try adjusting your search terms or browse all courses.</p>
        <a href="<?= url('catalog') ?>" class="btn btn-primary">Reset filters</a>
      </div>
    <?php else: ?>
      <div class="grid grid-auto">
        <?php foreach ($courses as $c): ?>
          <div class="course-card">
            <a href="<?= url('course/view/' . $c['id']) ?>" class="course-cover">
              <?php if (!empty($c['cover_image'])): ?>
                <img src="<?= uploadUrl($c['cover_image']) ?>" alt="">
              <?php else: ?>
                <?= strtoupper(substr($c['title'], 0, 1)) ?>
              <?php endif; ?>
              <span class="level-pill"><?= e($c['level']) ?></span>
            </a>
            <div class="course-body">
              <?php if (!empty($c['category_name'])): ?>
                <span class="category"><?= e($c['category_name']) ?></span>
              <?php endif; ?>
              <h3><a href="<?= url('course/view/' . $c['id']) ?>"><?= e($c['title']) ?></a></h3>
              <p class="desc"><?= e(truncate($c['description'] ?? '', 110)) ?></p>
              <div class="meta">
                <span class="teacher">
                  <span class="avatar sm" style="background:<?= avatarColor($c['teacher_name']) ?>"><?= initials($c['teacher_name']) ?></span>
                  <?= e($c['teacher_name']) ?>
                </span>
                <span><span class="material-icons" style="font-size:14px;vertical-align:middle;">group</span> <?= (int) $c['enroll_count'] ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?= $pagination ?>
    <?php endif; ?>
  </div>
</div>
