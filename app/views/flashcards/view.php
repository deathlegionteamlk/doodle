<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="breadcrumb">
  <a href="<?= url('flashcards') ?>">Flashcards</a>
  <span class="material-icons">chevron_right</span>
  <span><?= e($deck['title']) ?></span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1><?= e($deck['title']) ?></h1>
    <p><?= count($cards) ?> cards · <?= $deck['is_ai_generated'] ? 'AI-generated' : 'Manual deck' ?></p>
  </div>
  <div class="actions">
    <?php if (count($cards) > 0): ?>
      <a href="<?= url('flashcards/study/' . $deck['id']) ?>" class="btn btn-primary"><span class="material-icons">school</span> Start studying</a>
    <?php endif; ?>
  </div>
</div>

<?php if (empty($cards)): ?>
  <div class="card card-pad empty">
    <span class="material-icons">style</span>
    <h3>This deck has no cards yet</h3>
    <p>Use the AI assistant to auto-generate cards, or ask the deck creator to add some.</p>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>#</th><th>Front</th><th>Back</th></tr></thead>
        <tbody>
          <?php foreach ($cards as $i => $c): ?>
            <tr>
              <td class="text-muted"><?= $i + 1 ?></td>
              <td><strong><?= e($c['front']) ?></strong></td>
              <td class="text-muted"><?= e(truncate($c['back'], 100)) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
