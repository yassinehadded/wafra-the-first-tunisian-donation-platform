<?php
/**
 * Admin Donation Controller (Back Office)
 * Handles admin donation management operations
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../models/Donation.php';
require_once __DIR__ . '/../models/User.php';

class AdminDonationController {
    private $pdo;
    private $donationModel;
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
        $this->donationModel = new Donation($this->pdo);
        $this->userModel = new User($this->pdo);
    }

    /**
     * Display all donations (admin)
     */
    public function index() {
        $filters = [
            'status' => $_GET['status'] ?? '',
            'category' => $_GET['category'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        $donations = $this->donationModel->getAllDonations($filters);
        $stats = $this->donationModel->getStats();
        
        // Get all donation requests
        $allRequests = $this->donationModel->getAllDonationRequests();
        $pendingRequestsCount = 0;
        foreach ($allRequests as $request) {
            if (($request['status'] ?? 'pending') === 'pending') {
                $pendingRequestsCount++;
            }
        }
        
        $baseUrl = BASE_URL;
        $successMessage = $_SESSION['admin_donation_success'] ?? null;
        $errorMessage = $_SESSION['admin_donation_error'] ?? null;
        unset($_SESSION['admin_donation_success'], $_SESSION['admin_donation_error']);
        
        require_once __DIR__ . '/../view/backoffice/donations/index.php';
    }

    /**
     * Update donation status (AJAX)
     */
    public function updateStatus() {
        // Clean output buffers first
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set JSON header
        header('Content-Type: application/json; charset=utf-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if (!$id || !in_array($status, ['active', 'fulfilled'])) {
            echo json_encode(['success' => false, 'error' => 'Paramètres invalides'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $success = $this->donationModel->updateDonationStatus($id, $status);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * Update donation quantity (AJAX)
     */
    public function updateQuantity() {
        // Clean output buffers first
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set JSON header
        header('Content-Type: application/json; charset=utf-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        
        if (!$id || $quantity < 0) {
            echo json_encode(['success' => false, 'error' => 'Paramètres invalides'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $success = $this->donationModel->updateDonationQuantity($id, $quantity);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Quantité mise à jour avec succès'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * Delete donation (AJAX)
     */
    public function delete($id = null) {
        // Clean output buffers first
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set JSON header
        header('Content-Type: application/json; charset=utf-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Get ID from parameter or POST
        $donationId = $id ? (int)$id : (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        
        if (!$donationId) {
            echo json_encode(['success' => false, 'error' => 'ID de donation invalide'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Delete donation (admin can delete any donation, so no userId check)
        $success = $this->donationModel->deleteDonation($donationId, null);
        
        if ($success) {
            $_SESSION['admin_donation_success'] = 'Donation supprimée avec succès';
            echo json_encode(['success' => true, 'message' => 'Donation supprimée avec succès'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * Update donation request status (approve/reject) with email notification
     */
    public function updateRequestStatus() {
        // Clean output buffers first
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set JSON header
        header('Content-Type: application/json; charset=utf-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $requestId = (int)($_POST['request_id'] ?? 0);
        $donationId = (int)($_POST['donation_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $reason = trim($_POST['reason'] ?? '');
        
        if (!$requestId || !$donationId || !in_array($status, ['approved', 'denied'])) {
            echo json_encode(['success' => false, 'error' => 'Paramètres invalides'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            // Get request details
            $request = $this->donationModel->getDonationRequestById($requestId);
            if (!$request) {
                echo json_encode(['success' => false, 'error' => 'Demande introuvable'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Get donation details
            $donation = $this->donationModel->getDonationById($donationId);
            if (!$donation) {
                echo json_encode(['success' => false, 'error' => 'Donation introuvable'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Update request status
            $success = $this->donationModel->updateRequestStatus($requestId, $status, $donationId);
            
            if ($success) {
                // Send email notification
                require_once __DIR__ . '/../services/DonationEmailService.php';
                $emailService = new DonationEmailService();
                
                if ($status === 'approved') {
                    // Get donor contact information
                    $donorInfo = [];
                    if ($donation['user_id']) {
                        $donor = $this->userModel->getUserByCin($donation['user_id']);
                        if ($donor) {
                            $donorInfo['name'] = trim(($donor['firstname'] ?? '') . ' ' . ($donor['lastname'] ?? ''));
                            $donorInfo['email'] = $donor['email'] ?? '';
                            $donorInfo['phone'] = $donor['phone'] ?? '';
                        }
                    }
                    
                    // If no user info, use donation fields
                    if (empty($donorInfo['name'])) {
                        $donorInfo['name'] = $donation['donor_name'] ?? 'Donateur';
                    }
                    if (empty($donorInfo['email'])) {
                        $donorInfo['email'] = $donation['donor_email'] ?? '';
                    }
                    if (empty($donorInfo['phone'])) {
                        $donorInfo['phone'] = $donation['donor_phone'] ?? '';
                    }
                    
                    // Get requester email
                    $requesterEmail = $request['requester_email'] ?? $request['email'] ?? '';
                    
                    // Send approval email with donor contact info
                    if (!empty($requesterEmail)) {
                        $emailService->sendApprovalEmailWithDonorContact($requesterEmail, $request, $donation, $donorInfo);
                    }
                } elseif ($status === 'denied') {
                    // Get requester email
                    $requesterEmail = $request['requester_email'] ?? $request['email'] ?? '';
                    
                    // Send rejection email
                    if (!empty($requesterEmail)) {
                        $emailService->sendRejectionEmail($requesterEmail, $request, $reason);
                    }
                }
                
                echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès'], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour'], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log("AdminDonationController::updateRequestStatus() error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}


