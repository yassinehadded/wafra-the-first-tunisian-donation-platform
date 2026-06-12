<?php
/**
 * Post Save Service
 * Handles post saving/bookmarking operations
 */
class PostSaveService {
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
            $checkTable = $this->pdo->query("SHOW TABLES LIKE 'post_save'");
            if ($checkTable->rowCount() > 0) {
                return true;
            }
            
            $sql = "CREATE TABLE post_save (
                id_save INT AUTO_INCREMENT PRIMARY KEY,
                id_post INT NOT NULL,
                id_user INT NOT NULL,
                date_save TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_save (id_post, id_user),
                INDEX idx_post (id_post),
                INDEX idx_user (id_user)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->pdo->exec($sql);
            return true;
        } catch (Exception $e) {
            error_log("Error creating post_save table: " . $e->getMessage());
            try {
                $sql = "CREATE TABLE IF NOT EXISTS post_save (
                    id_save INT AUTO_INCREMENT PRIMARY KEY,
                    id_post INT NOT NULL,
                    id_user INT NOT NULL,
                    date_save TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_save (id_post, id_user)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $this->pdo->exec($sql);
                return true;
            } catch (Exception $e2) {
                error_log("Error creating post_save table: " . $e2->getMessage());
                return false;
            }
        }
    }
    
    /**
     * Toggle save (add or remove)
     */
    public function toggleSave($id_post, $id_user) {
        try {
            if ($this->isSaved($id_post, $id_user)) {
                return $this->removeSave($id_post, $id_user) ? 'removed' : false;
            } else {
                return $this->addSave($id_post, $id_user) ? 'added' : false;
            }
        } catch (Exception $e) {
            error_log("Error toggleSave: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add a save
     */
    public function addSave($id_post, $id_user) {
        try {
            $sql = "INSERT INTO post_save (id_post, id_user) VALUES (:id_post, :id_user)
                    ON DUPLICATE KEY UPDATE date_save = CURRENT_TIMESTAMP";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':id_post' => (int)$id_post,
                ':id_user' => (int)$id_user
            ]);
        } catch (Exception $e) {
            error_log("Error addSave: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove a save
     */
    public function removeSave($id_post, $id_user) {
        try {
            $sql = "DELETE FROM post_save WHERE id_post = :id_post AND id_user = :id_user";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':id_post' => (int)$id_post,
                ':id_user' => (int)$id_user
            ]);
        } catch (Exception $e) {
            error_log("Error removeSave: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if post is saved
     */
    public function isSaved($id_post, $id_user) {
        try {
            $sql = "SELECT COUNT(*) as count FROM post_save WHERE id_post = :id_post AND id_user = :id_user";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id_post' => (int)$id_post,
                ':id_user' => (int)$id_user
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Error isSaved: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get saved posts for a user with like and comment counts
     */
    public function getSavedPosts($id_user) {
        try {
            $sql = "SELECT p.*, ps.date_save,
                    (SELECT COUNT(*) FROM post_like WHERE id_post = p.id_post) as likes_count,
                    (SELECT COUNT(*) FROM post_comment WHERE id_post = p.id_post) as comments_count
                    FROM post_save ps
                    INNER JOIN post p ON ps.id_post = p.id_post
                    WHERE ps.id_user = :id_user
                    ORDER BY ps.date_save DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_user' => (int)$id_user]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getSavedPosts: " . $e->getMessage());
            return [];
        }
    }
}

