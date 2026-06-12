<?php
/**
 * Association Controller (Front Office)
 * Handles user association operations
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../models/Association.php';

class AssociationController {
    private $pdo;
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
        $this->associationModel = new Association($this->pdo);
    }

    /**
     * Display all associations
     */
    public function index() {
        $userId = (int)$_SESSION['userID'];
        $associations = $this->associationModel->getAllAssociations('Active');
        $userAssociations = $this->associationModel->getUserAssociations($userId);
        $userAssociationIds = array_column($userAssociations, 'id');
        
        $baseUrl = BASE_URL;
        $successMessage = $_SESSION['association_success'] ?? null;
        $errorMessage = $_SESSION['association_error'] ?? null;
        unset($_SESSION['association_success'], $_SESSION['association_error']);
        
        require_once __DIR__ . '/../view/frontoffice/associations/index.php';
    }

    /**
     * Show association details
     */
    public function show($id) {
        $userId = (int)$_SESSION['userID'];
        $association = $this->associationModel->getAssociationById($id);
        
        if (!$association) {
            $_SESSION['association_error'] = 'Association introuvable';
            header('Location: ' . BASE_URL . '/index.php?action=associations');
            exit;
        }
        
        $isMember = $this->associationModel->isMember($userId, $id);
        $members = $this->associationModel->getMembers($id);
        
        $baseUrl = BASE_URL;
        $successMessage = $_SESSION['association_success'] ?? null;
        $errorMessage = $_SESSION['association_error'] ?? null;
        unset($_SESSION['association_success'], $_SESSION['association_error']);
        
        require_once __DIR__ . '/../view/frontoffice/associations/show.php';
    }

    /**
     * Join association
     */
    public function join($id) {
        $userId = (int)$_SESSION['userID'];
        
        try {
            $association = $this->associationModel->getAssociationById($id);
            if (!$association) {
                $_SESSION['association_error'] = 'Association introuvable';
                header('Location: ' . BASE_URL . '/index.php?action=associations');
                exit;
            }
            
            if ($this->associationModel->isMember($userId, $id)) {
                $_SESSION['association_error'] = 'Vous êtes déjà membre de cette association';
                header('Location: ' . BASE_URL . '/index.php?action=association_show&id=' . $id);
                exit;
            }
            
            if ($this->associationModel->joinAssociation($userId, $id)) {
                $_SESSION['association_success'] = 'Vous avez rejoint l\'association avec succès';
            } else {
                $_SESSION['association_error'] = 'Erreur lors de l\'adhésion';
            }
        } catch (Exception $e) {
            $_SESSION['association_error'] = $e->getMessage();
        }
        
        header('Location: ' . BASE_URL . '/index.php?action=association_show&id=' . $id);
        exit;
    }
}
?>



