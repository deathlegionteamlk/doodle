<?php /** @var array $data */
extract($data, EXTR_SKIP);
$c = $course;
?>
<div class="breadcrumb">
  <a href="<?= url('teacher/courses/edit/' . $c['id']) ?>"><?= e($c['title']) ?></a>
  <span class="material-icons">chevron_right</span>
  <span>Certificate templates</span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1>Certificate Templates</h1>
    <p><?= e($c['title']) ?> · configure certificates students receive on course completion.</p>
  </div>
  <div class="actions">
    <button class="btn btn-primary" onclick="document.getElementById('newTemplateModal').style.display='flex'"><span class="material-icons">add</span> New template</button>
  </div>
</div>

<?php if (empty($templates)): ?>
  <div class="card card-pad empty">
    <span class="material-icons">workspace_premium</span>
    <h3>No certificate templates yet</h3>
    <p>Without a template, students who finish the course won't receive a certificate. Create one to start awarding them automatically.</p>
    <button class="btn btn-primary" onclick="document.getElementById('newTemplateModal').style.display='flex'"><span class="material-icons">add</span> Create template</button>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Name</th><th>Min score</th><th>Issued to students</th><th>Created</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($templates as $t):
            $issued = Database::count('certificates', 'template_id = :tid', ['tid' => $t['id']]);
          ?>
            <tr>
              <td><strong><?= e($t['name']) ?></strong></td>
              <td><?= $t['min_score'] ?>%</td>
              <td><?= $issued ?></td>
              <td class="text-muted" style="font-size:.82rem;"><?= timeAgo($t['created_at']) ?></td>
              <td class="text-right">
                <a href="<?= url('certificates/deleteTemplate/' . $t['id']) ?>" class="btn btn-danger btn-sm" data-confirm="Delete this template?"><span class="material-icons">delete</span></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<div id="newTemplateModal" class="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px;">
  <div class="card" style="max-width:600px;width:100%;">
    <div class="card-head">
      <h3>New certificate template</h3>
      <button class="btn btn-ghost btn-sm" onclick="document.getElementById('newTemplateModal').style.display='none'"><span class="material-icons">close</span></button>
    </div>
    <div class="card-body">
      <form method="post" action="<?= url('certificates/createTemplate/' . $c['id']) ?>">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <div class="form-group">
          <label>Template name</label>
          <input type="text" name="name" required placeholder="e.g., Course Completion Certificate">
        </div>
        <div class="form-group">
          <label>Minimum score required (%)</label>
          <input type="number" name="min_score" value="60" min="0" max="100" step="1">
          <span class="hint">Students must achieve at least this average to receive the certificate.</span>
        </div>
        <div class="form-group">
          <label>Custom template HTML (optional)</label>
          <textarea name="template_html" rows="6" placeholder="Leave blank to use the default doodle certificate design."></textarea>
          <span class="hint">Supports {{name}}, {{course}}, {{score}}, {{date}}, {{code}} placeholders.</span>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Create template</button>
      </form>
    </div>
  </div>
</div>
