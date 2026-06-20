<?php
/**
 * doodle - Admin controller
 *
 * Site administrator console: users, courses, categories, announcements,
 * settings, and global statistics.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class AdminController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $stats = [
            'users'      => Database::count('users'),
            'students'   => Database::count('users', "role = 'student'"),
            'teachers'   => Database::count('users', "role = 'teacher'"),
            'admins'     => Database::count('users', "role = 'admin'"),
            'courses'    => Database::count('courses'),
            'published'  => Database::count('courses', "status = 'published'"),
            'drafts'     => Database::count('courses', "status = 'draft'"),
            'enrollments'=> Database::count('enrollments'),
            'submissions'=> Database::count('submissions'),
            'pending'    => Database::count('submissions', "status = 'submitted'"),
            'forum'      => Database::count('forum_topics'),
            'posts'      => Database::count('forum_posts'),
        ];
        $recentUsers = Database::fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 8");
        $recentCourses = Database::fetchAll(
            "SELECT c.*, u.full_name AS teacher_name
             FROM courses c JOIN users u ON c.teacher_id = u.id
             ORDER BY c.created_at DESC LIMIT 6"
        );
        $recentActivity = Database::fetchAll(
            "SELECT al.*, u.full_name AS user_name
             FROM activity_log al
             LEFT JOIN users u ON al.user_id = u.id
             ORDER BY al.created_at DESC LIMIT 10"
        );
        $this->view('admin/dashboard', [
            'pageTitle'  => 'Admin Dashboard',
            'stats'      => $stats,
            'recentUsers'=> $recentUsers,
            'recentCourses' => $recentCourses,
            'recentActivity' => $recentActivity,
        ]);
    }

    public function users(): void
    {
        $q    = trim($_GET['q'] ?? '');
        $role = $_GET['role'] ?? '';
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $offset = ($page - 1) * PER_PAGE;

        $where  = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(username LIKE :q OR email LIKE :q OR full_name LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }
        if (in_array($role, ['admin','teacher','student'], true)) {
            $where[] = 'role = :role';
            $params['role'] = $role;
        }
        $whereSql = implode(' AND ', $where);
        $total = (int) Database::fetch("SELECT COUNT(*) AS c FROM users WHERE $whereSql", $params)['c'];
        $users = Database::fetchAll(
            "SELECT u.*,
                    (SELECT COUNT(*) FROM courses c WHERE c.teacher_id = u.id) AS course_count,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.student_id = u.id) AS enroll_count
             FROM users u
             WHERE $whereSql
             ORDER BY u.created_at DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, ['limit' => PER_PAGE, 'offset' => $offset])
        );
        $baseUrl = 'admin/users?' . http_build_query(array_filter(['r'=>'admin/users','q'=>$q,'role'=>$role]));
        $this->view('admin/users', [
            'pageTitle' => 'Manage Users',
            'users'    => $users,
            'q'         => $q,
            'role'      => $role,
            'pagination'=> paginate($total, PER_PAGE, $page, $baseUrl),
            'total'     => $total,
        ]);
    }

    public function createUser(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($this->input('username', ''));
            $email    = trim($this->input('email', ''));
            $fullName = trim($this->input('full_name', ''));
            $password = $this->input('password', '');
            $role     = $this->input('role', 'student');

            if (!in_array($role, ['admin','teacher','student'], true)) $role = 'student';
            if (strlen($username) < 3 || !preg_match('/^[A-Za-z0-9_\.]+$/', $username)) {
                $this->flash('error', 'Invalid username.');
            } elseif (Database::fetch('SELECT id FROM users WHERE username = :u', ['u' => $username])) {
                $this->flash('error', 'Username already exists.');
            } elseif (!isValidEmail($email)) {
                $this->flash('error', 'Invalid email.');
            } elseif (Database::fetch('SELECT id FROM users WHERE email = :e', ['e' => $email])) {
                $this->flash('error', 'Email already registered.');
            } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
                $this->flash('error', 'Password too short.');
            } elseif (empty($fullName)) {
                $this->flash('error', 'Full name is required.');
            } else {
                Auth::create([
                    'username'  => $username,
                    'email'     => $email,
                    'password'  => $password,
                    'full_name' => $fullName,
                    'role'      => $role,
                ]);
                $this->flash('success', "User {$username} created as {$role}.");
            }
        }
        $this->redirect('admin/users');
    }

    public function editUser(int $id): void
    {
        $user = Database::fetch('SELECT * FROM users WHERE id = :id', ['id' => $id]);
        if (!$user) { $this->flash('error', 'User not found.'); $this->redirect('admin/users'); }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $updates = [
                'username'  => trim($this->input('username', $user['username'])),
                'email'     => trim($this->input('email', $user['email'])),
                'full_name' => trim($this->input('full_name', $user['full_name'])),
                'role'      => $this->input('role', $user['role']),
                'status'    => $this->input('status', $user['status']),
                'bio'       => trim($this->input('bio', $user['bio'])),
                'phone'     => trim($this->input('phone', $user['phone'])),
                'updated_at'=> date('Y-m-d H:i:s'),
            ];
            if (!in_array($updates['role'], ['admin','teacher','student'], true)) $updates['role'] = $user['role'];
            if (!in_array($updates['status'], ['active','suspended','pending'], true)) $updates['status'] = 'active';
            $newPass = $this->input('password', '');
            if (!empty($newPass)) {
                if (strlen($newPass) < PASSWORD_MIN_LENGTH) {
                    $this->flash('error', 'Password too short.');
                    $this->redirect('admin/users');
                }
                $updates['password'] = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
            }
            Database::update('users', $updates, 'id = :id', ['id' => $id]);
            $this->flash('success', 'User updated.');
            $this->redirect('admin/users');
        }
        $this->view('admin/user_edit', ['pageTitle' => 'Edit user · ' . $user['full_name'], 'user' => $user]);
    }

    public function deleteUser(int $id): void
    {
        if ($id == Auth::id()) {
            $this->flash('error', 'You cannot delete your own account.');
            $this->redirect('admin/users');
        }
        Database::delete('users', 'id = :id', ['id' => $id]);
        $this->flash('success', 'User deleted.');
        $this->redirect('admin/users');
    }

    public function courses(): void
    {
        $q    = trim($_GET['q'] ?? '');
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $offset = ($page - 1) * PER_PAGE;
        $where = $q !== '' ? 'WHERE c.title LIKE :q' : '';
        $params = $q !== '' ? ['q' => '%'.$q.'%'] : [];
        $total = (int) Database::fetch("SELECT COUNT(*) AS c FROM courses c $where", $params)['c'];
        $courses = Database::fetchAll(
            "SELECT c.*, u.full_name AS teacher_name,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id AND e.status = 'active') AS enroll_count
             FROM courses c JOIN users u ON c.teacher_id = u.id
             $where
             ORDER BY c.created_at DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, ['limit' => PER_PAGE, 'offset' => $offset])
        );
        $baseUrl = 'admin/courses?' . http_build_query(array_filter(['r'=>'admin/courses','q'=>$q]));
        $this->view('admin/courses', [
            'pageTitle' => 'All Courses',
            'courses'   => $courses,
            'q'         => $q,
            'pagination'=> paginate($total, PER_PAGE, $page, $baseUrl),
            'total'     => $total,
        ]);
    }

    public function categories(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($this->input('name', ''));
            $desc = trim($this->input('description', ''));
            $parentId = (int) $this->input('parent_id', 0) ?: null;
            if (empty($name)) {
                $this->flash('error', 'Category name is required.');
            } else {
                $slug = slugify($name);
                $i = 1;
                while (Database::fetch('SELECT id FROM categories WHERE slug = :s', ['s' => $slug])) {
                    $slug = slugify($name) . '-' . $i++;
                }
                Database::insert('categories', [
                    'name' => $name, 'slug' => $slug, 'description' => $desc,
                    'parent_id' => $parentId, 'sort_order' => 0,
                ]);
                $this->flash('success', "Category '{$name}' created.");
            }
            $this->redirect('admin/categories');
        }
        $categories = Database::fetchAll("SELECT cat.*, (SELECT COUNT(*) FROM courses c WHERE c.category_id = cat.id) AS course_count FROM categories cat ORDER BY cat.sort_order, cat.name");
        $this->view('admin/categories', [
            'pageTitle' => 'Categories',
            'categories' => $categories,
        ]);
    }

    public function deleteCategory(int $id): void
    {
        Database::delete('categories', 'id = :id', ['id' => $id]);
        $this->flash('success', 'Category deleted.');
        $this->redirect('admin/categories');
    }

    public function announcements(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title   = trim($this->input('title', ''));
            $body    = trim($this->input('body', ''));
            $audience= $this->input('audience', 'all');
            $courseId = (int) $this->input('course_id', 0) ?: null;
            if (empty($title) || empty($body)) {
                $this->flash('error', 'Title and body are required.');
            } else {
                $id = Database::insert('announcements', [
                    'course_id' => $courseId, 'user_id' => Auth::id(),
                    'title' => $title, 'body' => $body, 'audience' => $audience,
                ]);
                // Notify all users (or those in the course)
                if ($courseId) {
                    $users = Database::fetchAll("SELECT student_id AS id FROM enrollments WHERE course_id = :cid AND status = 'active'", ['cid' => $courseId]);
                } else {
                    $users = Database::fetchAll("SELECT id FROM users WHERE status = 'active'");
                }
                foreach ($users as $u) {
                    Database::insert('notifications', [
                        'user_id' => $u['id'], 'title' => $title, 'body' => truncate($body, 200),
                        'link' => $courseId ? 'course/view/' . $courseId : '',
                        'is_read' => 0, 'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                $this->flash('success', 'Announcement broadcast.');
            }
            $this->redirect('admin/announcements');
        }
        $announcements = Database::fetchAll(
            "SELECT a.*, u.full_name AS author_name, c.title AS course_title
             FROM announcements a
             JOIN users u ON a.user_id = u.id
             LEFT JOIN courses c ON a.course_id = c.id
             ORDER BY a.created_at DESC"
        );
        $courses = Database::fetchAll("SELECT id, title FROM courses ORDER BY title");
        $this->view('admin/announcements', [
            'pageTitle' => 'Announcements',
            'announcements' => $announcements,
            'courses' => $courses,
        ]);
    }

    public function deleteAnnouncement(int $id): void
    {
        Database::delete('announcements', 'id = :id', ['id' => $id]);
        $this->flash('success', 'Announcement deleted.');
        $this->redirect('admin/announcements');
    }

    public function settings(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $keys = ['site_name','site_tagline','site_description','allow_registration','default_role','contact_email','footer_text','theme_primary',
                'maintenance_mode','maintenance_message',
                'smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from_email','smtp_from_name','enable_email',
                'enable_web_push','web_push_vapid_public','web_push_vapid_private',
                'oauth_google_client_id','oauth_google_secret','oauth_github_client_id','oauth_github_secret',
                'recaptcha_site_key','recaptcha_secret_key',
                'default_language','login_max_attempts','login_lockout_minutes'];
            foreach ($keys as $k) {
                $v = $this->input($k, '');
                // Portable UPSERT — works on both SQLite and MySQL
                $exists = Database::fetch('SELECT setting_key FROM settings WHERE setting_key = :k', ['k' => $k]);
                if ($exists) {
                    Database::query('UPDATE settings SET setting_value = :v, updated_at = :t WHERE setting_key = :k', ['k' => $k, 'v' => $v, 't' => date('Y-m-d H:i:s')]);
                } else {
                    Database::query('INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (:k, :v, :t)', ['k' => $k, 'v' => $v, 't' => date('Y-m-d H:i:s')]);
                }
            }
            $this->flash('success', 'Settings saved.');
            $this->redirect('admin/settings');
        }
        $settings = [];
        foreach (Database::fetchAll('SELECT * FROM settings') as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        $this->view('admin/settings', [
            'pageTitle' => 'System Settings',
            'settings'  => $settings,
        ]);
    }

    /** CSV user import. Columns: full_name, username, email, role, password (optional). */
    public function importUsers(): void
    {
        Auth::requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['csv']['tmp_name'])) {
            $this->flash('error', 'Please upload a CSV file.');
            $this->redirect('admin/users');
        }
        $fh = fopen($_FILES['csv']['tmp_name'], 'r');
        if (!$fh) { $this->flash('error', 'Could not read file.'); $this->redirect('admin/users'); }
        $header = fgetcsv($fh);
        $required = ['full_name', 'username', 'email', 'role'];
        $headerMap = [];
        foreach ($header as $i => $col) $headerMap[strtolower(trim($col))] = $i;
        foreach ($required as $r) {
            if (!isset($headerMap[$r])) {
                $this->flash('error', "CSV must have a '$r' column. Found columns: " . implode(', ', $header));
                $this->redirect('admin/users');
            }
        }
        $created = 0; $skipped = 0; $errors = [];
        $lineNum = 1;
        while (($row = fgetcsv($fh)) !== false) {
            $lineNum++;
            $fullName = trim($row[$headerMap['full_name']]);
            $username = trim($row[$headerMap['username']]);
            $email    = trim($row[$headerMap['email']]);
            $role     = strtolower(trim($row[$headerMap['role']]));
            $password = isset($headerMap['password']) && !empty($row[$headerMap['password']]) ? $row[$headerMap['password']] : bin2hex(random_bytes(8));
            if (!in_array($role, ['admin','teacher','student'], true)) $role = 'student';
            if (empty($fullName) || empty($username) || !isValidEmail($email)) {
                $errors[] = "Line $lineNum: invalid data, skipped.";
                $skipped++;
                continue;
            }
            if (Database::fetch('SELECT id FROM users WHERE username = :u OR email = :e', ['u' => $username, 'e' => $email])) {
                $skipped++;
                continue;
            }
            try {
                Auth::create([
                    'username' => $username, 'email' => $email, 'password' => $password,
                    'full_name' => $fullName, 'role' => $role,
                ]);
                $created++;
            } catch (Throwable $e) {
                $errors[] = "Line $lineNum: " . $e->getMessage();
                $skipped++;
            }
        }
        fclose($fh);
        $msg = "Imported $created user(s). Skipped $skipped.";
        if (!empty($errors)) $msg .= " Errors: " . implode(' ', array_slice($errors, 0, 3));
        $this->flash($created > 0 ? 'success' : 'error', $msg);
        $this->redirect('admin/users');
    }

    /** Export all users as CSV. */
    public function exportUsers(): void
    {
        Auth::requireRole('admin');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="doodle-users-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['id', 'full_name', 'username', 'email', 'role', 'status', 'created_at', 'last_login']);
        $users = Database::fetchAll('SELECT id, full_name, username, email, role, status, created_at, last_login FROM users ORDER BY created_at');
        foreach ($users as $u) fputcsv($out, $u);
        fclose($out);
        exit;
    }
}
