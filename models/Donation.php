<?php
/**
 * Donation Model
 * Handles all donation-related database operations
 */
class Donation {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new donation
     */
    public function createDonation($data) {
        try {
            // Check if table exists and has required columns
            $this->ensureTableStructure();
            
            $sql = "INSERT INTO donor_offers (donor_name, donor_email, donor_phone, title, description, category, quantity, item_image, date, status, user_id) 
                    VALUES (:donor_name, :donor_email, :donor_phone, :title, :description, :category, :quantity, :item_image, :date, :status, :user_id)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':donor_name' => trim($data['donor_name']),
                ':donor_email' => !empty($data['donor_email']) ? trim($data['donor_email']) : null,
                ':donor_phone' => !empty($data['donor_phone']) ? (int)$data['donor_phone'] : null,
                ':title' => trim($data['title']),
                ':description' => !empty($data['description']) ? trim($data['description']) : null,
                ':category' => $data['category'],
                ':quantity' => (int)$data['quantity'],
                ':item_image' => !empty($data['item_image']) ? $data['item_image'] : null,
                ':date' => $data['date'] ?? date('Y-m-d'),
                ':status' => $data['status'] ?? 'active',
                ':user_id' => !empty($data['user_id']) ? (int)$data['user_id'] : null
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error createDonation: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Data: " . print_r($data, true));
            // Include the actual error message for debugging
            throw new Exception("Erreur lors de la création de la donation: " . $e->getMessage());
        }
    }

    /**
     * Ensure table structure exists (create table or add missing columns)
     */
    private function ensureTableStructure() {
        try {
            // Check if table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'donor_offers'");
            if ($stmt->rowCount() === 0) {
                // Table doesn't exist, create it
                $createTableSql = "CREATE TABLE `donor_offers` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `donor_name` VARCHAR(255) NOT NULL,
                    `donor_email` TEXT DEFAULT NULL,
                    `donor_phone` INT(8) DEFAULT NULL,
                    `title` VARCHAR(255) NOT NULL,
                    `description` TEXT DEFAULT NULL,
                    `category` VARCHAR(100) DEFAULT NULL,
                    `quantity` INT(11) DEFAULT 1,
                    `item_image` VARCHAR(255) DEFAULT NULL,
                    `date` DATE DEFAULT CURDATE(),
                    `status` ENUM('active','fulfilled') DEFAULT 'active',
                    `user_id` INT(11) DEFAULT NULL,
                    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_user_id` (`user_id`),
                    KEY `idx_status` (`status`),
                    KEY `idx_category` (`category`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                $this->pdo->exec($createTableSql);
                error_log("Created donor_offers table");
                return;
            }
            
            // Table exists, check for missing columns
            $stmt = $this->pdo->query("SHOW COLUMNS FROM donor_offers LIKE 'user_id'");
            if ($stmt->rowCount() === 0) {
                // Add missing columns
                $this->pdo->exec("ALTER TABLE `donor_offers` ADD COLUMN `user_id` INT(11) DEFAULT NULL AFTER `status`");
                error_log("Added user_id column to donor_offers");
            }
            
            $stmt = $this->pdo->query("SHOW COLUMNS FROM donor_offers LIKE 'created_at'");
            if ($stmt->rowCount() === 0) {
                $this->pdo->exec("ALTER TABLE `donor_offers` ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `user_id`");
                error_log("Added created_at column to donor_offers");
            }
            
            $stmt = $this->pdo->query("SHOW COLUMNS FROM donor_offers LIKE 'updated_at'");
            if ($stmt->rowCount() === 0) {
                $this->pdo->exec("ALTER TABLE `donor_offers` ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`");
                error_log("Added updated_at column to donor_offers");
            }
        } catch (PDOException $e) {
            error_log("Error ensuring table structure: " . $e->getMessage());
            // Don't throw, let the insert try anyway
        }
    }

