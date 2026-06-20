<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>Quiz Bank</h1>
    <p>Build quizzes with multiple-choice, true/false, and short-answer questions.</p>
  </div>
  <div class="actions">
    <button class="btn btn-primary" onclick="document.getElementById('newQuizModal').style.display='flex'"><span class="material-icons">add</span> New quiz</button>
  </div>
</div>

<?php if (empty($quizzes)): ?>
  <div class="card card-pad empty">
    <span class="material-icons">quiz</span>
    <h3>No quizzes yet</h3>
    <p>Create your first quiz and add questions to it. Quizzes can be auto-graded and shown to enrolled students.</p>
    <button class="btn btn-primary" onclick="document.getElementById('newQuizModal').style.display='flex'"><span class="material-icons">add</span> New quiz</button>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr>
            <th>Quiz</th>
            <th>Course</th>
            <th>Questions</th>
            <th>Attempts</th>
            <th>Pass %</th>
            <th>Created</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($quizzes as $q): ?>
            <tr>
              <td><a href="<?= url('teacher/quiz/' . $q['id']) ?>"><strong><?= e($q['title']) ?></strong></a></td>
              <td class="text-muted" style="font-size:.85rem;"><?= e($q['course_title']) ?></td>
              <td><?= (int) $q['question_count'] ?></td>
              <td><?= (int) $q['attempt_count'] ?></td>
              <td><?= $q['passing_score'] ?>%</td>
              <td class="text-muted" style="font-size:.82rem;"><?= timeAgo($q['created_at']) ?></td>
              <td class="text-right">
                <a href="<?= url('teacher/quiz/' . $q['id']) ?>" class="btn btn-secondary btn-sm"><span class="material-icons">edit</span></a>
                <a href="<?= url('quiz/take/' . $q['id']) ?>" class="btn btn-secondary btn-sm"><span class="material-icons">visibility</span></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<div id="newQuizModal" class="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px;">
  <div class="card" style="max-width:560px;width:100%;max-height:90vh;overflow-y:auto;">
    <div class="card-head">
      <h3>Create new quiz</h3>
      <button class="btn btn-ghost btn-sm" onclick="document.getElementById('newQuizModal').style.display='none'"><span class="material-icons">close</span></button>
    </div>
    <div class="card-body">
      <form method="post" action="<?= url('teacher/createQuiz') ?>">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <div class="form-group">
          <label>Course</label>
          <select name="course_id" required>
            <option value="">— Select course —</option>
            <?php foreach ($courses as $c): ?>
              <option value="<?= $c['id'] ?>"><?= e($c['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Quiz title</label>
          <input type="text" name="title" required>
        </div>
        <div class="form-group">
          <label>Description (optional)</label>
          <textarea name="description" rows="2"></textarea>
        </div>
        <div class="form-group">
          <label>Instructions for students</label>
          <textarea name="instructions" rows="3"></textarea>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Time limit (minutes, 0 = none)</label>
            <input type="number" name="time_limit" value="0" min="0">
          </div>
          <div class="form-group">
            <label>Max attempts</label>
            <input type="number" name="max_attempts" value="1" min="1">
          </div>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Passing score (%)</label>
            <input type="number" name="passing_score" value="60" min="0" max="100" step="1">
          </div>
          <div class="form-group">
            <label>Options</label>
            <div class="checkbox-group" style="margin-bottom:6px;">
              <input type="checkbox" name="shuffle" id="shuf" value="1">
              <label for="shuf">Shuffle questions</label>
            </div>
            <div class="checkbox-group">
              <input type="checkbox" name="show_answers" id="showA" value="1">
              <label for="showA">Show correct answers after</label>
            </div>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Create quiz</button>
      </form>
    </div>
  </div>
</div>
