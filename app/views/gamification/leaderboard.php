<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>Leaderboard</h1>
    <p><?= $course ? 'Top learners in ' . e($course['title']) : 'Top learners across the platform' ?>.</p>
  </div>
  <div class="actions">
    <?php if ($course): ?>
      <a href="<?= url('gamification/leaderboard') ?>" class="btn btn-secondary">All users</a>
    <?php endif; ?>
    <a href="<?= url('gamification') ?>" class="btn btn-ghost">My achievements</a>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th style="width:60px;">Rank</th>
          <th>User</th>
          <th>Level</th>
          <th>Total XP</th>
          <th>Streak</th>
          <th>Role</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($board)): ?>
          <tr><td colspan="6" class="text-center text-muted" style="padding:30px;">No users yet.</td></tr>
        <?php else: foreach ($board as $i => $u): ?>
          <tr <?= $u['id'] == Auth::id() ? 'style="background:var(--primary-light);"' : '' ?>>
            <td style="font-family:var(--font-display);font-size:1.1rem;font-weight:800;">
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
                <div>
                  <strong><?= e($u['full_name']) ?></strong>
                  <?php if ($u['id'] == Auth::id()): ?><span class="badge badge-primary" style="margin-left:6px;">You</span><?php endif; ?>
                </div>
              </div>
            </td>
            <td><span class="badge badge-primary">Lv <?= (int) $u['level'] ?></span></td>
            <td><strong style="font-family:var(--font-display);"><?= number_format($u['xp']) ?></strong> XP</td>
            <td><?= (int) $u['streak'] ?> 🔥</td>
            <td><span class="badge role-badge <?= e($u['role']) ?>"><?= ucfirst($u['role']) ?></span></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
