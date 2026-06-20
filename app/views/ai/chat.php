<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="breadcrumb">
  <a href="<?= url('ai') ?>">AI Assistant</a>
  <span class="material-icons">chevron_right</span>
  <span><?= e($conv['title']) ?></span>
</div>

<div class="grid" style="grid-template-columns: 1fr 280px; gap: 18px; align-items: flex-start;">
  <div class="chat-window card" style="display:flex;flex-direction:column;height:calc(100vh - 200px);min-height:500px;">
    <div class="card-head" style="flex-shrink:0;">
      <div>
        <h3 style="display:flex;align-items:center;gap:8px;">
          <span class="material-icons" style="color:var(--primary);">smart_toy</span>
          <?= e($conv['title']) ?>
        </h3>
        <?php if ($course): ?>
          <p class="text-muted" style="font-size:.78rem;margin-top:2px;">Context: <?= e($course['title']) ?></p>
        <?php endif; ?>
      </div>
      <a href="<?= url('ai/delete/' . $conv['id']) ?>" class="btn btn-ghost btn-sm" data-confirm="Delete this conversation?"><span class="material-icons">delete</span></a>
    </div>

    <div class="chat-messages" id="chatMessages" style="flex:1;overflow-y:auto;padding:20px;">
      <?php if (empty($messages)): ?>
        <div class="chat-empty">
          <span class="material-icons" style="font-size:48px;color:var(--primary);">smart_toy</span>
          <h3>Hi! I'm your AI study buddy</h3>
          <p>Ask me anything about your course, or try:</p>
          <div class="chat-suggestions">
            <button class="suggestion-chip" onclick="fillPrompt('Summarize this course')">Summarize this course</button>
            <button class="suggestion-chip" onclick="fillPrompt('Give me a practice quiz')">Give me a practice quiz</button>
            <button class="suggestion-chip" onclick="fillPrompt('What should I study?')">What should I study?</button>
            <button class="suggestion-chip" onclick="fillPrompt('Make flashcards from the first lesson')">Make flashcards</button>
          </div>
        </div>
      <?php else: foreach ($messages as $m): ?>
        <div class="chat-msg chat-msg-<?= e($m['role']) ?>">
          <div class="chat-avatar">
            <?php if ($m['role'] === 'assistant'): ?>
              <span class="material-icons">smart_toy</span>
            <?php else: ?>
              <span class="material-icons">person</span>
            <?php endif; ?>
          </div>
          <div class="chat-bubble">
            <div class="chat-text"><?= nl2br(e($m['content'])) ?></div>
            <div class="chat-time"><?= timeAgo($m['created_at']) ?></div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <form class="chat-input" method="post" action="<?= url('ai/chat/' . $conv['id']) ?>">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
      <textarea name="message" id="messageInput" placeholder="Ask anything... (Shift+Enter for new line)" rows="1" required></textarea>
      <button type="submit" class="btn btn-primary"><span class="material-icons">send</span></button>
    </form>
  </div>

  <aside>
    <div class="card card-pad mb-3">
      <h4 style="margin-bottom: 10px;">Quick actions</h4>
      <div class="flex" style="flex-direction:column;gap:6px;">
        <?php if ($course): ?>
          <a href="<?= url('ai/generateQuiz/' . $course['id']) ?>" class="btn btn-secondary btn-sm btn-block" style="justify-content:flex-start;"><span class="material-icons">quiz</span> Generate practice quiz</a>
        <?php endif; ?>
        <a href="<?= url('ai') ?>" class="btn btn-ghost btn-sm btn-block" style="justify-content:flex-start;"><span class="material-icons">list</span> All conversations</a>
        <a href="<?= url('ai/new') ?>" class="btn btn-ghost btn-sm btn-block" style="justify-content:flex-start;"><span class="material-icons">add</span> New chat</a>
      </div>
    </div>

    <div class="card card-pad">
      <h4 style="margin-bottom: 10px;">Tips</h4>
      <ul style="font-size:.8rem;line-height:1.6;color:var(--text-muted);padding-left:18px;">
        <li>Be specific — name the lesson you want to summarize</li>
        <li>Ask follow-up questions to dive deeper</li>
        <li>The AI learns from your course's text lessons only</li>
        <li>All chats are private to you</li>
      </ul>
    </div>
  </aside>
</div>

<style>
.chat-messages { background: var(--surface-2); }
.chat-empty { text-align: center; padding: 40px 20px; color: var(--text-muted); }
.chat-empty h3 { margin-top: 12px; margin-bottom: 6px; }
.chat-suggestions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; margin-top: 16px; }
.suggestion-chip {
  background: var(--surface); border: 1px solid var(--border);
  padding: 6px 14px; border-radius: 20px; font-size: .85rem; cursor: pointer;
  color: var(--text); transition: all .15s;
}
.suggestion-chip:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
.chat-msg { display: flex; gap: 12px; margin-bottom: 16px; align-items: flex-start; }
.chat-msg-assistant { flex-direction: row; }
.chat-msg-user { flex-direction: row-reverse; }
.chat-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  background: var(--primary); color: #fff;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.chat-msg-user .chat-avatar { background: var(--text-muted); }
.chat-avatar .material-icons { font-size: 20px; }
.chat-bubble {
  background: var(--surface); padding: 12px 16px; border-radius: 14px;
  max-width: 75%; box-shadow: var(--shadow-sm); border: 1px solid var(--border);
}
.chat-msg-user .chat-bubble { background: var(--primary); color: #fff; border-color: var(--primary); }
.chat-text { line-height: 1.6; font-size: .92rem; word-wrap: break-word; }
.chat-msg-user .chat-text strong { color: #fff; }
.chat-time { font-size: .7rem; color: var(--text-light); margin-top: 4px; }
.chat-msg-user .chat-time { color: rgba(255,255,255,.7); }
.chat-input {
  display: flex; gap: 10px; padding: 14px; border-top: 1px solid var(--border); background: var(--surface);
}
.chat-input textarea {
  flex: 1; resize: none; padding: 10px 14px; border-radius: 24px;
  border: 1px solid var(--border); font-family: var(--font); font-size: .92rem;
  min-height: 42px; max-height: 140px;
}
.chat-input button { border-radius: 50%; width: 42px; height: 42px; padding: 0; flex-shrink: 0; }
</style>

<script>
function fillPrompt(text) {
  document.getElementById('messageInput').value = text;
  document.getElementById('messageInput').focus();
}
// Auto-scroll to bottom
const msgs = document.getElementById('chatMessages');
if (msgs) msgs.scrollTop = msgs.scrollHeight;
// Auto-grow textarea
const ta = document.getElementById('messageInput');
if (ta) {
  ta.addEventListener('input', () => {
    ta.style.height = 'auto';
    ta.style.height = Math.min(ta.scrollHeight, 140) + 'px';
  });
  // Enter to send (without shift)
  ta.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      ta.form.submit();
    }
  });
}
</script>
