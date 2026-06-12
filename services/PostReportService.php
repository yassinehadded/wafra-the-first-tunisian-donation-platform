<?php
/**
 * Post Report Service
 * Handles post reporting operations
 */
class PostReportService {
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
            // Check if table exists
            $checkTable = $this->pdo->query("SHOW TABLES LIKE 'post_report'");
            $tableExists = $checkTable && $checkTable->rowCount() > 0;
            
            if (!$tableExists) {
                // Create table with full structure
                $sql = "CREATE TABLE post_report (
                    id_report INT AUTO_INCREMENT PRIMARY KEY,
                    id_post INT NOT NULL,
                    id_user INT NOT NULL,
                    reason TEXT,
                    description TEXT,
                    status VARCHAR(50) DEFAULT 'pending',
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
                error_log("post_report table created successfully");
                return true;
            } else {
                // Table exists, check and add missing columns
                $this->addMissingColumns();
                return true;
            }
        } catch (PDOException $e) {
            error_log("PDO Error creating post_report table: " . $e->getMessage());
            // If table exists, try to add missing columns
            if (strpos($e->getMessage(), 'already exists') !== false) {
                $this->addMissingColumns();
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error creating post_report table: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add missing columns to existing table
     */
    private function addMissingColumns() {
        try {
            // Get existing columns
            $stmt = $this->pdo->query("SHOW COLUMNS FROM post_report");
            $existingColumns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existingColumns[] = $row['Field'];
            }
            
            // List of columns we need
            $requiredColumns = [
                'description' => "ALTER TABLE post_report ADD COLUMN description TEXT AFTER reason",
                'admin_notes' => "ALTER TABLE post_report ADD COLUMN admin_notes TEXT AFTER status",
                'reviewed_by' => "ALTER TABLE post_report ADD COLUMN reviewed_by INT NULL AFTER admin_notes",
                'reviewed_at' => "ALTER TABLE post_report ADD COLUMN reviewed_at TIMESTAMP NULL AFTER reviewed_by"
            ];
            
            // Add missing columns
            foreach ($requiredColumns as $column => $sql) {
                if (!in_array($column, $existingColumns)) {
                    try {
                        $this->pdo->exec($sql);
                        error_log("Added column '{$column}' to post_report table");
                    } catch (PDOException $e) {
                        error_log("Error adding column '{$column}': " . $e->getMessage());
                        // Continue with other columns even if one fails
                    }
                }
            }
            
            // Ensure UNIQUE constraint exists
            try {
                $indexes = $this->pdo->query("SHOW INDEXES FROM post_report WHERE Key_name = 'unique_report'");
                if ($indexes->rowCount() == 0) {
                    $this->pdo->exec("ALTER TABLE post_report ADD UNIQUE KEY unique_report (id_post, id_user)");
                    error_log("Added UNIQUE constraint to post_report table");
                }
            } catch (PDOException $e) {
                // Constraint might already exist, ignore
                error_log("Note: unique_report constraint check: " . $e->getMessage());
            }
            
        } catch (Exception $e) {
            error_log("Error adding missing columns: " . $e->getMessage());
        }
    }
    
    /**
     * Report a post
     */
    public function reportPost($id_post, $id_user, $reason = 'other', $description = '') {
        try {
            // Ensure table exists and has all required columns
            $this->createTableIfNotExists();
            
            // Validate reason
            $validReasons = ['spam', 'harassment', 'hate_speech', 'fake_information', 'inappropriate_content', 'other'];
            if (!in_array($reason, $validReasons)) {
                $reason = 'other';
            }
            
            // Check if description column exists
            $checkDesc = $this->pdo->query("SHOW COLUMNS FROM post_report LIKE 'description'");
            $hasDescription = $checkDesc && $checkDesc->rowCount() > 0;
            
            if ($hasDescription) {
                $sql = "INSERT INTO post_report (id_post, id_user, reason, description) 
                        VALUES (:id_post, :id_user, :reason, :description)
                        ON DUPLICATE KEY UPDATE 
                            reason = VALUES(reason), 
                            description = VALUES(description), 
                            date_report = CURRENT_TIMESTAMP,
                            status = 'pending'";
            } else {
                // Fallback: use only existing columns (for backward compatibility)
                $sql = "INSERT INTO post_report (id_post, id_user, reason) 
                        VALUES (:id_post, :id_user, :reason)
                        ON DUPLICATE KEY UPDATE 
                            reason = VALUES(reason), 
                            date_report = CURRENT_TIMESTAMP,
                            status = 'pending'";
            }
            
            $stmt = $this->pdo->prepare($sql);
            if (!$stmt) {
                $error = $this->pdo->errorInfo();
                error_log("Error preparing reportPost statement: " . print_r($error, true));
                return ['success' => false, 'error' => 'Database error: ' . ($error[2] ?? 'Unknown error')];
            }
            
            // Prepare parameters based on whether description column exists
            $params = [
                ':id_post' => (int)$id_post,
                ':id_user' => (int)$id_user,
                ':reason' => $reason
            ];
            
            if ($hasDescription) {
                $params[':description'] = trim($description);
            }
            
            $result = $stmt->execute($params);
            
            if (!$result) {
                $error = $stmt->errorInfo();
                error_log("Error executing reportPost: " . print_r($error, true));
                return ['success' => false, 'error' => 'Database error: ' . ($error[2] ?? 'Unknown error')];
            }
            
            return ['success' => true, 'report_id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            error_log("PDO Error reportPost: " . $e->getMessage());
            error_log("SQL State: " . $e->getCode());
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("Error reportPost: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Check if user has already reported a post
     */
    public function isReported($id_post, $id_user) {
        try {
            // Ensure table exists first
            $this->createTableIfNotExists();
            
            $sql = "SELECT COUNT(*) as count FROM post_report WHERE id_post = :id_post AND id_user = :id_user";
            $stmt = $this->pdo->prepare($sql);
            if (!$stmt) {
                error_log("Error preparing isReported statement");
                return false;
            }
            $stmt->execute([
                ':id_post' => (int)$id_post,
                ':id_user' => (int)$id_user
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("PDO Error isReported: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error isReported: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get report count for a post
     */
    public function getReportCount($id_post) {
        try {
            $sql = "SELECT COUNT(*) as count FROM post_report WHERE id_post = :id_post";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_post' => (int)$id_post]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Error getReportCount: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get all reports with filters
     */
    public function getAllReports($status = null, $limit = 50, $offset = 0) {
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
        } catch (Exception $e) {
            error_log("Error getAllReports: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get reports for a specific post
     */
    public function getReportsByPost($id_post) {
        try {
            $sql = "SELECT r.*, 
                    u.firstname, u.lastname, u.email as reporter_email
                    FROM post_report r
                    LEFT JOIN users u ON r.id_user = u.cin
                    WHERE r.id_post = :id_post
                    ORDER BY r.date_report DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_post' => (int)$id_post]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getReportsByPost: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update report status
     */
    public function updateStatus($report_id, $status, $admin_id = null, $admin_notes = null) {
        try {
            $validStatuses = ['pending', 'reviewed', 'dismissed'];
            if (!in_array($status, $validStatuses)) {
                return false;
            }
            
            $sql = "UPDATE post_report 
                    SET status = :status, 
                        reviewed_by = :admin_id,
                        reviewed_at = CURRENT_TIMESTAMP";
            
            if ($admin_notes !== null) {
                $sql .= ", admin_notes = :admin_notes";
            }
            
            $sql .= " WHERE id_report = :report_id";
            
            $stmt = $this->pdo->prepare($sql);
            $params = [
                ':status' => $status,
                ':admin_id' => $admin_id ? (int)$admin_id : null,
                ':report_id' => (int)$report_id
            ];
            
            if ($admin_notes !== null) {
                $params[':admin_notes'] = trim($admin_notes);
            }
            
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error updateStatus: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get pending reports count
     */
    public function getPendingCount() {
        try {
            $sql = "SELECT COUNT(*) as count FROM post_report WHERE status = 'pending'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Error getPendingCount: " . $e->getMessage());
            return 0;
        }
    }
}


