<?php
/**
 * Cotisation Model
 * Handles all cotisation (membership fee) related database operations
 */
class Cotisation {
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
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'cotisations'");
            if ($stmt->rowCount() === 0) {
                $sql = "CREATE TABLE IF NOT EXISTS cotisations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    association_id INT NOT NULL,
                    user_id INT NOT NULL,
                    amount DECIMAL(10, 2) NOT NULL,
                    period VARCHAR(50) NOT NULL COMMENT 'monthly, yearly, etc.',
                    payment_date DATE,
                    payment_status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
                    payment_method VARCHAR(50) COMMENT 'cash, bank_transfer, online, etc.',
                    payment_reference VARCHAR(255),
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_association (association_id),
                    INDEX idx_user (user_id),
                    INDEX idx_status (payment_status),
                    FOREIGN KEY (association_id) REFERENCES association(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                $this->pdo->exec($sql);
            }
        } catch (PDOException $e) {
            error_log("Error ensuring cotisations table: " . $e->getMessage());
        }
    }

    /**
     * Create cotisation
     */
    public function createCotisation($data) {
        try {
            $sql = "INSERT INTO cotisations (association_id, user_id, amount, period, payment_date, payment_status, payment_method, payment_reference, notes) 
                    VALUES (:association_id, :user_id, :amount, :period, :payment_date, :payment_status, :payment_method, :payment_reference, :notes)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':association_id' => (int)$data['association_id'],
                ':user_id' => (int)$data['user_id'],
                ':amount' => (float)$data['amount'],
                ':period' => $data['period'] ?? 'monthly',
                ':payment_date' => !empty($data['payment_date']) ? $data['payment_date'] : null,
                ':payment_status' => $data['payment_status'] ?? 'pending',
                ':payment_method' => !empty($data['payment_method']) ? $data['payment_method'] : null,
                ':payment_reference' => !empty($data['payment_reference']) ? $data['payment_reference'] : null,
                ':notes' => !empty($data['notes']) ? $data['notes'] : null
            ]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error createCotisation: " . $e->getMessage());
            throw new Exception("Erreur lors de la création de la cotisation: " . $e->getMessage());
        }
    }

    /**
     * Get user cotisations
     */
    public function getUserCotisations($userId, $associationId = null) {
        try {
            if ($associationId) {
                $sql = "SELECT c.*, a.name as association_name, a.category as association_category
                        FROM cotisations c
                        INNER JOIN association a ON c.association_id = a.id
                        WHERE c.user_id = :user_id AND c.association_id = :association_id
                        ORDER BY c.created_at DESC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':user_id' => (int)$userId,
                    ':association_id' => (int)$associationId
                ]);
            } else {
                $sql = "SELECT c.*, a.name as association_name, a.category as association_category
                        FROM cotisations c
                        INNER JOIN association a ON c.association_id = a.id
                        WHERE c.user_id = :user_id
                        ORDER BY c.created_at DESC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':user_id' => (int)$userId]);
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getUserCotisations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get association cotisations
     */
    public function getAssociationCotisations($associationId, $status = null) {
        try {
            if ($status) {
                $sql = "SELECT c.*, u.firstname, u.lastname, u.email, u.cin
                        FROM cotisations c
                        LEFT JOIN users u ON c.user_id = u.cin
                        WHERE c.association_id = :association_id AND c.payment_status = :status
                        ORDER BY c.created_at DESC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':association_id' => (int)$associationId,
                    ':status' => $status
                ]);
            } else {
                $sql = "SELECT c.*, u.firstname, u.lastname, u.email, u.cin
                        FROM cotisations c
                        LEFT JOIN users u ON c.user_id = u.cin
                        WHERE c.association_id = :association_id
                        ORDER BY c.created_at DESC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':association_id' => (int)$associationId]);
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getAssociationCotisations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get cotisation by ID
     */
    public function getCotisationById($id) {
        try {
            $sql = "SELECT c.*, a.name as association_name, u.firstname, u.lastname, u.email
                    FROM cotisations c
                    INNER JOIN association a ON c.association_id = a.id
                    LEFT JOIN users u ON c.user_id = u.cin
                    WHERE c.id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => (int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getCotisationById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update payment status
     */
    public function validatePayment($id, $paymentData) {
        try {
            $sql = "UPDATE cotisations SET 
                    payment_status = :payment_status,
                    payment_date = :payment_date,
                    payment_method = :payment_method,
                    payment_reference = :payment_reference,
                    notes = :notes
                    WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':payment_status' => $paymentData['payment_status'] ?? 'paid',
                ':payment_date' => $paymentData['payment_date'] ?? date('Y-m-d'),
                ':payment_method' => !empty($paymentData['payment_method']) ? $paymentData['payment_method'] : null,
                ':payment_reference' => !empty($paymentData['payment_reference']) ? $paymentData['payment_reference'] : null,
                ':notes' => !empty($paymentData['notes']) ? $paymentData['notes'] : null,
                ':id' => (int)$id
            ]);
        } catch (PDOException $e) {
            error_log("Error validatePayment: " . $e->getMessage());
            throw new Exception("Erreur lors de la validation du paiement: " . $e->getMessage());
        }
    }

    /**
     * Get all cotisations (admin)
     */
    public function getAllCotisations($filters = []) {
        try {
            $sql = "SELECT c.*, a.name as association_name, u.firstname, u.lastname, u.email
                    FROM cotisations c
                    INNER JOIN association a ON c.association_id = a.id
                    LEFT JOIN users u ON c.user_id = u.cin
                    WHERE 1=1";
            $params = [];
            
            if (!empty($filters['status'])) {
                $sql .= " AND c.payment_status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['association_id'])) {
                $sql .= " AND c.association_id = :association_id";
                $params[':association_id'] = (int)$filters['association_id'];
            }
            
            $sql .= " ORDER BY c.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getAllCotisations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete cotisation
     */
    public function deleteCotisation($id) {
        try {
            $sql = "DELETE FROM cotisations WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => (int)$id]);
        } catch (PDOException $e) {
            error_log("Error deleteCotisation: " . $e->getMessage());
            throw new Exception("Erreur lors de la suppression de la cotisation: " . $e->getMessage());
        }
    }
}
?>



