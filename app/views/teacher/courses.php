<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>My Courses</h1>
    <p>You have <?= count($courses) ?> courses. Create a new one or edit an existing course.</p>
  </div>
  <div class="actions">
    <button class="btn btn-primary" onclick="document.getElementById('newCourseModal').style.display='flex'">
      <span class="material-icons">add</span> New course
    </button>
  </div>
</div>

<?php if (empty($courses)): ?>
  <div class="card card-pad empty">
    <span class="material-icons">school</span>
    <h3>You haven't created any courses yet</h3>
    <p>Start by creating your first course. You'll be able to add modules, lessons, quizzes, and assignments.</p>
    <button class="btn btn-primary" onclick="document.getElementById('newCourseModal').style.display='flex'">
      <span class="material-icons">add</span> Create your first course
    </button>
  </div>
<?php else: ?>
  <div class="grid grid-3">
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
          <div class="flex-between mb-1">
            <span class="badge badge-<?= $c['status'] === 'published' ? 'success' : 'warning' ?>"><?= ucfirst($c['status']) ?></span>
            <span class="text-muted" style="font-size:.78rem;"><?= (int) $c['lesson_count'] ?> lessons</span>
          </div>
          <h3><a href="<?= url('course/view/' . $c['id']) ?>"><?= e($c['title']) ?></a></h3>
          <p class="desc"><?= e(truncate($c['description'] ?? '', 100)) ?></p>
          <div class="meta">
            <span class="teacher"><span class="material-icons" style="font-size:14px;">group</span> <?= (int) $c['student_count'] ?> students</span>
            <div class="flex gap-sm">
              <a href="<?= url('teacher/courses/edit/' . $c['id']) ?>" class="btn btn-secondary btn-sm"><span class="material-icons">edit</span></a>
              <a href="<?= url('course/students/' . $c['id']) ?>" class="btn btn-secondary btn-sm"><span class="material-icons">people</span></a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- New course modal -->
<div id="newCourseModal" class="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px;">
  <div class="card" style="max-width:640px;width:100%;max-height:90vh;overflow-y:auto;">
    <div class="card-head">
      <h3>Create new course</h3>
      <button class="btn btn-ghost btn-sm" onclick="document.getElementById('newCourseModal').style.display='none'"><span class="material-icons">close</span></button>
    </div>
    <div class="card-body">
      <form method="post" action="<?= url('teacher/createCourse') ?>" enctype="multipart/form-data">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <div class="form-group">
          <label>Course title</label>
          <input type="text" name="title" required placeholder="e.g., Introduction to Python Programming">
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" rows="4" placeholder="What will students learn in this course?"></textarea>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Category</label>
            <select name="category_id">
              <option value="0">— Uncategorized —</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Level</label>
            <select name="level">
              <option value="beginner">Beginner</option>
              <option value="intermediate">Intermediate</option>
              <option value="advanced">Advanced</option>
            </select>
          </div>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Visibility</label>
            <select name="visibility">
              <option value="public">Public (shown in catalog)</option>
              <option value="private">Private (only with direct link)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Language</label>
            <select name="language">
              <option value="en">English</option>
              <option value="es">Spanish</option>
              <option value="fr">French</option>
              <option value="de">German</option>
              <option value="zh">Chinese</option>
              <option value="ja">Japanese</option>
            </select>
          </div>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Start date (optional)</label>
            <input type="date" name="start_date">
          </div>
          <div class="form-group">
            <label>End date (optional)</label>
            <input type="date" name="end_date">
          </div>
        </div>
        <div class="form-group">
          <label>Enrollment key (optional)</label>
          <input type="text" name="enroll_key" placeholder="Leave blank for open enrollment">
          <span class="hint">If set, students must enter this key to enroll.</span>
        </div>
        <div class="form-group">
          <label>Cover image (optional)</label>
          <input type="file" name="cover" accept="image/*">
        </div>
        <button type="submit" class="btn btn-primary btn-lg btn-block"><span class="material-icons">add</span> Create course</button>
      </form>
    </div>
  </div>
</div>
