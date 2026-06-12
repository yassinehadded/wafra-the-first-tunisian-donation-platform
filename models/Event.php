<?php
/**
 * Event Model
 * Handles all event-related database operations
 */
class Event {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($data): bool {
        try {
            $sql = "INSERT INTO evenements 
                    (nom_evenement, type_evenement, date_debut, date_fin, description, lieu, latitude, longitude, qr_code)
                    VALUES (:nom_evenement, :type_evenement, :date_debut, :date_fin, :description, :lieu, :latitude, :longitude, :qr_code)";

            $stmt = $this->pdo->prepare($sql);

            $params = [
                ':nom_evenement'  => $data['nom_evenement'],
                ':type_evenement' => $data['type_evenement'],
                ':date_debut'     => $data['date_debut'],
                ':date_fin'       => $data['date_fin'],
                ':description'    => $data['description'],
                ':lieu'           => $data['lieu'] ?? null,
                ':latitude'       => $data['latitude'] ?? null,
                ':longitude'      => $data['longitude'] ?? null,
                ':qr_code'        => $data['qr_code'] ?? null
            ];

            $result = $stmt->execute($params);

            if (!$result) {
                $error = $stmt->errorInfo();
                error_log("Error executing event creation query: " . print_r($error, true));
                return false;
            }

            error_log("Event created successfully. ID: " . $this->pdo->lastInsertId());
            return true;
        } catch (Exception $e) {
            error_log("Exception in Event::create: " . $e->getMessage());
            return false;
        }
    }

    public function getAll($page = 1, $perPage = 2, $searchTerm = '') {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM evenements ";
        $params = [];
        
        if (!empty($searchTerm)) {
            $searchPattern = "%$searchTerm%";
            $sql .= " WHERE (nom_evenement LIKE :searchTerm1 ";
            $sql .= " OR description LIKE :searchTerm2 ";
            $sql .= " OR type_evenement LIKE :searchTerm3 ";
            $sql .= " OR lieu LIKE :searchTerm4)";
            $params[':searchTerm1'] = $searchPattern;
            $params[':searchTerm2'] = $searchPattern;
            $params[':searchTerm3'] = $searchPattern;
            $params[':searchTerm4'] = $searchPattern;
        }
        
        $sql .= " ORDER BY date_debut DESC ";
        $sql .= " LIMIT :offset, :perPage";
        
        $params[':offset'] = (int)$offset;
        $params[':perPage'] = (int)$perPage;
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        
        $stmt->execute();
        
        $total = $this->pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $results,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $total > 0 ? ceil($total / $perPage) : 1,
            'searchTerm' => $searchTerm
        ];
    }

    /**
     * Get events that the logged-in user has reservations for
     * @param int $userId User ID
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param string $searchTerm Search term
     * @return array
     */
    public function getEventsByUserReservations($userId, $page = 1, $perPage = 10, $searchTerm = '') {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT e.* 
                FROM evenements e
                INNER JOIN reservations r ON r.evenement_id = e.id
                WHERE r.cin = :cin";
        $params = [':cin' => (int)$userId];
        
        if (!empty($searchTerm)) {
            $searchPattern = "%$searchTerm%";
            $sql .= " AND (e.nom_evenement LIKE :searchTerm1 ";
            $sql .= " OR e.description LIKE :searchTerm2 ";
            $sql .= " OR e.type_evenement LIKE :searchTerm3 ";
            $sql .= " OR e.lieu LIKE :searchTerm4)";
            $params[':searchTerm1'] = $searchPattern;
            $params[':searchTerm2'] = $searchPattern;
            $params[':searchTerm3'] = $searchPattern;
            $params[':searchTerm4'] = $searchPattern;
        }
        
        $sql .= " ORDER BY e.date_debut DESC ";
        $sql .= " LIMIT :offset, :perPage";
        
        $params[':offset'] = (int)$offset;
        $params[':perPage'] = (int)$perPage;
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        
        $stmt->execute();
        
        $total = $this->pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $results,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $total > 0 ? ceil($total / $perPage) : 1,
            'searchTerm' => $searchTerm
        ];
    }

    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM evenements WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($id): bool {
        try {
            $checkStmt = $this->pdo->prepare("SELECT id FROM evenements WHERE id = :id");
            $checkStmt->execute([':id' => $id]);
            
            if ($checkStmt->rowCount() === 0) {
                error_log("Attempt to delete non-existent event (ID: $id)");
                return false;
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM evenements WHERE id = :id");
            $result = $stmt->execute([':id' => $id]);
            
            if (!$result) {
                $error = $stmt->errorInfo();
                error_log("Error deleting event (ID: $id): " . print_r($error, true));
                return false;
            }
            
            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            error_log("Exception deleting event (ID: $id): " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data): bool {
        $sql = "UPDATE evenements SET 
                nom_evenement = :nom_evenement,
                type_evenement = :type_evenement,
                date_debut = :date_debut,
                date_fin = :date_fin,
                description = :description,
                lieu = :lieu,
                latitude = :latitude,
                longitude = :longitude
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':nom_evenement'  => $data['nom_evenement'],
            ':type_evenement' => $data['type_evenement'],
            ':date_debut'     => $data['date_debut'],
            ':date_fin'       => $data['date_fin'],
            ':description'    => $data['description'],
            ':lieu'           => $data['lieu'] ?? null,
            ':latitude'       => $data['latitude'] ?? null,
            ':longitude'      => $data['longitude'] ?? null,
            ':id'             => $id
        ]);
    }

    public function getUpcomingEvents() {
        try {
            $now = date('Y-m-d H:i:s');
            
            $sql = "SELECT * FROM evenements 
                    WHERE date_fin >= :now
                    ORDER BY date_debut ASC";
                    
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':now' => $now]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in Event::getUpcomingEvents: " . $e->getMessage());
            return [];
        }
    }
}

