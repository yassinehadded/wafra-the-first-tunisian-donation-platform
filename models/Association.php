<?php
/**
 * Association Model
 * Handles all association-related database operations
 */
class Association {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->ensureTableStructure();
    }

    /**
     * Ensure table structure exists
     */
    private function ensureTableStructure() {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'association'");
            if ($stmt->rowCount() === 0) {
                $sql = "CREATE TABLE IF NOT EXISTS association (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    address VARCHAR(255),
                    phone VARCHAR(20),
                    email VARCHAR(255),
                    category VARCHAR(100),
                    status VARCHAR(50) DEFAULT 'Active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                $this->pdo->exec($sql);
            }
        } catch (PDOException $e) {
            error_log("Error ensuring association table: " . $e->getMessage());
        }
    }

    /**
     * Get all associations
     */
    public function getAllAssociations($status = null) {
        try {
            if ($status) {
                $sql = "SELECT * FROM association WHERE status = :status ORDER BY name ASC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':status' => $status]);
            } else {
                $sql = "SELECT * FROM association ORDER BY name ASC";
                $stmt = $this->pdo->query($sql);
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getAllAssociations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get association by ID
     */
    public function getAssociationById($id) {
        try {
            $sql = "SELECT * FROM association WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => (int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getAssociationById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create association
     */
    public function createAssociation($data) {
        try {
            $sql = "INSERT INTO association (name, description, address, phone, email, category, status) 
                    VALUES (:name, :description, :address, :phone, :email, :category, :status)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name' => trim($data['name']),
                ':description' => !empty($data['description']) ? trim($data['description']) : null,
                ':address' => !empty($data['address']) ? trim($data['address']) : null,
                ':phone' => !empty($data['phone']) ? trim($data['phone']) : null,
                ':email' => trim($data['email']),
                ':category' => !empty($data['category']) ? trim($data['category']) : null,
                ':status' => $data['status'] ?? 'Active'
            ]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error createAssociation: " . $e->getMessage());
            throw new Exception("Erreur lors de la création de l'association: " . $e->getMessage());
        }
    }

    /**
     * Update association
     */
    public function updateAssociation($id, $data) {
        try {
            $sql = "UPDATE association SET 
                    name = :name,
                    description = :description,
                    address = :address,
                    phone = :phone,
                    email = :email,
                    category = :category,
                    status = :status
                    WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':name' => trim($data['name']),
                ':description' => !empty($data['description']) ? trim($data['description']) : null,
                ':address' => !empty($data['address']) ? trim($data['address']) : null,
                ':phone' => !empty($data['phone']) ? trim($data['phone']) : null,
                ':email' => trim($data['email']),
                ':category' => !empty($data['category']) ? trim($data['category']) : null,
                ':status' => $data['status'] ?? 'Active',
                ':id' => (int)$id
            ]);
        } catch (PDOException $e) {
            error_log("Error updateAssociation: " . $e->getMessage());
            throw new Exception("Erreur lors de la mise à jour de l'association: " . $e->getMessage());
        }
    }

    /**
     * Delete association
     */
    public function deleteAssociation($id) {
        try {
            $sql = "DELETE FROM association WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => (int)$id]);
        } catch (PDOException $e) {
            error_log("Error deleteAssociation: " . $e->getMessage());
            throw new Exception("Erreur lors de la suppression de l'association: " . $e->getMessage());
        }
    }

    /**
     * Join association (add user as member)
     */
    public function joinAssociation($userId, $associationId) {
        try {
            $this->ensureMembersTable();
            
            // Check if already a member
            $checkSql = "SELECT id FROM association_members WHERE user_id = :user_id AND association_id = :association_id";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([
                ':user_id' => (int)$userId,
                ':association_id' => (int)$associationId
            ]);
            
            if ($checkStmt->fetch()) {
                return false; // Already a member
            }
            
            $sql = "INSERT INTO association_members (user_id, association_id, joined_at, status) 
                    VALUES (:user_id, :association_id, NOW(), 'active')";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':user_id' => (int)$userId,
                ':association_id' => (int)$associationId
            ]);
        } catch (PDOException $e) {
            error_log("Error joinAssociation: " . $e->getMessage());
            throw new Exception("Erreur lors de l'adhésion: " . $e->getMessage());
        }
    }

    /**
     * Get members of an association
     */
    public function getMembers($associationId) {
        try {
            $this->ensureMembersTable();
            
            $sql = "SELECT am.*, u.firstname, u.lastname, u.email, u.cin 
                    FROM association_members am
                    LEFT JOIN users u ON am.user_id = u.cin
                    WHERE am.association_id = :association_id
                    ORDER BY am.joined_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':association_id' => (int)$associationId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getMembers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if user is member of association
     */
    public function isMember($userId, $associationId) {
        try {
            $this->ensureMembersTable();
            
            $sql = "SELECT id FROM association_members 
                    WHERE user_id = :user_id AND association_id = :association_id AND status = 'active'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => (int)$userId,
                ':association_id' => (int)$associationId
            ]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Error isMember: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's associations
     */
    public function getUserAssociations($userId) {
        try {
            $this->ensureMembersTable();
            
            $sql = "SELECT a.*, am.joined_at, am.status as membership_status
                    FROM association a
                    INNER JOIN association_members am ON a.id = am.association_id
                    WHERE am.user_id = :user_id AND am.status = 'active'
                    ORDER BY am.joined_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => (int)$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getUserAssociations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ensure members table exists
     */
    private function ensureMembersTable() {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'association_members'");
            if ($stmt->rowCount() === 0) {
                $sql = "CREATE TABLE IF NOT EXISTS association_members (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    association_id INT NOT NULL,
                    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    UNIQUE KEY unique_membership (user_id, association_id),
                    FOREIGN KEY (association_id) REFERENCES association(id) ON DELETE CASCADE,
                    INDEX idx_user (user_id),
                    INDEX idx_association (association_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                $this->pdo->exec($sql);
            }
        } catch (PDOException $e) {
            error_log("Error ensuring association_members table: " . $e->getMessage());
        }
    }
}
?>



