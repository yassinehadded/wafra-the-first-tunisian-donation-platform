<?php
/**
 * Main Configuration File
 * Loads environment variables and defines application constants
 */

// Load environment variables from .env file if it exists
require_once __DIR__ . '/env_loader.php';

// Helper function to get environment variable or use default value
function getEnvOrDefault($key, $default) {
    $value = getenv($key);
    // If not set or empty string, use default
    if ($value === false || trim($value) === '') {
        return $default;
    }
    return $value;
}

// Database configuration - use environment variables or defaults
if (!defined('DB_HOST')) {
    define('DB_HOST', getEnvOrDefault('DB_HOST', 'localhost'));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getEnvOrDefault('DB_NAME', 'wafra'));
}
if (!defined('DB_USER')) {
    define('DB_USER', getEnvOrDefault('DB_USER', 'root'));
}
if (!defined('DB_PASS')) {
    $dbPassEnv = getenv('DB_PASS');
    // If password is not set or empty, use empty string (no password for XAMPP default)
    if ($dbPassEnv === false || $dbPassEnv === null || trim($dbPassEnv) === '') {
        define('DB_PASS', '');
    } else {
        define('DB_PASS', trim($dbPassEnv));
    }
}

// Application base URL
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $protocol . '://' . $host . '/wafra/wafra-integration';
    define('BASE_URL', $baseUrl);
}

// Error reporting (enable in development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Uncomment to disable error display in production
// error_reporting(0);
// ini_set('display_errors', 0);

// Load Database class
require_once __DIR__ . '/Database.php';
