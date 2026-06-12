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

$token = $_GET['token'] ?? '';
$status = $_GET['status'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Password - Wafra</title>
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
      .alert a {
        color: #155724;
        text-decoration: underline;
      }
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
          <h1>Reset Password</h1>
          <p>Enter your new password below</p>
        </div>

        <?php if ($status === 'missing'): ?>
        <div class="alert alert-error">Reset token is missing.</div>
        <?php elseif ($status === 'invalid'): ?>
        <div class="alert alert-error">Invalid or expired reset link.</div>
        <?php elseif ($status === 'mismatch'): ?>
        <div class="alert alert-error">Passwords do not match.</div>
        <?php elseif ($status === 'weak'): ?>
        <div class="alert alert-warning">Password must be at least 8 characters.</div>
        <?php elseif ($status === 'error'): ?>
        <div class="alert alert-error">Unable to reset password. Try again.</div>
        <?php elseif ($status === 'success'): ?>
        <div class="alert alert-success">Password updated! <a href="<?= $baseUrl ?>/view/frontoffice/login.php">Sign in</a></div>
        <?php endif; ?>

        <?php if ($status !== 'success'): ?>
        <form class="login-form" action="<?= $baseUrl ?>/controllers/reset_password.php" method="post">
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES); ?>" />
          <div class="smart-field" data-field="password">
            <div class="field-background"></div>
            <input type="password" id="password" name="password" required placeholder=" " autocomplete="new-password" />
            <label for="password">New Password</label>
          </div>
          <div class="smart-field" data-field="confirm">
            <div class="field-background"></div>
            <input type="password" id="confirm_password" name="confirm_password" required placeholder=" " autocomplete="new-password" />
            <label for="confirm_password">Confirm Password</label>
          </div>
          <button type="submit" class="neural-button">
            <div class="button-bg"></div>
            <span class="button-text">Update Password</span>
            <div class="button-glow"></div>
          </button>
        </form>
        <?php endif; ?>

        <div class="signup-section">
          <span>Need help?</span>
          <a href="<?= $baseUrl ?>/view/frontoffice/forgot_password.php" class="neural-signup">Request new link</a>
        </div>
      </div>
    </div>
  </body>
</html>


















