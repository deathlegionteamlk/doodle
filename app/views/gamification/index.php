<?php /** @var array $data */
extract($data, EXTR_SKIP);
$st = $stats;
$lp = $st['level_progress'];
?>
<div class="page-head">
  <div class="title-block">
    <h1>My Achievements</h1>
    <p>Track your progress, earn badges, and climb the leaderboard.</p>
  </div>
  <div class="actions">
    <a href="<?= url('gamification/leaderboard') ?>" class="btn btn-secondary"><span class="material-icons">leaderboard</span> Leaderboard</a>
    <a href="<?= url('gamification/badges') ?>" class="btn btn-primary"><span class="material-icons">emoji_events</span> All badges</a>
  </div>
</div>

<div class="grid grid-4 mb-3">
  <div class="stat primary">
    <div>
      <div class="stat-label">Total XP</div>
      <div class="stat-value"><?= number_format($st['xp']) ?></div>
      <div class="stat-delta">Level <?= $st['level'] ?></div>
    </div>
    <div class="stat-icon"><span class="material-icons">bolt</span></div>
  </div>
  <div class="stat success">
    <div>
      <div class="stat-label">Current Streak</div>
      <div class="stat-value"><?= $st['current_streak'] ?> <span style="font-size:1.2rem;">🔥</span></div>
      <div class="stat-delta">Longest: <?= $st['longest_streak'] ?> days</div>
    </div>
    <div class="stat-icon"><span class="material-icons">local_fire_department</span></div>
  </div>
  <div class="stat accent">
    <div>
      <div class="stat-label">Badges Earned</div>
      <div class="stat-value"><?= $st['badges_earned'] ?>/<?= $st['badges_total'] ?></div>
      <div class="stat-delta"><?= $st['badges_total'] - $st['badges_earned'] ?> to unlock</div>
    </div>
    <div class="stat-icon"><span class="material-icons">emoji_events</span></div>
  </div>
  <div class="stat info">
    <div>
      <div class="stat-label">Leaderboard Rank</div>
      <div class="stat-value"><?= $rank > 0 ? '#' . $rank : '—' ?></div>
      <div class="stat-delta">of all active users</div>
    </div>
    <div class="stat-icon"><span class="material-icons">leaderboard</span></div>
  </div>
</div>

<div class="grid grid-2" style="grid-template-columns: 1fr 1fr; gap: 24px; align-items: flex-start;">
  <div class="card card-pad">
    <h3 style="margin-bottom: 14px;">Level Progress</h3>
    <div class="text-center" style="padding:20px 0;">
      <div style="display:inline-flex;align-items:center;justify-content:center;width:140px;height:140px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));color:#fff;font-family:var(--font-display);font-weight:800;font-size:3rem;box-shadow:0 10px 30px rgba(79,70,229,.3);">
        <?= $st['level'] ?>
      </div>
      <div style="margin-top:14px;font-size:1.1rem;font-weight:700;">Level <?= $st['level'] ?></div>
      <div class="text-muted" style="font-size:.88rem;"><?= number_format($lp['current_xp']) ?> / <?= number_format($lp['next_level_xp']) ?> XP</div>
    </div>
    <div class="progress progress-lg" style="margin:18px 0;">
      <div class="progress-bar" style="width:<?= $lp['progress_pct'] ?>%;"></div>
    </div>
    <div class="flex-between text-muted" style="font-size:.82rem;">
      <span>Level <?= $st['level'] ?></span>
      <span><?= $lp['progress_pct'] ?>% to Level <?= $st['level'] + 1 ?></span>
      <span>Level <?= $st['level'] + 1 ?></span>
    </div>
    <p class="text-muted text-center" style="font-size:.85rem;margin-top:14px;">
      Just <strong><?= number_format($lp['xp_to_next']) ?> XP</strong> to reach Level <?= $st['level'] + 1 ?>!
    </p>
  </div>

  <div class="card">
    <div class="card-head">
      <h3>Recent Activity</h3>
    </div>
    <?php if (empty($st['recent_points'])): ?>
      <div class="empty" style="padding:24px;">
        <span class="material-icons" style="font-size:36px;">history</span>
        <p style="font-size:.85rem;">No activity yet. Complete a lesson or take a quiz to earn XP!</p>
      </div>
    <?php else: ?>
      <div style="max-height:340px;overflow-y:auto;">
        <?php foreach ($st['recent_points'] as $p): ?>
          <div class="flex-between" style="padding:10px 18px;border-bottom:1px solid var(--border);">
            <div>
              <div style="font-size:.9rem;font-weight:600;"><?= e(ucfirst($p['reason'])) ?></div>
              <div class="text-muted" style="font-size:.72rem;"><?= timeAgo($p['created_at']) ?></div>
            </div>
            <span class="badge badge-success" style="font-size:.85rem;">+<?= $p['points'] ?> XP</span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="card mt-3">
  <div class="card-head">
    <h3>Your Badges</h3>
    <a href="<?= url('gamification/badges') ?>" class="btn btn-ghost btn-sm">View all</a>
  </div>
  <div class="card-body">
    <div class="grid grid-4" style="gap:14px;">
      <?php foreach (array_slice($badges, 0, 8) as $b): ?>
        <div class="badge-card <?= $b['earned'] ? 'earned' : 'locked' ?>" style="text-align:center;padding:16px;border-radius:var(--radius);<?= $b['earned'] ? '' : 'opacity:.45;' ?>">
          <div class="badge-icon" style="width:54px;height:54px;border-radius:50%;background:<?= $b['color'] ?>;color:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;">
            <span class="material-icons" style="font-size:28px;"><?= e($b['icon']) ?></span>
          </div>
          <strong style="font-size:.85rem;display:block;"><?= e($b['name']) ?></strong>
          <small class="text-muted" style="font-size:.72rem;"><?= $b['earned'] ? timeAgo($b['awarded_at']) : 'Locked' ?></small>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="card mt-3">
  <div class="card-head">
    <h3>Top Learners</h3>
    <a href="<?= url('gamification/leaderboard') ?>" class="btn btn-ghost btn-sm">Full leaderboard</a>
  </div>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Rank</th><th>User</th><th>Level</th><th>XP</th><th>Streak</th></tr></thead>
      <tbody>
        <?php foreach ($leaderboard as $i => $u): ?>
          <tr <?= $u['id'] == Auth::id() ? 'style="background:var(--primary-light);"' : '' ?>>
            <td>
              <?php if ($i === 0): ?>🥇
              <?php elseif ($i === 1): ?>🥈
              <?php elseif ($i === 2): ?>🥉
              <?php else: ?><?= $i + 1 ?>
              <?php endif; ?>
            </td>
            <td>
              <div class="flex gap-sm">
                <span class="avatar sm" style="background:<?= avatarColor($u['full_name']) ?>;">
                  <?php if (!empty($u['avatar'])): ?><img src="<?= uploadUrl($u['avatar']) ?>" alt=""><?php else: ?><?= initials($u['full_name']) ?><?php endif; ?>
                </span>
                <strong><?= e($u['full_name']) ?></strong>
                <?php if ($u['id'] == Auth::id()): ?><span class="badge badge-primary">You</span><?php endif; ?>
              </div>
            </td>
            <td><?= (int) $u['level'] ?></td>
            <td><strong><?= number_format($u['xp']) ?></strong></td>
            <td><?= (int) $u['streak'] ?> 🔥</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
