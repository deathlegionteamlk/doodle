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
if (IS_INSTALLED && !file_exists(ROOT_DIR . '/.v2_migrated')) {
    try {
        require_once DATABASE_DIR . '/schema.php';
        require_once DATABASE_DIR . '/schema_v2.php';
        $db = Database::getInstance();
        Schema::install($db, DB_TYPE);
        SchemaV2::install($db);
        @file_put_contents(ROOT_DIR . '/.v2_migrated', date('Y-m-d H:i:s'));
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

// Route the request
$router = new Router();
$router->dispatch();
