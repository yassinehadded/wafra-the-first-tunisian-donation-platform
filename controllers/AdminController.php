<?php
/**
 * Admin Controller
 * Handles admin dashboard and management operations
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Reservation.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Settings.php';

class AdminController {
    private $pdo;
    private $eventModel;
    private $reservationModel;
    private $userModel;
    private $settingsModel;

    public function __construct() {
        // Session is already started in index.php
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is admin
        if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
            exit;
        }

        $this->pdo = Database::connect();
        $this->eventModel = new Event($this->pdo);
        $this->reservationModel = new Reservation($this->pdo);
        $this->userModel = new User($this->pdo);
        $this->settingsModel = new Settings($this->pdo);
    }

    /**
     * Display admin dashboard
     */
    public function dashboard() {
        $section = $_GET['section'] ?? $_POST['section'] ?? 'dashboard';
        $action = $_GET['section_action'] ?? $_POST['section_action'] ?? 'list';

        // Handle special GET requests that return JSON/HTML (must be before POST handling)
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Handle get single post for modal (reported_posts section)
            if ($section === 'reported_posts' && isset($_GET['get_post'])) {
                // Clear any output buffers completely
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Disable error display
                ini_set('display_errors', 0);
                ini_set('display_startup_errors', 0);
                
                // Set JSON header
                header('Content-Type: application/json; charset=utf-8');
                
                // Prevent any output
                error_reporting(0);
                
                require_once __DIR__ . '/../models/Post.php';
                $postId = (int)$_GET['get_post'];
                $postModel = new Post($this->pdo);
                $post = $postModel->find($postId);
                
                if ($post) {
                    // Get user info
                    $userStmt = $this->pdo->prepare("SELECT firstname, lastname, email FROM users WHERE cin = :cin");
                    $userStmt->execute([':cin' => (int)$post['id_user']]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        $post['user_name'] = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
                        $post['user_email'] = $user['email'] ?? '';
                    }
                    
                    $response = [
                        'success' => true,
                        'post' => $post
                    ];
                } else {
                    // Post not found - it may have been deleted
                    // Also delete any reports for this post if they exist
                    require_once __DIR__ . '/../models/PostReport.php';
                    $postReportModel = new PostReport($this->pdo);
                    $postReportModel->deleteReportsByPost($postId);
                    
                    http_response_code(404);
                    $response = [
                        'success' => false,
                        'error' => 'Post not found (may have been deleted)'
                    ];
                }
                
                // Output JSON and exit immediately
                echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
        }

        // Handle POST requests (but let dashboard.php handle associations and cotisations)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            error_log("AdminController::dashboard() - POST request - Section: $section, Action: $action");
            // For associations and cotisations, let dashboard.php handle POST requests
            if ($section === 'associations' || $section === 'cotisations') {
                // Don't call handlePostRequest, let dashboard.php handle it
                // Continue to load dashboard.php below
            } else {
                $this->handlePostRequest($section, $action);
                return;
            }
        }

        // Handle DELETE requests
        if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
            $deleteSection = $_GET['section'] ?? $section;
            $this->handleDeleteRequest($deleteSection);
            return;
        }

        // Load data based on section
        $data = $this->loadSectionData($section, $action);
        $data['current_section'] = $section;
        $data['current_action'] = $action;

        extract($data);
        require __DIR__ . '/../view/backoffice/dashboard.php';
    }

    /**
     * Load data for different sections
     */
    private function loadSectionData($section, $action) {
        $data = [];
        $data['stats'] = $this->getStatistics();

        switch ($section) {
            case 'dashboard':
                // Load all reservations with search term support
                $searchTerm = $_GET['search'] ?? '';
                $data['allReservations'] = $this->reservationModel->selectAllWithEvent(null, $searchTerm);
                $data['latestReservations'] = $data['allReservations']; // Keep for backward compatibility
                error_log("AdminController: Loaded " . count($data['allReservations']) . " reservations for dashboard");
                if (count($data['allReservations']) > 0) {
                    error_log("AdminController: First reservation: " . json_encode($data['allReservations'][0]));
                }
                $result = $this->eventModel->getAll(1, 10);
                $data['latestEvents'] = $result['data'] ?? [];
                $data['eventsByType'] = $this->getEventsByType();
                $data['reservationsByMonth'] = $this->getReservationsByMonth();
                $data['latestUsers'] = $this->getLatestUsers(5);
                $data['allUsers'] = $this->getAllUsers();
                $data['loginSessions'] = $this->getLoginSessions(50);
                break;
            
            case 'users':
                $searchTerm = $_GET['search'] ?? '';
                $data['allUsers'] = $this->getAllUsers($searchTerm);
                if (isset($_GET['edit_id'])) {
                    $editId = (int)$_GET['edit_id'];
                    $data['userToEdit'] = $this->userModel->getUserByCin($editId);
                }
                break;
            
            case 'login_sessions':
                $searchTerm = $_GET['search'] ?? '';
                $data['loginSessions'] = $this->getLoginSessions(100, $searchTerm);
                break;

            case 'reported_posts':
                require_once __DIR__ . '/../models/PostReport.php';
                require_once __DIR__ . '/../models/Post.php';
                $reportModel = new PostReport($this->pdo);
                
                // Handle get single report for modal
                if (isset($_GET['get_report'])) {
                    // Clear any output buffers
                    while (ob_get_level()) {
                        ob_end_clean();
                    }
                    $reportId = (int)$_GET['get_report'];
                    $report = $reportModel->find($reportId);
                    if ($report) {
                        $reportsForPost = $reportModel->getReportsByPost($report['id_post']);
                        require __DIR__ . '/../view/backoffice/report_details.php';
                        exit;
                    }
                }
                
                // Note: get_post is now handled in dashboard() method before loadSectionData() is called
                // This ensures no HTML is output before the JSON response
                
                $status = $_GET['status'] ?? null;
                $pageNum = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
                $limit = 20;
                $offset = ($pageNum - 1) * $limit;
                
                // Get all reports without pagination first to get total count
                $allReports = $reportModel->getAll($status, 10000, 0); // Get all for count
                $data['totalReports'] = count($allReports);
                
                // Get paginated reports
                $data['reports'] = $reportModel->getAll($status, $limit, $offset);
                $data['pendingCount'] = $this->getPendingReportsCount();
                $data['currentPage'] = $pageNum;
                $data['totalPages'] = ceil($data['totalReports'] / $limit);
                break;
            
            case 'comment_reports':
                require_once __DIR__ . '/../models/CommentReport.php';
                require_once __DIR__ . '/../services/PostCommentService.php';
                $commentReportModel = new CommentReport($this->pdo);
                $commentService = new PostCommentService($this->pdo);
                
                // Handle get single report for modal
                if (isset($_GET['get_report'])) {
                    $reportId = (int)$_GET['get_report'];
                    $report = $commentReportModel->find($reportId);
                    if ($report) {
                        $reportsForComment = $commentReportModel->getReportsByComment($report['id_comment']);
                        require __DIR__ . '/../view/backoffice/comment_report_details.php';
                        exit;
                    }
                }
                
                // Handle delete comment action
                if (isset($_GET['delete_comment'])) {
                    $commentId = (int)$_GET['delete_comment'];
                    if ($commentService->deleteCommentById($commentId)) {
                        $_SESSION['success'] = 'Commentaire supprimé avec succès.';
                    } else {
                        $_SESSION['errors'] = ['Erreur lors de la suppression du commentaire.'];
                    }
                    header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=comment_reports');
                    exit;
                }
                
                $status = $_GET['status'] ?? null;
                $pageNum = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
                $limit = 20;
                $offset = ($pageNum - 1) * $limit;
                
                $allReports = $commentReportModel->getAll($status, 10000, 0);
                $data['totalReports'] = count($allReports);
                $data['reports'] = $commentReportModel->getAll($status, $limit, $offset);
                $data['pendingCount'] = $commentReportModel->getPendingCount();
                $data['currentPage'] = $pageNum;
                $data['totalPages'] = ceil($data['totalReports'] / $limit);
                break;

            case 'events':
                if ($action === 'list') {
                    $pageNum = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
                    $searchTerm = $_GET['search'] ?? '';
                    $result = $this->eventModel->getAll($pageNum, 10, $searchTerm);
                    $data['evenements'] = $result['data'] ?? [];
                    $data['pagination'] = [
                        'currentPage' => $pageNum,
                        'totalPages' => $result['totalPages'] ?? 1,
                        'totalItems' => $result['total'] ?? 0,
                        'searchTerm' => $searchTerm
                    ];
                } elseif ($action === 'create' || $action === 'edit') {
                    if ($action === 'edit' && isset($_GET['id'])) {
                        $eventData = $this->eventModel->find((int)$_GET['id']);
                        $data['event'] = $eventData;
                        $data['evenement'] = $eventData; // Also set evenement for compatibility
                        error_log("AdminController: Loaded event for edit - ID: " . $_GET['id'] . ", Data: " . json_encode($eventData));
                    }
                }
                break;

            case 'reservations':
                if ($action === 'list') {
                    $searchTerm = $_GET['search'] ?? '';
                    $data['allReservations'] = $this->reservationModel->selectAllWithEvent(null, $searchTerm);
                    $data['reservations'] = $data['allReservations']; // Keep for backward compatibility
                    error_log("AdminController: Loaded " . count($data['allReservations']) . " reservations for reservations section");
                } elseif ($action === 'create' || $action === 'edit') {
                    $result = $this->eventModel->getAll(1, 100);
                    $data['allEvents'] = $result['data'] ?? [];
                    $data['allEvenements'] = $result['data'] ?? []; // Also set allEvenements for compatibility
                    if ($action === 'edit' && isset($_GET['id'])) {
                        $reservationData = $this->reservationModel->select((int)$_GET['id']);
                        $data['reservation'] = $reservationData;
                        $data['reservationToEdit'] = $reservationData; // Also set reservationToEdit for compatibility
                        error_log("AdminController: Loaded reservation for edit - ID: " . $_GET['id'] . ", Data: " . json_encode($reservationData));
                        error_log("AdminController: Loaded " . count($data['allEvenements']) . " events for dropdown");
                    }
                }
                break;

            case 'reclamations':
                require_once __DIR__ . '/../models/Reclamation.php';
                $reclamationModel = new Reclamation($this->pdo);
                $filters = [
                    'priorite' => $_GET['priorite'] ?? '',
                    'statut' => $_GET['statut'] ?? '',
                    'type' => $_GET['type'] ?? '',
                    'search' => $_GET['search'] ?? ''
                ];
                $data['reclamations'] = $reclamationModel->getAllReclamations($filters);
                $data['stats'] = $reclamationModel->getStats();
                $data['filters'] = $filters;
                break;
            
            case 'donations':
                require_once __DIR__ . '/../models/Donation.php';
                $donationModel = new Donation($this->pdo);
                $filters = [
                    'status' => $_GET['status'] ?? '',
                    'category' => $_GET['category'] ?? '',
                    'search' => $_GET['search'] ?? ''
                ];
                $data['donations'] = $donationModel->getAllDonations($filters);
                $data['stats'] = $donationModel->getStats();
                $data['filters'] = $filters;
                break;
        }

        return $data;
    }

    /**
     * Get pending reports count
     */
    private function getPendingReportsCount() {
        try {
            require_once __DIR__ . '/../models/PostReport.php';
            $reportModel = new PostReport($this->pdo);
            $reports = $reportModel->getAll('pending');
            return count($reports);
        } catch (Exception $e) {
            error_log("Error getting pending reports count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Handle POST requests
     */
    private function handlePostRequest($section, $action) {
        // Handle report status updates
        if ($section === 'reported_posts' && isset($_POST['update_report_status'])) {
            require_once __DIR__ . '/../models/PostReport.php';
            $reportModel = new PostReport($this->pdo);
            $reportId = (int)$_POST['report_id'];
            $status = $_POST['status'] ?? 'reviewed';
            $adminNotes = $_POST['admin_notes'] ?? '';
            $adminId = (int)$_SESSION['userID'];
            
            if ($reportModel->updateStatus($reportId, $status, $adminId, $adminNotes)) {
                $_SESSION['success'] = 'Statut du signalement mis à jour avec succès.';
            } else {
                $_SESSION['errors'] = ['Erreur lors de la mise à jour du statut.'];
            }
            
            header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=reported_posts');
            exit;
        }
        
        // Handle comment report status updates
        if ($section === 'comment_reports' && isset($_POST['update_report_status'])) {
            require_once __DIR__ . '/../models/CommentReport.php';
            $reportModel = new CommentReport($this->pdo);
            $reportId = (int)$_POST['report_id'];
            $status = $_POST['status'] ?? 'reviewed';
            $adminNotes = $_POST['admin_notes'] ?? '';
            $adminId = (int)$_SESSION['userID'];
            
            if ($reportModel->updateStatus($reportId, $status, $adminId, $adminNotes)) {
                $_SESSION['success'] = 'Statut du signalement mis à jour avec succès.';
            } else {
                $_SESSION['errors'] = ['Erreur lors de la mise à jour du statut.'];
            }
            
            header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=comment_reports');
            exit;
        }
        
        // For associations and cotisations, let dashboard.php handle it
        if ($section === 'associations' || $section === 'cotisations') {
            // Don't handle here, let dashboard.php handle it
            return;
        }
        
        switch ($section) {
            case 'events':
                if ($action === 'create') {
                    $this->createEvent();
                } elseif ($action === 'edit') {
                    $this->updateEvent();
                }
                break;

            case 'users':
                if ($action === 'edit') {
                    $this->updateUser();
                }
                break;

            case 'reservations':
                if ($action === 'create') {
                    $this->createReservation();
                } elseif ($action === 'edit') {
                    $this->updateReservation();
                }
                break;

            case 'settings':
                if ($action === 'update' || $action === 'save') {
                    $this->updateSettings();
                }
                break;
        }
    }

    /**
     * Handle DELETE requests
     */
    private function handleDeleteRequest($section) {
        $id = (int)$_GET['delete_id'];
        $type = $_GET['type'] ?? 'reservation';
        
        // Get section from URL if not provided
        if (empty($section)) {
            $section = $_GET['section'] ?? 'reservations';
        }

        if ($type === 'event') {
            $this->eventModel->delete($id);
            header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=events&section_action=list');
        } elseif ($type === 'user') {
            try {
                // Prevent deleting own account
                if ($id == $_SESSION['userID']) {
                    $_SESSION['errors'] = ['Vous ne pouvez pas supprimer votre propre compte.'];
                    header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=users');
                    exit;
                }
                // Delete user
                $stmt = $this->pdo->prepare("DELETE FROM users WHERE cin = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = 'Utilisateur supprimé avec succès.';
            } catch (Exception $e) {
                $_SESSION['errors'] = ['Erreur lors de la suppression : ' . $e->getMessage()];
            }
            header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=users');
        } else {
            $this->reservationModel->delete($id);
            header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=reservations&section_action=list');
        }
        exit;
    }

    /**
     * Create event
     */
    private function createEvent() {
        // Enable error display for debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Start output buffering to catch any errors
        ob_start();
        
        try {
            // Log to a file we can check
            $logFile = __DIR__ . '/../debug.log';
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - AdminController::createEvent() - POST data: " . json_encode($_POST) . "\n", FILE_APPEND);
            
            // Map form field names (camelCase) to database field names (snake_case)
            $data = [
                'nom_evenement' => trim($_POST['nomEvenement'] ?? ''),
                'type_evenement' => trim($_POST['typeEvenement'] ?? ''),
                'date_debut' => trim($_POST['dateDebut'] ?? ''),
                'date_fin' => trim($_POST['dateFin'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'lieu' => !empty($_POST['lieu']) ? trim($_POST['lieu']) : null,
                'latitude' => !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null,
                'longitude' => !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null
            ];

            file_put_contents($logFile, date('Y-m-d H:i:s') . " - AdminController::createEvent() - Mapped data: " . json_encode($data) . "\n", FILE_APPEND);

            // Validate required fields
            if (empty($data['nom_evenement'])) {
                throw new Exception("Le nom de l'événement est requis.");
            }
            if (empty($data['type_evenement'])) {
                throw new Exception("Le type d'événement est requis.");
            }
            if (empty($data['date_debut'])) {
                throw new Exception("La date de début est requise.");
            }
            if (empty($data['date_fin'])) {
                throw new Exception("La date de fin est requise.");
            }

            file_put_contents($logFile, date('Y-m-d H:i:s') . " - AdminController::createEvent() - Calling eventModel->create()\n", FILE_APPEND);
            $result = $this->eventModel->create($data);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - AdminController::createEvent() - Result: " . var_export($result, true) . "\n", FILE_APPEND);

            if ($result) {
                $_SESSION['success'] = 'Événement créé avec succès.';
                ob_end_clean();
                header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=events&section_action=list&success=created');
                exit;
            } else {
                throw new Exception("Erreur lors de la création de l'événement. Vérifiez les logs pour plus de détails.");
            }
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            $logFile = __DIR__ . '/../debug.log';
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - AdminController::createEvent() - Exception: " . $errorMsg . "\n", FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
            $_SESSION['errors'] = [$errorMsg];
            ob_end_clean();
            header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=events&section_action=create&error=1');
            exit;
        } catch (Error $e) {
            $errorMsg = $e->getMessage();
            $logFile = __DIR__ . '/../debug.log';
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - AdminController::createEvent() - Fatal Error: " . $errorMsg . "\n", FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
            $_SESSION['errors'] = ['Erreur fatale lors de la création de l\'événement: ' . $errorMsg];
            ob_end_clean();
            header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=events&section_action=create&error=1');
            exit;
        }
    }

    /**
     * Update event
     */
    private function updateEvent() {
        try {
            $id = (int)$_POST['id'];
            
            // Map form field names (camelCase) to database field names (snake_case)
            $data = [
                'nom_evenement' => $_POST['nomEvenement'] ?? '',
                'type_evenement' => $_POST['typeEvenement'] ?? '',
                'date_debut' => $_POST['dateDebut'] ?? '',
                'date_fin' => $_POST['dateFin'] ?? '',
                'description' => $_POST['description'] ?? '',
                'lieu' => $_POST['lieu'] ?? null,
                'latitude' => !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null,
                'longitude' => !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null
            ];

            // Validate required fields
            if (empty($data['nom_evenement'])) {
                throw new Exception("Le nom de l'événement est requis.");
            }
            if (empty($data['type_evenement'])) {
                throw new Exception("Le type d'événement est requis.");
            }
            if (empty($data['date_debut'])) {
                throw new Exception("La date de début est requise.");
            }
            if (empty($data['date_fin'])) {
                throw new Exception("La date de fin est requise.");
            }

            if ($this->eventModel->update($id, $data)) {
                $_SESSION['success'] = 'Événement mis à jour avec succès.';
                header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=events&section_action=list&success=updated');
            } else {
                throw new Exception("Erreur lors de la mise à jour de l'événement.");
            }
        } catch (Exception $e) {
            $_SESSION['errors'] = [$e->getMessage()];
            error_log("Error updating event: " . $e->getMessage());
            header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=events&section_action=edit&id=' . $id . '&error=1');
        }
        exit;
    }

    /**
     * Update user
     */
    private function updateUser() {
        try {
            $cin = (int)$_POST['cin'];
            $firstname = $_POST['firstname'];
            $lastname = $_POST['lastname'];
            $email = $_POST['email'];
            $role = $_POST['role'];
            $emailVerified = isset($_POST['email_verified']) ? (int)$_POST['email_verified'] : 0;

            $this->userModel->updateUser($cin, $firstname, $lastname, $email, $role);
            
            // Update email_verified if provided
            if (isset($_POST['email_verified'])) {
                $stmt = $this->pdo->prepare("UPDATE users SET email_verified = ? WHERE cin = ?");
                $stmt->execute([$emailVerified, $cin]);
            }

            $_SESSION['success'] = 'Utilisateur mis à jour avec succès.';
            header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=users');
        } catch (Exception $e) {
            $_SESSION['errors'] = ['Erreur lors de la mise à jour : ' . $e->getMessage()];
            header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=users&edit_id=' . (int)$_POST['cin']);
        }
        exit;
    }

    /**
     * Create reservation
     */
    private function createReservation() {
        try {
            $data = [
                'evenement_id' => $_POST['evenement_id'],
                'nom' => $_POST['nom'],
                'tel' => $_POST['tel'],
                'lieu' => $_POST['lieu'],
                'date_naissance' => $_POST['date_naissance'],
                'softskills' => $_POST['softskills'],
                'email' => $_POST['email'] ?? null
            ];

            $this->reservationModel->insert($data);
            $_SESSION['success'] = 'Réservation créée avec succès.';
            header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=reservations&section_action=list&success=created');
        } catch (Exception $e) {
            header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=reservations&section_action=create&error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    /**
     * Update reservation
     */
    private function updateReservation() {
        try {
            $id = (int)$_POST['id'];
            $data = [
                'evenement_id' => $_POST['evenement_id'],
                'nom' => $_POST['nom'],
                'tel' => $_POST['tel'],
                'lieu' => $_POST['lieu'],
                'date_naissance' => $_POST['date_naissance'],
                'softskills' => $_POST['softskills'],
                'email' => $_POST['email'] ?? null
            ];

            $this->reservationModel->update($id, $data);
            $_SESSION['success'] = 'Réservation mise à jour avec succès.';
            header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=reservations&section_action=list&success=updated');
        } catch (Exception $e) {
            header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=reservations&section_action=edit&id=' . $id . '&error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    /**
     * Get statistics
     */
    private function getStatistics() {
        // Total users from users table
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM users");
        $totalUsers = (int)$stmt->fetch()['total'];
        
        // Total events
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM evenements");
        $totalEvents = (int)$stmt->fetch()['total'];
        
        // Total reservations
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM reservations");
        $totalReservations = (int)$stmt->fetch()['total'];
        
        return [
            'total_users' => $totalUsers,
            'total_events' => $totalEvents,
            'total_reservations' => $totalReservations
        ];
    }
    
    /**
     * Get events by type for chart
     */
    private function getEventsByType() {
        $stmt = $this->pdo->query("
            SELECT type_evenement, COUNT(*) as count 
            FROM evenements 
            GROUP BY type_evenement
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get reservations by month for chart
     */
    private function getReservationsByMonth() {
        $stmt = $this->pdo->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count
            FROM reservations
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month
            ORDER BY month ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get latest users
     */
    private function getLatestUsers($limit = 5) {
        $stmt = $this->pdo->prepare("
            SELECT u.cin, u.firstname, u.lastname, u.email, u.role, u.created_at,
                   COUNT(DISTINCT r.id) as reservation_count
            FROM users u
            LEFT JOIN reservations r ON r.cin = u.cin
            GROUP BY u.cin, u.firstname, u.lastname, u.email, u.role, u.created_at
            ORDER BY u.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all users
     */
    private function getAllUsers($searchTerm = '') {
        $sql = "SELECT u.*, COUNT(DISTINCT r.id) as reservation_count
                FROM users u
                LEFT JOIN reservations r ON r.cin = u.cin";
        
        $params = [];
        if (!empty($searchTerm)) {
            $sql .= " WHERE u.firstname LIKE :search1 OR u.lastname LIKE :search2 OR u.email LIKE :search3";
            $searchPattern = "%$searchTerm%";
            $params[':search1'] = $searchPattern;
            $params[':search2'] = $searchPattern;
            $params[':search3'] = $searchPattern;
        }
        
        $sql .= " GROUP BY u.cin, u.firstname, u.lastname, u.email, u.role, u.created_at, u.updated_at, u.email_verified, u.verification_token, u.reset_token, u.reset_expires_at, u.token_expiry, u.profile_picture
                  ORDER BY u.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get login sessions
     */
    private function getLoginSessions($limit = 50, $searchTerm = '') {
        $sql = "SELECT ls.*, u.firstname, u.lastname, u.email, u.role
                FROM loginsession ls
                LEFT JOIN users u ON u.cin = ls.userID";
        
        $params = [];
        $paramIndex = 1;
        
        if (!empty($searchTerm)) {
            $sql .= " WHERE u.firstname LIKE ? OR u.lastname LIKE ? OR u.email LIKE ? OR ls.ipAddress LIKE ?";
            $searchValue = "%$searchTerm%";
            $params[] = $searchValue;
            $params[] = $searchValue;
            $params[] = $searchValue;
            $params[] = $searchValue;
            $paramIndex = 5;
        }
        
        $sql .= " ORDER BY ls.loginTime DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update settings
     */
    private function updateSettings() {
        // CSRF validation
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['errors'] = ['Invalid request token. Please try again.'];
            header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=settings');
            exit;
        }

        try {
            // Validate and sanitize inputs
            $siteName = trim($_POST['site_name'] ?? '');
            $contactEmail = trim($_POST['contact_email'] ?? '');
            $maintenanceMode = isset($_POST['maintenance_mode']) ? 1 : 0;
            $recaptchaSiteKey = trim($_POST['recaptcha_site_key'] ?? '');
            $recaptchaSecretKey = trim($_POST['recaptcha_secret_key'] ?? '');
            $sessionTimeout = (int)($_POST['session_timeout_minutes'] ?? 30);
            $emailNotificationsEnabled = isset($_POST['email_notifications_enabled']) ? 1 : 0;
            $emailSenderName = trim($_POST['email_sender_name'] ?? '');
            $emailSenderEmail = trim($_POST['email_sender_email'] ?? '');
            $emailTemplateWelcome = trim($_POST['email_template_welcome'] ?? '');
            $emailTemplateDonation = trim($_POST['email_template_donation'] ?? '');

            if ($siteName === '') {
                throw new Exception('Site name is required.');
            }

            if ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Contact email is invalid.');
            }

            if ($emailSenderEmail !== '' && !filter_var($emailSenderEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Sender email is invalid.');
            }

            // Clamp session timeout
            if ($sessionTimeout < 5) $sessionTimeout = 5;
            if ($sessionTimeout > 1440) $sessionTimeout = 1440;

            // Handle logo upload
            $currentSettings = $this->settingsModel->getSettings();
            $logoPath = $currentSettings['site_logo_path'] ?? null;

            if (!empty($_FILES['site_logo']['name']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['site_logo']['tmp_name'];
                $fileSize = (int)$_FILES['site_logo']['size'];
                $allowedMime = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/svg+xml' => 'svg', 'image/jpg' => 'jpg'];

                // Size limit 2MB
                if ($fileSize > 2 * 1024 * 1024) {
                    throw new Exception('Logo is too large. Max 2MB.');
                }

                // Validate MIME
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmpName);
                finfo_close($finfo);
                if (!array_key_exists($mime, $allowedMime)) {
                    throw new Exception('Invalid logo format. Allowed: PNG, JPG, SVG.');
                }

                $ext = $allowedMime[$mime];
                $newName = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destDir = __DIR__ . '/../uploads/site';
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0775, true);
                }

                $destPath = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newName;
                if (!move_uploaded_file($tmpName, $destPath)) {
                    throw new Exception('Failed to save uploaded logo.');
                }

                // Store path relative to project root for serving
                $logoPath = 'uploads/site/' . $newName;
            }

            // Handle admin password change (optional)
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($newPassword !== '' || $confirmPassword !== '' || $currentPassword !== '') {
                if (strlen($newPassword) < 8) {
                    throw new Exception('New password must be at least 8 characters.');
                }
                if ($newPassword !== $confirmPassword) {
                    throw new Exception('New password and confirmation do not match.');
                }

                // Verify current password
                $user = $this->userModel->getUserByCin($_SESSION['userID']);
                if (!$user || !password_verify($currentPassword, $user['password'])) {
                    throw new Exception('Current password is incorrect.');
                }

                // Update password
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("UPDATE users SET password = :password WHERE cin = :cin");
                $stmt->execute([':password' => $hashed, ':cin' => $_SESSION['userID']]);
            }

            // Save settings
            $payload = [
                'site_name' => $siteName,
                'site_logo_path' => $logoPath,
                'contact_email' => $contactEmail,
                'maintenance_mode' => $maintenanceMode,
                'recaptcha_site_key' => $recaptchaSiteKey,
                'recaptcha_secret_key' => $recaptchaSecretKey,
                'session_timeout_minutes' => $sessionTimeout,
                'email_notifications_enabled' => $emailNotificationsEnabled,
                'email_sender_name' => $emailSenderName,
                'email_sender_email' => $emailSenderEmail,
                'email_template_welcome' => $emailTemplateWelcome,
                'email_template_donation' => $emailTemplateDonation,
                'updated_by' => $_SESSION['userID'],
            ];

            $ok = $this->settingsModel->saveSettings($payload);
            if ($ok) {
                $_SESSION['success'] = 'Settings updated successfully.';
                header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=settings');
            } else {
                throw new Exception('Failed to update settings. Please try again.');
            }
        } catch (Exception $e) {
            $_SESSION['errors'] = [$e->getMessage()];
            error_log("Error updating settings: " . $e->getMessage());
            header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=settings');
        }
        exit;
    }
}

