<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="breadcrumb">
  <a href="<?= url('course/view/' . $course['id']) ?>"><?= e($course['title']) ?></a>
  <span class="material-icons">chevron_right</span>
  <span>Code exercises</span>
</div>
<div class="page-head">
  <div class="title-block"><h1>Manage Code Exercises</h1><p><?= e($course['title']) ?></p></div>
  <div class="actions"><button class="btn btn-primary" onclick="document.getElementById('newExModal').style.display='flex'"><span class="material-icons">add</span> New exercise</button></div>
</div>

<?php if (empty($exercises)): ?>
  <div class="card card-pad empty"><span class="material-icons">code</span><h3>No exercises yet</h3><p>Create your first programming challenge.</p></div>
<?php else: ?>
  <div class="card">
    <div class="table-wrap"><table class="data">
      <thead><tr><th>Title</th><th>Language</th><th>Difficulty</th><th>Tests</th><th>Submissions</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($exercises as $ex):
          $tests = Database::count('code_test_cases', 'exercise_id = :eid', ['eid' => $ex['id']]);
          $subs = Database::count('code_submissions', 'exercise_id = :eid', ['eid' => $ex['id']]);
        ?>
          <tr>
            <td><strong><?= e($ex['title']) ?></strong></td>
            <td><?= ucfirst($ex['language']) ?></td>
            <td><span class="badge badge-<?= $ex['difficulty'] === 'easy' ? 'success' : ($ex['difficulty'] === 'medium' ? 'warning' : 'danger') ?>"><?= ucfirst($ex['difficulty']) ?></span></td>
            <td><?= $tests ?></td>
            <td><?= $subs ?></td>
            <td class="text-right">
              <a href="<?= url('code/exercise/' . $ex['id']) ?>" class="btn btn-secondary btn-sm"><span class="material-icons">visibility</span></a>
              <a href="<?= url('code/delete/' . $ex['id']) ?>" class="btn btn-danger btn-sm" data-confirm="Delete exercise?"><span class="material-icons">delete</span></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
<?php endif; ?>

<div id="newExModal" class="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px;">
  <div class="card" style="max-width:760px;width:100%;max-height:90vh;overflow-y:auto;">
    <div class="card-head"><h3>New code exercise</h3><button class="btn btn-ghost btn-sm" onclick="document.getElementById('newExModal').style.display='none'"><span class="material-icons">close</span></button></div>
    <div class="card-body">
      <form method="post" action="<?= url('code/create/' . $course['id']) ?>">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <div class="form-grid">
          <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
          <div class="form-group"><label>Difficulty</label><select name="difficulty"><option value="easy">Easy</option><option value="medium">Medium</option><option value="hard">Hard</option></select></div>
        </div>
        <div class="form-grid">
          <div class="form-group"><label>Language</label><select name="language"><option value="php">PHP</option><option value="python">Python 3</option></select></div>
          <div class="form-group"><label>Points</label><input type="number" name="points" value="10" min="1" step="0.5"></div>
        </div>
        <div class="form-group"><label>Description (short)</label><input type="text" name="description"></div>
        <div class="form-group"><label>Problem statement</label><textarea name="problem_statement" rows="6" required></textarea></div>
        <div class="form-group"><label>Starter code</label><textarea name="starter_code" rows="4" style="font-family:monospace;"></textarea></div>
        <div class="form-group"><label>Solution code (private)</label><textarea name="solution_code" rows="4" style="font-family:monospace;"></textarea></div>
        <h4 style="margin:18px 0 8px;">Test cases</h4>
        <div id="testCases">
          <div class="test-case-row">
            <div class="form-group"><label>Input</label><textarea name="test_input[]" rows="2" style="font-family:monospace;"></textarea></div>
            <div class="form-group"><label>Expected output</label><textarea name="test_expected[]" rows="2" style="font-family:monospace;" required></textarea></div>
            <div class="checkbox-group"><input type="checkbox" name="test_hidden[]" value="0" id="h0"><label for="h0">Hidden</label></div>
          </div>
        </div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="addTestCase()"><span class="material-icons">add</span> Add test case</button>
        <hr style="border:0;border-top:1px solid var(--border);margin:18px 0;">
        <button type="submit" class="btn btn-primary btn-lg btn-block">Create exercise</button>
      </form>
    </div>
  </div>
</div>
<style>.test-case-row{padding:12px;border:1px solid var(--border);border-radius:8px;margin-bottom:12px;}</style>
<script>
let tcCount = 1;
function addTestCase() {
  tcCount++;
  const div = document.createElement('div');
  div.className = 'test-case-row';
  div.innerHTML = `
    <div class="form-group"><label>Input</label><textarea name="test_input[]" rows="2" style="font-family:monospace;"></textarea></div>
    <div class="form-group"><label>Expected output</label><textarea name="test_expected[]" rows="2" style="font-family:monospace;" required></textarea></div>
    <div class="checkbox-group"><input type="checkbox" name="test_hidden[]" value="${tcCount-1}" id="h${tcCount}"><label for="h${tcCount}">Hidden</label></div>
    <button type="button" class="btn btn-ghost btn-sm" onclick="this.parentElement.remove()"><span class="material-icons">delete</span> Remove</button>
  `;
  document.getElementById('testCases').appendChild(div);
}
</script>
