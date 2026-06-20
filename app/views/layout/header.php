<?php /** @var array $data */
/** @var array $currentUser */
extract($data, EXTR_SKIP);
$nav = $data['nav'] ?? '';
$currentUri = $data['currentUri'] ?? '';
$pendingCount = 0;
if (Auth::isTeacher()) {
    $pendingCount = Database::count('submissions s JOIN assignments a ON s.assignment_id = a.id JOIN courses c ON a.course_id = c.id', 'c.teacher_id = :tid AND s.status = :st', ['tid' => Auth::id(), 'st' => 'submitted']);
}
$notifCount = Auth::check() ? Database::count('notifications', 'user_id = :uid AND is_read = 0', ['uid' => Auth::id()]) : 0;
$recentNotifs = Auth::check() ? Database::fetchAll('SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 6', ['uid' => Auth::id()]) : [];

// Unread DM count
$unreadMsgs = 0;
if (Auth::check()) {
    try {
        $row = Database::fetch(
            "SELECT COUNT(*) AS c FROM dm_messages m
             JOIN dm_participants p ON p.thread_id = m.thread_id AND p.user_id = :uid
             WHERE m.user_id != :uid AND m.created_at > COALESCE(p.last_read_at, '1970-01-01')",
            ['uid' => Auth::id()]
        );
        $unreadMsgs = (int) ($row['c'] ?? 0);
    } catch (Throwable $e) { /* table might not exist yet */ }
}
$dueFlashcards = 0;
if (Auth::check()) {
    try {
        $dueFlashcards = Database::count('flashcard_reviews fr', 'fr.user_id = :uid AND fr.next_review <= :today', ['uid' => Auth::id(), 'today' => date('Y-m-d')]);
    } catch (Throwable $e) {}
}

