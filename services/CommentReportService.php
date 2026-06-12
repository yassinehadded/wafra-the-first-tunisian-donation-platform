<?php
/**
 * Comment Report Service
 * Handles comment reporting operations
 */
class CommentReportService {
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
            $checkTable = $this->pdo->query("SHOW TABLES LIKE 'comment_report'");
            if ($checkTable->rowCount() > 0) {
                return true;
            }
            
            $sql = "CREATE TABLE comment_report (
                id_report INT AUTO_INCREMENT PRIMARY KEY,
                id_comment INT NOT NULL,
                id_user INT NOT NULL,
                reason ENUM('spam', 'harassment', 'inappropriate_content', 'other') DEFAULT 'other',
                description TEXT,
                status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
                admin_notes TEXT,
                reviewed_by INT NULL,
                reviewed_at TIMESTAMP NULL,
                date_report TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_comment (id_comment),
                INDEX idx_user (id_user),
                INDEX idx_status (status),
                INDEX idx_date (date_report),
                UNIQUE KEY unique_report (id_comment, id_user)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->pdo->exec($sql);
            return true;
        } catch (Exception $e) {
            error_log("Error creating comment_report table: " . $e->getMessage());
            try {
                $sql = "CREATE TABLE IF NOT EXISTS comment_report (
                    id_report INT AUTO_INCREMENT PRIMARY KEY,
                    id_comment INT NOT NULL,
                    id_user INT NOT NULL,
                    reason ENUM('spam', 'harassment', 'inappropriate_content', 'other') DEFAULT 'other',
                    description TEXT,
                    status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
                    admin_notes TEXT,
                    reviewed_by INT NULL,
                    reviewed_at TIMESTAMP NULL,
                    date_report TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_report (id_comment, id_user)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $this->pdo->exec($sql);
                return true;
            } catch (Exception $e2) {
                error_log("Error creating comment_report table (fallback): " . $e2->getMessage());
                return false;
            }
        }
    }
    
    /**
     * Report a comment
     */
    public function reportComment($id_comment, $id_user, $reason = 'other', $description = '') {
        try {
            $validReasons = ['spam', 'harassment', 'inappropriate_content', 'other'];
            if (!in_array($reason, $validReasons)) {
                $reason = 'other';
            }
            
            // Check if description column exists
            $checkDesc = $this->pdo->query("SHOW COLUMNS FROM comment_report LIKE 'description'");
            $hasDescription = $checkDesc && $checkDesc->rowCount() > 0;
            
            if ($hasDescription) {
                $sql = "INSERT INTO comment_report (id_comment, id_user, reason, description) 
                        VALUES (:id_comment, :id_user, :reason, :description)
                        ON DUPLICATE KEY UPDATE 
                            reason = VALUES(reason), 
                            description = VALUES(description), 
                            date_report = CURRENT_TIMESTAMP,
                            status = 'pending'";
            } else {
                $sql = "INSERT INTO comment_report (id_comment, id_user, reason) 
                        VALUES (:id_comment, :id_user, :reason)
                        ON DUPLICATE KEY UPDATE 
                            reason = VALUES(reason), 
                            date_report = CURRENT_TIMESTAMP,
                            status = 'pending'";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $params = [
                ':id_comment' => (int)$id_comment,
                ':id_user' => (int)$id_user,
                ':reason' => $reason
            ];
            
            if ($hasDescription) {
                $params[':description'] = trim($description);
            }
            
            $result = $stmt->execute($params);
            return $result;
        } catch (Exception $e) {
            error_log("Error reportComment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user has already reported a comment
     */
    public function isReported($id_comment, $id_user) {
        try {
            $sql = "SELECT COUNT(*) as count FROM comment_report WHERE id_comment = :id_comment AND id_user = :id_user";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id_comment' => (int)$id_comment,
                ':id_user' => (int)$id_user
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Error isReported: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get report count for a comment
     */
    public function getReportCount($id_comment) {
        try {
            $sql = "SELECT COUNT(*) as count FROM comment_report WHERE id_comment = :id_comment";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_comment' => (int)$id_comment]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Error getReportCount: " . $e->getMessage());
            return 0;
        }
    }
}





