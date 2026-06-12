<?php
session_start();

// Accept either sessionID or SessionID for compatibility
$hasSessionId = !empty($_SESSION['sessionID']) || !empty($_SESSION['SessionID']);

require_once __DIR__ . '/../../config/config.php';

// Force BASE_URL to correct value
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $protocol . '://' . $host . '/wafra/wafra-integration';
if (!defined('BASE_URL')) {
    define('BASE_URL', $baseUrl);
}

// Check if user is logged in (admin or user)
if (!$hasSessionId || empty($_SESSION['userID']) || empty($_SESSION['role'])) {
    header('Location: ' . $baseUrl . '/view/frontoffice/login.php');
    exit();
}

// Check if viewing another user's profile (admin can view any user)
$viewingUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$isAdmin = ($_SESSION['role'] === 'admin');
$isViewingOtherUser = $viewingUserId && $viewingUserId != $_SESSION['userID'];

// If viewing another user, must be admin
if ($isViewingOtherUser && !$isAdmin) {
    header('Location: ' . $baseUrl . '/view/frontoffice/login.php');
    exit();
}

// Determine which user's profile to show
$targetUserId = $isViewingOtherUser ? $viewingUserId : (int)$_SESSION['userID'];

// Get target user data
require_once __DIR__ . '/../../config/autoload.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/User.php';

$pdo = Database::connect();
$userModel = new User($pdo);
$targetUserData = $userModel->getUserByCin($targetUserId);

if (!$targetUserData) {
    header('Location: ' . $baseUrl . '/index.php?action=dashboard&section=users');
    exit();
}

