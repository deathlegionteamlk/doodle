<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block"><h1>Backups</h1><p>Create and restore database snapshots.</p></div>
  <div class="actions">
    <form method="post" action="<?= url('backup/create') ?>" style="display:inline;">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
      <button class="btn btn-primary"><span class="material-icons">backup</span> Create backup now</button>
    </form>
  </div>
</div>

<?php if (DB_TYPE !== 'sqlite'): ?>
  <div class="alert alert-warning"><span class="material-icons">warning</span><div>Backups currently only support SQLite. For MySQL, use <code>mysqldump</code> from your server's command line.</div></div>
<?php endif; ?>

<?php if (empty($backups)): ?>
  <div class="card card-pad empty"><span class="material-icons">backup</span><h3>No backups yet</h3><p>Create your first backup to safeguard your data.</p></div>
<?php else: ?>
  <div class="card">
    <div class="table-wrap"><table class="data">
      <thead><tr><th>Filename</th><th>Size</th><th>Created by</th><th>Date</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($backups as $b): ?>
          <tr>
            <td><code><?= e($b['filename']) ?></code></td>
            <td><?= humanFileSize((int)$b['file_size']) ?></td>
            <td><?= e($b['creator_name'] ?? '—') ?></td>
            <td class="text-muted" style="font-size:.82rem;"><?= timeAgo($b['created_at']) ?></td>
            <td class="text-right">
              <a href="<?= url('backup/download/' . $b['id']) ?>" class="btn btn-secondary btn-sm"><span class="material-icons">download</span></a>
              <a href="<?= url('backup/delete/' . $b['id']) ?>" class="btn btn-danger btn-sm" data-confirm="Delete this backup?"><span class="material-icons">delete</span></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
<?php endif; ?>
