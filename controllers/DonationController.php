<?php
/**
 * Donation Controller (Front Office)
 * Handles user donation operations
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../models/Donation.php';
require_once __DIR__ . '/../models/User.php';

class DonationController {
    private $pdo;
    private $donationModel;
    private $userModel;

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
        $this->donationModel = new Donation($this->pdo);
        $this->userModel = new User($this->pdo);
    }

    /**
     * Display donations feed or form based on action
     */
    public function index() {
        // Prevent any output before HTML
        ob_start();
        
        $userId = (int)$_SESSION['userID'];
        $baseUrl = BASE_URL;
        
        // Check if creating new donation
        if (isset($_GET['create']) && $_GET['create'] == '1') {
            $currentUser = $this->userModel->getUserByCin($userId);
            $successMessage = $_SESSION['donation_success'] ?? null;
            $errorMessage = $_SESSION['donation_error'] ?? null;
            $oldData = $_SESSION['old_donation_data'] ?? [];
            unset($_SESSION['donation_success'], $_SESSION['donation_error'], $_SESSION['old_donation_data']);
            
            ob_end_clean();
            require_once __DIR__ . '/../view/frontoffice/donations/create.php';
            return;
        }
        
        // Show donations feed
        $donations = $this->donationModel->getActiveDonationsWithUsers(20);
        
        // Check which donations user has already requested
        $hasRequested = [];
        foreach ($donations as $donation) {
            $hasRequested[$donation['id']] = $this->donationModel->hasUserRequested($donation['id'], $userId);
        }
        
        $successMessage = $_SESSION['donation_success'] ?? null;
        $errorMessage = $_SESSION['donation_error'] ?? null;
        unset($_SESSION['donation_success'], $_SESSION['donation_error']);
        
        ob_end_clean();
        require_once __DIR__ . '/../view/frontoffice/donations/feed.php';
    }
    
    /**
     * Show donation details
     */
    public function show($id) {
        $userId = (int)$_SESSION['userID'];
        $donation = $this->donationModel->getDonationWithUser($id);
        
        if (!$donation) {
            $_SESSION['donation_error'] = 'Donation introuvable';
            header('Location: ' . BASE_URL . '/index.php?action=donations');
            exit;
        }
        
        $requests = $this->donationModel->getDonationRequests($id);
        $hasRequested = $this->donationModel->hasUserRequested($id, $userId);
        $isOwner = $donation['user_id'] == $userId;
        
        $baseUrl = BASE_URL;
        $successMessage = $_SESSION['donation_success'] ?? null;
        $errorMessage = $_SESSION['donation_error'] ?? null;
        unset($_SESSION['donation_success'], $_SESSION['donation_error']);
        
        require_once __DIR__ . '/../view/frontoffice/donations/show.php';
    }
    
    /**
     * Show user's donations
     */
    public function myDonations() {
        $userId = (int)$_SESSION['userID'];
        $userDonations = $this->donationModel->getUserDonations($userId);
        $userRequests = $this->donationModel->getUserRequests($userId);
        
        // Get requests received for each donation
        $donationRequests = [];
        foreach ($userDonations as $donation) {
            $requests = $this->donationModel->getDonationRequests($donation['id']);
            $donationRequests[$donation['id']] = $requests;
        }
        
        $baseUrl = BASE_URL;
        $successMessage = $_SESSION['donation_success'] ?? null;
        $errorMessage = $_SESSION['donation_error'] ?? null;
        unset($_SESSION['donation_success'], $_SESSION['donation_error']);
        
        require_once __DIR__ . '/../view/frontoffice/donations/my.php';
    }
    
    /**
     * Request a donation
     */
    public function request() {
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
        
        $userId = (int)$_SESSION['userID'];
        $donationId = (int)($_POST['donation_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        
        if (!$donationId) {
            echo json_encode(['success' => false, 'error' => 'Donation invalide'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Check if already requested
        if ($this->donationModel->hasUserRequested($donationId, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Vous avez déjà demandé cette donation'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Check if user is the donor
        $donation = $this->donationModel->getDonationById($donationId);
        if ($donation && $donation['user_id'] == $userId) {
            echo json_encode(['success' => false, 'error' => 'Vous ne pouvez pas demander votre propre donation'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            // Get donation details
            $donation = $this->donationModel->getDonationById($donationId);
            if (!$donation) {
                echo json_encode(['success' => false, 'error' => 'Donation introuvable'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Create request
            $requestId = $this->donationModel->createRequest($donationId, $userId, $message);
            
            // Get request details for email
            $request = $this->donationModel->getDonationRequestById($requestId);
            
            // Send email to requester (non-blocking - don't fail if email fails)
            if ($request && !empty($request['email'])) {
                try {
                    require_once __DIR__ . '/../services/DonationEmailService.php';
                    $emailService = new DonationEmailService();
                    $emailService->sendProcessingEmail($request['email'], $request, $donation);
                } catch (Exception $e) {
                    error_log("Email sending error (non-fatal): " . $e->getMessage());
                }
            }
            
            // Send email to admin (non-blocking)
            $adminEmail = 'yassineou.haddadou@gmail.com';
            if ($request) {
                try {
                    require_once __DIR__ . '/../services/DonationEmailService.php';
                    $emailService = new DonationEmailService();
                    $emailService->sendAdminNotificationEmail($adminEmail, $request, $donation);
                } catch (Exception $e) {
                    error_log("Admin email sending error (non-fatal): " . $e->getMessage());
                }
            }
            
            // Ensure no output before JSON
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            echo json_encode(['success' => true, 'message' => 'Demande envoyée avec succès !'], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Exception $e) {
            error_log("DonationController::request() error: " . $e->getMessage());
            // Clean output before error JSON
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    /**
     * Update request status (for donor)
     */
    public function updateRequestStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        $userId = (int)$_SESSION['userID'];
        $requestId = (int)($_POST['request_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $donationId = (int)($_POST['donation_id'] ?? 0);
        
        if (!$requestId || !in_array($status, ['approved', 'denied'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
            exit;
        }
        
        // Verify user owns the donation
        $donation = $this->donationModel->getDonationById($donationId);
        if (!$donation || $donation['user_id'] != $userId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            exit;
        }
        
        $success = $this->donationModel->updateRequestStatus($requestId, $status, $donationId);
        
        // Send email notification
        if ($success) {
            $request = $this->donationModel->getDonationRequestById($requestId);
            $donation = $this->donationModel->getDonationById($donationId);
            
            if ($request && $donation) {
                require_once __DIR__ . '/../services/DonationEmailService.php';
                $emailService = new DonationEmailService();
                
                // Get requester email
                $requesterEmail = $request['requester_email'] ?? $request['email'] ?? '';
                
                if ($status === 'approved' && !empty($requesterEmail)) {
                    // Get donor contact information
                    $donorInfo = [];
                    $currentUser = $this->userModel->getUserByCin($userId);
                    if ($currentUser) {
                        $donorInfo['name'] = trim(($currentUser['firstname'] ?? '') . ' ' . ($currentUser['lastname'] ?? ''));
                        $donorInfo['email'] = $currentUser['email'] ?? '';
                        $donorInfo['phone'] = $currentUser['phone'] ?? '';
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
                    
                    // Send approval email with donor contact info
                    $emailService->sendApprovalEmailWithDonorContact($requesterEmail, $request, $donation, $donorInfo);
                } elseif ($status === 'denied' && !empty($requesterEmail)) {
                    $reason = trim($_POST['reason'] ?? '');
                    $emailService->sendRejectionEmail($requesterEmail, $request, $reason);
                }
            }
        }
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * Submit donation
     */
    public function submit() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?action=donations');
            exit;
        }

        $userId = (int)$_SESSION['userID'];
        $currentUser = $this->userModel->getUserByCin($userId);
        
        // Get and validate input
        $donor_name = trim($_POST['donor_name'] ?? '');
        $donor_email = trim($_POST['donor_email'] ?? '');
        $donor_phone = trim($_POST['donor_phone'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = $_POST['category'] ?? '';
        $quantity = $_POST['quantity'] ?? '';
        
        // Validation
        $errors = [];
        
        if (empty($donor_name) || !preg_match('/^[A-Za-zÀ-ÿ\s]{3,50}$/u', $donor_name)) {
            $errors[] = 'Le nom doit contenir uniquement des lettres (3 à 50 caractères)';
        }
        
        if (!empty($donor_email) && !filter_var($donor_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide';
        }
        
        if (!empty($donor_phone) && !preg_match('/^[0-9]{8}$/', $donor_phone)) {
            $errors[] = 'Le téléphone doit contenir exactement 8 chiffres';
        }
        
        if (empty($title) || strlen($title) < 3) {
            $errors[] = 'Le titre doit contenir au moins 3 caractères';
        }
        
        if (empty($category)) {
            $errors[] = 'La catégorie est obligatoire';
        }
        
        if (empty($quantity) || !is_numeric($quantity) || (int)$quantity < 1) {
            $errors[] = 'La quantité doit être un nombre positif';
        }
        
        // Handle file upload
        $item_image = null;
        if (!empty($_FILES['item_image']['name'])) {
            $uploadResult = $this->handleImageUpload($_FILES['item_image'], $category);
            if ($uploadResult['success']) {
                $item_image = $uploadResult['path'];
            } else {
                $errors[] = $uploadResult['error'];
            }
        }
        
        if (!empty($errors)) {
            $_SESSION['donation_error'] = implode('<br>', $errors);
            $_SESSION['old_donation_data'] = $_POST;
            header('Location: ' . BASE_URL . '/index.php?action=donations&create=1');
            exit;
        }

        try {
            $data = [
                'donor_name' => $donor_name,
                'donor_email' => $donor_email ?: null,
                'donor_phone' => $donor_phone ?: null,
                'title' => $title,
                'description' => $description ?: null,
                'category' => $category,
                'quantity' => (int)$quantity,
                'item_image' => $item_image,
                'user_id' => $userId,
                'status' => 'active'
            ];
            
            $id = $this->donationModel->createDonation($data);
            
            $_SESSION['donation_success'] = 'Donation créée avec succès ! Numéro: #' . $id;
            header('Location: ' . BASE_URL . '/index.php?action=donations');
            exit;
        } catch (Exception $e) {
            error_log("DonationController::submit() error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['donation_error'] = 'Erreur lors de la création de la donation: ' . $e->getMessage();
            $_SESSION['old_donation_data'] = $_POST;
            header('Location: ' . BASE_URL . '/index.php?action=donations&create=1');
            exit;
        }
    }

    /**
     * Handle image upload
     */
    private function handleImageUpload($file, $category) {
        $targetDir = __DIR__ . '/../uploads/donations/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        $categoryDir = $targetDir . $category . '/';
        if (!is_dir($categoryDir)) {
            mkdir($categoryDir, 0755, true);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $file['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            return ['success' => false, 'error' => 'Type de fichier non autorisé. Utilisez JPEG, PNG, GIF ou WebP.'];
        }
        
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'Le fichier est trop volumineux (max 5MB).'];
        }
        
        $originalName = basename($file['name']);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $fileName = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9]/", "_", pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $extension;
        $targetFilePath = $categoryDir . $fileName;
        
        if (!move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            return ['success' => false, 'error' => 'Erreur lors de l\'upload du fichier.'];
        }
        
        return ['success' => true, 'path' => 'uploads/donations/' . $category . '/' . $fileName];
    }

    /**
     * Delete donation (user can delete their own)
     */
    public function delete($id) {
        $userId = (int)$_SESSION['userID'];
        
        $success = $this->donationModel->deleteDonation($id, $userId);
        
        if ($success) {
            $_SESSION['donation_success'] = 'Donation supprimée avec succès';
        } else {
            $_SESSION['donation_error'] = 'Erreur lors de la suppression ou donation introuvable';
        }
        
        header('Location: ' . BASE_URL . '/index.php?action=my_donations');
        exit;
    }
    
    /**
     * Mark donation as fulfilled
     */
    public function markFulfilled($id) {
        $userId = (int)$_SESSION['userID'];
        
        // Verify ownership
        $donation = $this->donationModel->getDonationById($id, $userId);
        if (!$donation) {
            $_SESSION['donation_error'] = 'Donation introuvable ou accès non autorisé';
            header('Location: ' . BASE_URL . '/index.php?action=my_donations');
            exit;
        }
        
        $success = $this->donationModel->updateDonationStatus($id, 'fulfilled');
        
        if ($success) {
            $_SESSION['donation_success'] = 'Donation marquée comme remplie';
        } else {
            $_SESSION['donation_error'] = 'Erreur lors de la mise à jour';
        }
        
        header('Location: ' . BASE_URL . '/index.php?action=my_donations');
        exit;
    }
}


