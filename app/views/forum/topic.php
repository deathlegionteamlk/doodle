<?php /** @var array $data */
extract($data, EXTR_SKIP);
$t = $topic;
?>
<div class="breadcrumb">
  <a href="<?= url('course/view/' . $t['course_id']) ?>"><?= e($t['course_title']) ?></a>
  <span class="material-icons">chevron_right</span>
  <a href="<?= url('forum/course/' . $t['course_id']) ?>">Forum</a>
  <span class="material-icons">chevron_right</span>
  <span><?= e(truncate($t['title'], 40)) ?></span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1><?= e($t['title']) ?></h1>
    <p>
      <?php if ($t['pinned']): ?><span class="badge badge-warning">Pinned</span><?php endif; ?>
      <?php if ($t['locked']): ?><span class="badge badge-danger">Locked</span><?php endif; ?>
      <span class="text-muted" style="margin-left:8px;"><?= (int) $t['views'] ?> views · <?= (int) $t['reply_count'] ?> replies</span>
    </p>
  </div>
  <?php if ($canModerate): ?>
    <div class="actions">
      <a href="<?= url('forum/togglePin/' . $t['id']) ?>" class="btn btn-secondary btn-sm">
        <span class="material-icons"><?= $t['pinned'] ? 'push_pin' : 'outlined_flag' ?></span>
        <?= $t['pinned'] ? 'Unpin' : 'Pin' ?>
      </a>
      <a href="<?= url('forum/toggleLock/' . $t['id']) ?>" class="btn btn-secondary btn-sm">
        <span class="material-icons"><?= $t['locked'] ? 'lock_open' : 'lock' ?></span>
        <?= $t['locked'] ? 'Unlock' : 'Lock' ?>
      </a>
      <a href="<?= url('forum/deleteTopic/' . $t['id']) ?>" class="btn btn-danger btn-sm" data-confirm="Delete this topic and all replies?">
        <span class="material-icons">delete</span>
      </a>
    </div>
  <?php endif; ?>
</div>

<!-- Original post -->
<div class="post">
  <span class="avatar lg" style="background:<?= avatarColor($t['author_name']) ?>;">
    <?php if (!empty($t['author_avatar'])): ?><img src="<?= uploadUrl($t['author_avatar']) ?>" alt=""><?php else: ?><?= initials($t['author_name']) ?><?php endif; ?>
  </span>
  <div style="flex:1;">
    <div class="post-meta">
      <span class="post-author"><?= e($t['author_name']) ?></span>
      <span class="badge role-badge <?= e($t['author_role']) ?>"><?= ucfirst($t['author_role']) ?></span>
      · <?= formatDateTime($t['created_at']) ?>
    </div>
    <div class="post-body">
      <?= nl2br(e($t['body'] ?? '')) ?>
    </div>
  </div>
</div>

<!-- Replies -->
<?php foreach ($posts as $p): ?>
  <div class="post">
    <span class="avatar" style="background:<?= avatarColor($p['author_name']) ?>;">
      <?php if (!empty($p['author_avatar'])): ?><img src="<?= uploadUrl($p['author_avatar']) ?>" alt=""><?php else: ?><?= initials($p['author_name']) ?><?php endif; ?>
    </span>
    <div style="flex:1;">
      <div class="post-meta">
        <span class="post-author"><?= e($p['author_name']) ?></span>
        <span class="badge role-badge <?= e($p['author_role']) ?>"><?= ucfirst($p['author_role']) ?></span>
        · <?= formatDateTime($p['created_at']) ?>
      </div>
      <div class="post-body">
        <?= nl2br(e($p['body'])) ?>
      </div>
      <?php if ($canModerate || $p['user_id'] == Auth::id()): ?>
      <div style="margin-top:6px;">
        <a href="<?= url('forum/deletePost/' . $p['id']) ?>" class="btn btn-ghost btn-sm" data-confirm="Delete this reply?">
          <span class="material-icons" style="font-size:14px;">delete</span> Delete
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>

<!-- Reply form -->
<?php if ($canReply): ?>
<div class="card card-pad" style="margin-top:20px;">
  <h3 style="margin-bottom: 12px;">Post a reply</h3>
  <form method="post" action="<?= url('forum/reply/' . $t['id']) ?>">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
    <div class="form-group">
      <textarea name="body" rows="4" placeholder="Write your reply..." required></textarea>
    </div>
    <button type="submit" class="btn btn-primary"><span class="material-icons">reply</span> Post reply</button>
  </form>
</div>
<?php else: ?>
<div class="alert alert-warning" style="margin-top:20px;">
  <span class="material-icons">lock</span>
  <div>This topic is locked. No new replies can be posted.</div>
</div>
<?php endif; ?>
