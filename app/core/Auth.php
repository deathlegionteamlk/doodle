<?php
/**
 * doodle - Authentication & authorization
 *
 * Roles: admin | teacher | student
 *
 * @package doodle
 * @author  Death Legion Team
 */

class Auth
{
    public static ?array $user = null;

    public static function init(): void
    {
        Session::start();
        $uid = Session::get('user_id');
        if ($uid) {
            $user = Database::fetch('SELECT * FROM users WHERE id = :id AND status = :status', ['id' => $uid, 'status' => 'active']);
            if ($user) {
                self::$user = $user;
            } else {
                self::logout();
            }
        }
    }

    public static function check(): bool
    {
        return self::$user !== null;
    }

    public static function user(): ?array
    {
        return self::$user;
    }

    public static function id(): ?int
    {
        return self::$user['id'] ?? null;
    }

    public static function role(): ?string
    {
        return self::$user['role'] ?? null;
    }

    public static function is(string $role): bool
    {
        return self::role() === $role;
    }

    public static function isAdmin(): bool
    {
        return self::is('admin');
    }

    public static function isTeacher(): bool
    {
        return self::is('teacher');
    }

    public static function isStudent(): bool
    {
        return self::is('student');
    }

    /** Allow any of the given roles. */
    public static function inRoles(array $roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    public static function login(string $identifier, string $password, bool $remember = false): bool
    {
        $user = Database::fetch(
            'SELECT * FROM users WHERE (username = :id OR email = :id) AND status = :status LIMIT 1',
            ['id' => $identifier, 'status' => 'active']
        );
        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }
        // Update last login
        Database::update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
        Session::regenerate();
        Session::set('user_id', $user['id']);
        self::$user = $user;
        return true;
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user = null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('auth/login');
            exit;
        }
    }

    public static function requireRole(string $role): void
    {
        self::requireLogin();
        if (!self::is($role)) {
            http_response_code(403);
            view('errors/403', ['message' => 'You do not have permission to access this page.']);
            exit;
        }
    }

    public static function requireAnyRole(array $roles): void
    {
        self::requireLogin();
        if (!self::inRoles($roles)) {
            http_response_code(403);
            view('errors/403', ['message' => 'You do not have permission to access this page.']);
            exit;
        }
    }

    /** Check if the current teacher is the owner of a course. */
    public static function ownsCourse(int $courseId): bool
    {
        if (self::isAdmin()) return true;
        if (!self::isTeacher()) return false;
        $c = Database::fetch('SELECT id FROM courses WHERE id = :id AND teacher_id = :tid', ['id' => $courseId, 'tid' => self::id()]);
        return $c !== null;
    }

    /** Check if current student is enrolled in a course. */
    public static function isEnrolled(int $courseId): bool
    {
        if (!self::isStudent()) return false;
        $e = Database::fetch('SELECT id FROM enrollments WHERE course_id = :cid AND student_id = :sid AND status = :s', ['cid' => $courseId, 'sid' => self::id(), 's' => 'active']);
        return $e !== null;
    }

    public static function create(array $data): int
    {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => HASH_COST]);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? 'active';
        return Database::insert('users', $data);
    }
}
