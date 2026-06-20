<?php /** @var array $data */
extract($data, EXTR_SKIP);
$p = $page;
?>
<div class="breadcrumb"><a href="<?= url('course/view/' . $p['course_id']) ?>"><?= e($p['course_title']) ?></a><span class="material-icons">chevron_right</span><a href="<?= url('wiki/course/' . $p['course_id']) ?>">Wiki</a><span class="material-icons">chevron_right</span><span><?= e($p['title']) ?></span></div>
<div class="page-head">
  <div class="title-block"><h1><?= e($p['title']) ?></h1><p class="text-muted" style="font-size:.85rem;">by <?= e($p['creator_name']) ?> · last updated by <?= e($p['updater_name'] ?: $p['creator_name']) ?> · <?= timeAgo($p['updated_at'] ?: $p['created_at']) ?></p></div>
  <div class="actions"><a href="<?= url('wiki/edit/' . $p['id']) ?>" class="btn btn-primary"><span class="material-icons">edit</span> Edit</a></div>
</div>
<div class="grid" style="grid-template-columns: 2fr 1fr; gap:24px; align-items:flex-start;">
  <div class="card card-pad"><div class="lesson-content"><?= $p['content'] ?></div></div>
  <aside>
    <div class="card card-pad">
      <h4 style="margin-bottom:10px;">Revisions</h4>
      <?php if (empty($revisions)): ?><p class="text-muted" style="font-size:.85rem;">No revisions yet.</p><?php else: foreach (array_slice($revisions, 0, 10) as $r): ?>
        <div style="padding:6px 0;border-bottom:1px solid var(--border);font-size:.82rem;">
          <strong><?= e($r['editor_name']) ?></strong><br>
          <span class="text-muted"><?= timeAgo($r['edited_at']) ?></span>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </aside>
</div>
