<?php
/**
 * doodle - Wiki + Group + Push + Backup controllers (combined for compactness)
 *
 * @package doodle
 * @author  Death Legion Team
 */

class WikiController extends Controller
{
    public function __construct() { parent::__construct(); Auth::requireLogin(); }

    public function course(int $courseId): void
    {
        $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
        if (!$course) { $this->redirect(''); }
        $this->assertAccess($course);
        $pages = Database::fetchAll('SELECT * FROM wiki_pages WHERE course_id = :cid ORDER BY title', ['cid' => $courseId]);
        $this->view('wiki/course', [
            'pageTitle' => 'Wiki · ' . $course['title'],
            'course'    => $course,
            'pages'     => $pages,
        ]);
    }

    public function view(int $pageId): void
    {
        $page = Database::fetch(
            "SELECT w.*, c.title AS course_title, c.id AS course_id, c.teacher_id,
                    u.full_name AS creator_name, u2.full_name AS updater_name
             FROM wiki_pages w
             JOIN courses c ON w.course_id = c.id
             JOIN users u ON w.created_by = u.id
             LEFT JOIN users u2 ON w.updated_by = u2.id
             WHERE w.id = :id",
            ['id' => $pageId]
        );
        if (!$page) { $this->redirect(''); }
        $this->assertAccess($page);
        $revisions = Database::fetchAll(
            "SELECT r.*, u.full_name AS editor_name FROM wiki_revisions r JOIN users u ON r.edited_by = u.id WHERE r.page_id = :pid ORDER BY r.edited_at DESC LIMIT 20",
            ['pid' => $pageId]
        );
        $this->view('wiki/view', [
            'pageTitle' => $page['title'],
            'page'      => $page,
            'revisions' => $revisions,
        ]);
    }

    public function edit(int $pageId): void
    {
        $page = Database::fetch(
            "SELECT w.*, c.teacher_id FROM wiki_pages w JOIN courses c ON w.course_id = c.id WHERE w.id = :id",
            ['id' => $pageId]
        );
        if (!$page) { $this->redirect(''); }
        $this->assertAccess($page);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($this->input('title', ''));
            $content = $this->input('content', '');
            // Sanitize HTML
            $content = Security::sanitizeHtml($content);
            // Save revision
            Database::insert('wiki_revisions', [
                'page_id' => $pageId, 'content' => $page['content'], 'edited_by' => Auth::id(),
            ]);
            Database::update('wiki_pages', [
                'title' => $title, 'content' => $content, 'updated_by' => Auth::id(), 'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = :id', ['id' => $pageId]);
            $this->flash('success', 'Wiki page updated.');
            $this->redirect('wiki/view/' . $pageId);
        }
        $this->view('wiki/edit', ['pageTitle' => 'Edit · ' . $page['title'], 'page' => $page]);
    }

    public function create(int $courseId): void
    {
        $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
        if (!$course) { $this->redirect(''); }
        $this->assertAccess($course);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($this->input('title', ''));
            $content = Security::sanitizeHtml($this->input('content', ''));
            $slug = slugify($title);
            $i = 1;
            while (Database::fetch('SELECT id FROM wiki_pages WHERE course_id = :cid AND slug = :s', ['cid' => $courseId, 's' => $slug])) {
                $slug = slugify($title) . '-' . $i++;
            }
            $pageId = Database::insert('wiki_pages', [
                'course_id'  => $courseId,
                'title'      => $title,
                'slug'       => $slug,
                'content'    => $content,
                'created_by' => Auth::id(),
            ]);
            $this->flash('success', 'Wiki page created.');
            $this->redirect('wiki/view/' . $pageId);
        }
        $this->view('wiki/create', ['pageTitle' => 'New Wiki Page', 'course' => $course]);
    }

    public function delete(int $pageId): void
    {
        $page = Database::fetch("SELECT w.*, c.teacher_id FROM wiki_pages w JOIN courses c ON w.course_id = c.id WHERE w.id = :id", ['id' => $pageId]);
        if (!$page) { $this->redirect(''); }
        if (!Auth::isAdmin() && !(Auth::isTeacher() && $page['teacher_id'] == Auth::id())) {
            $this->flash('error', 'Only course teachers can delete wiki pages.');
            $this->redirect('');
        }
        Database::delete('wiki_pages', 'id = :id', ['id' => $pageId]);
        $this->flash('success', 'Wiki page deleted.');
        $this->redirect('wiki/course/' . $page['course_id']);
    }

    private function assertAccess(array $courseOrPage): void
    {
        $courseId = $courseOrPage['course_id'] ?? $courseOrPage['id'];
        $teacherId = $courseOrPage['teacher_id'] ?? null;
        if ($teacherId === null) {
            $c = Database::fetch('SELECT teacher_id FROM courses WHERE id = :id', ['id' => $courseId]);
            $teacherId = $c['teacher_id'] ?? 0;
        }
        $can = Auth::isAdmin() || (Auth::isTeacher() && $teacherId == Auth::id()) || (Auth::isStudent() && Auth::isEnrolled($courseId));
        if (!$can) { $this->flash('error', 'Access denied.'); $this->redirect(''); }
    }
}

