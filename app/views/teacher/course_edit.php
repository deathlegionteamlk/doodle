<?php /** @var array $data */
extract($data, EXTR_SKIP);
$c = $course;
?>
<div class="breadcrumb">
  <a href="<?= url('teacher/courses') ?>">My courses</a>
  <span class="material-icons">chevron_right</span>
  <span><?= e($c['title']) ?></span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1><?= e($c['title']) ?></h1>
    <p>
      <span class="badge badge-<?= $c['status'] === 'published' ? 'success' : 'warning' ?>"><?= ucfirst($c['status']) ?></span>
      <span class="badge"><?= ucfirst($c['visibility']) ?></span>
      <span class="badge"><?= ucfirst($c['level']) ?></span>
    </p>
  </div>
  <div class="actions">
    <a href="<?= url('course/view/' . $c['id']) ?>" class="btn btn-secondary"><span class="material-icons">visibility</span> Preview</a>
    <a href="<?= url('course/students/' . $c['id']) ?>" class="btn btn-secondary"><span class="material-icons">group</span> Students</a>
    <?php if ($c['status'] === 'draft'): ?>
      <form method="post" action="<?= url('teacher/courses/edit/' . $c['id']) ?>" style="display:inline;">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <input type="hidden" name="action" value="publish">
        <button class="btn btn-success"><span class="material-icons">publish</span> Publish</button>
      </form>
    <?php else: ?>
      <form method="post" action="<?= url('teacher/courses/edit/' . $c['id']) ?>" style="display:inline;">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <input type="hidden" name="action" value="unpublish">
        <button class="btn btn-ghost"><span class="material-icons">unpublished</span> Unpublish</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="tabs">
  <a href="#course-details" class="active" onclick="switchTab(event,'course-details')">Course details</a>
  <a href="#curriculum" onclick="switchTab(event,'curriculum')">Curriculum</a>
  <a href="#assignments" onclick="switchTab(event,'assignments')">Assignments</a>
</div>

