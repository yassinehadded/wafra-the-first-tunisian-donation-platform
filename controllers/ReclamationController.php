<?php
/**
 * Reclamation Controller (Front Office)
 * Handles user reclamation operations
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../models/Reclamation.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/EmailService.php';

class ReclamationController {
    private $pdo;
    private $reclamationModel;
    private $userModel;

    public function __construct($skipAuth = false) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Ensure BASE_URL is defined
        if (!defined('BASE_URL')) {
            require_once __DIR__ . '/../config/config.php';
        }
        
        // Check if user is logged in (skip for AJAX requests)
        if (!$skipAuth && (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user')) {
            header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
            exit;
        }

        try {
            $this->pdo = Database::connect();
            $this->reclamationModel = new Reclamation($this->pdo);
            $this->userModel = new User($this->pdo);
        } catch (Exception $e) {
            error_log("Error in ReclamationController constructor: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            // Always throw for AJAX requests so we can return proper JSON error
            throw $e;
        } catch (Error $e) {
            error_log("Fatal error in ReclamationController constructor: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Display reclamation form and list
     */
    public function index() {
        $userId = (int)$_SESSION['userID'];
        $currentUser = $this->userModel->getUserByCin($userId);
        $reclamations = $this->reclamationModel->getUserReclamations($userId);
        
        // Get user avatar
        $userAvatar = BASE_URL . '/view/frontoffice/assets/images/default-avatar.png';
        if (!empty($currentUser['profile_picture'])) {
            $avatarPath = __DIR__ . '/../uploads/profile_pictures/' . basename($currentUser['profile_picture']);
            if (file_exists($avatarPath)) {
                $userAvatar = BASE_URL . '/uploads/profile_pictures/' . basename($currentUser['profile_picture']);
            }
        }
        
        $baseUrl = BASE_URL;
        $successMessage = $_SESSION['reclamation_success'] ?? null;
        $errorMessage = $_SESSION['reclamation_error'] ?? null;
        unset($_SESSION['reclamation_success'], $_SESSION['reclamation_error']);
        
        require_once __DIR__ . '/../view/frontoffice/reclamations/index.php';
    }

    /**
     * Submit new reclamation
     */
    public function submit() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?action=reclamations');
            exit;
        }

        $userId = (int)$_SESSION['userID'];
        
        // Get and validate input
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $type = $_POST['type'] ?? '';
        $priorite = $_POST['priorite'] ?? '';
        $description = trim($_POST['description'] ?? '');
        
        // Validation
        $errors = [];
        
        if (empty($nom) || !preg_match('/^[A-Za-zÀ-ÿ\s]{3,50}$/u', $nom)) {
            $errors[] = 'Le nom doit contenir uniquement des lettres (3 à 50 caractères)';
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide';
        }
        
        if (empty($telephone) || !preg_match('/^[0-9]{8}$/', $telephone)) {
            $errors[] = 'Le téléphone doit contenir exactement 8 chiffres';
        }
        
        if (empty($type) || !in_array($type, ['Service', 'Produit', 'Livraison', 'Facturation', 'Technique', 'Autre'])) {
            $errors[] = 'Type de réclamation invalide';
        }
        
        if (empty($priorite) || !in_array($priorite, ['Basse', 'Moyenne', 'Haute'])) {
            $errors[] = 'Priorité invalide';
        }
        
        if (empty($description) || strlen($description) < 20) {
            $errors[] = 'La description doit contenir au moins 20 caractères';
        }
        
        if (!empty($errors)) {
            $_SESSION['reclamation_error'] = implode('<br>', $errors);
            header('Location: ' . BASE_URL . '/view/frontoffice/index.php#reclamations');
            exit;
        }
        
        try {
            $id = $this->reclamationModel->createReclamation(
                $userId,
                $nom,
                $email,
                $telephone,
                $type,
                $priorite,
                $description
            );
            
            // Get the created reclamation to send email
            $reclamation = $this->reclamationModel->getReclamationById($id);
            if ($reclamation) {
                // Send email to admin
                $emailService = new EmailService();
                $emailService->sendNewReclamationNotificationToAdmin($reclamation);
            }
            
            $_SESSION['reclamation_success'] = 'Réclamation créée avec succès ! Numéro de suivi: #' . $id;
            header('Location: ' . BASE_URL . '/view/frontoffice/index.php#reclamations');
            exit;
        } catch (Exception $e) {
            $_SESSION['reclamation_error'] = 'Erreur lors de la création de la réclamation: ' . $e->getMessage();
            header('Location: ' . BASE_URL . '/view/frontoffice/index.php#reclamations');
            exit;
        }
    }

    /**
     * View single reclamation
     */
    public function view($id) {
        $userId = (int)$_SESSION['userID'];
        $reclamation = $this->reclamationModel->getReclamationById($id, $userId);
        
        if (!$reclamation) {
            $_SESSION['reclamation_error'] = 'Réclamation introuvable ou accès non autorisé';
            header('Location: ' . BASE_URL . '/view/frontoffice/index.php#reclamations');
            exit;
        }
        
        $currentUser = $this->userModel->getUserByCin($userId);
        $userAvatar = BASE_URL . '/view/frontoffice/assets/images/default-avatar.png';
        if (!empty($currentUser['profile_picture'])) {
            $avatarPath = __DIR__ . '/../uploads/profile_pictures/' . basename($currentUser['profile_picture']);
            if (file_exists($avatarPath)) {
                $userAvatar = BASE_URL . '/uploads/profile_pictures/' . basename($currentUser['profile_picture']);
            }
        }
        
        $responses = $this->reclamationModel->getResponses($id);
        $baseUrl = BASE_URL;
        
        require_once __DIR__ . '/../view/frontoffice/reclamations/view.php';
    }

    /**
     * Get reclamation details (AJAX)
     */
    public function getDetailsAjax() {
        // Disable error display for clean JSON output
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        error_reporting(E_ALL);
        
        // Clean any output that might have been sent
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Ensure we output JSON only
        header('Content-Type: application/json; charset=utf-8');
        
        // Wrap everything in try-catch to catch any fatal errors
        try {
            // Check authentication
            if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
                echo json_encode(['success' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $id = (int)($_GET['id'] ?? 0);
            $userId = (int)$_SESSION['userID'];
            
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'Invalid ID'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Check if database connection is available
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            // Check if models are initialized
            if (!$this->reclamationModel) {
                throw new Exception('Reclamation model not initialized');
            }
            
            $reclamation = $this->reclamationModel->getReclamationById($id, $userId);
            
            if (!$reclamation) {
                echo json_encode(['success' => false, 'error' => 'Réclamation introuvable ou accès non autorisé'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $responses = $this->reclamationModel->getResponses($id);
            
            // Ensure all data is JSON-serializable
            $output = [
                'success' => true,
                'reclamation' => $reclamation,
                'responses' => $responses ?: []
            ];
            
            $json = json_encode($output, JSON_UNESCAPED_UNICODE);
            
            if ($json === false) {
                throw new Exception('JSON encoding failed: ' . json_last_error_msg());
            }
            
            echo $json;
        } catch (PDOException $e) {
            // Clean any output
            while (ob_get_level()) {
                ob_end_clean();
            }
            error_log("PDO Error in getDetailsAjax: " . $e->getMessage());
            error_log("PDO Error trace: " . $e->getTraceAsString());
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Erreur de base de données: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            // Clean any output
            while (ob_get_level()) {
                ob_end_clean();
            }
            error_log("Error in getDetailsAjax: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Erreur: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (Error $e) {
            // Clean any output
            while (ob_get_level()) {
                ob_end_clean();
            }
            error_log("Fatal error in getDetailsAjax: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Erreur fatale: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            // Clean any output
            while (ob_get_level()) {
                ob_end_clean();
            }
            error_log("Throwable in getDetailsAjax: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Erreur: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * Update reclamation (AJAX)
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $userId = (int)$_SESSION['userID'];
        $id = (int)($_POST['id'] ?? 0);
        
        if (!$id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'ID manquant']);
            exit;
        }
        
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $type = $_POST['type'] ?? '';
        $priorite = $_POST['priorite'] ?? '';
        $description = trim($_POST['description'] ?? '');
        
        // Validation
        if (empty($nom) || empty($email) || empty($telephone) || empty($type) || empty($priorite) || empty($description)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Tous les champs sont obligatoires']);
            exit;
        }
        
        $success = $this->reclamationModel->updateReclamation(
            $id,
            $userId,
            $nom,
            $email,
            $telephone,
            $type,
            $priorite,
            $description
        );
        
        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Réclamation modifiée avec succès']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la modification']);
        }
        exit;
    }

    /**
     * Delete reclamation (AJAX)
     */
    public function delete($id = 0) {
        // Clear any output buffers before sending JSON response
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Disable error display for AJAX
        ini_set('display_errors', 0);
        error_reporting(E_ALL);
        
        // Set JSON header
        header('Content-Type: application/json; charset=utf-8');
        
        // Check authentication
        if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
            echo json_encode(['success' => false, 'error' => 'Non autorisé'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Check request method - allow both POST and DELETE for flexibility
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            echo json_encode(['success' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Get ID from parameter, POST, or GET (in that order)
        if (!$id || $id === 0) {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
        }

        if (!$id || $id === 0) {
            echo json_encode(['success' => false, 'error' => 'ID de réclamation invalide'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $userId = (int)$_SESSION['userID'];
        $id = (int)$id;
        
        try {
            $success = $this->reclamationModel->deleteReclamation($id, $userId);
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Réclamation supprimée avec succès'], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression ou réclamation introuvable'], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log("Error deleting reclamation: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}

