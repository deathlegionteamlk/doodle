<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="breadcrumb"><a href="<?= url('course/view/' . $course['id']) ?>"><?= e($course['title']) ?></a><span class="material-icons">chevron_right</span><span>New wiki page</span></div>
<div class="page-head"><div class="title-block"><h1>New Wiki Page</h1><p><?= e($course['title']) ?></p></div></div>
<div class="card card-pad">
  <form method="post" action="<?= url('wiki/create/' . $course['id']) ?>">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
    <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
    <div class="form-group"><label>Content (HTML allowed)</label><textarea name="content" rows="14"></textarea></div>
    <button type="submit" class="btn btn-primary btn-lg"><span class="material-icons">save</span> Create page</button>
    <a href="<?= url('wiki/course/' . $course['id']) ?>" class="btn btn-ghost">Cancel</a>
  </form>
</div>
