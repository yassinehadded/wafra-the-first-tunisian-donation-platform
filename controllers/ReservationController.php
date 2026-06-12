<?php
/**
 * Reservation Controller
 * Handles reservation-related operations
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../models/Reservation.php';

class ReservationController {
    private $pdo;
    private $reservationModel;

    public function __construct() {
        $this->pdo = Database::connect();
        $this->reservationModel = new Reservation($this->pdo);
    }

    /**
     * Create new reservation
     */
    public function create() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
            header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $redirectUrl = BASE_URL . '/view/frontoffice/index.php#reservations';
            // Prevent redirect loops by ensuring we don't add duplicate paths
            $redirectUrl = str_replace('/view/frontoffice/view/frontoffice', '/view/frontoffice', $redirectUrl);
            header('Location: ' . $redirectUrl);
            exit;
        }

        try {
            // Log all POST data for debugging
            error_log("ReservationController::create() - POST data: " . json_encode($_POST));
            error_log("ReservationController::create() - SESSION userID: " . ($_SESSION['userID'] ?? 'NOT SET'));
            
            // Ensure userID is set
            if (empty($_SESSION['userID'])) {
                error_log("ERROR: Session userID is empty!");
                throw new Exception("Session utilisateur invalide. Veuillez vous reconnecter.");
            }
            
            $userCin = (int)$_SESSION['userID'];
            error_log("ReservationController: Creating reservation with CIN=$userCin, UserID={$_SESSION['userID']}");
            
            // Validate required fields
            if (empty($_POST['evenement_id'])) {
                throw new Exception("Veuillez sélectionner un événement.");
            }
            if (empty($_POST['nom'])) {
                throw new Exception("Le nom est requis.");
            }
            if (empty($_POST['tel'])) {
                throw new Exception("Le téléphone est requis.");
            }
            if (empty($_POST['lieu'])) {
                throw new Exception("Le lieu est requis.");
            }
            if (empty($_POST['date_naissance'])) {
                throw new Exception("La date de naissance est requise.");
            }
            if (empty($_POST['softskills'])) {
                throw new Exception("Veuillez sélectionner une compétence.");
            }
            
            $data = [
                'evenement_id' => $_POST['evenement_id'],
                'cin' => $userCin,
                'nom' => $_POST['nom'],
                'tel' => $_POST['tel'],
                'lieu' => $_POST['lieu'],
                'date_naissance' => $_POST['date_naissance'],
                'softskills' => $_POST['softskills'],
                'email' => $_POST['email'] ?? null
            ];
            
            // Verify CIN is set
            if (empty($data['cin']) || $data['cin'] <= 0) {
                error_log("ERROR: CIN is invalid: " . var_export($data['cin'], true));
                throw new Exception("CIN utilisateur invalide. Veuillez vous reconnecter.");
            }
            
            error_log("ReservationController: Data to insert: " . json_encode($data));

            $reservationId = $this->reservationModel->insert($data);
            
            // Log for debugging
            error_log("Reservation created successfully: ID=$reservationId, CIN={$data['cin']}, UserID={$_SESSION['userID']}");
            
            // Verify reservation was created and can be retrieved
            $createdReservation = $this->reservationModel->select($reservationId);
            error_log("Created reservation verification: " . json_encode($createdReservation));
            
            $_SESSION['success'] = 'Réservation créée avec succès!';
            // Get baseUrl from config or use BASE_URL
            $redirectBase = defined('BASE_URL') ? BASE_URL : 'http://localhost/wafra/wafra-integration';
            // Force page reload by adding a timestamp to prevent cache
            $redirectUrl = $redirectBase . '/view/frontoffice/index.php?refresh=' . time() . '#reservations';
            // Prevent redirect loops by ensuring we don't add duplicate paths
            $redirectUrl = str_replace('/view/frontoffice/view/frontoffice', '/view/frontoffice', $redirectUrl);
            header('Location: ' . $redirectUrl);
            exit;
        } catch (Exception $e) {
            $_SESSION['errors'] = [$e->getMessage()];
            $redirectUrl = BASE_URL . '/view/frontoffice/index.php#reservations';
            // Prevent redirect loops by ensuring we don't add duplicate paths
            $redirectUrl = str_replace('/view/frontoffice/view/frontoffice', '/view/frontoffice', $redirectUrl);
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    /**
     * Update reservation
     */
    public function update() {
        session_start();

        if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
            header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $redirectUrl = BASE_URL . '/view/frontoffice/index.php#reservations';
            // Prevent redirect loops by ensuring we don't add duplicate paths
            $redirectUrl = str_replace('/view/frontoffice/view/frontoffice', '/view/frontoffice', $redirectUrl);
            header('Location: ' . $redirectUrl);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);

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

            $this->reservationModel->update($id, $data);
            
            $_SESSION['success'] = 'Réservation mise à jour avec succès!';
            $redirectUrl = BASE_URL . '/view/frontoffice/index.php#reservations';
            // Prevent redirect loops by ensuring we don't add duplicate paths
            $redirectUrl = str_replace('/view/frontoffice/view/frontoffice', '/view/frontoffice', $redirectUrl);
            header('Location: ' . $redirectUrl);
            exit;
        } catch (Exception $e) {
            $_SESSION['errors'] = [$e->getMessage()];
            $redirectUrl = BASE_URL . '/view/frontoffice/index.php#reservations';
            // Prevent redirect loops by ensuring we don't add duplicate paths
            $redirectUrl = str_replace('/view/frontoffice/view/frontoffice', '/view/frontoffice', $redirectUrl);
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    /**
     * Delete reservation
     */
    public function delete($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
            header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
            exit;
        }

        try {
            $this->reservationModel->delete($id);
            $_SESSION['success'] = 'Réservation supprimée avec succès!';
        } catch (Exception $e) {
            $_SESSION['errors'] = ['Erreur lors de la suppression de la réservation.'];
        }

        $redirectUrl = BASE_URL . '/view/frontoffice/index.php#reservations';
        // Prevent redirect loops by ensuring we don't add duplicate paths
        $redirectUrl = str_replace('/view/frontoffice/view/frontoffice', '/view/frontoffice', $redirectUrl);
        header('Location: ' . $redirectUrl);
        exit;
    }
}

