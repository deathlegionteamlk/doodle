<?php /** @var array $data */
extract($data, EXTR_SKIP);
$c = $course;
?>
<div class="breadcrumb">
  <a href="<?= url('catalog') ?>">Catalog</a>
  <span class="material-icons">chevron_right</span>
  <span><?= e($c['title']) ?></span>
</div>

<div class="course-hero">
  <div>
    <?php if (!empty($c['category_name'])): ?>
      <span class="badge" style="background:rgba(255,255,255,.15);color:#fff;margin-bottom:12px;"><?= e($c['category_name']) ?></span>
    <?php endif; ?>
    <h1><?= e($c['title']) ?></h1>
    <p class="desc"><?= e(truncate($c['description'] ?? '', 240)) ?></p>
    <div class="meta-row">
      <span class="item"><span class="material-icons" style="font-size:18px;">person</span> <?= e($c['teacher_name']) ?></span>
      <span class="item"><span class="material-icons" style="font-size:18px;">school</span> <?= e(ucfirst($c['level'])) ?></span>
      <span class="item"><span class="material-icons" style="font-size:18px;">group</span> <?= (int) $c['enroll_count'] ?> enrolled</span>
      <span class="item"><span class="material-icons" style="font-size:18px;">menu_book</span> <?= $totalLessons ?> lessons</span>
      <?php if (!empty($c['start_date'])): ?>
        <span class="item"><span class="material-icons" style="font-size:18px;">event</span> <?= formatDate($c['start_date']) ?></span>
      <?php endif; ?>
    </div>
  </div>
  <div class="enroll-box">
    <?php if ($isEnrolled): ?>
      <p style="color:#fff;margin-bottom:14px;font-size:.85rem;">You're enrolled · <?= $progress ?>% complete</p>
      <div class="progress progress-lg" style="background:rgba(255,255,255,.2);margin-bottom:18px;">
        <div class="progress-bar" style="width:<?= $progress ?>%;"></div>
      </div>
      <a href="<?= url('course/learn/' . $c['id']) ?>" class="btn btn-primary btn-block btn-lg">
        <span class="material-icons">play_arrow</span> Continue learning
      </a>
      <a href="<?= url('course/unenroll/' . $c['id']) ?>" class="btn btn-ghost btn-block" style="color:#fff;margin-top:8px;" data-confirm="Are you sure you want to unenroll? Your progress will be lost.">Unenroll</a>
    <?php elseif ($isOwner): ?>
      <p style="color:#fff;font-size:.85rem;margin-bottom:14px;">This is your course.</p>
      <a href="<?= url('teacher/courses/edit/' . $c['id']) ?>" class="btn btn-primary btn-block btn-lg"><span class="material-icons">edit</span> Manage course</a>
      <a href="<?= url('course/learn/' . $c['id']) ?>" class="btn btn-ghost btn-block" style="color:#fff;margin-top:8px;">Preview as student</a>
    <?php elseif (Auth::isStudent()): ?>
      <?php if (!empty($c['enroll_key'])): ?>
        <p style="color:#fff;font-size:.85rem;margin-bottom:14px;">This course requires an enrollment key.</p>
        <form method="post" action="<?= url('course/enroll/' . $c['id']) ?>">
          <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
          <input type="text" name="enroll_key" placeholder="Enter enrollment key" class="form-control" style="margin-bottom:10px;background:rgba(255,255,255,.9);">
          <button class="btn btn-primary btn-block btn-lg"><span class="material-icons">vpn_key</span> Enroll with key</button>
        </form>
      <?php else: ?>
        <p style="color:#fff;font-size:.85rem;margin-bottom:14px;">Free to enroll · start today.</p>
        <form method="post" action="<?= url('course/enroll/' . $c['id']) ?>">
          <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
          <button class="btn btn-primary btn-block btn-lg"><span class="material-icons">school</span> Enroll now</button>
        </form>
      <?php endif; ?>
    <?php elseif (!Auth::check()): ?>
      <p style="color:#fff;font-size:.85rem;margin-bottom:14px;">Create a free student account to enroll.</p>
      <a href="<?= url('auth/register') ?>" class="btn btn-primary btn-block btn-lg">Sign up to enroll</a>
    <?php else: ?>
      <p style="color:#fff;font-size:.85rem;margin-bottom:14px;">Teacher accounts can't enroll as students.</p>
    <?php endif; ?>
  </div>
