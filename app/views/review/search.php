<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block"><h1>Search</h1><p>Find courses, lessons, forum topics, and more.</p></div>
</div>

<div class="card card-pad mb-3">
  <form method="get" action="<?= url('review/search') ?>" style="display:flex;gap:10px;">
    <input type="hidden" name="r" value="review/search">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search doodle..." autofocus style="flex:1;font-size:1.05rem;padding:14px 20px;border-radius:30px;">
    <button class="btn btn-primary btn-lg" type="submit"><span class="material-icons">search</span> Search</button>
  </form>
</div>

<?php if ($q === ''): ?>
  <div class="card card-pad empty"><span class="material-icons">search</span><h3>Start typing to search</h3><p>Search across courses, lessons, and forum topics.</p></div>
<?php elseif ($total === 0): ?>
  <div class="card card-pad empty"><span class="material-icons">search_off</span><h3>No results for "<?= e($q) ?>"</h3><p>Try different keywords or check your spelling.</p></div>
<?php else: ?>
  <p class="text-muted mb-2">Found <?= $total ?> result(s) for "<?= e($q) ?>"</p>

  <?php if (!empty($results['courses'])): ?>
    <h3 style="margin-bottom:10px;"><span class="material-icons" style="vertical-align:middle;">school</span> Courses (<?= count($results['courses']) ?>)</h3>
    <div class="card mb-3">
      <?php foreach ($results['courses'] as $c): ?>
        <a href="<?= url('course/view/' . $c['id']) ?>" class="forum-topic" style="text-decoration:none;color:inherit;">
          <div class="topic-icon"><span class="material-icons">school</span></div>
          <div class="topic-body"><div class="topic-title"><?= e($c['title']) ?></div><div class="topic-meta"><?= e(truncate($c['description'] ?? '', 120)) ?> · <?= ucfirst($c['level']) ?> · <?= $c['enroll_count'] ?> enrolled</div></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($results['lessons'])): ?>
    <h3 style="margin-bottom:10px;"><span class="material-icons" style="vertical-align:middle;">article</span> Lessons (<?= count($results['lessons']) ?>)</h3>
    <div class="card mb-3">
      <?php foreach ($results['lessons'] as $l): ?>
        <a href="<?= url('course/learn/' . $l['course_id'] . '/' . $l['id']) ?>" class="forum-topic" style="text-decoration:none;color:inherit;">
          <div class="topic-icon"><span class="material-icons">article</span></div>
          <div class="topic-body"><div class="topic-title"><?= e($l['title']) ?></div><div class="topic-meta">Lesson in <?= e($l['course_title']) ?></div></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($results['forum'])): ?>
    <h3 style="margin-bottom:10px;"><span class="material-icons" style="vertical-align:middle;">forum</span> Forum topics (<?= count($results['forum']) ?>)</h3>
    <div class="card mb-3">
      <?php foreach ($results['forum'] as $t): ?>
        <a href="<?= url('forum/topic/' . $t['id']) ?>" class="forum-topic" style="text-decoration:none;color:inherit;">
          <div class="topic-icon"><span class="material-icons">forum</span></div>
          <div class="topic-body"><div class="topic-title"><?= e($t['title']) ?></div><div class="topic-meta">Topic in <?= e($t['course_title']) ?></div></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($results['users'])): ?>
    <h3 style="margin-bottom:10px;"><span class="material-icons" style="vertical-align:middle;">group</span> Users (<?= count($results['users']) ?>)</h3>
    <div class="card mb-3">
      <?php foreach ($results['users'] as $u): ?>
        <div class="forum-topic">
          <div class="topic-icon"><span class="material-icons">person</span></div>
          <div class="topic-body"><div class="topic-title"><?= e($u['full_name']) ?> (<?= e($u['username']) ?>)</div><div class="topic-meta"><span class="badge role-badge <?= e($u['role']) ?>"><?= ucfirst($u['role']) ?></span></div></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
