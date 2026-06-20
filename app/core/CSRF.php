<?php
/**
 * doodle - CSRF protection
 *
 * @package doodle
 * @author  Death Legion Team
 */

class CSRF
{
    public static function token(): string
    {
        if (!Session::has(CSRF_TOKEN_NAME)) {
            Session::set(CSRF_TOKEN_NAME, bin2hex(random_bytes(32)));
        }
        return Session::get(CSRF_TOKEN_NAME);
    }

    public static function verify(): bool
    {
        $token = $_POST[CSRF_TOKEN_NAME] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!hash_equals(Session::get(CSRF_TOKEN_NAME, ''), $token)) {
            return false;
        }
        return true;
    }

    public static function field(): string
    {
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . self::token() . '">';
    }
}