class GroupController extends Controller
{
    public function __construct() { parent::__construct(); Auth::requireLogin(); }

    public function course(int $courseId): void
    {
        $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
        if (!$course) { $this->redirect(''); }
        $this->assertAccess($course);
        $groups = Database::fetchAll(
            "SELECT g.*, (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) AS member_count
             FROM course_groups g WHERE g.course_id = :cid ORDER BY g.name",
            ['cid' => $courseId]
        );
        $myGroupIds = array_column(
            Database::fetchAll('SELECT group_id FROM group_members WHERE user_id = :uid', ['uid' => Auth::id()]),
            'group_id'
        );
        $this->view('group/course', [
            'pageTitle' => 'Groups · ' . $course['title'],
            'course'    => $course,
            'groups'    => $groups,
            'myGroupIds' => $myGroupIds,
        ]);
    }

    public function create(int $courseId): void
    {
        $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
        if (!$course) { $this->redirect(''); }
        $this->assertAccess($course);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($this->input('name', ''));
            $desc = trim($this->input('description', ''));
            if (!empty($name)) {
                $gid = Database::insert('course_groups', [
                    'course_id' => $courseId, 'name' => $name, 'description' => $desc,
                ]);
                Database::insert('group_members', [
                    'group_id' => $gid, 'user_id' => Auth::id(), 'role' => 'leader',
                ]);
                $this->flash('success', 'Group created. You are the leader.');
            }
            $this->redirect('group/course/' . $courseId);
        }
    }

    public function join(int $groupId): void
    {
        $g = Database::fetch('SELECT * FROM course_groups WHERE id = :id', ['id' => $groupId]);
        if (!$g) { $this->redirect(''); }
        $this->assertAccess($g);
        $existing = Database::fetch('SELECT id FROM group_members WHERE group_id = :gid AND user_id = :uid', ['gid' => $groupId, 'uid' => Auth::id()]);
        if (!$existing) {
            Database::insert('group_members', ['group_id' => $groupId, 'user_id' => Auth::id(), 'role' => 'member']);
            $this->flash('success', 'Joined group.');
        } else {
            $this->flash('info', 'Already a member.');
        }
        $this->redirect('group/course/' . $g['course_id']);
    }

    public function leave(int $groupId): void
    {
        $g = Database::fetch('SELECT * FROM course_groups WHERE id = :id', ['id' => $groupId]);
        if (!$g) { $this->redirect(''); }
        Database::delete('group_members', 'group_id = :gid AND user_id = :uid', ['gid' => $groupId, 'uid' => Auth::id()]);
        $this->flash('success', 'Left group.');
        $this->redirect('group/course/' . $g['course_id']);
    }

    public function delete(int $groupId): void
    {
        $g = Database::fetch("SELECT g.*, c.teacher_id FROM course_groups g JOIN courses c ON g.course_id = c.id WHERE g.id = :id", ['id' => $groupId]);
        if (!$g) { $this->redirect(''); }
        if (!Auth::isAdmin() && !(Auth::isTeacher() && $g['teacher_id'] == Auth::id())) {
            $this->flash('error', 'Only course teacher can delete groups.');
            $this->redirect('');
        }
        Database::delete('course_groups', 'id = :id', ['id' => $groupId]);
        $this->flash('success', 'Group deleted.');
        $this->redirect('group/course/' . $g['course_id']);
    }

    private function assertAccess(array $courseOrGroup): void
    {
        $courseId = $courseOrGroup['course_id'] ?? $courseOrGroup['id'];
        $teacherId = $courseOrGroup['teacher_id'] ?? null;
        if ($teacherId === null) {
            $c = Database::fetch('SELECT teacher_id FROM courses WHERE id = :id', ['id' => $courseId]);
            $teacherId = $c['teacher_id'] ?? 0;
        }
        $can = Auth::isAdmin() || (Auth::isTeacher() && $teacherId == Auth::id()) || (Auth::isStudent() && Auth::isEnrolled($courseId));
        if (!$can) { $this->flash('error', 'Access denied.'); $this->redirect(''); }
    }
}

