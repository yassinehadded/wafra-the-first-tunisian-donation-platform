<?php
/**
 * Admin Cotisation Controller
 * Handles admin cotisation management
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../models/Cotisation.php';
require_once __DIR__ . '/../models/Association.php';

class AdminCotisationController {
    private $pdo;
    private $cotisationModel;
    private $associationModel;

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
        $this->cotisationModel = new Cotisation($this->pdo);
        $this->associationModel = new Association($this->pdo);
    }

    /**
     * List all cotisations
     */
    public function index() {
        $filters = [];
        if (isset($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        if (isset($_GET['association_id'])) {
            $filters['association_id'] = (int)$_GET['association_id'];
        }
        
        $cotisations = $this->cotisationModel->getAllCotisations($filters);
        $associations = $this->associationModel->getAllAssociations();
        
        $baseUrl = BASE_URL;
        $successMessage = $_SESSION['admin_cotisation_success'] ?? null;
        $errorMessage = $_SESSION['admin_cotisation_error'] ?? null;
        unset($_SESSION['admin_cotisation_success'], $_SESSION['admin_cotisation_error']);
        
        require_once __DIR__ . '/../view/backoffice/admin/cotisations/index.php';
    }

    /**
     * Validate payment
     */
    public function validatePayment($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $paymentData = [
                    'payment_status' => $_POST['payment_status'] ?? 'paid',
                    'payment_date' => $_POST['payment_date'] ?? date('Y-m-d'),
                    'payment_method' => $_POST['payment_method'] ?? null,
                    'payment_reference' => $_POST['payment_reference'] ?? null,
                    'notes' => $_POST['notes'] ?? null
                ];
                
                $this->cotisationModel->validatePayment($id, $paymentData);
                $_SESSION['admin_cotisation_success'] = 'Paiement validé avec succès';
            } catch (Exception $e) {
                $_SESSION['admin_cotisation_error'] = $e->getMessage();
            }
        }
        
        // Clear any output buffers before redirect
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=cotisations&_t=' . time() . '#cotisations-section', true, 303);
        exit;
    }

    /**
     * Delete cotisation
     */
    public function delete($id) {
        try {
            $this->cotisationModel->deleteCotisation($id);
            $_SESSION['admin_cotisation_success'] = 'Cotisation supprimée avec succès';
        } catch (Exception $e) {
            $_SESSION['admin_cotisation_error'] = $e->getMessage();
        }
        
        // Clear any output buffers before redirect
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=cotisations&_t=' . time() . '#cotisations-section', true, 303);
        exit;
    }
}
?>

