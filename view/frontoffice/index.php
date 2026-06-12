<?php
/**
 * Frontend Single Entry Point - All in One File
 * Single page layout with left sidebar navigation
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../config/autoload.php';

// Force baseUrl to correct value
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $protocol . '://' . $host . '/wafra/wafra-integration';
if (!defined('BASE_URL')) {
    define('BASE_URL', $baseUrl);
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    error_log("POST received in index.php - Action: " . $action . ", POST data: " . json_encode($_POST));
    
    switch ($action) {
        case 'save_reservation':
        case 'reservation_create':
            require_once __DIR__ . '/../../controllers/ReservationController.php';
            $controller = new ReservationController();
            $controller->create();
            break;
            
        case 'update_reservation':
        case 'reservation_update':
            require_once __DIR__ . '/../../controllers/ReservationController.php';
            $controller = new ReservationController();
            $controller->update();
            break;
            
        default:
            error_log("Unknown POST action: " . $action);
            $redirectUrl = $baseUrl . '/view/frontoffice/index.php#reservations';
            // Prevent redirect loops by ensuring we don't add duplicate paths
            $redirectUrl = str_replace('/view/frontoffice/view/frontoffice', '/view/frontoffice', $redirectUrl);
            header('Location: ' . $redirectUrl);
            exit;
    }
}

// Check if user is logged in
if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: ' . $baseUrl . '/view/frontoffice/login.php');
    exit;
}

// Load all data needed for the single page
require_once __DIR__ . '/../../models/Event.php';
require_once __DIR__ . '/../../models/Reservation.php';
require_once __DIR__ . '/../../models/Reclamation.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Association.php';
require_once __DIR__ . '/../../models/Cotisation.php';

$pdo = Database::connect();
$eventModel = new Event($pdo);
$reservationModel = new Reservation($pdo);
$reclamationModel = new Reclamation($pdo);
$userModel = new User($pdo);
$associationModel = new Association($pdo);
$cotisationModel = new Cotisation($pdo);

$userId = (int)$_SESSION['userID'];

// Get all events (like in wafra-events)
$pageNum = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$searchTerm = $_GET['search'] ?? '';
$eventsResult = $eventModel->getAll($pageNum, 10, $searchTerm);
$evenements = $eventsResult['data'] ?? [];
$totalItems = $eventsResult['total'] ?? 0;
$perPage = 10;
$totalPages = max(1, ceil($totalItems / $perPage));

// Get featured events (first 3)
$featuredEvents = array_slice($evenements, 0, 3);

// Get reservations for this user
$reservations = $reservationModel->selectAllWithEvent($userId);
// Debug: Log reservation count and user info
error_log("User ID: $userId, Reservations found: " . count($reservations));
if (count($reservations) === 0) {
    // Try to find any reservations with this CIN to debug
    $debugStmt = $pdo->prepare("SELECT * FROM reservations WHERE cin = ?");
    $debugStmt->execute([$userId]);
    $debugReservations = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Debug: Direct query for CIN=$userId found " . count($debugReservations) . " reservations");
    if (count($debugReservations) > 0) {
        error_log("Debug: First reservation: " . json_encode($debugReservations[0]));
    }
}
$reservationToEdit = null;
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    $reservationToEdit = $reservationModel->select($editId);
}

// Get all events for reservation form
$allEventsResult = $eventModel->getAll(1, 100);
$allEvenements = $allEventsResult['data'] ?? [];

// Get event details if event_id is provided
$selectedEvent = null;
if (isset($_GET['event_id'])) {
    $eventId = (int)$_GET['event_id'];
    $selectedEvent = $eventModel->find($eventId);
}

// Check if user is admin
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Get reclamations for this user
$userReclamations = $reclamationModel->getUserReclamations($userId);
$currentUser = $userModel->getUserByCin($userId);

// Get associations and cotisations data
$associations = $associationModel->getAllAssociations('Active');
$userAssociations = $associationModel->getUserAssociations($userId);
$userAssociationIds = array_column($userAssociations, 'id');
$userCotisations = $cotisationModel->getUserCotisations($userId);

// Get session messages
$successMessage = $_SESSION['success'] ?? null;
$errorMessages = $_SESSION['errors'] ?? [];
$reclamationSuccessMessage = $_SESSION['reclamation_success'] ?? null;
$reclamationErrorMessage = $_SESSION['reclamation_error'] ?? null;

// Clear session messages
unset($_SESSION['success'], $_SESSION['errors'], $_SESSION['reclamation_success'], $_SESSION['reclamation_error']);

$softskillsList = [
    "Romans", "Science-fiction", "Fantasy", "Policier / Thriller", "Horreur",
    "Romance", "Aventure", "Classiques", "Essais", "Développement personnel",
    "Biographies / Mémoires", "Philosophie", "Histoire", "Jeunesse", "Manga",
    "Bandes dessinées", "Poésie", "Théâtre", "Livres scolaires", "Livres universitaires",
    "Cuisine / Gastronomie", "Art / Photographie", "Business / Management",
    "Technologie / Informatique", "Spiritualité / Religion", "Santé / Bien-être"
];

$pageTitle = 'Wafra';
$pageDescription = 'Découvrez et réservez vos événements';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:100,200,300,400,500,600,700,800,900" rel="stylesheet">
    
    <!-- Bootstrap core CSS -->
    <link href="<?= $baseUrl ?>/view/frontoffice/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Additional CSS Files -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/fontawesome.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/templatemo-grad-school.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/owl.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/lightbox.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/flex-slider.css">
    
    <!-- Top Bar CSS - Loaded last to override all template styles -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/topbar.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/topbar-enhanced.css">
    
    <!-- Chatbot CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/components/chatbot.css">
    
    <!-- Additional inline styles -->
    <style>
        /* Ensure chatbot is positioned correctly and visible */
        .chatbot-toggle,
        .chatbot-container {
            position: fixed !important;
            z-index: 10000 !important;
        }
        .chatbot-toggle {
            bottom: 30px !important;
            right: 30px !important;
        }
        .chatbot-container {
            bottom: 30px !important;
            right: 30px !important;
        }
    </style>
    <style>
        .sidebar-nav {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #1a1a1a;
            padding: 20px 0;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-nav .logo {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #333;
            margin-bottom: 20px;
        }
        
        .sidebar-nav .logo a {
            color: #ffd700;
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
        }
        
        .sidebar-nav .logo a em {
            color: #ffd700;
        }
        
        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav ul li {
            margin: 0;
        }
        
        .sidebar-nav ul li a {
            display: block;
            padding: 15px 20px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-nav ul li a:hover,
        .sidebar-nav ul li a.active {
            background: #2a2a2a;
            border-left-color: #f5a425;
            color: #f5a425;
        }
        
        .main-content {
            min-height: 100vh;
        }
        
        .section {
            padding: 80px 0;
            min-height: 100vh;
        }
        
        .item {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .item:hover {
            transform: translateY(-5px);
            box-shadow: 0px 10px 25px rgba(0,0,0,0.3) !important;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
        }
        
        /* Informational Sections Styling - Keep template background, improve text readability */
        #about .down-content,
        #mission .down-content,
        #how-it-works .down-content,
        #help .down-content,
        #contact .down-content,
        #transparency .down-content,
        #privacy .down-content,
        #terms .down-content {
            color: #fff;
        }
        
        #about .down-content p,
        #mission .down-content p,
        #help .down-content p,
        #contact .down-content p,
        #transparency .down-content p,
        #privacy .down-content p,
        #terms .down-content p {
            font-size: 16px;
            line-height: 1.8;
            color: #fff;
            margin-bottom: 20px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        #about .down-content .lead,
        #mission .down-content .lead,
        #help .down-content .lead {
            font-size: 20px;
            font-weight: 600;
            color: #ffd700;
            margin-bottom: 25px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        #about .down-content h4,
        #mission .down-content h4,
        #help .down-content h4,
        #contact .down-content h4,
        #transparency .down-content h4,
        #privacy .down-content h4,
        #terms .down-content h4 {
            color: #f5a425;
            font-weight: 700;
            margin-top: 30px;
            margin-bottom: 20px;
            font-size: 22px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        #about .down-content ul,
        #mission .down-content ul,
        #help .down-content ul,
        #transparency .down-content ul,
        #privacy .down-content ul,
        #terms .down-content ul {
            list-style: none;
            padding-left: 0;
        }
        
        #about .down-content ul li,
        #mission .down-content ul li,
        #help .down-content ul li,
        #transparency .down-content ul li,
        #privacy .down-content ul li,
        #terms .down-content ul li {
            padding: 12px 0;
            padding-left: 30px;
            position: relative;
            font-size: 16px;
            line-height: 1.8;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        #about .down-content ul li:before,
        #mission .down-content ul li:before,
        #help .down-content ul li:before,
        #transparency .down-content ul li:before,
        #privacy .down-content ul li:before,
        #terms .down-content ul li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
            font-size: 18px;
            text-shadow: none;
        }
        
        #about .down-content ul li strong,
        #mission .down-content ul li strong,
        #help .down-content ul li strong,
        #transparency .down-content ul li strong,
        #privacy .down-content ul li strong,
        #terms .down-content ul li strong {
            color: #ffd700;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        
        #how-it-works .down-content h5 {
            color: #f5a425;
            font-weight: 700;
            font-size: 20px;
            margin-top: 20px;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        #how-it-works .down-content p {
            color: #fff;
            font-size: 15px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        #help .accordion-item {
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 10px;
            border-radius: 8px;
            overflow: hidden;
            background: rgba(255,255,255,0.1);
        }
        
        #help .accordion-button {
            background: rgba(245, 164, 37, 0.2);
            color: #fff;
            font-weight: 600;
            padding: 15px 20px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        #help .accordion-button:not(.collapsed) {
            background: rgba(245, 164, 37, 0.8);
            color: #fff;
        }
        
        #help .accordion-body {
            background: rgba(255,255,255,0.1);
            color: #fff;
            padding: 20px;
            font-size: 15px;
            line-height: 1.8;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        #contact .down-content p {
            font-size: 18px;
            margin-bottom: 15px;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        #contact .down-content i {
            color: #f5a425;
            margin-right: 10px;
            font-size: 20px;
            width: 25px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        
        @media (max-width: 768px) {
            body {
                padding-left: 0;
            }
            
            .sidebar-nav {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .sidebar-nav.active {
                transform: translateX(0);
            }
            
            .mobile-menu-toggle {
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                background: #1a1a1a;
                color: #fff;
                border: none;
                padding: 10px 15px;
                cursor: pointer;
                border-radius: 5px;
            }
            
            #about .course-item,
            #mission .course-item,
            #help .course-item,
            #contact .course-item,
            #transparency .course-item,
            #privacy .course-item,
            #terms .course-item {
                padding: 25px;
            }
        }
    </style>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <div class="top-bar-left">
        <h4 style="margin: 0; color: #ffd700; font-weight: 700;">Wafra</h4>
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

<!-- Mobile Menu Toggle -->
<button class="mobile-menu-toggle d-md-none" onclick="toggleSidebar()">
    <i class="fa fa-bars"></i>
</button>

