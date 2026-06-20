<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>Notifications</h1>
    <p>All your recent notifications from courses, submissions, and forum activity.</p>
  </div>
  <div class="actions">
    <a href="<?= url('notifications/markAllRead') ?>" class="btn btn-secondary"><span class="material-icons">done_all</span> Mark all read</a>
  </div>
</div>

<?php if (empty($notifs)): ?>
  <div class="card card-pad empty">
    <span class="material-icons">notifications_none</span>
    <h3>No notifications</h3>
    <p>You're all caught up. New notifications will appear here.</p>
  </div>
<?php else: ?>
  <div class="card">
    <?php foreach ($notifs as $n): ?>
      <a href="<?= $n['link'] ? url('notifications/markRead/' . $n['id']) : '#' ?>" class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>" style="display:block;text-decoration:none;color:inherit;">
        <div class="notif-title"><?= e($n['title']) ?> <?php if (!$n['is_read']): ?><span class="badge badge-primary" style="font-size:.6rem;">NEW</span><?php endif; ?></div>
        <div class="notif-body"><?= e($n['body']) ?></div>
        <div class="notif-time"><?= timeAgo($n['created_at']) ?></div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
