<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config and ensure BASE_URL is defined
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}

// Force BASE_URL to correct value - use hardcoded path
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path = '/wafra/wafra-integration';

// Always set baseUrl variable to correct value (override config.php if needed)
$baseUrl = $protocol . '://' . $host . $path;

$status = $_GET['status'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot Password - Wafra</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/login.css" />
    <style>
      .alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 16px;
        font-weight: 600;
      }
      .alert-success { background: #d4edda; color: #155724; }
      .alert-error { background: #f8d7da; color: #721c24; }
      .alert-warning { background: #fff3cd; color: #856404; }
    </style>
  </head>
  <body>
    <div class="neural-background">
        <div class="neural-node"></div>
        <div class="neural-node"></div>
        <div class="neural-node"></div>
        <div class="neural-node"></div>
        <div class="neural-node"></div>
    </div>

    <div class="login-container">
      <div class="login-card">
        <div class="glow"></div>
        <div class="login-header">
          <div class="logo">
            <div class="logo-core">
              <img src="<?= $baseUrl ?>/view/frontoffice/images/logo.jpg" alt="Wafra Logo" class="logo-inside-circle" />
            </div>
          </div>
          <h1>Forgot Password</h1>
          <p>We'll send a reset link to your email</p>
        </div>

        <?php if ($status === 'sent'): ?>
        <div class="alert alert-success">If the email exists, a reset link has been sent.</div>
        <?php elseif ($status === 'mail_error'): ?>
        <div class="alert alert-warning">Unable to send the email. Please try again later.</div>
        <?php elseif ($status === 'invalid'): ?>
        <div class="alert alert-error">Please provide a valid email address.</div>
        <?php endif; ?>

        <form class="login-form" action="<?= $baseUrl ?>/controllers/forgot_password.php" method="post">
          <div class="smart-field" data-field="email">
            <div class="field-background"></div>
            <input type="email" id="email" name="email" required placeholder=" " autocomplete="email" />
            <label for="email">Email Address</label>
          </div>
          <button type="submit" class="neural-button">
            <div class="button-bg"></div>
            <span class="button-text">Send Reset Link</span>
            <div class="button-glow"></div>
          </button>
        </form>

        <div class="signup-section">
          <span>Remembered your password?</span>
          <a href="<?= $baseUrl ?>/view/frontoffice/login.php" class="neural-signup">Back to login</a>
        </div>
      </div>
    </div>
  </body>
</html>


