</div>

<div class="tabs">
  <a href="#" class="active" onclick="return false;">Overview</a>
  <?php if ($isEnrolled || $isOwner): ?>
    <a href="<?= url('course/learn/' . $c['id']) ?>">Curriculum</a>
    <a href="<?= url('forum/course/' . $c['id']) ?>">Forum</a>
    <a href="<?= url('student/grades/' . $c['id']) ?>">Grades</a>
  <?php endif; ?>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px; align-items: flex-start;">
  <div>
    <div class="card card-pad mb-3">
      <h3 style="margin-bottom: 12px;">About this course</h3>
      <div style="line-height:1.7;color:var(--text);">
        <?= nl2br(e($c['description'] ?? 'No description provided.')) ?>
      </div>
    </div>

    <div class="card card-pad mb-3">
      <h3 style="margin-bottom: 14px;">Curriculum</h3>
      <?php if (empty($modules)): ?>
        <p class="text-muted">No modules yet.</p>
      <?php else: ?>
        <?php foreach ($modules as $m): ?>
          <div class="module-block" style="margin-bottom:10px;">
            <div class="module-head" style="cursor:default;">
              <span class="material-icons">folder</span>
              <span class="title"><?= e($m['title']) ?></span>
              <span class="badge"><?= $m['lesson_count'] ?> lessons</span>
            </div>
            <?php
            $lessons = Database::fetchAll("SELECT * FROM lessons WHERE module_id = :mid ORDER BY sort_order, id", ['mid' => $m['id']]);
            foreach ($lessons as $l): ?>
              <div class="lesson-item">
                <span class="material-icons icon"><?= $l['content_type'] === 'video' ? 'play_circle' : ($l['content_type'] === 'file' ? 'attachment' : 'article') ?></span>
                <span class="title"><?= e($l['title']) ?></span>
                <?php if ($l['is_preview']): ?><span class="preview">Free preview</span><?php endif; ?>
                <?php if ($isEnrolled || $isOwner || $l['is_preview']): ?>
                  <a href="<?= url('course/learn/' . $c['id'] . '/' . $l['id']) ?>" class="btn btn-ghost btn-sm">View</a>
                <?php else: ?>
                  <span class="material-icons" style="font-size:18px;color:var(--text-light);">lock</span>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if (!empty($announcements) && ($isEnrolled || $isOwner)): ?>
      <div class="card card-pad">
        <h3 style="margin-bottom: 14px;">Announcements</h3>
        <?php foreach ($announcements as $a): ?>
          <div style="padding:12px 0;border-bottom:1px solid var(--border);">
            <strong><?= e($a['title']) ?></strong>
            <p class="text-muted" style="font-size:.85rem;margin-top:4px;"><?= e(truncate($a['body'], 160)) ?></p>
            <small class="text-muted"><?= e($a['author_name']) ?> · <?= timeAgo($a['created_at']) ?></small>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <aside>
    <div class="card card-pad mb-3">
      <h3 style="margin-bottom: 14px;">Instructor</h3>
      <div class="flex gap-sm" style="margin-bottom:10px;">
        <span class="avatar lg" style="background:<?= avatarColor($c['teacher_name']) ?>;">
          <?php if (!empty($c['teacher_avatar'])): ?><img src="<?= uploadUrl($c['teacher_avatar']) ?>" alt=""><?php else: ?><?= initials($c['teacher_name']) ?><?php endif; ?>
        </span>
        <div>
          <strong style="display:block;font-size:1rem;"><?= e($c['teacher_name']) ?></strong>
          <span class="badge role-badge teacher">Teacher</span>
        </div>
      </div>
      <p class="text-muted" style="font-size:.88rem;line-height:1.6;"><?= e($c['teacher_bio'] ?? 'No bio provided.') ?></p>
    </div>

    <?php if ($c['rating'] > 0): ?>
      <div class="card card-pad mb-3 text-center">
        <h3 style="margin-bottom:8px;">Course Rating</h3>
        <div class="star-display" style="font-size:2rem;"><?= str_repeat('★', round($c['rating'])) ?><span style="color:var(--text-light);"><?= str_repeat('★', 5 - round($c['rating'])) ?></span></div>
        <p class="text-muted" style="font-size:.85rem;"><?= number_format($c['rating'], 2) ?> / 5.00</p>
      </div>
    <?php endif; ?>

    <?php if ($isEnrolled || $isOwner): ?>
    <div class="card card-pad mb-3" style="background:linear-gradient(135deg,var(--primary-light),#fff);border-color:var(--primary);">
      <h3 style="margin-bottom:10px;display:flex;align-items:center;gap:6px;">
        <span class="material-icons" style="color:var(--primary);">smart_toy</span> AI Tools
      </h3>
      <p class="text-muted" style="font-size:.82rem;margin-bottom:10px;">Use the AI assistant to master this course faster.</p>
      <div class="flex" style="flex-direction:column;gap:6px;">
        <a href="<?= url('ai/new?course_id=' . $c['id']) ?>" class="btn btn-primary btn-sm btn-block" style="justify-content:flex-start;"><span class="material-icons">chat</span> Ask AI about this course</a>
        <a href="<?= url('ai/generateQuiz/' . $c['id']) ?>" class="btn btn-secondary btn-sm btn-block" style="justify-content:flex-start;"><span class="material-icons">quiz</span> Generate practice quiz</a>
        <a href="<?= url('flashcards/generateFromCourse/' . $c['id']) ?>" class="btn btn-secondary btn-sm btn-block" style="justify-content:flex-start;"><span class="material-icons">style</span> Generate flashcards</a>
        <a href="<?= url('whiteboard/course/' . $c['id']) ?>" class="btn btn-ghost btn-sm btn-block" style="justify-content:flex-start;"><span class="material-icons">draw</span> Whiteboard</a>
        <a href="<?= url('wiki/course/' . $c['id']) ?>" class="btn btn-ghost btn-sm btn-block" style="justify-content:flex-start;"><span class="material-icons">menu_book</span> Course wiki</a>
        <a href="<?= url('group/course/' . $c['id']) ?>" class="btn btn-ghost btn-sm btn-block" style="justify-content:flex-start;"><span class="material-icons">group</span> Study groups</a>
        <a href="<?= url('poll/course/' . $c['id']) ?>" class="btn btn-ghost btn-sm btn-block" style="justify-content:flex-start;"><span class="material-icons">poll</span> Polls</a>
        <?php if (Auth::isTeacher() || Auth::isAdmin()): ?>
          <a href="<?= url('code/course/' . $c['id']) ?>" class="btn btn-ghost btn-sm btn-block" style="justify-content:flex-start;"><span class="material-icons">code</span> Code exercises</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($quizzes) && ($isEnrolled || $isOwner)): ?>
      <div class="card card-pad mb-3">
        <h3 style="margin-bottom: 14px;">Quizzes</h3>
        <?php foreach ($quizzes as $q): ?>
          <div class="flex-between" style="padding:8px 0;border-bottom:1px solid var(--border);">
            <div>
              <strong style="font-size:.9rem;"><?= e($q['title']) ?></strong>
              <div class="text-muted" style="font-size:.75rem;"><?= $q['max_attempts'] ?> attempt(s) · <?= $q['passing_score'] ?>% to pass</div>
            </div>
            <a href="<?= url('quiz/take/' . $q['id']) ?>" class="btn btn-secondary btn-sm">Take</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($assignments) && ($isEnrolled || $isOwner)): ?>
      <div class="card card-pad">
        <h3 style="margin-bottom: 14px;">Assignments</h3>
        <?php foreach ($assignments as $a): ?>
          <div class="flex-between" style="padding:8px 0;border-bottom:1px solid var(--border);">
            <div>
              <strong style="font-size:.9rem;"><?= e($a['title']) ?></strong>
              <div class="text-muted" style="font-size:.75rem;">
                <?= $a['due_date'] ? 'Due ' . formatDate($a['due_date']) : 'No due date' ?> · <?= $a['max_score'] ?> pts
              </div>
            </div>
            <a href="<?= url('assignment/view/' . $a['id']) ?>" class="btn btn-secondary btn-sm">Open</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </aside>
