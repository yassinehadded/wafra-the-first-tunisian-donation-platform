<?php
/**
 * Admin Association Controller
 * Handles admin association management
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../models/Association.php';

class AdminAssociationController {
    private $pdo;
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
        $this->associationModel = new Association($this->pdo);
    }

    /**
     * List all associations
     */
    public function index() {
        $associations = $this->associationModel->getAllAssociations();
        
        $baseUrl = BASE_URL;
        $successMessage = $_SESSION['admin_association_success'] ?? null;
        $errorMessage = $_SESSION['admin_association_error'] ?? null;
        unset($_SESSION['admin_association_success'], $_SESSION['admin_association_error']);
        
        require_once __DIR__ . '/../view/backoffice/admin/associations/index.php';
    }

    /**
     * Create association
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Validate required fields
                $requiredFields = ['name', 'email', 'phone', 'address', 'category'];
                $missingFields = [];
                foreach ($requiredFields as $field) {
                    if (empty($_POST[$field])) {
                        $missingFields[] = $field;
                    }
                }
                
                if (!empty($missingFields)) {
                    throw new Exception('Les champs suivants sont requis: ' . implode(', ', $missingFields));
                }
                
                $data = [
                    'name' => trim($_POST['name'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'address' => trim($_POST['address'] ?? ''),
                    'phone' => trim($_POST['phone'] ?? ''),
                    'email' => trim($_POST['email'] ?? ''),
                    'category' => trim($_POST['category'] ?? ''),
                    'status' => $_POST['status'] ?? 'Active'
                ];
                
                // Validate email format
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Format d\'email invalide');
                }
                
                $id = $this->associationModel->createAssociation($data);
                $_SESSION['admin_association_success'] = 'Association créée avec succès';
                
                // Clear any output buffers before redirect
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Redirect to list view (without section_action) with cache-busting parameter
                // Use absolute URL to ensure proper redirect
                $redirectUrl = BASE_URL . '/index.php?action=dashboard&section=associations&_t=' . time() . '#associations-section';
                
                // Log for debugging
                error_log("AdminAssociationController::create() - Redirecting to: " . $redirectUrl);
                
                // Send redirect header
                header('Location: ' . $redirectUrl, true, 303);
                
                // Ensure no output is sent
                if (ob_get_level()) {
                    ob_end_flush();
                }
                exit;
            } catch (Exception $e) {
                $_SESSION['admin_association_error'] = $e->getMessage();
                // On error, redirect back to create form to show error
                while (ob_get_level()) {
                    ob_end_clean();
                }
                header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=associations&section_action=create');
                exit;
            }
        }
        
        $baseUrl = BASE_URL;
        $errorMessage = $_SESSION['admin_association_error'] ?? null;
        unset($_SESSION['admin_association_error']);
        require_once __DIR__ . '/../view/backoffice/admin/associations/create.php';
    }

    /**
     * Update association
     */
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'name' => $_POST['name'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'address' => $_POST['address'] ?? '',
                    'phone' => $_POST['phone'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'category' => $_POST['category'] ?? '',
                    'status' => $_POST['status'] ?? 'Active'
                ];
                
                $this->associationModel->updateAssociation($id, $data);
                $_SESSION['admin_association_success'] = 'Association mise à jour avec succès';
                header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=associations#associations-section');
                exit;
            } catch (Exception $e) {
                $_SESSION['admin_association_error'] = $e->getMessage();
            }
        }
        
        $association = $this->associationModel->getAssociationById($id);
        if (!$association) {
            $_SESSION['admin_association_error'] = 'Association introuvable';
            header('Location: ' . BASE_URL . '/dashboard.php?action=admin_associations');
            exit;
        }
        
        $baseUrl = BASE_URL;
        require_once __DIR__ . '/../view/backoffice/admin/associations/edit.php';
    }

    /**
     * Delete association
     */
    public function delete($id) {
        try {
            $this->associationModel->deleteAssociation($id);
            $_SESSION['admin_association_success'] = 'Association supprimée avec succès';
        } catch (Exception $e) {
            $_SESSION['admin_association_error'] = $e->getMessage();
        }
        
        // Clear any output buffers before redirect
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=associations&_t=' . time() . '#associations-section', true, 303);
        exit;
    }

    /**
     * Manage members
     */
    public function members($id) {
        $association = $this->associationModel->getAssociationById($id);
        if (!$association) {
            $_SESSION['admin_association_error'] = 'Association introuvable';
            header('Location: ' . BASE_URL . '/index.php?action=dashboard&section=associations#associations-section');
            exit;
        }
        
        $members = $this->associationModel->getMembers($id);
        
        $baseUrl = BASE_URL;
        require_once __DIR__ . '/../view/backoffice/admin/associations/members.php';
    }
}
?>

