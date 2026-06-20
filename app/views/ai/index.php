<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>AI Learning Assistant</h1>
    <p>Your built-in study buddy — answers questions, summarizes lessons, generates quizzes & flashcards, and recommends what to study next.</p>
  </div>
  <div class="actions">
    <a href="<?= url('ai/new') ?>" class="btn btn-primary"><span class="material-icons">add</span> New chat</a>
  </div>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px; align-items: flex-start;">
  <div>
    <h3 style="margin-bottom: 14px;">Recent conversations</h3>
    <?php if (empty($conversations)): ?>
      <div class="card card-pad empty">
        <span class="material-icons">smart_toy</span>
        <h3>No conversations yet</h3>
        <p>Start chatting with your AI assistant. It can answer questions from your course materials, summarize lessons, generate practice quizzes, and more.</p>
        <a href="<?= url('ai/new') ?>" class="btn btn-primary"><span class="material-icons">chat</span> Start a conversation</a>
      </div>
    <?php else: ?>
      <div class="grid grid-2">
        <?php foreach ($conversations as $c): ?>
          <a href="<?= url('ai/chat/' . $c['id']) ?>" class="card card-pad" style="text-decoration:none;color:inherit;">
            <div class="flex-between mb-1">
              <strong><?= e($c['title']) ?></strong>
              <span class="text-muted" style="font-size:.72rem;"><?= timeAgo($c['updated_at'] ?? $c['created_at']) ?></span>
            </div>
            <?php if ($c['course_title']): ?>
              <span class="badge badge-primary" style="font-size:.7rem;"><?= e($c['course_title']) ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <aside>
    <div class="card card-pad mb-3">
      <h3 style="margin-bottom: 12px;"><span class="material-icons" style="vertical-align:middle;color:var(--primary);">tips_and_updates</span> What I can do</h3>
      <ul style="font-size:.88rem;line-height:1.8;color:var(--text-muted);padding-left:18px;">
        <li><strong>Answer questions</strong> from your course materials</li>
        <li><strong>Summarize</strong> any lesson — say "summarize [title]"</li>
        <li><strong>Generate flashcards</strong> — say "make flashcards from [title]"</li>
        <li><strong>Generate a quiz</strong> — say "give me a practice quiz"</li>
        <li><strong>Study recommendations</strong> — say "what should I study?"</li>
        <li><strong>Smart grading</strong> for short-answer questions</li>
      </ul>
      <p style="font-size:.78rem;color:var(--text-light);margin-top:10px;">100% free, runs on your server, no API keys needed.</p>
    </div>

    <div class="card card-pad">
      <h3 style="margin-bottom: 12px;"><span class="material-icons" style="vertical-align:middle;color:var(--success);">lightbulb</span> Recommended for you</h3>
      <div style="font-size:.88rem;line-height:1.7;">
        <?php foreach ($recommendations as $r): ?>
          <div style="padding:8px 0;border-bottom:1px solid var(--border);">
            <?= nl2br(e($r)) ?>
          </div>
        <?php endforeach; ?>
      </div>
      <a href="<?= url('ai/new') ?>" class="btn btn-primary btn-block" style="margin-top:14px;"><span class="material-icons">chat</span> Ask the AI</a>
    </div>
  </aside>
</div>
