<?php
/**
 * doodle - Front Controller (entry point)
 * LMS by Death Legion Team — GPL-3.0
 *
 * @package doodle
 * @author  Death Legion Team
 */

// Load configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// If not installed, redirect to installer
if (!IS_INSTALLED && basename($_SERVER['SCRIPT_NAME']) !== 'install.php') {
    header('Location: ' . BASE_URL . '/install.php');
    exit;
}

// Autoloader for core, controllers, models, helpers
spl_autoload_register(function ($class) {
    $candidates = [
        APP_DIR . '/core/'      . $class . '.php',
        APP_DIR . '/controllers/' . $class . '.php',
        APP_DIR . '/models/'    . $class . '.php',
        APP_DIR . '/helpers/'   . $class . '.php',
        CONFIG_DIR . '/'        . $class . '.php',
    ];
    foreach ($candidates as $f) {
        if (file_exists($f)) {
            require_once $f;
            return;
        }
    }
});

// Helper functions
require_once APP_DIR . '/helpers/functions.php';

// Run schema migrations if needed (auto-upgrade for existing installs)
if (IS_INSTALLED && !file_exists(ROOT_DIR . '/.v3_migrated')) {
    try {
        require_once DATABASE_DIR . '/schema.php';
        require_once DATABASE_DIR . '/schema_v2.php';
        require_once DATABASE_DIR . '/schema_v3.php';
        $db = Database::getInstance();
        Schema::install($db, DB_TYPE);
        SchemaV2::install($db);
        SchemaV3::install($db);
        @file_put_contents(ROOT_DIR . '/.v3_migrated', date('Y-m-d H:i:s'));
    } catch (Throwable $e) {
        // Non-fatal: continue serving the request
        if (ENVIRONMENT === 'development') {
            error_log('Schema migration failed: ' . $e->getMessage());
        }
    }
}

// Bootstrap session, auth
Session::start();
CSRF::token();
Auth::init();

// Send security headers (must come before any output)
Security::sendHeaders();

// Determine current route for maintenance check
$currentRoute = $_GET['r'] ?? '';

// Maintenance mode check (admins + auth/login still work)
if (IS_INSTALLED) {
    $maintenance = false;
    try {
        $row = Database::fetch('SELECT setting_value FROM settings WHERE setting_key = :k', ['k' => 'maintenance_mode']);
        $maintenance = ($row && $row['setting_value'] === '1');
    } catch (Throwable $e) {}
    if ($maintenance && !Auth::isAdmin()
        && $currentRoute !== 'auth/login'
        && $currentRoute !== 'auth/logout'
        && strpos($currentRoute, 'admin') !== 0
        && $currentRoute !== 'api/auth'
        && $currentRoute !== 'api/me') {
        $msg = Database::fetch('SELECT setting_value FROM settings WHERE setting_key = :k', ['k' => 'maintenance_message'])['setting_value'] ?? 'Under maintenance. Please check back soon.';
        http_response_code(503);
        $appName = APP_NAME; $appTeam = APP_TEAM; $appVersion = APP_VERSION;
        echo "<!DOCTYPE html><html><head><title>Maintenance · $appName</title><style>body{font-family:sans-serif;background:#0F172A;color:#F1F5F9;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}div{text-align:center;max-width:500px;padding:40px}h1{font-size:3rem;margin:0 0 10px;color:#6366F1}.mark{width:80px;height:80px;background:linear-gradient(135deg,#4F46E5,#EC4899);border-radius:18px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2.5rem;font-weight:800;margin:0 auto 20px}p{color:#94A3B8;line-height:1.6}footer{margin-top:30px;font-size:.8rem;color:#64748B}</style></head><body><div><div class='mark'>d</div><h1>$appName</h1><p>$msg</p><footer>$appVersion · GPL-3.0 · $appTeam</footer></div></body></html>";
        exit;
    }
}

// Route the request
$router = new Router();
$router->dispatch();
