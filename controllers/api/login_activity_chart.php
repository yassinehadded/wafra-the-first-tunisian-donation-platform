<?php
// Login activity per day for the last 30 days
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

if (empty($_SESSION['userID']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = Database::connect();

try {
    // Count successful login sessions
    $sql = "
        SELECT DATE(loginTime) AS day,
               COUNT(*) AS success_count,
               0 AS failed_count
        FROM loginsession
        WHERE loginTime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(loginTime)
        ORDER BY day
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build a full 30-day series (including days with 0)
    $series = [];
    $rowIndex = [];
    foreach ($rows as $row) {
        $rowIndex[$row['day']] = [
            'success_count' => (int)$row['success_count'],
            'failed_count' => (int)$row['failed_count'],
        ];
    }

    $dayCursor = new DateTime('-29 days'); // include today
    for ($i = 0; $i < 30; $i++) {
        $key = $dayCursor->format('Y-m-d');
        $series[] = [
            'day' => $key,
            'success_count' => $rowIndex[$key]['success_count'] ?? 0,
            'failed_count' => $rowIndex[$key]['failed_count'] ?? 0,
        ];
        $dayCursor->modify('+1 day');
    }

    echo json_encode($series);
} catch (Throwable $e) {
    error_log('[login_activity_chart.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Unable to fetch login activity data']);
}
