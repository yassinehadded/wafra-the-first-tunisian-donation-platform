<?php
/**
 * GitHub OAuth Callback
 * Handles the callback from GitHub OAuth - matches wafra-user implementation
 */

// Clear any output before redirecting
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

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

// Force correct $redirectBase to prevent redirect loops
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$redirectBase = $protocol . '://' . $host . '/wafra/wafra-integration';

// Check if GitHub sent authorization code and state
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    error_log("GitHub OAuth callback: Missing code or state");
    ob_end_clean();
    header("Location: " . $redirectBase . "/view/frontoffice/login.php?error=oauth_failed");
    exit;
}

// Verify state token matches to prevent attacks
if (!isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    error_log("GitHub OAuth callback: Invalid state token");
    ob_end_clean();
    header("Location: " . $redirectBase . "/view/frontoffice/login.php?error=oauth_csrf");
    exit;
}

// Remove state token after verification
unset($_SESSION['oauth_state']);

// Get authorization code from GitHub
$code = $_GET['code'];

// Prepare data to exchange code for access token
$tokenData = [
    'client_id' => GITHUB_CLIENT_ID,
    'client_secret' => GITHUB_CLIENT_SECRET,
    'code' => $code,
    'redirect_uri' => GITHUB_REDIRECT_URI
];

// Send request to GitHub to get access token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, GITHUB_TOKEN_URL);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/x-www-form-urlencoded'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check if token exchange failed
if ($httpCode !== 200) {
    error_log("GitHub OAuth token exchange failed: HTTP $httpCode - $response");
    ob_end_clean();
    header("Location: " . $redirectBase . "/view/frontoffice/login.php?error=oauth_token_failed");
    exit;
}

// Parse token response
$tokenResult = json_decode($response, true);

// Check if access token was received
if (!isset($tokenResult['access_token'])) {
    error_log("GitHub OAuth: No access token in response");
    ob_end_clean();
    header("Location: " . $redirectBase . "/view/frontoffice/login.php?error=oauth_token_missing");
    exit;
}

// Get access token
$accessToken = $tokenResult['access_token'];

// Request user information from GitHub
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, GITHUB_USERINFO_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: token ' . $accessToken,
    'User-Agent: WAFRA-OAuth'
]);

$userInfoResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check if user info request failed
if ($httpCode !== 200) {
    error_log("GitHub OAuth userinfo failed: HTTP $httpCode");
    ob_end_clean();
    header("Location: " . $redirectBase . "/view/frontoffice/login.php?error=oauth_userinfo_failed");
    exit;
}

// Parse user information
$userInfo = json_decode($userInfoResponse, true);

// Check if required user data is present
if (!isset($userInfo['id'])) {
    error_log("GitHub OAuth: Missing ID in userinfo");
    ob_end_clean();
    header("Location: " . $redirectBase . "/view/frontoffice/login.php?error=oauth_incomplete");
    exit;
}

// Extract user information
$githubId = $userInfo['id'];
$username = $userInfo['login'] ?? '';
$name = $userInfo['name'] ?? $username;
$avatarUrl = $userInfo['avatar_url'] ?? '';

// Split name into first and last name
$nameParts = explode(' ', $name, 2);
$firstName = $nameParts[0] ?? 'User';
$lastName = $nameParts[1] ?? '';

// Request user email from GitHub
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, GITHUB_EMAIL_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: token ' . $accessToken,
    'User-Agent: WAFRA-OAuth',
    'Accept: application/vnd.github.v3+json'
]);

$emailResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Try to get email from response
$email = null;
if ($httpCode === 200) {
    $emails = json_decode($emailResponse, true);
    if (is_array($emails)) {
        // Look for primary email
        foreach ($emails as $emailData) {
            if (isset($emailData['primary']) && $emailData['primary'] && isset($emailData['email'])) {
                $email = $emailData['email'];
                break;
            }
        }
        // Use first email if no primary found
        if (!$email && isset($emails[0]['email'])) {
            $email = $emails[0]['email'];
        }
    }
}

// Generate email if GitHub didn't provide one
if (!$email) {
    $email = $username . '@github.local';
    error_log("GitHub OAuth: No email found, using generated: $email");
}

// Get database connection and user model
$pdo = Database::connect();
$userModel = new User($pdo);

// Check if user already exists
$existingUser = $userModel->findUserByEmail($email);

if ($existingUser) {
    // Use existing user
    $user = $existingUser;
    error_log("GitHub OAuth: Existing user found: " . $email);
} else {
    // Generate unique CIN for new user
    $cin = (int)substr((string)$githubId, 0, 8) + 200000000;
    // Make sure CIN doesn't already exist
    while ($userModel->getUserByCin($cin)) {
        $cin = rand(200000000, 299999999);
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
        error_log("GitHub OAuth: Failed to create user");
        ob_end_clean();
        header("Location: " . $redirectBase . "/view/frontoffice/login.php?error=user_creation_failed");
        exit;
    }
    
    // Get newly created user
    $user = $userModel->getUserByCin($cin);
    error_log("GitHub OAuth: New user created: " . $email);
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
    error_log("GitHub OAuth: Failed to create login session. Error: " . ($modelError ?? 'unknown'));
    ob_end_clean();
    header("Location: " . $redirectBase . "/view/frontoffice/login.php?error=sessionfail");
    exit;
}

// Store session ID
$_SESSION['sessionID'] = $sessionID;
error_log("GitHub OAuth: Login successful for userID=" . $_SESSION['userID'] . ", role=" . $_SESSION['role']);

// Redirect based on user role
$role = $_SESSION['role'];

// Redirect admin to dashboard
if ($role === 'admin') {
    ob_end_clean();
    header("Location: " . $redirectBase . "/index.php?action=dashboard");
    exit;
}

// Redirect regular user to main site (index.php with events)
if ($role === 'user') {
    ob_end_clean();
    header("Location: " . $redirectBase . "/view/frontoffice/index.php");
    exit;
}

// Unknown role - redirect back to login
ob_end_clean();
header("Location: " . $redirectBase . "/view/frontoffice/login.php?error=role");
exit;
?>

