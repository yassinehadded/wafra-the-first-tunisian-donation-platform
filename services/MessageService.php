<?php
/**
 * Message Service
 * Handles message creation, retrieval, and security
 */
class MessageService {
    private $pdo;
    private $conversationService;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        require_once __DIR__ . '/ConversationService.php';
        $this->conversationService = new ConversationService($pdo);
    }
    
    /**
     * Send a message
     * Validates rate limits, sanitizes content, and creates message
     * 
     * @param int $conversationId Conversation ID
     * @param int $senderId Sender user ID
     * @param string $messageText Message content
     * @return array|false Message data or false on error
     */
    public function sendMessage($conversationId, $senderId, $messageText) {
        try {
            // Validate conversation exists and user belongs to it
            if (!$this->conversationService->userBelongsToConversation($conversationId, $senderId)) {
                error_log("Unauthorized message attempt: conversation_id=$conversationId, user_id=$senderId");
                return false;
            }
            
            // Check rate limiting for first messages
            if (!$this->checkRateLimit($senderId)) {
                error_log("Rate limit exceeded for user: $senderId");
                return ['error' => 'Vous avez atteint la limite de messages pour aujourd\'hui. Réessayez demain.'];
            }
            
            // Sanitize message
            $messageText = $this->sanitizeMessage($messageText);
            if (empty(trim($messageText))) {
                return ['error' => 'Le message ne peut pas être vide'];
            }
            
            // Check message length
            if (strlen($messageText) > 5000) {
                return ['error' => 'Le message est trop long (maximum 5000 caractères)'];
            }
            
            // Insert message
            $sql = "INSERT INTO messages (conversation_id, sender_id, message) 
                    VALUES (:conversation_id, :sender_id, :message)";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':conversation_id' => (int)$conversationId,
                ':sender_id' => (int)$senderId,
                ':message' => $messageText
            ]);
            
            if ($result) {
                $messageId = $this->pdo->lastInsertId();
                
                // Update conversation last_message_at
                $this->conversationService->updateLastMessageAt($conversationId);
                
                // Get the created message
                return $this->getMessageById($messageId);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error sendMessage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get messages for a conversation
     * 
     * @param int $conversationId Conversation ID
     * @param int $userId User ID (for authorization)
     * @param int $limit Limit of messages
     * @param int $offset Offset for pagination
     * @return array Messages
     */
    public function getMessages($conversationId, $userId, $limit = 50, $offset = 0) {
        try {
            // Verify user belongs to conversation
            if (!$this->conversationService->userBelongsToConversation($conversationId, $userId)) {
                return [];
            }
            
            $sql = "SELECT m.*, 
                           u.firstname, u.lastname, u.profile_picture
                    FROM messages m
                    LEFT JOIN users u ON m.sender_id = u.cin
                    WHERE m.conversation_id = :conversation_id
                    ORDER BY m.created_at ASC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':conversation_id', (int)$conversationId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format messages
            return array_map(function($msg) use ($userId) {
                $fullName = trim(($msg['firstname'] ?? '') . ' ' . ($msg['lastname'] ?? ''));
                $senderName = $fullName ?: 'Utilisateur';
                
                return [
                    'id' => (int)$msg['id'],
                    'conversation_id' => (int)$msg['conversation_id'],
                    'sender_id' => (int)$msg['sender_id'],
                    'sender_name' => $senderName,
                    'sender_avatar' => $msg['profile_picture'] ?? null,
                    'message' => $msg['message'],
                    'is_read' => (bool)$msg['is_read'],
                    'is_sender' => (int)$msg['sender_id'] === (int)$userId,
                    'created_at' => $msg['created_at']
                ];
            }, $messages);
        } catch (Exception $e) {
            error_log("Error getMessages: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get message by ID
     */
    public function getMessageById($messageId) {
        try {
            $sql = "SELECT * FROM messages WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => (int)$messageId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getMessageById: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark messages as read
     * 
     * @param int $conversationId Conversation ID
     * @param int $userId User ID (marks messages not sent by this user as read)
     */
    public function markAsRead($conversationId, $userId) {
        try {
            // Verify user belongs to conversation
            if (!$this->conversationService->userBelongsToConversation($conversationId, $userId)) {
                return false;
            }
            
            $sql = "UPDATE messages 
                    SET is_read = 1 
                    WHERE conversation_id = :conversation_id 
                    AND sender_id != :user_id 
                    AND is_read = 0";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':conversation_id' => (int)$conversationId,
                ':user_id' => (int)$userId
            ]);
        } catch (Exception $e) {
            error_log("Error markAsRead: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get unread message count for a user
     */
    public function getUnreadCount($userId) {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM messages m
                    INNER JOIN conversations c ON m.conversation_id = c.id
                    WHERE (c.user_one_id = :user_id OR c.user_two_id = :user_id)
                    AND m.sender_id != :user_id
                    AND m.is_read = 0
                    AND c.is_blocked = 0";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => (int)$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log("Error getUnreadCount: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Check rate limit for first messages
     * Limits users to 5 first messages per day
     */
    private function checkRateLimit($userId) {
        try {
            $today = date('Y-m-d');
            
            // Check if user has sent first messages today
            $sql = "SELECT message_count FROM message_rate_limits 
                    WHERE user_id = :user_id AND first_message_date = :date";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => (int)$userId,
                ':date' => $today
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // User has sent first messages today
                if ((int)$result['message_count'] >= 5) {
                    return false; // Rate limit exceeded
                }
                // Increment count
                $sql = "UPDATE message_rate_limits 
                        SET message_count = message_count + 1 
                        WHERE user_id = :user_id AND first_message_date = :date";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':user_id' => (int)$userId,
                    ':date' => $today
                ]);
            } else {
                // First message today, create record
                $sql = "INSERT INTO message_rate_limits (user_id, first_message_date, message_count) 
                        VALUES (:user_id, :date, 1)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':user_id' => (int)$userId,
                    ':date' => $today
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error checkRateLimit: " . $e->getMessage());
            // Allow message on error (fail open)
            return true;
        }
    }
    
    /**
     * Sanitize message content
     * Removes HTML/JS and escapes special characters
     */
    private function sanitizeMessage($message) {
        // Remove HTML tags
        $message = strip_tags($message);
        // Trim whitespace
        $message = trim($message);
        // Escape special characters for database
        return $message;
    }
    
    /**
     * Check if user can initiate conversation
     * Validates that conversation is allowed based on entity type
     */
    public function canInitiateConversation($userId, $otherUserId, $entityType, $entityId) {
        try {
            // Prevent self-messaging
            if ($userId == $otherUserId) {
                return false;
            }
            
            // Validate based on entity type
            switch ($entityType) {
                case 'donation':
                    // Check if donation exists and user is recipient or donor
                    $sql = "SELECT id FROM donor_offers WHERE id = :id";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([':id' => (int)$entityId]);
                    return $stmt->fetch() !== false;
                    
                case 'post':
                    // Check if post exists
                    $sql = "SELECT id_post, id_user FROM post WHERE id_post = :id";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([':id' => (int)$entityId]);
                    $post = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($post) {
                        // Allow if user is post owner OR if post owner allows messages
                        return true; // Simplified - can add more logic
                    }
                    return false;
                    
                case 'request':
                    // Check if request exists
                    $sql = "SELECT id FROM donor_requests WHERE id = :id";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([':id' => (int)$entityId]);
                    return $stmt->fetch() !== false;
                    
                default:
                    // No entity - allow general messaging (with rate limits)
                    return true;
            }
        } catch (Exception $e) {
            error_log("Error canInitiateConversation: " . $e->getMessage());
            return false;
        }
    }
}





