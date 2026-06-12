<?php
session_start();

// Load config and ensure BASE_URL is defined
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}

// Force baseUrl variable to correct value
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path = '/wafra/wafra-integration';
$baseUrl = $protocol . '://' . $host . $path;
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign Up - WAFRA</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/login.css" />
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
            <div class="logo-rings">
              <div class="ring ring-1"></div>
              <div class="ring ring-2"></div>
              <div class="ring ring-3"></div>
            </div>
          </div>
          <h1>Wafra</h1>
          <p>Connect to your donation space</p>
        </div>

        <?php if (isset($_GET['verification_sent']) && $_GET['verification_sent'] == 1): ?>
        <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb; font-weight: bold;">
            ✓ Account created successfully! Please check your email and click the verification link to activate your account.
        </div>
        <?php elseif (isset($_GET['mail_error'])): ?>
        <div class="alert alert-warning" style="background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ffeaa7;">
            ⚠ Your account was created, but we could not send the verification email. Please contact support or try resending from the login page.
        </div>
        <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <?php
            $errorMsg = 'An error occurred. Please try again.';
            if ($_GET['error'] == 'password_mismatch') {
                $errorMsg = 'Passwords do not match!';
            } elseif ($_GET['error'] == 'email_exists') {
                $errorMsg = 'This email is already registered!';
            } elseif ($_GET['error'] == 'cin_exists') {
                $errorMsg = 'This CIN is already registered!';
            } elseif (isset($_GET['msg'])) {
                $errorMsg = htmlspecialchars($_GET['msg']);
            }
            echo '✗ ' . $errorMsg;
            ?>
        </div>
        <?php endif; ?>

        <form class="login-form" id="loginForm" action="<?= $baseUrl ?>/index.php?action=signup" method="post" novalidate>
          <div class="smart-field" data-field="firstname">
            <div class="field-background"></div>
            <input type="text" id="firstname" name="firstname" required autocomplete="given-name" placeholder=" " />
            <label for="firstname">First Name</label>
            <span class="error-message" id="firstnameError"></span>
          </div>
          <div class="smart-field" data-field="lastname">
            <div class="field-background"></div>
            <input type="text" id="lastname" name="lastname" required autocomplete="family-name" placeholder=" " />
            <label for="lastname">Last Name</label>
            <span class="error-message" id="lastnameError"></span>
          </div>
          <div class="smart-field" data-field="cin">
            <div class="field-background"></div>
            <input type="text" id="cin" name="cin" required autocomplete="off" placeholder=" " />
            <label for="cin">CIN</label>
            <span class="error-message" id="cinError"></span>
          </div>
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
          <div class="smart-field" data-field="confirmPassword">
            <div class="field-background"></div>
            <input type="password" id="confirmPassword" name="confirmPassword" required autocomplete="current-password" placeholder=" " />
            <label for="confirmPassword">Confirm Password</label>
            <span class="error-message" id="confirmPasswordError"></span>
          </div>
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
            <span>Already have an account?</span>
            <a href="<?= $baseUrl ?>/view/frontoffice/login.php" class="neural-signup">Sign in</a>
          </div>
          <button type="submit" class="neural-button" name="submit">
            <div class="button-bg"></div>
            <span class="button-text">Create account</span>
            <div class="button-loader">
              <div class="neural-spinner">
                <div class="spinner-segment"></div>
                <div class="spinner-segment"></div>
                <div class="spinner-segment"></div>
              </div>
            </div>
            <div class="button-glow"></div>
          </button>
        </form>
      </div>
    </div>
    <script>
      // Make BASE_URL available to JavaScript
      const BASE_URL = '<?= $baseUrl ?>';
    </script>
    <script src="<?= $baseUrl ?>/view/frontoffice/assets/js/signup.js"></script>
  </body>
</html>

