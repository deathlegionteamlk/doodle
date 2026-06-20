<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>My Learning</h1>
    <p>Welcome back, <?= e(Auth::user()['full_name']) ?>. Keep up the great work!</p>
  </div>
  <div class="actions">
    <a href="<?= url('catalog') ?>" class="btn btn-primary"><span class="material-icons">explore</span> Browse catalog</a>
  </div>
</div>

<div class="grid grid-4 mb-3">
  <div class="stat primary">
    <div><div class="stat-label">Enrolled Courses</div><div class="stat-value"><?= $stats['enrolled'] ?></div><div class="stat-delta"><?= $stats['inProgress'] ?> in progress</div></div>
    <div class="stat-icon"><span class="material-icons">school</span></div>
  </div>
  <div class="stat success">
    <div><div class="stat-label">Completed</div><div class="stat-value"><?= $stats['completed'] ?></div><div class="stat-delta">courses finished</div></div>
    <div class="stat-icon"><span class="material-icons">emoji_events</span></div>
  </div>
  <div class="stat info">
    <div><div class="stat-label">Quizzes Taken</div><div class="stat-value"><?= $stats['quizzes'] ?></div><div class="stat-delta">attempts total</div></div>
    <div class="stat-icon"><span class="material-icons">quiz</span></div>
  </div>
  <div class="stat accent">
    <div><div class="stat-label">Average Score</div><div class="stat-value"><?= round($stats['avgScore'], 1) ?>%</div><div class="stat-delta">across all quizzes</div></div>
    <div class="stat-icon"><span class="material-icons">trending_up</span></div>
  </div>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px; align-items: flex-start;">
  <div>
    <h3 style="margin-bottom:14px;">My Courses</h3>
    <?php if (empty($enrollments)): ?>
      <div class="card card-pad empty">
        <span class="material-icons">school</span>
        <h3>You haven't enrolled in any courses yet</h3>
        <p>Browse the catalog and find your first course to start learning.</p>
        <a href="<?= url('catalog') ?>" class="btn btn-primary"><span class="material-icons">explore</span> Browse catalog</a>
      </div>
    <?php else: ?>
      <div class="grid grid-2">
        <?php foreach ($enrollments as $e): ?>
          <div class="course-card">
            <a href="<?= url('course/learn/' . $e['course_id']) ?>" class="course-cover">
              <?php if (!empty($e['cover_image'])): ?>
                <img src="<?= uploadUrl($e['cover_image']) ?>" alt="">
              <?php else: ?>
                <?= strtoupper(substr($e['title'], 0, 1)) ?>
              <?php endif; ?>
            </a>
            <div class="course-body">
              <h3 style="font-size:1rem;"><a href="<?= url('course/learn/' . $e['course_id']) ?>"><?= e($e['title']) ?></a></h3>
              <p class="desc" style="font-size:.82rem;">by <?= e($e['teacher_name']) ?></p>
              <div class="flex-between mb-1" style="font-size:.78rem;">
                <span class="text-muted"><?= $e['progress'] ?>% complete</span>
                <span class="text-muted"><?= $e['completed'] ?>/<?= $e['lesson_count'] ?> lessons</span>
              </div>
              <div class="progress" style="margin-bottom:8px;"><div class="progress-bar" style="width:<?= $e['progress'] ?>%;"></div></div>
              <a href="<?= url('course/learn/' . $e['course_id']) ?>" class="btn btn-primary btn-sm btn-block">
                <?= $e['progress'] > 0 ? 'Continue' : 'Start learning' ?>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <aside>
    <h3 style="margin-bottom:14px;">Upcoming deadlines</h3>
    <div class="card mb-3">
      <?php if (empty($upcoming)): ?>
        <div class="empty" style="padding:24px;">
          <span class="material-icons" style="font-size:36px;">event_available</span>
          <p style="font-size:.85rem;">No upcoming deadlines.</p>
        </div>
      <?php else: ?>
        <?php foreach ($upcoming as $a): ?>
          <a href="<?= url('student/submitAssignment/' . $a['id']) ?>" style="display:block;padding:14px 18px;border-bottom:1px solid var(--border);text-decoration:none;color:inherit;">
            <strong style="font-size:.9rem;"><?= e($a['title']) ?></strong>
            <div class="text-muted" style="font-size:.78rem;"><?= e($a['course_title']) ?></div>
            <div class="text-muted" style="font-size:.72rem;margin-top:4px;">
              <span class="material-icons" style="font-size:12px;vertical-align:middle;">schedule</span>
              Due <?= formatDateTime($a['due_date']) ?>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="card card-pad" style="background:linear-gradient(135deg,var(--primary-light),#fff);border-color:var(--primary);">
      <h3 style="margin-bottom:8px;display:flex;align-items:center;gap:6px;">
        <span class="material-icons" style="color:var(--primary);">smart_toy</span> AI Assistant
      </h3>
      <p class="text-muted" style="font-size:.82rem;margin-bottom:10px;">Ask questions, summarize lessons, generate quizzes & flashcards.</p>
      <a href="<?= url('ai') ?>" class="btn btn-primary btn-block btn-sm">Open AI Assistant</a>
    </div>
  </aside>
</div>
