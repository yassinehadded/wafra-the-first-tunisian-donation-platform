<?php
/**
 * Event Controller
 * Handles event-related operations
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../models/Event.php';

class EventController {
    private $pdo;
    private $eventModel;

    public function __construct() {
        $this->pdo = Database::connect();
        $this->eventModel = new Event($this->pdo);
    }

    /**
     * List events for frontoffice (filtered by user reservations)
     */
    public function listFront() {
        session_start();

        if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
            header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
            exit;
        }

        $userId = (int)$_SESSION['userID'];
        $pageNum = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
        $searchTerm = $_GET['search'] ?? '';

        $eventsResult = $this->eventModel->getEventsByUserReservations($userId, $pageNum, 10, $searchTerm);
        
        // Store in session for view
        $_SESSION['events_data'] = $eventsResult;
        
        header('Location: ' . BASE_URL . '/view/frontoffice/index.php#events');
        exit;
    }

    /**
     * Show event details
     */
    public function show($id) {
        session_start();

        if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
            header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
            exit;
        }

        $event = $this->eventModel->find($id);
        
        if (!$event) {
            header('Location: ' . BASE_URL . '/view/frontoffice/index.php?error=event_not_found#events');
            exit;
        }

        $_SESSION['event_details'] = $event;
        header('Location: ' . BASE_URL . '/view/frontoffice/index.php?event_id=' . $id . '#events');
        exit;
    }
}

