<?php
/**
 * Reservation Model
 * Handles all reservation-related database operations
 */
class Reservation {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function validateData($data) {
        $errors = [];
        
        if (empty($data['nom']) || strlen(trim($data['nom'])) < 3) {
            $errors[] = "Le nom doit contenir au moins 3 caractères.";
        }
        
        if (empty($data['tel']) || !preg_match('/^\+[1-9]\d{0,14}$/', $data['tel']) || strlen($data['tel']) > 15 || strlen($data['tel']) < 7) {
            $errors[] = "Numéro de téléphone invalide. Format attendu : +21612345678";
        }
        
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format d'email invalide.";
        }
        
        if (empty($data['lieu']) || strlen(trim($data['lieu'])) < 2) {
            $errors[] = "Le lieu doit contenir au moins 2 caractères.";
        }
        
        if (empty($data['date_naissance'])) {
            $errors[] = "La date de naissance est requise.";
        } else {
            $today = new DateTime();
            $birthdate = new DateTime($data['date_naissance']);
            $age = $today->diff($birthdate)->y;
            
            if ($age < 18) {
                $errors[] = "Vous devez avoir au moins 18 ans pour vous inscrire.";
            }
        }
        
        if (empty($data['softskills'])) {
            $errors[] = "Veuillez sélectionner une compétence.";
        }
        
