<?php
/**
 * Post Like API Endpoint
 * Handles like operations via JSON API
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
    require_once __DIR__ . '/../../services/PostLikeService.php';
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
    $likeService = new PostLikeService($pdo);
    $id_user = (int)$_SESSION['userID'];
    
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
        $id_post = isset($data['id_post']) ? (int)$data['id_post'] : 0;
        
        if ($id_post <= 0) {
            jsonError('Invalid post ID', 400);
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
        case 'toggle':
            $result = $likeService->toggleLike($id_post, $id_user);
            if ($result !== false) {
                $isLiked = ($result === 'added');
                $count = $likeService->getLikeCount($id_post);
                
                // Send notification if like was added
                if ($isLiked) {
                    try {
                        $postModel = new Post($pdo);
                        $post = $postModel->find($id_post);
                        if ($post && isset($post['id_user'])) {
                            $notificationService = new NotificationService($pdo);
                            $notificationService->notifyPostLiked($id_post, $id_user, (int)$post['id_user']);
                            
                            // Check for milestones
                            $notificationService->checkPostMilestones($id_post, (int)$post['id_user']);
                        }
                    } catch (Exception $e) {
                        error_log("Error sending like notification: " . $e->getMessage());
                        // Don't fail the like operation if notification fails
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'isLiked' => $isLiked,
                    'count' => $count
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error toggling like']);
            }
            break;
            
        case 'get':
            $isLiked = $likeService->isLiked($id_post, $id_user);
            $count = $likeService->getLikeCount($id_post);
            echo json_encode([
                'success' => true,
                'isLiked' => $isLiked,
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
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Handle GET request for getting users who liked a post
    try {
        $id_post = isset($_GET['id_post']) ? (int)$_GET['id_post'] : 0;
        $action = $_GET['action'] ?? 'get_likers';
        
        if ($id_post <= 0) {
            jsonError('Invalid post ID', 400);
        }
        
        if ($action !== 'get_likers') {
            jsonError('Invalid action', 400);
        }
        
        // Get pagination parameters
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        // Validate pagination
        if ($limit < 1 || $limit > 100) {
            $limit = 20;
        }
        if ($offset < 0) {
            $offset = 0;
        }
        
        // Check if post exists
        $postModel = new Post($pdo);
        $post = $postModel->find($id_post);
        if (!$post) {
            jsonError('Post not found', 404);
        }
        
        // Security check: Only authenticated users can see who liked
        // Post owner and admin can see full list, others get count only
        $isPostOwner = isset($post['id_user']) && (int)$post['id_user'] === $id_user;
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        $canSeeFullList = $isPostOwner || $isAdmin;
        
        // Get total count
        $totalCount = $likeService->getUsersWhoLikedCount($id_post);
        
        if ($canSeeFullList) {
            // Return full list with user details
            $users = $likeService->getUsersWhoLiked($id_post, $limit, $offset);
            
            // Format user data (don't expose email or private data)
            $formattedUsers = array_map(function($user) use ($baseUrl) {
                $fullName = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
                $displayName = $fullName ?: 'Utilisateur';
                
                // Get avatar URL
                $avatar = $baseUrl . '/view/frontoffice/assets/images/default-avatar.png';
                if (!empty($user['profile_picture'])) {
                    $avatar = $baseUrl . '/uploads/profile_pictures/' . basename($user['profile_picture']);
                }
                
                return [
                    'id' => (int)$user['id'],
                    'name' => $displayName,
                    'avatar' => $avatar,
                    'date_like' => $user['date_like'] ?? null
                ];
            }, $users);
            
            echo json_encode([
                'success' => true,
                'users' => $formattedUsers,
                'total' => $totalCount,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + count($formattedUsers)) < $totalCount
            ]);
        } else {
            // Return count only for non-owners/non-admins
            echo json_encode([
                'success' => true,
                'users' => [],
                'total' => $totalCount,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => false,
                'message' => 'Only post owner and admins can see who liked this post'
            ]);
        }
    } catch (Exception $e) {
        error_log("API Error in GET handler: " . $e->getMessage());
        jsonError('Server error: ' . $e->getMessage(), 500);
    }
} else {
    jsonError('Method not allowed', 405);
}
exit;


