<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="breadcrumb"><a href="<?= url('course/view/' . $course['id']) ?>"><?= e($course['title']) ?></a><span class="material-icons">chevron_right</span><span>Groups</span></div>
<div class="page-head">
  <div class="title-block"><h1>Course Groups</h1><p><?= e($course['title']) ?> · form study groups</p></div>
  <div class="actions">
    <form method="post" action="<?= url('group/create/' . $course['id']) ?>" style="display:inline-flex;gap:8px;">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
      <input type="text" name="name" placeholder="Group name" required style="width:200px;">
      <button class="btn btn-primary"><span class="material-icons">add</span> Create</button>
    </form>
  </div>
</div>
<?php if (empty($groups)): ?>
  <div class="card card-pad empty"><span class="material-icons">groups</span><h3>No groups yet</h3><p>Form study groups to collaborate on assignments.</p></div>
<?php else: ?>
  <div class="grid grid-3">
    <?php foreach ($groups as $g): ?>
      <div class="card card-pad">
        <h3 style="font-size:1.05rem;margin-bottom:4px;"><?= e($g['name']) ?></h3>
        <?php if (!empty($g['description'])): ?><p class="text-muted" style="font-size:.82rem;"><?= e($g['description']) ?></p><?php endif; ?>
        <div class="flex gap-lg mt-2" style="font-size:.78rem;color:var(--text-muted);">
          <span><span class="material-icons" style="font-size:14px;vertical-align:middle;">group</span> <?= $g['member_count'] ?> members</span>
        </div>
        <div class="flex gap-sm mt-2">
          <?php if (in_array($g['id'], $myGroupIds)): ?>
            <a href="<?= url('group/leave/' . $g['id']) ?>" class="btn btn-secondary btn-sm">Leave</a>
          <?php else: ?>
            <a href="<?= url('group/join/' . $g['id']) ?>" class="btn btn-primary btn-sm">Join</a>
          <?php endif; ?>
          <?php if (Auth::ownsCourse($course['id']) || Auth::isAdmin()): ?>
            <a href="<?= url('group/delete/' . $g['id']) ?>" class="btn btn-danger btn-sm" data-confirm="Delete group?"><span class="material-icons">delete</span></a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
