<?php /** @var array $data */
extract($data, EXTR_SKIP);
$c = $course;
?>
<div class="breadcrumb">
  <a href="<?= url('course/view/' . $c['id']) ?>"><?= e($c['title']) ?></a>
  <span class="material-icons">chevron_right</span>
  <span>Forum</span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1>Discussion Forum</h1>
    <p><?= e($c['title']) ?> · <?= count($topics) ?> topic(s)</p>
  </div>
  <div class="actions">
    <button class="btn btn-primary" onclick="document.getElementById('newTopicModal').style.display='flex'">
      <span class="material-icons">add</span> New topic
    </button>
  </div>
</div>

<?php if (empty($topics)): ?>
  <div class="card card-pad empty">
    <span class="material-icons">forum</span>
    <h3>No discussions yet</h3>
    <p>Be the first to start a conversation in this course's forum.</p>
    <button class="btn btn-primary" onclick="document.getElementById('newTopicModal').style.display='flex'"><span class="material-icons">add</span> Start a topic</button>
  </div>
<?php else: ?>
  <div class="card">
    <?php foreach ($topics as $t): ?>
      <div class="forum-topic <?= $t['pinned'] ? 'pinned' : '' ?> <?= $t['locked'] ? 'locked' : '' ?>">
        <div class="topic-icon">
          <span class="material-icons"><?= $t['pinned'] ? 'push_pin' : ($t['locked'] ? 'lock' : 'forum') ?></span>
        </div>
        <div class="topic-body">
          <div class="topic-title">
            <a href="<?= url('forum/topic/' . $t['id']) ?>"><?= e($t['title']) ?></a>
            <?php if ($t['pinned']): ?><span class="badge badge-warning" style="margin-left:6px;">Pinned</span><?php endif; ?>
            <?php if ($t['locked']): ?><span class="badge badge-danger" style="margin-left:6px;">Locked</span><?php endif; ?>
          </div>
          <div class="topic-meta">
            by <?= e($t['author_name']) ?> · <span class="badge role-badge <?= e($t['author_role']) ?>" style="font-size:.6rem;"><?= ucfirst($t['author_role']) ?></span>
            · <?= timeAgo($t['created_at']) ?>
          </div>
        </div>
        <div class="topic-stats">
          <strong><?= (int) $t['post_count'] ?></strong>
          <span>replies</span>
        </div>
        <div class="topic-stats">
          <strong><?= (int) $t['views'] ?></strong>
          <span>views</span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div id="newTopicModal" class="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px;">
  <div class="card" style="max-width:600px;width:100%;">
    <div class="card-head">
      <h3>New discussion topic</h3>
      <button class="btn btn-ghost btn-sm" onclick="document.getElementById('newTopicModal').style.display='none'"><span class="material-icons">close</span></button>
    </div>
    <div class="card-body">
      <form method="post" action="<?= url('forum/newTopic/' . $c['id']) ?>">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <div class="form-group">
          <label>Topic title</label>
          <input type="text" name="title" required>
        </div>
        <div class="form-group">
          <label>Initial post</label>
          <textarea name="body" rows="6" placeholder="What would you like to discuss?"></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg"><span class="material-icons">send</span> Post topic</button>
      </form>
    </div>
  </div>
</div>
