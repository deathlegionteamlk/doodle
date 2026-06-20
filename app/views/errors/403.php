<?php /** @var array $data */
extract($data, EXTR_SKIP);
$msg = $message ?? 'You do not have permission to access this page.';
?>
<div class="empty" style="padding:80px 20px;">
  <div style="font-family:var(--font-display);font-size:5rem;color:var(--danger);font-weight:800;line-height:1;">403</div>
  <h2 style="margin-top:14px;">Access denied</h2>
  <p><?= e($msg) ?></p>
  <a href="<?= url('') ?>" class="btn btn-primary"><span class="material-icons">home</span> Back to dashboard</a>
</div>
