<?php /** @var array $data */
extract($data, EXTR_SKIP);
$p = $path;
?>
<div class="breadcrumb">
  <a href="<?= url('learning_paths') ?>">Learning Paths</a>
  <span class="material-icons">chevron_right</span>
  <span><?= e($p['title']) ?></span>
</div>

<div class="course-hero" style="background:linear-gradient(135deg,#1E1B4B,#7C3AED);">
  <div>
    <h1><?= e($p['title']) ?></h1>
    <p class="desc"><?= e($p['description']) ?></p>
    <div class="meta-row">
      <span class="item"><span class="material-icons" style="font-size:18px;">school</span> <?= count($courses) ?> courses</span>
      <span class="item"><span class="material-icons" style="font-size:18px;">group</span> <?= $enrollCount ?> enrolled</span>
      <?php if ($p['estimated_hours']): ?>
        <span class="item"><span class="material-icons" style="font-size:18px;">schedule</span> <?= $p['estimated_hours'] ?> hours</span>
      <?php endif; ?>
      <span class="item"><span class="material-icons" style="font-size:18px;">person</span> by <?= e($p['creator_name']) ?></span>
    </div>
  </div>
  <div class="enroll-box">
    <?php if (Auth::isStudent()): ?>
      <?php if ($enrolled): ?>
        <p style="color:#fff;font-size:.85rem;margin-bottom:14px;">You're enrolled in this path!</p>
        <a href="<?= url('course/view/' . ($courses[0]['id'] ?? '')) ?>" class="btn btn-primary btn-block btn-lg">Continue learning</a>
      <?php else: ?>
        <p style="color:#fff;font-size:.85rem;margin-bottom:14px;">Enroll to start this learning path.</p>
        <a href="<?= url('learning_paths/enroll/' . $p['id']) ?>" class="btn btn-primary btn-block btn-lg"><span class="material-icons">school</span> Enroll free</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-head"><h3>Path curriculum</h3></div>
  <div>
    <?php if (empty($courses)): ?>
      <div class="empty" style="padding:30px;">
        <span class="material-icons">route</span>
        <p>No courses in this path yet.</p>
      </div>
    <?php else: foreach ($courses as $i => $c): ?>
      <div class="path-step">
        <div class="path-step-num <?= $c['progress'] >= 100 ? 'done' : (isset($c['enroll_status']) ? 'active' : '') ?>">
          <?php if ($c['progress'] >= 100): ?>
            <span class="material-icons">check</span>
          <?php else: ?>
            <?= $i + 1 ?>
          <?php endif; ?>
        </div>
        <div class="path-step-body">
          <div class="flex-between">
            <div>
              <h3 style="font-size:1.05rem;margin-bottom:4px;">
                <a href="<?= url('course/view/' . $c['id']) ?>" style="color:inherit;text-decoration:none;"><?= e($c['title']) ?></a>
              </h3>
              <p class="text-muted" style="font-size:.82rem;">by <?= e($c['teacher_name']) ?> · <?= ucfirst($c['level']) ?></p>
            </div>
            <div class="flex gap-sm">
              <?php if (isset($c['enroll_status']) && $c['enroll_status'] === 'active'): ?>
                <span class="badge badge-info"><?= (int) $c['progress'] ?>%</span>
                <a href="<?= url('course/learn/' . $c['id']) ?>" class="btn btn-primary btn-sm">Continue</a>
              <?php else: ?>
                <a href="<?= url('course/view/' . $c['id']) ?>" class="btn btn-secondary btn-sm">View</a>
              <?php endif; ?>
            </div>
          </div>
          <?php if (isset($c['enroll_status']) && $c['enroll_status'] === 'active'): ?>
            <div class="progress" style="margin-top:8px;height:6px;"><div class="progress-bar" style="width:<?= $c['progress'] ?>%;"></div></div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<?php if ($p['creator_id'] == Auth::id() || Auth::isAdmin()): ?>
  <p class="mt-3">
    <a href="<?= url('learning_paths/delete/' . $p['id']) ?>" class="btn btn-danger" data-confirm="Delete this learning path? Students enrolled will lose access."><span class="material-icons">delete</span> Delete path</a>
  </p>
<?php endif; ?>

<style>
.path-step { display: flex; gap: 18px; padding: 20px 22px; border-bottom: 1px solid var(--border); position: relative; }
.path-step:last-child { border-bottom: 0; }
.path-step-num {
  width: 40px; height: 40px; border-radius: 50%;
  background: var(--surface-2); color: var(--text-muted);
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-family: var(--font-display); flex-shrink: 0;
  border: 2px solid var(--border);
}
.path-step-num.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.path-step-num.done { background: var(--success); color: #fff; border-color: var(--success); }
.path-step-body { flex: 1; }
</style>
