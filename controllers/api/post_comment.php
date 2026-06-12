<?php
/**
 * Post Comment API Endpoint
 * Handles comment operations via JSON API
 */
// Disable error display and clean output buffers
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Clean any existing output buffers to ensure clean JSON output
while (ob_get_level()) {
    ob_end_clean();
}

session_start();
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
    require_once __DIR__ . '/../../services/PostCommentService.php';
    require_once __DIR__ . '/../../services/NotificationService.php';
    require_once __DIR__ . '/../../models/Post.php';
} catch (Exception $e) {
    error_log("API Error loading files: " . $e->getMessage());
    jsonError('Server configuration error', 500);
}

if (!isset($_SESSION['userID'])) {
    jsonError('User not logged in', 401);
}

try {
    $pdo = Database::connect();
    if (!$pdo) {
        jsonError('Database connection failed', 500);
    }
    $commentService = new PostCommentService($pdo);
    $id_user = (int)$_SESSION['userID'];
} catch (Exception $e) {
    error_log("API Error initializing: " . $e->getMessage());
    jsonError('Server initialization error', 500);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = file_get_contents('php://input');
        if (empty($input)) {
            jsonError('No data received', 400);
        }
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            jsonError('Invalid JSON: ' . json_last_error_msg(), 400);
        }
        $action = $data['action'] ?? '';
        
        if (empty($action)) {
            jsonError('Action is required', 400);
        }
    } catch (Exception $e) {
        error_log("API Error parsing input: " . $e->getMessage());
        jsonError('Error parsing request', 400);
    }
    
    try {
        switch ($action) {
        case 'add':
            $id_post = isset($data['id_post']) ? (int)$data['id_post'] : 0;
            $comment_text = isset($data['comment_text']) ? trim($data['comment_text']) : '';
            
            if ($id_post <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid post ID']);
                exit;
            }
            
            if (empty($comment_text)) {
                echo json_encode(['success' => false, 'error' => 'Comment cannot be empty']);
                exit;
            }
            
            $comment_id = $commentService->addComment($id_post, $id_user, $comment_text);
            if ($comment_id !== false) {
                // Get the comment with user info
                $comments = $commentService->getCommentsByPost($id_post);
                $new_comment = null;
                foreach ($comments as $comment) {
                    if ($comment['id_comment'] == $comment_id) {
                        $new_comment = $comment;
                        break;
                    }
                }
                
                // Send notification to post owner
                try {
                    $postModel = new Post($pdo);
                    $post = $postModel->find($id_post);
                    if ($post && isset($post['id_user'])) {
                        $notificationService = new NotificationService($pdo);
                        $notificationService->notifyPostCommented($id_post, $id_user, (int)$post['id_user']);
                        
                        // Check for milestones
                        $notificationService->checkPostMilestones($id_post, (int)$post['id_user']);
                    }
                } catch (Exception $e) {
                    error_log("Error sending comment notification: " . $e->getMessage());
                    // Don't fail the comment operation if notification fails
                }
                
                echo json_encode([
                    'success' => true,
                    'comment' => $new_comment,
                    'count' => count($comments)
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error adding comment']);
            }
            break;
            
        case 'get':
            $id_post = isset($data['id_post']) ? (int)$data['id_post'] : 0;
            
            if ($id_post <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid post ID']);
                exit;
            }
            
            $comments = $commentService->getCommentsByPost($id_post);
            $count = $commentService->getCommentCount($id_post);
            
            echo json_encode([
                'success' => true,
                'comments' => $comments,
                'count' => $count
            ]);
            break;
            
        case 'get_comment':
            $id_comment = isset($data['id_comment']) ? (int)$data['id_comment'] : 0;
            
            if ($id_comment <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid comment ID']);
                exit;
            }
            
            // Get comment with post_id
            $comment = $commentService->getCommentById($id_comment);
            if ($comment && isset($comment['id_post'])) {
                echo json_encode([
                    'success' => true,
                    'comment' => $comment
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Comment not found']);
            }
            break;
            
        case 'delete':
            $id_comment = isset($data['id_comment']) ? (int)$data['id_comment'] : 0;
            
            if ($id_comment <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid comment ID']);
                exit;
            }
            
            $result = $commentService->deleteComment($id_comment, $id_user);
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error deleting comment']);
            }
            break;
            
        default:
            jsonError('Invalid action', 400);
        }
    } catch (Exception $e) {
        error_log("API Error in action handler: " . $e->getMessage());
        jsonError('Server error: ' . $e->getMessage(), 500);
    }
} else {
    jsonError('Method not allowed', 405);
}
exit;


