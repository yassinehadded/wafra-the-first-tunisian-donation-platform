<?php
/**
 * Cotisation Controller (Front Office)
 * Handles user cotisation operations
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../models/Cotisation.php';
require_once __DIR__ . '/../models/Association.php';

class CotisationController {
    private $pdo;
    private $cotisationModel;
    private $associationModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in
        if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
            header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
            exit;
        }

        $this->pdo = Database::connect();
        $this->cotisationModel = new Cotisation($this->pdo);
        $this->associationModel = new Association($this->pdo);
    }

    /**
     * Display user's cotisations
     */
    public function index() {
        $userId = (int)$_SESSION['userID'];
        $associationId = isset($_GET['association_id']) ? (int)$_GET['association_id'] : null;
        
        $cotisations = $this->cotisationModel->getUserCotisations($userId, $associationId);
        $userAssociations = $this->associationModel->getUserAssociations($userId);
        
        $baseUrl = BASE_URL;
        $successMessage = $_SESSION['cotisation_success'] ?? null;
        $errorMessage = $_SESSION['cotisation_error'] ?? null;
        unset($_SESSION['cotisation_success'], $_SESSION['cotisation_error']);
        
        require_once __DIR__ . '/../view/frontoffice/cotisations/index.php';
    }

    /**
     * Show cotisation history
     */
    public function history() {
        $userId = (int)$_SESSION['userID'];
        $cotisations = $this->cotisationModel->getUserCotisations($userId);
        
        $baseUrl = BASE_URL;
        require_once __DIR__ . '/../view/frontoffice/cotisations/history.php';
    }

    /**
     * Pay cotisation
     */
    public function pay() {
        $userId = (int)$_SESSION['userID'];
        $associationId = isset($_POST['association_id']) ? (int)$_POST['association_id'] : 0;
        
        if (!$associationId) {
            $_SESSION['cotisation_error'] = 'Association invalide';
            header('Location: ' . BASE_URL . '/index.php?action=cotisations');
            exit;
        }
        
        // Check if user is member
        if (!$this->associationModel->isMember($userId, $associationId)) {
            $_SESSION['cotisation_error'] = 'Vous devez être membre de l\'association pour payer une cotisation';
            header('Location: ' . BASE_URL . '/index.php?action=associations');
            exit;
        }
        
        try {
            $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
            $period = $_POST['period'] ?? 'monthly';
            $paymentMethod = $_POST['payment_method'] ?? 'online';
            
            if ($amount <= 0) {
                $_SESSION['cotisation_error'] = 'Le montant doit être supérieur à 0';
                header('Location: ' . BASE_URL . '/index.php?action=cotisations');
                exit;
            }
            
            $data = [
                'association_id' => $associationId,
                'user_id' => $userId,
                'amount' => $amount,
                'period' => $period,
                'payment_status' => 'pending',
                'payment_method' => $paymentMethod,
                'notes' => $_POST['notes'] ?? null
            ];
            
            $cotisationId = $this->cotisationModel->createCotisation($data);
            
            if ($cotisationId) {
                $_SESSION['cotisation_success'] = 'Cotisation enregistrée. En attente de validation.';
            } else {
                $_SESSION['cotisation_error'] = 'Erreur lors de l\'enregistrement de la cotisation';
            }
        } catch (Exception $e) {
            $_SESSION['cotisation_error'] = $e->getMessage();
        }
        
        header('Location: ' . BASE_URL . '/index.php?action=cotisations');
        exit;
    }
}
?>