        return $errors;
    }

    public function selectAllWithEvent($userId = null, $searchTerm = '') {
        $sql = "SELECT r.*, 
                       e.nom_evenement AS evenement_nom, 
                       e.type_evenement AS evenement_type
                FROM reservations r
                LEFT JOIN evenements e ON e.id = r.evenement_id
                WHERE 1=1";
        
        $params = [];
        
        // Filter by user CIN if provided
        if ($userId !== null && $userId !== '') {
            $sql .= " AND r.cin = :cin";
            $params[':cin'] = (int)$userId;
            error_log("Reservation query: Filtering by CIN=" . (int)$userId);
        } else {
            error_log("Reservation query: No user filter (userId=" . var_export($userId, true) . ")");
        }
        
        if (!empty($searchTerm)) {
            $searchPattern = "%$searchTerm%";
            $sql .= " AND (r.nom LIKE :searchTerm1 ";
            $sql .= " OR r.tel LIKE :searchTerm2 ";
            $sql .= " OR r.lieu LIKE :searchTerm3 ";
            $sql .= " OR r.softskills LIKE :searchTerm4 ";
            $sql .= " OR e.nom_evenement LIKE :searchTerm5 ";
            $sql .= " OR e.type_evenement LIKE :searchTerm6)";
            $params[':searchTerm1'] = $searchPattern;
            $params[':searchTerm2'] = $searchPattern;
            $params[':searchTerm3'] = $searchPattern;
            $params[':searchTerm4'] = $searchPattern;
            $params[':searchTerm5'] = $searchPattern;
            $params[':searchTerm6'] = $searchPattern;
        }
        
        $sql .= " ORDER BY r.id DESC";
        
        error_log("Reservation query SQL: " . $sql);
        error_log("Reservation query params: " . json_encode($params));
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            error_log("Reservation query error: " . json_encode($errorInfo));
            return [];
        }
        
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Reservation query returned " . count($reservations) . " reservations");
        
        // Debug: Log first reservation if any
        if (count($reservations) > 0) {
            error_log("First reservation sample: " . json_encode($reservations[0]));
        } else {
            // Try to find reservations without the join to see if they exist
            if ($userId !== null && $userId !== '') {
                $debugSql = "SELECT * FROM reservations WHERE cin = :cin";
                $debugStmt = $this->pdo->prepare($debugSql);
                $debugStmt->execute([':cin' => (int)$userId]);
                $debugReservations = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Debug: Found " . count($debugReservations) . " reservations for CIN=" . (int)$userId . " without join");
                if (count($debugReservations) > 0) {
                    error_log("Debug: Sample reservation: " . json_encode($debugReservations[0]));
                }
            } else {
                // Check if there are any reservations at all
                $debugSql = "SELECT COUNT(*) as total FROM reservations";
                $debugStmt = $this->pdo->query($debugSql);
                $totalCount = $debugStmt->fetch(PDO::FETCH_ASSOC)['total'];
                error_log("Debug: Total reservations in database: " . $totalCount);
                if ($totalCount > 0) {
                    // Get a sample reservation
                    $sampleSql = "SELECT * FROM reservations LIMIT 1";
                    $sampleStmt = $this->pdo->query($sampleSql);
                    $sample = $sampleStmt->fetch(PDO::FETCH_ASSOC);
                    error_log("Debug: Sample reservation from DB: " . json_encode($sample));
                }
            }
        }
        
        return $reservations;
    }

    public function insert($data) {
        // Normalize phone number
        if (!empty($data['tel'])) {
            $tel = trim($data['tel']);
            $tel = preg_replace('/[^\d+]/', '', $tel);
            
            if (substr($tel, 0, 1) !== '+') {
                if (substr($tel, 0, 1) === '0') {
                    $tel = '+216' . substr($tel, 1);
                } else {
                    $tel = '+216' . $tel;
                }
            }
            $data['tel'] = $tel;
        }
        
        $errors = $this->validateData($data);
        if (!empty($errors)) {
            throw new Exception(implode(' ', $errors));
        }

        try {
            $this->pdo->beginTransaction();

            $eventStmt = $this->pdo->prepare("SELECT * FROM evenements WHERE id = ?");
            $eventStmt->execute([$data['evenement_id']]);
            $event = $eventStmt->fetch(PDO::FETCH_ASSOC);

            if (!$event) {
                throw new Exception("L'événement spécifié n'existe pas.");
            }

            // Include cin if provided
            $sql = "INSERT INTO reservations (
                        evenement_id, 
                        cin,
                        nom, 
                        tel, 
                        lieu, 
                        date_naissance, 
                        softskills, 
                        email,
                        created_at
                    ) VALUES (
                        :evenement_id, 
                        :cin,
                        :nom, 
                        :tel, 
                        :lieu, 
                        :date_naissance, 
                        :softskills, 
                        :email,
                        NOW()
                    )";
            
            // Ensure CIN is set and not null
            $cin = null;
            if (isset($data["cin"]) && !empty($data["cin"])) {
                $cin = (int)$data["cin"];
            } elseif (isset($data["user_id"]) && !empty($data["user_id"])) {
                $cin = (int)$data["user_id"];
            }
            
            // Log CIN value for debugging
            error_log("Reservation insert - CIN value: " . var_export($cin, true) . ", data: " . json_encode($data));
            
            if ($cin === null || $cin === 0) {
                error_log("ERROR: CIN is null or 0! Cannot create reservation without CIN.");
                throw new Exception("CIN utilisateur manquant. Impossible de créer la réservation.");
            }
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ":evenement_id"   => $data["evenement_id"],
                ":cin"            => $cin,
                ":nom"            => $data["nom"],
                ":tel"            => $data["tel"],
                ":lieu"           => $data["lieu"],
                ":date_naissance" => $data["date_naissance"],
                ":softskills"     => $data["softskills"],
                ":email"          => $data["email"] ?? null
            ]);

            if (!$result) {
                throw new Exception("Erreur lors de l'insertion de la réservation");
            }

            $reservationId = $this->pdo->lastInsertId();
            $data['id'] = $reservationId;
            $data['evenement_nom'] = $event['nom_evenement'];
            $data['evenement_date'] = $event['date_debut'];
            $data['evenement_lieu'] = $event['lieu'];
            
            // Try to send emails, but don't fail if email sending fails
            if (!empty($data['email'])) {
                try {
                    $emailSent = $this->sendClientConfirmation($data);
                    if (!$emailSent) {
                        error_log("Failed to send client confirmation for reservation #$reservationId (non-fatal)");
                    }
                } catch (Exception $e) {
                    error_log("Error sending client confirmation email (non-fatal): " . $e->getMessage());
                }
            }
            
            try {
                $adminNotified = $this->sendAdminNotification($data);
                if (!$adminNotified) {
                    error_log("Failed to send admin notification for reservation #$reservationId (non-fatal)");
                }
            } catch (Exception $e) {
                error_log("Error sending admin notification email (non-fatal): " . $e->getMessage());
            }
            
            $this->pdo->commit();
            return $reservationId;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error inserting reservation: " . $e->getMessage());
            throw $e;
        }
    }

    private function sendAdminNotification($data) {
        try {
            require_once __DIR__ . '/../services/EmailService.php';
            $emailService = new EmailService();
            
            $subject = 'Nouvelle réservation #' . $data['id'];
            
            $message = "<h2>Nouvelle réservation reçue</h2>";
            $message .= "<p>Une nouvelle réservation a été effectuée sur le site.</p>";
            $message .= "<h3>Détails de la réservation :</h3>";
            $message .= "<ul>";
            $message .= "<li><strong>Référence :</strong> " . $data['id'] . "</li>";
            $message .= "<li><strong>Date :</strong> " . date('d/m/Y H:i') . "</li>";
            $message .= "<li><strong>Nom :</strong> " . htmlspecialchars($data['nom']) . "</li>";
            $message .= "<li><strong>Email :</strong> " . (!empty($data['email']) ? htmlspecialchars($data['email']) : 'Non fourni') . "</li>";
            $message .= "<li><strong>Téléphone :</strong> " . htmlspecialchars($data['tel']) . "</li>";
            $message .= "<li><strong>Lieu :</strong> " . htmlspecialchars($data['lieu']) . "</li>";
            $message .= "<li><strong>Date de naissance :</strong> " . $data['date_naissance'] . "</li>";
            $message .= "<li><strong>Compétences :</strong> " . htmlspecialchars($data['softskills']) . "</li>";
            $message .= "</ul>";
            
            if (!empty($data['evenement_nom'])) {
                $message .= "<h3>Détails de l'événement :</h3>";
                $message .= "<ul>";
                $message .= "<li><strong>Événement :</strong> " . htmlspecialchars($data['evenement_nom']) . "</li>";
                if (!empty($data['evenement_date'])) {
                    $message .= "<li><strong>Date :</strong> " . date('d/m/Y H:i', strtotime($data['evenement_date'])) . "</li>";
                }
                if (!empty($data['evenement_lieu'])) {
                    $message .= "<li><strong>Lieu :</strong> " . htmlspecialchars($data['evenement_lieu']) . "</li>";
                }
                $message .= "</ul>";
            }
            
            $adminEmail = getenv('ADMIN_EMAIL') ?: 'yassineou.haddadou@gmail.com';
            return $emailService->send($adminEmail, $subject, $message, true);
            
        } catch (Exception $e) {
            error_log("Error sending admin notification: " . $e->getMessage());
            return false;
        }
    }

    private function sendClientConfirmation($data) {
        try {
            require_once __DIR__ . '/../services/EmailService.php';
            $emailService = new EmailService();
            
            $subject = 'Confirmation de votre réservation #' . $data['id'];
            
            $message = "<h2>Confirmation de réservation</h2>";
            $message .= "<p>Bonjour " . htmlspecialchars($data['nom']) . ",</p>";
            $message .= "<p>Merci pour votre réservation. Voici les détails :</p>";
            $message .= "<h3>Vos informations :</h3>";
            $message .= "<ul>";
            $message .= "<li><strong>Référence :</strong> " . $data['id'] . "</li>";
            $message .= "<li><strong>Nom :</strong> " . htmlspecialchars($data['nom']) . "</li>";
            $message .= "<li><strong>Téléphone :</strong> " . htmlspecialchars($data['tel']) . "</li>";
            $message .= "<li><strong>Lieu :</strong> " . htmlspecialchars($data['lieu']) . "</li>";
            $message .= "<li><strong>Date de naissance :</strong> " . $data['date_naissance'] . "</li>";
            $message .= "<li><strong>Compétences :</strong> " . htmlspecialchars($data['softskills']) . "</li>";
            $message .= "</ul>";
            
            if (!empty($data['evenement_id'])) {
                $eventStmt = $this->pdo->prepare("SELECT * FROM evenements WHERE id = ?");
                $eventStmt->execute([$data['evenement_id']]);
                $event = $eventStmt->fetch();
                
                if ($event) {
                    $message .= "<h3>Détails de l'événement :</h3>";
                    $message .= "<ul>";
                    $message .= "<li><strong>Événement :</strong> " . htmlspecialchars($event['nom_evenement']) . "</li>";
                    $message .= "<li><strong>Date :</strong> " . date('d/m/Y H:i', strtotime($event['date_debut'])) . "</li>";
                    $message .= "<li><strong>Lieu :</strong> " . htmlspecialchars($event['lieu']) . "</li>";
                    $message .= "</ul>";
                }
            }
            
            $message .= "<p>Nous vous remercions de votre confiance !</p>";
            
            return $emailService->send($data['email'], $subject, $message, true);
            
        } catch (Exception $e) {
            error_log("Error sending client confirmation: " . $e->getMessage());
            return false;
        }
    }

    public function select($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM reservations WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        // Normalize phone number
        if (!empty($data['tel'])) {
            $tel = trim($data['tel']);
            $tel = preg_replace('/[^\d+]/', '', $tel);
            
            if (substr($tel, 0, 1) !== '+') {
                if (substr($tel, 0, 1) === '0') {
                    $tel = '+216' . substr($tel, 1);
                } else {
                    $tel = '+216' . $tel;
                }
            }
            $data['tel'] = $tel;
        }
        
        $errors = $this->validateData($data);
        if (!empty($errors)) {
            throw new Exception(implode(' ', $errors));
        }

        try {
            $this->pdo->beginTransaction();

            $existingReservation = $this->select($id);
            if (!$existingReservation) {
                throw new Exception("La réservation spécifiée n'existe pas.");
            }

            $eventStmt = $this->pdo->prepare("SELECT * FROM evenements WHERE id = ?");
            $eventStmt->execute([$data['evenement_id']]);
            $event = $eventStmt->fetch(PDO::FETCH_ASSOC);

            if (!$event) {
                throw new Exception("L'événement spécifié n'existe pas.");
            }

            $sql = "UPDATE reservations SET 
                        evenement_id = :evenement_id, 
                        nom = :nom, 
                        tel = :tel, 
                        lieu = :lieu, 
                        date_naissance = :date_naissance, 
                        softskills = :softskills, 
                        email = :email
                    WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':id' => $id,
                ':evenement_id' => $data['evenement_id'],
                ':nom' => $data['nom'],
                ':tel' => $data['tel'],
                ':lieu' => $data['lieu'],
                ':date_naissance' => $data['date_naissance'],
                ':softskills' => $data['softskills'],
                ':email' => $data['email'] ?? null
            ]);

            if (!$result) {
                throw new Exception("Erreur lors de la mise à jour de la réservation");
            }

            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error updating reservation: " . $e->getMessage());
            throw $e;
        }
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM reservations WHERE id=?");
        return $stmt->execute([$id]);
    }
}

