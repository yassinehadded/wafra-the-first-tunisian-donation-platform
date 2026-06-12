<?php
// User profile API – returns user profile data for modal display
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../config/autoload.php';
require_once __DIR__ . '/../../models/User.php';

// Guard: only allow authenticated admin sessions
if (empty($_SESSION['userID']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'debug' => ['session_userID' => $_SESSION['userID'] ?? null, 'session_role' => $_SESSION['role'] ?? null]]);
    exit;
}

if (empty($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID is required']);
    exit;
}

$userId = (int)$_GET['user_id'];

try {
    $pdo = Database::connect();
    $userModel = new User($pdo);
    
    // Try getUserByCin first, if not available, use direct query
    if (method_exists($userModel, 'getUserByCin')) {
        $userData = $userModel->getUserByCin($userId);
    } else {
        // Fallback: direct query
        $stmt = $pdo->prepare("SELECT * FROM users WHERE cin = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$userData || empty($userData)) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found', 'debug' => ['user_id' => $userId]]);
        exit;
    }
    
    // Get profile picture path
    $profilePicture = null;
    if (!empty($userData['profile_picture'])) {
        $picturePath = $userData['profile_picture'];
        
        // If it's already a full URL, use it directly
        if (filter_var($picturePath, FILTER_VALIDATE_URL)) {
            $profilePicture = $picturePath;
        } 
        // If it starts with /, it's an absolute path from web root
        elseif (strpos($picturePath, '/') === 0) {
            $profilePicture = BASE_URL . $picturePath;
        }
        // If it's a relative path, try different locations
        else {
            // Try uploads/profile_pictures/ first
            $pictureFile = __DIR__ . '/../../uploads/profile_pictures/' . basename($picturePath);
            if (file_exists($pictureFile) && is_file($pictureFile)) {
                $profilePicture = BASE_URL . '/uploads/profile_pictures/' . basename($picturePath);
            }
            // Try view/frontoffice/assets/images/
            else {
                $pictureFile = __DIR__ . '/../../view/frontoffice/assets/images/' . basename($picturePath);
                if (file_exists($pictureFile) && is_file($pictureFile)) {
                    $profilePicture = BASE_URL . '/view/frontoffice/assets/images/' . basename($picturePath);
                }
                // Try the path as stored in database
                else {
                    $pictureFile = __DIR__ . '/../../' . $picturePath;
                    if (file_exists($pictureFile) && is_file($pictureFile)) {
                        $profilePicture = BASE_URL . '/' . $picturePath;
                    }
                }
            }
        }
    }
    
    // If no profile picture found, use default
    if (empty($profilePicture)) {
        $profilePicture = BASE_URL . '/view/frontoffice/assets/images/default-avatar.png';
    }
    
    // Debug logging (can be removed in production)
    error_log("User profile API - User ID: $userId, Profile picture: " . ($profilePicture ?? 'null'));
    
    echo json_encode([
        'cin' => $userData['cin'] ?? null,
        'firstname' => $userData['firstname'] ?? '',
        'lastname' => $userData['lastname'] ?? '',
        'email' => $userData['email'] ?? '',
        'role' => $userData['role'] ?? 'user',
        'email_verified' => (bool)($userData['email_verified'] ?? 0),
        'profile_picture' => $profilePicture,
        'created_at' => $userData['created_at'] ?? null,
    ]);
} catch (Throwable $e) {
    error_log('[user_profile.php] ' . $e->getMessage());
    error_log('[user_profile.php] Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => 'Unable to fetch user profile',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

