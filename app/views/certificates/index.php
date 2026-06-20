<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>My Certificates</h1>
    <p>Certificates you've earned through course completion.</p>
  </div>
  <div class="actions">
    <a href="<?= url('certificates/verify') ?>" class="btn btn-secondary"><span class="material-icons">verified</span> Verify a certificate</a>
  </div>
</div>

<?php if (empty($certificates)): ?>
  <div class="card card-pad empty">
    <span class="material-icons">workspace_premium</span>
    <h3>No certificates yet</h3>
    <p>Complete a course with a passing grade to earn your first certificate.</p>
    <a href="<?= url('catalog') ?>" class="btn btn-primary">Browse courses</a>
  </div>
<?php else: ?>
  <div class="grid grid-2">
    <?php foreach ($certificates as $c): ?>
      <div class="card card-pad cert-card" style="background: linear-gradient(135deg, #fff 0%, #FAFAFC 100%); border: 2px solid var(--primary);">
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;">
          <div style="width:54px;height:54px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;">
            <span class="material-icons" style="font-size:28px;">workspace_premium</span>
          </div>
          <div>
            <h3 style="margin:0;font-size:1.05rem;"><?= e($c['course_title']) ?></h3>
            <p class="text-muted" style="font-size:.78rem;margin:2px 0 0;">Issued <?= formatDate($c['issued_at']) ?></p>
          </div>
        </div>
        <div style="padding:14px 0;border-top:1px solid var(--border);border-bottom:1px solid var(--border);margin-bottom:14px;">
          <div class="flex-between" style="font-size:.85rem;">
            <span class="text-muted">Final score</span>
            <strong style="font-size:1.1rem;color:var(--primary);"><?= $c['final_score'] ?>%</strong>
          </div>
        </div>
        <div class="text-muted" style="font-size:.78rem;margin-bottom:10px;">
          Verification code: <code style="background:var(--surface-2);padding:2px 6px;border-radius:4px;"><?= e($c['verify_code']) ?></code>
        </div>
        <div class="flex gap-sm">
          <a href="<?= url('certificates/view/' . $c['id']) ?>" class="btn btn-primary btn-sm" style="flex:1;"><span class="material-icons">visibility</span> View</a>
          <a href="<?= url('certificates/verify?code=' . $c['verify_code']) ?>" target="_blank" class="btn btn-secondary btn-sm">Verify</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
