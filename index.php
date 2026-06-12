<?php
/**
 * Main Router
 * Routes all requests to appropriate controllers
 */

// Enable error reporting but disable display for AJAX requests
error_reporting(E_ALL);
// Check if this is an AJAX request
$isAjaxRequest = isset($_GET['action']) && (
    strpos($_GET['action'], 'api_') === 0 || 
    strpos($_GET['action'], '_ajax') !== false ||
    strpos($_GET['action'], '_get_details') !== false ||
    $_GET['action'] === 'reclamation_get_details'
);

if ($isAjaxRequest) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
} else {
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
}

// Start output buffering to catch any accidental output
if (!ob_get_level()) {
    ob_start();
}

session_start();

// Load configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/autoload.php';

// Get action from query string, POST, or REQUEST and trim whitespace
$action = trim($_GET['action'] ?? $_POST['action'] ?? $_REQUEST['action'] ?? '');

// Debug logging
error_log("[Router] Action received: '" . $action . "' (length: " . strlen($action) . ")");
error_log("[Router] GET params: " . json_encode($_GET));
error_log("[Router] REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set'));

// Route to appropriate controller
switch ($action) {
    // Authentication routes
    case 'login':
        // Clean output buffer before login to prevent HTML errors in JSON response
        while (ob_get_level()) {
            ob_end_clean();
        }
        require_once __DIR__ . '/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->login();
        break;

    case 'signup':
        require_once __DIR__ . '/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->signup();
        break;

    case 'logout':
        require_once __DIR__ . '/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->logout();
        break;

    case 'settings':
        // Redirect to dashboard with settings section (to be implemented)
        header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=settings');
        exit;
        break;

    case 'update_profile':
        require_once __DIR__ . '/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->updateProfile();
        break;

    case 'verify_email':
        require_once __DIR__ . '/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->verifyEmail();
        break;

    // OAuth routes - MUST be before default case
    case 'google_login':
        // TEMPORARY DEBUG - Remove after testing
        if (isset($_GET['debug'])) {
            die("GOOGLE LOGIN ROUTE HIT! Action: '" . $action . "'");
        }
        error_log("[Router] ===== GOOGLE LOGIN ROUTE HIT =====");
        error_log("[Router] Action value: '" . $action . "'");
        error_log("[Router] Action type: " . gettype($action));
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        if (!defined('GOOGLE_CLIENT_ID')) {
            error_log("[Router] ERROR: GOOGLE_CLIENT_ID not defined!");
            die("OAuth configuration error. Check error logs.");
        }
        require_once __DIR__ . '/controllers/OAuthController.php';
        $controller = new OAuthController();
        $controller->googleLogin();
        // Should not reach here as googleLogin() exits
        die("ERROR: googleLogin() should have exited");
        break;

    case 'github_login':
        error_log("[Router] ===== GITHUB LOGIN ROUTE HIT =====");
        error_log("[Router] Action value: '" . $action . "'");
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        require_once __DIR__ . '/controllers/OAuthController.php';
        $controller = new OAuthController();
        $controller->githubLogin();
        // Should not reach here as githubLogin() exits
        die("ERROR: githubLogin() should have exited");
        break;

    case 'google_callback':
        require_once __DIR__ . '/controllers/OAuthController.php';
        $controller = new OAuthController();
        $controller->googleCallback();
        break;

    case 'github_callback':
        require_once __DIR__ . '/controllers/OAuthController.php';
        $controller = new OAuthController();
        $controller->githubCallback();
        break;

    // Event routes
    case 'events':
        require_once __DIR__ . '/controllers/EventController.php';
        $controller = new EventController();
        $controller->listFront();
        break;

    case 'event_show':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        require_once __DIR__ . '/controllers/EventController.php';
        $controller = new EventController();
        $controller->show($id);
        break;

    // Reservation routes
    case 'reservation_create':
        require_once __DIR__ . '/controllers/ReservationController.php';
        $controller = new ReservationController();
        $controller->create();
        break;

    case 'reservation_update':
        require_once __DIR__ . '/controllers/ReservationController.php';
        $controller = new ReservationController();
        $controller->update();
        break;

    case 'reservation_delete':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        require_once __DIR__ . '/controllers/ReservationController.php';
        $controller = new ReservationController();
        $controller->delete($id);
        break;

    // Admin routes
    case 'dashboard':
        // Check if this is a JSON request (get_post)
        if (isset($_GET['section']) && $_GET['section'] === 'reported_posts' && isset($_GET['get_post'])) {
            // Clear any output buffers before handling JSON request
            while (ob_get_level()) {
                ob_end_clean();
            }
        }
        require_once __DIR__ . '/controllers/AdminController.php';
        $controller = new AdminController();
        $controller->dashboard();
        break;

    // Chatbot route
    case 'chatbot':
        require_once __DIR__ . '/controllers/ChatbotController.php';
        $controller = new ChatbotController();
        $controller->handle();
        break;

    // API routes
    case 'api_user_stats':
        require_once __DIR__ . '/controllers/api/user_stats.php';
        exit;
    
    case 'api_user_registrations_chart':
        require_once __DIR__ . '/controllers/api/user_registrations_chart.php';
        exit;
    
    case 'api_login_activity_chart':
        require_once __DIR__ . '/controllers/api/login_activity_chart.php';
        exit;

    case 'api_user_profile':
        require_once __DIR__ . '/controllers/api/user_profile.php';
        exit;

    case 'api_search_events':
        require_once __DIR__ . '/controllers/api/search_events.php';
        exit;

    case 'api_search_reservations':
        require_once __DIR__ . '/controllers/api/search_reservations.php';
        exit;

    // Forum routes
    case 'forum_list':
        require_once __DIR__ . '/controllers/PostController.php';
        $controller = new PostController();
        $controller->listFront();
        break;

    case 'forum_show':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        require_once __DIR__ . '/controllers/PostController.php';
        $controller = new PostController();
        $controller->show($id);
        break;

    case 'forum_create':
        require_once __DIR__ . '/controllers/PostController.php';
        $controller = new PostController();
        $controller->create();
        break;

    case 'forum_update':
        require_once __DIR__ . '/controllers/PostController.php';
        $controller = new PostController();
        $controller->update();
        break;

    case 'forum_delete':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        require_once __DIR__ . '/controllers/PostController.php';
        $controller = new PostController();
        $controller->delete($id);
        break;

    case 'forum_search':
        require_once __DIR__ . '/controllers/PostController.php';
        $controller = new PostController();
        $controller->search();
        break;

    // Forum API routes
    case 'api_post_comment':
        require_once __DIR__ . '/controllers/api/post_comment.php';
        exit;

    case 'api_post_like':
        require_once __DIR__ . '/controllers/api/post_like.php';
        exit;

    case 'api_post_report':
        require_once __DIR__ . '/controllers/api/post_report.php';
        exit;

    case 'api_comment_report':
        require_once __DIR__ . '/controllers/api/comment_report.php';
        exit;

    case 'post_report':
        require_once __DIR__ . '/controllers/PostReportController.php';
        $controller = new PostReportController();
        $action = $_GET['action'] ?? 'report';
        if ($action === 'check') {
            $controller->check();
        } else {
            $controller->report();
        }
        exit;

    case 'api_post_save':
        require_once __DIR__ . '/controllers/api/post_save.php';
        exit;

    // Notification routes
    case 'api_notifications':
        require_once __DIR__ . '/controllers/NotificationController.php';
        $controller = new NotificationController();
        $action = $_GET['subaction'] ?? $_GET['action'] ?? 'getUnread';
        switch ($action) {
            case 'getUnread':
                $controller->getUnread();
                break;
            case 'getAll':
                $controller->getAll();
                break;
            case 'markAsRead':
                $controller->markAsRead();
                break;
            case 'markAllAsRead':
                $controller->markAllAsRead();
                break;
            case 'getCount':
                $controller->getCount();
                break;
            default:
                $controller->getUnread();
        }
        exit;

    case 'api_admin_notifications':
        require_once __DIR__ . '/controllers/AdminNotificationController.php';
        $controller = new AdminNotificationController();
        $action = $_GET['subaction'] ?? $_GET['action'] ?? 'getUnread';
        switch ($action) {
            case 'getUnread':
                $controller->getUnread();
                break;
            case 'markAsRead':
                $controller->markAsRead();
                break;
            case 'markAllAsRead':
                $controller->markAllAsRead();
                break;
            case 'getCount':
                $controller->getCount();
                break;
            default:
                $controller->getUnread();
        }
        exit;

    // Messaging routes
    case 'api_message':
        // Clean output buffers before including API
        while (ob_get_level()) {
            ob_end_clean();
        }
        require_once __DIR__ . '/controllers/api/message.php';
        exit;

    case 'messages':
        require_once __DIR__ . '/view/frontoffice/messages.php';
        exit;

    case 'admin_messages':
        require_once __DIR__ . '/controllers/AdminMessageController.php';
        $controller = new AdminMessageController();
        $action = $_GET['action'] ?? 'getAllConversations';
        switch ($action) {
            case 'getAllConversations':
                $controller->getAllConversations();
                break;
            case 'getConversationMessages':
                $controller->getConversationMessages();
                break;
            case 'blockConversation':
                $controller->blockConversation();
                break;
            case 'getReportedConversations':
                $controller->getReportedConversations();
                break;
            default:
                $controller->getAllConversations();
        }
        exit;

    // Reclamation routes (Front Office)
    case 'reclamations':
        require_once __DIR__ . '/controllers/ReclamationController.php';
        $controller = new ReclamationController();
        $controller->index();
        break;

    case 'reclamation_submit':
        require_once __DIR__ . '/controllers/ReclamationController.php';
        $controller = new ReclamationController();
        $controller->submit();
        break;

    case 'reclamation_view':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        require_once __DIR__ . '/controllers/ReclamationController.php';
        $controller = new ReclamationController();
        $controller->view($id);
        break;

    case 'reclamation_get_details':
        // Disable error display immediately
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        error_reporting(E_ALL);
        
        // Start output buffering to catch any accidental output
        if (!ob_get_level()) {
            ob_start();
        }
        
        // Clean any existing output buffers
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        
        try {
            require_once __DIR__ . '/controllers/ReclamationController.php';
            $controller = new ReclamationController(true); // Skip auth check, will be done in method
            $controller->getDetailsAjax();
        } catch (Throwable $e) {
            // Clean any output
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            error_log("Error in reclamation_get_details: " . $e->getMessage());
            error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;

    case 'reclamation_update':
        require_once __DIR__ . '/controllers/ReclamationController.php';
        $controller = new ReclamationController();
        $controller->update();
        break;

    case 'reclamation_delete':
        // Get ID from GET, POST, or REQUEST
        $id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : (isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0));
        // Clear output buffers for AJAX response
        while (ob_get_level()) {
            ob_end_clean();
        }
        require_once __DIR__ . '/controllers/ReclamationController.php';
        $controller = new ReclamationController();
        $controller->delete($id);
        exit; // Exit after delete to prevent further execution
        break;

    // Donation routes (Front Office)
    case 'donations':
        require_once __DIR__ . '/controllers/DonationController.php';
        $controller = new DonationController();
        $controller->index();
        break;

    case 'donation_show':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        require_once __DIR__ . '/controllers/DonationController.php';
        $controller = new DonationController();
        $controller->show($id);
        break;

    case 'my_donations':
        require_once __DIR__ . '/controllers/DonationController.php';
        $controller = new DonationController();
        $controller->myDonations();
        break;

    case 'donation_submit':
        require_once __DIR__ . '/controllers/DonationController.php';
        $controller = new DonationController();
        $controller->submit();
        break;

    case 'donation_request':
        // Clean output buffers for AJAX request
        while (ob_get_level()) {
            ob_end_clean();
        }
        // Disable error display for JSON response
        ini_set('display_errors', 0);
        error_reporting(E_ALL);
        ini_set('log_errors', 1);
        
        require_once __DIR__ . '/controllers/DonationController.php';
        try {
            $controller = new DonationController();
            $controller->request();
        } catch (Throwable $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false, 
                'error' => 'Erreur serveur: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            error_log("Donation request error: " . $e->getMessage());
            exit;
        }
        break;

    case 'donation_update_request_status':
        require_once __DIR__ . '/controllers/DonationController.php';
        $controller = new DonationController();
        $controller->updateRequestStatus();
        break;

    case 'donation_mark_fulfilled':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        require_once __DIR__ . '/controllers/DonationController.php';
        $controller = new DonationController();
        $controller->markFulfilled($id);
        break;

    case 'donation_delete':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        require_once __DIR__ . '/controllers/DonationController.php';
        $controller = new DonationController();
        $controller->delete($id);
        break;

    // Association routes (Front Office)
    case 'associations':
        require_once __DIR__ . '/controllers/AssociationController.php';
        $controller = new AssociationController();
        $controller->index();
        break;

    case 'association_show':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        require_once __DIR__ . '/controllers/AssociationController.php';
        $controller = new AssociationController();
        $controller->show($id);
        break;

    case 'association_join':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        require_once __DIR__ . '/controllers/AssociationController.php';
        $controller = new AssociationController();
        $controller->join($id);
        break;

    // Cotisation routes (Front Office)
    case 'cotisations':
        require_once __DIR__ . '/controllers/CotisationController.php';
        $controller = new CotisationController();
        $controller->index();
        break;

    case 'cotisation_history':
        require_once __DIR__ . '/controllers/CotisationController.php';
        $controller = new CotisationController();
        $controller->history();
        break;

    case 'cotisation_pay':
        require_once __DIR__ . '/controllers/CotisationController.php';
        $controller = new CotisationController();
        $controller->pay();
        break;

    // Admin Donation routes (Back Office)
    case 'admin_donation_delete':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        require_once __DIR__ . '/controllers/AdminDonationController.php';
        $controller = new AdminDonationController();
        $controller->delete($id);
        break;

    case 'admin_donation_update_status':
        require_once __DIR__ . '/controllers/AdminDonationController.php';
        $controller = new AdminDonationController();
        $controller->updateStatus();
        break;

    case 'admin_donation_update_quantity':
        require_once __DIR__ . '/controllers/AdminDonationController.php';
        $controller = new AdminDonationController();
        $controller->updateQuantity();
        break;

    case 'admin_donation_request_status':
        require_once __DIR__ . '/controllers/AdminDonationController.php';
        $controller = new AdminDonationController();
        $controller->updateRequestStatus();
        break;

    // Admin Reclamation routes
    case 'admin_reclamation_update_status':
        require_once __DIR__ . '/controllers/AdminReclamationController.php';
        $controller = new AdminReclamationController();
        $controller->updateStatus();
        break;

    case 'admin_reclamation_add_response':
        require_once __DIR__ . '/controllers/AdminReclamationController.php';
        $controller = new AdminReclamationController();
        $controller->addResponse();
        break;

    case 'admin_reclamation_delete':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        require_once __DIR__ . '/controllers/AdminReclamationController.php';
        $controller = new AdminReclamationController();
        $controller->delete($id);
        break;

    case 'admin_reclamation_details':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        require_once __DIR__ . '/controllers/AdminReclamationController.php';
        $controller = new AdminReclamationController();
        $controller->getDetails($id);
        break;

    case 'api_admin_reclamations':
        require_once __DIR__ . '/controllers/AdminReclamationController.php';
        $controller = new AdminReclamationController();
        $controller->getReclamationsAjax();
        break;

    // Static pages route
    case 'page':
        $pageName = $_GET['name'] ?? 'about';
        $allowedPages = ['about', 'mission', 'how-it-works', 'help', 'contact', 'report', 'transparency', 'privacy', 'terms'];
        if (in_array($pageName, $allowedPages)) {
            $pagePath = __DIR__ . '/view/pages/' . $pageName . '.php';
            if (file_exists($pagePath)) {
                require_once $pagePath;
                exit;
            }
        }
        http_response_code(404);
        die("Page not found");
        break;

    // Homepage route (public landing page)
    case 'homepage':
        $homepagePath = __DIR__ . '/view/frontoffice/homepage.php';
        if (!file_exists($homepagePath)) {
            http_response_code(500);
            die("Error: Homepage file not found at: " . $homepagePath);
        }
        require_once $homepagePath;
        exit;

    // Default: show homepage or redirect based on authentication status
    default:
        // Only redirect if action is empty (not set)
        if (empty($action)) {
        // Check if user is logged in
        if (isset($_SESSION['userID']) && isset($_SESSION['role'])) {
            if ($_SESSION['role'] === 'admin') {
                header('Location: ' . BASE_URL . '/index.php?action=dashboard');
            } else {
                // Logged-in users go to their main page (not homepage)
                header('Location: ' . BASE_URL . '/view/frontoffice/index.php');
            }
            exit;
        } else {
            // Not logged in - show public homepage directly
            $homepagePath = __DIR__ . '/view/frontoffice/homepage.php';
            if (file_exists($homepagePath)) {
                require_once $homepagePath;
            } else {
                die("Error: Homepage file not found.");
                }
                exit;
            }
        } else {
            // Unknown action - show error or redirect
            error_log("[Router] Unknown action: '$action'");
            if (isset($_SESSION['userID']) && isset($_SESSION['role'])) {
                if ($_SESSION['role'] === 'admin') {
                    header('Location: ' . BASE_URL . '/index.php?action=dashboard');
                } else {
                    header('Location: ' . BASE_URL . '/view/frontoffice/index.php');
                }
            } else {
                header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
            }
            exit;
        }
}

