<?php
/**
 * Comment Report API Endpoint
 * Handles comment reporting operations via JSON API
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
    require_once __DIR__ . '/../../models/CommentReport.php';
    require_once __DIR__ . '/../../services/PostCommentService.php';
    require_once __DIR__ . '/../../services/NotificationService.php';
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
    $reportModel = new CommentReport($pdo);
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
        $id_comment = isset($data['id_comment']) ? (int)$data['id_comment'] : 0;
        
        if ($id_comment <= 0) {
            jsonError('Invalid comment ID', 400);
        }
        
        if (empty($action)) {
            jsonError('Action is required', 400);
        }
    } catch (Exception $e) {
        error_log("API Error parsing input: " . $e->getMessage());
        jsonError('Error parsing request', 400);
    }
    
    try {
        switch ($action) {
        case 'report':
            $reason = isset($data['reason']) ? trim($data['reason']) : 'other';
            $description = isset($data['description']) ? trim($data['description']) : '';
            
            // Check if comment exists
            $comment = $commentService->getCommentById($id_comment);
            if (!$comment) {
                jsonError('Comment not found', 404);
            }
            
            // Check if already reported
            if ($reportModel->hasAlreadyReported($id_comment, $id_user)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'You have already reported this comment',
                    'alreadyReported' => true
                ]);
                break;
            }
            
            $result = $reportModel->create($id_comment, $id_user, $reason, $description);
            
            if (is_array($result)) {
                if ($result['success']) {
                    $count = $reportModel->getReportsByComment($id_comment);
                    
                    // Notify admins
                    try {
                        $notificationService = new NotificationService($pdo);
                        $notificationService->notifyAdminCommentReported($id_comment, $id_user);
                    } catch (Exception $e) {
                        error_log("Error sending admin notification: " . $e->getMessage());
                        // Don't fail the report operation if notification fails
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Comment reported successfully',
                        'count' => count($count)
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => $result['error'] ?? 'Error reporting comment'
                    ]);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Error reporting comment']);
            }
            break;
            
        case 'check':
            $isReported = $reportModel->hasAlreadyReported($id_comment, $id_user);
            $reports = $reportModel->getReportsByComment($id_comment);
            $count = count($reports);
            echo json_encode([
                'success' => true,
                'isReported' => $isReported,
                'count' => $count
            ]);
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

