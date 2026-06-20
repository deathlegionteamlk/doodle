<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="breadcrumb"><a href="<?= url('course/view/' . $course['id']) ?>"><?= e($course['title']) ?></a><span class="material-icons">chevron_right</span><span>Wiki</span></div>
<div class="page-head">
  <div class="title-block"><h1>Course Wiki</h1><p><?= e($course['title']) ?> · collaborative knowledge base</p></div>
  <div class="actions"><a href="<?= url('wiki/create/' . $course['id']) ?>" class="btn btn-primary"><span class="material-icons">add</span> New page</a></div>
</div>
<?php if (empty($pages)): ?>
  <div class="card card-pad empty"><span class="material-icons">menu_book</span><h3>No wiki pages yet</h3><p>Build a collaborative knowledge base for your course.</p></div>
<?php else: ?>
  <div class="card"><div class="table-wrap"><table class="data"><thead><tr><th>Page</th><th>Last updated</th><th></th></tr></thead><tbody>
    <?php foreach ($pages as $p): ?>
      <tr><td><strong><a href="<?= url('wiki/view/' . $p['id']) ?>" style="color:inherit;text-decoration:none;"><?= e($p['title']) ?></a></strong></td>
        <td class="text-muted" style="font-size:.82rem;"><?= $p['updated_at'] ? timeAgo($p['updated_at']) : timeAgo($p['created_at']) ?></td>
        <td class="text-right"><a href="<?= url('wiki/view/' . $p['id']) ?>" class="btn btn-secondary btn-sm">View</a></td></tr>
    <?php endforeach; ?>
  </tbody></table></div></div>
<?php endif; ?>
