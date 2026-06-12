<?php
/**
 * Post Report Model
 * Handles database operations for post reports
 */
class PostReport {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        // Ensure table exists
        $this->createTableIfNotExists();
    }
    
    /**
     * Create table if it doesn't exist
     */
    private function createTableIfNotExists() {
        try {
            // Check if table exists
            $checkTable = $this->pdo->query("SHOW TABLES LIKE 'post_report'");
            if ($checkTable->rowCount() > 0) {
                return true; // Table already exists
            }
            
            $sql = "CREATE TABLE post_report (
                id_report INT AUTO_INCREMENT PRIMARY KEY,
                id_post INT NOT NULL,
                id_user INT NOT NULL,
                reason ENUM('spam', 'harassment', 'hate_speech', 'fake_information', 'inappropriate_content', 'other') DEFAULT 'other',
                description TEXT,
                status ENUM('pending', 'reviewed', 'dismissed') DEFAULT 'pending',
                admin_notes TEXT,
                reviewed_by INT NULL,
                reviewed_at TIMESTAMP NULL,
                date_report TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_post (id_post),
                INDEX idx_user (id_user),
                INDEX idx_status (status),
                INDEX idx_date (date_report),
                UNIQUE KEY unique_report (id_post, id_user)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->pdo->exec($sql);
            return true;
        } catch (Exception $e) {
            error_log("Error creating post_report table in model: " . $e->getMessage());
            // Try to create without constraints if error
            try {
                $sql = "CREATE TABLE IF NOT EXISTS post_report (
                    id_report INT AUTO_INCREMENT PRIMARY KEY,
                    id_post INT NOT NULL,
                    id_user INT NOT NULL,
                    reason ENUM('spam', 'harassment', 'hate_speech', 'fake_information', 'inappropriate_content', 'other') DEFAULT 'other',
                    description TEXT,
                    status ENUM('pending', 'reviewed', 'dismissed') DEFAULT 'pending',
                    admin_notes TEXT,
                    reviewed_by INT NULL,
                    reviewed_at TIMESTAMP NULL,
                    date_report TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_post (id_post),
                    INDEX idx_user (id_user),
                    INDEX idx_status (status),
                    INDEX idx_date (date_report),
                    UNIQUE KEY unique_report (id_post, id_user)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $this->pdo->exec($sql);
                return true;
            } catch (Exception $e2) {
                error_log("Error creating post_report table (without FK): " . $e2->getMessage());
                return false;
            }
        }
    }

    /**
     * Create a new report
     */
    public function create($postId, $userId, $reason = 'other', $description = '') {
        try {
            $validReasons = ['spam', 'harassment', 'hate_speech', 'fake_information', 'inappropriate_content', 'other'];
            if (!in_array($reason, $validReasons)) {
                $reason = 'other';
            }
            
            $sql = "INSERT INTO post_report (id_post, id_user, reason, description) 
                    VALUES (:id_post, :id_user, :reason, :description)
                    ON DUPLICATE KEY UPDATE 
                        reason = VALUES(reason), 
                        description = VALUES(description), 
                        date_report = CURRENT_TIMESTAMP,
                        status = 'pending'";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':id_post' => (int)$postId,
                ':id_user' => (int)$userId,
                ':reason' => $reason,
                ':description' => trim($description)
            ]);
            
            if ($result) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error creating report: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user has already reported this post
     */
    public function hasAlreadyReported($postId, $userId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM post_report 
                    WHERE id_post = :id_post AND id_user = :id_user";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id_post' => (int)$postId,
                ':id_user' => (int)$userId
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error checking report: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all reports for a post
     */
    public function getReportsByPost($postId) {
        try {
            $sql = "SELECT r.*, 
                    u.firstname, u.lastname, u.email as reporter_email
                    FROM post_report r
                    LEFT JOIN users u ON r.id_user = u.cin
                    WHERE r.id_post = :id_post
                    ORDER BY r.date_report DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_post' => (int)$postId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting reports: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update report status
     */
    public function updateStatus($reportId, $status, $adminId = null, $adminNotes = null) {
        try {
            $validStatuses = ['pending', 'reviewed', 'dismissed'];
            if (!in_array($status, $validStatuses)) {
                return false;
            }
            
            $sql = "UPDATE post_report 
                    SET status = :status, 
                        reviewed_by = :admin_id,
                        reviewed_at = CURRENT_TIMESTAMP";
            
            if ($adminNotes !== null) {
                $sql .= ", admin_notes = :admin_notes";
            }
            
            $sql .= " WHERE id_report = :report_id";
            
            $stmt = $this->pdo->prepare($sql);
            $params = [
                ':status' => $status,
                ':admin_id' => $adminId ? (int)$adminId : null,
                ':report_id' => (int)$reportId
            ];
            
            if ($adminNotes !== null) {
                $params[':admin_notes'] = trim($adminNotes);
            }
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating report status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get report by ID
     */
    public function find($reportId) {
        try {
            $sql = "SELECT r.*, 
                    p.titre as post_title, p.description as post_description,
                    u.firstname, u.lastname, u.email as reporter_email
                    FROM post_report r
                    LEFT JOIN post p ON r.id_post = p.id_post
                    LEFT JOIN users u ON r.id_user = u.cin
                    WHERE r.id_report = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => (int)$reportId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding report: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all reports with filters
     */
    public function getAll($status = null, $limit = 50, $offset = 0) {
        try {
            $sql = "SELECT r.*, 
                    p.titre as post_title, p.description as post_description,
                    u.firstname, u.lastname, u.email as reporter_email,
                    (SELECT COUNT(*) FROM post_report WHERE id_post = r.id_post) as total_reports
                    FROM post_report r
                    LEFT JOIN post p ON r.id_post = p.id_post
                    LEFT JOIN users u ON r.id_user = u.cin
                    WHERE 1=1";
            $params = [];
            
            if ($status) {
                $sql .= " AND r.status = :status";
                $params[':status'] = $status;
            }
            
            $sql .= " ORDER BY r.date_report DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all reports: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete all reports for a specific post
     * This is called automatically when a post is deleted
     */
    public function deleteReportsByPost($postId) {
        try {
            $sql = "DELETE FROM post_report WHERE id_post = :id_post";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([':id_post' => (int)$postId]);
            
            if ($result) {
                $deletedCount = $stmt->rowCount();
                error_log("Deleted $deletedCount report(s) for post ID: $postId");
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error deleting reports by post: " . $e->getMessage());
            return false;
        }
    }
}

