<?php /** @var array $data */
extract($data, EXTR_SKIP);
$featured = $featured ?? [];
$stats = $stats ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(APP_NAME) ?> · Personalized Learning Platform</title>
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
</head>
<body style="background:#fff;">

<header class="landing-nav">
  <div class="container flex-between">
    <div class="brand">
      <div class="brand-mark">d</div>
      <div class="brand-text">
        <h1><?= e(APP_NAME) ?></h1>
        <p>by <?= e(APP_TEAM) ?></p>
      </div>
    </div>
    <nav class="flex gap-lg">
      <a href="<?= url('catalog') ?>">Catalog</a>
      <a href="<?= url('auth/login') ?>" class="btn btn-secondary">Sign in</a>
      <a href="<?= url('auth/register') ?>" class="btn btn-primary">Get started</a>
    </nav>
  </div>
</header>

<section class="hero">
  <div class="container">
    <div class="hero-content">
      <span class="hero-pill"><span class="material-icons">stars</span> Open-source LMS · GPL-3.0</span>
      <h1>Learning, reimagined.<br><span>by Death Legion Team.</span></h1>
      <p class="hero-sub">A complete, Moodle-like platform for schools and companies — built for educators, students, and administrators. Host it yourself, customize every line, scale to thousands.</p>
      <div class="hero-actions">
        <a href="<?= url('auth/register') ?>" class="btn btn-primary btn-lg">Start learning free <span class="material-icons">arrow_forward</span></a>
        <a href="<?= url('catalog') ?>" class="btn btn-secondary btn-lg">Browse catalog</a>
      </div>
      <div class="hero-stats">
        <div><strong><?= number_format($stats['courses']) ?></strong> Courses</div>
        <div><strong><?= number_format($stats['students']) ?></strong> Students</div>
        <div><strong><?= number_format($stats['teachers']) ?></strong> Educators</div>
        <div><strong><?= number_format($stats['lessons']) ?></strong> Lessons</div>
      </div>
    </div>
  </div>
</section>

<section class="container section">
  <div class="section-head">
    <h2>Featured courses</h2>
    <a href="<?= url('catalog') ?>" class="btn btn-secondary btn-sm">View all <span class="material-icons">arrow_forward</span></a>
  </div>
  <?php if (empty($featured)): ?>
    <div class="empty">
      <span class="material-icons">school</span>
      <h3>No courses published yet</h3>
      <p>Once teachers publish courses, they'll appear here for the world to discover.</p>
      <a href="<?= url('auth/register') ?>" class="btn btn-primary">Become a teacher</a>
    </div>
  <?php else: ?>
    <div class="grid grid-3">
      <?php foreach ($featured as $c): ?>
        <div class="course-card">
          <a href="<?= url('course/view/' . $c['id']) ?>" class="course-cover">
            <?php if (!empty($c['cover_image'])): ?>
              <img src="<?= uploadUrl($c['cover_image']) ?>" alt="">
            <?php else: ?>
              <?= strtoupper(substr($c['title'], 0, 1)) ?>
            <?php endif; ?>
            <span class="level-pill"><?= e($c['level']) ?></span>
          </a>
          <div class="course-body">
            <?php if (!empty($c['category_name'])): ?>
              <span class="category"><?= e($c['category_name']) ?></span>
            <?php endif; ?>
            <h3><a href="<?= url('course/view/' . $c['id']) ?>"><?= e($c['title']) ?></a></h3>
            <p class="desc"><?= e(truncate($c['description'] ?? '', 110)) ?></p>
            <div class="meta">
              <span class="teacher">
                <span class="avatar sm" style="background:<?= avatarColor($c['teacher_name']) ?>"><?= initials($c['teacher_name']) ?></span>
                <?= e($c['teacher_name']) ?>
              </span>
              <span><span class="material-icons" style="font-size:14px;vertical-align:middle;">group</span> <?= (int) $c['enroll_count'] ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="features">
  <div class="container">
    <div class="section-head text-center">
      <h2>Everything an institution needs</h2>
      <p class="text-muted">A complete toolkit for running courses online — for a single classroom or a global university.</p>
    </div>
    <div class="grid grid-3">
      <div class="feature">
        <div class="feat-icon primary"><span class="material-icons">co_present</span></div>
        <h3>For Teachers</h3>
        <p>Build modular courses, upload documents and videos, create quizzes with auto-grading, manage assignments, and engage students in course forums.</p>
      </div>
      <div class="feature">
        <div class="feat-icon success"><span class="material-icons">school</span></div>
        <h3>For Students</h3>
        <p>Browse the catalog, enroll in seconds, track your progress lesson-by-lesson, take quizzes, submit assignments, and watch your grades update live.</p>
      </div>
      <div class="feature">
        <div class="feat-icon accent"><span class="material-icons">admin_panel_settings</span></div>
        <h3>For Administrators</h3>
        <p>Manage every user, course, and category. Broadcast announcements. Configure the platform. Audit activity. All from one console.</p>
      </div>
      <div class="feature">
        <div class="feat-icon info"><span class="material-icons">quiz</span></div>
        <h3>Quiz Engine</h3>
        <p>Multiple-choice, true/false, and short-answer questions. Time limits, attempt limits, automatic grading, and per-question explanations.</p>
      </div>
      <div class="feature">
        <div class="feat-icon warning"><span class="material-icons">forum</span></div>
        <h3>Course Forums</h3>
        <p>Each course has its own discussion space. Pin important threads, lock resolved ones, and let the community teach itself.</p>
      </div>
      <div class="feature">
        <div class="feat-icon danger"><span class="material-icons">shield</span></div>
        <h3>Secure by Design</h3>
        <p>Bcrypt password hashing, CSRF protection on every form, role-based access control, prepared statements throughout — built to be internet-facing.</p>
      </div>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container text-center">
    <h2>Ready to start teaching?</h2>
    <p>Create a free account and publish your first course in minutes.</p>
    <a href="<?= url('auth/register') ?>" class="btn btn-primary btn-lg">Get started <span class="material-icons">arrow_forward</span></a>
  </div>
