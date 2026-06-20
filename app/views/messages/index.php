<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>Messages</h1>
    <p>Direct messages with teachers and classmates.</p>
  </div>
  <div class="actions">
    <a href="<?= url('messages/new') ?>" class="btn btn-primary"><span class="material-icons">edit</span> New message</a>
  </div>
</div>

<?php if (empty($threads)): ?>
  <div class="card card-pad empty">
    <span class="material-icons">mail</span>
    <h3>No conversations yet</h3>
    <p>Send a message to a teacher or classmate to start a conversation.</p>
    <a href="<?= url('messages/new') ?>" class="btn btn-primary"><span class="material-icons">edit</span> Start a conversation</a>
  </div>
<?php else: ?>
  <div class="card">
    <?php foreach ($threads as $t): ?>
      <a href="<?= url('messages/view/' . $t['id']) ?>" class="forum-topic" style="text-decoration:none;color:inherit;">
        <div class="topic-icon" style="background:<?= $t['unread'] > 0 ? 'var(--primary-light)' : 'var(--surface-2)' ?>;color:<?= $t['unread'] > 0 ? 'var(--primary)' : 'var(--text-muted)' ?>;">
          <span class="material-icons"><?= $t['type'] === 'group' ? 'group' : 'person' ?></span>
        </div>
        <div class="topic-body">
          <div class="topic-title">
            <?= $t['name'] ? e($t['name']) : e(implode(', ', array_map(fn($p) => $p['full_name'], array_filter($t['participants'], fn($p) => $p['id'] != Auth::id())))) ?>
            <?php if ($t['unread'] > 0): ?>
              <span class="badge badge-primary" style="margin-left:6px;"><?= $t['unread'] ?> new</span>
            <?php endif; ?>
          </div>
          <div class="topic-meta">
            <?= $t['last_msg'] ? e(truncate($t['last_msg'], 80)) : 'No messages yet' ?>
            · <?= $t['last_msg_at'] ? timeAgo($t['last_msg_at']) : timeAgo($t['created_at']) ?>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
