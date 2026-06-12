<?php
/**
 * Reclamation Model
 * Handles all reclamation-related database operations
 */
class Reclamation {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new reclamation
     */
    public function createReclamation($userId, $nom, $email, $telephone, $type, $priorite, $description) {
        try {
            $sql = "INSERT INTO reclamation (user_id, nom, email, telephone, type, priorite, description, statut, date_creation) 
                    VALUES (:user_id, :nom, :email, :telephone, :type, :priorite, :description, 'En attente', NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId ? (int)$userId : null,
                ':nom' => trim($nom),
                ':email' => trim($email),
                ':telephone' => trim($telephone),
                ':type' => $type,
                ':priorite' => $priorite,
                ':description' => trim($description)
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error createReclamation: " . $e->getMessage());
            throw new Exception("Erreur lors de la création de la réclamation");
        }
    }

    /**
     * Get all reclamations for a specific user
     */
    public function getUserReclamations($userId) {
        try {
            $sql = "SELECT r.*, 
                           (SELECT COUNT(*) FROM reponses WHERE reclamation_id = r.id) as nb_reponses,
                           (SELECT message FROM reponses WHERE reclamation_id = r.id ORDER BY date_reponse DESC LIMIT 1) as derniere_reponse,
                           (SELECT date_reponse FROM reponses WHERE reclamation_id = r.id ORDER BY date_reponse DESC LIMIT 1) as date_derniere_reponse
                    FROM reclamation r
                    WHERE r.user_id = :user_id
                    ORDER BY r.date_creation DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => (int)$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getUserReclamations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single reclamation by ID (with user check)
     */
    public function getReclamationById($id, $userId = null) {
        try {
            $sql = "SELECT r.*, 
                           (SELECT COUNT(*) FROM reponses WHERE reclamation_id = r.id) as nb_reponses,
                           (SELECT message FROM reponses WHERE reclamation_id = r.id ORDER BY date_reponse DESC LIMIT 1) as derniere_reponse,
                           (SELECT date_reponse FROM reponses WHERE reclamation_id = r.id ORDER BY date_reponse DESC LIMIT 1) as date_derniere_reponse
                    FROM reclamation r
                    WHERE r.id = :id";
            
            $params = [':id' => (int)$id];
            
            // If userId provided, ensure user owns the reclamation
            if ($userId !== null) {
                $sql .= " AND r.user_id = :user_id";
                $params[':user_id'] = (int)$userId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getReclamationById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all reclamations (admin only)
     */
    public function getAllReclamations($filters = []) {
        try {
            $sql = "SELECT r.*, 
                           u.firstname as user_firstname,
                           u.lastname as user_lastname,
                           u.email as user_email,
                           (SELECT COUNT(*) FROM reponses WHERE reclamation_id = r.id) as nb_reponses
                    FROM reclamation r
                    LEFT JOIN users u ON r.user_id = u.cin
                    WHERE 1=1";
            
            $params = [];
            
            if (!empty($filters['priorite'])) {
                $sql .= " AND r.priorite = :priorite";
                $params[':priorite'] = $filters['priorite'];
            }
            
            if (!empty($filters['statut'])) {
                $sql .= " AND r.statut = :statut";
                $params[':statut'] = $filters['statut'];
            }
            
            if (!empty($filters['type'])) {
                $sql .= " AND r.type = :type";
                $params[':type'] = $filters['type'];
            }
            
            if (!empty($filters['search'])) {
                $searchTerm = '%' . mb_strtolower(trim($filters['search'])) . '%';
                $sql .= " AND (
                    LOWER(r.nom) LIKE :search 
                    OR LOWER(r.email) LIKE :search 
                    OR r.telephone LIKE :search 
                    OR LOWER(r.description) LIKE :search
                    OR LOWER(u.firstname) LIKE :search
                    OR LOWER(u.lastname) LIKE :search
                    OR LOWER(CONCAT(COALESCE(u.firstname, ''), ' ', COALESCE(u.lastname, ''))) LIKE :search
                )";
                $params[':search'] = $searchTerm;
            }
            
            $sql .= " ORDER BY r.date_creation DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getAllReclamations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update reclamation status
     */
    public function updateStatus($id, $status) {
        try {
            $sql = "UPDATE reclamation SET statut = :statut, updated_at = NOW() WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':statut' => $status,
                ':id' => (int)$id
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error updateStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update reclamation (user can update their own)
     */
    public function updateReclamation($id, $userId, $nom, $email, $telephone, $type, $priorite, $description) {
        try {
            $sql = "UPDATE reclamation 
                    SET nom = :nom, email = :email, telephone = :telephone, 
                        type = :type, priorite = :priorite, description = :description,
                        updated_at = NOW()
                    WHERE id = :id AND user_id = :user_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':nom' => trim($nom),
                ':email' => trim($email),
                ':telephone' => trim($telephone),
                ':type' => $type,
                ':priorite' => $priorite,
                ':description' => trim($description),
                ':id' => (int)$id,
                ':user_id' => (int)$userId
            ]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error updateReclamation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete reclamation
     */
    public function deleteReclamation($id, $userId = null) {
        try {
            $sql = "DELETE FROM reclamation WHERE id = :id";
            $params = [':id' => (int)$id];
            
            // If userId provided, ensure user owns the reclamation
            if ($userId !== null) {
                $sql .= " AND user_id = :user_id";
                $params[':user_id'] = (int)$userId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error deleteReclamation: " . $e->getMessage());
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
                'en_attente' => 0,
                'en_cours' => 0,
                'repondues' => 0
            ];
            
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM reclamation");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total'] = $result ? (int)$result['total'] : 0;
            
            $stmt = $this->pdo->query("SELECT statut, COUNT(*) as count FROM reclamation GROUP BY statut");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $statut = $row['statut'];
                $count = (int)$row['count'];
                
                if ($statut === 'En attente') {
                    $stats['en_attente'] = $count;
                } elseif ($statut === 'En cours') {
                    $stats['en_cours'] = $count;
                } elseif ($statut === 'Répondu') {
                    $stats['repondues'] = $count;
                }
            }
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Error getStats: " . $e->getMessage());
            return [
                'total' => 0,
                'en_attente' => 0,
                'en_cours' => 0,
                'repondues' => 0
            ];
        }
    }

    /**
     * Add response to reclamation (admin only)
     */
    public function addResponse($reclamationId, $adminId, $message) {
        try {
            $this->pdo->beginTransaction();
            
            // Add response
            $sql = "INSERT INTO reponses (reclamation_id, admin_id, message, date_reponse) 
                    VALUES (:reclamation_id, :admin_id, :message, NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':reclamation_id' => (int)$reclamationId,
                ':admin_id' => (int)$adminId,
                ':message' => trim($message)
            ]);
            
            // Update reclamation status
            $this->updateStatus($reclamationId, 'Répondu');
            
            $this->pdo->commit();
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error addResponse: " . $e->getMessage());
            throw new Exception("Erreur lors de l'ajout de la réponse");
        }
    }

    /**
     * Get responses for a reclamation
     */
    public function getResponses($reclamationId) {
        try {
            $sql = "SELECT r.*, u.firstname as admin_firstname, u.lastname as admin_lastname
                    FROM reponses r
                    LEFT JOIN users u ON r.admin_id = u.cin
                    WHERE r.reclamation_id = :reclamation_id
                    ORDER BY r.date_reponse DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':reclamation_id' => (int)$reclamationId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getResponses: " . $e->getMessage());
            return [];
        }
    }
}

