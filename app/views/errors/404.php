<?php /** @var array $data */
extract($data, EXTR_SKIP);
$msg = $message ?? 'The page you are looking for does not exist.';
?>
<div class="empty" style="padding:80px 20px;">
  <div style="font-family:var(--font-display);font-size:5rem;color:var(--primary);font-weight:800;line-height:1;">404</div>
  <h2 style="margin-top:14px;">Page not found</h2>
  <p><?= e($msg) ?></p>
  <a href="<?= url('') ?>" class="btn btn-primary"><span class="material-icons">home</span> Back to dashboard</a>
</div>
