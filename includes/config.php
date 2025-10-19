<?php
/**
 * Main Configuration File
 * Loads all configuration files and initializes the application
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load constants
require_once __DIR__ . '/../config/constants.php';

// Load database configuration
require_once CONFIG_PATH . '/database.php';

// Load helper functions
require_once __DIR__ . '/helpers.php';

// Load session management
require_once __DIR__ . '/session.php';

// Autoload classes
spl_autoload_register(function ($class) {
    $paths = [
        BACKEND_PATH . '/models/' . $class . '.php',
        BACKEND_PATH . '/controllers/' . $class . '.php',
        BACKEND_PATH . '/services/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Set error handling based on environment
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
}

// Set timezone
date_default_timezone_set(SYSTEM_TIMEZONE);

// Initialize database connection
try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
} catch (Exception $e) {
    if (DEBUG_MODE) {
        die('Database Error: ' . $e->getMessage());
    } else {
        die('System temporarily unavailable. Please try again later.');
    }
}

// Check maintenance mode
if (MAINTENANCE_MODE && !isset($_SESSION['is_admin'])) {
    http_response_code(503);
    include INCLUDES_PATH . '/maintenance.php';
    exit;
}