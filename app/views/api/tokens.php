<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>My API Tokens</h1>
    <p>Generate tokens to authenticate with the <?= e(APP_NAME) ?> REST API from mobile apps or scripts.</p>
  </div>
</div>

<?php if ($newToken): ?>
  <div class="alert alert-success">
    <span class="material-icons">check_circle</span>
    <div>
      <strong>New token created!</strong> Copy it now — for security, you won't be able to see it again.
      <pre style="margin-top:8px;background:var(--surface);padding:10px;border-radius:6px;overflow-x:auto;font-size:.85rem;"><?= e($newToken) ?></pre>
    </div>
  </div>
<?php endif; ?>

<div class="card card-pad mb-3">
  <h3 style="margin-bottom: 14px;">Generate new token</h3>
  <form method="post" action="<?= url('api/tokens') ?>" style="display:flex;gap:10px;">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
    <input type="text" name="name" placeholder="Token name (e.g., Mobile app)" required style="flex:1;">
    <button type="submit" class="btn btn-primary"><span class="material-icons">add</span> Generate</button>
  </form>
</div>

<div class="card">
  <div class="card-head"><h3>Your active tokens</h3></div>
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr><th>Name</th><th>Token (partial)</th><th>Last used</th><th>Expires</th><th>Created</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($tokens)): ?>
          <tr><td colspan="6" class="text-center text-muted" style="padding:30px;">No tokens yet.</td></tr>
        <?php else: foreach ($tokens as $t): ?>
          <tr>
            <td><strong><?= e($t['name']) ?></strong></td>
            <td><code style="font-size:.78rem;"><?= e(substr($t['token'], 0, 12)) ?>…<?= e(substr($t['token'], -6)) ?></code></td>
            <td class="text-muted" style="font-size:.82rem;"><?= $t['last_used'] ? timeAgo($t['last_used']) : 'Never' ?></td>
            <td class="text-muted" style="font-size:.82rem;"><?= formatDate($t['expires_at']) ?></td>
            <td class="text-muted" style="font-size:.82rem;"><?= timeAgo($t['created_at']) ?></td>
            <td class="text-right">
              <a href="<?= url('api/revokeToken/' . $t['id']) ?>" class="btn btn-danger btn-sm" data-confirm="Revoke this token? Any apps using it will stop working."><span class="material-icons">delete</span></a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card card-pad mt-3">
  <h3 style="margin-bottom: 12px;">API documentation</h3>
  <p style="font-size:.88rem;line-height:1.6;color:var(--text-muted);margin-bottom:14px;">
    All endpoints are JSON-formatted. Pass your token via the <code>X-API-Token</code> header.
    Base URL: <code><?= e(BASE_URL) ?>/index.php?r=api/&lt;endpoint&gt;</code>
  </p>
  <div class="table-wrap">
    <table class="data" style="font-size:.85rem;">
      <thead><tr><th>Method</th><th>Endpoint</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td><span class="badge badge-success">POST</span></td><td><code>api/auth</code></td><td>Exchange username/password for a token</td></tr>
        <tr><td><span class="badge">GET</span></td><td><code>api/me</code></td><td>Current authenticated user info</td></tr>
        <tr><td><span class="badge">GET</span></td><td><code>api/courses</code></td><td>List published courses</td></tr>
        <tr><td><span class="badge">GET</span></td><td><code>api/courses/:id</code></td><td>Course detail</td></tr>
        <tr><td><span class="badge">GET</span></td><td><code>api/my/courses</code></td><td>Student's enrolled courses</td></tr>
        <tr><td><span class="badge">GET</span></td><td><code>api/my/notifications</code></td><td>Recent notifications</td></tr>
        <tr><td><span class="badge badge-success">POST</span></td><td><code>api/lessons/:id/complete</code></td><td>Mark lesson complete</td></tr>
        <tr><td><span class="badge">GET</span></td><td><code>api/flashcards/due</code></td><td>Due flashcards for review</td></tr>
        <tr><td><span class="badge">GET</span></td><td><code>api/leaderboard</code></td><td>Top 20 users by XP</td></tr>
      </tbody>
    </table>
  </div>

  <h4 style="margin: 20px 0 8px;">Example: authenticate</h4>
  <pre style="background:#1E1B4B;color:#E2E8F0;padding:14px;border-radius:6px;overflow-x:auto;font-size:.82rem;">curl -X POST <?= e(BASE_URL) ?>/index.php?r=api/auth \
  -d "username=alice" \
  -d "password=secret" \
  -d "device_name=iPhone"</pre>

  <h4 style="margin: 20px 0 8px;">Example: authenticated request</h4>
  <pre style="background:#1E1B4B;color:#E2E8F0;padding:14px;border-radius:6px;overflow-x:auto;font-size:.82rem;">curl <?= e(BASE_URL) ?>/index.php?r=api/my/courses \
  -H "X-API-Token: YOUR_TOKEN_HERE"</pre>
</div>
