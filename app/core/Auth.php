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

    public static function login(string $identifier, string $password, bool $remember = false): array
    {
        $ip = Security::clientIp();

        // Rate limit check
        $waitMin = Security::checkRateLimit($identifier, $ip);
        if ($waitMin > 0) {
            return ['ok' => false, 'error' => "Too many failed attempts. Try again in $waitMin minute(s)."];
        }

        $user = Database::fetch(
            'SELECT * FROM users WHERE (username = :id OR email = :id) AND status = :status LIMIT 1',
            ['id' => $identifier, 'status' => 'active']
        );
        if (!$user || !password_verify($password, $user['password'])) {
            Security::recordAttempt($identifier, $ip, false);
            return ['ok' => false, 'error' => 'Invalid credentials.'];
        }

        // If 2FA enabled, return challenge
        if (!empty($user['two_factor_enabled'])) {
            $twoFa = Database::fetch('SELECT * FROM user_2fa WHERE user_id = :uid', ['uid' => $user['id']]);
            if ($twoFa && !empty($twoFa['enabled_at'])) {
                // Don't authenticate yet — stash user_id for the 2FA step
                Session::set('pending_2fa_user_id', $user['id']);
                Session::set('pending_2fa_remember', $remember);
                Security::recordAttempt($identifier, $ip, true);
                Security::clearAttempts($identifier, $ip);
                return ['ok' => false, 'requires_2fa' => true];
            }
        }

        Security::recordAttempt($identifier, $ip, true);
        Security::clearAttempts($identifier, $ip);
        Security::rotateSession();

        // Update last login
        Database::update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
        Session::set('user_id', $user['id']);
        self::$user = $user;
        return ['ok' => true];
    }

    /** Complete login after a successful 2FA code verification. */
    public static function completeTotpLogin(string $code): array
    {
        $uid = Session::get('pending_2fa_user_id');
        if (!$uid) return ['ok' => false, 'error' => 'No pending 2FA session.'];
        $twoFa = Database::fetch('SELECT * FROM user_2fa WHERE user_id = :uid AND enabled_at IS NOT NULL', ['uid' => $uid]);
        if (!$twoFa) return ['ok' => false, 'error' => '2FA not set up.'];

        // Check TOTP code
        $valid = Security::verifyTotp($twoFa['secret'], $code);
        // Check backup codes
        if (!$valid) {
            $backups = json_decode($twoFa['backup_codes'] ?? '[]', true) ?: [];
            foreach ($backups as $i => $b) {
                if (hash_equals($b, strtoupper(trim($code)))) {
                    $valid = true;
                    // Consume the backup code
                    unset($backups[$i]);
                    Database::update('user_2fa', ['backup_codes' => json_encode(array_values($backups))], 'user_id = :uid', ['uid' => $uid]);
                    break;
                }
            }
        }
        if (!$valid) return ['ok' => false, 'error' => 'Invalid 2FA code.'];

        // Complete login
        $user = Database::fetch('SELECT * FROM users WHERE id = :id AND status = :status', ['id' => $uid, 'status' => 'active']);
        if (!$user) return ['ok' => false, 'error' => 'User not found.'];
        Session::remove('pending_2fa_user_id');
        Session::remove('pending_2fa_remember');
        Security::rotateSession();
        Database::update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $uid]);
        Session::set('user_id', $uid);
        self::$user = $user;
        return ['ok' => true];
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
