<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="breadcrumb">
  <a href="<?= url('flashcards') ?>">Flashcards</a>
  <span class="material-icons">chevron_right</span>
  <span>Create deck</span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1>Create Flashcard Deck</h1>
    <p>Build your own study deck, or use the AI assistant to auto-generate one.</p>
  </div>
</div>

<div class="card card-pad">
  <form method="post" action="<?= url('flashcards/create') ?>">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
    <div class="form-grid">
      <div class="form-group">
        <label>Deck title</label>
        <input type="text" name="title" required placeholder="e.g., Spanish Vocabulary - Week 1">
      </div>
      <div class="form-group">
        <label>Course (optional)</label>
        <select name="course_id">
          <option value="0">— Standalone deck —</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= $c['id'] ?>"><?= e($c['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea name="description" rows="2" placeholder="What is this deck about?"></textarea>
    </div>
    <div class="form-group checkbox-group">
      <input type="checkbox" name="is_public" id="isPublic" value="1">
      <label for="isPublic">Make this deck public (visible to all users)</label>
    </div>

    <h3 style="margin: 24px 0 12px;">Cards</h3>
    <div id="cardsList">
      <div class="card-row">
        <div class="form-grid">
          <div class="form-group">
            <label>Front (question)</label>
            <input type="text" name="front[]" placeholder="What is...?">
          </div>
          <div class="form-group">
            <label>Back (answer)</label>
            <input type="text" name="back[]" placeholder="The answer is...">
          </div>
        </div>
      </div>
      <div class="card-row">
        <div class="form-grid">
          <div class="form-group">
            <label>Front</label>
            <input type="text" name="front[]" placeholder="What is...?">
          </div>
          <div class="form-group">
            <label>Back</label>
            <input type="text" name="back[]" placeholder="The answer is...">
          </div>
        </div>
      </div>
    </div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="addCardRow()"><span class="material-icons">add</span> Add card</button>

    <hr style="border:0;border-top:1px solid var(--border);margin:24px 0;">
    <button type="submit" class="btn btn-primary btn-lg"><span class="material-icons">save</span> Create deck</button>
    <a href="<?= url('flashcards') ?>" class="btn btn-ghost">Cancel</a>
  </form>
</div>

<script>
function addCardRow() {
  const div = document.createElement('div');
  div.className = 'card-row';
  div.innerHTML = `
    <div class="form-grid">
      <div class="form-group">
        <label>Front</label>
        <input type="text" name="front[]" placeholder="What is...?">
      </div>
      <div class="form-group">
        <label>Back</label>
        <input type="text" name="back[]" placeholder="The answer is...">
      </div>
    </div>
    <button type="button" class="btn btn-ghost btn-sm" onclick="this.parentElement.remove()" style="margin-bottom:14px;"><span class="material-icons">delete</span> Remove</button>
  `;
  document.getElementById('cardsList').appendChild(div);
}
</script>
