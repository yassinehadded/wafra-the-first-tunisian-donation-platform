<?php
// User statistics API – returns aggregated counts for dashboard cards
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

// Guard: only allow authenticated admin sessions
if (empty($_SESSION['userID']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = Database::connect();

function fetchCount($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

try {
    // 1. Totals and activity windows
    $totalUsers = fetchCount($pdo, "SELECT COUNT(*) FROM users");

    $activeUsers = fetchCount(
        $pdo,
        "SELECT COUNT(DISTINCT userID) FROM loginsession WHERE loginTime >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );

    // Users with no login in the last 30 days
    $inactiveUsers = fetchCount(
        $pdo,
        "SELECT COUNT(*) FROM users u WHERE u.cin NOT IN (
            SELECT DISTINCT userID FROM loginsession WHERE loginTime >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        )"
    );

    $newUsersToday = fetchCount(
        $pdo,
        "SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()"
    );

    $newUsersMonth = fetchCount(
        $pdo,
        "SELECT COUNT(*) FROM users WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
    );

    // 2. Login attempts (count successful sessions)
    $successCount = fetchCount($pdo, "SELECT COUNT(*) FROM loginsession");
    $loginAttempts = [
        'success' => $successCount,
        'failed' => 0,
        'total' => $successCount,
    ];

    // 3. Users considered "online" (logged in, no logout, and recent activity)
    $usersOnline = fetchCount(
        $pdo,
        "SELECT COUNT(*) FROM loginsession WHERE logoutTime IS NULL AND loginTime >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)"
    );

    echo json_encode([
        'total_users'     => $totalUsers,
        'active_users'    => $activeUsers,
        'inactive_users'  => $inactiveUsers,
        'new_users_today' => $newUsersToday,
        'new_users_month' => $newUsersMonth,
        'login_attempts'  => $loginAttempts,
        'users_online'    => $usersOnline,
        'generated_at'    => date(DATE_ATOM),
    ]);
} catch (Throwable $e) {
    error_log('[user_stats.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Unable to fetch user statistics']);
}
