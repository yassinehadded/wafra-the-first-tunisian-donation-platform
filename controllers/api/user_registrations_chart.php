<?php
// Registration counts per month for the last 12 months
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
    // Fetch actual counts
    $sql = "
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS registrations
        FROM users
        WHERE created_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 11 MONTH)
        GROUP BY month
        ORDER BY month
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build a full 12-month series (including months with 0)
    $series = [];
    $monthCursor = new DateTime('first day of this month');
    $monthCursor->modify('-11 months');
    $rowIndex = [];
    foreach ($rows as $row) {
        $rowIndex[$row['month']] = (int)$row['registrations'];
    }
    for ($i = 0; $i < 12; $i++) {
        $key = $monthCursor->format('Y-m');
        $series[] = [
            'month' => $key,
            'registrations' => $rowIndex[$key] ?? 0,
        ];
        $monthCursor->modify('+1 month');
    }

    echo json_encode($series);
} catch (Throwable $e) {
    error_log('[user_registrations_chart.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Unable to fetch registration chart data']);
}