</div>

<?php
// Reviews section
$reviews = Database::fetchAll(
    "SELECT r.*, u.full_name AS author_name, u.avatar AS author_avatar, u.role AS author_role
     FROM course_reviews r JOIN users u ON r.user_id = u.id
     WHERE r.course_id = :cid ORDER BY r.created_at DESC LIMIT 10",
    ['cid' => $c['id']]
);
$myReview = Auth::check() ? Database::fetch('SELECT * FROM course_reviews WHERE course_id = :cid AND user_id = :uid', ['cid' => $c['id'], 'uid' => Auth::id()]) : null;
$reviewCount = Database::count('course_reviews', 'course_id = :cid', ['cid' => $c['id']]);
?>
<div class="card mt-3" id="reviews">
  <div class="card-head">
    <h3>Reviews (<?= $reviewCount ?>)</h3>
    <?php if (Auth::isStudent() && $isEnrolled && !$myReview): ?>
      <button class="btn btn-primary btn-sm" onclick="document.getElementById('reviewModal').style.display='flex'"><span class="material-icons">star</span> Write a review</button>
    <?php elseif ($myReview): ?>
      <button class="btn btn-secondary btn-sm" onclick="document.getElementById('reviewModal').style.display='flex'"><span class="material-icons">edit</span> Edit your review</button>
    <?php endif; ?>
  </div>
  <?php if (empty($reviews)): ?>
    <div class="empty" style="padding:30px;"><span class="material-icons" style="font-size:36px;">star_outline</span><p style="font-size:.88rem;">No reviews yet. Be the first to share your experience!</p></div>
  <?php else: ?>
    <?php foreach ($reviews as $r): ?>
      <div style="padding:14px 22px;border-bottom:1px solid var(--border);">
        <div class="flex-between mb-1">
          <div class="flex gap-sm">
            <span class="avatar sm" style="background:<?= avatarColor($r['author_name']) ?>;<?= $r['is_anonymous'] ? 'visibility:hidden;' : '' ?>">
              <?php if (!$r['is_anonymous'] && !empty($r['author_avatar'])): ?><img src="<?= uploadUrl($r['author_avatar']) ?>" alt=""><?php else: ?><?= initials($r['author_name']) ?><?php endif; ?>
            </span>
            <div>
              <strong><?= $r['is_anonymous'] ? 'Anonymous' : e($r['author_name']) ?></strong>
              <?php if (!$r['is_anonymous']): ?><span class="badge role-badge <?= e($r['author_role']) ?>" style="font-size:.6rem;margin-left:4px;"><?= ucfirst($r['author_role']) ?></span><?php endif; ?>
              <div class="star-display" style="font-size:.95rem;"><?= str_repeat('★', $r['rating']) ?><span style="color:var(--text-light);"><?= str_repeat('★', 5 - $r['rating']) ?></span></div>
            </div>
          </div>
          <span class="text-muted" style="font-size:.78rem;"><?= timeAgo($r['updated_at'] ?: $r['created_at']) ?></span>
        </div>
        <?php if (!empty($r['review'])): ?>
          <p style="margin-top:6px;font-size:.9rem;line-height:1.6;color:var(--text-muted);"><?= nl2br(e($r['review'])) ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php if (Auth::isStudent() && $isEnrolled): ?>
