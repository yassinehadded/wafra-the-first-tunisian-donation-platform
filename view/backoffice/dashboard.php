<?php
// view/backoffice/dashboard.php - Unified Admin Dashboard
// Session is already started in AdminController
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/autoload.php';
require_once __DIR__ . '/../../models/Event.php';
require_once __DIR__ . '/../../models/Reservation.php';

// Check if user is admin
if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
    exit;
}

$baseUrl = BASE_URL;

// Get admin user info from session
$adminFirstName = $_SESSION['firstname'] ?? 'Admin';
$adminLastName = $_SESSION['lastname'] ?? '';
$adminEmail = $_SESSION['email'] ?? '';
$adminUsername = $_SESSION['username'] ?? ($adminFirstName . ' ' . $adminLastName);
$adminInitials = strtoupper(substr($adminFirstName, 0, 1) . substr($adminLastName, 0, 1));

// Get current section and action
$currentSection = $current_section ?? ($_GET['section'] ?? $_POST['section'] ?? 'dashboard');
// Handle admin association/cotisation actions
// Check both $_GET['action'] and $_REQUEST['action'] to handle different routing scenarios
$actionParam = $_GET['action'] ?? $_POST['action'] ?? $_REQUEST['action'] ?? '';
// Also check if section is explicitly set for associations/cotisations (from GET or POST)
if (isset($_GET['section']) && ($_GET['section'] === 'associations' || $_GET['section'] === 'cotisations')) {
    $currentSection = $_GET['section'];
} elseif (isset($_POST['section']) && ($_POST['section'] === 'associations' || $_POST['section'] === 'cotisations')) {
    $currentSection = $_POST['section'];
} elseif (!empty($actionParam)) {
    if (strpos($actionParam, 'admin_association') === 0) {
        $currentSection = 'associations';
    } elseif (strpos($actionParam, 'admin_cotisation') === 0) {
        $currentSection = 'cotisations';
    }
}
// Use section_action to avoid conflict with main action parameter
// Default to 'list' for events and reservations sections if no section_action specified
if (isset($current_action)) {
    $currentAction = $current_action;
} elseif (isset($_GET['section_action'])) {
    $currentAction = $_GET['section_action'];
} elseif ($currentSection === 'events' || $currentSection === 'reservations') {
    // For events and reservations sections, default action is 'list'
    // Don't use $_GET['action'] because it will be 'dashboard' for routing
    $currentAction = 'list';
} else {
    // For dashboard section, use action from URL or default to empty
    $currentAction = $_GET['action'] ?? '';
}

// Initialize variables with defaults
$stats = $stats ?? [
    'total_users' => 0,
    'total_events' => 0,
    'total_reservations' => 0
];
$latestUsers = $latestUsers ?? [];
$allUsers = $allUsers ?? [];
$loginSessions = $loginSessions ?? [];
$eventsByType = $eventsByType ?? [];
$reservationsByMonth = $reservationsByMonth ?? [];
$allReservations = $allReservations ?? [];
$reservationToEdit = $reservationToEdit ?? null;
$allEvenements = $allEvenements ?? [];
$evenements = $evenements ?? [];
$pagination = $pagination ?? [];
$evenement = $evenement ?? null;
$old = $old ?? [];
$settings = $settings ?? [];

// Get session messages
$successMessage = $_SESSION['success'] ?? null;
$errorMessages = $_SESSION['errors'] ?? [];
unset($_SESSION['success'], $_SESSION['errors']);

// ========== HANDLE ASSOCIATIONS POST/DELETE REQUESTS BEFORE HTML OUTPUT ==========
// This must be done BEFORE any HTML is output to allow proper redirects
$showAssociationsCheck = ($currentSection === 'associations' || 
                          (isset($actionParam) && strpos($actionParam, 'admin_association') === 0) ||
                          (isset($_GET['action']) && strpos($_GET['action'], 'admin_association') === 0) ||
                          (isset($_POST['section']) && $_POST['section'] === 'associations'));

if ($showAssociationsCheck) {
    require_once __DIR__ . '/../../controllers/AdminAssociationController.php';
    $assocAction = $actionParam ?? $_GET['action'] ?? $_POST['action'] ?? '';
    $assocSectionAction = $_GET['section_action'] ?? $_POST['section_action'] ?? '';
    $assocId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
    
    // Handle DELETE requests FIRST (before any HTML output)
    if ($assocSectionAction === 'delete' || $assocAction === 'admin_association_delete') {
        // Clear any output buffers before redirect
        while (ob_get_level()) {
            ob_end_clean();
        }
        $adminAssociationController = new AdminAssociationController();
        $adminAssociationController->delete($assocId);
        exit; // delete() will redirect, so exit here
    }
    
    // Handle POST requests for create/update (these will redirect, so exit after)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postAction = $_POST['action'] ?? '';
        $getSectionAction = $_GET['section_action'] ?? '';
        $postSectionAction = $_POST['section_action'] ?? '';
        
        // Check if this is a create request
        $isCreate = ($postAction === 'admin_association_create' || 
                    $getSectionAction === 'create' || 
                    $postSectionAction === 'create' || 
                    $assocSectionAction === 'create');
        
        if ($isCreate) {
            $adminAssociationController = new AdminAssociationController();
            $adminAssociationController->create();
            exit; // create() will redirect, so exit here
        } 
        
        // Check if this is an update request
        $isUpdate = ($postAction === 'admin_association_update' || 
                    $getSectionAction === 'update' || 
                    $postSectionAction === 'update' || 
                    $assocSectionAction === 'update');
        
        if ($isUpdate) {
            $adminAssociationController = new AdminAssociationController();
            $adminAssociationController->update($assocId);
            exit; // update() will redirect, so exit here
        }
    }
}

// ========== HANDLE COTISATIONS POST/DELETE REQUESTS BEFORE HTML OUTPUT ==========
// This must be done BEFORE any HTML is output to allow proper redirects
$showCotisationsCheck = ($currentSection === 'cotisations' || 
                         (isset($actionParam) && strpos($actionParam, 'admin_cotisation') === 0) ||
                         (isset($_GET['action']) && strpos($_GET['action'], 'admin_cotisation') === 0) ||
                         (isset($_POST['section']) && $_POST['section'] === 'cotisations'));

