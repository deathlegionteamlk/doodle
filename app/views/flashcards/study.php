<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="breadcrumb">
  <a href="<?= url('flashcards') ?>">Flashcards</a>
  <span class="material-icons">chevron_right</span>
  <a href="<?= url('flashcards/view/' . $deck['id']) ?>"><?= e($deck['title']) ?></a>
  <span class="material-icons">chevron_right</span>
  <span>Study</span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1>Studying: <?= e($deck['title']) ?></h1>
    <p><?= count($cards) ?> card(s) in this session · <span id="progress">0</span>/<?= count($cards) ?> reviewed</p>
  </div>
  <div class="actions">
    <a href="<?= url('flashcards/view/' . $deck['id']) ?>" class="btn btn-ghost">Exit</a>
  </div>
</div>

<div class="flashcard-study text-center">
  <div class="flashcard" id="flashcard" onclick="flipCard()">
    <div class="flashcard-inner">
      <div class="flashcard-front">
        <span class="flashcard-label">Question</span>
        <div class="flashcard-text" id="cardFront"></div>
        <span class="flashcard-hint">Click to flip</span>
      </div>
      <div class="flashcard-back">
        <span class="flashcard-label">Answer</span>
        <div class="flashcard-text" id="cardBack"></div>
        <span class="flashcard-hint">How did you do?</span>
      </div>
    </div>
  </div>

  <div id="ratingButtons" class="rating-buttons" style="display:none;">
    <p class="text-muted" style="margin-bottom:14px;">How well did you know this?</p>
    <div class="flex gap-sm" style="justify-content:center;">
      <button class="btn btn-danger" onclick="rateCard(1)"><span class="material-icons">close</span> Again</button>
      <button class="btn btn-secondary" onclick="rateCard(2)"><span class="material-icons">priority_high</span> Hard</button>
      <button class="btn btn-primary" onclick="rateCard(3)"><span class="material-icons">check</span> Good</button>
      <button class="btn btn-success" onclick="rateCard(4)"><span class="material-icons">done_all</span> Easy</button>
    </div>
  </div>

  <div id="sessionComplete" style="display:none;padding:40px 20px;">
    <span class="material-icons" style="font-size:72px;color:var(--success);">celebration</span>
    <h2>Session complete!</h2>
    <p class="text-muted">You reviewed all due cards. Come back tomorrow for the next batch.</p>
    <a href="<?= url('flashcards/view/' . $deck['id']) ?>" class="btn btn-primary">Back to deck</a>
  </div>
</div>

<style>
.flashcard-study { max-width: 600px; margin: 0 auto; padding: 20px; }
.flashcard {
  perspective: 1000px;
  height: 320px;
  cursor: pointer;
  margin-bottom: 24px;
}
.flashcard-inner {
  position: relative; width: 100%; height: 100%;
  transition: transform 0.6s;
  transform-style: preserve-3d;
}
.flashcard.flipped .flashcard-inner { transform: rotateY(180deg); }
.flashcard-front, .flashcard-back {
  position: absolute; inset: 0;
  backface-visibility: hidden;
  border-radius: var(--radius);
  padding: 30px;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--border);
}
.flashcard-front { background: linear-gradient(135deg, #fff, var(--surface-2)); }
.flashcard-back { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; transform: rotateY(180deg); }
.flashcard-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; color: var(--text-muted); margin-bottom: 14px; }
.flashcard-back .flashcard-label { color: rgba(255,255,255,.7); }
.flashcard-text { font-size: 1.4rem; font-weight: 700; line-height: 1.4; text-align: center; }
.flashcard-hint { margin-top: 18px; font-size: .78rem; color: var(--text-light); }
.flashcard-back .flashcard-hint { color: rgba(255,255,255,.7); }
.rating-buttons { padding: 20px 0; }
</style>

<script>
const cards = <?= json_encode($cards) ?>;
let currentIdx = 0;
let reviewed = 0;

function showCard() {
  if (currentIdx >= cards.length) {
    document.getElementById('flashcard').style.display = 'none';
    document.getElementById('ratingButtons').style.display = 'none';
    document.getElementById('sessionComplete').style.display = 'block';
    return;
  }
  const c = cards[currentIdx];
  document.getElementById('cardFront').textContent = c.front;
  document.getElementById('cardBack').textContent = c.back;
  document.getElementById('flashcard').classList.remove('flipped');
  document.getElementById('ratingButtons').style.display = 'none';
}
function flipCard() {
  document.getElementById('flashcard').classList.toggle('flipped');
  if (document.getElementById('flashcard').classList.contains('flipped')) {
    document.getElementById('ratingButtons').style.display = 'block';
  }
}
function rateCard(rating) {
  const c = cards[currentIdx];
  fetch('<?= url('flashcard/review') ?>', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'review_id=' + c.review_id + '&rating=' + rating + '&<?= CSRF_TOKEN_NAME ?>=<?= CSRF::token() ?>'
  }).then(r => r.json()).then(() => {
    reviewed++;
    document.getElementById('progress').textContent = reviewed;
    currentIdx++;
    showCard();
  });
}
showCard();
</script>
