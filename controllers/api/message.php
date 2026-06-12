<?php
/**
 * Message API Endpoint
 * Handles user-side messaging operations
 */
// Disable error display and clean output buffers
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Clean any existing output buffers to ensure clean JSON output
while (ob_get_level()) {
    ob_end_clean();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure session cookie is sent
if (isset($_COOKIE[session_name()])) {
    // Session cookie exists
} else {
    // Try to regenerate session ID if needed
    if (session_status() === PHP_SESSION_ACTIVE && !isset($_SESSION['userID'])) {
        error_log("Session active but userID not set. Session ID: " . session_id());
    }
}

header('Content-Type: application/json; charset=utf-8');

// Error handler to return JSON errors
function jsonError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

try {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../config/Database.php';
    require_once __DIR__ . '/../../config/autoload.php';
    require_once __DIR__ . '/../../services/ConversationService.php';
    require_once __DIR__ . '/../../services/MessageService.php';
    require_once __DIR__ . '/../../services/NotificationService.php';
    require_once __DIR__ . '/../../models/Post.php';
} catch (Exception $e) {
    error_log("API Error loading files: " . $e->getMessage());
    jsonError('Server configuration error', 500);
}

if (!isset($_SESSION['userID'])) {
    error_log("API message.php - User not logged in. Session data: " . print_r($_SESSION, true));
    jsonError('User not logged in', 401);
}

// Debug: Log session info
error_log("API message.php - Session userID: " . ($_SESSION['userID'] ?? 'not set'));

try {
    $pdo = Database::connect();
    if (!$pdo) {
        jsonError('Database connection failed', 500);
    }
    $conversationService = new ConversationService($pdo);
    $messageService = new MessageService($pdo);
    $userId = (int)$_SESSION['userID'];
    
    // Get base URL for avatar paths
    $baseUrl = defined('BASE_URL') ? BASE_URL : '';
    if (empty($baseUrl)) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $protocol . '://' . $host . '/wafra/wafra-integration';
    }
} catch (Exception $e) {
    error_log("API Error initializing: " . $e->getMessage());
    jsonError('Server initialization error', 500);
}

$method = $_SERVER['REQUEST_METHOD'];

// Handle action parameter - can be in GET or POST
// Note: index.php passes action=api_message, so we need to get the sub-action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// If action is 'api_message', get the actual action from query string
if ($action === 'api_message' || empty($action)) {
    // Check for sub-action parameter
    $action = $_GET['subaction'] ?? $_GET['action2'] ?? $_GET['msg_action'] ?? '';
    
    // Fallback: try to parse from URL
    if (empty($action) && isset($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $params);
        $action = $params['action'] ?? $params['subaction'] ?? '';
        // Remove 'api_message' if it's the action
        if ($action === 'api_message') {
            $action = '';
        }
    }
}

// If still empty, default to get_conversations for GET requests
if (empty($action) && $method === 'GET') {
    $action = 'get_conversations';
}

