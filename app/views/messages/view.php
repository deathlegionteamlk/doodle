<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="breadcrumb">
  <a href="<?= url('messages') ?>">Messages</a>
  <span class="material-icons">chevron_right</span>
  <span>Conversation</span>
</div>

<div class="grid" style="grid-template-columns: 1fr 240px; gap: 18px; align-items: flex-start;">
  <div class="card" style="display:flex;flex-direction:column;height:calc(100vh - 200px);min-height:500px;">
    <div class="card-head" style="flex-shrink:0;">
      <h3>
        <?= $thread['name'] ? e($thread['name']) : e(implode(', ', array_map(fn($p) => $p['full_name'], array_filter($participants, fn($p) => $p['id'] != Auth::id())))) ?>
      </h3>
    </div>

    <div id="msgList" style="flex:1;overflow-y:auto;padding:18px;background:var(--surface-2);">
      <?php if (empty($messages)): ?>
        <div class="empty" style="padding:50px 20px;">
          <span class="material-icons" style="font-size:48px;color:var(--text-light);">chat_bubble_outline</span>
          <p style="font-size:.9rem;">Start the conversation!</p>
        </div>
      <?php else: foreach ($messages as $m): ?>
        <div class="chat-msg chat-msg-<?= $m['user_id'] == Auth::id() ? 'user' : 'other' ?>">
          <span class="avatar sm" style="background:<?= avatarColor($m['full_name']) ?>;">
            <?php if (!empty($m['avatar'])): ?><img src="<?= uploadUrl($m['avatar']) ?>" alt=""><?php else: ?><?= initials($m['full_name']) ?><?php endif; ?>
          </span>
          <div class="chat-bubble">
            <?php if ($m['user_id'] != Auth::id()): ?>
              <div class="chat-author"><?= e($m['full_name']) ?> <span class="badge role-badge <?= e($m['role']) ?>" style="font-size:.6rem;"><?= ucfirst($m['role']) ?></span></div>
            <?php endif; ?>
            <div class="chat-text"><?= nl2br(e($m['body'])) ?></div>
            <div class="chat-time"><?= timeAgo($m['created_at']) ?></div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <form class="chat-input" method="post" action="<?= url('messages/view/' . $thread['id']) ?>">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
      <textarea name="body" placeholder="Type a message..." rows="1" required></textarea>
      <button type="submit" class="btn btn-primary"><span class="material-icons">send</span></button>
    </form>
  </div>

  <aside>
    <div class="card card-pad">
      <h4 style="margin-bottom: 10px;">Participants (<?= count($participants) ?>)</h4>
      <?php foreach ($participants as $p): ?>
        <div class="flex gap-sm" style="padding:8px 0;border-bottom:1px solid var(--border);">
          <span class="avatar sm" style="background:<?= avatarColor($p['full_name']) ?>;">
            <?php if (!empty($p['avatar'])): ?><img src="<?= uploadUrl($p['avatar']) ?>" alt=""><?php else: ?><?= initials($p['full_name']) ?><?php endif; ?>
          </span>
          <div>
            <strong style="font-size:.85rem;"><?= e($p['full_name']) ?></strong>
            <div><span class="badge role-badge <?= e($p['role']) ?>" style="font-size:.6rem;"><?= ucfirst($p['role']) ?></span></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </aside>
</div>

<style>
.chat-msg { display: flex; gap: 10px; margin-bottom: 14px; align-items: flex-start; }
.chat-msg-user { flex-direction: row-reverse; }
.chat-bubble { background: var(--surface); padding: 10px 14px; border-radius: 14px; max-width: 75%; box-shadow: var(--shadow-sm); border: 1px solid var(--border); }
.chat-msg-user .chat-bubble { background: var(--primary); color: #fff; border-color: var(--primary); }
.chat-author { font-weight: 700; font-size: .78rem; margin-bottom: 2px; }
.chat-text { line-height: 1.5; font-size: .9rem; word-wrap: break-word; }
.chat-time { font-size: .68rem; color: var(--text-light); margin-top: 4px; }
.chat-msg-user .chat-time { color: rgba(255,255,255,.7); }
.chat-input { display: flex; gap: 10px; padding: 14px; border-top: 1px solid var(--border); background: var(--surface); }
.chat-input textarea { flex: 1; resize: none; padding: 10px 14px; border-radius: 24px; border: 1px solid var(--border); font-family: var(--font); font-size: .92rem; min-height: 42px; max-height: 120px; }
.chat-input button { border-radius: 50%; width: 42px; height: 42px; padding: 0; flex-shrink: 0; }
</style>

<script>
const msgList = document.getElementById('msgList');
if (msgList) msgList.scrollTop = msgList.scrollHeight;
const ta = document.querySelector('.chat-input textarea');
if (ta) {
  ta.addEventListener('input', () => { ta.style.height = 'auto'; ta.style.height = Math.min(ta.scrollHeight, 120) + 'px'; });
  ta.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); ta.form.submit(); }
  });
}
</script>
