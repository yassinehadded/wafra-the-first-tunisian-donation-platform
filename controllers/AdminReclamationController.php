<?php
/**
 * Admin Reclamation Controller
 * Handles admin reclamation management
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../models/Reclamation.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/EmailService.php';

class AdminReclamationController {
    private $pdo;
    private $reclamationModel;
    private $userModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is admin
        if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
            exit;
        }

        $this->pdo = Database::connect();
        $this->reclamationModel = new Reclamation($this->pdo);
        $this->userModel = new User($this->pdo);
    }

    /**
     * Display all reclamations (admin)
     */
    public function index() {
        $filters = [
            'priorite' => $_GET['priorite'] ?? '',
            'statut' => $_GET['statut'] ?? '',
            'type' => $_GET['type'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        $reclamations = $this->reclamationModel->getAllReclamations($filters);
        $stats = $this->reclamationModel->getStats();
        
        $baseUrl = BASE_URL;
        $successMessage = $_SESSION['admin_reclamation_success'] ?? null;
        $errorMessage = $_SESSION['admin_reclamation_error'] ?? null;
        unset($_SESSION['admin_reclamation_success'], $_SESSION['admin_reclamation_error']);
        
        require_once __DIR__ . '/../view/backoffice/reclamations/index.php';
    }

    /**
     * Update status (AJAX)
     */
    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if (!$id || !in_array($status, ['En attente', 'En cours', 'Répondu'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
            exit;
        }
        
        $success = $this->reclamationModel->updateStatus($id, $status);
        
        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour']);
        }
        exit;
    }

    /**
     * Add response to reclamation (AJAX)
     */
    public function addResponse() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $reclamationId = (int)($_POST['reclamation_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $adminId = (int)$_SESSION['userID'];
        
        if (!$reclamationId || empty($message)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
            exit;
        }
        
        try {
            $responseId = $this->reclamationModel->addResponse($reclamationId, $adminId, $message);
            
            // Get the reclamation and response to send email
            $reclamation = $this->reclamationModel->getReclamationById($reclamationId);
            $responses = $this->reclamationModel->getResponses($reclamationId);
            
            if ($reclamation && !empty($responses)) {
                // Get the latest response (just added)
                $latestResponse = $responses[0];
                
                // Send email to user
                $emailService = new EmailService();
                $emailService->sendReclamationResponseNotification($reclamation, $latestResponse, $reclamation['email']);
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Réponse ajoutée avec succès', 'response_id' => $responseId]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Delete reclamation (AJAX)
     */
    public function delete($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $id = (int)$id;
        $success = $this->reclamationModel->deleteReclamation($id);
        
        header('Content-Type: application/json');
        if ($success) {
            $_SESSION['admin_reclamation_success'] = 'Réclamation supprimée avec succès';
            echo json_encode(['success' => true, 'message' => 'Réclamation supprimée avec succès']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression']);
        }
        exit;
    }

    /**
     * Get reclamation details (AJAX)
     */
    public function getDetails($id) {
        $reclamation = $this->reclamationModel->getReclamationById($id);
        $responses = $this->reclamationModel->getResponses($id);
        
        header('Content-Type: application/json');
        if ($reclamation) {
            echo json_encode([
                'success' => true,
                'reclamation' => $reclamation,
                'responses' => $responses
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Réclamation introuvable']);
        }
        exit;
    }

    /**
     * Get reclamations via AJAX (for filtering without page reload)
     */
    public function getReclamationsAjax() {
        $filters = [
            'priorite' => $_GET['priorite'] ?? '',
            'statut' => $_GET['statut'] ?? '',
            'type' => $_GET['type'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        $reclamations = $this->reclamationModel->getAllReclamations($filters);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'reclamations' => $reclamations
        ]);
        exit;
    }
}