if ($showCotisationsCheck) {
    require_once __DIR__ . '/../../controllers/AdminCotisationController.php';
    $cotisAction = $actionParam ?? $_GET['action'] ?? $_POST['action'] ?? '';
    $cotisSectionAction = $_GET['section_action'] ?? $_POST['section_action'] ?? '';
    $cotisId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
    
    // Handle DELETE requests FIRST (before any HTML output)
    if ($cotisSectionAction === 'delete' || $cotisAction === 'admin_cotisation_delete') {
        // Clear any output buffers before redirect
        while (ob_get_level()) {
            ob_end_clean();
        }
        $adminCotisationController = new AdminCotisationController();
        $adminCotisationController->delete($cotisId);
        exit; // delete() will redirect, so exit here
    }
    
    // Handle POST/GET requests for validate (these will redirect, so exit after)
    if ($cotisSectionAction === 'validate' || $cotisAction === 'admin_cotisation_validate') {
        // Clear any output buffers before redirect
        while (ob_get_level()) {
            ob_end_clean();
        }
        $adminCotisationController = new AdminCotisationController();
        $adminCotisationController->validatePayment($cotisId);
        exit; // validatePayment() will redirect, so exit here
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php 
        $titles = [
            'dashboard' => 'Dashboard',
            'events' => 'Événements',
            'reservations' => 'Réservations',
            'donations' => 'Donations'
        ];
        echo ($titles[$currentSection] ?? 'Dashboard') . ' - Mazer Admin Dashboard';
    ?></title>
    
    <!-- Flatpickr for date inputs -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <!-- QR Code library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <!-- assets -->
    <link rel="shortcut icon" href="<?= $baseUrl ?>/view/backoffice/assets/images/favicon.svg" type="image/x-icon">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/backoffice/assets/css/app.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/backoffice/assets/css/app-dark.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- FontAwesome for chatbot icons -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/fontawesome.css">
    <!-- Chatbot CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/components/chatbot.css">
    
    <!-- Bootstrap CSS for dropdown -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Ensure Bootstrap JS is available -->
    <script>
        // Check if bootstrap is loaded
        window.bootstrapLoaded = false;
    </script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        /* Admin Avatar Styles */
        .admin-avatar-dropdown {
            margin-left: auto;
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .avatar-initials {
            line-height: 1;
        }
        
        .admin-name {
            color: var(--bs-body-color);
            font-weight: 500;
            margin-right: 8px;
        }
        
        .admin-avatar-dropdown .dropdown-toggle {
            border: none;
            background: transparent;
            color: inherit;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background-color 0.2s;
        }
        
        .admin-avatar-dropdown .dropdown-toggle:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .admin-avatar-dropdown .dropdown-toggle:focus {
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .admin-avatar-dropdown .dropdown-menu {
            min-width: 250px;
            margin-top: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .admin-avatar-dropdown .dropdown-header {
            padding: 1rem;
        }
        
        .admin-avatar-dropdown .dropdown-header .admin-avatar {
            width: 48px;
            height: 48px;
            font-size: 18px;
        }
        
        .admin-avatar-dropdown .dropdown-item {
            padding: 0.75rem 1rem;
            transition: background-color 0.2s;
        }
        
        .admin-avatar-dropdown .dropdown-item:hover {
            background-color: rgba(102, 126, 234, 0.1);
        }
        
        .admin-avatar-dropdown .dropdown-item i {
            width: 20px;
            text-align: center;
        }
        
        /* Dark mode support */
        body.dark-mode .admin-name {
            color: var(--bs-body-color);
        }
        
        body.dark-mode .admin-avatar-dropdown .dropdown-toggle:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        @media (max-width: 768px) {
            .admin-avatar-dropdown .dropdown-menu {
                min-width: 200px;
            }
        }
    </style>
</head>
<body>
<div id="app">
    <div id="sidebar" class="active">
        <div class="sidebar-wrapper active">
            <div class="sidebar-header">
                <div class="d-flex align-items-center justify-content-between" style="gap: 12px; padding: 15px;">
                    <div class="logo" style="flex: 0 0 auto;">
                        <a href="<?= $baseUrl ?>/index.php?action=dashboard" style="display: flex; align-items: center;">
                            <img src="<?= $baseUrl ?>/view/backoffice/assets/images/favicon.svg" alt="Logo" style="width: 32px; height: 32px; margin-right: 8px;">
                            <span class="wafra-title" style="color: #87CEEB; font-size: 20px; font-weight: bold; letter-spacing: 1px;">WAFRA</span>
                        </a>
                    </div>
                    <div class="admin-avatar-sidebar" style="flex: 0 0 auto;">
                        <div class="admin-avatar" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; font-size: 14px; border: 2px solid #87CEEB;">
                            <span class="avatar-initials"><?= htmlspecialchars($adminInitials) ?></span>
                        </div>
                    </div>
                    <div class="sidebar-toggler x" style="flex: 0 0 auto;">
                        <a href="#" class="sidebar-hide d-xl-none d-block">
                            <i class="bi bi-x bi-middle"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="sidebar-menu">
                <ul class="menu">
                    <li class="sidebar-title">Menu</li>

                    <li class="sidebar-item <?= $currentSection === 'dashboard' ? 'active' : '' ?>">
                        <a href="<?= $baseUrl ?>/index.php?action=dashboard" class="sidebar-link">
                            <i class="bi bi-grid-fill"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <li class="sidebar-item <?= $currentSection === 'events' && $currentAction === 'create' ? 'active' : '' ?>">
                        <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=events&section_action=create" class="sidebar-link">
                            <i class="bi bi-calendar-plus"></i>
                            <span>Créer Événement</span>
                        </a>
                    </li>

                    <li class="sidebar-item <?= $currentSection === 'events' && ($currentAction === 'list' || empty($currentAction)) ? 'active' : '' ?>">
                        <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=events" class="sidebar-link">
                            <i class="bi bi-calendar-event"></i>
                            <span>Liste des Événements</span>
                        </a>
                    </li>

                    <li class="sidebar-item <?= $currentSection === 'reservations' ? 'active' : '' ?>">
                        <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=reservations" class="sidebar-link">
                            <i class="bi bi-list-check"></i>
                            <span>Liste Réservations</span>
                        </a>
                    </li>

                    <li class="sidebar-item <?= $currentSection === 'users' ? 'active' : '' ?>">
                        <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=users" class="sidebar-link">
                            <i class="bi bi-people"></i>
                            <span>Utilisateurs</span>
                        </a>
                    </li>

                    <li class="sidebar-item <?= $currentSection === 'login_sessions' ? 'active' : '' ?>">
                        <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=login_sessions" class="sidebar-link">
                            <i class="bi bi-clock-history"></i>
                            <span>Sessions de Connexion</span>
                        </a>
                    </li>

                    <li class="sidebar-item <?= $currentSection === 'reported_posts' ? 'active' : '' ?>">
                        <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=reported_posts" class="sidebar-link">
                            <i class="bi bi-flag-fill" style="color: #e74c3c;"></i>
                            <span>Posts Signalés
                                <?php if (isset($pendingCount) && $pendingCount > 0): ?>
                                    <span class="badge bg-danger ms-2"><?= $pendingCount ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                    </li>

                    <li class="sidebar-item <?= $currentSection === 'comment_reports' ? 'active' : '' ?>">
                        <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=comment_reports" class="sidebar-link">
                            <i class="bi bi-chat-left-text" style="color: #e74c3c;"></i>
                            <span>Commentaires Signalés
                                <?php 
                                $commentPendingCount = 0;
                                if ($currentSection === 'comment_reports' && isset($pendingCount)) {
                                    $commentPendingCount = $pendingCount;
                                } else {
                                    try {
                                        require_once __DIR__ . '/../../models/CommentReport.php';
                                        require_once __DIR__ . '/../../config/Database.php';
                                        $pdo = Database::connect();
                                        $commentReportModel = new CommentReport($pdo);
                                        $commentPendingCount = $commentReportModel->getPendingCount();
                                    } catch (Exception $e) {
                                        $commentPendingCount = 0;
                                    }
                                }
                                if ($commentPendingCount > 0): ?>
                                    <span class="badge bg-danger ms-2"><?= $commentPendingCount ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                    </li>

                    <li class="sidebar-item <?= $currentSection === 'reclamations' ? 'active' : '' ?>">
                        <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=reclamations" class="sidebar-link">
                            <i class="bi bi-exclamation-triangle-fill" style="color: #f5a425;"></i>
                            <span>Réclamations</span>
                        </a>
                    </li>

                    <li class="sidebar-item <?= $currentSection === 'donations' ? 'active' : '' ?>">
                        <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=donations" class="sidebar-link">
                            <i class="bi bi-gift-fill" style="color: #f5a425;"></i>
                            <span>Donations</span>
                        </a>
                    </li>

                    <li class="sidebar-item <?= ($currentSection === 'associations' || (isset($actionParam) && strpos($actionParam, 'admin_association') === 0)) ? 'active' : '' ?>">
                        <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=associations#associations-section" class="sidebar-link">
                            <i class="bi bi-building" style="color: #667eea;"></i>
                            <span>Associations</span>
                        </a>
                    </li>

                    <li class="sidebar-item <?= ($currentSection === 'cotisations' || (isset($actionParam) && strpos($actionParam, 'admin_cotisation') === 0)) ? 'active' : '' ?>">
                        <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=cotisations#cotisations-section" class="sidebar-link">
                            <i class="bi bi-cash-coin" style="color: #28a745;"></i>
                            <span>Cotisations</span>
                        </a>
                    </li>

                    <li class="sidebar-item <?= $currentSection === 'settings' ? 'active' : '' ?>">
                        <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=settings" class="sidebar-link">
                            <i class="bi bi-gear-fill"></i>
                            <span>Settings</span>
                        </a>
                    </li>

                    <!-- Divider -->
                    <li class="sidebar-title mt-4" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem; margin-top: 1rem;"></li>

                    <!-- About WAFRA Section -->
                    <li class="sidebar-title" style="font-size: 0.75rem; opacity: 0.7; text-transform: uppercase; letter-spacing: 0.5px;">À propos de WAFRA</li>
                    <li class="sidebar-item">
                        <a href="<?= $baseUrl ?>/index.php?action=page&name=about" class="sidebar-link" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                            <i class="bi bi-info-circle" style="font-size: 0.875rem;"></i>
                            <span>À propos</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="<?= $baseUrl ?>/index.php?action=page&name=mission" class="sidebar-link" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                            <i class="bi bi-bullseye" style="font-size: 0.875rem;"></i>
                            <span>Notre Mission</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="<?= $baseUrl ?>/index.php?action=page&name=how-it-works" class="sidebar-link" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                            <i class="bi bi-question-circle" style="font-size: 0.875rem;"></i>
                            <span>Comment ça marche</span>
                        </a>
                    </li>

                    <!-- Support & Help Section -->
                    <li class="sidebar-title mt-3" style="font-size: 0.75rem; opacity: 0.7; text-transform: uppercase; letter-spacing: 0.5px;">Support & Aide</li>
                    <li class="sidebar-item">
                        <a href="<?= $baseUrl ?>/index.php?action=page&name=help" class="sidebar-link" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                            <i class="bi bi-question-circle" style="font-size: 0.875rem;"></i>
                            <span>Centre d'aide / FAQ</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="<?= $baseUrl ?>/index.php?action=page&name=contact" class="sidebar-link" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                            <i class="bi bi-envelope" style="font-size: 0.875rem;"></i>
                            <span>Nous contacter</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="<?= $baseUrl ?>/index.php?action=page&name=report" class="sidebar-link" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                            <i class="bi bi-exclamation-triangle" style="font-size: 0.875rem;"></i>
                            <span>Signaler un problème</span>
                        </a>
                    </li>

                    <!-- Trust & Legal Section -->
                    <li class="sidebar-title mt-3" style="font-size: 0.75rem; opacity: 0.7; text-transform: uppercase; letter-spacing: 0.5px;">Confiance & Légal</li>
                    <li class="sidebar-item">
                        <a href="<?= $baseUrl ?>/index.php?action=page&name=transparency" class="sidebar-link" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                            <i class="bi bi-eye" style="font-size: 0.875rem;"></i>
                            <span>Transparence</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="<?= $baseUrl ?>/index.php?action=page&name=privacy" class="sidebar-link" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                            <i class="bi bi-shield-lock" style="font-size: 0.875rem;"></i>
                            <span>Politique de confidentialité</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="<?= $baseUrl ?>/index.php?action=page&name=terms" class="sidebar-link" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                            <i class="bi bi-file-text" style="font-size: 0.875rem;"></i>
                            <span>Conditions d'utilisation</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <!-- =============== END SIDEBAR =============== -->

    <!-- ================= MAIN ================= -->
    <div id="main">
        <header class="mb-3">
            <div class="d-flex justify-content-between align-items-center w-100">
            <a href="#" class="burger-btn d-block d-xl-none">
                <i class="bi bi-list"></i>
            </a>
                
                <!-- Admin Notifications Bell -->
                <div class="notification-container" style="position: relative; margin-right: 15px;">
                    <button class="btn btn-link notification-bell" id="adminNotificationBell" onclick="toggleAdminNotificationDropdown()" style="position: relative; padding: 8px 12px; color: var(--bs-body-color); text-decoration: none;">
                        <i class="bi bi-bell" style="font-size: 20px;"></i>
                        <span class="notification-badge" id="adminNotificationBadge" style="position: absolute; top: 5px; right: 5px; background: #e74c3c; color: #fff; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; display: none; align-items: center; justify-content: center; font-weight: bold;">0</span>
                    </button>
                    <div class="notification-dropdown" id="adminNotificationDropdown" style="display: none; position: absolute; top: 100%; right: 0; background: #fff; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); width: 400px; max-height: 500px; overflow-y: auto; z-index: 1050; margin-top: 10px;">
                        <div class="notification-header" style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f8f9fa;">
                            <h5 style="margin: 0; font-weight: 600;">Notifications Admin</h5>
                            <button onclick="markAllAdminNotificationsRead()" style="background: none; border: none; color: #667eea; cursor: pointer; font-size: 12px;">Tout marquer comme lu</button>
                        </div>
                        <div class="notification-list" id="adminNotificationList" style="max-height: 400px; overflow-y: auto;">
                            <div class="text-center" style="padding: 20px; color: #999;">
                                <i class="bi bi-arrow-repeat spin"></i> Chargement...
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Admin Avatar Dropdown -->
                <div class="admin-avatar-dropdown">
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle d-flex align-items-center text-decoration-none p-0" type="button" id="adminDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="admin-avatar me-2">
                                <span class="avatar-initials"><?= htmlspecialchars($adminInitials) ?></span>
                            </div>
                            <span class="admin-name d-none d-md-inline"><?= htmlspecialchars(trim($adminUsername)) ?></span>
                            <i class="bi bi-chevron-down ms-2"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                            <li>
                                <h6 class="dropdown-header">
                                    <div class="d-flex align-items-center">
                                        <div class="admin-avatar me-2">
                                            <span class="avatar-initials"><?= htmlspecialchars($adminInitials) ?></span>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars(trim($adminUsername)) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($adminEmail) ?></small>
                                        </div>
                                    </div>
                                </h6>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?= $baseUrl ?>/view/frontoffice/profile.php">
                                    <i class="bi bi-person me-2"></i> Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= $baseUrl ?>/index.php?action=settings">
                                    <i class="bi bi-gear me-2"></i> Settings
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= $baseUrl ?>/index.php?action=logout">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        <div class="page-heading">
            <h3><?php 
                $headings = [
                    'dashboard' => 'Dashboard',
                    'events' => 'Gestion des Événements',
                    'reservations' => 'Gestion des Réservations',
                    'reported_posts' => 'Posts Signalés',
                    'comment_reports' => 'Commentaires Signalés',
                    'associations' => 'Gestion des Associations',
                    'cotisations' => 'Gestion des Cotisations'
                ];
                echo $headings[$currentSection] ?? 'Dashboard';
            ?></h3>
        </div>

        <div class="page-content">
            <?php
            // Display success/error messages
            if ($successMessage): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" id="successAlert">
                    <?= htmlspecialchars($successMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <script>
                    // Auto-dismiss success message after 5 seconds
                    document.addEventListener('DOMContentLoaded', function() {
                        setTimeout(function() {
                            var alert = document.getElementById('successAlert');
                            if (alert) {
                                var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                                bsAlert.close();
                            }
                        }, 5000);
                    });
                </script>
            <?php endif; ?>
            
            <?php if (!empty($errorMessages)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errorMessages as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php
            // ========== DASHBOARD SECTION ==========
            // Only show dashboard if we're not on associations or cotisations
            $isAssociations = ($currentSection === 'associations' || 
                              (isset($actionParam) && strpos($actionParam, 'admin_association') === 0) ||
                              (isset($_GET['action']) && strpos($_GET['action'], 'admin_association') === 0));
            $isCotisations = ($currentSection === 'cotisations' || 
                              (isset($actionParam) && strpos($actionParam, 'admin_cotisation') === 0) ||
                              (isset($_GET['action']) && strpos($_GET['action'], 'admin_cotisation') === 0));
            
            if ($currentSection === 'dashboard' && !$isAssociations && !$isCotisations): ?>
            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Total Utilisateurs</h6>
                                    <h2 class="mb-0"><?= number_format($stats['total_users'] ?? 0) ?></h2>
                                </div>
                                <div class="avatar avatar-lg bg-primary">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Total Événements</h6>
                                    <h2 class="mb-0"><?= number_format($stats['total_events'] ?? 0) ?></h2>
                                </div>
                                <div class="avatar avatar-lg bg-success">
                                    <i class="bi bi-calendar-event"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Charts Row -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Événements par Type</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="eventsByTypeChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Réservations par Mois</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="reservationsByMonthChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Statistics Charts -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Inscriptions par Mois</h5>
                            <button id="refreshUserStats" class="btn btn-sm btn-primary">
                                <i class="bi bi-arrow-repeat"></i> Actualiser
                            </button>
                        </div>
                        <div class="card-body">
                            <canvas id="userRegistrationsChart" height="220"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Activité de Connexion (30 derniers jours)</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="loginActivityChart" height="220"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Statistics Cards -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Utilisateurs Actifs (30j)</h6>
                                    <h4 class="mb-0" id="stat-active-users">--</h4>
                                </div>
                                <div class="avatar avatar-lg bg-info">
                                    <i class="bi bi-person-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Nouveaux (Aujourd'hui)</h6>
                                    <h4 class="mb-0" id="stat-new-today">--</h4>
                                </div>
                                <div class="avatar avatar-lg bg-success">
                                    <i class="bi bi-person-plus"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Nouveaux (Ce Mois)</h6>
                                    <h4 class="mb-0" id="stat-new-month">--</h4>
                                </div>
                                <div class="avatar avatar-lg bg-warning">
                                    <i class="bi bi-calendar-month"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">En Ligne</h6>
                                    <h4 class="mb-0" id="stat-online">--</h4>
                                </div>
                                <div class="avatar avatar-lg bg-success">
                                    <i class="bi bi-wifi"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest Users Row -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Derniers Utilisateurs</h5>
                            <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=users" class="btn btn-sm btn-primary">
                                Voir tout
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($latestUsers)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>CIN</th>
                                                <th>Nom</th>
                                                <th>Email</th>
                                                <th>Rôle</th>
                                                <th>Réservations</th>
                                                <th>Date d'inscription</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($latestUsers as $user): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($user['cin'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')) ?></td>
                                                    <td><?= htmlspecialchars($user['email'] ?? 'N/A') ?></td>
                                                    <td><span class="badge bg-<?= ($user['role'] ?? 'user') === 'admin' ? 'primary' : 'info' ?>"><?= ucfirst($user['role'] ?? 'user') ?></span></td>
                                                    <td><span class="badge bg-info"><?= $user['reservation_count'] ?? 0 ?></span></td>
                                                    <td><?= !empty($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : 'N/A' ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Aucun utilisateur récent.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Login Sessions Row -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Sessions de Connexion Récentes</h5>
                            <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=login_sessions" class="btn btn-sm btn-primary">
                                Voir tout
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($loginSessions)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Utilisateur</th>
                                                <th>Email</th>
                                                <th>IP Address</th>
                                                <th>Device</th>
                                                <th>Date de Connexion</th>
                                                <th>Date de Déconnexion</th>
                                                <th>Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($loginSessions, 0, 10) as $session): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars(($session['firstname'] ?? '') . ' ' . ($session['lastname'] ?? '')) ?></td>
                                                    <td><?= htmlspecialchars($session['email'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($session['ipAddress'] ?? 'N/A') ?></td>
                                                    <td><small><?= htmlspecialchars(substr($session['device'] ?? 'N/A', 0, 50)) ?></small></td>
                                                    <td><?= !empty($session['loginTime']) ? date('d/m/Y H:i', strtotime($session['loginTime'])) : 'N/A' ?></td>
                                                    <td><?= !empty($session['logoutTime']) ? date('d/m/Y H:i', strtotime($session['logoutTime'])) : '<span class="badge bg-success">En ligne</span>' ?></td>
                                                    <td><?= empty($session['logoutTime']) ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Terminée</span>' ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Aucune session de connexion récente.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des Réservations -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Liste des Réservations</h5>
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#reservationModal" onclick="openReservationModal()">
                                <i class="bi bi-plus-circle"></i> Nouvelle Réservation
                            </button>
                        </div>
                        <div class="card-body">
                            <!-- Search Bar -->
                            <div class="mb-3">
                                <form method="GET" action="<?= $baseUrl ?>/index.php?action=dashboard&section=dashboard" class="row g-2" id="dashboardReservationsSearchForm">
                                    <input type="hidden" name="action" value="dashboard">
                                    <input type="hidden" name="section" value="dashboard">
                                    <div class="col-md-10">
                                        <input type="text" name="search" id="dashboardReservationsSearch" class="form-control search-input" 
                                               placeholder="Rechercher par nom, téléphone, lieu, événement..." 
                                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-search"></i> Rechercher
                                        </button>
                                    </div>
                                    <?php if (!empty($_GET['search'])): ?>
                                    <div class="col-md-12 mt-2">
                                        <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=dashboard" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-x-circle"></i> Réinitialiser
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </form>
                            </div>
                            
                            <?php 
                            // Debug output
                            error_log("Dashboard view: allReservations count = " . count($allReservations ?? []));
                            error_log("Dashboard view: allReservations is_array = " . (is_array($allReservations ?? []) ? 'yes' : 'no'));
                            error_log("Dashboard view: allReservations empty = " . (empty($allReservations) ? 'yes' : 'no'));
                            if (!empty($allReservations) && is_array($allReservations) && count($allReservations) > 0) {
                                error_log("Dashboard view: First reservation: " . json_encode($allReservations[0]));
                            }
                            ?>
                            <?php if (!empty($allReservations) && is_array($allReservations) && count($allReservations) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nom</th>
                                                <th>Téléphone</th>
                                                <th>Lieu</th>
                                                <th>Date Naiss.</th>
                                                <th>Softskills</th>
                                                <th>Événement</th>
                                                <th>Email</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($allReservations as $reservation): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($reservation['id'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($reservation['nom'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($reservation['tel'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($reservation['lieu'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($reservation['date_naissance'] ?? 'N/A') ?></td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            <?= htmlspecialchars($reservation['softskills'] ?? 'N/A') ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($reservation['evenement_nom'] ?? 'N/A') ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?= htmlspecialchars($reservation['evenement_type'] ?? '') ?></small>
                                                    </td>
                                                    <td><?= htmlspecialchars($reservation['email'] ?? '-') ?></td>
                                                    <td>
                                                        <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=reservations&section_action=edit&id=<?= $reservation['id'] ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" 
                                                           class="btn btn-sm btn-warning" 
                                                           title="Modifier">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=reservations&delete_id=<?= $reservation['id'] ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" 
                                                           class="btn btn-sm btn-danger" 
                                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?')"
                                                           title="Supprimer">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3">
                                    <p class="text-muted">
                                        <strong><?= count($allReservations) ?></strong> réservation(s) trouvée(s)
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                                    <p class="text-muted mt-3">
                                        <?php if (!empty($_GET['search'])): ?>
                                            Aucune réservation trouvée pour "<?= htmlspecialchars($_GET['search']) ?>"
                                        <?php else: ?>
                                            Aucune réservation pour le moment.
                                        <?php endif; ?>
                                    </p>
                                    <!-- Debug info -->
                                    <div class="alert alert-warning mt-3" style="font-size: 12px;">
                                        <strong>Debug:</strong> 
                                        allReservations count = <?= count($allReservations ?? []) ?>, 
                                        is_array = <?= is_array($allReservations ?? []) ? 'yes' : 'no' ?>,
                                        type = <?= gettype($allReservations ?? null) ?>
                                        <?php if (isset($allReservations) && is_array($allReservations) && count($allReservations) > 0): ?>
                                            <br>First item: <?= htmlspecialchars(json_encode($allReservations[0])) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Actions Rapides</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=events&section_action=create" class="btn btn-primary">
                                    <i class="bi bi-calendar-plus"></i> Créer un Événement
                                </a>
                                <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=events" class="btn btn-info">
                                    <i class="bi bi-calendar-event"></i> Gérer les Événements
                                </a>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#reservationModal" onclick="openReservationModal()">
                                    <i class="bi bi-ticket-perforated"></i> Nouvelle Réservation
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php endif; ?>
            
            <?php if ($currentSection === 'events'): ?>
            <!-- ========== EVENTS SECTION ========== -->
            <?php if ($currentAction === 'list'): ?>
                <!-- Events List -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5>Liste des Événements</h5>
                                <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=events&section_action=create" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle"></i> Nouvel Événement
                                </a>
                            </div>
                            <div class="card-body">
                                <!-- Search Bar -->
                                <div class="mb-3">
                                    <form method="GET" action="<?= $baseUrl ?>/index.php?action=dashboard&section=events" class="row g-2" id="eventsSearchForm">
                                        <div class="col-md-10">
                                            <input type="text" name="search" id="eventsSearch" class="form-control search-input" 
                                                   placeholder="Rechercher un événement..." 
                                                   value="<?= htmlspecialchars($pagination['searchTerm'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="bi bi-search"></i> Rechercher
                                            </button>
                                        </div>
                                        <?php if (!empty($pagination['searchTerm'])): ?>
                                        <div class="col-md-12 mt-2">
                                            <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=events" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-x-circle"></i> Réinitialiser
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                    </form>
                                </div>
                                
                                <?php if (!empty($evenements) && is_array($evenements) && count($evenements) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover" id="eventsTable">
                                            <thead>
                                                <tr>
                                                    <th>Nom</th>
                                                    <th>Type</th>
                                                    <th>Date Début</th>
                                                    <th>Date Fin</th>
                                                    <th>Lieu</th>
                                                    <th>Statut</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($evenements as $e): ?>
                                                    <?php
                                                    $now = time();
                                                    $start = strtotime($e['date_debut']);
                                                    $end = strtotime($e['date_fin']);
                                                    if ($now < $start) $statut = "<span class='badge bg-success'>À venir</span>";
                                                    elseif ($now > $end) $statut = "<span class='badge bg-secondary'>Terminé</span>";
                                                    else $statut = "<span class='badge bg-warning'>En cours</span>";
                                                    ?>
                                                    <tr>
                                                        <td><strong><?= htmlspecialchars($e['nom_evenement']) ?></strong></td>
                                                        <td><span class="badge bg-info"><?= ucfirst($e['type_evenement']) ?></span></td>
                                                        <td><?= htmlspecialchars($e['date_debut']) ?></td>
                                                        <td><?= htmlspecialchars($e['date_fin']) ?></td>
                                                        <td><?= htmlspecialchars($e['lieu'] ?? 'N/A') ?></td>
                                                        <td><?= $statut ?></td>
                                                        <td>
                                                            <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=events&section_action=view&id=<?= $e['id'] ?>" class="btn btn-sm btn-info">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=events&section_action=edit&id=<?= $e['id'] ?>" class="btn btn-sm btn-warning">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=events&delete_id=<?= $e['id'] ?>&type=event" 
                                                               class="btn btn-sm btn-danger"
                                                               onclick="return confirm('Supprimer cet événement ?')">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Pagination -->
                                    <?php if ($pagination['totalPages'] > 1): ?>
                                    <nav class="mt-3">
                                        <ul class="pagination justify-content-center">
                                            <?php
                                            $queryParams = ['action' => 'dashboard', 'section' => 'events'];
                                            if (!empty($pagination['searchTerm'])) {
                                                $queryParams['search'] = $pagination['searchTerm'];
                                            }
                                            
                                            if ($pagination['currentPage'] > 1):
                                                $queryParams['p'] = 1;
                                            ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= http_build_query($queryParams) ?>">
                                                        <i class="bi bi-chevron-double-left"></i>
                                                    </a>
                                                </li>
                                                <?php $queryParams['p'] = $pagination['currentPage'] - 1; ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= http_build_query($queryParams) ?>">
                                                        <i class="bi bi-chevron-left"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php
                                            $start = max(1, $pagination['currentPage'] - 2);
                                            $end = min($pagination['totalPages'], $pagination['currentPage'] + 2);
                                            
                                            for ($i = $start; $i <= $end; $i++):
                                                $queryParams['p'] = $i;
                                            ?>
                                                <li class="page-item <?= $i === $pagination['currentPage'] ? 'active' : '' ?>">
                                                    <a class="page-link" href="?<?= http_build_query($queryParams) ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($pagination['currentPage'] < $pagination['totalPages']):
                                                $queryParams['p'] = $pagination['currentPage'] + 1;
                                            ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= http_build_query($queryParams) ?>">
                                                        <i class="bi bi-chevron-right"></i>
                                                    </a>
                                                </li>
                                                <?php $queryParams['p'] = $pagination['totalPages']; ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= http_build_query($queryParams) ?>">
                                                        <i class="bi bi-chevron-double-right"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-info text-center">
                                        Aucun événement trouvé.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($currentAction === 'create' || $currentAction === 'edit'): ?>
                <!-- Event Form (Create/Edit) -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5><i class="bi bi-calendar-plus me-2"></i><?= $currentAction === 'edit' ? 'Modifier' : 'Créer' ?> un Événement</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?= $baseUrl ?>/index.php?action=dashboard">
                                    <input type="hidden" name="section" value="events">
                                    <input type="hidden" name="section_action" value="<?= $currentAction === 'edit' ? 'edit' : 'create' ?>">
                                    <?php if ($currentAction === 'edit' && isset($evenement)): ?>
                                        <input type="hidden" name="id" value="<?= $evenement['id'] ?>">
                                    <?php endif; ?>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="form-label">Nom de l'Événement <span class="text-danger">*</span></label>
                                            <input type="text" name="nomEvenement" class="form-control" 
                                                   value="<?= htmlspecialchars($currentAction === 'edit' && isset($evenement) ? $evenement['nom_evenement'] : ($old['nomEvenement'] ?? '')) ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">Type <span class="text-danger">*</span></label>
                                            <select name="typeEvenement" class="form-select" required>
                                                <option value="">-- Choisir --</option>
                                                <?php 
                                                $types = ["conference","seminaire","formation","workshop","concert","festival","sportif","culturel","social","autre"];
                                                foreach ($types as $t): 
                                                    $selected = '';
                                                    if ($currentAction === 'edit' && isset($evenement) && $evenement['type_evenement'] === $t) {
                                                        $selected = 'selected';
                                                    } elseif (isset($old['typeEvenement']) && $old['typeEvenement'] === $t) {
                                                        $selected = 'selected';
                                                    }
                                                ?>
                                                    <option value="<?= $t ?>" <?= $selected ?>><?= ucfirst($t) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <label class="form-label">Date Début <span class="text-danger">*</span></label>
                                            <input type="text" name="dateDebut" id="dateDebut" class="form-control flatpickr-datetime" 
                                                   value="<?= htmlspecialchars($currentAction === 'edit' && isset($evenement) ? $evenement['date_debut'] : ($old['dateDebut'] ?? '')) ?>" required>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <label class="form-label">Date Fin <span class="text-danger">*</span></label>
                                            <input type="text" name="dateFin" id="dateFin" class="form-control flatpickr-datetime" 
                                                   value="<?= htmlspecialchars($currentAction === 'edit' && isset($evenement) ? $evenement['date_fin'] : ($old['dateFin'] ?? '')) ?>" required>
                                        </div>
                                        
                                        <div class="col-md-12">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($currentAction === 'edit' && isset($evenement) ? $evenement['description'] : ($old['description'] ?? '')) ?></textarea>
                                        </div>
                                        
                                        <div class="col-md-12">
                                            <label class="form-label">Lieu</label>
                                            <input type="text" name="lieu" class="form-control" 
                                                   value="<?= htmlspecialchars($currentAction === 'edit' && isset($evenement) ? ($evenement['lieu'] ?? '') : ($old['lieu'] ?? '')) ?>">
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">Latitude</label>
                                            <input type="number" name="latitude" step="0.00000001" class="form-control" 
                                                   value="<?= htmlspecialchars($currentAction === 'edit' && isset($evenement) ? ($evenement['latitude'] ?? '') : ($old['latitude'] ?? '')) ?>"
                                                   min="-90" max="90">
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">Longitude</label>
                                            <input type="number" name="longitude" step="0.00000001" class="form-control" 
                                                   value="<?= htmlspecialchars($currentAction === 'edit' && isset($evenement) ? ($evenement['longitude'] ?? '') : ($old['longitude'] ?? '')) ?>"
                                                   min="-180" max="180">
                                        </div>
                                        
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-check-circle"></i> <?= $currentAction === 'edit' ? 'Mettre à jour' : 'Enregistrer' ?>
                                            </button>
                                            <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=events" class="btn btn-secondary">
                                                <i class="bi bi-x-circle"></i> Annuler
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($currentAction === 'view' && isset($evenement)): ?>
                <!-- Event View -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5><?= htmlspecialchars($evenement['nom_evenement']) ?></h5>
                                <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=events" class="btn btn-light btn-sm">
                                    <i class="bi bi-arrow-left"></i> Retour
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Type:</strong> <span class="badge bg-info"><?= ucfirst($evenement['type_evenement']) ?></span></p>
                                        <p><strong>Date Début:</strong> <?= htmlspecialchars($evenement['date_debut']) ?></p>
                                        <p><strong>Date Fin:</strong> <?= htmlspecialchars($evenement['date_fin']) ?></p>
                                        <p><strong>Lieu:</strong> <?= htmlspecialchars($evenement['lieu'] ?? 'N/A') ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <?php if (!empty($evenement['latitude']) && !empty($evenement['longitude'])): ?>
                                            <p><strong>Coordonnées GPS:</strong></p>
                                            <p>Lat: <?= $evenement['latitude'] ?>, Lng: <?= $evenement['longitude'] ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-12 mt-3">
                                        <p><strong>Description:</strong></p>
                                        <p><?= nl2br(htmlspecialchars($evenement['description'] ?? '')) ?></p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=events&section_action=edit&id=<?= $evenement['id'] ?>" class="btn btn-warning">
                                        <i class="bi bi-pencil"></i> Modifier
                                    </a>
                                    <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=events&delete_id=<?= $evenement['id'] ?>&type=event" 
                                       class="btn btn-danger"
                                       onclick="return confirm('Supprimer cet événement ?')">
                                        <i class="bi bi-trash"></i> Supprimer
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php endif; ?>
            
            <?php if ($currentSection === 'reservations'): ?>
            <!-- ========== RESERVATIONS SECTION ========== -->
            <?php if ($currentAction === 'list'): ?>
                <!-- Reservations List -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5>Liste des Réservations</h5>
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#reservationModal" onclick="openReservationModal()">
                                    <i class="bi bi-plus-circle"></i> Nouvelle Réservation
                                </button>
                            </div>
                            <div class="card-body">
                                <!-- Search Bar -->
                                <div class="mb-3">
                                    <form method="GET" action="<?= $baseUrl ?>/index.php?action=dashboard&section=reservations" class="row g-2" id="reservationsSearchForm">
                                        <div class="col-md-10">
                                            <input type="text" name="search" id="reservationsSearch" class="form-control search-input" 
                                                   placeholder="Rechercher par nom, téléphone, lieu, événement..." 
                                                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="bi bi-search"></i> Rechercher
                                            </button>
                                        </div>
                                        <?php if (!empty($_GET['search'])): ?>
                                        <div class="col-md-12 mt-2">
                                            <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=reservations" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-x-circle"></i> Réinitialiser
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                    </form>
                                </div>
                                
                                <?php if (!empty($allReservations) && is_array($allReservations) && count($allReservations) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="reservationsTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Nom</th>
                                                    <th>Téléphone</th>
                                                    <th>Lieu</th>
                                                    <th>Date Naiss.</th>
                                                    <th>Softskills</th>
                                                    <th>Événement</th>
                                                    <th>Email</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($allReservations as $reservation): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($reservation['id'] ?? 'N/A') ?></td>
                                                        <td><?= htmlspecialchars($reservation['nom'] ?? 'N/A') ?></td>
                                                        <td><?= htmlspecialchars($reservation['tel'] ?? 'N/A') ?></td>
                                                        <td><?= htmlspecialchars($reservation['lieu'] ?? 'N/A') ?></td>
                                                        <td><?= htmlspecialchars($reservation['date_naissance'] ?? 'N/A') ?></td>
                                                        <td>
                                                            <span class="badge bg-info">
                                                                <?= htmlspecialchars($reservation['softskills'] ?? 'N/A') ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <strong><?= htmlspecialchars($reservation['evenement_nom'] ?? 'N/A') ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?= htmlspecialchars($reservation['evenement_type'] ?? '') ?></small>
                                                        </td>
                                                        <td><?= htmlspecialchars($reservation['email'] ?? '-') ?></td>
                                                        <td>
                                                            <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=reservations&section_action=edit&id=<?= $reservation['id'] ?>" 
                                                               class="btn btn-sm btn-warning" title="Modifier">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=reservations&delete_id=<?= $reservation['id'] ?>" 
                                                               class="btn btn-sm btn-danger" 
                                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?')"
                                                               title="Supprimer">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-muted">
                                            <strong><?= count($allReservations) ?></strong> réservation(s) trouvée(s)
                                        </p>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                                        <p class="text-muted mt-3">
                                            <?php if (!empty($_GET['search'])): ?>
                                                Aucune réservation trouvée pour "<?= htmlspecialchars($_GET['search']) ?>"
                                            <?php else: ?>
                                                Aucune réservation pour le moment.
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($currentAction === 'create' || $currentAction === 'edit'): ?>
                <!-- Reservation Form (Create/Edit) -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5><i class="bi bi-ticket-perforated me-2"></i><?= $currentAction === 'edit' ? 'Modifier' : 'Nouvelle' ?> Réservation</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?= $baseUrl ?>/index.php?action=dashboard">
                                    <input type="hidden" name="section" value="reservations">
                                    <input type="hidden" name="section_action" value="<?= $currentAction === 'edit' ? 'edit' : 'create' ?>">
                                    <?php if ($currentAction === 'edit' && isset($reservationToEdit)): ?>
                                        <input type="hidden" name="id" value="<?= $reservationToEdit['id'] ?>">
                                    <?php endif; ?>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Événement <span class="text-danger">*</span></label>
                                            <select name="evenement_id" class="form-select" required>
                                                <option value="">-- Choisir un événement --</option>
                                                <?php 
                                                $currentEventId = null;
                                                if ($currentAction === 'edit' && isset($reservationToEdit)) {
                                                    $currentEventId = isset($reservationToEdit['evenement_id']) ? (int)$reservationToEdit['evenement_id'] : null;
                                                    error_log("Reservation edit - Current event ID: " . var_export($currentEventId, true));
                                                    error_log("Reservation edit - Reservation data: " . json_encode($reservationToEdit));
                                                }
                                                foreach($allEvenements ?? [] as $e): 
                                                    $selected = '';
                                                    $eventId = (int)($e['id'] ?? 0);
                                                    if ($currentAction === 'edit' && $currentEventId !== null && $currentEventId == $eventId) {
                                                        $selected = 'selected';
                                                    }
                                                ?>
                                                    <option value="<?= $eventId ?>" <?= $selected ?>>
                                                        <?= htmlspecialchars($e['nom_evenement'] ?? '') ?> (<?= htmlspecialchars($e['type_evenement'] ?? '') ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">Nom complet <span class="text-danger">*</span></label>
                                            <input type="text" name="nom" class="form-control" 
                                                   value="<?= htmlspecialchars($currentAction === 'edit' && isset($reservationToEdit) ? $reservationToEdit['nom'] : '') ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                                            <input type="text" name="tel" class="form-control" 
                                                   value="<?= htmlspecialchars($currentAction === 'edit' && isset($reservationToEdit) ? $reservationToEdit['tel'] : '') ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">Lieu <span class="text-danger">*</span></label>
                                            <input type="text" name="lieu" class="form-control" 
                                                   value="<?= htmlspecialchars($currentAction === 'edit' && isset($reservationToEdit) ? $reservationToEdit['lieu'] : '') ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">Date de naissance <span class="text-danger">*</span></label>
                                            <input type="date" name="date_naissance" class="form-control" 
                                                   value="<?= htmlspecialchars($currentAction === 'edit' && isset($reservationToEdit) ? $reservationToEdit['date_naissance'] : '') ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">Softskills / Technologies <span class="text-danger">*</span></label>
                                            <select name="softskills" class="form-select" required>
                                                <option value="">-- Choisir --</option>
                                                <?php 
                                                $softskillsList = [
                                                    "Romans", "Science-fiction", "Fantasy", "Policier / Thriller", "Horreur",
                                                    "Romance", "Aventure", "Classiques", "Essais", "Développement personnel",
                                                    "Biographies / Mémoires", "Philosophie", "Histoire", "Jeunesse", "Manga",
                                                    "Bandes dessinées", "Poésie", "Théâtre", "Livres scolaires", "Livres universitaires",
                                                    "Cuisine / Gastronomie", "Art / Photographie", "Business / Management",
                                                    "Technologie / Informatique", "Spiritualité / Religion", "Santé / Bien-être"
                                                ];
                                                foreach($softskillsList as $s): 
                                                    $selected = '';
                                                    if ($currentAction === 'edit' && isset($reservationToEdit) && $reservationToEdit['softskills'] === $s) {
                                                        $selected = 'selected';
                                                    }
                                                ?>
                                                    <option value="<?= $s ?>" <?= $selected ?>><?= $s ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-12">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" 
                                                   value="<?= htmlspecialchars($currentAction === 'edit' && isset($reservationToEdit) ? ($reservationToEdit['email'] ?? '') : '') ?>">
                                        </div>
                                        
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-check-circle"></i> <?= $currentAction === 'edit' ? 'Mettre à jour' : 'Enregistrer' ?>
                                            </button>
                                            <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=reservations" class="btn btn-secondary">
                                                <i class="bi bi-x-circle"></i> Annuler
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($currentSection === 'users'): ?>
            <!-- ========== USERS SECTION ========== -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Gestion des Utilisateurs</h5>
                            <div class="d-flex gap-2">
                                <form method="GET" action="<?= $baseUrl ?>/index.php?action=dashboard&section=users" class="d-flex" id="usersSearchForm">
                                    <input type="hidden" name="action" value="dashboard">
                                    <input type="hidden" name="section" value="users">
                                    <input type="text" name="search" id="usersSearch" class="form-control me-2 search-input" 
                                           placeholder="Rechercher un utilisateur..." 
                                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i>
                                    </button>
                                    <?php if (!empty($_GET['search'])): ?>
                                    <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=users" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($allUsers)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="usersTable">
                                        <thead>
                                            <tr>
                                                <th>CIN</th>
                                                <th>Nom</th>
                                                <th>Email</th>
                                                <th>Rôle</th>
                                                <th>Email Vérifié</th>
                                                <th>Réservations</th>
                                                <th>Date d'inscription</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($allUsers as $user): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($user['cin'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')) ?></td>
                                                    <td><?= htmlspecialchars($user['email'] ?? 'N/A') ?></td>
                                                    <td><span class="badge bg-<?= ($user['role'] ?? 'user') === 'admin' ? 'primary' : 'info' ?>"><?= ucfirst($user['role'] ?? 'user') ?></span></td>
                                                    <td><span class="badge bg-<?= ($user['email_verified'] ?? 0) ? 'success' : 'warning' ?>"><?= ($user['email_verified'] ?? 0) ? 'Oui' : 'Non' ?></span></td>
                                                    <td><span class="badge bg-info"><?= $user['reservation_count'] ?? 0 ?></span></td>
                                                    <td><?= !empty($user['created_at']) ? date('d/m/Y H:i', strtotime($user['created_at'])) : 'N/A' ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-info" 
                                                                    title="Voir"
                                                                    onclick="viewUserProfile(<?= $user['cin'] ?>)">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                            <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=users&edit_id=<?= $user['cin'] ?>" 
                                                               class="btn btn-sm btn-primary" title="Modifier">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=users&delete_id=<?= $user['cin'] ?>&type=user" 
                                                               class="btn btn-sm btn-danger" 
                                                               title="Supprimer"
                                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-people" style="font-size: 48px; color: #ccc;"></i>
                                    <p class="text-muted mt-3">Aucun utilisateur trouvé.</p>
                                </div>
                            <?php endif; ?>
                        </div>
        </div>
    </div>
</div>

            <?php if (isset($userToEdit) && $userToEdit): ?>
            <!-- Edit User Form -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5><i class="bi bi-pencil me-2"></i>Modifier l'Utilisateur</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?= $baseUrl ?>/index.php?action=dashboard">
                                <input type="hidden" name="section" value="users">
                                <input type="hidden" name="section_action" value="edit">
                                <input type="hidden" name="cin" value="<?= $userToEdit['cin'] ?>">
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">CIN</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($userToEdit['cin']) ?>" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Prénom <span class="text-danger">*</span></label>
                                        <input type="text" name="firstname" class="form-control" 
                                               value="<?= htmlspecialchars($userToEdit['firstname'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                                        <input type="text" name="lastname" class="form-control" 
                                               value="<?= htmlspecialchars($userToEdit['lastname'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?= htmlspecialchars($userToEdit['email'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Rôle <span class="text-danger">*</span></label>
                                        <select name="role" class="form-select" required>
                                            <option value="user" <?= ($userToEdit['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>User</option>
                                            <option value="admin" <?= ($userToEdit['role'] ?? 'user') === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Vérifié</label>
                                        <select name="email_verified" class="form-select">
                                            <option value="0" <?= ($userToEdit['email_verified'] ?? 0) == 0 ? 'selected' : '' ?>>Non</option>
                                            <option value="1" <?= ($userToEdit['email_verified'] ?? 0) == 1 ? 'selected' : '' ?>>Oui</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle"></i> Enregistrer
                                        </button>
                                        <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=users" class="btn btn-secondary">
                                            <i class="bi bi-x-circle"></i> Annuler
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($currentSection === 'settings'): ?>
            <!-- ========== SETTINGS SECTION ========== -->
            <div class="row">
                <div class="col-12">
                    <div class="page-heading">
                        <h3>Settings</h3>
                        <p class="text-subtitle text-muted">Configure your application</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <?php if (isset($_SESSION['success']) && strpos($_SESSION['success'], 'Settings') !== false): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert" id="settingsSuccessAlert">
                            <?= htmlspecialchars($_SESSION['success']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <script>
                            setTimeout(function() {
                                var alert = document.getElementById('settingsSuccessAlert');
                                if (alert) {
                                    var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                                    bsAlert.close();
                                }
                            }, 5000);
                        </script>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($errorMessages)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($errorMessages as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form class="row g-3" action="<?= $baseUrl ?>/index.php?action=dashboard" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="section" value="settings">
                        <input type="hidden" name="section_action" value="update">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4>General Settings</h4>
                                </div>
                                <div class="card-body row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Site Name</label>
                                        <input type="text" name="site_name" class="form-control" required maxlength="150" value="<?= htmlspecialchars($settings['site_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contact Email</label>
                                        <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($settings['contact_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label d-block">Site Logo</label>
                                        <?php if (!empty($settings['site_logo_path'])): ?>
                                            <div class="mb-2">
                                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($settings['site_logo_path'], ENT_QUOTES, 'UTF-8') ?>" alt="Current logo" style="max-height: 60px;">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" name="site_logo" accept=".png,.jpg,.jpeg,.svg" class="form-control">
                                        <small class="text-muted">PNG/JPG/SVG, max 2MB.</small>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="maintenance_mode" name="maintenance_mode" <?= !empty($settings['maintenance_mode']) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Security Settings</h4>
                                </div>
                                <div class="card-body row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Session Timeout (minutes)</label>
                                        <input type="number" name="session_timeout_minutes" min="5" max="1440" class="form-control" value="<?= htmlspecialchars($settings['session_timeout_minutes'] ?? 30, ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">reCAPTCHA v3 Site Key</label>
                                        <input type="text" name="recaptcha_site_key" class="form-control" value="<?= htmlspecialchars($settings['recaptcha_site_key'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">reCAPTCHA v3 Secret Key</label>
                                        <input type="text" name="recaptcha_secret_key" class="form-control" value="<?= htmlspecialchars($settings['recaptcha_secret_key'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="col-12">
                                        <h6 class="mt-2">Admin Password</h6>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <input type="password" name="current_password" class="form-control" placeholder="Current password">
                                            </div>
                                            <div class="col-md-4">
                                                <input type="password" name="new_password" class="form-control" placeholder="New password (min 8 chars)">
                                            </div>
                                            <div class="col-md-4">
                                                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Notification Settings</h4>
                                </div>
                                <div class="card-body row g-3">
                                    <div class="col-md-4 d-flex align-items-center">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="email_notifications_enabled" name="email_notifications_enabled" <?= !empty($settings['email_notifications_enabled']) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="email_notifications_enabled">Email Notifications</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Email Sender Name</label>
                                        <input type="text" name="email_sender_name" class="form-control" value="<?= htmlspecialchars($settings['email_sender_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Email Sender Email</label>
                                        <input type="email" name="email_sender_email" class="form-control" value="<?= htmlspecialchars($settings['email_sender_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Welcome Email Template</label>
                                        <textarea name="email_template_welcome" class="form-control" rows="4"><?= htmlspecialchars($settings['email_template_welcome'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                        <small class="text-muted">Use placeholders: {{name}}</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Reservation Confirmation Template</label>
                                        <textarea name="email_template_donation" class="form-control" rows="4"><?= htmlspecialchars($settings['email_template_donation'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                        <small class="text-muted">Use placeholders: {{name}}, {{event}}</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Save Settings
                            </button>
                            <a href="<?= $baseUrl ?>/index.php?action=dashboard" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($currentSection === 'reported_posts'): ?>
            <!-- ========== REPORTED POSTS SECTION ========== -->
            <?php
            $reports = $reports ?? [];
            $pendingCount = $pendingCount ?? 0;
            $currentPage = $currentPage ?? 1;
            $totalPages = $totalPages ?? 1;
            $statusFilter = $_GET['status'] ?? null;
            ?>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>
                                <i class="bi bi-flag-fill text-danger"></i> Posts Signalés
                                <?php if ($pendingCount > 0): ?>
                                    <span class="badge bg-danger ms-2"><?= $pendingCount ?> en attente</span>
                                <?php endif; ?>
                            </h5>
                            <div class="d-flex gap-2">
                                <div class="btn-group" role="group">
                                    <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=reported_posts" 
                                       class="btn btn-sm <?= !$statusFilter ? 'btn-primary' : 'btn-outline-primary' ?>">
                                        Tous
                                    </a>
                                    <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=reported_posts&status=pending" 
                                       class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-warning' : 'btn-outline-warning' ?>">
                                        En attente
                                    </a>
                                    <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=reported_posts&status=reviewed" 
                                       class="btn btn-sm <?= $statusFilter === 'reviewed' ? 'btn-success' : 'btn-outline-success' ?>">
                                        Examinés
                                    </a>
                                    <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=reported_posts&status=dismissed" 
                                       class="btn btn-sm <?= $statusFilter === 'dismissed' ? 'btn-secondary' : 'btn-outline-secondary' ?>">
                                        Rejetés
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($reports)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Post</th>
                                                <th>Signalé par</th>
                                                <th>Raison</th>
                                                <th>Description</th>
                                                <th>Date</th>
                                                <th>Total Signalements</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reports as $report): ?>
                                                <tr>
                                                    <td><?= $report['id_report'] ?></td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($report['post_title'] ?? 'Post supprimé') ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            <a href="javascript:void(0)" 
                                                               onclick="viewPostInModal(<?= $report['id_post'] ?>)"
                                                               class="text-primary" style="cursor: pointer;">
                                                                <i class="bi bi-eye"></i> Voir le post #<?= $report['id_post'] ?>
                                                            </a>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars(($report['firstname'] ?? '') . ' ' . ($report['lastname'] ?? 'Utilisateur')) ?>
                                                        <br>
                                                        <small class="text-muted"><?= htmlspecialchars($report['reporter_email'] ?? '') ?></small>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $reasonLabels = [
                                                            'spam' => 'Spam',
                                                            'harassment' => 'Harcèlement',
                                                            'hate_speech' => 'Discours de haine',
                                                            'fake_information' => 'Fausse information',
                                                            'inappropriate_content' => 'Contenu inapproprié',
                                                            'other' => 'Autre'
                                                        ];
                                                        $reason = $report['reason'] ?? 'other';
                                                        ?>
                                                        <span class="badge bg-info"><?= $reasonLabels[$reason] ?? 'Autre' ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($report['description'])): ?>
                                                            <small><?= htmlspecialchars(substr($report['description'], 0, 100)) ?><?= strlen($report['description']) > 100 ? '...' : '' ?></small>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?= date('d/m/Y H:i', strtotime($report['date_report'])) ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?= $report['total_reports'] ?? 1 ?></span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $statusLabels = [
                                                            'pending' => ['label' => 'En attente', 'class' => 'warning'],
                                                            'reviewed' => ['label' => 'Examiné', 'class' => 'success'],
                                                            'dismissed' => ['label' => 'Rejeté', 'class' => 'secondary']
                                                        ];
                                                        $status = $report['status'] ?? 'pending';
                                                        $statusInfo = $statusLabels[$status] ?? $statusLabels['pending'];
                                                        ?>
                                                        <span class="badge bg-<?= $statusInfo['class'] ?>"><?= $statusInfo['label'] ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <?php if ($status === 'pending'): ?>
                                                                <button type="button" class="btn btn-success" 
                                                                        onclick="updateReportStatus(<?= $report['id_report'] ?>, 'reviewed')"
                                                                        title="Marquer comme examiné">
                                                                    <i class="bi bi-check-circle"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-secondary" 
                                                                        onclick="updateReportStatus(<?= $report['id_report'] ?>, 'dismissed')"
                                                                        title="Rejeter">
                                                                    <i class="bi bi-x-circle"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <button type="button" class="btn btn-info" 
                                                                    onclick="viewReportDetails(<?= $report['id_report'] ?>)"
                                                                    title="Voir détails">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($totalPages > 1): ?>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-center">
                                            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?action=dashboard&section=reported_posts&p=<?= $currentPage - 1 ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?>">Précédent</a>
                                            </li>
                                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                                    <a class="page-link" href="?action=dashboard&section=reported_posts&p=<?= $i ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?action=dashboard&section=reported_posts&p=<?= $currentPage + 1 ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?>">Suivant</a>
                                            </li>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Aucun signalement trouvé.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Report Details Modal -->
            <div class="modal fade" id="reportDetailsModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Détails du Signalement</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="reportDetailsContent">
                            <!-- Content loaded via AJAX -->
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
            function updateReportStatus(reportId, status) {
                if (!confirm('Êtes-vous sûr de vouloir mettre à jour le statut de ce signalement ?')) {
                    return;
                }
                
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?= $baseUrl ?>/index.php?action=dashboard&section=reported_posts';
                
                const reportIdInput = document.createElement('input');
                reportIdInput.type = 'hidden';
                reportIdInput.name = 'report_id';
                reportIdInput.value = reportId;
                form.appendChild(reportIdInput);
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = status;
                form.appendChild(statusInput);
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'update_report_status';
                actionInput.value = '1';
                form.appendChild(actionInput);
                
                document.body.appendChild(form);
                form.submit();
            }
            
            function viewReportDetails(reportId) {
                // Load report details via AJAX
                fetch('<?= $baseUrl ?>/index.php?action=dashboard&section=reported_posts&get_report=' + reportId)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('reportDetailsContent').innerHTML = html;
                        const modal = new bootstrap.Modal(document.getElementById('reportDetailsModal'));
                        modal.show();
                    })
                    .catch(error => {
                        console.error('Error loading report details:', error);
                        alert('Erreur lors du chargement des détails');
                    });
            }
            
            // viewPostInModal, renderPostInModal, and escapeHtml are now defined globally at the end of the page
            </script>
            <?php endif; ?>
            
            <?php if ($currentSection === 'reclamations'): ?>
                <?php require_once __DIR__ . '/reclamations/index.php'; ?>
            <?php endif; ?>

            <?php if ($currentSection === 'donations'): ?>
                <?php require_once __DIR__ . '/donations/index.php'; ?>
            <?php endif; ?>

            <?php 
            // Check if we should show associations section
            $showAssociations = ($currentSection === 'associations' || 
                                (isset($actionParam) && strpos($actionParam, 'admin_association') === 0) ||
                                (isset($_GET['action']) && strpos($_GET['action'], 'admin_association') === 0));
            if ($showAssociations): ?>
            <!-- ========== ASSOCIATIONS SECTION ========== -->
            <div id="associations-section" style="scroll-margin-top: 100px;">
            <?php
            // Note: POST and DELETE requests for associations are now handled at the top of dashboard.php
            // before any HTML output, so they can redirect properly.
            // This code only handles GET requests for displaying the associations section.
            require_once __DIR__ . '/../../controllers/AdminAssociationController.php';
            $action = $actionParam ?? $_GET['action'] ?? $_POST['action'] ?? '';
            $sectionAction = $_GET['section_action'] ?? $_POST['section_action'] ?? '';
            $id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
            
            // Handle GET requests for view/delete/members
            // Only show create form if explicitly requested via GET (not after redirect)
            // If we have a success message and section_action=create is in URL, redirect to list
            if (isset($_SESSION['admin_association_success']) && $sectionAction === 'create' && $_SERVER['REQUEST_METHOD'] === 'GET') {
                // Clear section_action from URL by redirecting to list
                while (ob_get_level()) {
                    ob_end_clean();
                }
                $redirectUrl = BASE_URL . '/index.php?action=dashboard&section=associations&_t=' . time() . '#associations-section';
                error_log("dashboard.php - Redirecting from create form to list: " . $redirectUrl);
                header('Location: ' . $redirectUrl, true, 303);
                exit;
            }
            
            // Only show create form if explicitly requested AND no success message exists
            if ($sectionAction === 'create' && $_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_SESSION['admin_association_success'])) {
                $adminAssociationController = new AdminAssociationController();
                $adminAssociationController->create();
            } elseif ($sectionAction === 'update' || $action === 'admin_association_update') {
                $adminAssociationController = new AdminAssociationController();
                $adminAssociationController->update($id);
            } elseif ($sectionAction === 'members' || $action === 'admin_association_members') {
                $adminAssociationController = new AdminAssociationController();
                $adminAssociationController->members($id);
            } else {
                // Default: show list (index) - this will show success message if set
                $adminAssociationController = new AdminAssociationController();
                $adminAssociationController->index();
            }
            ?>
            </div>
            <?php endif; ?>

            <?php 
            // Check if we should show cotisations section
            $showCotisations = ($currentSection === 'cotisations' || 
                               (isset($actionParam) && strpos($actionParam, 'admin_cotisation') === 0) ||
                               (isset($_GET['action']) && strpos($_GET['action'], 'admin_cotisation') === 0));
            if ($showCotisations): ?>
            <!-- ========== COTISATIONS SECTION ========== -->
            <div id="cotisations-section" style="scroll-margin-top: 100px;">
            <?php
            // Note: POST and DELETE requests for cotisations are now handled at the top of dashboard.php
            // before any HTML output, so they can redirect properly.
            // This code only handles GET requests for displaying the cotisations section.
            require_once __DIR__ . '/../../controllers/AdminCotisationController.php';
            $action = $actionParam ?? $_GET['action'] ?? '';
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            // Only handle GET requests here (display list)
            $adminCotisationController = new AdminCotisationController();
            $adminCotisationController->index();
            ?>
            </div>
            <?php endif; ?>

            <?php if ($currentSection === 'comment_reports'): ?>
            <!-- ========== COMMENT REPORTS SECTION ========== -->
            <?php
            $reports = $reports ?? [];
            $pendingCount = $pendingCount ?? 0;
            $currentPage = $currentPage ?? 1;
            $totalPages = $totalPages ?? 1;
            $statusFilter = $_GET['status'] ?? null;
            ?>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>
                                <i class="bi bi-chat-left-text text-danger"></i> Commentaires Signalés
                                <?php if ($pendingCount > 0): ?>
                                    <span class="badge bg-danger ms-2"><?= $pendingCount ?> en attente</span>
                                <?php endif; ?>
                            </h5>
                            <div class="d-flex gap-2">
                                <div class="btn-group" role="group">
                                    <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=comment_reports" 
                                       class="btn btn-sm <?= !$statusFilter ? 'btn-primary' : 'btn-outline-primary' ?>">
                                        Tous
                                    </a>
                                    <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=comment_reports&status=pending" 
                                       class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-warning' : 'btn-outline-warning' ?>">
                                        En attente
                                    </a>
                                    <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=comment_reports&status=reviewed" 
                                       class="btn btn-sm <?= $statusFilter === 'reviewed' ? 'btn-success' : 'btn-outline-success' ?>">
                                        Examinés
                                    </a>
                                    <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=comment_reports&status=resolved" 
                                       class="btn btn-sm <?= $statusFilter === 'resolved' ? 'btn-info' : 'btn-outline-info' ?>">
                                        Résolus
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($reports)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Commentaire</th>
                                                <th>Signalé par</th>
                                                <th>Raison</th>
                                                <th>Description</th>
                                                <th>Date</th>
                                                <th>Total Signalements</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reports as $report): ?>
                                                <tr>
                                                    <td><?= $report['id_report'] ?></td>
                                                    <td>
                                                        <div class="comment-preview" style="max-width: 300px;">
                                                            <strong><?= htmlspecialchars(substr($report['comment_text'] ?? 'Commentaire supprimé', 0, 100)) ?><?= strlen($report['comment_text'] ?? '') > 100 ? '...' : '' ?></strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                <a href="javascript:void(0)" 
                                                                   onclick="viewPostInModal(<?= $report['id_post'] ?? 0 ?>)"
                                                                   class="text-primary" style="cursor: pointer;">
                                                                    Voir le post #<?= $report['id_post'] ?? 'N/A' ?>
                                                                </a>
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars(($report['firstname'] ?? '') . ' ' . ($report['lastname'] ?? 'Utilisateur')) ?>
                                                        <br>
                                                        <small class="text-muted"><?= htmlspecialchars($report['reporter_email'] ?? '') ?></small>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $reasonLabels = [
                                                            'spam' => 'Spam',
                                                            'harassment' => 'Harcèlement / Haine',
                                                            'inappropriate_content' => 'Contenu inapproprié',
                                                            'other' => 'Autre'
                                                        ];
                                                        $reason = $report['reason'] ?? 'other';
                                                        ?>
                                                        <span class="badge bg-info"><?= $reasonLabels[$reason] ?? 'Autre' ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($report['description'])): ?>
                                                            <small><?= htmlspecialchars(substr($report['description'], 0, 100)) ?><?= strlen($report['description']) > 100 ? '...' : '' ?></small>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?= date('d/m/Y H:i', strtotime($report['date_report'])) ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?= $report['total_reports'] ?? 1 ?></span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $statusLabels = [
                                                            'pending' => ['label' => 'En attente', 'class' => 'warning'],
                                                            'reviewed' => ['label' => 'Examiné', 'class' => 'success'],
                                                            'resolved' => ['label' => 'Résolu', 'class' => 'info']
                                                        ];
                                                        $status = $report['status'] ?? 'pending';
                                                        $statusInfo = $statusLabels[$status] ?? $statusLabels['pending'];
                                                        ?>
                                                        <span class="badge bg-<?= $statusInfo['class'] ?>"><?= $statusInfo['label'] ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <?php if ($status === 'pending'): ?>
                                                                <button type="button" class="btn btn-success" 
                                                                        onclick="updateCommentReportStatus(<?= $report['id_report'] ?>, 'reviewed')"
                                                                        title="Marquer comme examiné">
                                                                    <i class="bi bi-check-circle"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-info" 
                                                                        onclick="updateCommentReportStatus(<?= $report['id_report'] ?>, 'resolved')"
                                                                        title="Marquer comme résolu">
                                                                    <i class="bi bi-check2-all"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-danger" 
                                                                        onclick="deleteComment(<?= $report['id_comment'] ?>, <?= $report['id_report'] ?>)"
                                                                        title="Supprimer le commentaire">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <button type="button" class="btn btn-secondary" 
                                                                    onclick="ignoreCommentReport(<?= $report['id_report'] ?>)"
                                                                    title="Ignorer le signalement">
                                                                <i class="bi bi-x-circle"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-info" 
                                                                    onclick="viewCommentReportDetails(<?= $report['id_report'] ?>)"
                                                                    title="Voir détails">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($totalPages > 1): ?>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-center">
                                            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?action=dashboard&section=comment_reports&p=<?= $currentPage - 1 ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?>">Précédent</a>
                                            </li>
                                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                                    <a class="page-link" href="?action=dashboard&section=comment_reports&p=<?= $i ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?action=dashboard&section=comment_reports&p=<?= $currentPage + 1 ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?>">Suivant</a>
                                            </li>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Aucun signalement de commentaire trouvé.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Comment Report Details Modal -->
            <div class="modal fade" id="commentReportDetailsModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Détails du Signalement</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="commentReportDetailsContent">
                            <!-- Content loaded via AJAX -->
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
            function updateCommentReportStatus(reportId, status) {
                if (!confirm('Êtes-vous sûr de vouloir mettre à jour le statut de ce signalement ?')) {
                    return;
                }
                
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?= $baseUrl ?>/index.php?action=dashboard&section=comment_reports';
                
                const reportIdInput = document.createElement('input');
                reportIdInput.type = 'hidden';
                reportIdInput.name = 'report_id';
                reportIdInput.value = reportId;
                form.appendChild(reportIdInput);
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = status;
                form.appendChild(statusInput);
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'update_report_status';
                actionInput.value = '1';
                form.appendChild(actionInput);
                
                document.body.appendChild(form);
                form.submit();
            }
            
            function deleteComment(commentId, reportId) {
                if (!confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ? Cette action est irréversible.')) {
                    return;
                }
                
                window.location.href = '<?= $baseUrl ?>/index.php?action=dashboard&section=comment_reports&delete_comment=' + commentId;
            }
            
            function ignoreCommentReport(reportId) {
                if (!confirm('Ignorer ce signalement ? Le statut sera marqué comme "Résolu".')) {
                    return;
                }
                updateCommentReportStatus(reportId, 'resolved');
            }
            
            function viewCommentReportDetails(reportId) {
                fetch('<?= $baseUrl ?>/index.php?action=dashboard&section=comment_reports&get_report=' + reportId)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('commentReportDetailsContent').innerHTML = html;
                        const modal = new bootstrap.Modal(document.getElementById('commentReportDetailsModal'));
                        modal.show();
                    })
                    .catch(error => {
                        console.error('Error loading comment report details:', error);
                        alert('Erreur lors du chargement des détails');
                    });
            }
            </script>
            <?php endif; ?>
            
            <?php if ($currentSection === 'login_sessions'): ?>
            <!-- ========== LOGIN SESSIONS SECTION ========== -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Sessions de Connexion</h5>
                            <div class="d-flex gap-2">
                                <form method="GET" action="<?= $baseUrl ?>/index.php?action=dashboard&section=login_sessions" class="d-flex" id="loginSessionsSearchForm">
                                    <input type="hidden" name="action" value="dashboard">
                                    <input type="hidden" name="section" value="login_sessions">
                                    <input type="text" name="search" id="loginSessionsSearch" class="form-control me-2 search-input" 
                                           placeholder="Rechercher..." 
                                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i>
                                    </button>
                                    <?php if (!empty($_GET['search'])): ?>
                                    <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=login_sessions" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($loginSessions)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="loginSessionsTable">
                                        <thead>
                                            <tr>
                                                <th>Session ID</th>
                                                <th>Utilisateur</th>
                                                <th>Email</th>
                                                <th>Rôle</th>
                                                <th>IP Address</th>
                                                <th>Device</th>
                                                <th>Connexion</th>
                                                <th>Déconnexion</th>
                                                <th>Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($loginSessions as $session): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($session['sessionID'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars(($session['firstname'] ?? '') . ' ' . ($session['lastname'] ?? '')) ?></td>
                                                    <td><?= htmlspecialchars($session['email'] ?? 'N/A') ?></td>
                                                    <td><span class="badge bg-<?= ($session['role'] ?? 'user') === 'admin' ? 'primary' : 'info' ?>"><?= ucfirst($session['role'] ?? 'user') ?></span></td>
                                                    <td><?= htmlspecialchars($session['ipAddress'] ?? 'N/A') ?></td>
                                                    <td><small><?= htmlspecialchars(substr($session['device'] ?? 'N/A', 0, 50)) ?></small></td>
                                                    <td><?= !empty($session['loginTime']) ? date('d/m/Y H:i:s', strtotime($session['loginTime'])) : 'N/A' ?></td>
                                                    <td><?= !empty($session['logoutTime']) ? date('d/m/Y H:i:s', strtotime($session['logoutTime'])) : '<span class="badge bg-warning">En cours</span>' ?></td>
                                                    <td>
                                                        <?php if (empty($session['logoutTime'])): ?>
                                                            <span class="badge bg-success">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Terminée</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-clock-history" style="font-size: 48px; color: #ccc;"></i>
                                    <p class="text-muted mt-3">Aucune session de connexion trouvée.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="<?= $baseUrl ?>/view/backoffice/assets/js/initTheme.js"></script>
<script src="<?= $baseUrl ?>/view/backoffice/assets/js/app.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.bootstrapLoaded = true;
</script>
<script>
// Events by Type Chart
const eventsByTypeCtx = document.getElementById('eventsByTypeChart');
if (eventsByTypeCtx) {
    const eventsByTypeData = <?= json_encode($eventsByType ?? []) ?>;
    new Chart(eventsByTypeCtx, {
        type: 'doughnut',
        data: {
            labels: eventsByTypeData.map(item => item.type_evenement),
            datasets: [{
                label: 'Événements',
                data: eventsByTypeData.map(item => item.count),
                backgroundColor: [
                    '#435ebe',
                    '#5a6fd8',
                    '#6c7ae0',
                    '#7d8ae8',
                    '#8e9bf0'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true
        }
    });
}

// Reservations by Month Chart
const reservationsByMonthCtx = document.getElementById('reservationsByMonthChart');
if (reservationsByMonthCtx) {
    const reservationsByMonthData = <?= json_encode($reservationsByMonth ?? []) ?>;
    new Chart(reservationsByMonthCtx, {
        type: 'line',
        data: {
            labels: reservationsByMonthData.map(item => item.month),
            datasets: [{
                label: 'Réservations',
                data: reservationsByMonthData.map(item => item.count),
                borderColor: '#435ebe',
                backgroundColor: 'rgba(67, 94, 190, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// User Statistics
const userStatsApi = '<?= $baseUrl ?>/index.php?action=api_user_stats';
function loadUserStats() {
    fetch(userStatsApi)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error loading user stats:', data.error);
                return;
            }
            document.getElementById('stat-active-users').textContent = data.active_users || 0;
            document.getElementById('stat-new-today').textContent = data.new_users_today || 0;
            document.getElementById('stat-new-month').textContent = data.new_users_month || 0;
            document.getElementById('stat-online').textContent = data.users_online || 0;
        })
        .catch(error => console.error('Error:', error));
}

// User Registrations Chart
const userRegistrationsCtx = document.getElementById('userRegistrationsChart');
let userRegistrationsChart = null;
function loadUserRegistrationsChart() {
    fetch('<?= $baseUrl ?>/index.php?action=api_user_registrations_chart')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error loading registrations chart:', data.error);
                return;
            }
            if (userRegistrationsChart) {
                userRegistrationsChart.destroy();
            }
            userRegistrationsChart = new Chart(userRegistrationsCtx, {
                type: 'bar',
                data: {
                    labels: data.map(item => item.month),
                    datasets: [{
                        label: 'Inscriptions',
                        data: data.map(item => item.registrations),
                        backgroundColor: '#435ebe',
                        borderColor: '#5a6fd8',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error:', error));
}

// Login Activity Chart
const loginActivityCtx = document.getElementById('loginActivityChart');
let loginActivityChart = null;
function loadLoginActivityChart() {
    fetch('<?= $baseUrl ?>/index.php?action=api_login_activity_chart')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error loading login activity chart:', data.error);
                return;
            }
            if (loginActivityChart) {
                loginActivityChart.destroy();
            }
            loginActivityChart = new Chart(loginActivityCtx, {
                type: 'line',
                data: {
                    labels: data.map(item => item.day),
                    datasets: [{
                        label: 'Connexions réussies',
                        data: data.map(item => item.success_count),
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error:', error));
}

// Load charts and stats on page load
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('userRegistrationsChart')) {
        loadUserStats();
        loadUserRegistrationsChart();
        loadLoginActivityChart();
    }
    
    // Refresh button
    const refreshBtn = document.getElementById('refreshUserStats');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            loadUserStats();
            loadUserRegistrationsChart();
            loadLoginActivityChart();
        });
    }
});

// Initialize Flatpickr for date inputs
document.addEventListener('DOMContentLoaded', function() {
    const dateInputs = document.querySelectorAll('.flatpickr-datetime');
    if (dateInputs.length > 0 && typeof flatpickr !== 'undefined') {
        dateInputs.forEach(function(input) {
            flatpickr(input, {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true,
                locale: "fr"
            });
        });
    }
});
</script>

<!-- Modal pour Créer/Modifier Réservation -->
<div class="modal fade" id="reservationModal" tabindex="-1" aria-labelledby="reservationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reservationModalLabel">
                    <i class="bi bi-ticket-perforated"></i> 
                    <span id="modalTitle">Nouvelle Réservation</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= $baseUrl ?>/index.php?action=dashboard">
                <div class="modal-body">
                    <input type="hidden" name="section" value="reservations">
                    <input type="hidden" name="section_action" id="formAction" value="create">
                    <input type="hidden" name="id" id="reservationId" value="">
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= htmlspecialchars($_SESSION['success']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['errors'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <ul class="mb-0">
                                <?php foreach ($_SESSION['errors'] as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['errors']); ?>
                    <?php endif; ?>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Événement <span class="text-danger">*</span></label>
                            <select name="evenement_id" id="evenement_id" class="form-select" required>
                                <option value="">-- Choisir un événement --</option>
                                <?php foreach($allEvenements ?? [] as $e): ?>
                                    <option value="<?= $e['id'] ?>">
                                        <?= htmlspecialchars($e['nom_evenement']) ?> (<?= htmlspecialchars($e['type_evenement']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" name="nom" id="nom" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                            <input type="text" name="tel" id="tel" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Lieu <span class="text-danger">*</span></label>
                            <input type="text" name="lieu" id="lieu" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Date de naissance <span class="text-danger">*</span></label>
                            <input type="date" name="date_naissance" id="date_naissance" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Softskills / Technologies <span class="text-danger">*</span></label>
                            <select name="softskills" id="softskills" class="form-select" required>
                                <option value="">-- Choisir --</option>
                                <?php 
                                $softskillsList = [
                                    "Romans", "Science-fiction", "Fantasy", "Policier / Thriller", "Horreur",
                                    "Romance", "Aventure", "Classiques", "Essais", "Développement personnel",
                                    "Biographies / Mémoires", "Philosophie", "Histoire", "Jeunesse", "Manga",
                                    "Bandes dessinées", "Poésie", "Théâtre", "Livres scolaires", "Livres universitaires",
                                    "Cuisine / Gastronomie", "Art / Photographie", "Business / Management",
                                    "Technologie / Informatique", "Spiritualité / Religion", "Santé / Bien-être"
                                ];
                                foreach($softskillsList as $s): ?>
                                    <option value="<?= $s ?>"><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openReservationModal(reservationId = null) {
    const modal = new bootstrap.Modal(document.getElementById('reservationModal'));
    const form = document.getElementById('reservationModal').querySelector('form');
    const modalTitle = document.getElementById('modalTitle');
    const formAction = document.getElementById('formAction');
    const reservationIdInput = document.getElementById('reservationId');
    
    // Reset form
    form.reset();
    formAction.value = 'create';
    reservationIdInput.value = '';
    
    if (reservationId) {
        // Mode édition - charger les données depuis PHP
        modalTitle.textContent = 'Modifier la Réservation';
        formAction.value = 'edit';
        reservationIdInput.value = reservationId;
        
        <?php if (isset($reservationToEdit) && $reservationToEdit): ?>
        if (reservationId == <?= $reservationToEdit['id'] ?? 0 ?>) {
            document.getElementById('evenement_id').value = '<?= $reservationToEdit['evenement_id'] ?? '' ?>';
            document.getElementById('nom').value = <?= json_encode($reservationToEdit['nom'] ?? '') ?>;
            document.getElementById('tel').value = <?= json_encode($reservationToEdit['tel'] ?? '') ?>;
            document.getElementById('lieu').value = <?= json_encode($reservationToEdit['lieu'] ?? '') ?>;
            document.getElementById('date_naissance').value = <?= json_encode($reservationToEdit['date_naissance'] ?? '') ?>;
            document.getElementById('softskills').value = <?= json_encode($reservationToEdit['softskills'] ?? '') ?>;
            document.getElementById('email').value = <?= json_encode($reservationToEdit['email'] ?? '') ?>;
        }
        <?php endif; ?>
    } else {
        // Mode création
        modalTitle.textContent = 'Nouvelle Réservation';
        formAction.value = 'reservation_create';
    }
    
    modal.show();
}

// Ouvrir la modale en mode édition si edit_id est présent dans l'URL
<?php if (isset($_GET['edit_id']) && isset($reservationToEdit) && $reservationToEdit): ?>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => openReservationModal(<?= (int)$_GET['edit_id'] ?>), 300);
});
<?php endif; ?>
</script>

<!-- User Profile Modal -->
<div class="modal fade" id="userProfileModal" tabindex="-1" aria-labelledby="userProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="userProfileModalLabel">
                    <i class="bi bi-person-circle"></i> Profil Utilisateur
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="userProfileContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewUserProfile(userId) {
    const modalElement = document.getElementById('userProfileModal');
    const modal = new bootstrap.Modal(modalElement);
    const content = document.getElementById('userProfileContent');
    
    // Show loading
    content.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></div>';
    modal.show();
    
    // Fetch user profile data
    fetch('<?= $baseUrl ?>/index.php?action=api_user_profile&user_id=' + userId)
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.error || 'Erreur HTTP: ' + response.status);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                let errorMsg = data.error;
                if (data.message) {
                    errorMsg += '<br><small>' + data.message + '</small>';
                }
                content.innerHTML = '<div class="alert alert-danger">' + errorMsg + '</div>';
                return;
            }
            
            // Get profile picture with fallback
            let profilePicture = data.profile_picture;
            if (!profilePicture || profilePicture === 'null' || profilePicture === '') {
                profilePicture = '<?= $baseUrl ?>/view/frontoffice/assets/images/default-avatar.png';
            }
            
            const emailVerified = data.email_verified ? '<span class="badge bg-success">Vérifié</span>' : '<span class="badge bg-warning">Non vérifié</span>';
            
            content.innerHTML = `
                <div class="row">
                    <div class="col-md-12 text-center mb-4">
                        <img src="${profilePicture}" 
                             alt="Photo de profil" 
                             class="rounded-circle shadow" 
                             style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #435ebe; cursor: pointer;"
                             onerror="this.onerror=null; this.src='<?= $baseUrl ?>/view/frontoffice/assets/images/default-avatar.png';"
                             onclick="window.open('${profilePicture}', '_blank')"
                             title="Cliquez pour voir en grand">
                        <p class="text-muted mt-2"><small>Photo de profil</small></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong><i class="bi bi-card-text"></i> CIN:</strong>
                        <p class="text-muted">${data.cin || 'N/A'}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong><i class="bi bi-person"></i> Prénom:</strong>
                        <p class="text-muted">${data.firstname || 'N/A'}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong><i class="bi bi-person-fill"></i> Nom:</strong>
                        <p class="text-muted">${data.lastname || 'N/A'}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong><i class="bi bi-envelope"></i> Email:</strong>
                        <p class="text-muted">${data.email || 'N/A'} ${emailVerified}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong><i class="bi bi-shield-check"></i> Rôle:</strong>
                        <p><span class="badge bg-${data.role === 'admin' ? 'primary' : 'info'}">${data.role ? data.role.charAt(0).toUpperCase() + data.role.slice(1) : 'User'}</span></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong><i class="bi bi-calendar"></i> Date d'inscription:</strong>
                        <p class="text-muted">${data.created_at ? new Date(data.created_at).toLocaleDateString('fr-FR') : 'N/A'}</p>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            let errorMsg = 'Erreur lors du chargement du profil utilisateur.';
            if (error.message) {
                errorMsg += '<br><small>' + error.message + '</small>';
            }
            content.innerHTML = '<div class="alert alert-danger">' + errorMsg + '</div>';
        });
}
</script>

<?php include __DIR__ . '/../components/chatbot.php'; ?>
<script>
// Manual dropdown toggle - works even if Bootstrap fails
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('adminDropdown');
    var menu = btn ? btn.closest('.dropdown').querySelector('.dropdown-menu') : null;
    
    if (btn && menu) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            menu.classList.toggle('show');
            btn.setAttribute('aria-expanded', menu.classList.contains('show'));
        });
        
        document.addEventListener('click', function(e) {
            if (!btn.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.remove('show');
                btn.setAttribute('aria-expanded', 'false');
            }
        });
    }
    
    // Also try Bootstrap if available
    if (typeof bootstrap !== 'undefined' && btn) {
        try {
            new bootstrap.Dropdown(btn);
        } catch(e) {}
    }
});
</script>
<script>
(function() {
    function initDropdown() {
        var btn = document.getElementById('adminDropdown');
        if (btn) {
            // Remove existing dropdown instance if any
            var existing = bootstrap.Dropdown.getInstance(btn);
            if (existing) {
                existing.dispose();
            }
            // Create new dropdown
            new bootstrap.Dropdown(btn, {
                boundary: 'viewport',
                popperConfig: null
            });
            console.log('Dropdown initialized');
        } else {
            console.error('Dropdown button not found');
        }
    }
    
    // Wait for Bootstrap to load
    function waitForBootstrap() {
        if (typeof bootstrap !== 'undefined' && window.bootstrapLoaded) {
            initDropdown();
        } else {
            setTimeout(waitForBootstrap, 50);
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', waitForBootstrap);
    } else {
        waitForBootstrap();
    }
})();

// Dynamic search functionality
document.addEventListener('DOMContentLoaded', function() {
    // Map search inputs to their corresponding tables
    const searchMappings = [
        { inputId: 'dashboardReservationsSearch', tableId: 'dashboardReservationsTable' },
        { inputId: 'reservationsSearch', tableId: 'reservationsTable' },
        { inputId: 'eventsSearch', tableId: 'eventsTable' },
        { inputId: 'usersSearch', tableId: 'usersTable' },
        { inputId: 'loginSessionsSearch', tableId: 'loginSessionsTable' }
    ];
    
    // Setup dynamic search for each mapping
    searchMappings.forEach(function(mapping) {
        const searchInput = document.getElementById(mapping.inputId);
        const table = document.getElementById(mapping.tableId);
        const form = searchInput ? searchInput.closest('form') : null;
        
        if (searchInput && table) {
            let searchTimeout;
            
            // Prevent form submission on Enter key (for dynamic search)
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Only prevent if we want pure client-side search
                    // For now, allow form submission but also do client-side filtering
                    // e.preventDefault();
                });
                
                // Handle Enter key - do both client-side filter and allow form submit
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        // Filter immediately on Enter
                        clearTimeout(searchTimeout);
                        const searchTerm = this.value.toLowerCase().trim();
                        filterTable(table, searchTerm);
                        // Allow form to submit normally for server-side search
                    }
                });
            }
            
            // Real-time search as user types (with debounce)
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const searchTerm = this.value.toLowerCase().trim();
                
                searchTimeout = setTimeout(function() {
                    filterTable(table, searchTerm);
                }, 300); // Wait 300ms after user stops typing
            });
            
            // Also filter on page load if there's a search term
            if (searchInput.value.trim()) {
                filterTable(table, searchInput.value.toLowerCase().trim());
            }
        }
    });
    
    // Function to filter table rows
    function filterTable(table, searchTerm) {
        if (!table) return;
        
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        
        const rows = tbody.querySelectorAll('tr');
        let visibleCount = 0;
        
        rows.forEach(function(row) {
            // Get all text content from the row (excluding action buttons)
            const cells = row.querySelectorAll('td:not(:last-child)'); // Exclude last column (actions)
            let rowText = '';
            
            cells.forEach(function(cell) {
                rowText += ' ' + cell.textContent.toLowerCase();
            });
            
            // Check if search term matches
            if (searchTerm === '' || rowText.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show/hide "no results" message if needed
        const noResultsMsg = table.closest('.card-body')?.querySelector('.no-results-msg');
        if (noResultsMsg) {
            if (visibleCount === 0 && searchTerm !== '') {
                noResultsMsg.style.display = 'block';
            } else {
                noResultsMsg.style.display = 'none';
            }
        }
    }
});

// Global Post View Modal Functions (available for all sections)
function viewPostInModal(postId) {
    if (!postId || postId === 0) {
        alert('ID de post invalide');
        return;
    }
    
    // Show loading state
    const content = document.getElementById('postViewContent');
    if (!content) {
        alert('Modal non trouvée. Veuillez recharger la page.');
        return;
    }
    
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mt-2">Chargement du post...</p>
        </div>
    `;
    
    // Update link
    const link = document.getElementById('postViewLink');
    if (link) {
        link.href = '<?= $baseUrl ?>/view/frontoffice/posts.php?post_id=' + postId;
    }
    
    // Show modal
    const modalElement = document.getElementById('postViewModal');
    if (!modalElement) {
        alert('Modal non trouvée. Veuillez recharger la page.');
        return;
    }
    
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    
    // Load post data
    fetch('<?= $baseUrl ?>/index.php?action=dashboard&section=reported_posts&get_post=' + postId)
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.post) {
                const post = data.post;
                renderPostInModal(post);
            } else {
                throw new Error(data.error || 'Post introuvable');
            }
        })
        .catch(error => {
            console.error('Error loading post:', error);
            if (content) {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> 
                        Erreur lors du chargement du post: ${error.message}
                    </div>
                `;
            }
        });
}

function renderPostInModal(post) {
    const date = new Date(post.date_creation);
    const formattedDate = date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    const region = post.region ? `<span class="badge bg-info">${escapeHtmlForModal(post.region)}</span>` : '';
    
    let mediaHtml = '';
    if (post.media) {
        const mediaUrl = '<?= $baseUrl ?>/' + escapeHtmlForModal(post.media);
        mediaHtml = `
            <div class="mt-3">
                <img src="${mediaUrl}" alt="Post media" class="img-fluid rounded" style="max-height: 400px; width: auto; cursor: pointer;" onclick="window.open('${mediaUrl}', '_blank')">
            </div>
        `;
    }
    
    const description = post.description ? post.description.replace(/\n/g, '<br>') : 'Aucune description';
    
    const html = `
        <div class="post-view-container">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4 class="card-title mb-2">${escapeHtmlForModal(post.titre || 'Sans titre')}</h4>
                            <div class="text-muted small">
                                <i class="bi bi-calendar"></i> ${formattedDate}
                                ${region ? ' | ' + region : ''}
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary">Post #${post.id_post}</span>
                        </div>
                    </div>
                    
                    ${post.nom ? `
                        <div class="mb-2">
                            <strong>Nom:</strong> ${escapeHtmlForModal(post.nom)}
                        </div>
                    ` : ''}
                    
                    ${post.email ? `
                        <div class="mb-2">
                            <strong>Email:</strong> <a href="mailto:${escapeHtmlForModal(post.email)}">${escapeHtmlForModal(post.email)}</a>
                        </div>
                    ` : ''}
                    
                    ${post['Numéro'] ? `
                        <div class="mb-2">
                            <strong>Numéro:</strong> ${escapeHtmlForModal(post['Numéro'])}
                        </div>
                    ` : ''}
                    
                    <div class="mb-3">
                        <strong>Description:</strong>
                        <div class="mt-2 p-3 bg-light rounded">
                            ${escapeHtmlForModal(description)}
                        </div>
                    </div>
                    
                    ${mediaHtml}
                    
                    <div class="mt-3 d-flex gap-3 text-muted small">
                        <span><i class="bi bi-heart"></i> ${post.likes_count || 0} J'aime</span>
                        <span><i class="bi bi-chat"></i> ${post.comments_count || 0} Commentaires</span>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const content = document.getElementById('postViewContent');
    if (content) {
        content.innerHTML = html;
    }
}

function escapeHtmlForModal(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ========== ADMIN NOTIFICATION SYSTEM ==========
let adminNotificationPollInterval = null;
const ADMIN_NOTIFICATION_POLL_INTERVAL = 30000; // 30 seconds

// Load admin notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    loadAdminNotifications();
    updateAdminNotificationCount();
    
    // Start polling for new notifications
    adminNotificationPollInterval = setInterval(() => {
        updateAdminNotificationCount();
        // Reload notifications if dropdown is open
        if (document.getElementById('adminNotificationDropdown').style.display !== 'none') {
            loadAdminNotifications();
        }
    }, ADMIN_NOTIFICATION_POLL_INTERVAL);
});

// Toggle admin notification dropdown
function toggleAdminNotificationDropdown() {
    const dropdown = document.getElementById('adminNotificationDropdown');
    if (dropdown.style.display === 'none' || !dropdown.style.display) {
        dropdown.style.display = 'block';
        loadAdminNotifications();
    } else {
        dropdown.style.display = 'none';
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const container = document.querySelector('.notification-container');
    const dropdown = document.getElementById('adminNotificationDropdown');
    if (container && !container.contains(event.target) && dropdown && dropdown.style.display === 'block') {
        dropdown.style.display = 'none';
    }
});

// Load admin notifications
function loadAdminNotifications() {
    const list = document.getElementById('adminNotificationList');
    if (!list) return;
    list.innerHTML = '<div class="text-center" style="padding: 20px; color: #999;"><i class="bi bi-arrow-repeat spin"></i> Chargement...</div>';
    
    fetch('<?= $baseUrl ?>/index.php?action=api_admin_notifications&subaction=getUnread&limit=20')
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(data => {
            if (data.success && data.notifications) {
                renderAdminNotifications(data.notifications);
                updateAdminNotificationBadge(data.unread_count || 0);
            } else {
                list.innerHTML = '<div class="text-center" style="padding: 20px; color: #999;">Aucune notification</div>';
            }
        })
        .catch(error => {
            console.error('Error loading admin notifications:', error);
            if (list) list.innerHTML = '<div class="text-center" style="padding: 20px; color: #e74c3c;">Erreur de chargement</div>';
        });
}

// Render admin notifications
function renderAdminNotifications(notifications) {
    const list = document.getElementById('adminNotificationList');
    if (!list) return;
    
    if (notifications.length === 0) {
        list.innerHTML = '<div class="text-center" style="padding: 20px; color: #999;">Aucune notification</div>';
        return;
    }
    
    list.innerHTML = notifications.map(notif => {
        const unreadClass = !notif.is_read ? 'unread' : '';
        const priorityClass = notif.priority === 'high' ? 'priority-high' : '';
        const icon = getAdminNotificationIcon(notif.type);
        const bgColor = notif.priority === 'high' ? '#fff3cd' : (unreadClass ? '#f8f9fa' : '#fff');
        return `
            <div class="notification-item ${unreadClass} ${priorityClass}" onclick="handleAdminNotificationClick(${notif.id}, '${notif.entity_type}', ${notif.entity_id}, '${notif.type}')" style="padding: 12px 15px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s; background: ${bgColor}; ${unreadClass ? 'font-weight: 600;' : ''}">
                <div style="display: flex; align-items: start; gap: 10px;">
                    <i class="bi ${icon}" style="color: ${notif.priority === 'high' ? '#e74c3c' : '#667eea'}; font-size: 18px; margin-top: 2px;"></i>
                    <div style="flex: 1;">
                        <div style="font-size: 14px; color: #333; margin-bottom: 4px;">${escapeHtmlForModal(notif.message)}</div>
                        <div style="font-size: 11px; color: #999;">${notif.time_ago || ''}</div>
                    </div>
                    ${notif.priority === 'high' ? '<span style="background: #e74c3c; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: bold;">URGENT</span>' : ''}
                </div>
            </div>
        `;
    }).join('');
}

// Get admin notification icon based on type
function getAdminNotificationIcon(type) {
    const icons = {
        'admin_post_reported': 'bi-flag-fill',
        'admin_comment_reported': 'bi-chat-left-text-fill',
        'admin_user_threshold': 'bi-exclamation-triangle-fill'
    };
    return icons[type] || 'bi-bell';
}

// Handle admin notification click
function handleAdminNotificationClick(notificationId, entityType, entityId, type) {
    // Mark as read
    markAdminNotificationAsRead(notificationId);
    
    // Navigate based on notification type
    if (type === 'admin_post_reported') {
        window.location.href = '<?= $baseUrl ?>/index.php?action=dashboard&section=reported_posts';
    } else if (type === 'admin_comment_reported') {
        window.location.href = '<?= $baseUrl ?>/index.php?action=dashboard&section=comment_reports';
    } else if (type === 'admin_user_threshold') {
        window.location.href = '<?= $baseUrl ?>/index.php?action=dashboard&section=users';
    }
    
    // Close dropdown
    const dropdown = document.getElementById('adminNotificationDropdown');
    if (dropdown) dropdown.style.display = 'none';
}

// Mark admin notification as read
function markAdminNotificationAsRead(notificationId) {
    fetch('<?= $baseUrl ?>/index.php?action=api_admin_notifications&subaction=markAsRead', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateAdminNotificationCount();
            loadAdminNotifications();
        }
    })
    .catch(error => console.error('Error marking admin notification as read:', error));
}

// Mark all admin notifications as read
function markAllAdminNotificationsRead() {
    fetch('<?= $baseUrl ?>/index.php?action=api_admin_notifications&subaction=markAllAsRead', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateAdminNotificationCount();
            loadAdminNotifications();
        }
    })
    .catch(error => console.error('Error marking all admin notifications as read:', error));
}

// Update admin notification count badge
function updateAdminNotificationCount() {
    fetch('<?= $baseUrl ?>/index.php?action=api_admin_notifications&subaction=getCount')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateAdminNotificationBadge(data.count || 0);
            }
        })
        .catch(error => console.error('Error updating admin notification count:', error));
}

// Update admin badge display
function updateAdminNotificationBadge(count) {
    const badge = document.getElementById('adminNotificationBadge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Scroll to associations or cotisations section on page load
function scrollToSection() {
    const urlParams = new URLSearchParams(window.location.search);
    const action = urlParams.get('action') || '';
    const section = urlParams.get('section') || '';
    const hash = window.location.hash;
    
    // Check hash first (for direct links)
    if (hash === '#associations-section' || hash === '#cotisations-section') {
        const sectionId = hash.substring(1);
        const sectionElement = document.getElementById(sectionId);
        if (sectionElement) {
            const offsetTop = sectionElement.offsetTop - 100;
            window.scrollTo({
                top: offsetTop,
                behavior: 'smooth'
            });
            return;
        }
    }
    
    // Check section parameter (for redirects after create/update)
    if (section === 'associations') {
        console.log('Section parameter is associations, looking for associations-section');
        let attempts = 0;
        const checkSection = setInterval(function() {
            attempts++;
            const sectionElement = document.getElementById('associations-section');
            if (sectionElement && sectionElement.offsetHeight > 0) {
                clearInterval(checkSection);
                console.log('Found associations-section, scrolling...');
                const headerOffset = 100;
                const elementPosition = sectionElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                window.scrollTo({
                    top: Math.max(0, offsetPosition),
                    behavior: 'smooth'
                });
            } else if (attempts > 50) {
                clearInterval(checkSection);
                console.log('Could not find associations-section after 5 seconds.');
            }
        }, 100);
        return;
    } else if (section === 'cotisations') {
        console.log('Section parameter is cotisations, looking for cotisations-section');
        let attempts = 0;
        const checkSection = setInterval(function() {
            attempts++;
            const sectionElement = document.getElementById('cotisations-section');
            if (sectionElement && sectionElement.offsetHeight > 0) {
                clearInterval(checkSection);
                console.log('Found cotisations-section, scrolling...');
                const headerOffset = 100;
                const elementPosition = sectionElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                window.scrollTo({
                    top: Math.max(0, offsetPosition),
                    behavior: 'smooth'
                });
            } else if (attempts > 50) {
                clearInterval(checkSection);
                console.log('Could not find cotisations-section after 5 seconds.');
            }
        }, 100);
        return;
    }
    
    // Check action parameter
    if (action && action.startsWith('admin_association')) {
        console.log('Looking for associations-section, action:', action);
        // Wait for section to be rendered, try multiple times
        let attempts = 0;
        const checkSection = setInterval(function() {
            attempts++;
            const section = document.getElementById('associations-section');
            if (section && section.offsetHeight > 0) {
                clearInterval(checkSection);
                console.log('Found associations-section, scrolling...');
                // Scroll to section
                const headerOffset = 100;
                const elementPosition = section.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                window.scrollTo({
                    top: Math.max(0, offsetPosition),
                    behavior: 'smooth'
                });
            } else if (attempts > 50) {
                // Stop trying after 5 seconds (50 * 100ms)
                clearInterval(checkSection);
                console.log('Could not find associations-section after 5 seconds. Element exists:', !!section);
            }
        }, 100);
    } else if (action && action.startsWith('admin_cotisation')) {
        console.log('Looking for cotisations-section, action:', action);
        // Wait for section to be rendered, try multiple times
        let attempts = 0;
        const checkSection = setInterval(function() {
            attempts++;
            const section = document.getElementById('cotisations-section');
            if (section && section.offsetHeight > 0) {
                clearInterval(checkSection);
                console.log('Found cotisations-section, scrolling...');
                // Scroll to section
                const headerOffset = 100;
                const elementPosition = section.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                window.scrollTo({
                    top: Math.max(0, offsetPosition),
                    behavior: 'smooth'
                });
            } else if (attempts > 50) {
                // Stop trying after 5 seconds (50 * 100ms)
                clearInterval(checkSection);
                console.log('Could not find cotisations-section after 5 seconds. Element exists:', !!section);
            }
        }, 100);
    }
}

// Run on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(scrollToSection, 200);
        setTimeout(scrollToSection, 500);
        setTimeout(scrollToSection, 1000);
    });
} else {
    // DOM is already ready
    setTimeout(scrollToSection, 200);
    setTimeout(scrollToSection, 500);
    setTimeout(scrollToSection, 1000);
}

// Also try after page fully loads
window.addEventListener('load', function() {
    setTimeout(scrollToSection, 100);
});

// Listen for hash changes
window.addEventListener('hashchange', function() {
    setTimeout(scrollToSection, 200);
});
</script>

<!-- Global Post View Modal (available for all sections) -->
<div class="modal fade" id="postViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-file-post"></i> Détails du Post
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="postViewContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2">Chargement du post...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <a href="#" id="postViewLink" target="_blank" class="btn btn-primary">
                    <i class="bi bi-box-arrow-up-right"></i> Ouvrir dans une nouvelle page
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>

