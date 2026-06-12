<?php
session_start();

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

$loginMessage = '';
$messageClass = '';

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid':
            $loginMessage = 'Incorrect email or password.';
            $messageClass = 'alert-danger';
            break;
        case 'unverified':
            $loginMessage = 'Please verify your email address. We just sent you a fresh verification link.';
            $messageClass = 'alert-warning';
            break;
        case 'role':
            $loginMessage = 'Your account role is not authorized to sign in here.';
            $messageClass = 'alert-danger';
            break;
        case 'oauth_config':
            $loginMessage = 'OAuth is not properly configured. Please contact the administrator.';
            $messageClass = 'alert-warning';
            break;
        case 'github_not_configured':
            $loginMessage = 'GitHub OAuth is not configured. Please contact the administrator.';
            $messageClass = 'alert-warning';
            break;
        case 'oauth_error':
            $loginMessage = 'An error occurred during OAuth authentication. Please try again.';
            $messageClass = 'alert-danger';
            break;
    }
} elseif (isset($_GET['verified']) && $_GET['verified'] === '1') {
    $loginMessage = 'Email verified successfully! You can now log in.';
    $messageClass = 'alert-success';
}

$recaptchaSiteKey = getenv('RECAPTCHA_SITE_KEY');
if ($recaptchaSiteKey === false && isset($_ENV['RECAPTCHA_SITE_KEY'])) {
    $recaptchaSiteKey = $_ENV['RECAPTCHA_SITE_KEY'];
}
if (empty($recaptchaSiteKey)) {
    $recaptchaSiteKey = 'SITE_KEY_HERE';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - WAFRA</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/login.css" />
    <!-- Debug: BASE_URL = <?= BASE_URL ?> -->
    <!-- Debug: baseUrl = <?= $baseUrl ?> -->
    <!-- Debug: CSS Path = <?= $baseUrl ?>/view/frontoffice/assets/css/login.css -->
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars($recaptchaSiteKey, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script>
        window.RECAPTCHA_SITE_KEY = '<?php echo htmlspecialchars($recaptchaSiteKey, ENT_QUOTES, 'UTF-8'); ?>';
    </script>
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
                <h1>Wafra</h1>
                <p>Connect to your donation space</p>
            </div>

            <?php if ($loginMessage): ?>
            <div class="alert <?php echo $messageClass; ?>" style="padding: 10px; border-radius: 6px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($loginMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <?php endif; ?>

            <form class="login-form" id="loginForm" action="<?= $baseUrl ?>/index.php?action=login" method="post" novalidate>
                <div class="smart-field" data-field="email">
                    <div class="field-background"></div>
                    <input type="email" id="email" name="email" required autocomplete="email" placeholder=" " />
                    <label for="email">Email Address</label>
                    <span class="error-message" id="emailError"></span>
                </div>

                <div class="smart-field" data-field="password">
                    <div class="field-background"></div>
                    <input type="password" id="password" name="password" required autocomplete="current-password" placeholder=" " />
                    <label for="password">Password</label>
                    <span class="error-message" id="passwordError"></span>
                </div>

                <div style="text-align: right; margin-bottom: 20px;">
                    <a href="<?= $baseUrl ?>/view/frontoffice/forgot_password.php" class="neural-signup" style="font-size: 14px;">Forgot password?</a>
                </div>

                <input type="hidden" id="recaptcha_token" name="recaptcha_token" value="" />

                <button type="submit" class="neural-button" id="loginSubmitBtn">
                    <div class="button-bg"></div>
                    <span class="button-text">Initialize Connection</span>
                    <div class="button-loader">
                        <div class="neural-spinner">
                            <div class="spinner-segment"></div>
                            <div class="spinner-segment"></div>
                            <div class="spinner-segment"></div>
                        </div>
                    </div>
                </button>
            </form>

            <div class="separator">
                <div class="separator-line"></div>
                <span class="separator-text">or connect via</span>
                <div class="separator-line"></div>
            </div>

            <!-- Neural Social Section -->
            <div class="neural-social">
                <button
                    type="button"
                    class="social-neural google-btn"
                    id="googleSignIn"
                >
                    <div class="social-bg"></div>
                    <svg width="18" height="18" viewBox="0 0 18 18">
                        <path
                            fill="#4285F4"
                            d="M16.51 7.39c0-.56-.05-1.12-.14-1.67H9.18v3.16h4.13c-.18 1.02-.77 1.88-1.64 2.45v2.06h2.66c1.56-1.44 2.46-3.56 2.46-6.07z"
                        />
                        <path
                            fill="#34A853"
                            d="M9.18 17c2.23 0 4.1-.74 5.47-2.01l-2.66-2.06c-.74.5-1.69.79-2.81.79-2.16 0-3.99-1.46-4.64-3.42H1.82v2.13C3.17 15.16 5.96 17 9.18 17z"
                        />
                        <path
                            fill="#FBBC05"
                            d="M4.54 10.53a4.84 4.84 0 010-3.06V5.34H1.82a8.01 8.01 0 000 7.18l2.72-2.13z"
                        />
                        <path
                            fill="#EA4335"
                            d="M9.18 3.68c1.22 0 2.31.42 3.17 1.24l2.38-2.38C13.27 1.14 11.41.45 9.18.45 5.96.45 3.17 2.29 1.82 5.34l2.72 2.13c.65-1.96 2.48-3.42 4.64-3.42z"
                        />
                    </svg>
                    <span>Continue with Google</span>
                    <div class="social-glow"></div>
                </button>

                <button
                    type="button"
                    class="social-neural github-btn"
                    id="githubSignIn"
                >
                    <div class="social-bg"></div>
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor">
                        <path
                            d="M9 1a8 8 0 00-2.53 15.59c.4.07.55-.17.55-.38v-1.49c-2.22.48-2.69-1.07-2.69-1.07-.36-.92-.88-1.17-.88-1.17-.72-.49.05-.48.05-.48.8.06 1.22.82 1.22.82.71 1.22 1.86.87 2.31.66.07-.52.28-.87.5-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.86 3.75-3.64 3.95.29.25.54.73.54 1.48v2.2c0 .21.15.46.56.38A8.01 8.01 0 009 1z"
                        />
                    </svg>
                    <span>Continue with GitHub</span>
                    <div class="social-glow"></div>
                </button>
            </div>

            <div class="signup-section">
                <span>Don't have an account?</span>
                <a href="<?= $baseUrl ?>/view/frontoffice/signup.php" class="neural-signup">Join the network</a>
            </div>
        </div>
    </div>

    <script>
      // Make BASE_URL available to JavaScript
      const BASE_URL = '<?= $baseUrl ?>';
    </script>
    <script src="<?= $baseUrl ?>/view/frontoffice/assets/js/login.js"></script>
</body>
</html>