class PushController extends Controller
{
    public function __construct() { parent::__construct(); Auth::requireLogin(); }

    public function subscribe(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['ok' => false], 405);
        $endpoint = $this->input('endpoint', '');
        $p256dh = $this->input('p256dh', '');
        $auth = $this->input('auth', '');
        if (empty($endpoint) || empty($p256dh) || empty($auth)) $this->json(['ok' => false, 'error' => 'Missing fields'], 400);
        $existing = Database::fetch('SELECT id FROM push_subscriptions WHERE user_id = :uid AND endpoint = :e', ['uid' => Auth::id(), 'e' => $endpoint]);
        if (!$existing) {
            Database::insert('push_subscriptions', [
                'user_id'    => Auth::id(),
                'endpoint'   => $endpoint,
                'p256dh_key' => $p256dh,
                'auth_key'   => $auth,
            ]);
        }
        $this->json(['ok' => true]);
    }

    public function unsubscribe(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['ok' => false], 405);
        $endpoint = $this->input('endpoint', '');
        Database::delete('push_subscriptions', 'user_id = :uid AND endpoint = :e', ['uid' => Auth::id(), 'e' => $endpoint]);
        $this->json(['ok' => true]);
    }

    public function vapidPublicKey(): void
    {
        $key = Database::fetch('SELECT setting_value FROM settings WHERE setting_key = :k', ['k' => 'web_push_vapid_public'])['setting_value'] ?? '';
        $this->json(['ok' => true, 'key' => $key]);
    }
}

class BackupController extends Controller
{
    public function __construct() { parent::__construct(); Auth::requireRole('admin'); }

    public function index(): void
    {
        $backups = Database::fetchAll('SELECT b.*, u.full_name AS creator_name FROM backups b LEFT JOIN users u ON b.created_by = u.id ORDER BY b.created_at DESC');
        $this->view('admin/backups', [
            'pageTitle' => 'Backups',
            'backups'   => $backups,
        ]);
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->redirect('admin/backups');
        if (DB_TYPE !== 'sqlite') {
            $this->flash('error', 'Backup currently only supports SQLite databases.');
            $this->redirect('admin/backups');
        }
        $backupDir = DATABASE_DIR . '/backups';
        if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
        $name = 'doodle-backup-' . date('Y-m-d-His') . '.sqlite';
        $path = $backupDir . '/' . $name;
        if (copy(DB_PATH, $path)) {
            $size = filesize($path);
            Database::insert('backups', [
                'filename' => $name, 'file_size' => $size, 'created_by' => Auth::id(),
            ]);
            $this->flash('success', 'Backup created (' . humanFileSize($size) . ').');
        } else {
            $this->flash('error', 'Failed to create backup.');
        }
        $this->redirect('admin/backups');
    }

    public function download(int $id): void
    {
        $b = Database::fetch('SELECT * FROM backups WHERE id = :id', ['id' => $id]);
        if (!$b) { $this->redirect('admin/backups'); }
        $path = DATABASE_DIR . '/backups/' . $b['filename'];
        if (!file_exists($path)) { $this->flash('error', 'Backup file missing.'); $this->redirect('admin/backups'); }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $b['filename'] . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function delete(int $id): void
    {
        $b = Database::fetch('SELECT * FROM backups WHERE id = :id', ['id' => $id]);
        if (!$b) { $this->redirect('admin/backups'); }
        $path = DATABASE_DIR . '/backups/' . $b['filename'];
        if (file_exists($path)) @unlink($path);
        Database::delete('backups', 'id = :id', ['id' => $id]);
        $this->flash('success', 'Backup deleted.');
        $this->redirect('admin/backups');
    }
}
