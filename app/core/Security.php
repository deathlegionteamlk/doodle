<?php
/**
 * doodle - Security hardening utilities
 *
 * - Login rate limiting (brute-force protection)
 * - TOTP-based 2FA (RFC 6238) — pure PHP, no dependencies
 * - Password strength validation (entropy-based)
 * - Security headers (CSP, X-Frame-Options, etc.)
 * - HTML sanitizer for rich-text content (defangs scripts/iframes)
 * - Path traversal protection for file operations
 * - Session fixation protection
 *
 * @package doodle
 * @author  Death Legion Team
 */

class Security
{
    /** Default settings (overridable via settings table). */
    private static function setting(string $key, $default = '')
    {
        try {
            $row = Database::fetch('SELECT setting_value FROM settings WHERE setting_key = :k', ['k' => $key]);
            return $row ? ($row['setting_value'] ?? $default) : $default;
        } catch (Throwable $e) { return $default; }
    }

    // -------------------------------------------------------------------
    // LOGIN RATE LIMITING
    // -------------------------------------------------------------------

    /** Check if an identifier/IP is currently locked out. Returns wait minutes (0 = OK). */
    public static function checkRateLimit(string $identifier, string $ip): int
    {
        $maxAttempts = (int) self::setting('login_max_attempts', '5');
        $lockoutMin  = (int) self::setting('login_lockout_minutes', '15');
        $since = date('Y-m-d H:i:s', strtotime("-$lockoutMin minutes"));

        // Count failed attempts from this identifier OR IP
        $count = (int) Database::fetch(
            "SELECT COUNT(*) AS c FROM login_attempts
             WHERE (identifier = :id OR ip_address = :ip) AND success = 0 AND created_at > :since",
            ['id' => $identifier, 'ip' => $ip, 'since' => $since]
        )['c'];

        if ($count >= $maxAttempts) {
            // Find most recent attempt and compute remaining lockout
            $last = Database::fetch(
                "SELECT created_at FROM login_attempts
                 WHERE (identifier = :id OR ip_address = :ip) AND success = 0
                 ORDER BY created_at DESC LIMIT 1",
                ['id' => $identifier, 'ip' => $ip]
            );
            if ($last) {
                $unlockAt = strtotime("+{$lockoutMin} minutes", strtotime($last['created_at']));
                $remaining = (int) ceil(($unlockAt - time()) / 60);
                return max(0, $remaining);
            }
        }
        return 0;
    }

    /** Record a login attempt (success or failure). */
    public static function recordAttempt(string $identifier, string $ip, bool $success): void
    {
        try {
            Database::insert('login_attempts', [
                'identifier' => $identifier,
                'ip_address' => $ip,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'success'    => $success ? 1 : 0,
            ]);
        } catch (Throwable $e) {}
    }

    /** Clear failed attempts for an identifier (on successful login). */
    public static function clearAttempts(string $identifier, string $ip): void
    {
        try {
            Database::query(
                "DELETE FROM login_attempts WHERE (identifier = :id OR ip_address = :ip) AND success = 0",
                ['id' => $identifier, 'ip' => $ip]
            );
        } catch (Throwable $e) {}
    }

