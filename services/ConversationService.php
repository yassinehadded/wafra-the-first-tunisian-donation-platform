<?php
/**
 * Conversation Service
 * Handles conversation creation, retrieval, and management
 */
class ConversationService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->createTablesIfNotExist();
    }
    
    /**
     * Create tables if they don't exist
     */
    private function createTablesIfNotExist() {
        try {
            $sqlFile = __DIR__ . '/../sql/messaging_tables.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                // Remove comments and split by semicolon
                $sql = preg_replace('/--.*$/m', '', $sql);
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        try {
                            $this->pdo->exec($statement);
                        } catch (PDOException $e) {
                            // Ignore "table already exists" errors
                            if (strpos($e->getMessage(), 'already exists') === false) {
                                error_log("Error creating messaging table: " . $e->getMessage());
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error in createTablesIfNotExist: " . $e->getMessage());
        }
    }
    
    /**
     * Create or get existing conversation
     * Ensures user_one_id < user_two_id for consistency
     * 
     * @param int $userOneId First user ID
     * @param int $userTwoId Second user ID
     * @param string|null $entityType Related entity type (donation, post, request)
     * @param int|null $entityId Related entity ID
     * @return array|false Conversation data or false on error
     */
    public function createOrGetConversation($userOneId, $userTwoId, $entityType = null, $entityId = null) {
        try {
            // Prevent self-messaging
            if ($userOneId == $userTwoId) {
                error_log("Attempted self-messaging: user_id=$userOneId");
                return false;
            }
            
            // Normalize user IDs (always store with smaller ID first)
            if ($userOneId > $userTwoId) {
                $temp = $userOneId;
                $userOneId = $userTwoId;
                $userTwoId = $temp;
            }
            
            // Check if conversation already exists
            $sql = "SELECT * FROM conversations 
                    WHERE user_one_id = :user_one AND user_two_id = :user_two";
            $params = [
                ':user_one' => (int)$userOneId,
                ':user_two' => (int)$userTwoId
            ];
            
            if ($entityType && $entityId) {
                $sql .= " AND related_entity_type = :entity_type AND related_entity_id = :entity_id";
                $params[':entity_type'] = $entityType;
                $params[':entity_id'] = (int)$entityId;
            } else {
                $sql .= " AND related_entity_type IS NULL AND related_entity_id IS NULL";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Check if blocked
                if ($existing['is_blocked']) {
                    return false;
                }
                return $existing;
            }
            
            // Create new conversation
            $sql = "INSERT INTO conversations (user_one_id, user_two_id, related_entity_type, related_entity_id) 
                    VALUES (:user_one, :user_two, :entity_type, :entity_id)";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':user_one' => (int)$userOneId,
                ':user_two' => (int)$userTwoId,
                ':entity_type' => $entityType ?: null,
                ':entity_id' => $entityId ? (int)$entityId : null
            ]);
            
            if ($result) {
                $conversationId = $this->pdo->lastInsertId();
                return $this->getConversationById($conversationId);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error createOrGetConversation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get conversation by ID
     */
    public function getConversationById($conversationId) {
        try {
            $sql = "SELECT * FROM conversations WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => (int)$conversationId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getConversationById: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user belongs to conversation
     */
    public function userBelongsToConversation($conversationId, $userId) {
        try {
            $conversationId = (int)$conversationId;
            $userId = (int)$userId;
            
            // First, get the conversation
            $sql = "SELECT * FROM conversations WHERE id = :conversation_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':conversation_id' => $conversationId]);
            $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$conversation) {
                error_log("Conversation not found: $conversationId");
                return false;
            }
            
            // Check if user is part of conversation
            $isUserOne = ((int)$conversation['user_one_id'] === $userId);
            $isUserTwo = ((int)$conversation['user_two_id'] === $userId);
            $isBlocked = (int)($conversation['is_blocked'] ?? 0) === 1;
            
            error_log("Conversation check - ID: $conversationId, User: $userId, UserOne: {$conversation['user_one_id']}, UserTwo: {$conversation['user_two_id']}, IsUserOne: " . ($isUserOne ? 'true' : 'false') . ", IsUserTwo: " . ($isUserTwo ? 'true' : 'false') . ", Blocked: " . ($isBlocked ? 'true' : 'false'));
            
            if ($isBlocked) {
                return false;
            }
            
            return $isUserOne || $isUserTwo;
        } catch (Exception $e) {
            error_log("Error userBelongsToConversation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all conversations for a user
     * Ordered by last_message_at DESC
     */
    public function getUserConversations($userId) {
        try {
            error_log("=== ConversationService::getUserConversations START ===");
            error_log("User ID: " . (int)$userId);
            error_log("User ID type: " . gettype($userId));
            
            // Initialize conversations array
            $conversations = [];
            
            // First, try a simple query to get conversations
            error_log("Executing simple query with user_id: " . (int)$userId);
            $simpleSql = "SELECT c.*
                    FROM conversations c
                    WHERE (c.user_one_id = :user_id1 OR c.user_two_id = :user_id2)
                    AND (c.is_blocked IS NULL OR c.is_blocked = 0)";
            
            try {
                $simpleStmt = $this->pdo->prepare($simpleSql);
                $simpleStmt->bindValue(':user_id1', (int)$userId, PDO::PARAM_INT);
                $simpleStmt->bindValue(':user_id2', (int)$userId, PDO::PARAM_INT);
                $simpleStmt->execute();
                $conversations = $simpleStmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Simple query executed successfully, found " . count($conversations) . " conversations");
                
                if (count($conversations) > 0) {
                    error_log("First conversation from simple query: " . json_encode($conversations[0]));
                }
            } catch (Exception $e) {
                error_log("ERROR in simple query: " . $e->getMessage());
                error_log("SQL: " . $simpleSql);
                error_log("User ID 1: " . (int)$userId);
                error_log("User ID 2: " . (int)$userId);
                throw $e;
            }
            
            // Now add message data for each conversation
            if (count($conversations) > 0) {
                foreach ($conversations as &$conv) {
                    // Get unread count
                    $unreadSql = "SELECT COUNT(*) as count FROM messages 
                                 WHERE conversation_id = :conv_id 
                                 AND is_read = 0 
                                 AND sender_id != :user_id";
                    $unreadStmt = $this->pdo->prepare($unreadSql);
                    $unreadStmt->bindValue(':conv_id', (int)$conv['id'], PDO::PARAM_INT);
                    $unreadStmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
                    $unreadStmt->execute();
                    $unreadResult = $unreadStmt->fetch(PDO::FETCH_ASSOC);
                    $conv['unread_count'] = (int)($unreadResult['count'] ?? 0);
                    
                    // Get last message
                    $lastMsgSql = "SELECT message, created_at FROM messages 
                                  WHERE conversation_id = :conv_id 
                                  ORDER BY created_at DESC LIMIT 1";
                    $lastMsgStmt = $this->pdo->prepare($lastMsgSql);
                    $lastMsgStmt->bindValue(':conv_id', (int)$conv['id'], PDO::PARAM_INT);
                    $lastMsgStmt->execute();
                    $lastMsg = $lastMsgStmt->fetch(PDO::FETCH_ASSOC);
                    $conv['last_message'] = $lastMsg['message'] ?? null;
                    $conv['last_message_time'] = $lastMsg['created_at'] ?? null;
                }
                unset($conv); // Break reference
                error_log("Added message data to " . count($conversations) . " conversations");
            }
            
            // Now fetch user data separately for each conversation
            if (count($conversations) > 0) {
                foreach ($conversations as &$conv) {
                    // Fetch user_one data
                    $user1Sql = "SELECT firstname, lastname, profile_picture FROM users WHERE cin = :user_id";
                    $user1Stmt = $this->pdo->prepare($user1Sql);
                    $user1Stmt->bindValue(':user_id', (int)$conv['user_one_id'], PDO::PARAM_INT);
                    $user1Stmt->execute();
                    $user1 = $user1Stmt->fetch(PDO::FETCH_ASSOC);
                    if ($user1) {
                        $conv['user_one_firstname'] = $user1['firstname'];
                        $conv['user_one_lastname'] = $user1['lastname'];
                        $conv['user_one_avatar'] = $user1['profile_picture'];
                    } else {
                        error_log("User 1 (ID: " . $conv['user_one_id'] . ") not found in users table");
                        $conv['user_one_firstname'] = null;
                        $conv['user_one_lastname'] = null;
                        $conv['user_one_avatar'] = null;
                    }
                    
                    // Fetch user_two data
                    $user2Sql = "SELECT firstname, lastname, profile_picture FROM users WHERE cin = :user_id";
                    $user2Stmt = $this->pdo->prepare($user2Sql);
                    $user2Stmt->bindValue(':user_id', (int)$conv['user_two_id'], PDO::PARAM_INT);
                    $user2Stmt->execute();
                    $user2 = $user2Stmt->fetch(PDO::FETCH_ASSOC);
                    if ($user2) {
                        $conv['user_two_firstname'] = $user2['firstname'];
                        $conv['user_two_lastname'] = $user2['lastname'];
                        $conv['user_two_avatar'] = $user2['profile_picture'];
                    } else {
                        error_log("User 2 (ID: " . $conv['user_two_id'] . ") not found in users table");
                        $conv['user_two_firstname'] = null;
                        $conv['user_two_lastname'] = null;
                        $conv['user_two_avatar'] = null;
                    }
                }
                unset($conv); // Break reference
                error_log("Fetched user data separately for " . count($conversations) . " conversations");
            } else {
                error_log("No conversations found for user " . (int)$userId);
            }
            
            if (count($conversations) > 0) {
                error_log("First conversation sample (raw): " . json_encode($conversations[0]));
            } else {
                error_log("No conversations found - checking why...");
                // Try a simpler query to see if JOINs are the issue
                $simpleSql = "SELECT c.* FROM conversations c 
                              WHERE (c.user_one_id = :user_id_simple OR c.user_two_id = :user_id_simple2)
                              AND (c.is_blocked IS NULL OR c.is_blocked = 0)";
                $simpleStmt = $this->pdo->prepare($simpleSql);
                $simpleStmt->bindValue(':user_id_simple', (int)$userId, PDO::PARAM_INT);
                $simpleStmt->bindValue(':user_id_simple2', (int)$userId, PDO::PARAM_INT);
                $simpleStmt->execute();
                $simpleConvs = $simpleStmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Simple query found " . count($simpleConvs) . " conversations");
                if (count($simpleConvs) > 0) {
                    error_log("Simple query sample: " . json_encode($simpleConvs[0]));
                    
                    // Check if users exist for these IDs
                    $userOneId = $simpleConvs[0]['user_one_id'];
                    $userTwoId = $simpleConvs[0]['user_two_id'];
                    
                    $checkUser1Sql = "SELECT cin, firstname, lastname, profile_picture FROM users WHERE cin = :user_id";
                    $checkUser1Stmt = $this->pdo->prepare($checkUser1Sql);
                    $checkUser1Stmt->bindValue(':user_id', (int)$userOneId, PDO::PARAM_INT);
                    $checkUser1Stmt->execute();
                    $user1 = $checkUser1Stmt->fetch(PDO::FETCH_ASSOC);
                    error_log("User 1 (ID: $userOneId) found: " . ($user1 ? 'YES - ' . json_encode($user1) : 'NO'));
                    
                    $checkUser2Sql = "SELECT cin, firstname, lastname, profile_picture FROM users WHERE cin = :user_id";
                    $checkUser2Stmt = $this->pdo->prepare($checkUser2Sql);
                    $checkUser2Stmt->bindValue(':user_id', (int)$userTwoId, PDO::PARAM_INT);
                    $checkUser2Stmt->execute();
                    $user2 = $checkUser2Stmt->fetch(PDO::FETCH_ASSOC);
                    error_log("User 2 (ID: $userTwoId) found: " . ($user2 ? 'YES - ' . json_encode($user2) : 'NO'));
                }
            }
            
            // Format conversations to include other user info
            // Group by other_user_id to avoid duplicates (keep most recent conversation per user)
            $formatted = [];
            $conversationsByOtherUser = []; // Group conversations by other_user_id
            
            error_log("=== STARTING FORMATTING ===");
            error_log("Raw conversations count: " . count($conversations));
            error_log("User ID: " . (int)$userId);
            
            foreach ($conversations as $index => $conv) {
                try {
                    error_log("--- Formatting conversation " . ($index + 1) . "/" . count($conversations) . " ---");
                    error_log("Conversation ID: " . ($conv['id'] ?? 'unknown'));
                    error_log("User one ID: " . ($conv['user_one_id'] ?? 'unknown'));
                    error_log("User two ID: " . ($conv['user_two_id'] ?? 'unknown'));
                    
                    $isUserOne = (int)$conv['user_one_id'] === (int)$userId;
                    $otherUserId = $isUserOne ? (int)$conv['user_two_id'] : (int)$conv['user_one_id'];
                    
                    error_log("Is current user user_one? " . ($isUserOne ? 'YES' : 'NO'));
                    error_log("Other user ID: " . $otherUserId);
                    
                    // If current user is user_one, other user is user_two (and vice versa)
                    $otherUserName = $isUserOne 
                        ? trim(($conv['user_two_firstname'] ?? '') . ' ' . ($conv['user_two_lastname'] ?? ''))
                        : trim(($conv['user_one_firstname'] ?? '') . ' ' . ($conv['user_one_lastname'] ?? ''));
                    
                    $otherUserAvatar = $isUserOne ? ($conv['user_two_avatar'] ?? null) : ($conv['user_one_avatar'] ?? null);
                    
                    // Fallback if name is empty
                    if (empty($otherUserName) || trim($otherUserName) === '') {
                        $otherUserName = 'Utilisateur';
                    }
                    
                    $formattedConv = [
                        'id' => (int)$conv['id'],
                        'other_user_id' => (int)$otherUserId,
                        'other_user_name' => $otherUserName,
                        'other_user_avatar' => $otherUserAvatar,
                        'related_entity_type' => $conv['related_entity_type'] ?? null,
                        'related_entity_id' => isset($conv['related_entity_id']) && $conv['related_entity_id'] ? (int)$conv['related_entity_id'] : null,
                        'last_message' => $conv['last_message'] ?? null,
                        'last_message_at' => $conv['last_message_time'] ?? $conv['last_message_at'] ?? null,
                        'unread_count' => (int)($conv['unread_count'] ?? 0),
                        'created_at' => $conv['created_at'] ?? null
                    ];
                    
                    // Group by other_user_id - keep the conversation with the most recent message
                    if (!isset($conversationsByOtherUser[$otherUserId])) {
                        $conversationsByOtherUser[$otherUserId] = $formattedConv;
                    } else {
                        // Compare timestamps - keep the one with more recent last_message_at
                        $existing = $conversationsByOtherUser[$otherUserId];
                        $existingTime = $existing['last_message_at'] ?? $existing['created_at'] ?? '1970-01-01';
                        $newTime = $formattedConv['last_message_at'] ?? $formattedConv['created_at'] ?? '1970-01-01';
                        
                        if ($newTime > $existingTime) {
                            // Merge unread counts
                            $formattedConv['unread_count'] = (int)($formattedConv['unread_count'] + $existing['unread_count']);
                            $conversationsByOtherUser[$otherUserId] = $formattedConv;
                        } else {
                            // Merge unread counts into existing
                            $conversationsByOtherUser[$otherUserId]['unread_count'] = (int)($existing['unread_count'] + $formattedConv['unread_count']);
                        }
                    }
                } catch (Exception $e) {
                    error_log("ERROR formatting conversation " . ($conv['id'] ?? 'unknown') . ": " . $e->getMessage());
                    // Continue with next conversation
                }
            }
            
            // Convert grouped conversations to array
            $formatted = array_values($conversationsByOtherUser);
            
            // Sort by last_message_at DESC (most recent first)
            usort($formatted, function($a, $b) {
                $timeA = $a['last_message_at'] ?? $a['created_at'] ?? '1970-01-01';
                $timeB = $b['last_message_at'] ?? $b['created_at'] ?? '1970-01-01';
                return strcmp($timeB, $timeA);
            });
            
            error_log("=== FORMATTING COMPLETE ===");
            error_log("Formatted " . count($formatted) . " unique conversations from " . count($conversations) . " raw conversations");
            error_log("=== RETURNING " . count($formatted) . " FORMATTED CONVERSATIONS ===");
            error_log("=== ConversationService::getUserConversations END ===");
            
            return $formatted;
        } catch (Exception $e) {
            error_log("Error getUserConversations: " . $e->getMessage());
            error_log("Exception trace: " . $e->getTraceAsString());
            return [];
        }
    }
    
    /**
     * Update last_message_at timestamp
     */
    public function updateLastMessageAt($conversationId) {
        try {
            $sql = "UPDATE conversations 
                    SET last_message_at = CURRENT_TIMESTAMP 
                    WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => (int)$conversationId]);
        } catch (Exception $e) {
            error_log("Error updateLastMessageAt: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Block a conversation
     */
    public function blockConversation($conversationId, $blockedByUserId) {
        try {
            $sql = "UPDATE conversations 
                    SET is_blocked = 1, blocked_by = :blocked_by 
                    WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':id' => (int)$conversationId,
                ':blocked_by' => (int)$blockedByUserId
            ]);
        } catch (Exception $e) {
            error_log("Error blockConversation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get other user ID in conversation
     */
    public function getOtherUserId($conversationId, $currentUserId) {
        try {
            $sql = "SELECT CASE 
                        WHEN user_one_id = :user_id THEN user_two_id 
                        ELSE user_one_id 
                    END as other_user_id
                    FROM conversations 
                    WHERE id = :conversation_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':conversation_id' => (int)$conversationId,
                ':user_id' => (int)$currentUserId
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['other_user_id'] : null;
        } catch (Exception $e) {
            error_log("Error getOtherUserId: " . $e->getMessage());
            return null;
        }
    }
}

