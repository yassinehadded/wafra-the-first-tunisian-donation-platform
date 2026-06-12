<?php
/**
 * Google OAuth Callback
 * Handles the callback from Google OAuth - matches wafra-user implementation
 */

// Clear any output before redirecting
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Configure error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration and models
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/oauth-config.php';
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/LoginSession.php';

// Force correct redirect base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$redirectBase = $protocol . '://' . $host . '/wafra/wafra-integration';

error_log('[Google OAuth] Callback hit with query: ' . json_encode($_GET));

// Check if Google sent authorization code and state
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    error_log("[Google OAuth] Missing code or state");
    ob_end_clean();
    header("Location: " . $redirectBase . "/view/frontoffice/login.php?error=oauth_failed");
    exit;
}

// Verify state token matches to prevent attacks
if (!isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    error_log("[Google OAuth] Invalid state token. Expected: " . ($_SESSION['oauth_state'] ?? 'null'));
    ob_end_clean();
    header("Location: " . $redirectBase . "/view/frontoffice/login.php?error=oauth_csrf");
    exit;
}

// Remove state token after verification
unset($_SESSION['oauth_state']);

// Get authorization code from Google
$code = $_GET['code'];

// Debug: Log credential info (partial for security)
$clientIdPreview = substr(GOOGLE_CLIENT_ID, 0, 20) . '...';
$clientSecretPreview = strlen(GOOGLE_CLIENT_SECRET) > 0 ? substr(GOOGLE_CLIENT_SECRET, 0, 4) . '...' : '[EMPTY]';
error_log("[Google OAuth] Using Client ID: " . $clientIdPreview . " (length: " . strlen(GOOGLE_CLIENT_ID) . ")");
error_log("[Google OAuth] Using Client Secret: " . $clientSecretPreview . " (length: " . strlen(GOOGLE_CLIENT_SECRET) . ")");
error_log("[Google OAuth] Using Redirect URI: " . GOOGLE_REDIRECT_URI);

// Check if credentials are still placeholder values
if (strpos(GOOGLE_CLIENT_ID, 'YOUR_GOOGLE_CLIENT_ID') !== false || 
    strpos(GOOGLE_CLIENT_SECRET, 'YOUR_GOOGLE_CLIENT_SECRET') !== false) {
    error_log("[Google OAuth] ERROR: Credentials appear to be placeholder values! Check your .env file.");
    ob_end_clean();
    header("Location: " . $redirectBase . "/view/frontoffice/login.php?error=oauth_config");
    exit;
}

// Validate client secret length (Google secrets are typically 24+ characters)
if (strlen(GOOGLE_CLIENT_SECRET) < 20) {
    error_log("[Google OAuth] WARNING: Client Secret is too short (" . strlen(GOOGLE_CLIENT_SECRET) . " chars). Google secrets are usually 24+ characters.");
    error_log("[Google OAuth] This suggests the secret may be incomplete or truncated.");
    error_log("[Google OAuth] Please verify you copied the ENTIRE secret from Google Cloud Console.");
}

// Prepare data to exchange code for access token
$tokenData = [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
];

// Send request to Google to get access token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, GOOGLE_TOKEN_URL);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check if token exchange failed
if ($httpCode !== 200) {
    $errorDetails = json_decode($response, true);
    $errorMsg = isset($errorDetails['error']) ? $errorDetails['error'] : 'unknown';
    $errorDesc = isset($errorDetails['error_description']) ? $errorDetails['error_description'] : 'no description';
    error_log("[Google OAuth] Token exchange failed: HTTP $httpCode");
    error_log("[Google OAuth] Error: $errorMsg - $errorDesc");
    error_log("[Google OAuth] Full response: " . $response);
    
    // Provide more specific error message
    if ($errorMsg === 'invalid_client') {
        error_log("[Google OAuth] CRITICAL: Client ID or Client Secret is incorrect. Please verify:");
        error_log("[Google OAuth] 1. GOOGLE_CLIENT_ID in .env matches Google Cloud Console");
        error_log("[Google OAuth] 2. GOOGLE_CLIENT_SECRET in .env matches Google Cloud Console");
        error_log("[Google OAuth] 3. Redirect URI in .env matches authorized redirect URI in Google Cloud Console");
    }
    
    ob_end_clean();
    header("Location: " . $redirectBase . "/view/frontoffice/login.php?error=oauth_token_failed");
    exit;
}

// Parse token response
$tokenResult = json_decode($response, true);

// Check if access token was received
if (!isset($tokenResult['access_token'])) {
    error_log("[Google OAuth] No access token in response: " . $response);
    ob_end_clean();
    header("Location: " . $redirectBase . "/view/frontoffice/login.php?error=oauth_token_missing");
    exit;
}

// Get access token
$accessToken = $tokenResult['access_token'];

// Request user information from Google
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, GOOGLE_USERINFO_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);

$userInfoResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check if user info request failed
if ($httpCode !== 200) {
    error_log("[Google OAuth] Userinfo failed: HTTP $httpCode - $userInfoResponse");
    ob_end_clean();
    header("Location: " . $redirectBase . "/view/frontoffice/login.php?error=oauth_userinfo_failed");
    exit;
}

// Parse user information
$userInfo = json_decode($userInfoResponse, true);

// Check if required user data is present
if (!isset($userInfo['email']) || !isset($userInfo['id'])) {
    error_log("[Google OAuth] Missing email or ID in userinfo payload: " . $userInfoResponse);
    ob_end_clean();
    header("Location: " . $redirectBase . "/view/frontoffice/login.php?error=oauth_incomplete");
    exit;
}

// Extract user information
$googleId = $userInfo['id'];
$email = $userInfo['email'];
$firstName = $userInfo['given_name'] ?? '';
$lastName = $userInfo['family_name'] ?? '';
$picture = $userInfo['picture'] ?? '';

// Use full name if first/last name not available
if (empty($firstName) && empty($lastName)) {
    $nameParts = explode(' ', $userInfo['name'] ?? '', 2);
    $firstName = $nameParts[0] ?? 'User';
    $lastName = $nameParts[1] ?? '';
}

// Get database connection and user model
$pdo = Database::connect();
$userModel = new User($pdo);

// Check if user already exists
$existingUser = $userModel->findUserByEmail($email);

if ($existingUser) {
    // Use existing user
    $user = $existingUser;
    error_log("[Google OAuth] Existing user found: " . $email . " (role=" . ($user['role'] ?? 'null') . ")");
} else {
    // Generate unique CIN for new user
    $cin = (int)substr($googleId, 0, 8) + 100000000;
    // Make sure CIN doesn't already exist
    while ($userModel->getUserByCin($cin)) {
        $cin = rand(100000000, 999999999);
    }
    
    // Generate random password for new user
    $randomPassword = bin2hex(random_bytes(32));
    $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);
    
    // Create new user in database (OAuth users are email verified by default)
    $sql = "INSERT INTO users (cin, firstname, lastname, email, password, role, email_verified) VALUES (?, ?, ?, ?, ?, 'user', 1)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$cin, $firstName, $lastName, $email, $hashedPassword]);
    
    // Check if user creation failed
    if (!$result) {
        error_log("[Google OAuth] Failed to create user record for email: " . $email);
        ob_end_clean();
        header("Location: " . $redirectBase . "/view/frontoffice/login.php?error=user_creation_failed");
        exit;
    }
    
    // Get newly created user
    $user = $userModel->getUserByCin($cin);
    error_log("[Google OAuth] New user created: " . $email . " (cin=" . $cin . ")");
}

// Store user information in session
$_SESSION['user'] = $user;
$_SESSION['userID'] = (int)$user['cin'];
$_SESSION['firstname'] = $user['firstname'];
$_SESSION['lastname'] = $user['lastname'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = trim($user['role'] ?? 'user');

// Create username for session
$usernameForSession = trim($user['firstname'] . ' ' . $user['lastname']) ?: ($user['firstname'] ?? 'User');
$_SESSION['username'] = $usernameForSession;

// Create login session record
$loginSessionModel = new LoginSession($pdo);
$ipAddress = filter_var($_SERVER['REMOTE_ADDR'] ?? null, FILTER_VALIDATE_IP) ?: null;
$device = $_SERVER['HTTP_USER_AGENT'] ?? null;

$sessionID = $loginSessionModel->createSession($_SESSION['userID'], $usernameForSession, $ipAddress, $device);

// Check if session creation failed
if (!$sessionID) {
    $modelError = $loginSessionModel->getLastError();
    error_log("[Google OAuth] Failed to create login session. Error: " . ($modelError ?? 'unknown'));
    ob_end_clean();
    header("Location: " . $redirectBase . "/view/frontoffice/login.php?error=sessionfail");
    exit;
}

// Store session ID
$_SESSION['sessionID'] = $sessionID;
error_log("[Google OAuth] Login success: userID=" . $_SESSION['userID'] . ", role=" . $_SESSION['role'] . ", sessionID=" . $sessionID);

// Redirect based on user role
$role = $_SESSION['role'];

// Redirect admin to dashboard
if ($role === 'admin') {
    error_log("[Google OAuth] Redirecting admin to dashboard");
    ob_end_clean();
    header("Location: " . $redirectBase . "/index.php?action=dashboard");
    exit;
}

// Redirect regular user to main site (index.php with events)
if ($role === 'user') {
    error_log("[Google OAuth] Redirecting user to main site");
    ob_end_clean();
    header("Location: " . $redirectBase . "/view/frontoffice/index.php");
    exit;
}

// Unknown role - redirect back to login
error_log("[Google OAuth] Unknown role encountered: " . $role);
ob_end_clean();
header("Location: " . $redirectBase . "/view/frontoffice/login.php?error=role");
exit;
?>

