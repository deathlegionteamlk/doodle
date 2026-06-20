<?php /** @var array $data */
extract($data, EXTR_SKIP);
$p = $page;
?>
<div class="breadcrumb"><a href="<?= url('wiki/view/' . $p['id']) ?>"><?= e($p['title']) ?></a><span class="material-icons">chevron_right</span><span>Edit</span></div>
<div class="page-head"><div class="title-block"><h1>Edit Wiki Page</h1><p><?= e($p['title']) ?></p></div></div>
<div class="card card-pad">
  <form method="post" action="<?= url('wiki/edit/' . $p['id']) ?>">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
    <div class="form-group"><label>Title</label><input type="text" name="title" value="<?= e($p['title']) ?>" required></div>
    <div class="form-group">
      <label>Content (HTML allowed)</label>
      <textarea name="content" rows="18" id="wikiContent"><?= e($p['content']) ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary btn-lg"><span class="material-icons">save</span> Save page</button>
    <a href="<?= url('wiki/view/' . $p['id']) ?>" class="btn btn-ghost">Cancel</a>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@2.29.1/dist/editorjs.min.js"></script>
<script>
// Simple WYSIWYG toolbar inline (avoid heavy dependency)
const ta = document.getElementById('wikiContent');
function wrap(open, close='') { const s=ta.selectionStart, e=ta.selectionEnd; ta.value = ta.value.substring(0,s)+open+ta.value.substring(s,e)+close+ta.value.substring(e); ta.focus(); ta.setSelectionRange(s+open.length, e+open.length); }
</script>
