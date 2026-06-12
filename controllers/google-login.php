<?php
/**
 * Google OAuth Login
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

// Generate random state token for security
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_provider'] = 'google';

// Build OAuth authorization URL parameters
$params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'access_type' => 'online',
    'prompt' => 'consent'
];

// Create full authorization URL
$authUrl = GOOGLE_AUTH_URL . '?' . http_build_query($params);

// Redirect user to Google login page
ob_end_clean();
header("Location: " . $authUrl);
exit;
?>


















