<?php
/**
 * OAuth Controller
 * Handles Google and GitHub OAuth authentication
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/oauth-config.php';
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/LoginSession.php';

class OAuthController {
    private $pdo = null;
    private $userModel = null;
    private $authService = null;

    public function __construct() {
        // Lazy load database connection only when needed (in callbacks)
    }

    /**
     * Get database connection (lazy load)
     */
    private function getPdo() {
        if ($this->pdo === null) {
            $this->pdo = Database::connect();
        }
        return $this->pdo;
    }

    /**
     * Get user model (lazy load)
     */
    private function getUserModel() {
        if ($this->userModel === null) {
            $this->userModel = new User($this->getPdo());
        }
        return $this->userModel;
    }

    /**
     * Get auth service (lazy load)
     */
    private function getAuthService() {
        if ($this->authService === null) {
            $this->authService = new AuthService($this->getPdo());
        }
        return $this->authService;
    }

    /**
     * Initiate Google OAuth login
     */
    public function googleLogin() {
// Clear any existing output
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    }

    /**
     * Handle Google OAuth callback
     */
    public function googleCallback() {
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        error_log('[Google OAuth] Callback hit with query: ' . json_encode($_GET));

        // Check if Google sent authorization code and state
        if (!isset($_GET['code']) || !isset($_GET['state'])) {
            error_log("[Google OAuth] Missing code or state");
            ob_end_clean();
            header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=oauth_failed");
            exit;
        }

        // Verify state token matches to prevent attacks
        if (!isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
            error_log("[Google OAuth] Invalid state token. Expected: " . ($_SESSION['oauth_state'] ?? 'null'));
            ob_end_clean();
            header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=oauth_csrf");
            exit;
        }

        // Remove state token after verification
        unset($_SESSION['oauth_state']);

        // Get authorization code from Google
        $code = $_GET['code'];

        // Check if credentials are still placeholder values
        if (strpos(GOOGLE_CLIENT_ID, 'YOUR_GOOGLE_CLIENT_ID') !== false || 
            strpos(GOOGLE_CLIENT_SECRET, 'YOUR_GOOGLE_CLIENT_SECRET') !== false) {
            error_log("[Google OAuth] ERROR: Credentials appear to be placeholder values! Check your .env file.");
            ob_end_clean();
            header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=oauth_config");
            exit;
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
            error_log("[Google OAuth] Token exchange failed: HTTP $httpCode");
            ob_end_clean();
            header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=oauth_token_failed");
            exit;
        }

        // Parse token response
        $tokenResult = json_decode($response, true);

        // Check if access token was received
        if (!isset($tokenResult['access_token'])) {
            error_log("[Google OAuth] No access token in response: " . $response);
            ob_end_clean();
            header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=oauth_token_missing");
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
            header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=oauth_userinfo_failed");
            exit;
        }

        // Parse user information
        $userInfo = json_decode($userInfoResponse, true);

        // Check if required user data is present
        if (!isset($userInfo['email']) || !isset($userInfo['id'])) {
            error_log("[Google OAuth] Missing email or ID in userinfo payload: " . $userInfoResponse);
            ob_end_clean();
            header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=oauth_incomplete");
            exit;
        }

        // Extract user information
        $googleId = $userInfo['id'];
        $email = $userInfo['email'];
        $firstName = $userInfo['given_name'] ?? '';
        $lastName = $userInfo['family_name'] ?? '';

        // Use full name if first/last name not available
        if (empty($firstName) && empty($lastName)) {
            $nameParts = explode(' ', $userInfo['name'] ?? '', 2);
            $firstName = $nameParts[0] ?? 'User';
            $lastName = $nameParts[1] ?? '';
        }

        // Check if user already exists
        $existingUser = $this->userModel->findUserByEmail($email);

        if ($existingUser) {
            $user = $existingUser;
            error_log("[Google OAuth] Existing user found: " . $email . " (role=" . ($user['role'] ?? 'null') . ")");
        } else {
            // Generate unique CIN for new user
            $cin = (int)substr($googleId, 0, 8) + 100000000;
            // Make sure CIN doesn't already exist
            while ($this->getUserModel()->getUserByCin($cin)) {
                $cin = rand(100000000, 999999999);
            }
            
            // Generate random password for new user
            $randomPassword = bin2hex(random_bytes(32));
            $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);
            
            // Create new user in database (OAuth users are email verified by default)
            $sql = "INSERT INTO users (cin, firstname, lastname, email, password, role, email_verified) VALUES (?, ?, ?, ?, ?, 'user', 1)";
            $stmt = $this->getPdo()->prepare($sql);
            $result = $stmt->execute([$cin, $firstName, $lastName, $email, $hashedPassword]);
            
            if (!$result) {
                error_log("[Google OAuth] Failed to create user record for email: " . $email);
                ob_end_clean();
                header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=user_creation_failed");
                exit;
            }
            
            // Get newly created user
            $user = $this->getUserModel()->getUserByCin($cin);
            error_log("[Google OAuth] New user created: " . $email . " (cin=" . $cin . ")");
        }

        // Store user information in session
        $_SESSION['user'] = $user;
        $_SESSION['userID'] = (int)$user['cin'];
        $_SESSION['firstname'] = $user['firstname'];
        $_SESSION['lastname'] = $user['lastname'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = trim($user['role'] ?? 'user');
        $_SESSION['username'] = trim($user['firstname'] . ' ' . $user['lastname']) ?: ($user['firstname'] ?? 'User');

        // Create login session record
        $loginSessionModel = new LoginSession($this->getPdo());
        $ipAddress = filter_var($_SERVER['REMOTE_ADDR'] ?? null, FILTER_VALIDATE_IP) ?: null;
        $device = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $sessionID = $loginSessionModel->createSession($_SESSION['userID'], $_SESSION['username'], $ipAddress, $device);

        if (!$sessionID) {
            $modelError = $loginSessionModel->getLastError();
            error_log("[Google OAuth] Failed to create login session. Error: " . ($modelError ?? 'unknown'));
            ob_end_clean();
            header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=sessionfail");
            exit;
        }

        $_SESSION['sessionID'] = $sessionID;
        error_log("[Google OAuth] Login success: userID=" . $_SESSION['userID'] . ", role=" . $_SESSION['role'] . ", sessionID=" . $sessionID);

        // Redirect based on user role
        $role = $_SESSION['role'];
        if ($role === 'admin') {
            ob_end_clean();
            header("Location: " . BASE_URL . "/index.php?action=dashboard");
            exit;
        } elseif ($role === 'user') {
            ob_end_clean();
            header("Location: " . BASE_URL . "/view/frontoffice/index.php");
            exit;
        }

        // Unknown role - redirect back to login
        ob_end_clean();
        header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=role");
        exit;
    }

    /**
     * Initiate GitHub OAuth login
     */
    public function githubLogin() {
        // Clear any existing output
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();

        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

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
    }

    /**
     * Handle GitHub OAuth callback
     */
    public function githubCallback() {
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if GitHub sent authorization code and state
        if (!isset($_GET['code']) || !isset($_GET['state'])) {
            error_log("GitHub OAuth callback: Missing code or state");
            ob_end_clean();
            header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=oauth_failed");
            exit;
        }

        // Verify state token matches to prevent attacks
        if (!isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
            error_log("GitHub OAuth callback: Invalid state token");
            ob_end_clean();
            header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=oauth_csrf");
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
            header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=oauth_token_failed");
            exit;
        }

        // Parse token response
        $tokenResult = json_decode($response, true);

        // Check if access token was received
        if (!isset($tokenResult['access_token'])) {
            error_log("GitHub OAuth: No access token in response");
            ob_end_clean();
            header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=oauth_token_missing");
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
            header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=oauth_userinfo_failed");
            exit;
        }

        // Parse user information
        $userInfo = json_decode($userInfoResponse, true);

        // Check if required user data is present
        if (!isset($userInfo['id'])) {
            error_log("GitHub OAuth: Missing ID in userinfo");
            ob_end_clean();
            header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=oauth_incomplete");
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

        // Check if user already exists
        $existingUser = $this->getUserModel()->findUserByEmail($email);

        if ($existingUser) {
            $user = $existingUser;
            error_log("GitHub OAuth: Existing user found: " . $email);
        } else {
            // Generate unique CIN for new user
            $cin = (int)substr((string)$githubId, 0, 8) + 200000000;
            // Make sure CIN doesn't already exist
            while ($this->getUserModel()->getUserByCin($cin)) {
                $cin = rand(200000000, 299999999);
            }
            
            // Generate random password for new user
            $randomPassword = bin2hex(random_bytes(32));
            $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);
            
            // Create new user in database (OAuth users are email verified by default)
            $sql = "INSERT INTO users (cin, firstname, lastname, email, password, role, email_verified) VALUES (?, ?, ?, ?, ?, 'user', 1)";
            $stmt = $this->getPdo()->prepare($sql);
            $result = $stmt->execute([$cin, $firstName, $lastName, $email, $hashedPassword]);
            
            if (!$result) {
                error_log("GitHub OAuth: Failed to create user");
                ob_end_clean();
                header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=user_creation_failed");
                exit;
            }
            
            // Get newly created user
            $user = $this->getUserModel()->getUserByCin($cin);
            error_log("GitHub OAuth: New user created: " . $email);
        }

        // Store user information in session
        $_SESSION['user'] = $user;
        $_SESSION['userID'] = (int)$user['cin'];
        $_SESSION['firstname'] = $user['firstname'];
        $_SESSION['lastname'] = $user['lastname'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = trim($user['role'] ?? 'user');
        $_SESSION['username'] = trim($user['firstname'] . ' ' . $user['lastname']) ?: ($user['firstname'] ?? 'User');

        // Create login session record
        $loginSessionModel = new LoginSession($this->getPdo());
        $ipAddress = filter_var($_SERVER['REMOTE_ADDR'] ?? null, FILTER_VALIDATE_IP) ?: null;
        $device = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $sessionID = $loginSessionModel->createSession($_SESSION['userID'], $_SESSION['username'], $ipAddress, $device);

        if (!$sessionID) {
            $modelError = $loginSessionModel->getLastError();
            error_log("GitHub OAuth: Failed to create login session. Error: " . ($modelError ?? 'unknown'));
            ob_end_clean();
            header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=sessionfail");
            exit;
        }

        $_SESSION['sessionID'] = $sessionID;
        error_log("GitHub OAuth: Login successful for userID=" . $_SESSION['userID'] . ", role=" . $_SESSION['role']);

        // Redirect based on user role
        $role = $_SESSION['role'];
        if ($role === 'admin') {
            ob_end_clean();
            header("Location: " . BASE_URL . "/index.php?action=dashboard");
            exit;
        } elseif ($role === 'user') {
            ob_end_clean();
            header("Location: " . BASE_URL . "/view/frontoffice/index.php");
            exit;
        }

        // Unknown role - redirect back to login
        ob_end_clean();
        header("Location: " . BASE_URL . "/view/frontoffice/login.php?error=role");
        exit;
    }
}
?>