</section>

<footer class="landing-foot">
  <div class="container flex-between">
    <div>
      <p><strong><?= e(APP_NAME) ?></strong> v<?= e(APP_VERSION) ?> · open source under GPL-3.0</p>
      <p class="text-muted">Built by <?= e(APP_TEAM) ?></p>
    </div>
    <div class="flex gap-lg">
      <a href="<?= url('catalog') ?>">Catalog</a>
      <a href="<?= url('auth/login') ?>">Sign in</a>
      <a href="<?= url('auth/register') ?>">Register</a>
    </div>
  </div>
</footer>

<style>
.landing-nav { padding: 18px 0; border-bottom: 1px solid var(--border); position: sticky; top: 0; background: rgba(255,255,255,.95); backdrop-filter: blur(8px); z-index: 50; }
.landing-nav .brand { display: flex; align-items: center; gap: 12px; }
.landing-nav .brand-mark { width: 38px; height: 38px; background: linear-gradient(135deg, var(--primary), var(--accent)); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-family: var(--font-display); font-weight: 800; font-size: 1.2rem; }
.landing-nav .brand-text h1 { font-size: 1.2rem; line-height: 1; }
.landing-nav .brand-text p { font-size: .72rem; color: var(--text-muted); }
.landing-nav nav a { color: var(--text); font-weight: 500; }
.container { max-width: 1180px; margin: 0 auto; padding: 0 24px; }
.hero { padding: 80px 0 100px; background: radial-gradient(circle at top right, rgba(79,70,229,.08), transparent 60%), radial-gradient(circle at bottom left, rgba(236,72,153,.08), transparent 60%); }
.hero-content { max-width: 720px; }
.hero-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; background: var(--primary-light); color: var(--primary); border-radius: 30px; font-size: .82rem; font-weight: 600; margin-bottom: 20px; }
.hero-content h1 { font-size: 3.4rem; line-height: 1.05; margin-bottom: 18px; letter-spacing: -.02em; }
.hero-content h1 span { background: linear-gradient(135deg, var(--primary), var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.hero-sub { font-size: 1.15rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 30px; max-width: 580px; }
.hero-actions { display: flex; gap: 14px; margin-bottom: 50px; flex-wrap: wrap; }
.hero-stats { display: flex; gap: 38px; flex-wrap: wrap; }
.hero-stats div { font-size: .85rem; color: var(--text-muted); }
.hero-stats strong { display: block; font-size: 2rem; color: var(--text); font-family: var(--font-display); }
.section { padding: 60px 0; }
.section-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; gap: 16px; flex-wrap: wrap; }
.section-head.text-center { flex-direction: column; text-align: center; }
.section-head.text-center p { max-width: 580px; }
.features { background: var(--surface-2); padding: 80px 0; }
.feature { background: var(--surface); padding: 28px; border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border); }
.feat-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
.feat-icon .material-icons { font-size: 28px; }
.feat-icon.primary { background: var(--primary-light); color: var(--primary); }
.feat-icon.success { background: var(--success-light); color: var(--success); }
.feat-icon.accent  { background: #FCE7F3; color: var(--accent); }
.feat-icon.info    { background: var(--info-light); color: var(--info); }
.feat-icon.warning { background: var(--warning-light); color: var(--warning); }
.feat-icon.danger  { background: var(--danger-light); color: var(--danger); }
.feature h3 { margin-bottom: 8px; }
.feature p { color: var(--text-muted); font-size: .92rem; line-height: 1.6; }
.cta-band { padding: 70px 0; background: linear-gradient(135deg, #1E1B4B, #4F46E5); color: #fff; text-align: center; }
.cta-band h2 { color: #fff; margin-bottom: 8px; }
.cta-band p { color: rgba(255,255,255,.85); margin-bottom: 24px; }
.landing-foot { padding: 30px 0; border-top: 1px solid var(--border); font-size: .88rem; }
.landing-foot a { color: var(--text); }
@media (max-width: 720px) {
  .hero-content h1 { font-size: 2.2rem; }
  .hero-stats { gap: 24px; }
  .hero-stats strong { font-size: 1.5rem; }
}
</style>
</body>
</html>
