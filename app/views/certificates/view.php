<?php /** @var array $data */
extract($data, EXTR_SKIP);
$c = $cert;
?>
<div class="breadcrumb">
  <a href="<?= url('certificates') ?>">My Certificates</a>
  <span class="material-icons">chevron_right</span>
  <span><?= e($c['course_title']) ?></span>
</div>

<div class="certificate-display">
  <div class="certificate-paper">
    <div class="cert-border">
      <div class="cert-corner cert-corner-tl"></div>
      <div class="cert-corner cert-corner-tr"></div>
      <div class="cert-corner cert-corner-bl"></div>
      <div class="cert-corner cert-corner-br"></div>

      <div class="cert-content">
        <div class="cert-seal">
          <span class="material-icons">workspace_premium</span>
        </div>
        <p class="cert-eyebrow"><?= e(APP_NAME) ?> · Certificate of Completion</p>
        <h1 class="cert-presented">This certificate is proudly presented to</h1>
        <h2 class="cert-name"><?= e($c['user_name']) ?></h2>
        <div class="cert-divider"></div>
        <p class="cert-body">for successfully completing all requirements of</p>
        <h3 class="cert-course"><?= e($c['course_title']) ?></h3>
        <p class="cert-body">with a final score of <strong><?= $c['final_score'] ?>%</strong></p>

        <div class="cert-foot">
          <div>
            <div class="cert-line"></div>
            <p class="cert-label">Date issued</p>
            <p class="cert-value"><?= formatDate($c['issued_at']) ?></p>
          </div>
          <div>
            <div class="cert-seal-mini"><span class="material-icons">verified</span></div>
          </div>
          <div>
            <div class="cert-line"></div>
            <p class="cert-label">Verification code</p>
            <p class="cert-value"><?= e($c['verify_code']) ?></p>
          </div>
        </div>
        <p class="cert-issuer">Issued by <?= e(APP_NAME) ?> · <?= e(APP_TEAM) ?></p>
      </div>
    </div>
  </div>
  <div class="text-center mt-3">
    <a href="<?= url('certificates/verify?code=' . $c['verify_code']) ?>" target="_blank" class="btn btn-secondary"><span class="material-icons">verified</span> Public verification link</a>
    <a href="<?= url('certificates') ?>" class="btn btn-ghost">Back to my certificates</a>
  </div>
</div>

<style>
.certificate-display { max-width: 900px; margin: 0 auto; }
.certificate-paper {
  background: linear-gradient(135deg, #FFFCF5, #FFF9EB);
  padding: 30px;
  border-radius: var(--radius);
  box-shadow: var(--shadow-lg);
}
.cert-border {
  position: relative;
  border: 3px double var(--primary);
  padding: 50px 40px;
  border-radius: 6px;
}
.cert-corner { position: absolute; width: 24px; height: 24px; border: 3px solid var(--accent); }
.cert-corner-tl { top: -3px; left: -3px; border-right: 0; border-bottom: 0; }
.cert-corner-tr { top: -3px; right: -3px; border-left: 0; border-bottom: 0; }
.cert-corner-bl { bottom: -3px; left: -3px; border-right: 0; border-top: 0; }
.cert-corner-br { bottom: -3px; right: -3px; border-left: 0; border-top: 0; }
.cert-content { text-align: center; }
.cert-seal {
  width: 70px; height: 70px; margin: 0 auto 18px;
  background: linear-gradient(135deg, var(--primary), var(--accent));
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: #fff; box-shadow: 0 8px 20px rgba(79,70,229,.3);
}
.cert-seal .material-icons { font-size: 38px; }
.cert-eyebrow { font-size: .78rem; text-transform: uppercase; letter-spacing: .15em; color: var(--text-muted); margin-bottom: 16px; }
.cert-presented { font-size: 1rem; font-weight: 500; color: var(--text-muted); margin-bottom: 10px; }
.cert-name { font-family: 'Sora', var(--font); font-size: 2.4rem; color: var(--primary); margin-bottom: 16px; font-weight: 800; }
.cert-divider { width: 80px; height: 3px; background: var(--accent); margin: 0 auto 18px; }
.cert-body { font-size: .95rem; color: var(--text-muted); margin-bottom: 8px; }
.cert-course { font-family: 'Sora', var(--font); font-size: 1.5rem; color: var(--text); margin-bottom: 12px; font-weight: 700; }
.cert-foot { display: flex; justify-content: space-around; align-items: flex-end; margin-top: 40px; gap: 30px; }
.cert-line { width: 160px; height: 1px; background: var(--text-muted); margin-bottom: 6px; }
.cert-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; color: var(--text-muted); }
.cert-value { font-weight: 700; font-size: .9rem; color: var(--text); }
.cert-seal-mini { width: 50px; height: 50px; background: var(--success); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.cert-seal-mini .material-icons { font-size: 26px; }
.cert-issuer { margin-top: 30px; font-size: .82rem; color: var(--text-muted); font-style: italic; }
@media print {
  .breadcrumb, .text-center { display: none !important; }
  body { background: #fff !important; }
  .certificate-paper { box-shadow: none; }
}
</style>