<!-- Course details tab -->
<div id="course-details" class="tab-pane">
  <div class="card card-pad">
    <h3 style="margin-bottom: 14px;">Course information</h3>
    <form method="post" action="<?= url('teacher/courses/edit/' . $c['id']) ?>" enctype="multipart/form-data">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
      <input type="hidden" name="action" value="save">
      <div class="form-group">
        <label>Course title</label>
        <input type="text" name="title" value="<?= e($c['title']) ?>" required>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="5"><?= e($c['description']) ?></textarea>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Category</label>
          <select name="category_id">
            <option value="0">— Uncategorized —</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $c['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Level</label>
          <select name="level">
            <option value="beginner"     <?= $c['level'] === 'beginner'     ? 'selected' : '' ?>>Beginner</option>
            <option value="intermediate" <?= $c['level'] === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
            <option value="advanced"     <?= $c['level'] === 'advanced'     ? 'selected' : '' ?>>Advanced</option>
          </select>
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Visibility</label>
          <select name="visibility">
            <option value="public"  <?= $c['visibility'] === 'public'  ? 'selected' : '' ?>>Public</option>
            <option value="private" <?= $c['visibility'] === 'private' ? 'selected' : '' ?>>Private</option>
          </select>
        </div>
        <div class="form-group">
          <label>Language</label>
          <select name="language">
            <?php foreach (['en','es','fr','de','zh','ja'] as $lng): ?>
              <option value="<?= $lng ?>" <?= $c['language'] === $lng ? 'selected' : '' ?>><?= strtoupper($lng) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Start date</label>
          <input type="date" name="start_date" value="<?= e($c['start_date']) ?>">
        </div>
        <div class="form-group">
          <label>End date</label>
          <input type="date" name="end_date" value="<?= e($c['end_date']) ?>">
        </div>
      </div>
      <div class="form-group">
        <label>Enrollment key</label>
        <input type="text" name="enroll_key" value="<?= e($c['enroll_key']) ?>">
        <span class="hint">Leave blank for open enrollment.</span>
      </div>
      <div class="form-group">
        <label>Cover image</label>
        <input type="file" name="cover" accept="image/*">
        <?php if (!empty($c['cover_image'])): ?>
          <div style="margin-top:8px;"><img src="<?= uploadUrl($c['cover_image']) ?>" alt="" style="max-width:160px;border-radius:var(--radius-sm);"></div>
        <?php endif; ?>
      </div>
      <div class="flex gap-sm">
        <button type="submit" class="btn btn-primary"><span class="material-icons">save</span> Save changes</button>
        <form method="post" action="<?= url('teacher/courses/edit/' . $c['id']) ?>" onsubmit="return confirm('Permanently delete this course and all its content?')">
          <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
          <input type="hidden" name="action" value="delete">
          <button type="submit" class="btn btn-danger"><span class="material-icons">delete</span> Delete course</button>
        </form>
      </div>
    </form>
  </div>
</div>

<!-- Curriculum tab -->
<div id="curriculum" class="tab-pane" style="display:none;">
  <div class="flex-between mb-3">
    <h3>Curriculum</h3>
    <button class="btn btn-primary" onclick="document.getElementById('newModuleModal').style.display='flex'"><span class="material-icons">add</span> New module</button>
  </div>

  <?php if (empty($modules)): ?>
    <div class="card card-pad empty">
      <span class="material-icons">folder</span>
      <h3>No modules yet</h3>
      <p>Modules group related lessons. Create your first module to start adding content.</p>
      <button class="btn btn-primary" onclick="document.getElementById('newModuleModal').style.display='flex'"><span class="material-icons">add</span> New module</button>
    </div>
  <?php else: foreach ($modules as $m): ?>
    <div class="module-block">
      <div class="module-head">
        <span class="material-icons">folder</span>
        <span class="title"><?= e($m['title']) ?></span>
        <span class="badge"><?= $m['lesson_count'] ?> lessons</span>
        <div class="actions">
          <button class="btn btn-secondary btn-sm" onclick="openLessonModal(<?= $m['id'] ?>, '<?= e($c['id']) ?>')"><span class="material-icons">add</span> Add lesson</button>
          <a href="<?= url('teacher/deleteModule/' . $m['id']) ?>" class="btn btn-danger btn-sm" data-confirm="Delete this module and all its lessons?"><span class="material-icons">delete</span></a>
        </div>
      </div>
      <div class="lesson-list">
        <?php
        $lessons = Database::fetchAll("SELECT * FROM lessons WHERE module_id = :mid ORDER BY sort_order, id", ['mid' => $m['id']]);
        if (empty($lessons)): ?>
          <div class="lesson-item" style="color:var(--text-muted);font-style:italic;">No lessons in this module yet.</div>
        <?php else: foreach ($lessons as $l): ?>
          <div class="lesson-item">
            <span class="material-icons icon"><?= $l['content_type'] === 'video' ? 'play_circle' : ($l['content_type'] === 'file' ? 'attachment' : 'article') ?></span>
            <span class="title"><?= e($l['title']) ?></span>
            <span class="badge"><?= ucfirst(str_replace('_', ' ', $l['content_type'])) ?></span>
            <?php if ($l['is_preview']): ?><span class="preview">Free preview</span><?php endif; ?>
            <a href="<?= url('course/learn/' . $c['id'] . '/' . $l['id']) ?>" class="btn btn-ghost btn-sm"><span class="material-icons">visibility</span></a>
            <a href="<?= url('teacher/deleteLesson/' . $l['id']) ?>" class="btn btn-danger btn-sm" data-confirm="Delete this lesson?"><span class="material-icons">delete</span></a>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<!-- Assignments tab -->
<div id="assignments" class="tab-pane" style="display:none;">
  <div class="flex-between mb-3">
    <h3>Assignments</h3>
    <button class="btn btn-primary" onclick="document.getElementById('newAssignmentModal').style.display='flex'"><span class="material-icons">add</span> New assignment</button>
  </div>
  <?php
  $assignments = Database::fetchAll("SELECT * FROM assignments WHERE course_id = :cid ORDER BY id", ['cid' => $c['id']]);
  if (empty($assignments)): ?>
    <div class="card card-pad empty">
      <span class="material-icons">assignment</span>
      <h3>No assignments yet</h3>
      <p>Create assignments so students can submit work for you to grade.</p>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>Title</th><th>Max score</th><th>Due date</th><th>Submissions</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($assignments as $a): ?>
              <?php $count = Database::count('submissions', 'assignment_id = :aid', ['aid' => $a['id']]); ?>
              <tr>
                <td><strong><?= e($a['title']) ?></strong></td>
                <td><?= $a['max_score'] ?></td>
                <td class="text-muted"><?= $a['due_date'] ? formatDate($a['due_date']) : 'No due date' ?></td>
                <td><?= $count ?></td>
                <td class="text-right"><a href="<?= url('teacher/assignment/' . $a['id']) ?>" class="btn btn-secondary btn-sm">Open</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- New module modal -->
<div id="newModuleModal" class="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px;">
  <div class="card" style="max-width:500px;width:100%;">
    <div class="card-head">
      <h3>New module</h3>
      <button class="btn btn-ghost btn-sm" onclick="document.getElementById('newModuleModal').style.display='none'"><span class="material-icons">close</span></button>
    </div>
    <div class="card-body">
      <form method="post" action="<?= url('teacher/addModule/' . $c['id']) ?>">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <div class="form-group">
          <label>Module title</label>
          <input type="text" name="title" required placeholder="e.g., Introduction & Setup">
        </div>
        <div class="form-group">
          <label>Description (optional)</label>
          <textarea name="description" rows="3"></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Create module</button>
      </form>
    </div>
  </div>
</div>

<!-- New lesson modal -->
<div id="newLessonModal" class="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px;">
  <div class="card" style="max-width:640px;width:100%;max-height:90vh;overflow-y:auto;">
    <div class="card-head">
      <h3>New lesson</h3>
      <button class="btn btn-ghost btn-sm" onclick="document.getElementById('newLessonModal').style.display='none'"><span class="material-icons">close</span></button>
    </div>
    <div class="card-body">
      <form method="post" action="" id="lessonForm" enctype="multipart/form-data">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <div class="form-group">
          <label>Lesson title</label>
          <input type="text" name="title" required>
        </div>
        <div class="form-group">
          <label>Content type</label>
          <select name="content_type" id="lessonType" onchange="toggleLessonFields()">
            <option value="text">Text (HTML / written content)</option>
            <option value="video">Video (uploaded file or YouTube/Vimeo URL)</option>
            <option value="file">File attachment (PDF, DOCX, etc.)</option>
            <option value="url">External link</option>
          </select>
        </div>
        <div class="form-group" id="contentField">
          <label>Lesson content (HTML allowed)</label>
          <textarea name="content" rows="6" placeholder="<p>Write your lesson content here...</p>"></textarea>
        </div>
        <div class="form-group" id="externalUrlField" style="display:none;">
          <label>External URL</label>
          <input type="url" name="external_url" placeholder="https://youtube.com/watch?v=...">
          <span class="hint">For video: paste a YouTube/Vimeo embed URL.</span>
        </div>
        <div class="form-group" id="fileField" style="display:none;">
          <label>Upload file</label>
          <input type="file" name="file">
          <span class="hint">Max size: <?= humanFileSize(MAX_UPLOAD_SIZE) ?>. Allowed: <?= implode(', ', array_keys(ALLOWED_UPLOAD_TYPES)) ?></span>
        </div>
        <div class="form-group checkbox-group">
          <input type="checkbox" name="is_preview" id="isPreview" value="1">
          <label for="isPreview">Allow free preview (visible to non-enrolled users)</label>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Add lesson</button>
      </form>
    </div>
  </div>
</div>

<!-- New assignment modal -->
<div id="newAssignmentModal" class="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px;">
  <div class="card" style="max-width:560px;width:100%;">
    <div class="card-head">
      <h3>New assignment</h3>
      <button class="btn btn-ghost btn-sm" onclick="document.getElementById('newAssignmentModal').style.display='none'"><span class="material-icons">close</span></button>
    </div>
    <div class="card-body">
      <form method="post" action="<?= url('teacher/createAssignment') ?>">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
        <div class="form-group">
          <label>Title</label>
          <input type="text" name="title" required>
        </div>
        <div class="form-group">
          <label>Instructions</label>
          <textarea name="description" rows="5"></textarea>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Max score</label>
            <input type="number" name="max_score" value="100" min="1" step="0.5">
          </div>
          <div class="form-group">
            <label>Due date</label>
            <input type="datetime-local" name="due_date">
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Create assignment</button>
      </form>
    </div>
  </div>
</div>

<script>
function switchTab(e, id) {
  e.preventDefault();
  document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
  document.querySelectorAll('.tabs a').forEach(a => a.classList.remove('active'));
  document.getElementById(id).style.display = 'block';
  e.target.classList.add('active');
}
function openLessonModal(moduleId, courseId) {
  document.getElementById('lessonForm').action = '<?= url('teacher/addLesson/') ?>' + moduleId;
  document.getElementById('newLessonModal').style.display = 'flex';
}
function toggleLessonFields() {
  const t = document.getElementById('lessonType').value;
  document.getElementById('contentField').style.display = (t === 'text' || t === 'video') ? 'block' : 'none';
  document.getElementById('externalUrlField').style.display = (t === 'video' || t === 'url') ? 'block' : 'none';
  document.getElementById('fileField').style.display = (t === 'file' || t === 'video') ? 'block' : 'none';
}
</script>
