<?php
/**
 * Admin Message Controller
 * Handles admin moderation of conversations and messages
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../services/ConversationService.php';
require_once __DIR__ . '/../services/MessageService.php';

class AdminMessageController {
    private $pdo;
    private $conversationService;
    private $messageService;
    
    public function __construct() {
        // Session is already started in index.php
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is admin
        if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        $this->pdo = Database::connect();
        $this->conversationService = new ConversationService($this->pdo);
        $this->messageService = new MessageService($this->pdo);
    }
    
    /**
     * Get all conversations (admin view)
     */
    public function getAllConversations() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            $sql = "SELECT c.*,
                           u1.firstname as user_one_firstname,
                           u1.lastname as user_one_lastname,
                           u2.firstname as user_two_firstname,
                           u2.lastname as user_two_lastname,
                           (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id) as message_count
                    FROM conversations c
                    LEFT JOIN users u1 ON c.user_one_id = u1.cin
                    LEFT JOIN users u2 ON c.user_two_id = u2.cin
                    ORDER BY c.last_message_at DESC, c.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'conversations' => $conversations
            ]);
        } catch (Exception $e) {
            error_log("Admin getAllConversations error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Server error']);
        }
    }
    
    /**
     * Get messages in a conversation (read-only for admin)
     */
    public function getConversationMessages() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
            
            if ($conversationId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid conversation ID']);
                return;
            }
            
            $sql = "SELECT m.*,
                           u.firstname, u.lastname, u.email
                    FROM messages m
                    LEFT JOIN users u ON m.sender_id = u.cin
                    WHERE m.conversation_id = :conversation_id
                    ORDER BY m.created_at ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':conversation_id' => $conversationId]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'messages' => $messages
            ]);
        } catch (Exception $e) {
            error_log("Admin getConversationMessages error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Server error']);
        }
    }
    
    /**
     * Block a conversation
     */
    public function blockConversation() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
                return;
            }
            
            $conversationId = isset($data['conversation_id']) ? (int)$data['conversation_id'] : 0;
            $adminId = (int)$_SESSION['userID'];
            
            if ($conversationId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid conversation ID']);
                return;
            }
            
            $result = $this->conversationService->blockConversation($conversationId, $adminId);
            
            if ($result) {
                // Log admin action
                $this->logAdminAction('block_conversation', $conversationId, $adminId);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Conversation blocked successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Failed to block conversation']);
            }
        } catch (Exception $e) {
            error_log("Admin blockConversation error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Server error']);
        }
    }
    
    /**
     * Get reported conversations
     */
    public function getReportedConversations() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            // For now, return blocked conversations as "reported"
            // In future, can add a separate reports table
            $sql = "SELECT c.*,
                           u1.firstname as user_one_firstname,
                           u1.lastname as user_one_lastname,
                           u2.firstname as user_two_firstname,
                           u2.lastname as user_two_lastname
                    FROM conversations c
                    LEFT JOIN users u1 ON c.user_one_id = u1.cin
                    LEFT JOIN users u2 ON c.user_two_id = u2.cin
                    WHERE c.is_blocked = 1
                    ORDER BY c.updated_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'conversations' => $conversations
            ]);
        } catch (Exception $e) {
            error_log("Admin getReportedConversations error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Server error']);
        }
    }
    
    /**
     * Log admin action
     */
    private function logAdminAction($action, $entityId, $adminId) {
        try {
            $sql = "INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, created_at) 
                    VALUES (:admin_id, :action, 'conversation', :entity_id, NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':admin_id' => $adminId,
                ':action' => $action,
                ':entity_id' => $entityId
            ]);
        } catch (Exception $e) {
            // Log table might not exist, just log to error log
            error_log("Admin action logged: $action on conversation $entityId by admin $adminId");
        }
    }
}