<!-- Left Sidebar Navigation -->
<nav class="sidebar-nav" id="sidebar">
    <div class="logo-user-container" style="display: flex; align-items: center; gap: 12px; padding: 20px; border-bottom: 1px solid #333; margin-bottom: 20px;">
        <div class="logo" style="flex: 0 0 auto;">
            <a href="#home" style="color: #ffd700; font-size: 24px; font-weight: bold; text-decoration: none;">Wafra</a>
        </div>
        <div class="user-avatar" style="flex: 0 0 auto; margin-left: auto;">
            <?php
            $profilePicture = $currentUser['profile_picture'] ?? null;
            if ($profilePicture && file_exists(__DIR__ . '/../../' . $profilePicture)) {
                echo '<img src="' . $baseUrl . '/' . htmlspecialchars($profilePicture) . '" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #ffd700;">';
            } else {
                echo '<i class="fa fa-user-circle" style="font-size: 40px; color: #f5a425;"></i>';
            }
            ?>
        </div>
    </div>
    <ul>
        <li><a href="#home" class="active">Accueil</a></li>
        <li><a href="#events">Événements</a></li>
        <li><a href="#reservations">Réservations</a></li>
        <li><a href="#reclamations">Réclamations</a></li>
        <li><a href="#associations">Associations</a></li>
        <li><a href="#cotisations">Cotisations</a></li>
        
        <!-- Divider -->
        <li style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 1rem; padding-top: 1rem;"></li>
        
        <!-- About WAFRA Section -->
        <li style="padding: 10px 20px; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.7; color: #999;">À propos de WAFRA</li>
        <li><a href="#about" style="font-size: 0.875rem; padding: 10px 20px;"><i class="fa fa-info-circle" style="margin-right: 8px; font-size: 0.875rem;"></i>À propos</a></li>
        <li><a href="#mission" style="font-size: 0.875rem; padding: 10px 20px;"><i class="fa fa-bullseye" style="margin-right: 8px; font-size: 0.875rem;"></i>Notre Mission</a></li>
        <li><a href="#how-it-works" style="font-size: 0.875rem; padding: 10px 20px;"><i class="fa fa-question-circle" style="margin-right: 8px; font-size: 0.875rem;"></i>Comment ça marche</a></li>
        
        <!-- Support & Help Section -->
        <li style="padding: 10px 20px; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.7; color: #999; margin-top: 0.5rem;">Support & Aide</li>
        <li><a href="#help" style="font-size: 0.875rem; padding: 10px 20px;"><i class="fa fa-question-circle" style="margin-right: 8px; font-size: 0.875rem;"></i>Centre d'aide / FAQ</a></li>
        <li><a href="#contact" style="font-size: 0.875rem; padding: 10px 20px;"><i class="fa fa-envelope" style="margin-right: 8px; font-size: 0.875rem;"></i>Nous contacter</a></li>
        
        <!-- Trust & Legal Section -->
        <li style="padding: 10px 20px; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.7; color: #999; margin-top: 0.5rem;">Confiance & Légal</li>
        <li><a href="#transparency" style="font-size: 0.875rem; padding: 10px 20px;"><i class="fa fa-eye" style="margin-right: 8px; font-size: 0.875rem;"></i>Transparence</a></li>
        <li><a href="#privacy" style="font-size: 0.875rem; padding: 10px 20px;"><i class="fa fa-shield-alt" style="margin-right: 8px; font-size: 0.875rem;"></i>Politique de confidentialité</a></li>
        <li><a href="#terms" style="font-size: 0.875rem; padding: 10px 20px;"><i class="fa fa-file-alt" style="margin-right: 8px; font-size: 0.875rem;"></i>Conditions d'utilisation</a></li>
    </ul>
</nav>

