<?php
/**
 * Admin Notification Controller
 * Handles admin notification operations
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

class AdminNotificationController {
    private $pdo;
    private $notificationModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is admin
        if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        
        try {
            $this->pdo = Database::connect();
            $this->notificationModel = new Notification($this->pdo);
        } catch (Exception $e) {
            error_log("AdminNotificationController constructor error: " . $e->getMessage());
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Server initialization error']);
            exit;
        }
    }

    /**
     * Get unread admin notifications
     */
    public function getUnread() {
        // Clean output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $adminId = (int)$_SESSION['userID'];
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            
            // Get admin notifications (types starting with 'admin_')
            $sql = "SELECT n.*, 
                    u.firstname as actor_firstname, u.lastname as actor_lastname
                    FROM notifications n
                    LEFT JOIN users u ON n.actor_id = u.cin
                    WHERE n.user_id = :user_id 
                    AND n.is_read = 0
                    AND n.type LIKE 'admin_%'
                    ORDER BY n.created_at DESC
                    LIMIT :limit";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', $adminId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $count = $this->getAdminUnreadCount($adminId);
            
            $formatted = array_map(function($notif) {
                return [
                    'id' => $notif['id'],
                    'type' => $notif['type'],
                    'message' => $notif['message'],
                    'entity_type' => $notif['entity_type'],
                    'entity_id' => $notif['entity_id'],
                    'actor_name' => trim(($notif['actor_firstname'] ?? '') . ' ' . ($notif['actor_lastname'] ?? '')),
                    'created_at' => $notif['created_at'],
                    'time_ago' => $this->getTimeAgo($notif['created_at']),
                    'priority' => $this->getPriority($notif['type'])
                ];
            }, $notifications);
            
            echo json_encode([
                'success' => true,
                'notifications' => $formatted,
                'unread_count' => $count
            ]);
        } catch (Exception $e) {
            error_log("AdminNotificationController error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error fetching notifications'
            ]);
        }
        exit;
    }

    /**
     * Get unread count for admin
     */
    private function getAdminUnreadCount($adminId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM notifications 
                    WHERE user_id = :user_id 
                    AND is_read = 0
                    AND type LIKE 'admin_%'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $adminId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Error getting admin unread count: " . $e->getMessage());
            return 0;
        }
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
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            $notificationId = isset($data['id']) ? (int)$data['id'] : 0;
            $adminId = (int)$_SESSION['userID'];
            
            if ($notificationId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
                exit;
            }
            
            $result = $this->notificationModel->markAsRead($notificationId, $adminId);
            
            if ($result) {
                $count = $this->getAdminUnreadCount($adminId);
                echo json_encode([
                    'success' => true,
                    'unread_count' => $count
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Failed to mark as read']);
            }
        } catch (Exception $e) {
            error_log("AdminNotificationController error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error updating notification'
            ]);
        }
        exit;
    }

    /**
     * Mark all as read
     */
    public function markAllAsRead() {
        // Clean output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        try {
            $adminId = (int)$_SESSION['userID'];
            
            $sql = "UPDATE notifications 
                    SET is_read = 1 
                    WHERE user_id = :user_id 
                    AND is_read = 0
                    AND type LIKE 'admin_%'";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([':user_id' => $adminId]);
            
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
            error_log("AdminNotificationController error: " . $e->getMessage());
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
        
        try {
            $adminId = (int)$_SESSION['userID'];
            $count = $this->getAdminUnreadCount($adminId);
            
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
        } catch (Exception $e) {
            error_log("AdminNotificationController error: " . $e->getMessage());
            echo json_encode([
                'success' => true,
                'count' => 0
            ]);
        }
        exit;
    }

    /**
     * Get priority level for notification type
     */
    private function getPriority($type) {
        $highPriority = ['admin_post_reported', 'admin_comment_reported', 'admin_user_threshold'];
        return in_array($type, $highPriority) ? 'high' : 'normal';
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
            $mins = floor($diff / 3600);
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

