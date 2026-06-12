<?php
/**
 * Authentication Controller
 * Handles login, signup, logout, profile, and email verification
 */

// Load config FIRST - this defines DB_HOST, DB_NAME, DB_USER, DB_PASS
require_once __DIR__ . '/../config/config.php';

// Now load Database class (which uses the constants defined above)
require_once __DIR__ . '/../config/Database.php';

// Load other dependencies
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $pdo;
    private $authService;
    private $userModel;

    public function __construct() {
        $this->pdo = Database::connect();
        $this->authService = new AuthService($this->pdo);
        $this->userModel = new User($this->pdo);
    }

    /**
     * Handle login
     */
    public function login() {
        // Clean any output buffers to ensure clean JSON response
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Start fresh output buffer
        ob_start();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
            exit;
        }

        session_start();

        // Verify reCAPTCHA
        $recaptchaToken = $_POST['recaptcha_token'] ?? '';
        $recaptchaSecretKey = getenv('RECAPTCHA_SECRET_KEY');
        
        if (empty($recaptchaSecretKey)) {
            $this->sendJsonResponse(false, 'Security verification is not properly configured.', 'config_error');
        }

        $recaptchaResult = $this->verifyRecaptcha($recaptchaToken, $recaptchaSecretKey);
        
        if (!isset($recaptchaResult['success']) || $recaptchaResult['success'] !== true) {
            $this->sendJsonResponse(false, 'Security verification failed. Please try again.', 'recaptcha_failed');
        }

        $score = $recaptchaResult['score'] ?? 0.0;
        if ($score < 0.5) {
            $this->sendJsonResponse(false, 'Security verification failed. Your activity appears suspicious.', 'recaptcha_low_score');
        }

        $action = $recaptchaResult['action'] ?? '';
        if ($action !== 'login') {
            $this->sendJsonResponse(false, 'Security verification failed. Invalid action.', 'recaptcha_action_mismatch');
        }

        // Get credentials
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->sendJsonResponse(false, 'Email and password are required.', 'missing_credentials');
        }

        $email = trim($email);

        try {
            $user = $this->authService->authenticate($email, $password);

            if (!$user) {
                $this->sendJsonResponse(false, 'Incorrect email or password.', 'invalid_credentials');
            }

            if (isset($user['error']) && $user['error'] === 'email_unverified') {
                $this->sendJsonResponse(false, 'Please verify your email address. We just sent you a fresh verification link.', 'email_unverified');
            }

            // Store user information in session
            $_SESSION['user'] = $user;
            $_SESSION['userID'] = (int)$user['cin'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname'] = $user['lastname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = trim($user['role'] ?? 'user');
            $_SESSION['username'] = trim($user['firstname'] . ' ' . $user['lastname']) ?: ($user['firstname'] ?? 'User');

            // Create login session
            $sessionID = $this->authService->createLoginSession($_SESSION['userID'], $_SESSION['username']);

            if (!$sessionID) {
                $this->sendJsonResponse(false, 'Something went wrong on our side. Please try again in a moment.', 'session_failed');
            }

            $_SESSION['sessionID'] = $sessionID;

            // Determine redirect URL based on role
            $role = $_SESSION['role'];
            $redirectUrl = BASE_URL . '/view/frontoffice/login.php?error=role';
            
            if ($role === 'admin') {
                $redirectUrl = BASE_URL . '/index.php?action=dashboard';
            } elseif ($role === 'user') {
                // Redirect users to frontoffice/index.php (not homepage.php)
                $redirectUrl = BASE_URL . '/view/frontoffice/index.php';
            } else {
                $this->sendJsonResponse(false, 'Your account role is not authorized to sign in here.', 'unauthorized_role');
            }

            $this->sendJsonResponse(true, 'Login successful', null, $redirectUrl);

        } catch (PDOException $e) {
            error_log("PDOException in login: " . $e->getMessage());
            $this->sendJsonResponse(false, 'Something went wrong on our side. Please try again in a moment.', 'server_error');
        } catch (Exception $e) {
            error_log("Exception in login: " . $e->getMessage());
            $this->sendJsonResponse(false, 'Something went wrong on our side. Please try again in a moment.', 'server_error');
        }
    }

    /**
     * Handle signup
     */
    public function signup() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/view/frontoffice/signup.php');
            exit;
        }

        // Check if passwords match
        if ($_POST['password'] !== $_POST['confirmPassword']) {
            header("Location: " . BASE_URL . "/view/frontoffice/signup.php?error=password_mismatch");
            exit;
        }

        try {
            $userData = [
                'cin' => $_POST['cin'],
                'firstname' => $_POST['firstname'],
                'lastname' => $_POST['lastname'],
                'email' => $_POST['email'],
                'password' => $_POST['password']
            ];

            $result = $this->authService->register($userData);

            if ($result === true) {
                header("Location: " . BASE_URL . "/view/frontoffice/signup.php?verification_sent=1");
            } elseif (is_array($result) && isset($result['error'])) {
                header("Location: " . BASE_URL . "/view/frontoffice/signup.php?error=" . $result['error']);
            } else {
                header("Location: " . BASE_URL . "/view/frontoffice/signup.php?error=1");
            }
            exit;
        } catch (PDOException $e) {
            error_log("PDOException in signup: " . $e->getMessage());
            header("Location: " . BASE_URL . "/view/frontoffice/signup.php?error=db_error&msg=" . urlencode($e->getMessage()));
            exit;
        } catch (Exception $e) {
            error_log("Exception in signup: " . $e->getMessage());
            header("Location: " . BASE_URL . "/view/frontoffice/signup.php?error=general_error&msg=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Handle logout
     */
    public function logout() {
        session_start();
        $this->authService->logout();
        header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
        exit;
    }

    /**
     * Handle profile update
     */
    public function updateProfile() {
        session_start();

        if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
            header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/view/frontoffice/profile.php');
            exit();
        }

        $userID = (int)$_SESSION['userID'];
        $error = null;

        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        $profilePictureFilename = null;
        $uploadDir = __DIR__ . '/../uploads/profile_pictures/';

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_picture'];
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png'];

            if (!in_array($fileExt, $allowedExts)) {
                $error = 'Invalid file type. Only JPG, JPEG, and PNG files are allowed.';
            } elseif ($file['size'] > 2097152) {
                $error = 'File size exceeds 2MB limit.';
            } elseif (!getimagesize($file['tmp_name'])) {
                $error = 'File is not a valid image.';
            } else {
                $newFileName = uniqid('profile_', true) . '_' . time() . '.' . $fileExt;
                $destination = $uploadDir . $newFileName;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $profilePictureFilename = $newFileName;

                    $currentUser = $this->userModel->getUserByCin($userID);
                    if ($currentUser && !empty($currentUser['profile_picture'])) {
                        $oldPicturePath = $uploadDir . $currentUser['profile_picture'];
                        if (file_exists($oldPicturePath)) {
                            unlink($oldPicturePath);
                        }
                    }
                } else {
                    $error = 'Failed to upload file. Please try again.';
                }
            }
        }

        if ($error) {
            header("Location: " . BASE_URL . "/view/frontoffice/profile.php?error=" . urlencode($error));
            exit();
        }

        if (empty($firstname) || empty($lastname) || empty($email)) {
            $error = 'All required fields must be filled.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format.';
        } elseif (!empty($newPassword) && strlen($newPassword) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif (!empty($newPassword) && $newPassword !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            $currentUser = $this->userModel->getUserByCin($userID);

            if (!$currentUser) {
                $error = 'User not found.';
            } else {
                $existingUser = $this->userModel->findUserByEmail($email);
                if ($existingUser && $existingUser['cin'] != $userID) {
                    $error = 'This email is already registered to another account.';
                } else {
                    $pictureUpdated = false;
                    if ($profilePictureFilename !== null) {
                        $pictureUpdated = $this->userModel->updateProfilePicture($userID, $profilePictureFilename);
                    }

                    if (!empty($newPassword)) {
                        $result = $this->userModel->updateUserProfileWithPassword($userID, $firstname, $lastname, $email, $newPassword);
                    } else {
                        $result = $this->userModel->updateUserProfile($userID, $firstname, $lastname, $email);
                    }

                    if ($result || $pictureUpdated) {
                        $_SESSION['firstname'] = $firstname;
                        $_SESSION['lastname'] = $lastname;
                        $_SESSION['email'] = $email;
                        $_SESSION['username'] = trim($firstname . ' ' . $lastname);

                        $successMsg = 'profile_updated';
                        if ($pictureUpdated && !$result) {
                            $successMsg = 'picture_updated';
                        }

                        if ($result && $currentUser['email'] !== $email) {
                            $this->userModel->sendEmailChangeVerification($userID, $email, trim($firstname . ' ' . $lastname));
                            $sql = "UPDATE users SET email_verified = 0 WHERE cin = ?";
                            $stmt = $this->pdo->prepare($sql);
                            $stmt->execute([$userID]);
                            $successMsg = 'profile_updated_email_verification';
                        }

                        header("Location: " . BASE_URL . "/view/frontoffice/profile.php?success=" . $successMsg);
                        exit();
                    } else {
                        if ($pictureUpdated) {
                            header("Location: " . BASE_URL . "/view/frontoffice/profile.php?success=picture_updated");
                            exit();
                        } else {
                            $error = 'Failed to update profile. Please try again.';
                        }
                    }
                }
            }
        }

        if ($error) {
            header("Location: " . BASE_URL . "/view/frontoffice/profile.php?error=" . urlencode($error));
            exit();
        }
    }

    /**
     * Handle email verification
     */
    public function verifyEmail() {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            header('Location: ' . BASE_URL . '/view/frontoffice/login.php?error=invalid_token');
            exit;
        }

        try {
            $result = $this->userModel->verifyEmail($token);

            if ($result) {
                header('Location: ' . BASE_URL . '/view/frontoffice/login.php?verified=1');
            } else {
                header('Location: ' . BASE_URL . '/view/frontoffice/login.php?error=invalid_token');
            }
            exit;
        } catch (Exception $e) {
            error_log("Exception in verifyEmail: " . $e->getMessage());
            header('Location: ' . BASE_URL . '/view/frontoffice/login.php?error=verification_failed');
            exit;
        }
    }

    /**
     * Verify reCAPTCHA token
     */
    private function verifyRecaptcha($token, $secretKey) {
        if (empty($token)) {
            return ['success' => false, 'error' => 'Missing reCAPTCHA token'];
        }

        $postData = [
            'secret' => $secretKey,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($curlError)) {
            error_log("reCAPTCHA verification cURL error: " . $curlError);
            return ['success' => false, 'error' => 'Failed to verify reCAPTCHA: Network error'];
        }

        if ($httpCode !== 200) {
            error_log("reCAPTCHA verification HTTP error: " . $httpCode);
            return ['success' => false, 'error' => 'Failed to verify reCAPTCHA: HTTP error'];
        }

        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("reCAPTCHA verification JSON decode error: " . json_last_error_msg());
            return ['success' => false, 'error' => 'Failed to verify reCAPTCHA: Invalid response'];
        }

        return $result;
    }

    /**
     * Send JSON response
     */
    private function sendJsonResponse($success, $message, $errorCode = null, $redirectUrl = null) {
        // Clean any output buffers to ensure clean JSON response
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set JSON header
        header('Content-Type: application/json; charset=utf-8');
        
        // Build response
        $response = [
            'success' => $success,
            'message' => $message
        ];
        if ($errorCode !== null) {
            $response['error'] = $errorCode;
        }
        if ($redirectUrl !== null) {
            $response['redirect'] = $redirectUrl;
        }
        
        // Output JSON and exit
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