try {
    switch ($action) {
        case 'get_conversations':
            // Debug: Check raw conversations from database
            $debugInfo = [];
            $debugInfo['user_id'] = $userId;
            
            // Direct database query to check conversations
            try {
                // Check without is_blocked filter
                $checkSql = "SELECT id, user_one_id, user_two_id, is_blocked, created_at 
                           FROM conversations 
                           WHERE (user_one_id = :user_id1 OR user_two_id = :user_id2)";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([
                    ':user_id1' => (int)$userId,
                    ':user_id2' => (int)$userId
                ]);
                $rawConversations = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
                $debugInfo['raw_conversations_count'] = count($rawConversations);
                $debugInfo['raw_conversations'] = $rawConversations;
                
                // Check with is_blocked filter (same as service)
                $checkSqlFiltered = "SELECT id, user_one_id, user_two_id, is_blocked, created_at 
                           FROM conversations 
                           WHERE (user_one_id = :user_id1 OR user_two_id = :user_id2)
                           AND (is_blocked IS NULL OR is_blocked = 0)";
                $checkStmtFiltered = $pdo->prepare($checkSqlFiltered);
                $checkStmtFiltered->execute([
                    ':user_id1' => (int)$userId,
                    ':user_id2' => (int)$userId
                ]);
                $filteredConversations = $checkStmtFiltered->fetchAll(PDO::FETCH_ASSOC);
                $debugInfo['filtered_conversations_count'] = count($filteredConversations);
                $debugInfo['filtered_conversations'] = $filteredConversations;
                
                // Check all conversations (first 5) to see what's in DB
                $allConvsSql = "SELECT id, user_one_id, user_two_id, is_blocked FROM conversations LIMIT 5";
                $allConvsStmt = $pdo->query($allConvsSql);
                $allConvs = $allConvsStmt->fetchAll(PDO::FETCH_ASSOC);
                $debugInfo['sample_all_conversations'] = $allConvs;
            } catch (Exception $e) {
                $debugInfo['db_check_error'] = $e->getMessage();
                $debugInfo['db_check_error_trace'] = $e->getTraceAsString();
            }
            
            // Test direct SQL query to verify the issue
            try {
                $testSql = "SELECT c.* FROM conversations c 
                           WHERE (c.user_one_id = :user_id1 OR c.user_two_id = :user_id2)
                           AND (c.is_blocked IS NULL OR c.is_blocked = 0)";
                $testStmt = $pdo->prepare($testSql);
                $testStmt->bindValue(':user_id1', (int)$userId, PDO::PARAM_INT);
                $testStmt->bindValue(':user_id2', (int)$userId, PDO::PARAM_INT);
                $testStmt->execute();
                $testConvs = $testStmt->fetchAll(PDO::FETCH_ASSOC);
                $debugInfo['direct_sql_test_count'] = count($testConvs);
                $debugInfo['direct_sql_test_result'] = $testConvs;
                error_log("Direct SQL test found " . count($testConvs) . " conversations");
            } catch (Exception $e) {
                $debugInfo['direct_sql_test_error'] = $e->getMessage();
                error_log("Direct SQL test error: " . $e->getMessage());
            }
            
            // Get all conversations for current user
            $conversations = $conversationService->getUserConversations($userId);
            $debugInfo['service_conversations_count'] = count($conversations);
            $debugInfo['service_conversations_raw'] = $conversations; // Include raw data for debugging
            
            error_log("Service returned " . count($conversations) . " conversations");
            if (count($conversations) > 0) {
                error_log("First conversation from service: " . json_encode($conversations[0]));
            } else {
                error_log("WARNING: Service returned 0 conversations but direct SQL test found " . ($debugInfo['direct_sql_test_count'] ?? 0));
            }
            
            error_log("API: Received " . count($conversations) . " conversations from service");
            if (count($conversations) > 0) {
                error_log("API: First conversation from service: " . json_encode($conversations[0]));
            }
            
            // Format conversations with avatar URLs
            $formattedConversations = [];
            foreach ($conversations as $index => $conv) {
                try {
                    error_log("API: Formatting conversation " . ($index + 1) . "/" . count($conversations) . " - ID: " . ($conv['id'] ?? 'unknown'));
                    
                    $avatar = $baseUrl . '/view/frontoffice/assets/images/default-avatar.png';
                    if (!empty($conv['other_user_avatar'])) {
                        $avatarPath = __DIR__ . '/../../uploads/profile_pictures/' . basename($conv['other_user_avatar']);
                        if (file_exists($avatarPath)) {
                            $avatar = $baseUrl . '/uploads/profile_pictures/' . basename($conv['other_user_avatar']);
                        }
                    }
                    
                    $formatted = [
                        'id' => (int)($conv['id'] ?? 0),
                        'other_user_id' => (int)($conv['other_user_id'] ?? 0),
                        'other_user_name' => $conv['other_user_name'] ?? 'Utilisateur',
                        'other_user_avatar' => $avatar,
                        'related_entity_type' => $conv['related_entity_type'] ?? null,
                        'related_entity_id' => $conv['related_entity_id'] ?? null,
                        'last_message' => $conv['last_message'] ?? null,
                        'last_message_at' => $conv['last_message_at'] ?? null,
                        'unread_count' => (int)($conv['unread_count'] ?? 0),
                        'created_at' => $conv['created_at'] ?? null
                    ];
                    
                    error_log("API: Formatted conversation: " . json_encode($formatted));
                    $formattedConversations[] = $formatted;
                } catch (Exception $e) {
                    error_log("API: Error formatting conversation " . ($index + 1) . ": " . $e->getMessage());
                    error_log("API: Conversation data: " . json_encode($conv));
                }
            }
            
            error_log("API: Total formatted conversations: " . count($formattedConversations));
            
            // Add more debug info to help identify the issue
            $debugInfo['api_formatted_count'] = count($formattedConversations);
            $debugInfo['api_formatted_sample'] = count($formattedConversations) > 0 ? $formattedConversations[0] : null;
            $debugInfo['service_returned_count'] = count($conversations);
            $debugInfo['service_returned_sample'] = count($conversations) > 0 ? $conversations[0] : null;
            
            $response = [
                'success' => true,
                'conversations' => $formattedConversations,
                'debug' => $debugInfo // Add debug info to response
            ];
            
            error_log("API: Final response - conversations count: " . count($formattedConversations));
            error_log("API: Response JSON length: " . strlen(json_encode($response)));
            
            echo json_encode($response);
            break;
            
        case 'get_messages':
            // Get messages for a conversation
            $conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
            
            if ($conversationId <= 0) {
                jsonError('Invalid conversation ID', 400);
            }
            
            // Verify user belongs to conversation
            if (!$conversationService->userBelongsToConversation($conversationId, $userId)) {
                jsonError('Unauthorized', 403);
            }
            
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            $messages = $messageService->getMessages($conversationId, $userId, $limit, $offset);
            
            // Format messages with avatar URLs
            $formattedMessages = array_map(function($msg) use ($baseUrl) {
                $avatar = $baseUrl . '/view/frontoffice/assets/images/default-avatar.png';
                if (!empty($msg['sender_avatar'])) {
                    $avatar = $baseUrl . '/uploads/profile_pictures/' . basename($msg['sender_avatar']);
                }
                
                return [
                    'id' => $msg['id'],
                    'conversation_id' => $msg['conversation_id'],
                    'sender_id' => $msg['sender_id'],
                    'sender_name' => $msg['sender_name'],
                    'sender_avatar' => $avatar,
                    'message' => $msg['message'],
                    'is_read' => $msg['is_read'],
                    'is_sender' => $msg['is_sender'],
                    'created_at' => $msg['created_at']
                ];
            }, $messages);
            
            // Mark messages as read
            $messageService->markAsRead($conversationId, $userId);
            
            echo json_encode([
                'success' => true,
                'messages' => $formattedMessages
            ]);
            break;
            
        case 'send_message':
            // Send a new message
            if ($method !== 'POST') {
                jsonError('Method not allowed', 405);
            }
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                jsonError('Invalid JSON: ' . json_last_error_msg(), 400);
            }
            
            $conversationId = isset($data['conversation_id']) ? (int)$data['conversation_id'] : 0;
            $messageText = isset($data['message']) ? trim($data['message']) : '';
            
            if ($conversationId <= 0) {
                jsonError('Invalid conversation ID', 400);
            }
            
            if (empty($messageText)) {
                jsonError('Message cannot be empty', 400);
            }
            
            // Debug: Log conversation check
            error_log("Send message - User ID: $userId, Conversation ID: $conversationId");
            
            // Verify user belongs to conversation
            $belongsToConversation = $conversationService->userBelongsToConversation($conversationId, $userId);
            error_log("User belongs to conversation: " . ($belongsToConversation ? 'true' : 'false'));
            
            if (!$belongsToConversation) {
                // Get conversation details for debugging
                $conversation = $conversationService->getConversationById($conversationId);
                if ($conversation) {
                    error_log("Conversation details - user_one_id: {$conversation['user_one_id']}, user_two_id: {$conversation['user_two_id']}, is_blocked: {$conversation['is_blocked']}");
                } else {
                    error_log("Conversation not found: $conversationId");
                }
                jsonError('Unauthorized: You do not have access to this conversation', 403);
            }
            
            $result = $messageService->sendMessage($conversationId, $userId, $messageText);
            
            if ($result === false) {
                jsonError('Failed to send message', 500);
            }
            
            if (isset($result['error'])) {
                echo json_encode([
                    'success' => false,
                    'error' => $result['error']
                ]);
                exit;
            }
            
            // Get other user ID for notification
            $otherUserId = $conversationService->getOtherUserId($conversationId, $userId);
            
            // Send notification
            if ($otherUserId) {
                try {
                    require_once __DIR__ . '/../../services/NotificationService.php';
                    $notificationService = new NotificationService($pdo);
                    $currentUser = $pdo->query("SELECT firstname, lastname FROM users WHERE cin = $userId")->fetch(PDO::FETCH_ASSOC);
                    $senderName = trim(($currentUser['firstname'] ?? '') . ' ' . ($currentUser['lastname'] ?? '')) ?: 'Quelqu\'un';
                    $notificationService->notifyNewMessage($conversationId, $userId, $otherUserId, $senderName);
                } catch (Exception $e) {
                    error_log("Error sending message notification: " . $e->getMessage());
                    // Don't fail the message if notification fails
                }
            }
            
            // Format message response
            $avatar = $baseUrl . '/view/frontoffice/assets/images/default-avatar.png';
            $currentUser = $pdo->query("SELECT firstname, lastname, profile_picture FROM users WHERE cin = $userId")->fetch(PDO::FETCH_ASSOC);
            if ($currentUser && !empty($currentUser['profile_picture'])) {
                $avatar = $baseUrl . '/uploads/profile_pictures/' . basename($currentUser['profile_picture']);
            }
            $senderName = trim(($currentUser['firstname'] ?? '') . ' ' . ($currentUser['lastname'] ?? '')) ?: 'Utilisateur';
            
            echo json_encode([
                'success' => true,
                'message' => [
                    'id' => (int)$result['id'],
                    'conversation_id' => (int)$result['conversation_id'],
                    'sender_id' => (int)$result['sender_id'],
                    'sender_name' => $senderName,
                    'sender_avatar' => $avatar,
                    'message' => $result['message'],
                    'is_read' => false,
                    'is_sender' => true,
                    'created_at' => $result['created_at']
                ]
            ]);
            break;
            
        case 'create_conversation':
            // Create a new conversation
            if ($method !== 'POST') {
                jsonError('Method not allowed', 405);
            }
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                jsonError('Invalid JSON: ' . json_last_error_msg(), 400);
            }
            
            $otherUserId = isset($data['other_user_id']) ? (int)$data['other_user_id'] : 0;
            $entityType = isset($data['entity_type']) ? trim($data['entity_type']) : null;
            $entityId = isset($data['entity_id']) ? (int)$data['entity_id'] : null;
            
            if ($otherUserId <= 0) {
                jsonError('Invalid user ID', 400);
            }
            
            // Check if user can initiate conversation
            if (!$messageService->canInitiateConversation($userId, $otherUserId, $entityType, $entityId)) {
                jsonError('Cannot initiate conversation', 403);
            }
            
            $conversation = $conversationService->createOrGetConversation(
                $userId,
                $otherUserId,
                $entityType,
                $entityId
            );
            
            if ($conversation === false) {
                jsonError('Failed to create conversation', 500);
            }
            
            // Get other user info
            $otherUser = $pdo->query("SELECT firstname, lastname, profile_picture FROM users WHERE cin = $otherUserId")->fetch(PDO::FETCH_ASSOC);
            $otherUserName = trim(($otherUser['firstname'] ?? '') . ' ' . ($otherUser['lastname'] ?? '')) ?: 'Utilisateur';
            $otherUserAvatar = $baseUrl . '/view/frontoffice/assets/images/default-avatar.png';
            if ($otherUser && !empty($otherUser['profile_picture'])) {
                $otherUserAvatar = $baseUrl . '/uploads/profile_pictures/' . basename($otherUser['profile_picture']);
            }
            
            echo json_encode([
                'success' => true,
                'conversation' => [
                    'id' => (int)$conversation['id'],
                    'other_user_id' => $otherUserId,
                    'other_user_name' => $otherUserName,
                    'other_user_avatar' => $otherUserAvatar,
                    'related_entity_type' => $conversation['related_entity_type'],
                    'related_entity_id' => $conversation['related_entity_id'] ? (int)$conversation['related_entity_id'] : null
                ]
            ]);
            break;
            
        case 'mark_read':
            // Mark messages as read
            $conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
            
            if ($conversationId <= 0) {
                jsonError('Invalid conversation ID', 400);
            }
            
            if (!$conversationService->userBelongsToConversation($conversationId, $userId)) {
                jsonError('Unauthorized', 403);
            }
            
            $result = $messageService->markAsRead($conversationId, $userId);
            
            echo json_encode([
                'success' => $result
            ]);
            break;
            
        case 'get_unread_count':
            // Get unread message count
            $count = $messageService->getUnreadCount($userId);
            
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
            break;
            
        case 'get_conversation_context':
            // Get conversation context (entity info)
            $conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
            
            if ($conversationId <= 0) {
                jsonError('Invalid conversation ID', 400);
            }
            
            if (!$conversationService->userBelongsToConversation($conversationId, $userId)) {
                jsonError('Unauthorized', 403);
            }
            
            $conversation = $conversationService->getConversationById($conversationId);
            if (!$conversation) {
                jsonError('Conversation not found', 404);
            }
            
            $context = [
                'entity_type' => $conversation['related_entity_type'],
                'entity_id' => $conversation['related_entity_id']
            ];
            
            // Fetch entity details based on type
            if ($conversation['related_entity_type'] && $conversation['related_entity_id']) {
                try {
                    if ($conversation['related_entity_type'] === 'post') {
                        $postModel = new Post($pdo);
                        $post = $postModel->find($conversation['related_entity_id']);
                        if ($post) {
                            $context['entity_title'] = $post['titre'] ?? 'Post';
                            $context['entity_link'] = $baseUrl . '/view/frontoffice/posts.php?post_id=' . $conversation['related_entity_id'];
                        }
                    } elseif ($conversation['related_entity_type'] === 'donation' || $conversation['related_entity_type'] === 'request') {
                        // Try donor_requests table
                        $stmt = $pdo->prepare("SELECT id, description, category FROM donor_requests WHERE id = ?");
                        $stmt->execute([$conversation['related_entity_id']]);
                        $request = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($request) {
                            $context['entity_title'] = $request['description'] ? substr($request['description'], 0, 50) . '...' : 'Demande de don';
                            $context['entity_link'] = $baseUrl . '/index.php?action=donation_request&id=' . $conversation['related_entity_id'];
                        } else {
                            // Try donor_offers table
                            $stmt = $pdo->prepare("SELECT id, title, description FROM donor_offers WHERE id = ?");
                            $stmt->execute([$conversation['related_entity_id']]);
                            $offer = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($offer) {
                                $context['entity_title'] = $offer['title'] ?? 'Offre de don';
                                $context['entity_link'] = $baseUrl . '/index.php?action=donation_offer&id=' . $conversation['related_entity_id'];
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error fetching conversation context: " . $e->getMessage());
                }
            }
            
            echo json_encode([
                'success' => true,
                'context' => $context
            ]);
            break;
            
        default:
            jsonError('Invalid action', 400);
    }
} catch (Exception $e) {
    error_log("API Error in action handler: " . $e->getMessage());
    jsonError('Server error: ' . $e->getMessage(), 500);
}
exit;