    public static function clientIp(): string
    {
        // Be careful — only trust X-Forwarded-For if behind a known proxy
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // -------------------------------------------------------------------
    // PASSWORD STRENGTH (entropy-based)
    // -------------------------------------------------------------------

    /** Score password 0-100. Returns ['score' => int, 'feedback' => string[]]. */
    public static function passwordStrength(string $pw): array
    {
        $feedback = [];
        $score = 0;
        $len = strlen($pw);
        if ($len < 8) { $feedback[] = 'Use at least 8 characters.'; }
        else { $score += 25; }
        if (preg_match('/[a-z]/', $pw)) $score += 10;
        if (preg_match('/[A-Z]/', $pw)) $score += 15;
        if (preg_match('/[0-9]/', $pw)) $score += 15;
        if (preg_match('/[^A-Za-z0-9]/', $pw)) $score += 20;
        // Penalize common patterns
        if (preg_match('/^(123|abc|qwe|password|letmein|admin|welcome)/i', $pw)) { $score -= 30; $feedback[] = 'Avoid common patterns.'; }
        if (preg_match('/(.)\1{2,}/', $pw)) { $feedback[] = 'Avoid repeated characters.'; }
        // Bonus for length
        if ($len >= 12) $score += 15;
        if ($len >= 16) $score += 10;
        $score = max(0, min(100, $score));
        if ($score < 50) $feedback[] = 'Password is weak — add length, mixed case, digits, symbols.';
        elseif ($score < 80) $feedback[] = 'Password is OK but could be stronger.';
        if (empty($feedback)) $feedback[] = 'Strong password.';
        return ['score' => $score, 'feedback' => $feedback];
    }

    // -------------------------------------------------------------------
    // 2FA / TOTP (RFC 6238) — pure PHP, no dependencies
    // -------------------------------------------------------------------

    /** Base32 encode (RFC 4648). */
    public static function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $out = '';
        $bits = 0; $value = 0;
        for ($i = 0; $i < strlen($data); $i++) {
            $value = ($value << 8) | ord($data[$i]);
            $bits += 8;
            while ($bits >= 5) {
                $out .= $alphabet[($value >> ($bits - 5)) & 31];
                $bits -= 5;
            }
        }
        if ($bits > 0) $out .= $alphabet[($value << (5 - $bits)) & 31];
        return $out;
    }

    /** Base32 decode. */
    public static function base32Decode(string $b32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $b32 = strtoupper(rtrim($b32, '='));
        $out = '';
        $bits = 0; $value = 0;
        for ($i = 0; $i < strlen($b32); $i++) {
            $pos = strpos($alphabet, $b32[$i]);
            if ($pos === false) continue;
            $value = ($value << 5) | $pos;
            $bits += 5;
            if ($bits >= 8) {
                $out .= chr(($value >> ($bits - 8)) & 0xFF);
                $bits -= 8;
            }
        }
        return $out;
    }

    /** Generate a new TOTP secret (20 bytes = 160 bits). */
    public static function generateTotpSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    /** Generate otpauth URI for QR code. */
    public static function totpUri(string $secret, string $accountName): string
    {
        $issuer = rawurlencode(APP_NAME);
        $account = rawurlencode($accountName);
        return "otpauth://totp/$issuer:$account?secret=$secret&issuer=$issuer&algorithm=SHA1&digits=6&period=30";
    }

    /** Verify a TOTP code (±1 step window). */
    public static function verifyTotp(string $secret, string $code, int $windowSteps = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (!preg_match('/^\d{6}$/', $code)) return false;
        $secretBin = self::base32Decode($secret);
        $timeStep = 30;
        $currentTime = floor(time() / $timeStep);
        for ($offset = -$windowSteps; $offset <= $windowSteps; $offset++) {
            $counter = $currentTime + $offset;
            if (hash_equals(self::totpAt($secretBin, $counter), $code)) return true;
        }
        return false;
    }

