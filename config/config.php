<?php
/**
 * doodle - LMS by Death Legion Team
 * Configuration file
 *
 * @package doodle
 * @author  Death Legion Team
 * @license GPL-3.0
 */

// ---------------------------------------------------------------------------
// Environment
// ---------------------------------------------------------------------------
define('APP_NAME', 'doodle');
define('APP_TEAM', 'Death Legion Team');
define('APP_VERSION', '0.2.0-beta');
define('APP_LICENSE', 'GPL-3.0');

// Detect environment: development | production
define('ENVIRONMENT', getenv('DOODLE_ENV') ?: 'development');

// Error reporting
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// ---------------------------------------------------------------------------
// Paths
// ---------------------------------------------------------------------------
define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR', ROOT_DIR . '/app');
define('PUBLIC_DIR', ROOT_DIR . '/public');
define('CONFIG_DIR', ROOT_DIR . '/config');
define('UPLOAD_DIR', PUBLIC_DIR . '/uploads');
define('DATABASE_DIR', ROOT_DIR . '/database');

// Base URL auto-detection (override in production if needed)
if (!defined('BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $script = ($script === '/' || $script === '\\') ? '' : $script;
    define('BASE_URL', $scheme . '://' . $host . $script);
}

// ---------------------------------------------------------------------------
// Database
// ---------------------------------------------------------------------------
// Default: SQLite (zero-config). Switch to MySQL by setting DB_TYPE to 'mysql'.
define('DB_TYPE', getenv('DOODLE_DB_TYPE') ?: 'sqlite');
define('DB_PATH', DATABASE_DIR . '/doodle.sqlite');
define('DB_HOST', getenv('DOODLE_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DOODLE_DB_PORT') ?: '3306');
define('DB_NAME', getenv('DOODLE_DB_NAME') ?: 'doodle');
define('DB_USER', getenv('DOODLE_DB_USER') ?: 'root');
define('DB_PASS', getenv('DOODLE_DB_PASS') ?: '');

// ---------------------------------------------------------------------------
// Session
// ---------------------------------------------------------------------------
define('SESSION_NAME', 'doodle_session');
define('SESSION_LIFETIME', 7200); // 2 hours

// ---------------------------------------------------------------------------
// Security
// ---------------------------------------------------------------------------
define('HASH_COST', 10); // bcrypt cost
define('CSRF_TOKEN_NAME', '_csrf_token');
define('PASSWORD_MIN_LENGTH', 8);

// ---------------------------------------------------------------------------
// File Uploads
// ---------------------------------------------------------------------------
define('MAX_UPLOAD_SIZE', 100 * 1024 * 1024); // 100 MB
define('ALLOWED_UPLOAD_TYPES', [
    // Documents
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'txt'  => 'text/plain',
    // Images
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    // Video
    'mp4'  => 'video/mp4',
    'webm' => 'video/webm',
    // Audio
    'mp3'  => 'audio/mpeg',
    'wav'  => 'audio/wav',
]);

// ---------------------------------------------------------------------------
// Pagination
// ---------------------------------------------------------------------------
define('PER_PAGE', 12);

// ---------------------------------------------------------------------------
// Installed flag
// ---------------------------------------------------------------------------
define('INSTALLED_FILE', ROOT_DIR . '/.installed');
define('IS_INSTALLED', file_exists(INSTALLED_FILE));