// Theme
$theme = Auth::check() && !empty(Auth::user()['theme_preference']) ? Auth::user()['theme_preference'] : 'auto';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $theme === 'dark' ? 'dark' : 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#4F46E5">
<meta name="description" content="<?= e(APP_NAME) ?> — open-source LMS with AI assistant. By <?= e(APP_TEAM) ?>.">
<link rel="manifest" href="<?= asset('manifest.json') ?>">
<link rel="icon" type="image/png" href="<?= asset('favicon.png') ?>">
<link rel="apple-touch-icon" href="<?= asset('apple-touch-icon.png') ?>">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= e(APP_NAME) ?>">
<title><?= e($data['pageTitle'] ?? $data['appName'] ?? 'doodle') ?> · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<script>
// Register service worker for PWA offline support
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('<?= asset('sw.js') ?>').catch(() => {});
  });
}
</script>
<div class="app-shell">
  <div class="sidebar-backdrop" onclick="document.querySelector('.sidebar').classList.remove('open'); this.classList.remove('show')"></div>

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="mark">d</div>
      <div>
        <h1><?= e(APP_NAME) ?></h1>
        <p>by <?= e(APP_TEAM) ?></p>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="sidebar-section">Main</div>
      <a href="<?= url('') ?>" class="<?= $currentUri === '' ? 'active' : '' ?>"><span class="material-icons">home</span> Dashboard</a>

      <?php if (Auth::isAdmin()): ?>
        <div class="sidebar-section">Administration</div>
        <a href="<?= url('admin') ?>" class="<?= strpos($currentUri, 'admin') === 0 ? 'active' : '' ?>"><span class="material-icons">admin_panel_settings</span> Admin Panel</a>
        <a href="<?= url('admin/users') ?>" class="<?= strpos($currentUri, 'admin/users') === 0 ? 'active' : '' ?>"><span class="material-icons">group</span> Users</a>
        <a href="<?= url('admin/courses') ?>" class="<?= strpos($currentUri, 'admin/courses') === 0 ? 'active' : '' ?>"><span class="material-icons">school</span> All Courses</a>
        <a href="<?= url('admin/categories') ?>" class="<?= strpos($currentUri, 'admin/categories') === 0 ? 'active' : '' ?>"><span class="material-icons">category</span> Categories</a>
        <a href="<?= url('admin/announcements') ?>" class="<?= strpos($currentUri, 'admin/announcements') === 0 ? 'active' : '' ?>"><span class="material-icons">campaign</span> Announcements</a>
        <a href="<?= url('analytics') ?>" class="<?= strpos($currentUri, 'analytics') === 0 ? 'active' : '' ?>"><span class="material-icons">analytics</span> Analytics</a>
        <a href="<?= url('admin/settings') ?>" class="<?= strpos($currentUri, 'admin/settings') === 0 ? 'active' : '' ?>"><span class="material-icons">settings</span> Settings</a>
      <?php endif; ?>

      <?php if (Auth::isTeacher()): ?>
        <div class="sidebar-section">Teaching</div>
        <a href="<?= url('teacher') ?>" class="<?= strpos($currentUri, 'teacher') === 0 && $currentUri !== 'teacher/quizzes' ? 'active' : '' ?>"><span class="material-icons">dashboard</span> Teacher Studio</a>
        <a href="<?= url('teacher/courses') ?>" class="<?= strpos($currentUri, 'teacher/courses') === 0 ? 'active' : '' ?>"><span class="material-icons">school</span> My Courses</a>
        <a href="<?= url('teacher/quizzes') ?>" class="<?= strpos($currentUri, 'teacher/quizzes') === 0 ? 'active' : '' ?>"><span class="material-icons">quiz</span> Quiz Bank</a>
        <a href="<?= url('teacher/gradebook') ?>" class="<?= strpos($currentUri, 'teacher/gradebook') === 0 ? 'active' : '' ?>"><span class="material-icons">grading</span> Gradebook
          <?php if ($pendingCount > 0): ?><span class="badge"><?= $pendingCount ?></span><?php endif; ?>
        </a>
        <a href="<?= url('analytics') ?>" class="<?= strpos($currentUri, 'analytics') === 0 ? 'active' : '' ?>"><span class="material-icons">analytics</span> Analytics</a>
      <?php endif; ?>

      <?php if (Auth::isStudent()): ?>
        <div class="sidebar-section">Learning</div>
        <a href="<?= url('student') ?>" class="<?= strpos($currentUri, 'student') === 0 ? 'active' : '' ?>"><span class="material-icons">dashboard</span> My Learning</a>
        <a href="<?= url('catalog') ?>" class="<?= strpos($currentUri, 'catalog') === 0 ? 'active' : '' ?>"><span class="material-icons">explore</span> Course Catalog</a>
        <a href="<?= url('learning_paths') ?>" class="<?= strpos($currentUri, 'learning_paths') === 0 ? 'active' : '' ?>"><span class="material-icons">route</span> Learning Paths</a>
        <a href="<?= url('student/grades') ?>" class="<?= strpos($currentUri, 'student/grades') === 0 ? 'active' : '' ?>"><span class="material-icons">grading</span> My Grades</a>
      <?php endif; ?>

      <?php if (Auth::check()): ?>
        <div class="sidebar-section">AI & Tools</div>
        <a href="<?= url('ai') ?>" class="<?= strpos($currentUri, 'ai') === 0 ? 'active' : '' ?>"><span class="material-icons">smart_toy</span> AI Assistant</a>
        <a href="<?= url('flashcards') ?>" class="<?= strpos($currentUri, 'flashcards') === 0 ? 'active' : '' ?>"><span class="material-icons">style</span> Flashcards
          <?php if ($dueFlashcards > 0): ?><span class="badge"><?= $dueFlashcards ?></span><?php endif; ?>
        </a>
        <a href="<?= url('calendar') ?>" class="<?= strpos($currentUri, 'calendar') === 0 ? 'active' : '' ?>"><span class="material-icons">calendar_month</span> Calendar</a>
        <a href="<?= url('messages') ?>" class="<?= strpos($currentUri, 'messages') === 0 ? 'active' : '' ?>"><span class="material-icons">mail</span> Messages
          <?php if ($unreadMsgs > 0): ?><span class="badge"><?= $unreadMsgs ?></span><?php endif; ?>
        </a>
        <a href="<?= url('gamification') ?>" class="<?= strpos($currentUri, 'gamification') === 0 ? 'active' : '' ?>"><span class="material-icons">emoji_events</span> Achievements</a>
        <a href="<?= url('certificates') ?>" class="<?= strpos($currentUri, 'certificates') === 0 ? 'active' : '' ?>"><span class="material-icons">workspace_premium</span> Certificates</a>
        <a href="<?= url('api/tokens') ?>" class="<?= strpos($currentUri, 'api/tokens') === 0 ? 'active' : '' ?>"><span class="material-icons">api</span> API Tokens</a>

        <div class="sidebar-section">Account</div>
        <a href="<?= url('auth/profile') ?>" class="<?= $currentUri === 'auth/profile' ? 'active' : '' ?>"><span class="material-icons">person</span> Profile</a>
        <a href="#" onclick="toggleTheme(); return false;"><span class="material-icons">dark_mode</span> Toggle theme</a>
        <a href="<?= url('auth/logout') ?>"><span class="material-icons">logout</span> Sign out</a>
      <?php else: ?>
        <div class="sidebar-section">Account</div>
        <a href="<?= url('auth/login') ?>"><span class="material-icons">login</span> Sign in</a>
        <a href="<?= url('auth/register') ?>"><span class="material-icons">person_add</span> Create account</a>
      <?php endif; ?>
    </nav>

    <div class="sidebar-foot">
      <p>v<?= APP_VERSION ?> · GPL-3.0</p>
      <p><a href="<?= url('') ?>"><?= e(APP_NAME) ?></a> · <?= e(APP_TEAM) ?></p>
    </div>
  </aside>

  <div class="main-area">
    <header class="topbar">
      <button class="menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open'); document.querySelector('.sidebar-backdrop').classList.toggle('show')">
        <span class="material-icons">menu</span>
      </button>

      <form class="topbar-search" action="<?= url('catalog') ?>" method="get">
        <input type="hidden" name="r" value="catalog">
        <span class="material-icons">search</span>
        <input type="text" name="q" placeholder="Search courses..." value="<?= e($_GET['q'] ?? '') ?>">
      </form>

      <div class="topbar-actions">
        <?php if (Auth::check()): ?>
          <?php
          // XP summary in topbar
          try {
              Gamification::ensureStreak(Auth::id());
              $xpRow = Database::fetch('SELECT total_xp, level, current_streak FROM user_streaks WHERE user_id = :uid', ['uid' => Auth::id()]);
              if ($xpRow) {
                  echo '<a href="' . url('gamification') . '" class="xp-chip" style="text-decoration:none;color:inherit;" title="Your XP and level">';
                  echo '<span class="material-icons" style="font-size:16px;color:var(--accent);">bolt</span>';
                  echo '<span>' . number_format($xpRow['total_xp']) . ' XP</span>';
                  if ($xpRow['current_streak'] > 0) echo '<span style="color:var(--warning);">· ' . $xpRow['current_streak'] . '🔥</span>';
                  echo '</a>';
              }
          } catch (Throwable $e) {}
          ?>

          <div class="relative">
            <button class="topbar-icon-btn" onclick="document.getElementById('notifDrop').classList.toggle('open')">
              <span class="material-icons">notifications</span>
              <?php if ($notifCount > 0): ?><span class="dot"></span><?php endif; ?>
            </button>
            <div class="notif-dropdown" id="notifDrop">
              <div class="notif-head">
                <span>Notifications</span>
                <?php if ($notifCount > 0): ?><a href="<?= url('notifications/markAllRead') ?>" class="btn btn-ghost btn-sm">Mark all read</a><?php endif; ?>
              </div>
              <?php if (empty($recentNotifs)): ?>
                <div style="padding:30px;text-align:center;color:var(--text-muted);font-size:.85rem;">No notifications</div>
              <?php else: ?>
                <?php foreach ($recentNotifs as $n): ?>
                  <a href="<?= $n['link'] ? url($n['link']) : '#' ?>" class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>" style="display:block;text-decoration:none;color:inherit;">
                    <div class="notif-title"><?= e($n['title']) ?></div>
                    <div class="notif-body"><?= e(truncate($n['body'], 100)) ?></div>
                    <div class="notif-time"><?= timeAgo($n['created_at']) ?></div>
                  </a>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <a href="<?= url('auth/profile') ?>" class="user-chip" style="text-decoration:none;color:inherit;">
            <span class="avatar" style="background:<?= avatarColor($currentUser['full_name']) ?>;">
              <?php if (!empty($currentUser['avatar'])): ?><img src="<?= uploadUrl($currentUser['avatar']) ?>" alt=""><?php else: ?><?= initials($currentUser['full_name']) ?><?php endif; ?>
            </span>
            <span class="name"><?= e($currentUser['full_name']) ?></span>
          </a>
        <?php else: ?>
          <a href="<?= url('auth/login') ?>" class="btn btn-secondary">Sign in</a>
          <a href="<?= url('auth/register') ?>" class="btn btn-primary">Get started</a>
        <?php endif; ?>
      </div>
    </header>

    <main class="content">
      <?= renderFlash($data['flash'] ?? []) ?>

<script>
function toggleTheme() {
  const html = document.documentElement;
  const cur = html.getAttribute('data-theme') || 'light';
  const next = cur === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', next);
  // Persist via fetch
  fetch('<?= url("auth/setTheme") ?>', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'theme=' + next + '&<?= CSRF_TOKEN_NAME ?>=<?= CSRF::token() ?>' });
}
// Auto dark mode if user preference is 'auto' and OS prefers dark
(function() {
  const html = document.documentElement;
  if (html.getAttribute('data-theme') === 'auto' || !html.getAttribute('data-theme')) {
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      html.setAttribute('data-theme', 'dark');
    } else {
      html.setAttribute('data-theme', 'light');
    }
  }
})();
</script>
