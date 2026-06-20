<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="breadcrumb">
  <a href="<?= url('messages') ?>">Messages</a>
  <span class="material-icons">chevron_right</span>
  <span>New message</span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1>New Message</h1>
    <p>Send a direct message to a teacher or classmate.</p>
  </div>
</div>

<div class="card card-pad" style="max-width:700px;">
  <form method="post" action="<?= url('messages/new') ?>">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
    <div class="form-group">
      <label>Recipients</label>
      <div class="recipient-picker" style="max-height:240px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px;">
        <?php if (empty($users)): ?>
          <p class="text-muted" style="padding:14px;text-align:center;">No other users available.</p>
        <?php else: foreach ($users as $u): ?>
          <label class="checkbox-group" style="padding:6px 0;display:flex;width:100%;border-bottom:1px solid var(--surface-2);">
            <input type="checkbox" name="recipients[]" value="<?= $u['id'] ?>" style="margin-right:10px;">
            <span class="avatar sm" style="background:<?= avatarColor($u['full_name']) ?>;margin-right:10px;">
              <?php if (!empty($u['avatar'])): ?><img src="<?= uploadUrl($u['avatar']) ?>" alt=""><?php else: ?><?= initials($u['full_name']) ?><?php endif; ?>
            </span>
            <div style="flex:1;">
              <strong style="font-size:.88rem;"><?= e($u['full_name']) ?></strong>
              <span class="badge role-badge <?= e($u['role']) ?>" style="font-size:.6rem;margin-left:6px;"><?= ucfirst($u['role']) ?></span>
              <div class="text-muted" style="font-size:.72rem;"><?= e($u['email']) ?></div>
            </div>
          </label>
        <?php endforeach; endif; ?>
      </div>
      <span class="hint">Select multiple recipients to start a group conversation.</span>
    </div>
    <div class="form-group">
      <label>Message</label>
      <textarea name="body" rows="5" required placeholder="Write your message..."></textarea>
    </div>
    <button type="submit" class="btn btn-primary btn-lg"><span class="material-icons">send</span> Send message</button>
    <a href="<?= url('messages') ?>" class="btn btn-ghost">Cancel</a>
  </form>
</div>
