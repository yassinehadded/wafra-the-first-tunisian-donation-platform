<?php
/**
 * Post Like Service
 * Handles like-related operations
 */
class PostLikeService {
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
            $checkTable = $this->pdo->query("SHOW TABLES LIKE 'post_like'");
            if ($checkTable->rowCount() > 0) {
                return true; // Table already exists
            }
            
            $sql = "CREATE TABLE post_like (
                id_like INT AUTO_INCREMENT PRIMARY KEY,
                id_post INT NOT NULL,
                id_user INT NOT NULL,
                date_like TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_like (id_post, id_user),
                INDEX idx_post (id_post),
                INDEX idx_user (id_user)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->pdo->exec($sql);
            return true;
        } catch (Exception $e) {
            error_log("Error creating post_like table: " . $e->getMessage());
            // Try to create without constraints if error
            try {
                $sql = "CREATE TABLE IF NOT EXISTS post_like (
                    id_like INT AUTO_INCREMENT PRIMARY KEY,
                    id_post INT NOT NULL,
                    id_user INT NOT NULL,
                    date_like TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_like (id_post, id_user)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $this->pdo->exec($sql);
                return true;
            } catch (Exception $e2) {
                error_log("Error creating post_like table (without FK): " . $e2->getMessage());
                return false;
            }
        }
    }
    
    /**
     * Add a like
     */
    public function addLike($id_post, $id_user) {
        try {
            $sql = "INSERT INTO post_like (id_post, id_user) VALUES (:id_post, :id_user)
                    ON DUPLICATE KEY UPDATE date_like = CURRENT_TIMESTAMP";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':id_post' => (int)$id_post,
                ':id_user' => (int)$id_user
            ]);
            return $result;
        } catch (Exception $e) {
            error_log("Error addLike: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove a like
     */
    public function removeLike($id_post, $id_user) {
        try {
            $sql = "DELETE FROM post_like WHERE id_post = :id_post AND id_user = :id_user";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':id_post' => (int)$id_post,
                ':id_user' => (int)$id_user
            ]);
            return $result;
        } catch (Exception $e) {
            error_log("Error removeLike: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user liked a post
     */
    public function isLiked($id_post, $id_user) {
        try {
            $sql = "SELECT COUNT(*) as count FROM post_like WHERE id_post = :id_post AND id_user = :id_user";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id_post' => (int)$id_post,
                ':id_user' => (int)$id_user
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Error isLiked: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get like count for a post
     */
    public function getLikeCount($id_post) {
        try {
            $sql = "SELECT COUNT(*) as count FROM post_like WHERE id_post = :id_post";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_post' => (int)$id_post]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Error getLikeCount: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Toggle like status
     */
    public function toggleLike($id_post, $id_user) {
        try {
            error_log("toggleLike called with post_id: $id_post, user_id: $id_user");
            
            // Ensure table exists
            if (!$this->createTableIfNotExists()) {
                error_log("Failed to ensure post_like table exists");
                return false;
            }
            
            // Validate IDs
            $id_post = (int)$id_post;
            $id_user = (int)$id_user;
            
            if ($id_post <= 0 || $id_user <= 0) {
                error_log("Invalid post_id or user_id: post_id=$id_post, user_id=$id_user");
                return false;
            }
            
            // Check if post exists
            $postExists = $this->pdo->query("SELECT COUNT(*) FROM post WHERE id_post = $id_post")->fetchColumn();
            if (!$postExists) {
                error_log("Post with id $id_post does not exist");
                return false;
            }
            
            $isLiked = $this->isLiked($id_post, $id_user);
            error_log("Current like status for post $id_post by user $id_user: " . ($isLiked ? 'liked' : 'not liked'));
            
            if ($isLiked) {
                $result = $this->removeLike($id_post, $id_user);
                error_log("Remove like result: " . ($result ? 'success' : 'failed'));
                return $result ? 'removed' : false;
            } else {
                $result = $this->addLike($id_post, $id_user);
                error_log("Add like result: " . ($result ? 'success' : 'failed'));
                return $result ? 'added' : false;
            }
        } catch (Exception $e) {
            $errorMsg = "Error in toggleLike: " . $e->getMessage() . "\n" . $e->getTraceAsString();
            error_log($errorMsg);
            return false;
        }
    }
    
    /**
     * Get users who liked a post
     * Returns paginated list of users with their profile information
     * Note: Adapted to use 'users' table with 'cin' instead of 'user' table with 'id_user'
     * 
     * @param int $id_post Post ID
     * @param int $limit Maximum number of results (default: 20)
     * @param int $offset Offset for pagination (default: 0)
     * @return array Array of user data with: cin (id), firstname, lastname, profile_picture, date_like
     */
    public function getUsersWhoLiked($id_post, $limit = 20, $offset = 0) {
        try {
            // Check if profile_picture column exists
            $checkColumn = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
            $hasProfilePicture = $checkColumn && $checkColumn->rowCount() > 0;
            
            $profilePictureField = $hasProfilePicture ? 'u.profile_picture' : 'NULL as profile_picture';
            
            $sql = "SELECT u.cin as id, 
                           u.firstname, 
                           u.lastname, 
                           $profilePictureField,
                           pl.date_like
                    FROM post_like pl
                    LEFT JOIN users u ON pl.id_user = u.cin
                    WHERE pl.id_post = :id_post
                    ORDER BY pl.date_like DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id_post', (int)$id_post, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format results to ensure consistent structure
            return array_map(function($row) {
                return [
                    'id' => (int)$row['id'],
                    'firstname' => $row['firstname'] ?? '',
                    'lastname' => $row['lastname'] ?? '',
                    'profile_picture' => $row['profile_picture'] ?? null,
                    'date_like' => $row['date_like'] ?? null
                ];
            }, $results);
        } catch (Exception $e) {
            error_log("Error getUsersWhoLiked: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of users who liked a post (for pagination)
     * 
     * @param int $id_post Post ID
     * @return int Total count
     */
    public function getUsersWhoLikedCount($id_post) {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM post_like 
                    WHERE id_post = :id_post";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_post' => (int)$id_post]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log("Error getUsersWhoLikedCount: " . $e->getMessage());
            return 0;
        }
    }
}


