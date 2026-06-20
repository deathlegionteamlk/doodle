<?php
/**
 * doodle - REST API controller
 *
 * Token-authenticated JSON API for mobile apps and integrations.
 *
 * Endpoints (all under /api?r=api/<action>):
 *   POST api/auth            → exchange username/password for a token
 *   GET  api/me              → current user info
 *   GET  api/courses         → list published courses
 *   GET  api/courses/:id     → course detail
 *   GET  api/my/courses      → student's enrolled courses
 *   GET  api/my/notifications→ unread notifications
 *   POST api/lessons/:id/complete → mark lesson complete
 *   GET  api/flashcards/due  → due flashcards
 *   GET  api/leaderboard     → top 20 users by XP
 *
 * @package doodle
 * @author  Death Legion Team
 */

class ApiController extends Controller
{
    private ?array $authUser = null;

    public function __construct()
    {
        // Don't call parent::__construct() because it requires session/auth
        // and we want to bypass the standard layout for JSON responses.
        $this->data['appName'] = APP_NAME;
        // Try token auth from header
        $token = $_SERVER['HTTP_X_API_TOKEN'] ?? ($_GET['token'] ?? '');
        if ($token) {
            $row = Database::fetch(
                "SELECT t.*, u.* FROM api_tokens t
                 JOIN users u ON t.user_id = u.id
                 WHERE t.token = :token AND u.status = 'active'
                   AND (t.expires_at IS NULL OR t.expires_at > :now)",
                ['token' => $token, 'now' => date('Y-m-d H:i:s')]
            );
            if ($row) {
                $this->authUser = $row;
                Database::query('UPDATE api_tokens SET last_used = :now WHERE id = :id', ['now' => date('Y-m-d H:i:s'), 'id' => $row['id']]);
                // Also bootstrap Auth::$user so helper methods work
                Auth::$user = $row;
            }
        }
    }

    private function requireAuth(): array
    {
        if (!$this->authUser) {
            $this->json(['ok' => false, 'error' => 'Authentication required.'], 401);
        }
        return $this->authUser;
    }

    /** POST /api/auth — exchange username + password for a token. */
    public function auth(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['ok' => false, 'error' => 'POST required'], 405);
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';
        $tokenName = trim($_POST['device_name'] ?? 'API Token');
        if (empty($user) || empty($pass)) $this->json(['ok' => false, 'error' => 'Missing credentials'], 400);