<!-- Main Content -->
<div class="main-content">
    
    <!-- Home Section -->
    <section class="section main-banner" id="home" data-section="section1">
        <video autoplay muted loop id="bg-video">
            <source src="<?= $baseUrl ?>/view/frontoffice/assets/images/course-video.mp4" type="video/mp4" />
        </video>
        <div class="video-overlay header-text">
            <div class="caption">
                <h6>Wafra</h6>
                <h2><em>Découvrez</em> nos Événements</h2>
                <div class="main-button">
                    <div class="scroll-to-section"><a href="#events">Voir les événements</a></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" data-section="section2">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-12">
                    <div class="features-post">
                        <div class="features-content">
                            <div class="content-show">
                                <h4><i class="fa fa-calendar"></i>Tous les Événements</h4>
                            </div>
                            <div class="content-hide">
                                <p>Découvrez tous nos événements et choisissez celui qui vous convient le mieux.</p>
                                <div class="scroll-to-section"><a href="#events">Voir plus</a></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-12">
                    <div class="features-post second-features">
                        <div class="features-content">
                            <div class="content-show">
                                <h4><i class="fa fa-ticket"></i>Réservations Faciles</h4>
                            </div>
                            <div class="content-hide">
                                <p>Réservez votre place en quelques clics. Simple, rapide et sécurisé.</p>
                                <div class="scroll-to-section"><a href="#reservations">Réserver</a></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-12">
                    <div class="features-post third-features">
                        <div class="features-content">
                            <div class="content-show">
                                <h4><i class="fa fa-info-circle"></i>Informations Détaillées</h4>
                            </div>
                            <div class="content-hide">
                                <p>Consultez tous les détails de nos événements : dates, lieux, descriptions.</p>
                                <div class="scroll-to-section"><a href="#events">En savoir plus</a></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Events Section -->
    <?php if (!empty($featuredEvents)): ?>
    <section class="section courses" data-section="section3">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-heading">
                        <h2>Événements en Vedette</h2>
                    </div>
                </div>
            </div>
            <div class="row">
                <?php foreach ($featuredEvents as $event): ?>
                    <?php 
                        $event = (array)$event;
                        $eventId = $event['id'] ?? 0;
                        $nom = htmlspecialchars($event['nom_evenement'] ?? '');
                        $type = htmlspecialchars($event['type_evenement'] ?? '');
                        $description = !empty($event['description']) ? htmlspecialchars(substr($event['description'], 0, 100)) : '';
                        $description .= (!empty($event['description']) && strlen($event['description']) > 100) ? '...' : '';
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="item">
                            <div class="down-content">
                                <h4><?= $nom ?></h4>
                                <p><strong>Type:</strong> <?= $type ?></p>
                                <p><?= $description ?></p>
                                <div class="text-button-pay">
                                    <a href="<?= $baseUrl ?>/view/frontoffice/index.php?event_id=<?= $eventId ?>#events">Voir détails <i class="fa fa-angle-double-right"></i></a>
                                </div>
                                <div class="mt-2">
                                    <a href="<?= $baseUrl ?>/view/frontoffice/index.php?event_id=<?= $eventId ?>&reserve=1#reservations" class="btn btn-sm btn-success">
                                        <i class="fa fa-ticket"></i> Réserver
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Events Section -->
    <section class="section courses" id="events" data-section="section4">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-heading">
                        <h2>Nos Événements</h2>
                    </div>
                </div>
                
                <?php if ($successMessage): ?>
                    <div class="col-md-12">
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($successMessage) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMessages)): ?>
                    <div class="col-md-12">
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($errorMessages as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Search Bar -->
                <div class="col-md-12 mb-5">
                    <form id="eventSearchForm" class="row g-3 justify-content-center">
                        <div class="col-md-6 col-lg-5">
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f5a425; color: #fff; border: none;">
                                    <i class="fa fa-search"></i>
                                </span>
                                <input type="text" id="eventSearchInput" class="form-control" 
                                       placeholder="Rechercher un événement..." 
                                       value="<?= htmlspecialchars($searchTerm) ?>"
                                       style="border: 2px solid rgba(250,250,250,0.1); background-color: rgba(255,255,255,0.1); color: #fff;">
                            </div>
                        </div>
                        <div class="col-md-auto">
                            <button type="button" id="eventSearchBtn" class="btn" style="background-color: #f5a425; color: #fff; border: none; padding: 10px 25px; font-weight: 700; text-transform: uppercase;">
                                <span id="searchBtnText">Rechercher</span>
                                <span id="searchBtnLoader" style="display: none;">
                                    <i class="fa fa-spinner fa-spin"></i>
                                </span>
                            </button>
                        </div>
                        <div class="col-md-auto" id="resetSearchBtn" style="display: <?= !empty($searchTerm) ? 'block' : 'none' ?>;">
                            <button type="button" id="resetSearch" class="btn" style="background-color: transparent; color: #fff; border: 2px solid rgba(250,250,250,0.3); padding: 10px 25px;">
                                <i class="fa fa-undo"></i> Réinitialiser
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="row" id="eventsContainer">
                <?php if (is_array($evenements) && count($evenements) > 0): ?>
                    <?php foreach ($evenements as $e): ?>
                        <?php if (is_array($e) || is_object($e)): ?>
                            <?php 
                                $e = (array)$e;
                                $nom = htmlspecialchars($e['nom_evenement'] ?? 'Non défini');
                                $type = htmlspecialchars($e['type_evenement'] ?? 'Non défini');
                                $dateDebut = !empty($e['date_debut']) ? date('d/m/Y H:i', strtotime($e['date_debut'])) : 'Non définie';
                                $dateFin = !empty($e['date_fin']) ? date('d/m/Y H:i', strtotime($e['date_fin'])) : 'Non définie';
                                $description = !empty($e['description']) ? htmlspecialchars(substr($e['description'], 0, 120)) : '';
                                $description .= (!empty($e['description']) && strlen($e['description']) > 120) ? '...' : '';
                                $eventId = $e['id'] ?? 0;
                                $lieu = htmlspecialchars($e['lieu'] ?? 'Non défini');
                            ?>
                            <div class="col-md-4 mb-5">
                                <div class="item" style="background-color: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0px 5px 15px rgba(0,0,0,0.2); transition: transform 0.3s;">
                                    <div class="down-content" style="padding: 35px;">
                                        <div style="display: flex; align-items: center; margin-bottom: 20px;">
                                            <div style="width: 50px; height: 50px; background-color: #f5a425; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                                                <i class="fa fa-calendar" style="color: #fff; font-size: 20px;"></i>
                                            </div>
                                            <h4 style="margin: 0; flex: 1;"><?= $nom ?></h4>
                                        </div>
                                        
                                        <div style="margin-bottom: 15px; padding: 12px; background-color: #f8f9fa; border-radius: 5px;">
                                            <p style="margin: 5px 0; color: #1e1e1e;">
                                                <i class="fa fa-tag" style="color: #f5a425; margin-right: 8px;"></i>
                                                <strong>Type:</strong> <?= $type ?>
                                            </p>
                                        </div>
                                        
                                        <div style="margin-bottom: 15px;">
                                            <p style="margin: 5px 0; color: #1e1e1e;">
                                                <i class="fa fa-map-marker-alt" style="color: #f5a425; margin-right: 8px;"></i>
                                                <strong>Lieu:</strong> <?= $lieu ?>
                                            </p>
                                        </div>
                                        
                                        <div style="margin-bottom: 15px; display: flex; gap: 15px; flex-wrap: wrap;">
                                            <div style="flex: 1; min-width: 120px;">
                                                <p style="margin: 5px 0; color: #1e1e1e; font-size: 12px;">
                                                    <i class="fa fa-clock" style="color: #f5a425; margin-right: 5px;"></i>
                                                    <strong>Début:</strong><br>
                                                    <?= $dateDebut ?>
                                                </p>
                                            </div>
                                            <div style="flex: 1; min-width: 120px;">
                                                <p style="margin: 5px 0; color: #1e1e1e; font-size: 12px;">
                                                    <i class="fa fa-clock" style="color: #f5a425; margin-right: 5px;"></i>
                                                    <strong>Fin:</strong><br>
                                                    <?= $dateFin ?>
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($description)): ?>
                                        <p style="margin-bottom: 20px; color: #7a7a7a; line-height: 1.6;"><?= $description ?></p>
                                        <?php endif; ?>
                                        
                                        <div style="display: flex; gap: 10px; margin-top: 25px;">
                                            <div class="text-button-pay" style="flex: 1;">
                                                <a href="<?= $baseUrl ?>/view/frontoffice/index.php?event_id=<?= $eventId ?>#events" style="color: #f5a425; font-weight: 700; text-transform: uppercase; font-size: 13px;">
                                                    Voir détails <i class="fa fa-angle-double-right"></i>
                                                </a>
                                            </div>
                                            <a href="<?= $baseUrl ?>/view/frontoffice/index.php?event_id=<?= $eventId ?>&reserve=1#reservations" 
                                               style="background-color: #f5a425; color: #fff; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: 700; text-transform: uppercase; font-size: 12px; transition: background-color 0.3s;"
                                               onmouseover="this.style.backgroundColor='#e5941f'" 
                                               onmouseout="this.style.backgroundColor='#f5a425'">
                                                <i class="fa fa-ticket"></i> Réserver
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-md-12">
                        <div style="text-align: center; padding: 60px 20px; background-color: rgba(255,255,255,0.1); border-radius: 8px; border: 2px solid rgba(250,250,250,0.1);">
                            <i class="fa fa-calendar-times" style="font-size: 48px; color: rgba(255,255,255,0.5); margin-bottom: 20px;"></i>
                            <p style="color: #fff; font-size: 18px; margin: 0;">Aucun événement trouvé.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <div class="row mt-4" id="eventsPagination" style="display: <?= $totalPages > 1 ? 'block' : 'none' ?>;">
                <div class="col-md-12">
                    <nav>
                        <ul class="pagination justify-content-center" id="paginationList">
                            <li class="page-item <?= $pageNum <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="javascript:void(0)" onclick="searchEvents('<?= htmlspecialchars($searchTerm, ENT_QUOTES) ?>', 1)">
                                    <i class="fa fa-angle-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item <?= $pageNum <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="javascript:void(0)" onclick="searchEvents('<?= htmlspecialchars($searchTerm, ENT_QUOTES) ?>', <?= $pageNum - 1 ?>)">
                                    <i class="fa fa-angle-left"></i>
                                </a>
                            </li>
                            
                            <?php for ($i = max(1, $pageNum - 2); $i <= min($pageNum + 2, $totalPages); $i++): ?>
                                <li class="page-item <?= $i == $pageNum ? 'active' : '' ?>">
                                    <a class="page-link" href="javascript:void(0)" onclick="searchEvents('<?= htmlspecialchars($searchTerm, ENT_QUOTES) ?>', <?= $i ?>)">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= $pageNum >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="javascript:void(0)" onclick="searchEvents('<?= htmlspecialchars($searchTerm, ENT_QUOTES) ?>', <?= $pageNum + 1 ?>)">
                                    <i class="fa fa-angle-right"></i>
                                </a>
                            </li>
                            <li class="page-item <?= $pageNum >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="javascript:void(0)" onclick="searchEvents('<?= htmlspecialchars($searchTerm, ENT_QUOTES) ?>', <?= $totalPages ?>)">
                                    <i class="fa fa-angle-double-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Reservations Section -->
    <section class="section courses" id="reservations" data-section="section5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-heading">
                        <h2><?= isset($reservationToEdit) ? 'Modifier la réservation' : 'Réserver un Événement' ?></h2>
                    </div>
                </div>
            </div>
            
            <!-- Reservation Form -->
            <div class="row justify-content-center">
                <div class="col-md-10 col-lg-8">
                    <div class="item" style="background-color: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0px 5px 15px rgba(0,0,0,0.2);">
                        <div class="down-content" style="padding: 40px;">
                            <div style="text-align: center; margin-bottom: 30px;">
                                <div style="width: 70px; height: 70px; background-color: #f5a425; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                                    <i class="fa fa-ticket-alt" style="color: #fff; font-size: 30px;"></i>
                                </div>
                                <h3 style="color: #1e1e1e; margin: 0; text-transform: uppercase; font-weight: 700;">
                                    <?= isset($reservationToEdit) ? 'Modifier la réservation' : 'Réserver un Événement' ?>
                                </h3>
                            </div>
                            
                            <?php if (isset($_GET['error'])): ?>
                                <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #dc3545;">
                                    <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($_GET['error']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($successMessage): ?>
                                <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                                    <i class="fa fa-check-circle"></i> <?= htmlspecialchars($successMessage) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php $isEditMode = isset($reservationToEdit); ?>
                            <form method="POST" action="" class="row g-4" id="reservationForm">
                                <input type="hidden" name="action" value="<?= $isEditMode ? 'reservation_update' : 'reservation_create' ?>">
                                <?php if ($isEditMode): ?>
                                    <input type="hidden" name="id" value="<?= $reservationToEdit['id'] ?>">
                                <?php endif; ?>

                                <div class="col-md-6">
                                    <label class="form-label" style="color: #1e1e1e; font-weight: 600; margin-bottom: 8px; display: block;">
                                        <i class="fa fa-calendar" style="color: #f5a425; margin-right: 5px;"></i>Événement
                                    </label>
                                    <select name="evenement_id" class="form-select" required id="evenementSelect" 
                                            style="border: 2px solid #e0e0e0; padding: 12px; border-radius: 5px; transition: border-color 0.3s;"
                                            onfocus="this.style.borderColor='#f5a425'" 
                                            onblur="this.style.borderColor='#e0e0e0'">
                                        <option value="">-- Choisir un événement --</option>
                                        <?php foreach($allEvenements as $e): ?>
                                            <option value="<?= $e['id'] ?>" 
                                                <?= ($isEditMode && $reservationToEdit['evenement_id'] == $e['id']) || (isset($selectedEvent) && $selectedEvent['id'] == $e['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($e['nom_evenement']) ?> (<?= htmlspecialchars($e['type_evenement']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" style="color: #1e1e1e; font-weight: 600; margin-bottom: 8px; display: block;">
                                        <i class="fa fa-user" style="color: #f5a425; margin-right: 5px;"></i>Nom complet
                                    </label>
                                    <input name="nom" class="form-control" 
                                           value="<?= $isEditMode ? htmlspecialchars($reservationToEdit['nom']) : '' ?>" 
                                           required
                                           style="border: 2px solid #e0e0e0; padding: 12px; border-radius: 5px; transition: border-color 0.3s;"
                                           onfocus="this.style.borderColor='#f5a425'" 
                                           onblur="this.style.borderColor='#e0e0e0'">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" style="color: #1e1e1e; font-weight: 600; margin-bottom: 8px; display: block;">
                                        <i class="fa fa-phone" style="color: #f5a425; margin-right: 5px;"></i>Téléphone
                                    </label>
                                    <input name="tel" class="form-control" 
                                           value="<?= $isEditMode ? htmlspecialchars($reservationToEdit['tel']) : '' ?>" 
                                           placeholder="+21612345678" 
                                           required
                                           style="border: 2px solid #e0e0e0; padding: 12px; border-radius: 5px; transition: border-color 0.3s;"
                                           onfocus="this.style.borderColor='#f5a425'" 
                                           onblur="this.style.borderColor='#e0e0e0'">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" style="color: #1e1e1e; font-weight: 600; margin-bottom: 8px; display: block;">
                                        <i class="fa fa-map-marker-alt" style="color: #f5a425; margin-right: 5px;"></i>Lieu
                                    </label>
                                    <input name="lieu" class="form-control" 
                                           value="<?= $isEditMode ? htmlspecialchars($reservationToEdit['lieu']) : '' ?>" 
                                           required
                                           style="border: 2px solid #e0e0e0; padding: 12px; border-radius: 5px; transition: border-color 0.3s;"
                                           onfocus="this.style.borderColor='#f5a425'" 
                                           onblur="this.style.borderColor='#e0e0e0'">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" style="color: #1e1e1e; font-weight: 600; margin-bottom: 8px; display: block;">
                                        <i class="fa fa-birthday-cake" style="color: #f5a425; margin-right: 5px;"></i>Date de naissance
                                    </label>
                                    <input type="date" name="date_naissance" class="form-control" 
                                           value="<?= $isEditMode ? htmlspecialchars($reservationToEdit['date_naissance']) : '' ?>" 
                                           required
                                           style="border: 2px solid #e0e0e0; padding: 12px; border-radius: 5px; transition: border-color 0.3s;"
                                           onfocus="this.style.borderColor='#f5a425'" 
                                           onblur="this.style.borderColor='#e0e0e0'">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" style="color: #1e1e1e; font-weight: 600; margin-bottom: 8px; display: block;">
                                        <i class="fa fa-book" style="color: #f5a425; margin-right: 5px;"></i>Softskills / Technologies
                                    </label>
                                    <select name="softskills" class="form-select" required
                                            style="border: 2px solid #e0e0e0; padding: 12px; border-radius: 5px; transition: border-color 0.3s;"
                                            onfocus="this.style.borderColor='#f5a425'" 
                                            onblur="this.style.borderColor='#e0e0e0'">
                                        <option value="">-- Choisir --</option>
                                        <?php foreach($softskillsList as $s): ?>
                                            <option value="<?= $s ?>" 
                                                <?= ($isEditMode && $reservationToEdit['softskills'] == $s) ? 'selected' : '' ?>>
                                                <?= $s ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" style="color: #1e1e1e; font-weight: 600; margin-bottom: 8px; display: block;">
                                        <i class="fa fa-envelope" style="color: #f5a425; margin-right: 5px;"></i>Email (optionnel)
                                    </label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?= $isEditMode ? htmlspecialchars($reservationToEdit['email'] ?? '') : '' ?>"
                                           style="border: 2px solid #e0e0e0; padding: 12px; border-radius: 5px; transition: border-color 0.3s;"
                                           onfocus="this.style.borderColor='#f5a425'" 
                                           onblur="this.style.borderColor='#e0e0e0'">
                                </div>

                                <div class="col-12" style="margin-top: 20px; text-align: center;">
                                    <button type="submit" 
                                            style="background-color: #f5a425; color: #fff; border: none; padding: 15px 40px; border-radius: 5px; font-weight: 700; text-transform: uppercase; font-size: 14px; transition: background-color 0.3s; margin-right: 10px;"
                                            onmouseover="this.style.backgroundColor='#e5941f'" 
                                            onmouseout="this.style.backgroundColor='#f5a425'">
                                        <i class="fa fa-check"></i> <?= $isEditMode ? 'Mettre à jour' : 'Réserver' ?>
                                    </button>
                                    <?php if ($isEditMode): ?>
                                        <a href="<?= $baseUrl ?>/view/frontoffice/index.php#reservations" 
                                           style="background-color: #6c757d; color: #fff; border: none; padding: 15px 40px; border-radius: 5px; font-weight: 700; text-transform: uppercase; font-size: 14px; text-decoration: none; display: inline-block;">
                                            <i class="fa fa-times"></i> Annuler
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Reservations List -->
            <div class="row mt-5">
                <div class="col-md-12">
                    <div class="section-heading">
                        <h2>Mes Réservations</h2>
                    </div>
                </div>
                
                <!-- Search Bar for Reservations -->
                <div class="col-md-12 mb-4">
                    <form id="reservationSearchForm" class="row g-3 justify-content-center">
                        <div class="col-md-6 col-lg-5">
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f5a425; color: #fff; border: none;">
                                    <i class="fa fa-search"></i>
                                </span>
                                <input type="text" id="reservationSearchInput" class="form-control" 
                                       placeholder="Rechercher dans mes réservations..." 
                                       style="border: 2px solid #e0e0e0; padding: 12px; border-radius: 5px;">
                            </div>
                        </div>
                        <div class="col-md-auto">
                            <button type="button" id="reservationSearchBtn" class="btn" style="background-color: #f5a425; color: #fff; border: none; padding: 10px 25px; font-weight: 700; text-transform: uppercase;">
                                <span id="reservationSearchBtnText">Rechercher</span>
                                <span id="reservationSearchBtnLoader" style="display: none;">
                                    <i class="fa fa-spinner fa-spin"></i>
                                </span>
                            </button>
                        </div>
                        <div class="col-md-auto" id="resetReservationSearchBtn" style="display: none;">
                            <button type="button" id="resetReservationSearch" class="btn" style="background-color: transparent; color: #1e1e1e; border: 2px solid #e0e0e0; padding: 10px 25px;">
                                <i class="fa fa-undo"></i> Réinitialiser
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="col-md-12">
                    <div class="item" style="background-color: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0px 5px 15px rgba(0,0,0,0.2);">
                        <div class="down-content" style="padding: 40px;">
                            <div id="reservationsContainer">
                                <?php if (!empty($reservations)): ?>
                                    <div class="table-responsive">
                                        <table style="width: 100%; border-collapse: collapse;" id="reservationsTable">
                                            <thead>
                                            <tr style="background-color: #f5a425; color: #fff;">
                                                <th style="padding: 15px; text-align: left; font-weight: 700; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;">
                                                    <i class="fa fa-user"></i> Nom
                                                </th>
                                                <th style="padding: 15px; text-align: left; font-weight: 700; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;">
                                                    <i class="fa fa-phone"></i> Téléphone
                                                </th>
                                                <th style="padding: 15px; text-align: left; font-weight: 700; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;">
                                                    <i class="fa fa-map-marker-alt"></i> Lieu
                                                </th>
                                                <th style="padding: 15px; text-align: left; font-weight: 700; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;">
                                                    <i class="fa fa-birthday-cake"></i> Date Naiss.
                                                </th>
                                                <th style="padding: 15px; text-align: left; font-weight: 700; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;">
                                                    <i class="fa fa-book"></i> Softskills
                                                </th>
                                                <th style="padding: 15px; text-align: left; font-weight: 700; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;">
                                                    <i class="fa fa-calendar"></i> Événement
                                                </th>
                                                <th style="padding: 15px; text-align: center; font-weight: 700; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;">
                                                    <i class="fa fa-cog"></i> Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($reservations as $index => $r): ?>
                                                <tr style="border-bottom: 1px solid #e0e0e0; transition: background-color 0.3s;"
                                                    onmouseover="this.style.backgroundColor='#f8f9fa'" 
                                                    onmouseout="this.style.backgroundColor='#fff'">
                                                    <td style="padding: 15px; color: #1e1e1e; font-weight: 600;">
                                                        <?= htmlspecialchars($r["nom"]) ?>
                                                    </td>
                                                    <td style="padding: 15px; color: #1e1e1e;">
                                                        <?= htmlspecialchars($r["tel"]) ?>
                                                    </td>
                                                    <td style="padding: 15px; color: #1e1e1e;">
                                                        <?= htmlspecialchars($r["lieu"]) ?>
                                                    </td>
                                                    <td style="padding: 15px; color: #1e1e1e;">
                                                        <?= htmlspecialchars($r["date_naissance"]) ?>
                                                    </td>
                                                    <td style="padding: 15px;">
                                                        <span style="background-color: #f5a425; color: #fff; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                                            <?= htmlspecialchars($r["softskills"]) ?>
                                                        </span>
                                                    </td>
                                                    <td style="padding: 15px; color: #1e1e1e;">
                                                        <strong><?= htmlspecialchars($r["evenement_nom"]) ?></strong>
                                                        <br><small style="color: #7a7a7a;"><?= htmlspecialchars($r["evenement_type"]) ?></small>
                                                    </td>
                                                    <td style="padding: 15px; text-align: center;">
                                                        <a href="<?= $baseUrl ?>/view/frontoffice/index.php?edit_id=<?= $r['id'] ?>#reservations" 
                                                           style="background-color: #ffc107; color: #fff; padding: 8px 12px; border-radius: 5px; text-decoration: none; margin-right: 5px; display: inline-block; transition: background-color 0.3s;"
                                                           onmouseover="this.style.backgroundColor='#e0a800'" 
                                                           onmouseout="this.style.backgroundColor='#ffc107'"
                                                           title="Modifier">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                        <a href="<?= $baseUrl ?>/index.php?action=reservation_delete&id=<?= $r['id'] ?>" 
                                                           onclick="return confirm('Supprimer cette réservation ?')"
                                                           style="background-color: #dc3545; color: #fff; padding: 8px 12px; border-radius: 5px; text-decoration: none; display: inline-block; transition: background-color 0.3s;"
                                                           onmouseover="this.style.backgroundColor='#c82333'" 
                                                           onmouseout="this.style.backgroundColor='#dc3545'"
                                                           title="Supprimer">
                                                            <i class="fa fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div style="text-align: center; padding: 60px 20px;">
                                    <i class="fa fa-ticket-alt" style="font-size: 48px; color: #e0e0e0; margin-bottom: 20px;"></i>
                                    <p style="color: #7a7a7a; font-size: 18px; margin: 0;">Aucune réservation pour le moment.</p>
                                    <p style="color: #7a7a7a; font-size: 14px; margin-top: 10px;">Utilisez le formulaire ci-dessus pour faire votre première réservation.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Reclamations Section -->
    <section class="section courses" id="reclamations" data-section="section6">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-heading">
                        <h2>Mes Réclamations</h2>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Form Section -->
                <div class="col-md-6">
                    <div class="item" style="background-color: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0px 5px 15px rgba(0,0,0,0.2);">
                        <div class="down-content" style="padding: 40px;">
                            <div style="text-align: center; margin-bottom: 30px;">
                                <div style="width: 70px; height: 70px; background-color: #f5a425; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                                    <i class="fa fa-exclamation-circle" style="color: #fff; font-size: 30px;"></i>
                                </div>
                                <h3 style="color: #1e1e1e; margin: 0; text-transform: uppercase; font-weight: 700;">
                                    Nouvelle Réclamation
                                </h3>
                            </div>
                            
                            <?php if ($reclamationSuccessMessage): ?>
                                <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                                    <i class="fa fa-check-circle"></i> <?= htmlspecialchars($reclamationSuccessMessage) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($reclamationErrorMessage): ?>
                                <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #dc3545;">
                                    <i class="fa fa-exclamation-circle"></i> <?= $reclamationErrorMessage ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="<?= $baseUrl ?>/index.php?action=reclamation_submit" id="reclamationForm">
                                <div style="margin-bottom: 20px;">
                                    <label style="display: block; margin-bottom: 8px; color: #1e1e1e; font-weight: 600;">
                                        Nom complet <span style="color: #dc3545;">*</span>
                                    </label>
                                    <input type="text" name="nom" class="form-control" 
                                           value="<?= htmlspecialchars(trim(($currentUser['firstname'] ?? '') . ' ' . ($currentUser['lastname'] ?? ''))) ?>"
                                           required pattern="[A-Za-zÀ-ÿ\s]{3,50}" 
                                           title="3 à 50 caractères, lettres uniquement"
                                           style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 5px;">
                                </div>
                                
                                <div style="margin-bottom: 20px;">
                                    <label style="display: block; margin-bottom: 8px; color: #1e1e1e; font-weight: 600;">
                                        Email <span style="color: #dc3545;">*</span>
                                    </label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>"
                                           required
                                           style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 5px;">
                                </div>
                                
                                <div style="margin-bottom: 20px;">
                                    <label style="display: block; margin-bottom: 8px; color: #1e1e1e; font-weight: 600;">
                                        Téléphone <span style="color: #dc3545;">*</span>
                                    </label>
                                    <input type="tel" name="telephone" class="form-control" 
                                           placeholder="8 chiffres" maxlength="8" 
                                           pattern="[0-9]{8}" required
                                           style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 5px;">
                                </div>
                                
                                <div style="margin-bottom: 20px;">
                                    <label style="display: block; margin-bottom: 8px; color: #1e1e1e; font-weight: 600;">
                                        Type de réclamation <span style="color: #dc3545;">*</span>
                                    </label>
                                    <select name="type" class="form-control" required
                                            style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 5px;">
                                        <option value="">-- Sélectionnez --</option>
                                        <option value="Service">Service client</option>
                                        <option value="Produit">Qualité du produit</option>
                                        <option value="Livraison">Problème de livraison</option>
                                        <option value="Facturation">Erreur de facturation</option>
                                        <option value="Technique">Problème technique</option>
                                        <option value="Autre">Autre</option>
                                    </select>
                                </div>
                                
                                <div style="margin-bottom: 20px;">
                                    <label style="display: block; margin-bottom: 8px; color: #1e1e1e; font-weight: 600;">
                                        Priorité <span style="color: #dc3545;">*</span>
                                    </label>
                                    <select name="priorite" class="form-control" required
                                            style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 5px;">
                                        <option value="">-- Sélectionnez --</option>
                                        <option value="Basse">🟢 Basse - Peut attendre</option>
                                        <option value="Moyenne">🟡 Moyenne - Important</option>
                                        <option value="Haute">🔴 Haute - Urgent</option>
                                    </select>
                                </div>
                                
                                <div style="margin-bottom: 25px;">
                                    <label style="display: block; margin-bottom: 8px; color: #1e1e1e; font-weight: 600;">
                                        Description détaillée <span style="color: #dc3545;">*</span>
                                    </label>
                                    <textarea name="description" class="form-control" rows="6" 
                                              placeholder="Décrivez votre réclamation en détail (minimum 20 caractères)..." 
                                              required minlength="20"
                                              style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 5px; resize: vertical;"></textarea>
                                </div>
                                
                                <button type="submit" style="background-color: #f5a425; color: #fff; padding: 15px 30px; border: none; border-radius: 5px; font-weight: 700; text-transform: uppercase; font-size: 14px; cursor: pointer; width: 100%; transition: background-color 0.3s;"
                                        onmouseover="this.style.backgroundColor='#e5941f'" 
                                        onmouseout="this.style.backgroundColor='#f5a425'">
                                    <i class="fa fa-paper-plane"></i> Envoyer la réclamation
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- List Section -->
                <div class="col-md-6">
                    <div class="item" style="background-color: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0px 5px 15px rgba(0,0,0,0.2);">
                        <div class="down-content" style="padding: 40px;">
                            <h3 style="color: #1e1e1e; margin-bottom: 25px; text-transform: uppercase; font-weight: 700;">
                                <i class="fa fa-list"></i> Historique
                            </h3>
                            
                            <?php if (empty($userReclamations)): ?>
                                <div style="text-align: center; padding: 60px 20px;">
                                    <i class="fa fa-inbox" style="font-size: 48px; color: #e0e0e0; margin-bottom: 20px;"></i>
                                    <p style="color: #7a7a7a; font-size: 18px; margin: 0;">Aucune réclamation</p>
                                    <p style="color: #7a7a7a; font-size: 14px; margin-top: 10px;">Vous n'avez pas encore soumis de réclamation.</p>
                                </div>
                            <?php else: ?>
                                <div style="max-height: 600px; overflow-y: auto;">
                                    <?php foreach ($userReclamations as $rec): ?>
                                        <div style="border-bottom: 1px solid #e0e0e0; padding: 20px 0;">
                                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                                <div>
                                                    <strong style="color: #1e1e1e;">#<?= $rec['id'] ?></strong> - 
                                                    <span style="color: #7a7a7a;"><?= htmlspecialchars($rec['type']) ?></span>
                                                </div>
                                                <div>
                                                    <?php
                                                    $statusClass = 'secondary';
                                                    if ($rec['statut'] === 'En cours') $statusClass = 'info';
                                                    if ($rec['statut'] === 'Répondu') $statusClass = 'success';
                                                    ?>
                                                    <span style="background-color: <?= $statusClass === 'secondary' ? '#6c757d' : ($statusClass === 'info' ? '#17a2b8' : '#28a745') ?>; color: #fff; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                                        <?= htmlspecialchars($rec['statut']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div style="margin-bottom: 10px;">
                                                <?php
                                                $priorityColor = '#28a745';
                                                if ($rec['priorite'] === 'Moyenne') $priorityColor = '#ffc107';
                                                if ($rec['priorite'] === 'Haute') $priorityColor = '#dc3545';
                                                ?>
                                                <span style="background-color: <?= $priorityColor ?>; color: #fff; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-right: 10px;">
                                                    <?= htmlspecialchars($rec['priorite']) ?>
                                                </span>
                                                <small style="color: #7a7a7a;">
                                                    <i class="fa fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($rec['date_creation'])) ?>
                                                </small>
                                            </div>
                                            
                                            <p style="color: #7a7a7a; font-size: 14px; margin-bottom: 15px;">
                                                <?= htmlspecialchars(mb_substr($rec['description'], 0, 100)) ?><?= mb_strlen($rec['description']) > 100 ? '...' : '' ?>
                                            </p>
                                            
                                            <?php if ($rec['nb_reponses'] > 0): ?>
                                                <div style="background-color: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 5px; margin-bottom: 10px; font-size: 13px;">
                                                    <i class="fa fa-reply"></i> <?= $rec['nb_reponses'] ?> réponse(s) disponible(s)
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div style="display: flex; gap: 10px;">
                                                <button onclick="viewReclamation(<?= $rec['id'] ?>)" 
                                                        style="background-color: #f5a425; color: #fff; padding: 8px 15px; border: none; border-radius: 5px; font-size: 13px; cursor: pointer; transition: background-color 0.3s;"
                                                        onmouseover="this.style.backgroundColor='#e5941f'" 
                                                        onmouseout="this.style.backgroundColor='#f5a425'">
                                                    <i class="fa fa-eye"></i> Voir
                                                </button>
                                                <?php if ($rec['statut'] === 'En attente'): ?>
                                                <button onclick="deleteReclamation(<?= $rec['id'] ?>)" 
                                                        style="background-color: #dc3545; color: #fff; padding: 8px 15px; border: none; border-radius: 5px; font-size: 13px; cursor: pointer; transition: background-color 0.3s;"
                                                        onmouseover="this.style.backgroundColor='#c82333'" 
                                                        onmouseout="this.style.backgroundColor='#dc3545'">
                                                    <i class="fa fa-trash"></i> Supprimer
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Associations Section -->
    <section class="section courses" id="associations" data-section="section7">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-heading">
                        <h2>Associations</h2>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <?php if (empty($associations)): ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <p>Aucune association active disponible pour le moment.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($associations as $association): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="item" style="background-color: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0px 5px 15px rgba(0,0,0,0.2);">
                                <div class="down-content" style="padding: 30px;">
                                    <h4 style="color: #1e1e1e; margin-bottom: 15px;"><?= htmlspecialchars($association['name']) ?></h4>
                                    <p style="color: #7a7a7a; margin-bottom: 10px;">
                                        <span class="badge bg-primary"><?= htmlspecialchars($association['category'] ?? 'N/A') ?></span>
                                    </p>
                                    <p style="color: #7a7a7a; margin-bottom: 15px;">
                                        <?= htmlspecialchars(substr($association['description'] ?? '', 0, 100)) ?>
                                        <?= strlen($association['description'] ?? '') > 100 ? '...' : '' ?>
                                    </p>
                                    <div style="margin-bottom: 15px; font-size: 14px; color: #666;">
                                        <p style="margin: 5px 0;"><i class="fa fa-map-marker" style="color: #f5a425;"></i> <?= htmlspecialchars($association['address'] ?? 'N/A') ?></p>
                                        <p style="margin: 5px 0;"><i class="fa fa-phone" style="color: #f5a425;"></i> <?= htmlspecialchars($association['phone'] ?? 'N/A') ?></p>
                                        <p style="margin: 5px 0;"><i class="fa fa-envelope" style="color: #f5a425;"></i> <?= htmlspecialchars($association['email'] ?? 'N/A') ?></p>
                                    </div>
                                    <div style="display: flex; gap: 10px;">
                                        <a href="<?= $baseUrl ?>/index.php?action=association_show&id=<?= $association['id'] ?>" 
                                           class="btn btn-sm btn-primary" style="flex: 1;">
                                            Voir détails
                                        </a>
                                        <?php if (in_array($association['id'], $userAssociationIds)): ?>
                                            <span class="btn btn-sm btn-success" style="flex: 1; pointer-events: none;">
                                                Membre
                                            </span>
                                        <?php else: ?>
                                            <a href="<?= $baseUrl ?>/index.php?action=association_join&id=<?= $association['id'] ?>" 
                                               class="btn btn-sm btn-success" style="flex: 1;">
                                                Rejoindre
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Cotisations Section -->
    <section class="section courses" id="cotisations" data-section="section8">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-heading">
                        <h2>Mes Cotisations</h2>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <?php if (empty($userAssociations)): ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <p>Vous n'êtes membre d'aucune association. <a href="#associations">Rejoignez une association</a> pour payer des cotisations.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="col-md-12">
                        <div class="item" style="background-color: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0px 5px 15px rgba(0,0,0,0.2);">
                            <div class="down-content" style="padding: 30px;">
                                <h4 style="margin-bottom: 20px;">Payer une cotisation</h4>
                                <form method="POST" action="<?= $baseUrl ?>/index.php?action=cotisation_pay">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Association</label>
                                            <select name="association_id" class="form-control" required>
                                                <option value="">Sélectionner une association</option>
                                                <?php foreach ($userAssociations as $assoc): ?>
                                                    <option value="<?= $assoc['id'] ?>"><?= htmlspecialchars($assoc['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Montant</label>
                                            <input type="number" name="amount" class="form-control" placeholder="Montant" step="0.01" min="0" required>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Période</label>
                                            <select name="period" class="form-control" required>
                                                <option value="monthly">Mensuel</option>
                                                <option value="yearly">Annuel</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Méthode</label>
                                            <select name="payment_method" class="form-control" required>
                                                <option value="online">En ligne</option>
                                                <option value="bank_transfer">Virement</option>
                                                <option value="cash">Espèces</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="submit" class="btn btn-primary w-100">Payer</button>
                                        </div>
                                    </div>
                                </form>
                                
                                <?php if (!empty($userCotisations)): ?>
                                    <hr style="margin: 30px 0;">
                                    <h5 style="margin-bottom: 20px;">Historique des cotisations</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Association</th>
                                                    <th>Montant</th>
                                                    <th>Période</th>
                                                    <th>Date</th>
                                                    <th>Statut</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($userCotisations as $cotisation): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($cotisation['association_name'] ?? 'N/A') ?></td>
                                                        <td><?= number_format($cotisation['amount'], 2) ?> TND</td>
                                                        <td><?= htmlspecialchars($cotisation['period'] ?? 'N/A') ?></td>
                                                        <td><?= $cotisation['payment_date'] ? date('d/m/Y', strtotime($cotisation['payment_date'])) : 'N/A' ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= $cotisation['payment_status'] === 'paid' ? 'success' : ($cotisation['payment_status'] === 'pending' ? 'warning' : 'danger') ?>">
                                                                <?= htmlspecialchars(ucfirst($cotisation['payment_status'])) ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="section courses" id="about" data-section="section8">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-heading">
                        <h2>À propos de WAFRA</h2>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="course-item">
                        <div class="down-content">
                            <p class="lead">WAFRA est une plateforme communautaire dédiée à la solidarité et à l'entraide.</p>
                            <p>Notre mission est de connecter les personnes qui souhaitent aider avec celles qui ont besoin d'assistance, en créant un réseau de soutien mutuel au sein de notre communauté.</p>
                            <p>À travers notre plateforme, vous pouvez participer à des événements, faire des dons, rejoindre des associations, et contribuer à des causes qui vous tiennent à cœur.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="section courses" id="mission" data-section="section9">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-heading">
                        <h2>Notre Mission</h2>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="course-item">
                        <div class="down-content">
                            <h4>Objectif Principal</h4>
                            <p>WAFRA vise à créer un écosystème de solidarité où chaque membre de la communauté peut contribuer et bénéficier de l'entraide mutuelle.</p>
                            
                            <h4 class="mt-4">Nos Valeurs</h4>
                            <ul>
                                <li><strong>Solidarité</strong> : Nous croyons en la force de la communauté</li>
                                <li><strong>Transparence</strong> : Toutes nos actions sont transparentes et vérifiables</li>
                                <li><strong>Respect</strong> : Nous respectons la dignité de chaque personne</li>
                                <li><strong>Engagement</strong> : Nous nous engageons à faire une différence positive</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="section courses" id="how-it-works" data-section="section10">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-heading">
                        <h2>Comment ça marche</h2>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="course-item">
                        <div class="down-content text-center">
                            <i class="fa fa-user-plus" style="font-size: 3rem; color: #667eea; margin-bottom: 20px;"></i>
                            <h5>1. Inscrivez-vous</h5>
                            <p>Créez votre compte gratuitement et rejoignez la communauté WAFRA.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="course-item">
                        <div class="down-content text-center">
                            <i class="fa fa-heart" style="font-size: 3rem; color: #e74c3c; margin-bottom: 20px;"></i>
                            <h5>2. Explorez</h5>
                            <p>Découvrez les événements, dons, associations et opportunités disponibles.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="course-item">
                        <div class="down-content text-center">
                            <i class="fa fa-hand-paper" style="font-size: 3rem; color: #28a745; margin-bottom: 20px;"></i>
                            <h5>3. Contribuez</h5>
                            <p>Participez, donnez, ou créez votre propre initiative pour aider les autres.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Help Section -->
    <section class="section courses" id="help" data-section="section11">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-heading">
                        <h2>Centre d'aide / FAQ</h2>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="course-item">
                        <div class="down-content">
                            <h4>Questions fréquentes</h4>
                            <div class="accordion mt-3" id="faqAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                            Comment créer un compte ?
                                        </button>
                                    </h2>
                                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Cliquez sur "Inscription" et remplissez le formulaire avec vos informations. Vous recevrez un email de confirmation.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                            Comment faire un don ?
                                        </button>
                                    </h2>
                                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Accédez à la section "Donations", parcourez les dons disponibles et suivez les instructions pour faire votre don.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                            Comment rejoindre une association ?
                                        </button>
                                    </h2>
                                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Visitez la section "Associations", choisissez une association et cliquez sur "Rejoindre".
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="section courses" id="contact" data-section="section12">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-heading">
                        <h2>Nous contacter</h2>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="course-item">
                        <div class="down-content">
                            <p>Pour toute question, suggestion ou demande d'assistance, n'hésitez pas à nous contacter :</p>
                            <div class="mt-4">
                                <p><i class="fa fa-envelope"></i> <strong>Email :</strong> contact@wafra.com</p>
                                <p><i class="fa fa-phone"></i> <strong>Téléphone :</strong> +33 1 23 45 67 89</p>
                                <p><i class="fa fa-clock"></i> <strong>Horaires :</strong> Lundi - Vendredi, 9h - 18h</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Transparency Section -->
    <section class="section courses" id="transparency" data-section="section14">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-heading">
                        <h2>Transparence</h2>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="course-item">
                        <div class="down-content">
                            <p>WAFRA s'engage à maintenir une transparence totale dans toutes ses opérations.</p>
                            <h4 class="mt-4">Nos engagements</h4>
                            <ul>
                                <li>Tous les dons et contributions sont tracés et vérifiables</li>
                                <li>Les associations sont validées et certifiées</li>
                                <li>Les transactions sont sécurisées et transparentes</li>
                                <li>Les rapports d'activité sont publiés régulièrement</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Privacy Section -->
    <section class="section courses" id="privacy" data-section="section15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-heading">
                        <h2>Politique de confidentialité</h2>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="course-item">
                        <div class="down-content">
                            <p><strong>Dernière mise à jour :</strong> <?= date('d/m/Y') ?></p>
                            <h4 class="mt-4">Collecte des données</h4>
                            <p>WAFRA collecte uniquement les données nécessaires au fonctionnement de la plateforme et à l'amélioration de nos services.</p>
                            <h4 class="mt-4">Utilisation des données</h4>
                            <p>Vos données personnelles sont utilisées pour :</p>
                            <ul>
                                <li>Gérer votre compte et vos interactions</li>
                                <li>Faciliter les transactions et communications</li>
                                <li>Améliorer nos services</li>
                                <li>Respecter nos obligations légales</li>
                            </ul>
                            <h4 class="mt-4">Protection des données</h4>
                            <p>Nous mettons en œuvre des mesures de sécurité appropriées pour protéger vos données personnelles contre tout accès non autorisé, altération, divulgation ou destruction.</p>
                            <h4 class="mt-4">Vos droits</h4>
                            <p>Conformément au RGPD, vous disposez du droit d'accès, de rectification, de suppression et de portabilité de vos données personnelles.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Terms Section -->
    <section class="section courses" id="terms" data-section="section16">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-heading">
                        <h2>Conditions d'utilisation</h2>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="course-item">
                        <div class="down-content">
                            <p><strong>Dernière mise à jour :</strong> <?= date('d/m/Y') ?></p>
                            <h4 class="mt-4">Acceptation des conditions</h4>
                            <p>En utilisant la plateforme WAFRA, vous acceptez d'être lié par ces conditions d'utilisation.</p>
                            <h4 class="mt-4">Utilisation de la plateforme</h4>
                            <p>Vous vous engagez à :</p>
                            <ul>
                                <li>Utiliser la plateforme de manière légale et conforme</li>
                                <li>Respecter les droits des autres utilisateurs</li>
                                <li>Ne pas publier de contenu offensant ou illégal</li>
                                <li>Maintenir la confidentialité de votre compte</li>
                            </ul>
                            <h4 class="mt-4">Responsabilité</h4>
                            <p>WAFRA ne peut être tenu responsable des dommages résultant de l'utilisation ou de l'impossibilité d'utiliser la plateforme.</p>
                            <h4 class="mt-4">Modifications</h4>
                            <p>Nous nous réservons le droit de modifier ces conditions à tout moment. Les modifications entreront en vigueur dès leur publication.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>

<!-- Footer -->
<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <p><i class="fa fa-copyright"></i> Copyright <?= date('Y') ?> Wafra - Tous droits réservés</p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap core JavaScript -->
<script src="<?= $baseUrl ?>/view/frontoffice/vendor/jquery/jquery.min.js"></script>
<script src="<?= $baseUrl ?>/view/frontoffice/vendor/bootstrap/js/bootstrap.min.js"></script>

<script src="<?= $baseUrl ?>/view/frontoffice/assets/js/isotope.min.js"></script>
<script src="<?= $baseUrl ?>/view/frontoffice/assets/js/owl-carousel.js"></script>
<script src="<?= $baseUrl ?>/view/frontoffice/assets/js/lightbox.js"></script>
<script src="<?= $baseUrl ?>/view/frontoffice/assets/js/tabs.js"></script>
<script src="<?= $baseUrl ?>/view/frontoffice/assets/js/video.js"></script>
<script src="<?= $baseUrl ?>/view/frontoffice/assets/js/slick-slider.js"></script>
<script src="<?= $baseUrl ?>/view/frontoffice/assets/js/custom.js"></script>

<script>
    // Define baseUrl globally
    var baseUrl = '<?= $baseUrl ?>';
    
    // Wait for jQuery and DOM to be ready
    $(document).ready(function() {
        console.log('Initializing search functionality...');
    
    // Sidebar navigation active state
    function updateActiveNav() {
        var scrollPos = $(window).scrollTop() + 100;
        $('.section').each(function() {
            var top = $(this).offset().top;
            var bottom = top + $(this).outerHeight();
            var id = $(this).attr('id');
            
            if (scrollPos >= top && scrollPos <= bottom) {
                $('.sidebar-nav a').removeClass('active');
                $('.sidebar-nav a[href="#' + id + '"]').addClass('active');
            }
        });
    }

    // Smooth scroll on sidebar link click
    $('.sidebar-nav a').on('click', function(e) {
        var target = $(this).attr('href');
        // Only prevent default for hash links (internal scrolling)
        // Allow normal navigation for external/page links
        if (target.indexOf('#') === 0) {
            e.preventDefault();
            var targetSection = $(target);
            if (targetSection.length) {
                $('html, body').animate({
                    scrollTop: targetSection.offset().top - 80
                }, 800);
            }
        }
    });

    // Update active nav on scroll
    $(window).on('scroll', updateActiveNav);
    
    // Update on page load
    updateActiveNav();
    
    // Handle hash on page load (for redirects with #reclamations)
    if (window.location.hash) {
        var hash = window.location.hash.substring(1);
        var targetSection = $('#' + hash);
        if (targetSection.length) {
            setTimeout(function() {
                $('html, body').animate({
                    scrollTop: targetSection.offset().top - 80
                }, 800);
            }, 100);
        }
    }
    
    // Mobile menu toggle
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }
    
    // Close sidebar when clicking outside on mobile
    $(document).on('click', function(e) {
        if ($(window).width() <= 768) {
            if (!$(e.target).closest('.sidebar-nav, .mobile-menu-toggle').length) {
                $('#sidebar').removeClass('active');
            }
        }
    });
    
    // Event details modal
    function showEventDetails(id) {
        // Load event details via AJAX or redirect
        window.location.href = '<?= $baseUrl ?>/view/frontoffice/index.php?event_id=' + id + '#events';
    }
    
    // Handle event_id parameter
    <?php if (isset($_GET['event_id'])): ?>
        var eventId = <?= (int)$_GET['event_id'] ?>;
        <?php if (isset($_GET['reserve'])): ?>
            // Scroll to reservations section and pre-select event
            setTimeout(function() {
                $('html, body').animate({
                    scrollTop: $('#reservations').offset().top - 80
                }, 800);
                $('#evenementSelect').val(eventId).trigger('change');
            }, 100);
        <?php else: ?>
            // Scroll to events section
            setTimeout(function() {
                $('html, body').animate({
                    scrollTop: $('#events').offset().top - 80
                }, 800);
            }, 100);
        <?php endif; ?>
    <?php endif; ?>
    
    // Handle edit_id parameter - scroll to reservations
    <?php if (isset($_GET['edit_id'])): ?>
        setTimeout(function() {
            $('html, body').animate({
                scrollTop: $('#reservations').offset().top - 80
            }, 800);
        }, 100);
    <?php endif; ?>
    
    // Dynamic Event Search
    var currentPage = <?= $pageNum ?>;
    var currentSearchTerm = '<?= htmlspecialchars($searchTerm, ENT_QUOTES) ?>';
    var searchTimeout;
    
    // Search function
    function searchEvents(searchTerm, page) {
        page = page || 1;
        currentPage = page;
        currentSearchTerm = searchTerm || '';
        
        // Show loading state
        $('#searchBtnText').hide();
        $('#searchBtnLoader').show();
        $('#eventSearchBtn').prop('disabled', true);
        
        // Show/hide reset button
        if (currentSearchTerm) {
            $('#resetSearchBtn').show();
        } else {
            $('#resetSearchBtn').hide();
        }
        
        // Make AJAX request
        $.ajax({
            url: baseUrl + '/index.php?action=api_search_events',
            method: 'GET',
            data: {
                search: currentSearchTerm,
                page: currentPage,
                per_page: 10
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateEventsDisplay(response);
                    updatePagination(response);
                    // Scroll to events section
                    $('html, body').animate({
                        scrollTop: $('#events').offset().top - 80
                    }, 500);
                } else {
                    showSearchError('Erreur lors de la recherche');
                }
            },
            error: function(xhr, status, error) {
                console.error('Search error:', error);
                showSearchError('Une erreur est survenue lors de la recherche');
            },
            complete: function() {
                // Hide loading state
                $('#searchBtnText').show();
                $('#searchBtnLoader').hide();
                $('#eventSearchBtn').prop('disabled', false);
            }
        });
    }
    
    // Update events display
    function updateEventsDisplay(response) {
        var container = $('#eventsContainer');
        container.empty();
        
        if (response.data && response.data.length > 0) {
            response.data.forEach(function(e) {
                var nom = escapeHtml(e.nom_evenement || 'Non défini');
                var type = escapeHtml(e.type_evenement || 'Non défini');
                var lieu = escapeHtml(e.lieu || 'Non défini');
                var eventId = e.id || 0;
                
                var dateDebut = 'Non définie';
                if (e.date_debut) {
                    var date = new Date(e.date_debut);
                    dateDebut = date.toLocaleDateString('fr-FR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
                
                var dateFin = 'Non définie';
                if (e.date_fin) {
                    var date = new Date(e.date_fin);
                    dateFin = date.toLocaleDateString('fr-FR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
                
                var description = '';
                if (e.description) {
                    description = escapeHtml(e.description.substring(0, 120));
                    if (e.description.length > 120) {
                        description += '...';
                    }
                }
                
                var eventHtml = '<div class="col-md-4 mb-5">' +
                    '<div class="item" style="background-color: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0px 5px 15px rgba(0,0,0,0.2); transition: transform 0.3s;">' +
                    '<div class="down-content" style="padding: 35px;">' +
                    '<div style="display: flex; align-items: center; margin-bottom: 20px;">' +
                    '<div style="width: 50px; height: 50px; background-color: #f5a425; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">' +
                    '<i class="fa fa-calendar" style="color: #fff; font-size: 20px;"></i>' +
                    '</div>' +
                    '<h4 style="margin: 0; flex: 1;">' + nom + '</h4>' +
                    '</div>' +
                    '<div style="margin-bottom: 15px; padding: 12px; background-color: #f8f9fa; border-radius: 5px;">' +
                    '<p style="margin: 5px 0; color: #1e1e1e;">' +
                    '<i class="fa fa-tag" style="color: #f5a425; margin-right: 8px;"></i>' +
                    '<strong>Type:</strong> ' + type +
                    '</p>' +
                    '</div>' +
                    '<div style="margin-bottom: 15px;">' +
                    '<p style="margin: 5px 0; color: #1e1e1e;">' +
                    '<i class="fa fa-map-marker-alt" style="color: #f5a425; margin-right: 8px;"></i>' +
                    '<strong>Lieu:</strong> ' + lieu +
                    '</p>' +
                    '</div>' +
                    '<div style="margin-bottom: 15px; display: flex; gap: 15px; flex-wrap: wrap;">' +
                    '<div style="flex: 1; min-width: 120px;">' +
                    '<p style="margin: 5px 0; color: #1e1e1e; font-size: 12px;">' +
                    '<i class="fa fa-clock" style="color: #f5a425; margin-right: 5px;"></i>' +
                    '<strong>Début:</strong><br>' + dateDebut +
                    '</p>' +
                    '</div>' +
                    '<div style="flex: 1; min-width: 120px;">' +
                    '<p style="margin: 5px 0; color: #1e1e1e; font-size: 12px;">' +
                    '<i class="fa fa-clock" style="color: #f5a425; margin-right: 5px;"></i>' +
                    '<strong>Fin:</strong><br>' + dateFin +
                    '</p>' +
                    '</div>' +
                    '</div>';
                
                if (description) {
                    eventHtml += '<p style="margin-bottom: 20px; color: #7a7a7a; line-height: 1.6;">' + description + '</p>';
                }
                
                eventHtml += '<div style="display: flex; gap: 10px; margin-top: 25px;">' +
                    '<div class="text-button-pay" style="flex: 1;">' +
                    '<a href="' + baseUrl + '/view/frontoffice/index.php?event_id=' + eventId + '#events" style="color: #f5a425; font-weight: 700; text-transform: uppercase; font-size: 13px;">' +
                    'Voir détails <i class="fa fa-angle-double-right"></i>' +
                    '</a>' +
                    '</div>' +
                    '<a href="' + baseUrl + '/view/frontoffice/index.php?event_id=' + eventId + '&reserve=1#reservations" ' +
                    'style="background-color: #f5a425; color: #fff; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: 700; text-transform: uppercase; font-size: 12px; transition: background-color 0.3s;" ' +
                    'onmouseover="this.style.backgroundColor=\'#e5941f\'" ' +
                    'onmouseout="this.style.backgroundColor=\'#f5a425\'">' +
                    '<i class="fa fa-ticket"></i> Réserver' +
                    '</a>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                
                container.append(eventHtml);
            });
        } else {
            container.html(
                '<div class="col-md-12">' +
                '<div style="text-align: center; padding: 60px 20px; background-color: rgba(255,255,255,0.1); border-radius: 8px; border: 2px solid rgba(250,250,250,0.1);">' +
                '<i class="fa fa-calendar-times" style="font-size: 48px; color: rgba(255,255,255,0.5); margin-bottom: 20px;"></i>' +
                '<p style="color: #fff; font-size: 18px; margin: 0;">Aucun événement trouvé.</p>' +
                '</div>' +
                '</div>'
            );
        }
    }
    
    // Update pagination
    function updatePagination(response) {
        var paginationContainer = $('#eventsPagination');
        var paginationList = $('#paginationList');
        
        if (response.totalPages > 1) {
            paginationContainer.show();
            paginationList.empty();
            
            // First page
            paginationList.append(
                '<li class="page-item ' + (response.page <= 1 ? 'disabled' : '') + '">' +
                '<a class="page-link" href="javascript:void(0)" onclick="searchEvents(\'' + escapeHtml(currentSearchTerm) + '\', 1)">' +
                '<i class="fa fa-angle-double-left"></i>' +
                '</a>' +
                '</li>'
            );
            
            // Previous page
            paginationList.append(
                '<li class="page-item ' + (response.page <= 1 ? 'disabled' : '') + '">' +
                '<a class="page-link" href="javascript:void(0)" onclick="searchEvents(\'' + escapeHtml(currentSearchTerm) + '\', ' + (response.page - 1) + ')">' +
                '<i class="fa fa-angle-left"></i>' +
                '</a>' +
                '</li>'
            );
            
            // Page numbers
            var startPage = Math.max(1, response.page - 2);
            var endPage = Math.min(response.page + 2, response.totalPages);
            
            for (var i = startPage; i <= endPage; i++) {
                paginationList.append(
                    '<li class="page-item ' + (i == response.page ? 'active' : '') + '">' +
                    '<a class="page-link" href="javascript:void(0)" onclick="searchEvents(\'' + escapeHtml(currentSearchTerm) + '\', ' + i + ')">' + i + '</a>' +
                    '</li>'
                );
            }
            
            // Next page
            paginationList.append(
                '<li class="page-item ' + (response.page >= response.totalPages ? 'disabled' : '') + '">' +
                '<a class="page-link" href="javascript:void(0)" onclick="searchEvents(\'' + escapeHtml(currentSearchTerm) + '\', ' + (response.page + 1) + ')">' +
                '<i class="fa fa-angle-right"></i>' +
                '</a>' +
                '</li>'
            );
            
            // Last page
            paginationList.append(
                '<li class="page-item ' + (response.page >= response.totalPages ? 'disabled' : '') + '">' +
                '<a class="page-link" href="javascript:void(0)" onclick="searchEvents(\'' + escapeHtml(currentSearchTerm) + '\', ' + response.totalPages + ')">' +
                '<i class="fa fa-angle-double-right"></i>' +
                '</a>' +
                '</li>'
            );
        } else {
            paginationContainer.hide();
        }
    }
    
    // Show search error
    function showSearchError(message) {
        var container = $('#eventsContainer');
        container.html(
            '<div class="col-md-12">' +
            '<div class="alert alert-danger" role="alert">' +
            '<i class="fa fa-exclamation-circle"></i> ' + message +
            '</div>' +
            '</div>'
        );
    }
    
    // Escape HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text ? text.replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
    }
    
    // Event listeners
    $('#eventSearchBtn').on('click', function() {
        var searchTerm = $('#eventSearchInput').val().trim();
        searchEvents(searchTerm, 1);
    });
    
    $('#resetSearch').on('click', function() {
        $('#eventSearchInput').val('');
        searchEvents('', 1);
    });
    
    // Search on Enter key
    $('#eventSearchInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#eventSearchBtn').click();
        }
    });
    
    // Real-time search with debounce (optional - uncomment to enable)
    /*
    $('#eventSearchInput').on('input', function() {
        clearTimeout(searchTimeout);
        var searchTerm = $(this).val().trim();
        searchTimeout = setTimeout(function() {
            searchEvents(searchTerm, 1);
        }, 500); // Wait 500ms after user stops typing
    });
    */
    
    // Dynamic Reservation Search
    var currentReservationSearchTerm = '';
    
    // Search reservations function
    function searchReservations(searchTerm) {
        currentReservationSearchTerm = searchTerm || '';
        
        // Show loading state
        $('#reservationSearchBtnText').hide();
        $('#reservationSearchBtnLoader').show();
        $('#reservationSearchBtn').prop('disabled', true);
        
        // Show/hide reset button
        if (currentReservationSearchTerm) {
            $('#resetReservationSearchBtn').show();
        } else {
            $('#resetReservationSearchBtn').hide();
        }
        
        // Make AJAX request
        $.ajax({
            url: baseUrl + '/index.php?action=api_search_reservations',
            method: 'GET',
            data: {
                search: currentReservationSearchTerm
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateReservationsDisplay(response);
                } else {
                    showReservationSearchError('Erreur lors de la recherche');
                }
            },
            error: function(xhr, status, error) {
                console.error('Reservation search error:', error, xhr.responseText);
                showReservationSearchError('Une erreur est survenue lors de la recherche');
            },
            complete: function() {
                // Hide loading state
                $('#reservationSearchBtnText').show();
                $('#reservationSearchBtnLoader').hide();
                $('#reservationSearchBtn').prop('disabled', false);
            }
        });
    }
    
    // Update reservations display
    function updateReservationsDisplay(response) {
        var container = $('#reservationsContainer');
        container.empty();
        
        if (response.data && response.data.length > 0) {
            var tableHtml = '<div class="table-responsive">' +
                '<table style="width: 100%; border-collapse: collapse;" id="reservationsTable">' +
                '<thead>' +
                '<tr style="background-color: #f5a425; color: #fff;">' +
                '<th style="padding: 15px; text-align: left; font-weight: 700; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;">' +
                '<i class="fa fa-user"></i> Nom' +
                '</th>' +
                '<th style="padding: 15px; text-align: left; font-weight: 700; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;">' +
                '<i class="fa fa-phone"></i> Téléphone' +
                '</th>' +
                '<th style="padding: 15px; text-align: left; font-weight: 700; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;">' +
                '<i class="fa fa-map-marker-alt"></i> Lieu' +
                '</th>' +
                '<th style="padding: 15px; text-align: left; font-weight: 700; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;">' +
                '<i class="fa fa-birthday-cake"></i> Date Naiss.' +
                '</th>' +
                '<th style="padding: 15px; text-align: left; font-weight: 700; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;">' +
                '<i class="fa fa-book"></i> Softskills' +
                '</th>' +
                '<th style="padding: 15px; text-align: left; font-weight: 700; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;">' +
                '<i class="fa fa-calendar"></i> Événement' +
                '</th>' +
                '<th style="padding: 15px; text-align: center; font-weight: 700; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;">' +
                '<i class="fa fa-cog"></i> Actions' +
                '</th>' +
                '</tr>' +
                '</thead>' +
                '<tbody>';
            
            response.data.forEach(function(r) {
                var nom = escapeHtml(r.nom || '');
                var tel = escapeHtml(r.tel || '');
                var lieu = escapeHtml(r.lieu || '');
                var dateNaissance = escapeHtml(r.date_naissance || '');
                var softskills = escapeHtml(r.softskills || '');
                var evenementNom = escapeHtml(r.evenement_nom || 'Non défini');
                var evenementType = escapeHtml(r.evenement_type || '');
                var reservationId = r.id || 0;
                
                tableHtml += '<tr style="border-bottom: 1px solid #e0e0e0; transition: background-color 0.3s;" ' +
                    'onmouseover="this.style.backgroundColor=\'#f8f9fa\'" ' +
                    'onmouseout="this.style.backgroundColor=\'#fff\'">' +
                    '<td style="padding: 15px; color: #1e1e1e; font-weight: 600;">' + nom + '</td>' +
                    '<td style="padding: 15px; color: #1e1e1e;">' + tel + '</td>' +
                    '<td style="padding: 15px; color: #1e1e1e;">' + lieu + '</td>' +
                    '<td style="padding: 15px; color: #1e1e1e;">' + dateNaissance + '</td>' +
                    '<td style="padding: 15px;">' +
                    '<span style="background-color: #f5a425; color: #fff; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">' +
                    softskills +
                    '</span>' +
                    '</td>' +
                    '<td style="padding: 15px; color: #1e1e1e;">' +
                    '<strong>' + evenementNom + '</strong>' +
                    (evenementType ? '<br><small style="color: #7a7a7a;">' + evenementType + '</small>' : '') +
                    '</td>' +
                    '<td style="padding: 15px; text-align: center;">' +
                    '<a href="' + baseUrl + '/view/frontoffice/index.php?edit_id=' + reservationId + '#reservations" ' +
                    'style="background-color: #ffc107; color: #fff; padding: 8px 12px; border-radius: 5px; text-decoration: none; margin-right: 5px; display: inline-block; transition: background-color 0.3s;" ' +
                    'onmouseover="this.style.backgroundColor=\'#e0a800\'" ' +
                    'onmouseout="this.style.backgroundColor=\'#ffc107\'" ' +
                    'title="Modifier">' +
                    '<i class="fa fa-edit"></i>' +
                    '</a>' +
                    '<a href="' + baseUrl + '/index.php?action=reservation_delete&id=' + reservationId + '" ' +
                    'onclick="return confirm(\'Supprimer cette réservation ?\')" ' +
                    'style="background-color: #dc3545; color: #fff; padding: 8px 12px; border-radius: 5px; text-decoration: none; display: inline-block; transition: background-color 0.3s;" ' +
                    'onmouseover="this.style.backgroundColor=\'#c82333\'" ' +
                    'onmouseout="this.style.backgroundColor=\'#dc3545\'" ' +
                    'title="Supprimer">' +
                    '<i class="fa fa-trash"></i>' +
                    '</a>' +
                    '</td>' +
                    '</tr>';
            });
            
            tableHtml += '</tbody></table></div>';
            container.html(tableHtml);
        } else {
            container.html(
                '<div style="text-align: center; padding: 60px 20px;">' +
                '<i class="fa fa-ticket-alt" style="font-size: 48px; color: #e0e0e0; margin-bottom: 20px;"></i>' +
                '<p style="color: #7a7a7a; font-size: 18px; margin: 0;">Aucune réservation trouvée.</p>' +
                (currentReservationSearchTerm ? 
                    '<p style="color: #7a7a7a; font-size: 14px; margin-top: 10px;">Essayez avec d\'autres mots-clés.</p>' :
                    '<p style="color: #7a7a7a; font-size: 14px; margin-top: 10px;">Utilisez le formulaire ci-dessus pour faire votre première réservation.</p>') +
                '</div>'
            );
        }
    }
    
    // Show reservation search error
    function showReservationSearchError(message) {
        var container = $('#reservationsContainer');
        container.html(
            '<div class="alert alert-danger" role="alert" style="margin: 20px;">' +
            '<i class="fa fa-exclamation-circle"></i> ' + message +
            '</div>'
        );
    }
    
    // Event listeners for reservation search
    $('#reservationSearchBtn').on('click', function() {
        var searchTerm = $('#reservationSearchInput').val().trim();
        searchReservations(searchTerm);
    });
    
    $('#resetReservationSearch').on('click', function() {
        $('#reservationSearchInput').val('');
        searchReservations('');
    });
    
    // Search on Enter key
    $('#reservationSearchInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#reservationSearchBtn').click();
        }
    });
    
        // Verify functions are available
        if (typeof searchEvents === 'function') {
            console.log('Event search function is available');
        } else {
            console.error('Event search function not found');
        }
        
        if (typeof searchReservations === 'function') {
            console.log('Reservation search function is available');
        } else {
            console.error('Reservation search function not found');
        }
    }); // End of $(document).ready

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
            if (document.getElementById('notificationDropdown').style.display !== 'none') {
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

    // Reclamation view function
    function viewReclamation(id) {
        const modal = new bootstrap.Modal(document.getElementById('reclamationDetailsModal'));
        const contentDiv = document.getElementById('reclamationDetailsContent');
        
        // Show loading state
        contentDiv.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-2">Chargement des détails...</p>
            </div>
        `;
        
        modal.show();
        
        // Fetch reclamation details
        fetch('<?= $baseUrl ?>/index.php?action=reclamation_get_details&id=' + id)
            .then(async response => {
                const responseText = await response.text();
                if (!response.ok) {
                    // Try to parse error message from JSON
                    try {
                        const errorData = JSON.parse(responseText);
                        throw new Error(errorData.error || 'HTTP error! status: ' + response.status);
                    } catch (e) {
                        throw new Error('HTTP error! status: ' + response.status + ' - ' + responseText.substring(0, 100));
                    }
                }
                return JSON.parse(responseText);
            })
            .then(data => {
                if (data.success) {
                    const rec = data.reclamation;
                    const responses = data.responses || [];
                    
                    // Status badge
                    let statusClass = 'secondary';
                    let statusColor = '#6c757d';
                    if (rec.statut === 'En cours') {
                        statusClass = 'info';
                        statusColor = '#17a2b8';
                    } else if (rec.statut === 'Répondu') {
                        statusClass = 'success';
                        statusColor = '#28a745';
                    }
                    
                    // Priority badge
                    let priorityColor = '#28a745';
                    if (rec.priorite === 'Moyenne') priorityColor = '#ffc107';
                    if (rec.priorite === 'Haute') priorityColor = '#dc3545';
                    
                    let html = `
                        <div class="reclamation-detail-view">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4>Réclamation #${rec.id}</h4>
                                <div>
                                    <span class="badge" style="background-color: ${statusColor}; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px;">
                                        ${rec.statut}
                                    </span>
                                    <span class="badge ms-2" style="background-color: ${priorityColor}; color: ${rec.priorite === 'Moyenne' ? '#000' : 'white'}; padding: 6px 12px; border-radius: 20px; font-size: 12px;">
                                        ${rec.priorite}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Nom:</strong> ${escapeHtml(rec.nom)}
                                </div>
                                <div class="col-md-6">
                                    <strong>Email:</strong> ${escapeHtml(rec.email)}
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Téléphone:</strong> ${escapeHtml(rec.telephone)}
                                </div>
                                <div class="col-md-6">
                                    <strong>Type:</strong> ${escapeHtml(rec.type)}
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Date de création:</strong> 
                                ${new Date(rec.date_creation).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
                            </div>
                            
                            <div class="mb-3">
                                <strong>Description:</strong>
                                <div class="mt-2 p-3" style="background: #f8f9fa; border-radius: 8px; white-space: pre-wrap;">
                                    ${escapeHtml(rec.description)}
                                </div>
                            </div>
                    `;
                    
                    if (responses.length > 0) {
                        html += `
                            <div class="response-section mt-4" style="background: #e7f3ff; border-left: 4px solid #17a2b8; padding: 20px; border-radius: 8px;">
                                <h5><i class="fa fa-reply"></i> Réponse(s) de l'administration</h5>
                        `;
                        
                        responses.forEach(response => {
                            const responseDate = new Date(response.date_reponse).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                            html += `
                                <div class="mb-3" style="background: white; padding: 15px; border-radius: 5px; margin-top: 15px;">
                                    <p style="white-space: pre-wrap; margin-bottom: 10px;">${escapeHtml(response.message)}</p>
                                    <small class="text-muted">
                                        Répondu le ${responseDate}
                                        ${response.admin_firstname || response.admin_lastname ? ' par ' + escapeHtml((response.admin_firstname || '') + ' ' + (response.admin_lastname || '')) : ''}
                                    </small>
                                </div>
                            `;
                        });
                        
                        html += `</div>`;
                    }
                    
                    html += `</div>`;
                    contentDiv.innerHTML = html;
                } else {
                    contentDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-circle"></i> ${data.error || 'Erreur lors du chargement des détails'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading reclamation:', error);
                console.error('Error details:', error.message);
                let errorMsg = error.message || 'Erreur de connexion au serveur';
                // If error message is too long, truncate it
                if (errorMsg.length > 200) {
                    errorMsg = errorMsg.substring(0, 200) + '...';
                }
                contentDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-circle"></i> Erreur de connexion au serveur
                        <br><small>${escapeHtml(errorMsg)}</small>
                        <br><small style="color: #666; font-size: 11px;">Vérifiez la console du navigateur (F12) pour plus de détails.</small>
                    </div>
                `;
            });
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
    }
    
    // Delete Reclamation Function - Global scope
    function deleteReclamation(id) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette réclamation ?')) {
            return;
        }
        
        fetch('<?= $baseUrl ?>/index.php?action=reclamation_delete&id=' + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Réclamation supprimée avec succès');
                location.reload();
            } else {
                alert('Erreur: ' + (data.error || 'Erreur lors de la suppression'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur de connexion');
        });
    }
</script>

<!-- Enhanced Top Bar JavaScript -->
<script src="<?= $baseUrl ?>/view/frontoffice/assets/js/topbar-enhanced.js"></script>

<!-- Event Details Modal -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de l'événement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="eventDetailsContent">
                <p>Chargement...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <a href="#" id="reserveEventBtn" class="btn btn-primary">Réserver</a>
            </div>
        </div>
    </div>
</div>

<!-- Reclamation Details Modal -->
<div class="modal fade" id="reclamationDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #f5a425; color: white;">
                <h5 class="modal-title">
                    <i class="fa fa-exclamation-circle"></i> Détails de la réclamation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="reclamationDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2">Chargement des détails...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<?php 
// Pass $baseUrl to chatbot component
$baseUrl = $baseUrl ?? (defined('BASE_URL') ? BASE_URL : '');
include __DIR__ . '/../components/chatbot.php'; 
?>
</body>
</html>
