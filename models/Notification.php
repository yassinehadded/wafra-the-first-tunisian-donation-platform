<?php
/**
 * Notification Model
 * Handles database operations for notifications
 */
class Notification {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->createTableIfNotExists();
    }
    
    /**
     * Create table if it doesn't exist
     */
    private function createTableIfNotExists() {
        try {
            $checkTable = $this->pdo->query("SHOW TABLES LIKE 'notifications'");
            $tableExists = $checkTable && $checkTable->rowCount() > 0;
            
            if (!$tableExists) {
                $sql = "CREATE TABLE notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    actor_id INT NULL,
                    type VARCHAR(50) NOT NULL,
                    entity_type VARCHAR(50) NOT NULL,
                    entity_id INT NOT NULL,
                    message TEXT NOT NULL,
                    is_read TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user (user_id),
                    INDEX idx_read (is_read),
                    INDEX idx_created (created_at),
                    INDEX idx_user_read (user_id, is_read)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                $this->pdo->exec($sql);
                error_log("notifications table created successfully");
                return true;
            }
            return true;
        } catch (PDOException $e) {
            error_log("PDO Error creating notifications table: " . $e->getMessage());
            try {
                $sql = "CREATE TABLE IF NOT EXISTS notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    actor_id INT NULL,
                    type VARCHAR(50) NOT NULL,
                    entity_type VARCHAR(50) NOT NULL,
                    entity_id INT NOT NULL,
                    message TEXT NOT NULL,
                    is_read TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user (user_id),
                    INDEX idx_read (is_read),
                    INDEX idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $this->pdo->exec($sql);
                return true;
            } catch (Exception $e2) {
                error_log("Error creating notifications table (fallback): " . $e2->getMessage());
                return false;
            }
        } catch (Exception $e) {
            error_log("Error creating notifications table: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new notification
     */
    public function create($userId, $type, $entityType, $entityId, $message, $actorId = null) {
        try {
            $sql = "INSERT INTO notifications (user_id, actor_id, type, entity_type, entity_id, message) 
                    VALUES (:user_id, :actor_id, :type, :entity_type, :entity_id, :message)";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':user_id' => (int)$userId,
                ':actor_id' => $actorId ? (int)$actorId : null,
                ':type' => $type,
                ':entity_type' => $entityType,
                ':entity_id' => (int)$entityId,
                ':message' => trim($message)
            ]);
            
            if ($result) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get unread notifications for a user
     */
    public function getUnread($userId, $limit = 10) {
        try {
            $sql = "SELECT n.*, 
                    u.firstname as actor_firstname, u.lastname as actor_lastname
                    FROM notifications n
                    LEFT JOIN users u ON n.actor_id = u.cin
                    WHERE n.user_id = :user_id AND n.is_read = 0
                    ORDER BY n.created_at DESC
                    LIMIT :limit";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting unread notifications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all notifications for a user
     */
    public function getAll($userId, $limit = 20, $offset = 0) {
        try {
            $sql = "SELECT n.*, 
                    u.firstname as actor_firstname, u.lastname as actor_lastname
                    FROM notifications n
                    LEFT JOIN users u ON n.actor_id = u.cin
                    WHERE n.user_id = :user_id
                    ORDER BY n.created_at DESC
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all notifications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get unread count for a user
     */
    public function getUnreadCount($userId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM notifications 
                    WHERE user_id = :user_id AND is_read = 0";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => (int)$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        try {
            $sql = "UPDATE notifications 
                    SET is_read = 1 
                    WHERE id = :id AND user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':id' => (int)$notificationId,
                ':user_id' => (int)$userId
            ]);
        } catch (PDOException $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($userId) {
        try {
            $sql = "UPDATE notifications 
                    SET is_read = 1 
                    WHERE user_id = :user_id AND is_read = 0";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':user_id' => (int)$userId]);
        } catch (PDOException $e) {
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete old notifications (soft delete or hard delete)
     */
    public function deleteOld($userId, $daysOld = 30) {
        try {
            $sql = "DELETE FROM notifications 
                    WHERE user_id = :user_id 
                    AND is_read = 1 
                    AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
            $stmt->bindValue(':days', (int)$daysOld, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting old notifications: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if notification already exists (prevent duplicates)
     */
    public function exists($userId, $type, $entityType, $entityId, $actorId = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM notifications 
                    WHERE user_id = :user_id 
                    AND type = :type 
                    AND entity_type = :entity_type 
                    AND entity_id = :entity_id
                    AND is_read = 0";
            
            $params = [
                ':user_id' => (int)$userId,
                ':type' => $type,
                ':entity_type' => $entityType,
                ':entity_id' => (int)$entityId
            ];
            
            if ($actorId !== null) {
                $sql .= " AND actor_id = :actor_id";
                $params[':actor_id'] = (int)$actorId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error checking notification existence: " . $e->getMessage());
            return false;
        }
    }
}





