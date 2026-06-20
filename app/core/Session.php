<?php
/**
 * doodle - Session management
 *
 * @package doodle
 * @author  Death Legion Team
 */

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            ]);
            session_start();
        }
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    /** Set a one-shot flash message (success / error / warning / info). */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function pullFlash(): array
    {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flashes;
    }
}