        $u = Database::fetch('SELECT * FROM users WHERE (username = :u OR email = :u) AND status = "active"', ['u' => $user]);
        if (!$u || !password_verify($pass, $u['password'])) {
            $this->json(['ok' => false, 'error' => 'Invalid credentials'], 401);
        }
        $token = bin2hex(random_bytes(32));
        Database::insert('api_tokens', [
            'user_id'  => $u['id'],
            'token'    => $token,
            'name'     => $tokenName,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
        ]);
        $this->json([
            'ok'    => true,
            'token' => $token,
            'user'  => $this->userJson($u),
        ]);
    }

    public function me(): void
    {
        $u = $this->requireAuth();
        $this->json(['ok' => true, 'user' => $this->userJson($u)]);
    }

    public function courses(): void
    {
        $courses = Database::fetchAll(
            "SELECT c.id, c.title, c.description, c.level, c.enroll_count, c.cover_image,
                    u.full_name AS teacher_name, cat.name AS category
             FROM courses c JOIN users u ON c.teacher_id = u.id
             LEFT JOIN categories cat ON c.category_id = cat.id
             WHERE c.status = 'published' AND c.visibility = 'public'
             ORDER BY c.enroll_count DESC LIMIT 100"
        );
        foreach ($courses as &$c) {
            if (!empty($c['cover_image'])) $c['cover_url'] = uploadUrl($c['cover_image']);
        }
        $this->json(['ok' => true, 'courses' => $courses]);
    }

    public function course(int $id): void
    {
        $course = Database::fetch(
            "SELECT c.*, u.full_name AS teacher_name FROM courses c JOIN users u ON c.teacher_id = u.id WHERE c.id = :id",
            ['id' => $id]
        );
        if (!$course) $this->json(['ok' => false, 'error' => 'Not found'], 404);
        $modules = Database::fetchAll(
            "SELECT m.id, m.title, (SELECT COUNT(*) FROM lessons l WHERE l.module_id = m.id) AS lesson_count
             FROM modules m WHERE m.course_id = :cid ORDER BY m.sort_order",
            ['cid' => $id]
        );
        $this->json(['ok' => true, 'course' => $course, 'modules' => $modules]);
    }

    public function myCourses(): void
    {
        $u = $this->requireAuth();
        $courses = Database::fetchAll(
            "SELECT c.id, c.title, c.description, c.cover_image, e.progress, e.enrolled_at, u.full_name AS teacher_name
             FROM enrollments e JOIN courses c ON e.course_id = c.id
             JOIN users u ON c.teacher_id = u.id
             WHERE e.student_id = :uid AND e.status = 'active'
             ORDER BY e.enrolled_at DESC",
            ['uid' => $u['id']]
        );
        foreach ($courses as &$c) if (!empty($c['cover_image'])) $c['cover_url'] = uploadUrl($c['cover_image']);
        $this->json(['ok' => true, 'courses' => $courses]);
    }

    public function myNotifications(): void
    {
        $u = $this->requireAuth();
        $notifs = Database::fetchAll(
            "SELECT id, title, body, link, is_read, created_at FROM notifications
             WHERE user_id = :uid ORDER BY created_at DESC LIMIT 50",
            ['uid' => $u['id']]
        );
        $this->json(['ok' => true, 'notifications' => $notifs]);
    }

    public function completeLesson(int $lessonId): void
    {
        $u = $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['ok' => false, 'error' => 'POST required'], 405);
        $lesson = Database::fetch("SELECT l.*, m.course_id FROM lessons l JOIN modules m ON l.module_id = m.id WHERE l.id = :id", ['id' => $lessonId]);
        if (!$lesson) $this->json(['ok' => false, 'error' => 'Lesson not found'], 404);
        if (!Auth::isEnrolled($lesson['course_id'])) $this->json(['ok' => false, 'error' => 'Not enrolled'], 403);
        $exists = Database::fetch('SELECT id FROM lesson_completions WHERE lesson_id = :lid AND student_id = :sid', ['lid' => $lessonId, 'sid' => $u['id']]);
        if (!$exists) {
            Database::insert('lesson_completions', [
                'lesson_id' => $lessonId, 'student_id' => $u['id'],
            ]);
        }
        $this->json(['ok' => true]);
    }

    public function flashcardsDue(): void
    {
        $u = $this->requireAuth();
        $cards = Database::fetchAll(
            "SELECT f.id, f.front, f.back, d.title AS deck_title, fr.id AS review_id
             FROM flashcards f
             JOIN flashcard_reviews fr ON fr.flashcard_id = f.id
             JOIN flashcard_decks d ON f.deck_id = d.id
             WHERE fr.user_id = :uid AND fr.next_review <= :today
             LIMIT 50",
            ['uid' => $u['id'], 'today' => date('Y-m-d')]
        );
        $this->json(['ok' => true, 'cards' => $cards, 'count' => count($cards)]);
    }

    public function leaderboard(): void
    {
        $board = Gamification::leaderboard(20);
        $this->json(['ok' => true, 'leaderboard' => $board]);
    }

    private function userJson(array $u): array
    {
        return [
            'id'         => (int) $u['id'],
            'username'   => $u['username'],
            'email'      => $u['email'],
            'full_name'  => $u['full_name'],
            'role'       => $u['role'],
            'avatar'     => $u['avatar'] ? uploadUrl($u['avatar']) : null,
        ];
    }

    /** User-facing: generate/manage API tokens. */
    public function tokens(): void
    {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($this->input('name', ''));
            if (!empty($name)) {
                $token = bin2hex(random_bytes(32));
                Database::insert('api_tokens', [
                    'user_id'    => Auth::id(),
                    'token'      => $token,
                    'name'       => $name,
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
                ]);
                $this->flash('success', 'Token created. Copy it now — you won\'t see it again.');
                $this->redirect('api/tokens?new=' . $token);
            }
        }
        $tokens = Database::fetchAll('SELECT id, name, token, last_used, expires_at, created_at FROM api_tokens WHERE user_id = :uid ORDER BY created_at DESC', ['uid' => Auth::id()]);
        $newToken = $_GET['new'] ?? '';
        $this->view('api/tokens', [
            'pageTitle' => 'API Tokens',
            'tokens'    => $tokens,
            'newToken'  => $newToken,
        ]);
    }

    public function revokeToken(int $id): void
    {
        Auth::requireLogin();
        Database::delete('api_tokens', 'id = :id AND user_id = :uid', ['id' => $id, 'uid' => Auth::id()]);
        $this->flash('success', 'Token revoked.');
        $this->redirect('api/tokens');
    }
}
