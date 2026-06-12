<?php
/**
 * Comment Report Model
 * Handles database operations for comment reports
 */
class CommentReport {
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
            $checkTable = $this->pdo->query("SHOW TABLES LIKE 'comment_report'");
            $tableExists = $checkTable && $checkTable->rowCount() > 0;
            
            if (!$tableExists) {
                // Create table with full structure
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
                error_log("comment_report table created successfully");
                return true;
            } else {
                // Table exists, check and add missing columns
                $this->addMissingColumns();
                return true;
            }
        } catch (PDOException $e) {
            error_log("PDO Error creating comment_report table: " . $e->getMessage());
            // If table exists, try to add missing columns
            if (strpos($e->getMessage(), 'already exists') !== false) {
                $this->addMissingColumns();
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error creating comment_report table: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add missing columns to existing table
     */
    private function addMissingColumns() {
        try {
            // Get existing columns
            $stmt = $this->pdo->query("SHOW COLUMNS FROM comment_report");
            $existingColumns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existingColumns[] = $row['Field'];
            }
            
            // List of columns we need
            $requiredColumns = [
                'description' => "ALTER TABLE comment_report ADD COLUMN description TEXT AFTER reason",
                'admin_notes' => "ALTER TABLE comment_report ADD COLUMN admin_notes TEXT AFTER status",
                'reviewed_by' => "ALTER TABLE comment_report ADD COLUMN reviewed_by INT NULL AFTER admin_notes",
                'reviewed_at' => "ALTER TABLE comment_report ADD COLUMN reviewed_at TIMESTAMP NULL AFTER reviewed_by"
            ];
            
            // Add missing columns
            foreach ($requiredColumns as $column => $sql) {
                if (!in_array($column, $existingColumns)) {
                    try {
                        $this->pdo->exec($sql);
                        error_log("Added column '{$column}' to comment_report table");
                    } catch (PDOException $e) {
                        error_log("Error adding column '{$column}': " . $e->getMessage());
                    }
                }
            }
            
            // Ensure UNIQUE constraint exists
            try {
                $indexes = $this->pdo->query("SHOW INDEXES FROM comment_report WHERE Key_name = 'unique_report'");
                if ($indexes->rowCount() == 0) {
                    $this->pdo->exec("ALTER TABLE comment_report ADD UNIQUE KEY unique_report (id_comment, id_user)");
                    error_log("Added UNIQUE constraint to comment_report table");
                }
            } catch (PDOException $e) {
                // Constraint might already exist, ignore
            }
            
        } catch (Exception $e) {
            error_log("Error adding missing columns: " . $e->getMessage());
        }
    }

    /**
     * Create a new report
     */
    public function create($commentId, $userId, $reason = 'other', $description = '') {
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
            if (!$stmt) {
                $error = $this->pdo->errorInfo();
                error_log("Error preparing create statement: " . print_r($error, true));
                return ['success' => false, 'error' => 'Database error: ' . ($error[2] ?? 'Unknown error')];
            }
            
            $params = [
                ':id_comment' => (int)$commentId,
                ':id_user' => (int)$userId,
                ':reason' => $reason
            ];
            
            if ($hasDescription) {
                $params[':description'] = trim($description);
            }
            
            $result = $stmt->execute($params);
            
            if (!$result) {
                $error = $stmt->errorInfo();
                error_log("Error executing create: " . print_r($error, true));
                return ['success' => false, 'error' => 'Database error: ' . ($error[2] ?? 'Unknown error')];
            }
            
            return ['success' => true, 'report_id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            error_log("PDO Error creating report: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("Error creating report: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Check if user has already reported this comment
     */
    public function hasAlreadyReported($commentId, $userId) {
        try {
            $this->createTableIfNotExists();
            
            $sql = "SELECT COUNT(*) as count FROM comment_report 
                    WHERE id_comment = :id_comment AND id_user = :id_user";
            $stmt = $this->pdo->prepare($sql);
            if (!$stmt) {
                error_log("Error preparing hasAlreadyReported statement");
                return false;
            }
            $stmt->execute([
                ':id_comment' => (int)$commentId,
                ':id_user' => (int)$userId
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("PDO Error hasAlreadyReported: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error hasAlreadyReported: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all reports with filters
     */
    public function getAll($status = null, $limit = 50, $offset = 0) {
        try {
            $sql = "SELECT r.*, 
                    c.comment_text, c.id_post,
                    u.firstname, u.lastname, u.email as reporter_email,
                    (SELECT COUNT(*) FROM comment_report WHERE id_comment = r.id_comment) as total_reports
                    FROM comment_report r
                    LEFT JOIN post_comment c ON r.id_comment = c.id_comment
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
     * Get reports for a specific comment
     */
    public function getReportsByComment($commentId) {
        try {
            $sql = "SELECT r.*, 
                    u.firstname, u.lastname, u.email as reporter_email
                    FROM comment_report r
                    LEFT JOIN users u ON r.id_user = u.cin
                    WHERE r.id_comment = :id_comment
                    ORDER BY r.date_report DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_comment' => (int)$commentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting reports by comment: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update report status
     */
    public function updateStatus($reportId, $status, $adminId = null, $adminNotes = null) {
        try {
            $validStatuses = ['pending', 'reviewed', 'resolved'];
            if (!in_array($status, $validStatuses)) {
                return false;
            }
            
            $sql = "UPDATE comment_report 
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
                    c.comment_text, c.id_post,
                    u.firstname, u.lastname, u.email as reporter_email
                    FROM comment_report r
                    LEFT JOIN post_comment c ON r.id_comment = c.id_comment
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
     * Get pending reports count
     */
    public function getPendingCount() {
        try {
            $sql = "SELECT COUNT(*) as count FROM comment_report WHERE status = 'pending'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Error getPendingCount: " . $e->getMessage());
            return 0;
        }
    }
}





