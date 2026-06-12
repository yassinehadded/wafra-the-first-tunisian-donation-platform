<?php
/**
 * Authentication Service
 * Handles authentication logic and session management
 */
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/LoginSession.php';

class AuthService {
    private $pdo;
    private $userModel;
    private $loginSessionModel;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->userModel = new User($pdo);
        $this->loginSessionModel = new LoginSession($pdo);
    }

    /**
     * Authenticate user with email and password
     * @param string $email
     * @param string $password
     * @return array|false User data on success, false on failure
     */
    public function authenticate($email, $password) {
        $user = $this->userModel->signIn($email, $password);
        
        if (!$user) {
            error_log("AuthService::authenticate - User not found or invalid password for email: " . $email);
            return false;
        }

        error_log("AuthService::authenticate - User found: CIN=" . $user['cin'] . ", email_verified=" . ($user['email_verified'] ?? 'NULL'));

        // Check if email is verified
        // email_verified can be 0, NULL, or empty string - all should be treated as unverified
        $emailVerified = isset($user['email_verified']) ? (int)$user['email_verified'] : 0;
        
        if ($emailVerified !== 1) {
            error_log("AuthService::authenticate - Email not verified for user CIN: " . $user['cin']);
            // Send verification email again
            $this->userModel->sendVerificationEmail(
                $user['cin'],
                $user['email'],
                trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''))
            );
            return ['error' => 'email_unverified'];
        }

        error_log("AuthService::authenticate - Authentication successful for user CIN: " . $user['cin']);
        return $user;
    }

    /**
     * Create login session for user
     * @param int $userId
     * @param string $username
     * @return string|false Session ID on success, false on failure
     */
    public function createLoginSession($userId, $username) {
        $ipAddress = filter_var($_SERVER['REMOTE_ADDR'] ?? null, FILTER_VALIDATE_IP) ?: null;
        $device = $_SERVER['HTTP_USER_AGENT'] ?? null;

        return $this->loginSessionModel->createSession($userId, $username, $ipAddress, $device);
    }

    /**
     * Register new user
     * @param array $userData
     * @return bool
     */
    public function register($userData) {
        // Check if email already exists
        $existingUser = $this->userModel->findUserByEmail($userData['email']);
        if ($existingUser) {
            return ['error' => 'email_exists'];
        }

        // Check if CIN already exists
        $existingCin = $this->userModel->getUserByCin($userData['cin']);
        if ($existingCin) {
            return ['error' => 'cin_exists'];
        }

        // Add user
        $result = $this->userModel->addUser(
            $userData['cin'],
            $userData['firstname'],
            $userData['lastname'],
            $userData['email'],
            $userData['password']
        );

        if ($result) {
            // Send verification email
            $this->userModel->sendVerificationEmail(
                $userData['cin'],
                $userData['email'],
                $userData['firstname'] . ' ' . $userData['lastname']
            );
            return true;
        }

        return false;
    }

    /**
     * Check if user is authenticated
     * @return bool
     */
    public function isAuthenticated() {
        return !empty($_SESSION['userID']) && !empty($_SESSION['sessionID']);
    }

    /**
     * Check if user has specific role
     * @param string $role
     * @return bool
     */
    public function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }

    /**
     * Get current user ID
     * @return int|null
     */
    public function getCurrentUserId() {
        return $_SESSION['userID'] ?? null;
    }

    /**
     * Logout user
     */
    public function logout() {
        session_start();
        if (!empty($_SESSION['sessionID'])) {
            try {
                $this->loginSessionModel->updateLogoutTime($_SESSION['sessionID']);
            } catch (Exception $e) {
                error_log("Error updating logout time: " . $e->getMessage());
            }
        }
        
        // Clear all session data
        $_SESSION = [];
        
        // Destroy session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        
        session_destroy();
    }
}