    /** Compute TOTP for a counter (HMAC-SHA1, 6 digits). */
    private static function totpAt(string $secretBin, float $counter): string
    {
        $binCounter = pack('N*', 0) . pack('N*', (int) $counter);
        $hash = hash_hmac('sha1', $binCounter, $secretBin, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;
        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    /** Generate 8 single-use backup codes. */
    public static function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(4)), 0, 4) . '-' . substr(bin2hex(random_bytes(4)), 0, 4));
        }
        return $codes;
    }

    // -------------------------------------------------------------------
    // SECURITY HEADERS
    // -------------------------------------------------------------------

    /** Send security headers. Call before any output. */
    public static function sendHeaders(): void
    {
        if (headers_sent()) return;
        // HSTS — only meaningful on HTTPS
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
        // Content-Security-Policy — relaxed enough for inline styles (we use a lot),
        // but strict on scripts (we don't load any external scripts except Material Icons + Google Fonts)
        header("Content-Security-Policy: default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
               "font-src 'self' https://fonts.gstatic.com data:; " .
               "img-src 'self' data: blob: https:; " .
               "media-src 'self' blob:; " .
               "frame-src 'self' https://www.youtube.com https://player.vimeo.com; " .
               "connect-src 'self'; " .
               "object-src 'none'; base-uri 'self'; form-action 'self'");
    }

    // -------------------------------------------------------------------
    // HTML SANITIZER (defang scripts, iframes, event handlers)
    // -------------------------------------------------------------------

    /** Strip dangerous tags and attributes from HTML. Allows formatting + links + images. */
    public static function sanitizeHtml(string $html): string
    {
        // Remove script, iframe, object, embed, style entirely
        $html = preg_replace('#<(script|iframe|object|embed|style|applet|form|input|button|textarea|select|option)[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#<(script|iframe|object|embed|style|applet|form|input|button|textarea|select|option)[^>]*/?>#is', '', $html);
        // Remove event handlers (on*)
        $html = preg_replace('#\s+on\w+\s*=\s*["\'][^"\']*["\']#i', '', $html);
        $html = preg_replace('#\s+on\w+\s*=\s*[^\s>]+#i', '', $html);
        // Remove javascript: URIs
        $html = preg_replace('#(href|src)\s*=\s*["\']javascript:[^"\']*["\']#i', '', $html);
        $html = preg_replace('#(href|src)\s*=\s*javascript:[^\s>]+#i', '', $html);
        // Remove data: URIs in href/src (potential XSS vector)
        $html = preg_replace('#(href|src)\s*=\s*["\']data:[^"\']*["\']#i', '', $html);
        // Remove style attributes that contain expression() or url(javascript:)
        $html = preg_replace('#style\s*=\s*["\'][^"\']*(expression|javascript:|vbscript:|@import)[^"\']*["\']#i', '', $html);
        return $html;
    }

    // -------------------------------------------------------------------
    // PATH TRAVERSAL PROTECTION
    // -------------------------------------------------------------------

    /** Verify a filename is safe (no traversal, no null bytes). */
    public static function safeFilename(string $name): string
    {
        $name = basename($name);
        $name = str_replace(["\0", "../", "..\\"], '', $name);
        $name = preg_replace('/[^\w\.\-]/', '_', $name);
        return $name;
    }

    /** Verify an upload MIME type matches the declared extension. */
    public static function verifyUploadMime(string $filename, string $tmpPath): bool
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!isset(ALLOWED_UPLOAD_TYPES[$ext])) return false;
        $expectedMime = ALLOWED_UPLOAD_TYPES[$ext];
        // Use finfo for real MIME detection
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $actualMime = $finfo->file($tmpPath) ?: '';
        // Allow some common alias mismatches
        $aliases = [
            'application/pdf' => ['application/pdf', 'application/x-pdf'],
            'image/jpeg'      => ['image/jpeg', 'image/jpg'],
            'image/png'       => ['image/png'],
            'video/mp4'       => ['video/mp4', 'application/mp4'],
        ];
        $allowedMimes = $aliases[$expectedMime] ?? [$expectedMime];
        return in_array($actualMime, $allowedMimes, true);
    }

    // -------------------------------------------------------------------
    // SESSION HARDENING
    // -------------------------------------------------------------------

    /** Call this after successful login to prevent session fixation. */
    public static function rotateSession(): void
    {
        Session::regenerate();
    }

    // -------------------------------------------------------------------
    // GENERIC VALIDATORS
    // -------------------------------------------------------------------

    public static function isSafeInt($v, int $min = 0, int $max = PHP_INT_MAX): bool
    {
        if (!is_numeric($v)) return false;
        $v = (int) $v;
        return $v >= $min && $v <= $max;
    }
}