// Use target user's data for display
$firstname = htmlspecialchars($targetUserData['firstname'] ?? '', ENT_QUOTES, 'UTF-8');
$lastname = htmlspecialchars($targetUserData['lastname'] ?? '', ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($targetUserData['email'] ?? '', ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($targetUserData['role'] ?? '', ENT_QUOTES, 'UTF-8');
$currentRole = htmlspecialchars($_SESSION['role'] ?? '', ENT_QUOTES, 'UTF-8');

// Get profile picture from session or database (with error handling)
// Path is relative to view/ directory
$profilePicturePath = BASE_URL . '/view/frontoffice/assets/images/default-avatar.png'; // Default fallback

try {
    require_once __DIR__ . '/../../config/autoload.php';
    require_once __DIR__ . '/../../models/User.php';
        
    $pdo = Database::connect();
    if ($pdo instanceof PDO) {
        try {
            $userModel = new User($pdo);
            $userData = $userModel->getUserByCin($targetUserId);
            
            if ($userData && is_array($userData)) {
                // Check if profile_picture column exists and has a value
                // Use array_key_exists to check if column exists (even if NULL)
                if (array_key_exists('profile_picture', $userData)) {
                    $profilePicture = $userData['profile_picture'];
                    
                    if ($profilePicture && !empty(trim($profilePicture))) {
                        $pictureFile = __DIR__ . '/../../uploads/profile_pictures/' . basename($profilePicture);
                        if (file_exists($pictureFile) && is_file($pictureFile)) {
                            // Path using BASE_URL
                            $profilePicturePath = BASE_URL . '/uploads/profile_pictures/' . basename($profilePicture);
                            error_log("Profile picture found: " . $profilePicturePath . " (file: " . $pictureFile . ", size: " . filesize($pictureFile) . " bytes)");
                        } else {
                            error_log("Profile picture file not found: " . $pictureFile . " (profile_picture value: " . $profilePicture . ")");
                            // Try to list files in directory for debugging
                            $uploadDir = __DIR__ . '/../../uploads/profile_pictures/';
                            if (is_dir($uploadDir)) {
                                $files = scandir($uploadDir);
                                error_log("Files in uploads directory: " . print_r($files, true));
                            }
                        }
                        } else {
                            error_log("Profile picture is empty or null for user ID: " . $targetUserId);
                        }
                } else {
                    // Column doesn't exist - user needs to run migration
                    error_log("WARNING: profile_picture column does not exist in users table. Please run: ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL;");
                }
            }
        } catch (PDOException $pdoe) {
            // Database error - column might not exist yet
            error_log("Profile picture PDO error: " . $pdoe->getMessage());
            // Continue with default picture
        } catch (Exception $e) {
            // Other errors
            error_log("Profile picture error: " . $e->getMessage());
            // Continue with default picture
        }
    }
} catch (Exception $e) {
    // Silently fail and use default picture
    error_log("Profile picture setup error: " . $e->getMessage());
    // Continue with default picture
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1, shrink-to-fit=no"
    />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <link
      href="https://fonts.googleapis.com/css?family=Montserrat:100,200,300,400,500,600,700,800,900"
      rel="stylesheet"
    />

    <title>User Profile - WAFRA</title>

    <link
      href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/view/frontoffice/assets/css/fontawesome.css" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/view/frontoffice/assets/css/templatemo-grad-school.css" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/view/frontoffice/assets/css/flex-slider.css" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/view/frontoffice/assets/css/owl.css" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/view/frontoffice/assets/css/lightbox.css" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/view/components/chatbot.css" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/view/frontoffice/assets/css/topbar.css" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/view/frontoffice/assets/css/topbar-enhanced.css" />
    
    
    <style>
      /* Modern Profile Page Styling - Blue/Gray/White Palette */
      :root {
        --primary-blue: #2563eb;
        --secondary-blue: #3b82f6;
        --light-blue: #dbeafe;
        --dark-gray: #1f2937;
        --medium-gray: #4b5563;
        --light-gray: #f3f4f6;
        --border-gray: #e5e7eb;
        --white: #ffffff;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
      }

      /* Section Styling */
      .section {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        min-height: calc(100vh - 200px);
        padding-bottom: 60px;
        padding-top: 100px !important;
      }

      .section-heading {
        margin-bottom: 3rem;
        animation: fadeInDown 0.6s ease-out;
      }

      .section-heading h2 {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--dark-gray);
        margin-bottom: 0.75rem;
        letter-spacing: -0.5px;
      }

      .section-heading p {
        font-size: 1.125rem;
        color: var(--medium-gray);
        font-weight: 400;
      }

      /* Card Enhancement */
      .card {
        border: none;
        border-radius: 16px;
        box-shadow: var(--shadow-xl);
        background: var(--white);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        animation: fadeInUp 0.6s ease-out 0.2s both;
      }

      .card:hover {
        transform: translateY(-4px);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
      }

      .card-body {
        padding: 2.5rem;
      }

      /* Profile Info Styling */
      .card-body .row {
        margin-bottom: 1.5rem;
      }

      .card-body .row:last-of-type {
        margin-bottom: 0;
      }

      .profile-info-wrapper {
        height: 100%;
      }

      .card-body p {
        margin-bottom: 0;
        padding: 1.25rem;
        background: var(--light-gray);
        border-radius: 12px;
        border-left: 4px solid var(--primary-blue);
        transition: all 0.3s ease;
        font-size: 1rem;
        line-height: 1.6;
        height: 100%;
      }

      .card-body p:hover {
        background: var(--light-blue);
        transform: translateX(4px);
        box-shadow: var(--shadow-md);
      }

      .card-body p strong {
        color: var(--dark-gray);
        font-weight: 600;
        display: inline-block;
        min-width: 120px;
        margin-right: 0.75rem;
        font-size: 0.95rem;
        letter-spacing: 0.3px;
      }

      .card-body p:not(:has(strong)) {
        color: var(--medium-gray);
        font-weight: 400;
      }

      /* Button Styling */
      .btn-primary {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
        border: none;
        border-radius: 10px;
        padding: 0.875rem 2.5rem;
        font-size: 1rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        box-shadow: var(--shadow-md);
        transition: all 0.3s ease;
        text-transform: uppercase;
        font-size: 0.875rem;
      }

      .btn-primary:hover {
        background: linear-gradient(135deg, var(--secondary-blue) 0%, var(--primary-blue) 100%);
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
      }

      .btn-primary:active {
        transform: translateY(0);
        box-shadow: var(--shadow-sm);
      }

      .btn-primary:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.3);
      }

      /* Edit Profile Button */
      .btn-secondary {
        background: linear-gradient(135deg, var(--medium-gray) 0%, #6b7280 100%);
        border: none;
        border-radius: 10px;
        padding: 0.875rem 2.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        box-shadow: var(--shadow-md);
        transition: all 0.3s ease;
        text-transform: uppercase;
        color: white;
      }

      .btn-secondary:hover {
        background: linear-gradient(135deg, #6b7280 0%, var(--medium-gray) 100%);
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
        color: white;
      }

      .btn-secondary:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(75, 85, 99, 0.3);
        color: white;
      }

      /* Modal Styling */
      .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1050;
        overflow: hidden;
        outline: 0;
      }

      .modal.show {
        display: block;
      }

      .modal-dialog {
        position: relative;
        width: auto;
        margin: 1.75rem auto;
        max-width: 500px;
        pointer-events: none;
      }

      .modal-content {
        position: relative;
        display: flex;
        flex-direction: column;
        width: 100%;
        pointer-events: auto;
        background-color: var(--white);
        background-clip: padding-box;
        border: 1px solid rgba(0, 0, 0, 0.2);
        border-radius: 16px;
        outline: 0;
        animation: fadeInUp 0.3s ease-out;
      }

      .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1040;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0, 0, 0, 0.5);
      }

      .modal-backdrop.fade {
        opacity: 0;
        transition: opacity 0.15s linear;
      }

      .modal-backdrop.show {
        opacity: 1;
      }

      .modal-header {
        padding: 1.5rem 2rem;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        border-bottom: 1px solid var(--border-gray);
      }

      .modal-body {
        background: var(--white);
        position: relative;
        flex: 1 1 auto;
        padding: 1rem;
      }

      .modal-footer {
        background: var(--light-gray);
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding: 1rem;
        border-top: 1px solid var(--border-gray);
      }

      .form-control:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
      }

      /* Profile Picture Styling */
      .profile-picture-container {
        position: relative;
      }

      .form-control-file {
        display: block;
        width: 100%;
        padding: 0.5rem;
        font-size: 0.9rem;
        border: 2px dashed var(--border-gray);
        border-radius: 8px;
        background: var(--light-gray);
        transition: all 0.3s ease;
        cursor: pointer;
      }

      .form-control-file:hover {
        border-color: var(--primary-blue);
        background: var(--light-blue);
      }

      .form-control-file:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
      }

      .alert {
        animation: fadeInDown 0.4s ease-out;
      }

      .alert-success {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        border: 1px solid #10b981;
        color: #065f46;
      }

      .alert-danger {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        border: 1px solid #ef4444;
        color: #991b1b;
      }

      body.modal-open {
        overflow: hidden;
      }

      /* Header Enhancement */
      .main-header {
        background: linear-gradient(135deg, var(--dark-gray) 0%, #374151 100%);
        box-shadow: var(--shadow-lg);
      }

      .main-header .logo a {
        color: var(--white);
        font-weight: 700;
        transition: color 0.3s ease;
      }

      .main-header .logo a:hover {
        color: var(--light-blue);
      }

      .main-menu a,
      .main-menu button {
        transition: all 0.3s ease;
      }

      .main-menu a:hover,
      .main-menu button:hover {
        color: var(--light-blue) !important;
        transform: translateY(-2px);
      }

      /* Footer Styling */
      footer {
        background: var(--dark-gray);
        color: var(--light-gray);
        padding: 2rem 0;
        margin-top: 4rem;
      }

      footer p {
        margin: 0;
        font-size: 0.9rem;
        color: var(--border-gray);
      }

      footer a {
        color: var(--secondary-blue);
        text-decoration: none;
        transition: color 0.3s ease;
      }

      footer a:hover {
        color: var(--light-blue);
      }

      /* Responsive Design */
      @media (max-width: 768px) {
        .section-heading h2 {
          font-size: 2rem;
        }

        .section-heading p {
          font-size: 1rem;
        }

        .card-body {
          padding: 1.5rem;
        }

        .card-body p {
          padding: 1rem;
          margin-bottom: 1rem;
        }

        .card-body p strong {
          display: block;
          margin-bottom: 0.5rem;
          min-width: auto;
        }

        .btn-primary {
          padding: 0.75rem 2rem;
          width: 100%;
          max-width: 300px;
        }
      }

      @media (max-width: 576px) {
        .section {
          padding-top: 100px;
        }

        .section-heading {
          margin-bottom: 2rem;
        }

        .section-heading h2 {
          font-size: 1.75rem;
        }

        .card-body {
          padding: 1.25rem;
        }
      }

      /* Animations */
      @keyframes fadeInDown {
        from {
          opacity: 0;
          transform: translateY(-20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      @keyframes fadeInUp {
        from {
          opacity: 0;
          transform: translateY(20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      /* Smooth Scrolling */
      html {
        scroll-behavior: smooth;
      }

      /* Additional Polish */
      .container {
        position: relative;
      }

      .text-center {
        margin-top: 2rem;
      }

      /* Profile Info Container Enhancement */
      .card-body .row > div {
        margin-bottom: 1rem;
      }

      @media (min-width: 768px) {
        .card-body .row > div:last-child {
          margin-bottom: 0;
        }
      }
      
      /* Profile page styling - using top bar */
      body {
        padding-top: 60px !important;
        padding-left: 0 !important;
      }
      
      /* Override top bar positioning for profile page (no sidebar) */
      .top-bar {
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
      }
      
      /* Hide old header */
      .main-header {
        display: none !important;
      }
      
      /* Modern Enhanced Header Styling */
      .main-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%) !important;
        height: 80px !important;
        position: fixed !important;
        z-index: 12 !important;
        width: 100% !important;
        top: 0 !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
        backdrop-filter: blur(10px) !important;
      }
      
      .main-header .logo {
        float: left !important;
        line-height: 80px !important;
        padding-left: 60px !important;
      }
      
      .main-header .logo a {
        font-size: 36px !important;
        text-transform: uppercase !important;
        font-weight: 800 !important;
        background: linear-gradient(135deg, #ffd700 0%, #f5a425 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        text-decoration: none !important;
        transition: all 0.3s ease !important;
        text-shadow: 0 0 20px rgba(245, 164, 37, 0.5) !important;
      }
      
      .main-header .logo a:hover {
        transform: scale(1.05) !important;
        filter: brightness(1.2) !important;
      }
      
      .main-menu {
        float: right !important;
        padding-right: 60px !important;
        list-style: none !important;
        margin: 0 !important;
        padding-top: 0 !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        height: 80px !important;
      }
      
      .main-menu li {
        display: inline-flex !important;
        align-items: center !important;
        line-height: 1 !important;
        margin-left: 0 !important;
        position: relative !important;
        height: 100% !important;
        vertical-align: middle !important;
      }
      
      .main-menu li a {
        padding: 8px 18px !important;
        font-size: 14px !important;
        text-transform: uppercase !important;
        letter-spacing: 1px !important;
        font-weight: 600 !important;
        color: #fff !important;
        text-decoration: none !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        border-radius: 20px !important;
        position: relative !important;
        overflow: hidden !important;
        background: rgba(255, 255, 255, 0.05) !important;
        border: 2px solid transparent !important;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1) !important;
        height: auto !important;
        line-height: 1.4 !important;
        margin: 0 !important;
        max-height: 50px !important;
      }
      
      .main-menu li a::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: -100% !important;
        width: 100% !important;
        height: 100% !important;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent) !important;
        transition: left 0.5s ease !important;
      }
      
      .main-menu li a:hover {
        color: #fff !important;
        background: linear-gradient(135deg, #f5a425 0%, #e5941f 100%) !important;
        border: 2px solid #f5a425 !important;
        transform: scale(1.05) !important;
        box-shadow: 0 2px 8px rgba(245, 164, 37, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
      }
      
      .main-menu li a:hover::before {
        left: 100% !important;
      }
      
      .main-menu li a:active {
        transform: translateY(0) !important;
        box-shadow: 0 2px 10px rgba(245, 164, 37, 0.3) !important;
      }
      
      /* Active/Current page indicator */
      .main-menu li a[href="#profile"] {
        background: linear-gradient(135deg, rgba(245, 164, 37, 0.2) 0%, rgba(229, 148, 31, 0.2) 100%) !important;
        border: 2px solid rgba(245, 164, 37, 0.5) !important;
      }
      
      .main-nav li:hover a,
      .main-nav li.active a {
        background: linear-gradient(135deg, #f5a425 0%, #e5941f 100%) !important;
        border: 2px solid #f5a425 !important;
        transform: scale(1.05) !important;
        box-shadow: 0 2px 8px rgba(245, 164, 37, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
      }
      
      /* Ensure header contains all elements - clip overflow */
      .main-header {
        overflow: hidden !important;
      }
      
      /* Keep buttons within header bounds */
      .main-menu li {
        overflow: visible !important;
      }
      
      .main-menu li a,
      .main-menu li a:hover {
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        vertical-align: middle !important;
        transform-origin: center !important;
      }
    </style>
  </head>

  <body class="no-sidebar">

<!-- Top Bar -->
<div class="top-bar">
    <div class="top-bar-left">
        <a href="<?= $baseUrl ?>/view/frontoffice/index.php" style="text-decoration: none;">
            <h4 style="margin: 0; color: #ffd700; font-weight: 700;">Wafra</h4>
        </a>
    </div>
    <div class="top-bar-right">
        <div class="user-info">
            <i class="fa fa-user-circle" style="font-size: 24px; color: #f5a425;"></i>
            <span class="user-name"><?= htmlspecialchars($_SESSION['firstname'] ?? '') . ' ' . htmlspecialchars($_SESSION['lastname'] ?? '') ?></span>
        </div>
        <!-- Notifications Bell -->
        <div class="notification-container" style="position: relative; margin-right: 10px;">
            <button class="notification-bell" id="notificationBell" onclick="toggleNotificationDropdown()" style="background: transparent; border: none; color: #fff; font-size: 20px; cursor: pointer; padding: 8px 12px; position: relative;">
                <i class="fa fa-bell"></i>
                <span class="notification-badge" id="notificationBadge" style="position: absolute; top: 5px; right: 5px; background: #e74c3c; color: #fff; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; display: none; align-items: center; justify-content: center; font-weight: bold;">0</span>
            </button>
            <div class="notification-dropdown" id="notificationDropdown" style="position: absolute; top: 100%; right: 0; background: #fff; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); width: 350px; max-height: 500px; overflow-y: auto; z-index: 1000; margin-top: 10px;">
                <div class="notification-header" style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                    <h5 style="margin: 0; font-weight: 600;">Notifications</h5>
                    <button onclick="markAllNotificationsRead()" style="background: none; border: none; color: #f5a425; cursor: pointer; font-size: 12px;">Tout marquer comme lu</button>
                </div>
                <div class="notification-list" id="notificationList" style="max-height: 400px; overflow-y: auto;">
                    <div class="text-center" style="padding: 20px; color: #999;">
                        <i class="fa fa-spinner fa-spin"></i> Chargement...
                    </div>
                </div>
            </div>
        </div>
        <a href="<?= $baseUrl ?>/view/frontoffice/index.php" class="profile-link" style="margin-right: 10px;">
            <i class="fa fa-home"></i>
            <span>Home</span>
        </a>
        <a href="<?= $baseUrl ?>/view/frontoffice/posts.php" class="profile-link" style="margin-right: 10px;">
            <i class="fa fa-comments"></i>
            <span>Posts</span>
        </a>
        <a href="<?= $baseUrl ?>/index.php?action=donations" class="profile-link" style="margin-right: 10px;">
            <i class="fa fa-gift"></i>
            <span>Donations</span>
        </a>
        <a href="<?= $baseUrl ?>/view/frontoffice/messages.php" class="profile-link" style="position: relative; margin-right: 10px;">
            <i class="fa fa-envelope"></i>
            <span>Messages</span>
            <span class="message-badge" id="messageBadge" style="position: absolute; top: -5px; right: -5px; background: #e74c3c; color: #fff; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; display: none; align-items: center; justify-content: center; font-weight: bold; z-index: 1000; line-height: 18px; text-align: center;">0</span>
        </a>
        <a href="<?= $baseUrl ?>/view/frontoffice/profile.php" class="profile-link">
            <i class="fa fa-user"></i>
            <span>Profil</span>
        </a>
        <a href="<?= $baseUrl ?>/index.php?action=logout" class="logout-link">
            <i class="fa fa-sign-out-alt"></i>
            <span>Déconnexion</span>
        </a>
    </div>
</div>

    <section class="section" id="profile">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-md-8">
            <?php
            // Display success/error messages
            if (isset($_GET['success'])) {
              $successMsg = '';
              if ($_GET['success'] === 'profile_updated') {
                $successMsg = 'Profile updated successfully!';
              } elseif ($_GET['success'] === 'profile_updated_email_verification') {
                $successMsg = 'Profile updated successfully! A verification email has been sent to your new email address.';
              } elseif ($_GET['success'] === 'picture_updated') {
                $successMsg = 'Profile picture updated successfully!';
              }
              if ($successMsg) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 10px; margin-bottom: 2rem; box-shadow: var(--shadow-md);">';
                echo '<i class="fa fa-check-circle"></i> ' . htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8');
                echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
                echo '<span aria-hidden="true">&times;</span>';
                echo '</button>';
                echo '</div>';
              }
            }
            if (isset($_GET['error'])) {
              echo '<div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 10px; margin-bottom: 2rem; box-shadow: var(--shadow-md);">';
              echo '<i class="fa fa-exclamation-circle"></i> ' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
              echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
              echo '<span aria-hidden="true">&times;</span>';
              echo '</button>';
              echo '</div>';
            }
            ?>
            <div class="section-heading text-center">
              <h2>Your Profile</h2>
              <p>Welcome to your WAFRA donation space.</p>
            </div>
            <div class="card">
              <div class="card-body">
                <div class="text-center mb-4">
                  <div class="profile-picture-container" style="display: inline-block; position: relative;">
                    <?php 
                    // Ensure profilePicturePath is set
                    if (!isset($profilePicturePath) || empty($profilePicturePath)) {
                        $profilePicturePath = '../uploads/profile_pictures/default.png';
                    }
                    ?>
                    <img 
                      src="<?php echo htmlspecialchars($profilePicturePath, ENT_QUOTES, 'UTF-8'); ?>" 
                      alt="Profile Picture" 
                      class="profile-picture"
                      style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-blue); box-shadow: var(--shadow-lg);"
                      onerror="this.src='<?php echo BASE_URL; ?>/view/frontoffice/assets/images/default-avatar.png'; this.onerror=null;"
                    >
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="profile-info-wrapper">
                      <p><strong>First Name:</strong> <?php echo $firstname; ?></p>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="profile-info-wrapper">
                      <p><strong>Last Name:</strong> <?php echo $lastname; ?></p>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="profile-info-wrapper">
                      <p><strong>Email:</strong> <?php echo $email; ?></p>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="profile-info-wrapper">
                      <p><strong>Role:</strong> <?php echo $role; ?></p>
                    </div>
                  </div>
                </div>
                <?php if ($isViewingOtherUser && $targetUserId != $_SESSION['userID']): ?>
                    <!-- Contact User Button -->
                    <div class="text-center mb-4" style="padding: 20px; background: #f8f9fa; border-radius: 12px; border: 2px dashed #e0e0e0;">
                        <?php
                        require_once __DIR__ . '/components/contact-button.php';
                        echo renderContactButton(
                            (int)$targetUserId,
                            null,
                            null,
                            [
                                'label' => '💬 Contacter cet utilisateur',
                                'icon' => 'fa-envelope',
                                'size' => 'lg',
                                'show_helper' => true
                            ]
                        );
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                  <?php if (!$isViewingOtherUser || $targetUserId == $_SESSION['userID']): ?>
                      <button type="button" class="btn btn-secondary mr-3" id="editProfileBtn" data-toggle="modal" data-target="#editProfileModal">
                        <i class="fa fa-edit"></i> Edit Profile
                      </button>
                  <?php endif; ?>
                  <form action="<?php echo BASE_URL; ?>/index.php?action=logout" method="post" style="display:inline;">
                    <button type="submit" class="btn btn-primary">Logout</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: var(--shadow-xl);">
          <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%); color: white; border-radius: 16px 16px 0 0; border: none;">
            <h5 class="modal-title" id="editProfileModalLabel" style="font-weight: 600;">
              <i class="fa fa-edit"></i> Edit Profile
            </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 0.9;">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body" style="padding: 2rem;">
            <form id="editProfileForm" action="<?php echo BASE_URL; ?>/index.php?action=update_profile" method="POST" enctype="multipart/form-data">
              <div class="form-group">
                <label for="editFirstname" style="font-weight: 600; color: var(--dark-gray); margin-bottom: 0.5rem;">
                  <i class="fa fa-user"></i> First Name
                </label>
                <input 
                  type="text" 
                  class="form-control" 
                  id="editFirstname" 
                  name="firstname" 
                  value="<?php echo $firstname; ?>" 
                  required
                  style="border-radius: 8px; border: 2px solid var(--border-gray); padding: 0.75rem; transition: all 0.3s ease;"
                  onfocus="this.style.borderColor='var(--primary-blue)'; this.style.boxShadow='0 0 0 3px rgba(37, 99, 235, 0.1)';"
                  onblur="this.style.borderColor='var(--border-gray)'; this.style.boxShadow='none';"
                >
              </div>
              
              <div class="form-group">
                <label for="editLastname" style="font-weight: 600; color: var(--dark-gray); margin-bottom: 0.5rem;">
                  <i class="fa fa-user"></i> Last Name
                </label>
                <input 
                  type="text" 
                  class="form-control" 
                  id="editLastname" 
                  name="lastname" 
                  value="<?php echo $lastname; ?>" 
                  required
                  style="border-radius: 8px; border: 2px solid var(--border-gray); padding: 0.75rem; transition: all 0.3s ease;"
                  onfocus="this.style.borderColor='var(--primary-blue)'; this.style.boxShadow='0 0 0 3px rgba(37, 99, 235, 0.1)';"
                  onblur="this.style.borderColor='var(--border-gray)'; this.style.boxShadow='none';"
                >
              </div>
              
              <div class="form-group">
                <label for="editEmail" style="font-weight: 600; color: var(--dark-gray); margin-bottom: 0.5rem;">
                  <i class="fa fa-envelope"></i> Email
                </label>
                <input 
                  type="email" 
                  class="form-control" 
                  id="editEmail" 
                  name="email" 
                  value="<?php echo $email; ?>" 
                  required
                  style="border-radius: 8px; border: 2px solid var(--border-gray); padding: 0.75rem; transition: all 0.3s ease;"
                  onfocus="this.style.borderColor='var(--primary-blue)'; this.style.boxShadow='0 0 0 3px rgba(37, 99, 235, 0.1)';"
                  onblur="this.style.borderColor='var(--border-gray)'; this.style.boxShadow='none';"
                >
              </div>
              
              <div class="form-group">
                <label for="editPassword" style="font-weight: 600; color: var(--dark-gray); margin-bottom: 0.5rem;">
                  <i class="fa fa-lock"></i> New Password <small style="color: var(--medium-gray); font-weight: 400;">(leave blank to keep current password)</small>
                </label>
                <input 
                  type="password" 
                  class="form-control" 
                  id="editPassword" 
                  name="new_password" 
                  placeholder="Enter new password (min. 8 characters)"
                  minlength="8"
                  style="border-radius: 8px; border: 2px solid var(--border-gray); padding: 0.75rem; transition: all 0.3s ease;"
                  onfocus="this.style.borderColor='var(--primary-blue)'; this.style.boxShadow='0 0 0 3px rgba(37, 99, 235, 0.1)';"
                  onblur="this.style.borderColor='var(--border-gray)'; this.style.boxShadow='none';"
                >
              </div>
              
              <div class="form-group">
                <label for="editConfirmPassword" style="font-weight: 600; color: var(--dark-gray); margin-bottom: 0.5rem;">
                  <i class="fa fa-lock"></i> Confirm New Password
                </label>
                <input 
                  type="password" 
                  class="form-control" 
                  id="editConfirmPassword" 
                  name="confirm_password" 
                  placeholder="Confirm new password"
                  style="border-radius: 8px; border: 2px solid var(--border-gray); padding: 0.75rem; transition: all 0.3s ease;"
                  onfocus="this.style.borderColor='var(--primary-blue)'; this.style.boxShadow='0 0 0 3px rgba(37, 99, 235, 0.1)';"
                  onblur="this.style.borderColor='var(--border-gray)'; this.style.boxShadow='none';"
                >
              </div>
              
              <div class="form-group">
                <label for="editProfilePicture" style="font-weight: 600; color: var(--dark-gray); margin-bottom: 0.5rem;">
                  <i class="fa fa-image"></i> Profile Picture
                </label>
                <div style="margin-bottom: 0.5rem;">
                  <?php 
                  // Ensure profilePicturePath is set for modal
                  if (!isset($profilePicturePath) || empty($profilePicturePath)) {
                      $profilePicturePath = 'uploads/profile_pictures/default.png';
                  }
                  ?>
                  <img 
                    src="<?php echo htmlspecialchars($profilePicturePath, ENT_QUOTES, 'UTF-8'); ?>" 
                    alt="Current Profile Picture" 
                    id="currentProfilePicturePreview"
                    style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border-gray); margin-bottom: 0.5rem;"
                    onerror="this.src='<?php echo BASE_URL; ?>/view/frontoffice/assets/images/default-avatar.png'; this.onerror=null;"
                  >
                </div>
                <input 
                  type="file" 
                  class="form-control-file" 
                  id="editProfilePicture" 
                  name="profile_picture" 
                  accept="image/jpeg,image/jpg,image/png"
                  style="border-radius: 8px; padding: 0.5rem;"
                  onchange="previewProfilePicture(this);"
                >
                <small style="color: var(--medium-gray); font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                  <i class="fa fa-info-circle"></i> Allowed formats: JPG, JPEG, PNG. Max size: 2MB.
                </small>
              </div>
              
              <div class="form-group" style="background: var(--light-gray); padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                <label style="font-weight: 600; color: var(--dark-gray); margin-bottom: 0.5rem;">
                  <i class="fa fa-shield"></i> Role
                </label>
                <input 
                  type="text" 
                  class="form-control" 
                  value="<?php echo ucfirst($role); ?>" 
                  disabled
                  style="border-radius: 8px; border: 2px solid var(--border-gray); padding: 0.75rem; background: white; cursor: not-allowed;"
                >
                <small style="color: var(--medium-gray); font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                  <i class="fa fa-info-circle"></i> Role cannot be changed for security reasons.
                </small>
              </div>
            </form>
          </div>
          <div class="modal-footer" style="border-top: 1px solid var(--border-gray); padding: 1.5rem 2rem; border-radius: 0 0 16px 16px;">
            <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 8px; padding: 0.75rem 1.5rem; font-weight: 600;">
              Cancel
            </button>
            <button type="submit" form="editProfileForm" class="btn btn-primary" style="border-radius: 8px; padding: 0.75rem 1.5rem; font-weight: 600;">
              <i class="fa fa-save"></i> Save Changes
            </button>
          </div>
        </div>
      </div>
    </div>

    <footer>
      <div class="container">
        <div class="row">
          <div class="col-md-12">
            <p>
              <i class="fa fa-copyright"></i> Copyright 2020 by Grad School |
              Design:
              <a href="https://templatemo.com" rel="sponsored" target="_parent"
                >TemplateMo</a
              >
            </p>
          </div>
        </div>
      </div>
    </footer>

    <script src="<?php echo BASE_URL; ?>/view/frontoffice/assets/vendor/jquery/jquery.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/view/frontoffice/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/view/frontoffice/assets/js/isotope.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/view/frontoffice/assets/js/owl-carousel.js"></script>
    <script src="<?php echo BASE_URL; ?>/view/frontoffice/assets/js/lightbox.js"></script>
    <script src="<?php echo BASE_URL; ?>/view/frontoffice/assets/js/tabs.js"></script>
    <script src="<?php echo BASE_URL; ?>/view/frontoffice/assets/js/video.js"></script>
    <script src="<?php echo BASE_URL; ?>/view/frontoffice/assets/js/slick-slider.js"></script>
    <script src="<?php echo BASE_URL; ?>/view/frontoffice/assets/js/custom.js"></script>
    
    <script>
      // Profile picture preview function
      function previewProfilePicture(input) {
        if (input.files && input.files[0]) {
          const file = input.files[0];
          const maxSize = 2 * 1024 * 1024; // 2MB in bytes
          const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
          
          // Validate file type
          if (!allowedTypes.includes(file.type)) {
            alert('Invalid file type. Only JPG, JPEG, and PNG files are allowed.');
            input.value = '';
            return;
          }
          
          // Validate file size
          if (file.size > maxSize) {
            alert('File size exceeds 2MB limit. Please choose a smaller file.');
            input.value = '';
            return;
          }
          
          // Preview the image
          const reader = new FileReader();
          reader.onload = function(e) {
            const preview = document.getElementById('currentProfilePicturePreview');
            if (preview) {
              preview.src = e.target.result;
            }
          };
          reader.readAsDataURL(file);
        }
      }
      
      // Password validation for edit profile form
      document.addEventListener('DOMContentLoaded', function() {
        // Ensure modal works - manual trigger if Bootstrap data attributes don't work
        const editProfileBtn = document.getElementById('editProfileBtn');
        const editProfileModal = document.getElementById('editProfileModal');
        
        if (editProfileBtn && editProfileModal) {
          editProfileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Try Bootstrap 4 method
            if (typeof jQuery !== 'undefined' && jQuery.fn.modal) {
              jQuery(editProfileModal).modal('show');
            } 
            // Try Bootstrap 5 method
            else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
              const modal = new bootstrap.Modal(editProfileModal);
              modal.show();
            }
            // Fallback: manual show
            else {
              editProfileModal.style.display = 'block';
              editProfileModal.classList.add('show');
              document.body.classList.add('modal-open');
              const backdrop = document.createElement('div');
              backdrop.className = 'modal-backdrop fade show';
              backdrop.id = 'modalBackdrop';
              document.body.appendChild(backdrop);
            }
          });
          
          // Handle modal close
          const closeButtons = editProfileModal.querySelectorAll('[data-dismiss="modal"], .close, [aria-label="Close"]');
          closeButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
              if (typeof jQuery !== 'undefined' && jQuery.fn.modal) {
                jQuery(editProfileModal).modal('hide');
              } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = bootstrap.Modal.getInstance(editProfileModal);
                if (modal) modal.hide();
              } else {
                editProfileModal.style.display = 'none';
                editProfileModal.classList.remove('show');
                document.body.classList.remove('modal-open');
                const backdrop = document.getElementById('modalBackdrop');
                if (backdrop) backdrop.remove();
              }
            });
          });
          
          // Close modal when clicking outside
          editProfileModal.addEventListener('click', function(e) {
            if (e.target === editProfileModal) {
              if (typeof jQuery !== 'undefined' && jQuery.fn.modal) {
                jQuery(editProfileModal).modal('hide');
              } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = bootstrap.Modal.getInstance(editProfileModal);
                if (modal) modal.hide();
              } else {
                editProfileModal.style.display = 'none';
                editProfileModal.classList.remove('show');
                document.body.classList.remove('modal-open');
                const backdrop = document.getElementById('modalBackdrop');
                if (backdrop) backdrop.remove();
              }
            }
          });
        }
        
        const editProfileForm = document.getElementById('editProfileForm');
        const newPasswordField = document.getElementById('editPassword');
        const confirmPasswordField = document.getElementById('editConfirmPassword');
        
        if (editProfileForm) {
          editProfileForm.addEventListener('submit', function(e) {
            const newPassword = newPasswordField.value.trim();
            const confirmPassword = confirmPasswordField.value.trim();
            
            // If password is provided, validate it
            if (newPassword !== '') {
              if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                newPasswordField.focus();
                return false;
              }
              
              if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match. Please try again.');
                confirmPasswordField.focus();
                return false;
              }
            } else {
              // If new password is empty, clear confirm password
              confirmPasswordField.value = '';
            }
          });
          
          // Real-time password matching validation
          confirmPasswordField.addEventListener('blur', function() {
            const newPassword = newPasswordField.value.trim();
            const confirmPassword = confirmPasswordField.value.trim();
            
            if (newPassword !== '' && confirmPassword !== '' && newPassword !== confirmPassword) {
              this.setCustomValidity('Passwords do not match');
              this.style.borderColor = '#ef4444';
            } else {
              this.setCustomValidity('');
              this.style.borderColor = '';
            }
          });
          
          newPasswordField.addEventListener('input', function() {
            confirmPasswordField.setCustomValidity('');
            confirmPasswordField.style.borderColor = '';
          });
        }
        
        // Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
          setTimeout(function() {
            // Try Bootstrap 4 method
            if (typeof jQuery !== 'undefined' && jQuery.fn.alert) {
              jQuery(alert).alert('close');
            }
            // Try Bootstrap 5 method
            else if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
              const bsAlert = new bootstrap.Alert(alert);
              if (bsAlert) {
                bsAlert.close();
              }
            }
            // Fallback: manual close
            else {
              alert.style.transition = 'opacity 0.3s';
              alert.style.opacity = '0';
              setTimeout(function() {
                alert.remove();
              }, 300);
            }
          }, 5000);
        });
      });

    // ========== NOTIFICATION SYSTEM ==========
    let notificationPollInterval = null;
    const NOTIFICATION_POLL_INTERVAL = 30000; // 30 seconds

    // Load notifications on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadNotifications();
        updateNotificationCount();
        
        // Start polling for new notifications
        notificationPollInterval = setInterval(() => {
            updateNotificationCount();
            // Reload notifications if dropdown is open
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown && dropdown.classList.contains('show')) {
                loadNotifications();
            }
        }, NOTIFICATION_POLL_INTERVAL);
    });

    // Toggle notification dropdown (enhanced version)
    function toggleNotificationDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown) return;
        
        const isVisible = dropdown.classList.contains('show');
        if (isVisible) {
            dropdown.classList.remove('show');
        } else {
            dropdown.classList.add('show');
            loadNotifications();
        }
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const container = document.querySelector('.notification-container');
        const dropdown = document.getElementById('notificationDropdown');
        if (container && dropdown && !container.contains(event.target) && dropdown.classList.contains('show')) {
            dropdown.classList.remove('show');
        }
    });

    // Load notifications
    function loadNotifications() {
        const list = document.getElementById('notificationList');
        if (!list) return;
        list.innerHTML = '<div class="text-center" style="padding: 20px; color: #999;"><i class="fa fa-spinner fa-spin"></i> Chargement...</div>';
        
        fetch('<?= $baseUrl ?>/index.php?action=api_notifications&subaction=getUnread&limit=10')
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Non-JSON response:', text.substring(0, 200));
                        throw new Error('Invalid response format');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.notifications) {
                    renderNotifications(data.notifications);
                    updateNotificationBadge(data.unread_count || 0);
                } else {
                    list.innerHTML = '<div class="text-center" style="padding: 20px; color: #999;">Aucune notification</div>';
                }
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
                if (list) list.innerHTML = '<div class="text-center" style="padding: 20px; color: #e74c3c;">Erreur de chargement</div>';
            });
    }

    // Render notifications
    function renderNotifications(notifications) {
        const list = document.getElementById('notificationList');
        if (!list) return;
        
        if (notifications.length === 0) {
            list.innerHTML = '<div class="text-center" style="padding: 20px; color: #999;">Aucune notification</div>';
            return;
        }
        
        list.innerHTML = notifications.map(notif => {
            const unreadClass = !notif.is_read ? 'unread' : '';
            const icon = getNotificationIcon(notif.type);
            return `
                <div class="notification-item ${unreadClass}" onclick="handleNotificationClick(${notif.id}, '${notif.entity_type}', ${notif.entity_id})" style="padding: 12px 15px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s; ${unreadClass ? 'background: #f8f9fa; font-weight: 600;' : ''}">
                    <div style="display: flex; align-items: start; gap: 10px;">
                        <i class="fa ${icon}" style="color: #f5a425; font-size: 18px; margin-top: 2px;"></i>
                        <div style="flex: 1;">
                            <div style="font-size: 14px; color: #333; margin-bottom: 4px;">${escapeHtml(notif.message)}</div>
                            <div style="font-size: 11px; color: #999;">${notif.time_ago || ''}</div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Get notification icon based on type
    function getNotificationIcon(type) {
        const icons = {
            'post_liked': 'fa-heart',
            'post_commented': 'fa-comment',
            'comment_replied': 'fa-reply',
            'post_milestone': 'fa-trophy',
            'post_removed': 'fa-ban'
        };
        return icons[type] || 'fa-bell';
    }

    // Handle notification click
    function handleNotificationClick(notificationId, entityType, entityId) {
        // Mark as read
        markNotificationAsRead(notificationId);
        
        // Navigate based on entity type
        if (entityType === 'conversation') {
            // Redirect to messages page with conversation ID
            window.location.href = '<?= $baseUrl ?>/view/frontoffice/messages.php?conversation_id=' + entityId;
        } else if (entityType === 'post') {
            window.location.href = '<?= $baseUrl ?>/view/frontoffice/posts.php?post_id=' + entityId;
        } else if (entityType === 'comment') {
            // For comments, we need to get the post_id from the comment
            fetch('<?= $baseUrl ?>/index.php?action=api_post_comment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_comment', id_comment: entityId })
            })
            .then(response => response.json())
            .then(data => {
                if (data && data.success && data.comment && data.comment.id_post) {
                    window.location.href = '<?= $baseUrl ?>/view/frontoffice/posts.php?post_id=' + data.comment.id_post + '&comment_id=' + entityId;
                } else {
                    window.location.href = '<?= $baseUrl ?>/view/frontoffice/posts.php';
                }
            })
            .catch(error => {
                console.error('Error fetching comment:', error);
                window.location.href = '<?= $baseUrl ?>/view/frontoffice/posts.php';
            });
        }
        
        // Close dropdown
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown) dropdown.classList.remove('show');
    }

    // Mark notification as read
    function markNotificationAsRead(notificationId) {
        fetch('<?= $baseUrl ?>/index.php?action=api_notifications&subaction=markAsRead', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationCount();
                loadNotifications();
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
    }

    // Mark all as read
    function markAllNotificationsRead() {
        fetch('<?= $baseUrl ?>/index.php?action=api_notifications&subaction=markAllAsRead', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationCount();
                loadNotifications();
            }
        })
        .catch(error => console.error('Error marking all as read:', error));
    }

    // Update notification count badge
    function updateNotificationCount() {
        fetch('<?= $baseUrl ?>/index.php?action=api_notifications&subaction=getCount')
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Non-JSON response:', text.substring(0, 200));
                        throw new Error('Invalid response format');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    updateNotificationBadge(data.count || 0);
                }
            })
            .catch(error => console.error('Error updating notification count:', error));
    }

    // Update badge display
    function updateNotificationBadge(count) {
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.add('show');
            } else {
                badge.classList.remove('show');
            }
        }
    }

    // Escape HTML helper
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Update message count
    function updateMessageCount() {
        fetch(baseUrl + '/index.php?action=api_message&subaction=get_unread_count')
            .then(async response => {
                const responseText = await response.text();
                if (!response.ok) {
                    return { success: false };
                }
                try {
                    return JSON.parse(responseText);
                } catch (e) {
                    return { success: false };
                }
            })
            .then(data => {
                if (data.success) {
                    updateMessageBadge(data.count || 0);
                }
            })
            .catch(error => console.error('Error updating message count:', error));
    }
    
    // Update message badge display
    function updateMessageBadge(count) {
        const badge = document.getElementById('messageBadge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.setProperty('display', 'flex', 'important');
                badge.style.setProperty('visibility', 'visible', 'important');
                badge.style.setProperty('opacity', '1', 'important');
            } else {
                badge.style.setProperty('display', 'none', 'important');
            }
        }
    }
    
    // Load message count on page load and update periodically
    updateMessageCount();
    setInterval(updateMessageCount, 30000); // Update every 30 seconds
    </script>

    <!-- Enhanced Top Bar JavaScript -->
    <script src="<?php echo BASE_URL; ?>/view/frontoffice/assets/js/topbar-enhanced.js"></script>

    <?php include __DIR__ . '/../components/chatbot.php'; ?>
  </body>
</html>


