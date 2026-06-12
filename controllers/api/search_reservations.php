<?php
/**
 * API Endpoint: Search Reservations
 * Returns JSON response with filtered reservations for the logged-in user
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
require_once __DIR__ . '/../../models/Reservation.php';

try {
    $pdo = Database::connect();
    $reservationModel = new Reservation($pdo);
    
    $userId = (int)$_SESSION['userID'];
    
    // Get search parameters
    $searchTerm = trim($_GET['search'] ?? '');
    
    // Get reservations for this user with search
    $reservations = $reservationModel->selectAllWithEvent($userId, $searchTerm);
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'data' => $reservations ?? [],
        'total' => count($reservations ?? []),
        'searchTerm' => $searchTerm
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    error_log("Error in search_reservations.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while searching reservations'
    ]);
}

exit;

