    /**
     * Get all donations for a specific user
     */
    public function getUserDonations($userId) {
        try {
            $sql = "SELECT * FROM donor_offers 
                    WHERE user_id = :user_id 
                    ORDER BY date DESC, created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => (int)$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getUserDonations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all donations (admin only)
     */
    public function getAllDonations($filters = []) {
        try {
            $sql = "SELECT d.*, 
                           u.firstname as user_firstname,
                           u.lastname as user_lastname,
                           u.email as user_email
                    FROM donor_offers d
                    LEFT JOIN users u ON d.user_id = u.cin
                    WHERE 1=1";
            
            $params = [];
            
            if (!empty($filters['status'])) {
                $sql .= " AND d.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['category'])) {
                $sql .= " AND d.category = :category";
                $params[':category'] = $filters['category'];
            }
            
            if (!empty($filters['search'])) {
                $searchTerm = '%' . strtolower($filters['search']) . '%';
                $sql .= " AND (
                    LOWER(d.title) LIKE :search 
                    OR LOWER(d.description) LIKE :search 
                    OR LOWER(d.donor_name) LIKE :search
                    OR LOWER(u.firstname) LIKE :search
                    OR LOWER(u.lastname) LIKE :search
                )";
                $params[':search'] = $searchTerm;
            }
            
            $sql .= " ORDER BY d.date DESC, d.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getAllDonations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get active donations (for public listing)
     */
    public function getActiveDonations($limit = null) {
        try {
            $sql = "SELECT * FROM donor_offers 
                    WHERE status = 'active' AND quantity > 0 
                    ORDER BY date DESC, created_at DESC";
            
            if ($limit) {
                $sql .= " LIMIT " . (int)$limit;
            }
            
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getActiveDonations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get donation by ID
     */
    public function getDonationById($id, $userId = null) {
        try {
            $sql = "SELECT * FROM donor_offers WHERE id = :id";
            $params = [':id' => (int)$id];
            
            if ($userId !== null) {
                $sql .= " AND user_id = :user_id";
                $params[':user_id'] = (int)$userId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error getDonationById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update donation status
     */
    public function updateDonationStatus($id, $status) {
        try {
            $sql = "UPDATE donor_offers SET status = :status, updated_at = NOW() WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':status' => $status,
                ':id' => (int)$id
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error updateDonationStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update donation quantity
     */
    public function updateDonationQuantity($id, $quantity) {
        try {
            $sql = "UPDATE donor_offers SET quantity = :quantity, updated_at = NOW() WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':quantity' => (int)$quantity,
                ':id' => (int)$id
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error updateDonationQuantity: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete donation
     */
    public function deleteDonation($id, $userId = null) {
        try {
            $sql = "DELETE FROM donor_offers WHERE id = :id";
            $params = [':id' => (int)$id];
            
            if ($userId !== null) {
                $sql .= " AND user_id = :user_id";
                $params[':user_id'] = (int)$userId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error deleteDonation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get statistics
     */
    public function getStats() {
        try {
            $stats = [
                'total' => 0,
                'active' => 0,
                'fulfilled' => 0
            ];
            
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM donor_offers");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total'] = $result ? (int)$result['total'] : 0;
            
            $stmt = $this->pdo->query("SELECT status, COUNT(*) as count FROM donor_offers GROUP BY status");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['status'] === 'active') {
                    $stats['active'] = (int)$row['count'];
                } elseif ($row['status'] === 'fulfilled') {
                    $stats['fulfilled'] = (int)$row['count'];
                }
            }
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Error getStats: " . $e->getMessage());
            return ['total' => 0, 'active' => 0, 'fulfilled' => 0];
        }
    }

    /**
     * Get donation with user info
     */
    public function getDonationWithUser($id) {
        try {
            $sql = "SELECT d.*, 
                           u.firstname as user_firstname,
                           u.lastname as user_lastname,
                           u.email as user_email,
                           u.profile_picture as user_avatar
                    FROM donor_offers d
                    LEFT JOIN users u ON d.user_id = u.cin
                    WHERE d.id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => (int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getDonationWithUser: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get active donations with user info (for feed)
     */
    public function getActiveDonationsWithUsers($limit = null, $offset = 0) {
        try {
            // Ensure requests table exists
            $this->ensureRequestsTable();
            
            $sql = "SELECT d.*, 
                           u.firstname as user_firstname,
                           u.lastname as user_lastname,
                           u.email as user_email,
                           u.profile_picture as user_avatar,
                           (SELECT COUNT(*) FROM donor_requests WHERE donation_id = d.id) as request_count
                    FROM donor_offers d
                    LEFT JOIN users u ON d.user_id = u.cin
                    WHERE d.status = 'active' AND d.quantity > 0 
                    ORDER BY d.date DESC, d.created_at DESC";
            
            if ($limit) {
                $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            }
            
            $stmt = $this->pdo->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $results ?: [];
        } catch (PDOException $e) {
            error_log("Error getActiveDonationsWithUsers: " . $e->getMessage());
            error_log("SQL: " . $sql);
            return [];
        }
    }

    /**
     * Create donation request
     */
    public function createRequest($donationId, $requesterId, $message) {
        try {
            // Ensure donor_requests table exists
            $this->ensureRequestsTable();
            
            $sql = "INSERT INTO donor_requests (donation_id, requester_id, requester_name, email, message, status, request_date) 
                    VALUES (:donation_id, :requester_id, :requester_name, :email, :message, 'pending', CURDATE())";
            
            // Get requester info
            $userStmt = $this->pdo->prepare("SELECT firstname, lastname, email FROM users WHERE cin = :id");
            $userStmt->execute([':id' => (int)$requesterId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':donation_id' => (int)$donationId,
                ':requester_id' => (int)$requesterId,
                ':requester_name' => trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')),
                ':email' => $user['email'] ?? null,
                ':message' => trim($message)
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error createRequest: " . $e->getMessage());
            throw new Exception("Erreur lors de la création de la demande: " . $e->getMessage());
        }
    }

    /**
     * Get requests for a donation
     */
    public function getDonationRequests($donationId) {
        try {
            $this->ensureRequestsTable();
            
            $sql = "SELECT dr.*, 
                           u.firstname as requester_firstname,
                           u.lastname as requester_lastname,
                           u.profile_picture as requester_avatar
                    FROM donor_requests dr
                    LEFT JOIN users u ON dr.requester_id = u.cin
                    WHERE dr.donation_id = :donation_id
                    ORDER BY dr.request_date DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':donation_id' => (int)$donationId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getDonationRequests: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all donation requests (for admin)
     */
    public function getAllDonationRequests() {
        try {
            $this->ensureRequestsTable();
            
            $sql = "SELECT dr.*, 
                           d.title as donation_title,
                           d.category as donation_category,
                           d.user_id as donor_id,
                           d.donor_name,
                           d.donor_email,
                           d.donor_phone,
                           u.firstname as requester_firstname,
                           u.lastname as requester_lastname,
                           u.email as requester_email,
                           donor.firstname as donor_firstname,
                           donor.lastname as donor_lastname
                    FROM donor_requests dr
                    LEFT JOIN donor_offers d ON dr.donation_id = d.id
                    LEFT JOIN users u ON dr.requester_id = u.cin
                    LEFT JOIN users donor ON d.user_id = donor.cin
                    ORDER BY dr.request_date DESC, dr.status ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getAllDonationRequests: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get requests by user
     */
    public function getUserRequests($userId) {
        try {
            $this->ensureRequestsTable();
            
            $sql = "SELECT dr.*, 
                           d.title as donation_title,
                           d.category as donation_category,
                           d.user_id as donor_id,
                           u.firstname as donor_firstname,
                           u.lastname as donor_lastname
                    FROM donor_requests dr
                    LEFT JOIN donor_offers d ON dr.donation_id = d.id
                    LEFT JOIN users u ON d.user_id = u.cin
                    WHERE dr.requester_id = :user_id
                    ORDER BY dr.request_date DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => (int)$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getUserRequests: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update request status
     */
    public function updateRequestStatus($requestId, $status, $donationId = null) {
        try {
            $this->ensureRequestsTable();
            
            $sql = "UPDATE donor_requests SET status = :status WHERE id = :id";
            $params = [':status' => $status, ':id' => (int)$requestId];
            
            if ($donationId) {
                $sql .= " AND donation_id = :donation_id";
                $params[':donation_id'] = (int)$donationId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            // If approved, update donation quantity
            if ($status === 'approved' && $donationId) {
                $this->pdo->exec("UPDATE donor_offers SET quantity = quantity - 1 WHERE id = " . (int)$donationId);
                // Check if quantity is 0, mark as fulfilled
                $checkStmt = $this->pdo->query("SELECT quantity FROM donor_offers WHERE id = " . (int)$donationId);
                $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
                if ($result && (int)$result['quantity'] <= 0) {
                    $this->updateDonationStatus($donationId, 'fulfilled');
                }
            }
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error updateRequestStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user has already requested this donation
     */
    public function hasUserRequested($donationId, $userId) {
        try {
            $this->ensureRequestsTable();
            
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM donor_requests WHERE donation_id = :donation_id AND requester_id = :user_id");
            $stmt->execute([
                ':donation_id' => (int)$donationId,
                ':user_id' => (int)$userId
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && (int)$result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error hasUserRequested: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get donation request by ID
     */
    public function getDonationRequestById($requestId) {
        try {
            $this->ensureRequestsTable();
            
            $sql = "SELECT dr.*, 
                           u.email as requester_email,
                           u.firstname as requester_firstname,
                           u.lastname as requester_lastname
                    FROM donor_requests dr
                    LEFT JOIN users u ON dr.requester_id = u.cin
                    WHERE dr.id = :id
                    LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => (int)$requestId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error getDonationRequestById: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Ensure donor_requests table exists
     */
    private function ensureRequestsTable() {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'donor_requests'");
            if ($stmt->rowCount() === 0) {
                $createTableSql = "CREATE TABLE `donor_requests` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `donation_id` INT(11) NOT NULL,
                    `requester_id` INT(11) DEFAULT NULL,
                    `requester_name` VARCHAR(255) NOT NULL,
                    `email` VARCHAR(255) DEFAULT NULL,
                    `phone` INT(8) DEFAULT NULL,
                    `message` TEXT DEFAULT NULL,
                    `status` ENUM('pending','approved','denied') DEFAULT 'pending',
                    `request_date` DATE DEFAULT CURDATE(),
                    PRIMARY KEY (`id`),
                    KEY `idx_donation_id` (`donation_id`),
                    KEY `idx_requester_id` (`requester_id`),
                    KEY `idx_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                $this->pdo->exec($createTableSql);
                error_log("Created donor_requests table");
            } else {
                // Check if requester_id column exists
                $stmt = $this->pdo->query("SHOW COLUMNS FROM donor_requests LIKE 'requester_id'");
                if ($stmt->rowCount() === 0) {
                    $this->pdo->exec("ALTER TABLE `donor_requests` ADD COLUMN `requester_id` INT(11) DEFAULT NULL AFTER `donation_id`");
                    error_log("Added requester_id column to donor_requests");
                }
                // Check if message column exists
                $stmt = $this->pdo->query("SHOW COLUMNS FROM donor_requests LIKE 'message'");
                if ($stmt->rowCount() === 0) {
                    $this->pdo->exec("ALTER TABLE `donor_requests` ADD COLUMN `message` TEXT DEFAULT NULL AFTER `phone`");
                    error_log("Added message column to donor_requests");
                }
            }
        } catch (PDOException $e) {
            error_log("Error ensuring requests table: " . $e->getMessage());
        }
    }
}


