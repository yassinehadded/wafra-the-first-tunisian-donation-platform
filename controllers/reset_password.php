<?php
/**
 * Reset Password Controller
 * Handles password reset with token
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Build redirect URL properly
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$redirectBase = $protocol . '://' . $host . '/wafra/wafra-integration';

// Get reset token from URL or form
$token = $_POST['token'] ?? $_GET['token'] ?? '';

// Check if token was provided
if (empty($token)) {
    $redirectUrl = $redirectBase . '/view/frontoffice/reset_password.php?status=missing';
    $redirectUrl = str_replace('/view/frontoffice/view/frontoffice', '/view/frontoffice', $redirectUrl);
    header('Location: ' . $redirectUrl);
    exit();
}

// Find user by reset token
$pdo = Database::connect();
$userModel = new User($pdo);
$user = $userModel->findUserByResetToken($token);

// Check if token is valid
if (!$user) {
    $redirectUrl = $redirectBase . '/view/frontoffice/reset_password.php?status=invalid';
    $redirectUrl = str_replace('/view/frontoffice/view/frontoffice', '/view/frontoffice', $redirectUrl);
    header('Location: ' . $redirectUrl);
    exit();
}

// If GET request, show reset form
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $redirectUrl = $redirectBase . '/view/frontoffice/reset_password.php?token=' . urlencode($token);
    $redirectUrl = str_replace('/view/frontoffice/view/frontoffice', '/view/frontoffice', $redirectUrl);
    header('Location: ' . $redirectUrl);
    exit();
}

// Get new password from form
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

// Check if passwords match
if ($password !== $confirm) {
    $redirectUrl = $redirectBase . '/view/frontoffice/reset_password.php?token=' . urlencode($token) . '&status=mismatch';
    $redirectUrl = str_replace('/view/frontoffice/view/frontoffice', '/view/frontoffice', $redirectUrl);
    header('Location: ' . $redirectUrl);
    exit();
}

// Check if password is long enough
if (strlen($password) < 8) {
    $redirectUrl = $redirectBase . '/view/frontoffice/reset_password.php?token=' . urlencode($token) . '&status=weak';
    $redirectUrl = str_replace('/view/frontoffice/view/frontoffice', '/view/frontoffice', $redirectUrl);
    header('Location: ' . $redirectUrl);
    exit();
}

// Update password and clear reset token
if ($userModel->updatePassword($user['email'], $password)) {
    // Remove reset token after successful password change
    $userModel->clearPasswordResetToken($user['email']);
    $redirectUrl = $redirectBase . '/view/frontoffice/reset_password.php?status=success';
    $redirectUrl = str_replace('/view/frontoffice/view/frontoffice', '/view/frontoffice', $redirectUrl);
    header('Location: ' . $redirectUrl);
    exit();
}

// Redirect with error if update failed
$redirectUrl = $redirectBase . '/view/frontoffice/reset_password.php?token=' . urlencode($token) . '&status=error';
$redirectUrl = str_replace('/view/frontoffice/view/frontoffice', '/view/frontoffice', $redirectUrl);
header('Location: ' . $redirectUrl);
exit();
?>

