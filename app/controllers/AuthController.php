<?php
/**
 * doodle - Authentication controller
 *
 * @package doodle
 * @author  Death Legion Team
 */

class AuthController extends Controller
{
    public function login(): void
    {
        if (Auth::check()) $this->redirect('');

        // Handle 2FA code submission
        if (Session::has('pending_2fa_user_id') && $_SERVER['REQUEST_METHOD'] === 'POST' && $this->input('totp_code') !== null) {
            $code = trim($this->input('totp_code', ''));
            $result = Auth::completeTotpLogin($code);
            if ($result['ok']) {
                $this->flash('success', 'Welcome back, ' . Auth::user()['full_name'] . '!');
                $home = Auth::isAdmin() ? 'admin' : (Auth::isTeacher() ? 'teacher' : 'student');
                $this->redirect($home);
            } else {
                $this->flash('error', $result['error'] ?? 'Invalid 2FA code.');
                $this->viewRaw('auth/login', ['pageTitle' => 'Two-factor authentication', 'requires2fa' => true]);
                return;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identifier = trim($this->input('identifier', ''));
            $password   = $this->input('password', '');
            $remember   = (bool) $this->input('remember');

            if (empty($identifier) || empty($password)) {
                $this->flash('error', 'Please enter your username/email and password.');
            } else {
                $result = Auth::login($identifier, $password, $remember);
                if (!empty($result['ok'])) {
                    $this->flash('success', 'Welcome back, ' . Auth::user()['full_name'] . '!');
                    $home = Auth::isAdmin() ? 'admin' : (Auth::isTeacher() ? 'teacher' : 'student');
                    $this->redirect($home);
                } elseif (!empty($result['requires_2fa'])) {
                    $this->viewRaw('auth/login', ['pageTitle' => 'Two-factor authentication', 'requires2fa' => true]);
                    return;
                } else {
                    $this->flash('error', $result['error'] ?? 'Invalid credentials.');
                }
            }
        }
        $this->viewRaw('auth/login', ['pageTitle' => 'Sign in']);
    }

    /** 2FA setup: generate secret + show QR code, then verify. */
    public function setup2fa(): void
    {
        Auth::requireLogin();
        $uid = Auth::id();
        $existing = Database::fetch('SELECT * FROM user_2fa WHERE user_id = :uid', ['uid' => $uid]);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $this->input('action', 'enable');
            if ($action === 'enable') {
                $code = trim($this->input('code', ''));
                $secret = $this->input('secret', '');
                if (Security::verifyTotp($secret, $code)) {
                    $backups = Security::generateBackupCodes();
                    if ($existing) {
                        Database::update('user_2fa', [
                            'secret' => $secret,
                            'backup_codes' => json_encode($backups),
                            'enabled_at' => date('Y-m-d H:i:s'),
                        ], 'user_id = :uid', ['uid' => $uid]);
                    } else {
                        Database::insert('user_2fa', [
                            'user_id' => $uid,
                            'secret' => $secret,
                            'backup_codes' => json_encode($backups),
                            'enabled_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                    Database::update('users', ['two_factor_enabled' => 1], 'id = :id', ['id' => $uid]);
                    $this->flash('success', 'Two-factor authentication enabled! Save your backup codes.');
                    $this->redirect('auth/setup2fa');
                } else {
                    $this->flash('error', 'Invalid verification code. Try again.');
                }
            } elseif ($action === 'disable') {
                Database::delete('user_2fa', 'user_id = :uid', ['uid' => $uid]);
                Database::update('users', ['two_factor_enabled' => 0], 'id = :id', ['id' => $uid]);
                $this->flash('success', 'Two-factor authentication disabled.');
                $this->redirect('auth/setup2fa');
            }
        }
        // Generate a new pending secret (only if not yet enabled)
        $pendingSecret = null;
        $qrUri = null;
        $backupCodes = null;
        if ($existing && !empty($existing['enabled_at'])) {
            // Already enabled — show backup codes if any remain
            $backupCodes = json_decode($existing['backup_codes'] ?? '[]', true) ?: [];
        } else {
            // Generate new pending secret to display in QR
            $pendingSecret = Security::generateTotpSecret();
            $qrUri = Security::totpUri($pendingSecret, Auth::user()['email']);
        }
        $this->view('auth/setup2fa', [
            'pageTitle' => 'Two-Factor Authentication',
            'enabled' => !empty($existing) && !empty($existing['enabled_at']),
            'pendingSecret' => $pendingSecret,
            'qrUri' => $qrUri,
            'backupCodes' => $backupCodes,
        ]);
    }

    public function register(): void
    {
        if (Auth::check()) $this->redirect('');

        $allowRegistration = (Database::fetch('SELECT setting_value FROM settings WHERE setting_key = :k', ['k' => 'allow_registration'])['setting_value'] ?? '1') === '1';
        if (!$allowRegistration) {
            $this->flash('error', 'Public registration is disabled. Please contact an administrator.');
            $this->redirect('auth/login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($this->input('username', ''));
            $email    = trim($this->input('email', ''));
            $password = $this->input('password', '');
            $pass2    = $this->input('password2', '');
            $fullName = trim($this->input('full_name', ''));
            $role     = $this->input('role', 'student');

            // Validation
            if (!in_array($role, ['student', 'teacher'], true)) $role = 'student';
            if (strlen($username) < 3 || !preg_match('/^[A-Za-z0-9_\.]+$/', $username)) {
                $this->flash('error', 'Username must be at least 3 characters and contain only letters, numbers, dots and underscores.');
            } elseif (Database::fetch('SELECT id FROM users WHERE username = :u', ['u' => $username])) {
                $this->flash('error', 'This username is already taken.');
            } elseif (!isValidEmail($email)) {
                $this->flash('error', 'Please enter a valid email address.');
            } elseif (Database::fetch('SELECT id FROM users WHERE email = :e', ['e' => $email])) {
                $this->flash('error', 'This email is already registered.');
            } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
                $this->flash('error', 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.');
            } elseif ($password !== $pass2) {
                $this->flash('error', 'Passwords do not match.');
            } elseif (empty($fullName)) {
                $this->flash('error', 'Please enter your full name.');
            } else {
                $id = Auth::create([
                    'username'  => $username,
                    'email'     => $email,
                    'password'  => $password,
                    'full_name' => $fullName,
                    'role'      => $role,
                ]);
                Auth::login($username, $password);
                $this->flash('success', 'Account created! Welcome to doodle.');
                $this->redirect($role === 'teacher' ? 'teacher' : 'student');
            }
        }
        $this->viewRaw('auth/register', ['pageTitle' => 'Create an account']);
    }

    public function logout(): void
    {
        Auth::logout();
        $this->flash('success', 'You have been signed out.');
        $this->redirect('auth/login');
    }

    public function profile(): void
    {
        Auth::requireLogin();
        $user = Auth::user();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fullName = trim($this->input('full_name', ''));
            $bio      = trim($this->input('bio', ''));
            $phone    = trim($this->input('phone', ''));
            $email    = trim($this->input('email', ''));
            $newPass  = $this->input('password', '');

            if (!isValidEmail($email)) {
                $this->flash('error', 'Please enter a valid email.');
            } elseif (Database::fetch('SELECT id FROM users WHERE email = :e AND id != :id', ['e' => $email, 'id' => $user['id']])) {
                $this->flash('error', 'Email is already used by another account.');
            } else {
                $updates = [
                    'full_name' => $fullName,
                    'bio'       => $bio,
                    'phone'     => $phone,
                    'email'     => $email,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                if (!empty($newPass)) {
                    if (strlen($newPass) < PASSWORD_MIN_LENGTH) {
                        $this->flash('error', 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.');
                        $this->redirect('auth/profile');
                    }
                    $updates['password'] = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
                }
                // Avatar upload
                if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg','jpeg','png','gif','webp'], true) && $_FILES['avatar']['size'] < 5*1024*1024) {
                        $name = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
                        if (!is_dir(UPLOAD_DIR . '/avatars')) mkdir(UPLOAD_DIR . '/avatars', 0755, true);
                        move_uploaded_file($_FILES['avatar']['tmp_name'], UPLOAD_DIR . '/avatars/' . $name);
                        $updates['avatar'] = 'avatars/' . $name;
                    }
                }
                Database::update('users', $updates, 'id = :id', ['id' => $user['id']]);
                $this->flash('success', 'Profile updated.');
                $this->redirect('auth/profile');
            }
        }
        $this->view('auth/profile', ['pageTitle' => 'My Profile', 'user' => $user]);
    }

    /** AJAX endpoint: persist theme preference. */
    public function setTheme(): void
    {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['ok' => false], 405);
        $theme = $this->input('theme', 'auto');
        if (!in_array($theme, ['light', 'dark', 'auto'], true)) $theme = 'auto';
        try {
            Database::update('users', ['theme_preference' => $theme], 'id = :id', ['id' => Auth::id()]);
        } catch (Throwable $e) {
            // Column might not exist yet — try ALTER first
            try {
                Database::getInstance()->exec("ALTER TABLE users ADD COLUMN theme_preference VARCHAR(10) DEFAULT 'auto'");
                Database::update('users', ['theme_preference' => $theme], 'id = :id', ['id' => Auth::id()]);
            } catch (Throwable $e2) {}
        }
        $this->json(['ok' => true, 'theme' => $theme]);
    }
}
