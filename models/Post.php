<?php
/**
 * Post Model
 * Handles database operations for posts
 */
class Post {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all posts with user information
     */
    public function getAll() {
        try {
            $sql = "SELECT p.*, 
                    u.firstname, u.lastname, u.email, u.profile_picture,
                    (SELECT COUNT(*) FROM post_like WHERE id_post = p.id_post) as likes_count,
                    (SELECT COUNT(*) FROM post_comment WHERE id_post = p.id_post) as comments_count
                    FROM post p
                    LEFT JOIN users u ON p.id_user = u.cin
                    ORDER BY p.date_creation DESC, p.id_post DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all posts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get post by ID
     */
    public function find($id) {
        try {
            $sql = "SELECT p.*, 
                    u.firstname, u.lastname, u.email, u.profile_picture,
                    (SELECT COUNT(*) FROM post_like WHERE id_post = p.id_post) as likes_count,
                    (SELECT COUNT(*) FROM post_comment WHERE id_post = p.id_post) as comments_count
                    FROM post p
                    LEFT JOIN users u ON p.id_user = u.cin
                    WHERE p.id_post = :id
                    LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => (int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding post: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get posts by user ID
     */
    public function getByUserId($userId) {
        try {
            $sql = "SELECT p.*, 
                    u.firstname, u.lastname, u.email, u.profile_picture,
                    (SELECT COUNT(*) FROM post_like WHERE id_post = p.id_post) as likes_count,
                    (SELECT COUNT(*) FROM post_comment WHERE id_post = p.id_post) as comments_count
                    FROM post p
                    LEFT JOIN users u ON p.id_user = u.cin
                    WHERE p.id_user = :user_id
                    ORDER BY p.date_creation DESC, p.id_post DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => (int)$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting posts by user ID: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get posts liked by user
     */
    public function getLikedByUser($userId) {
        try {
            $sql = "SELECT p.*, 
                    u.firstname, u.lastname, u.email, u.profile_picture,
                    (SELECT COUNT(*) FROM post_like WHERE id_post = p.id_post) as likes_count,
                    (SELECT COUNT(*) FROM post_comment WHERE id_post = p.id_post) as comments_count
                    FROM post p
                    INNER JOIN post_like pl ON p.id_post = pl.id_post
                    LEFT JOIN users u ON p.id_user = u.cin
                    WHERE pl.id_user = :user_id
                    ORDER BY pl.date_like DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => (int)$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting liked posts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new post
     */
    public function create($postData) {
        try {
            $sql = "INSERT INTO post (id_user, nom, Numéro, email, titre, region, description, date_creation, media) 
                    VALUES (:id_user, :nom, :numero, :email, :titre, :region, :description, :date_creation, :media)";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':id_user' => (int)$postData['id_user'],
                ':nom' => $postData['nom'] ?? '',
                ':numero' => $postData['numero'] ?? '',
                ':email' => $postData['email'] ?? '',
                ':titre' => $postData['titre'] ?? '',
                ':region' => $postData['region'] ?? '',
                ':description' => $postData['description'] ?? '',
                ':date_creation' => $postData['date_creation'] ?? date('Y-m-d'),
                ':media' => $postData['media'] ?? null
            ]);
            
            if ($result) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error creating post: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a post
     */
    public function update($id, $postData, $userId) {
        try {
            $sql = "UPDATE post SET 
                    nom = :nom, 
                    Numéro = :numero, 
                    email = :email, 
                    titre = :titre, 
                    region = :region, 
                    description = :description, 
                    date_creation = :date_creation";
            
            if (isset($postData['media'])) {
                $sql .= ", media = :media";
            }
            
            $sql .= " WHERE id_post = :id AND id_user = :user_id";
            
            $params = [
                ':id' => (int)$id,
                ':user_id' => (int)$userId,
                ':nom' => $postData['nom'] ?? '',
                ':numero' => $postData['numero'] ?? '',
                ':email' => $postData['email'] ?? '',
                ':titre' => $postData['titre'] ?? '',
                ':region' => $postData['region'] ?? '',
                ':description' => $postData['description'] ?? '',
                ':date_creation' => $postData['date_creation'] ?? date('Y-m-d')
            ];
            
            if (isset($postData['media'])) {
                $params[':media'] = $postData['media'];
            }
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating post: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a post
     */
    public function delete($id, $userId) {
        try {
            // First delete related data (likes, comments, saves, reports)
            // These should be handled by foreign key constraints or cascade deletes
            // But we'll delete them explicitly to be safe
            
            // Delete likes
            $stmt = $this->pdo->prepare("DELETE FROM post_like WHERE id_post = :id");
            $stmt->execute([':id' => (int)$id]);
            
            // Delete comments
            $stmt = $this->pdo->prepare("DELETE FROM post_comment WHERE id_post = :id");
            $stmt->execute([':id' => (int)$id]);
            
            // Delete saves
            $stmt = $this->pdo->prepare("DELETE FROM post_save WHERE id_post = :id");
            $stmt->execute([':id' => (int)$id]);
            
            // Delete reports (already handled by PostReport model, but just in case)
            $stmt = $this->pdo->prepare("DELETE FROM post_report WHERE id_post = :id");
            $stmt->execute([':id' => (int)$id]);
            
            // Finally delete the post
            $sql = "DELETE FROM post WHERE id_post = :id AND id_user = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':id' => (int)$id,
                ':user_id' => (int)$userId
            ]);
            
            return $result && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error deleting post: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Search posts
     */
    public function search($query, $filters = []) {
        try {
            $sql = "SELECT p.*, 
                    u.firstname, u.lastname, u.email, u.profile_picture,
                    (SELECT COUNT(*) FROM post_like WHERE id_post = p.id_post) as likes_count,
                    (SELECT COUNT(*) FROM post_comment WHERE id_post = p.id_post) as comments_count
                    FROM post p
                    LEFT JOIN users u ON p.id_user = u.cin
                    WHERE 1=1";
            $params = [];
            
            if (!empty($query)) {
                $sql .= " AND (p.titre LIKE :query OR p.description LIKE :query OR p.region LIKE :query)";
                $params[':query'] = '%' . $query . '%';
            }
            
            if (!empty($filters['region'])) {
                $sql .= " AND p.region = :region";
                $params[':region'] = $filters['region'];
            }
            
            if (!empty($filters['user_id'])) {
                $sql .= " AND p.id_user = :user_id";
                $params[':user_id'] = (int)$filters['user_id'];
            }
            
            $sql .= " ORDER BY p.date_creation DESC, p.id_post DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching posts: " . $e->getMessage());
            return [];
        }
    }
}
?>



