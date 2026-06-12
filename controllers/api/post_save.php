<?php
/**
 * Post Save API Endpoint
 * Handles post saving/bookmarking operations via JSON API
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
    require_once __DIR__ . '/../../services/PostSaveService.php';
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
    $saveService = new PostSaveService($pdo);
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
            $result = $saveService->toggleSave($id_post, $id_user);
            if ($result !== false) {
                $isSaved = ($result === 'added');
                echo json_encode([
                    'success' => true,
                    'isSaved' => $isSaved
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error toggling save']);
            }
            break;
            
        case 'check':
            $isSaved = $saveService->isSaved($id_post, $id_user);
            echo json_encode([
                'success' => true,
                'isSaved' => $isSaved
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

