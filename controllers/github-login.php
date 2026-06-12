<?php
/**
 * GitHub OAuth Login
 * Standalone file for OAuth initiation - matches wafra-user implementation
 */

// Clear any existing output
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/oauth-config.php';

// Check if GitHub credentials are configured
if (strpos(GITHUB_CLIENT_ID, 'YOUR_GITHUB_CLIENT_ID') !== false || 
    strpos(GITHUB_CLIENT_SECRET, 'YOUR_GITHUB_CLIENT_SECRET') !== false ||
    empty(GITHUB_CLIENT_ID) || empty(GITHUB_CLIENT_SECRET)) {
    error_log("[GitHub OAuth] ERROR: GitHub credentials not configured in .env file");
    ob_end_clean();
    header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=github_not_configured");
    exit;
}

// Generate random state token for security
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_provider'] = 'github';

error_log("[GitHub OAuth] Initiating login with Client ID: " . substr(GITHUB_CLIENT_ID, 0, 10) . "...");

// Build OAuth authorization URL parameters
$params = [
    'client_id' => GITHUB_CLIENT_ID,
    'redirect_uri' => GITHUB_REDIRECT_URI,
    'scope' => 'user:email',
    'state' => $state
];

// Create full authorization URL
$authUrl = GITHUB_AUTH_URL . '?' . http_build_query($params);

error_log("[GitHub OAuth] Redirecting to: " . GITHUB_AUTH_URL);

// Redirect user to GitHub login page
ob_end_clean();
header("Location: " . $authUrl);
exit;
?>


















