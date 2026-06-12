<?php
/**
 * OAuth Configuration
 * Loads OAuth credentials from environment variables
 */
require_once __DIR__ . '/env_loader.php';

// Get OAuth configuration from environment or use default value
function oauthEnvOrDefault($key, $default)
{
    $value = getenv($key);

    // Check $_ENV if getenv returns false
    if ($value === false && isset($_ENV[$key])) {
        $value = $_ENV[$key];
    }

    // Return value if it exists and is not empty
    if ($value !== false && $value !== null && trim($value) !== '') {
        return trim($value);
    }

    // Return default if value not found
    return $default;
}

// Auto-detect base URL for redirect URIs
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
$basePath = '/wafra-integration';
if (strpos($scriptName, '/wafra/wafra-integration') !== false) {
    $basePath = '/wafra/wafra-integration';
} elseif (strpos($scriptName, '/wafra-integration') !== false) {
    $basePath = '/wafra-integration';
}
$baseUrl = 'http://' . $host . $basePath;

// Set Google OAuth credentials from environment
define('GOOGLE_CLIENT_ID', oauthEnvOrDefault('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com'));
define('GOOGLE_CLIENT_SECRET', oauthEnvOrDefault('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET'));
define('GOOGLE_REDIRECT_URI', oauthEnvOrDefault('GOOGLE_REDIRECT_URI', $baseUrl . '/controllers/google-callback.php'));

// Set GitHub OAuth credentials from environment
define('GITHUB_CLIENT_ID', oauthEnvOrDefault('GITHUB_CLIENT_ID', 'YOUR_GITHUB_CLIENT_ID'));
define('GITHUB_CLIENT_SECRET', oauthEnvOrDefault('GITHUB_CLIENT_SECRET', 'YOUR_GITHUB_CLIENT_SECRET'));
define('GITHUB_REDIRECT_URI', oauthEnvOrDefault('GITHUB_REDIRECT_URI', $baseUrl . '/controllers/github-callback.php'));

// Set Google OAuth API URLs
define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USERINFO_URL', 'https://www.googleapis.com/oauth2/v2/userinfo');

// Set GitHub OAuth API URLs
define('GITHUB_AUTH_URL', 'https://github.com/login/oauth/authorize');
define('GITHUB_TOKEN_URL', 'https://github.com/login/oauth/access_token');
define('GITHUB_USERINFO_URL', 'https://api.github.com/user');
define('GITHUB_EMAIL_URL', 'https://api.github.com/user/emails');
