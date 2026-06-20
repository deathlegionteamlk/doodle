<?php /** @var array $data */
extract($data, EXTR_SKIP);
$ex = $exercise;
?>
<div class="breadcrumb">
  <a href="<?= url('course/view/' . $ex['course_id']) ?>"><?= e($ex['course_title']) ?></a>
  <span class="material-icons">chevron_right</span>
  <a href="<?= url('code/course/' . $ex['course_id']) ?>">Code</a>
  <span class="material-icons">chevron_right</span>
  <span><?= e($ex['title']) ?></span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1><?= e($ex['title']) ?></h1>
    <p>
      <span class="badge badge-<?= $ex['difficulty'] === 'easy' ? 'success' : ($ex['difficulty'] === 'medium' ? 'warning' : 'danger') ?>"><?= ucfirst($ex['difficulty']) ?></span>
      <span class="badge"><?= ucfirst($ex['language']) ?></span>
      <span class="badge badge-info"><?= $ex['points'] ?> pts</span>
    </p>
  </div>
</div>

<div class="grid" style="grid-template-columns: 1fr 1fr; gap:18px; align-items:stretch;">
  <div class="card card-pad">
    <h3 style="margin-bottom:10px;">Problem</h3>
    <div style="line-height:1.7;"><?= nl2br(e($ex['problem_statement'])) ?></div>
    <?php if (!empty($testCases)): ?>
      <h4 style="margin-top:18px;margin-bottom:8px;">Sample test cases</h4>
      <?php foreach ($testCases as $i => $tc): ?>
        <div style="background:var(--surface-2);padding:10px;border-radius:6px;margin-bottom:8px;font-family:monospace;font-size:.82rem;">
          <div><strong>Input:</strong> <pre style="margin:4px 0;background:transparent;padding:0;"><?= e($tc['input']) ?></pre></div>
          <div><strong>Expected:</strong> <pre style="margin:4px 0;background:transparent;padding:0;"><?= e($tc['expected_output']) ?></pre></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="card card-pad" style="display:flex;flex-direction:column;">
    <h3 style="margin-bottom:10px;">Your solution <span class="text-muted" style="font-size:.78rem;font-weight:400;">(<?= ucfirst($ex['language']) ?>)</span></h3>
    <textarea id="codeEditor" style="flex:1;min-height:280px;font-family:'SFMono-Regular',Consolas,monospace;font-size:.88rem;"><?= e($ex['starter_code'] ?: ($ex['language'] === 'php' ? "<?php\n\n// Read input from STDIN, print to STDOUT\n\$input = trim(fgets(STDIN));\necho \$input;\n" : "# Read input from STDIN, print to STDOUT\nimport sys\ninput = sys.stdin.read().strip()\nprint(input)\n")) ?></textarea>
    <div class="flex gap-sm mt-2">
      <button class="btn btn-primary" id="runBtn"><span class="material-icons">play_arrow</span> Run tests</button>
      <span id="runResult" class="badge" style="display:none;"></span>
    </div>
    <div id="testResults" style="margin-top:14px;"></div>
  </div>
</div>

<?php if (!empty($submissions)): ?>
<div class="card mt-3">
  <div class="card-head"><h3>Your recent submissions</h3></div>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Time</th><th>Status</th><th>Tests passed</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($submissions as $s): ?>
          <tr>
            <td class="text-muted" style="font-size:.82rem;"><?= timeAgo($s['submitted_at']) ?></td>
            <td>
              <?php if ($s['status'] === 'passed'): ?>
                <span class="badge badge-success">Passed</span>
              <?php else: ?>
                <span class="badge badge-danger">Failed</span>
              <?php endif; ?>
            </td>
            <td><?= $s['passed_tests'] ?> / <?= $s['total_tests'] ?></td>
            <td><button class="btn btn-ghost btn-sm" onclick='showCode(<?= htmlspecialchars(json_encode($s['code']), ENT_QUOTES) ?>)'>View code</button></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<script>
const exerciseId = <?= $ex['id'] ?>;
const csrfToken = '<?= CSRF::token() ?>';
document.getElementById('runBtn').addEventListener('click', () => {
  const code = document.getElementById('codeEditor').value;
  const btn = document.getElementById('runBtn');
  const result = document.getElementById('runResult');
  const results = document.getElementById('testResults');
  btn.disabled = true; btn.innerHTML = '<span class="material-icons">hourglass_empty</span> Running...';
  results.innerHTML = '<p class="text-muted">Running tests...</p>';
  fetch('<?= url("code/run/") ?>' + exerciseId, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'code=' + encodeURIComponent(code) + '&<?= CSRF_TOKEN_NAME ?>=' + csrfToken
  })
  .then(r => r.json())
  .then(d => {
    btn.disabled = false; btn.innerHTML = '<span class="material-icons">play_arrow</span> Run tests';
    if (d.ok) {
      result.style.display = 'inline-flex';
      result.className = 'badge badge-' + (d.status === 'passed' ? 'success' : 'danger');
      result.textContent = d.status === 'passed' ? '✓ All tests passed!' : `✗ ${d.passed}/${d.total} passed`;
      let html = '';
      d.results.forEach((r, i) => {
        const cls = r.passed ? 'success' : 'danger';
        const icon = r.passed ? 'check_circle' : 'cancel';
        html += `<div class="quiz-option" style="cursor:default;background:var(--${cls === 'success' ? 'success' : 'danger'}-light);border-color:var(--${cls});color:var(--${cls});margin-bottom:6px;">
          <span class="material-icons">${icon}</span>
          <div style="flex:1;">
            <strong>Test ${i+1} ${r.hidden ? '(hidden)' : ''}</strong>
            ${!r.hidden ? `<div style="font-family:monospace;font-size:.78rem;margin-top:4px;">Input: <pre style="margin:0;background:transparent;padding:0;">${r.input||'(empty)'}</pre>Expected: <pre style="margin:0;background:transparent;padding:0;">${r.expected}</pre>Got: <pre style="margin:0;background:transparent;padding:0;">${r.actual||'(empty)'}</pre></div>` : ''}
            ${r.error ? `<div style="color:var(--danger);font-size:.78rem;">${r.error}</div>` : ''}
          </div>
        </div>`;
      });
      results.innerHTML = html;
      if (d.status === 'passed') {
        setTimeout(() => location.reload(), 1500);
      }
    } else {
      results.innerHTML = `<div class="alert alert-error">${d.error || 'Run failed.'}</div>`;
    }
  })
  .catch(err => {
    btn.disabled = false; btn.innerHTML = '<span class="material-icons">play_arrow</span> Run tests';
    results.innerHTML = `<div class="alert alert-error">Network error: ${err}</div>`;
  });
});
function showCode(code) {
  const w = window.open('', '_blank');
  w.document.write('<pre style="font-family:monospace;padding:20px;">' + code.replace(/</g,'&lt;') + '</pre>');
}
</script>
