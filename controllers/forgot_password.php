<?php
/**
 * Forgot Password Controller
 * Handles password reset requests
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/EmailService.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Build redirect URL properly
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$redirectBase = $protocol . '://' . $host . '/wafra/wafra-integration';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $redirectUrl = $redirectBase . '/view/frontoffice/forgot_password.php';
    $redirectUrl = str_replace('/view/frontoffice/view/frontoffice', '/view/frontoffice', $redirectUrl);
    header('Location: ' . $redirectUrl);
    exit();
}

// Get email from form
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

// Check if email was provided
if (!$email) {
    $redirectUrl = $redirectBase . '/view/frontoffice/forgot_password.php?status=invalid';
    $redirectUrl = str_replace('/view/frontoffice/view/frontoffice', '/view/frontoffice', $redirectUrl);
    header('Location: ' . $redirectUrl);
    exit();
}

// Create password reset token
$pdo = Database::connect();
$userModel = new User($pdo);
$tokenData = $userModel->createPasswordResetToken($email);

// Always show success message even if email not found to prevent email enumeration
$status = 'sent';

// If token was created, send reset email
if ($tokenData) {
    // Get user name for email
    $userName = trim(($tokenData['user']['firstname'] ?? '') . ' ' . ($tokenData['user']['lastname'] ?? ''));
    $emailService = new EmailService();
    // Send password reset email
    $mailSent = $emailService->sendPasswordResetEmail($email, $userName ?: 'Wafra User', $tokenData['token']);
    // Check if email sending failed
    if (!$mailSent) {
        $status = 'mail_error';
    }
}

// Redirect back to forgot password page with status
$redirectUrl = $redirectBase . '/view/frontoffice/forgot_password.php?status=' . $status;
$redirectUrl = str_replace('/view/frontoffice/view/frontoffice', '/view/frontoffice', $redirectUrl);
header('Location: ' . $redirectUrl);
exit();
?>

