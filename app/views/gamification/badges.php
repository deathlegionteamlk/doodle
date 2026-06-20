<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>All Badges</h1>
    <p><?= count(array_filter($badges, fn($b) => $b['earned'])) ?> of <?= count($badges) ?> unlocked. Keep learning to earn them all!</p>
  </div>
</div>

<div class="grid grid-3">
  <?php foreach ($badges as $b): ?>
    <div class="card card-pad <?= $b['earned'] ? '' : 'badge-locked' ?>" style="text-align:center;<?= $b['earned'] ? '' : 'opacity:.55;' ?>">
      <div class="badge-icon-lg" style="width:80px;height:80px;border-radius:50%;background:<?= $b['color'] ?>;color:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;box-shadow:<?= $b['earned'] ? '0 8px 20px ' . $b['color'] . '55' : 'none' ?>;">
        <span class="material-icons" style="font-size:40px;"><?= e($b['icon']) ?></span>
      </div>
      <h3 style="margin-bottom:6px;"><?= e($b['name']) ?></h3>
      <p class="text-muted" style="font-size:.85rem;margin-bottom:10px;"><?= e($b['description']) ?></p>
      <?php if ($b['earned']): ?>
        <span class="badge badge-success"><span class="material-icons" style="font-size:14px;vertical-align:middle;">check</span> Earned <?= timeAgo($b['awarded_at']) ?></span>
      <?php else: ?>
        <span class="badge"><span class="material-icons" style="font-size:14px;vertical-align:middle;">lock</span> Locked</span>
        <?php if ($b['points_required'] > 0): ?>
          <p class="text-muted" style="font-size:.72rem;margin-top:6px;">Worth <?= $b['points_required'] ?> XP</p>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