<div id="reviewModal" class="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px;">
  <div class="card" style="max-width:500px;width:100%;">
    <div class="card-head"><h3><?= $myReview ? 'Edit your review' : 'Write a review' ?></h3><button class="btn btn-ghost btn-sm" onclick="document.getElementById('reviewModal').style.display='none'"><span class="material-icons">close</span></button></div>
    <div class="card-body">
      <form method="post" action="<?= url('review/add/' . $c['id']) ?>">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <div class="form-group">
          <label>Your rating</label>
          <div class="star-rating">
            <?php for ($i = 5; $i >= 1; $i--): ?>
              <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" <?= ($myReview['rating'] ?? 5) == $i ? 'checked' : '' ?> required>
              <label for="star<?= $i ?>"></label>
            <?php endfor; ?>
          </div>
        </div>
        <div class="form-group">
          <label>Your review (optional)</label>
          <textarea name="review" rows="5" placeholder="Share your experience with this course..."><?= e($myReview['review'] ?? '') ?></textarea>
        </div>
        <div class="form-group checkbox-group">
          <input type="checkbox" name="is_anonymous" id="anon" value="1" <?= ($myReview['is_anonymous'] ?? 0) ? 'checked' : '' ?>>
          <label for="anon">Post anonymously</label>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg"><?= $myReview ? 'Update review' : 'Post review' ?></button>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
