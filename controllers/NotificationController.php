<?php
/**
 * Notification Controller
 * Handles user notification operations
 */
// Disable error display and clean output buffers
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Clean any existing output buffers to ensure clean JSON output
while (ob_get_level()) {
    ob_end_clean();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../models/Notification.php';

class NotificationController {
    private $pdo;
    private $notificationModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        try {
            $this->pdo = Database::connect();
            $this->notificationModel = new Notification($this->pdo);
        } catch (Exception $e) {
            error_log("NotificationController constructor error: " . $e->getMessage());
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Server initialization error']);
            exit;
        }
    }

    /**
     * Get unread notifications (AJAX endpoint)
     */
    public function getUnread() {
        // Clean output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['userID'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'User not logged in']);
            exit;
        }

        try {
            $userId = (int)$_SESSION['userID'];
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            
            $notifications = $this->notificationModel->getUnread($userId, $limit);
            $count = $this->notificationModel->getUnreadCount($userId);
            
            // Format notifications for frontend
            $formatted = array_map(function($notif) {
                return [
                    'id' => $notif['id'],
                    'type' => $notif['type'],
                    'message' => $notif['message'],
                    'entity_type' => $notif['entity_type'],
                    'entity_id' => $notif['entity_id'],
                    'is_read' => (bool)($notif['is_read'] ?? 0),
                    'actor_name' => trim(($notif['actor_firstname'] ?? '') . ' ' . ($notif['actor_lastname'] ?? '')),
                    'created_at' => $notif['created_at'],
                    'time_ago' => $this->getTimeAgo($notif['created_at'])
                ];
            }, $notifications);
            
            echo json_encode([
                'success' => true,
                'notifications' => $formatted,
                'unread_count' => $count
            ]);
        } catch (Exception $e) {
            error_log("NotificationController error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error fetching notifications'
            ]);
        }
        exit;
    }

    /**
     * Get all notifications (paginated)
     */
    public function getAll() {
        // Clean output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['userID'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'User not logged in']);
            exit;
        }

        try {
            $userId = (int)$_SESSION['userID'];
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            $notifications = $this->notificationModel->getAll($userId, $limit, $offset);
            $count = $this->notificationModel->getUnreadCount($userId);
            
            $formatted = array_map(function($notif) {
                return [
                    'id' => $notif['id'],
                    'type' => $notif['type'],
                    'message' => $notif['message'],
                    'entity_type' => $notif['entity_type'],
                    'entity_id' => $notif['entity_id'],
                    'actor_name' => trim(($notif['actor_firstname'] ?? '') . ' ' . ($notif['actor_lastname'] ?? '')),
                    'is_read' => (bool)$notif['is_read'],
                    'created_at' => $notif['created_at'],
                    'time_ago' => $this->getTimeAgo($notif['created_at'])
                ];
            }, $notifications);
            
            echo json_encode([
                'success' => true,
                'notifications' => $formatted,
                'unread_count' => $count
            ]);
        } catch (Exception $e) {
            error_log("NotificationController error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error fetching notifications'
            ]);
        }
        exit;
    }

    /**
     * Mark notification as read
     */
    public function markAsRead() {
        // Clean output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['userID'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'User not logged in']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            $notificationId = isset($data['id']) ? (int)$data['id'] : 0;
            $userId = (int)$_SESSION['userID'];
            
            if ($notificationId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
                exit;
            }
            
            $result = $this->notificationModel->markAsRead($notificationId, $userId);
            
            if ($result) {
                $count = $this->notificationModel->getUnreadCount($userId);
                echo json_encode([
                    'success' => true,
                    'unread_count' => $count
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Failed to mark as read']);
            }
        } catch (Exception $e) {
            error_log("NotificationController error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error updating notification'
            ]);
        }
        exit;
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead() {
        // Clean output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['userID'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'User not logged in']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        try {
            $userId = (int)$_SESSION['userID'];
            $result = $this->notificationModel->markAllAsRead($userId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'unread_count' => 0
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Failed to mark all as read']);
            }
        } catch (Exception $e) {
            error_log("NotificationController error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error updating notifications'
            ]);
        }
        exit;
    }

    /**
     * Get unread count only
     */
    public function getCount() {
        // Clean output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['userID'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'User not logged in', 'count' => 0]);
            exit;
        }

        try {
            $userId = (int)$_SESSION['userID'];
            $count = $this->notificationModel->getUnreadCount($userId);
            
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
        } catch (Exception $e) {
            error_log("NotificationController error: " . $e->getMessage());
            echo json_encode([
                'success' => true,
                'count' => 0
            ]);
        }
        exit;
    }

    /**
     * Format time ago
     */
    private function getTimeAgo($datetime) {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return 'À l\'instant';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return "Il y a {$mins} min";
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "Il y a {$hours}h";
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return "Il y a {$days}j";
        } else {
            return date('d/m/Y', $timestamp);
        }
    }
}

