<?php
/**
 * Post Comment Service
 * Handles comment-related operations
 */
class PostCommentService {
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
            $checkTable = $this->pdo->query("SHOW TABLES LIKE 'post_comment'");
            if ($checkTable->rowCount() > 0) {
                return true; // Table already exists
            }
            
            $sql = "CREATE TABLE post_comment (
                id_comment INT AUTO_INCREMENT PRIMARY KEY,
                id_post INT NOT NULL,
                id_user INT NOT NULL,
                comment_text TEXT NOT NULL,
                date_comment TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_post (id_post),
                INDEX idx_user (id_user)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->pdo->exec($sql);
            return true;
        } catch (Exception $e) {
            error_log("Error creating post_comment table: " . $e->getMessage());
            // Try to create without constraints if error
            try {
                $sql = "CREATE TABLE IF NOT EXISTS post_comment (
                    id_comment INT AUTO_INCREMENT PRIMARY KEY,
                    id_post INT NOT NULL,
                    id_user INT NOT NULL,
                    comment_text TEXT NOT NULL,
                    date_comment TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $this->pdo->exec($sql);
                return true;
            } catch (Exception $e2) {
                error_log("Error creating post_comment table (without FK): " . $e2->getMessage());
                return false;
            }
        }
    }
    
    /**
     * Add a comment
     */
    public function addComment($id_post, $id_user, $comment_text) {
        try {
            $sql = "INSERT INTO post_comment (id_post, id_user, comment_text) VALUES (:id_post, :id_user, :comment_text)";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':id_post' => (int)$id_post,
                ':id_user' => (int)$id_user,
                ':comment_text' => trim($comment_text)
            ]);
            if ($result) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (Exception $e) {
            error_log("Error addComment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all comments for a post
     * Note: Adapted to use 'users' table with 'cin' instead of 'user' table with 'id_user'
     */
    public function getCommentsByPost($id_post) {
        try {
            // Check if profile_picture column exists
            $checkColumn = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
            $hasProfilePicture = $checkColumn && $checkColumn->rowCount() > 0;
            
            if ($hasProfilePicture) {
                $sql = "SELECT c.*, u.firstname, u.lastname, u.email, u.cin as id_user, u.profile_picture
                        FROM post_comment c
                        LEFT JOIN users u ON c.id_user = u.cin
                        WHERE c.id_post = :id_post
                        ORDER BY c.date_comment ASC";
            } else {
                $sql = "SELECT c.*, u.firstname, u.lastname, u.email, u.cin as id_user, NULL as profile_picture
                        FROM post_comment c
                        LEFT JOIN users u ON c.id_user = u.cin
                        WHERE c.id_post = :id_post
                        ORDER BY c.date_comment ASC";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_post' => (int)$id_post]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getCommentsByPost: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get a comment by ID
     */
    public function getCommentById($id_comment) {
        try {
            $sql = "SELECT c.*, u.firstname, u.lastname, u.email, u.cin as id_user, u.profile_picture
                    FROM post_comment c
                    LEFT JOIN users u ON c.id_user = u.cin
                    WHERE c.id_comment = :id_comment
                    LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_comment' => (int)$id_comment]);
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);
            return $comment ?: null;
        } catch (Exception $e) {
            error_log("Error getCommentById: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get comment count for a post
     */
    public function getCommentCount($id_post) {
        try {
            $sql = "SELECT COUNT(*) as count FROM post_comment WHERE id_post = :id_post";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_post' => (int)$id_post]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Error getCommentCount: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Delete a comment
     */
    public function deleteComment($id_comment, $id_user) {
        try {
            $sql = "DELETE FROM post_comment WHERE id_comment = :id_comment AND id_user = :id_user";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':id_comment' => (int)$id_comment,
                ':id_user' => (int)$id_user
            ]);
        } catch (Exception $e) {
            error_log("Error deleteComment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete comment by ID (admin function)
     */
    public function deleteCommentById($id_comment) {
        try {
            $sql = "DELETE FROM post_comment WHERE id_comment = :id_comment";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id_comment' => (int)$id_comment]);
        } catch (Exception $e) {
            error_log("Error deleteCommentById: " . $e->getMessage());
            return false;
        }
    }
}


