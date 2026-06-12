<?php
/**
 * API Endpoint: Search Events
 * Returns JSON response with filtered events
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Clean output buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Check if user is logged in
if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

// Load dependencies
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/Event.php';

try {
    $pdo = Database::connect();
    $eventModel = new Event($pdo);
    
    // Get search parameters
    $searchTerm = trim($_GET['search'] ?? '');
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? max(1, min(100, (int)$_GET['per_page'])) : 10;
    
    // Get events
    $eventsResult = $eventModel->getAll($page, $perPage, $searchTerm);
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'data' => $eventsResult['data'] ?? [],
        'total' => $eventsResult['total'] ?? 0,
        'page' => $eventsResult['page'] ?? $page,
        'perPage' => $eventsResult['perPage'] ?? $perPage,
        'totalPages' => $eventsResult['totalPages'] ?? 1,
        'searchTerm' => $searchTerm
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    error_log("Error in search_events.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while searching events'
    ]);
}

exit;

















