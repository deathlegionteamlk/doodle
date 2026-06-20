<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>Flashcards</h1>
    <p>Spaced-repetition flashcards with SM-2 algorithm. Study smarter, not harder.</p>
  </div>
  <div class="actions">
    <a href="<?= url('flashcards/create') ?>" class="btn btn-secondary"><span class="material-icons">add</span> New deck</a>
  </div>
</div>

<?php if ($dueCount > 0): ?>
  <div class="alert alert-warning" style="display:flex;align-items:center;gap:14px;">
    <span class="material-icons" style="font-size:32px;">campaign</span>
    <div style="flex:1;">
      <strong><?= $dueCount ?> flashcard(s) due for review today!</strong>
      <p style="margin:0;font-size:.85rem;">Spaced repetition works best when you stay current. Review now to keep your streak.</p>
    </div>
  </div>
<?php endif; ?>

<?php if (empty($decks)): ?>
  <div class="card card-pad empty">
    <span class="material-icons">style</span>
    <h3>No flashcard decks yet</h3>
    <p>Create your own deck, or auto-generate one from any course's lessons using the AI assistant.</p>
    <a href="<?= url('flashcards/create') ?>" class="btn btn-primary"><span class="material-icons">add</span> Create a deck</a>
  </div>
<?php else: ?>
  <div class="grid grid-3">
    <?php foreach ($decks as $d): ?>
      <div class="card card-pad">
        <div class="flex-between mb-1">
          <h3 style="font-size:1.05rem;"><?= e($d['title']) ?></h3>
          <?php if ($d['is_ai_generated']): ?>
            <span class="badge badge-accent" title="AI-generated"><span class="material-icons" style="font-size:12px;vertical-align:middle;">auto_awesome</span> AI</span>
          <?php endif; ?>
        </div>
        <?php if ($d['course_title']): ?>
          <span class="badge badge-primary" style="margin-bottom:8px;"><?= e($d['course_title']) ?></span>
        <?php endif; ?>
        <?php if (!empty($d['description'])): ?>
          <p class="text-muted" style="font-size:.85rem;margin-bottom:12px;"><?= e(truncate($d['description'], 100)) ?></p>
        <?php endif; ?>
        <div class="flex gap-lg mb-2" style="font-size:.82rem;color:var(--text-muted);">
          <span><span class="material-icons" style="font-size:14px;vertical-align:middle;">style</span> <?= $d['card_count'] ?> cards</span>
          <?php if ($d['due_count'] > 0): ?>
            <span class="badge badge-warning"><?= $d['due_count'] ?> due</span>
          <?php endif; ?>
        </div>
        <div class="flex gap-sm">
          <?php if ($d['card_count'] > 0): ?>
            <a href="<?= url('flashcards/study/' . $d['id']) ?>" class="btn btn-primary btn-sm" style="flex:1;"><span class="material-icons">school</span> Study</a>
          <?php endif; ?>
          <a href="<?= url('flashcards/view/' . $d['id']) ?>" class="btn btn-secondary btn-sm">View</a>
          <?php if ($d['user_id'] == Auth::id() || Auth::isAdmin()): ?>
            <a href="<?= url('flashcards/delete/' . $d['id']) ?>" class="btn btn-danger btn-sm" data-confirm="Delete this deck?"><span class="material-icons">delete</span></a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
