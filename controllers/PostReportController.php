<?php
/**
 * Post Report Controller
 * Handles post reporting operations following MVC pattern
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../models/PostReport.php';
require_once __DIR__ . '/../models/Post.php';

class PostReportController {
    private $pdo;
    private $reportModel;
    private $postModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->pdo = Database::connect();
        $this->reportModel = new PostReport($this->pdo);
        $this->postModel = new Post($this->pdo);
    }

    /**
     * Handle report submission (for AJAX)
     */
    public function report() {
        header('Content-Type: application/json; charset=utf-8');
        
        // Clean output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Check if user is logged in
        if (!isset($_SESSION['userID'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'User not logged in']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        try {
            $input = file_get_contents('php://input');
            if (empty($input)) {
                throw new Exception('No data received');
            }
            
            $data = json_decode($input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON: ' . json_last_error_msg());
            }

            $postId = isset($data['id_post']) ? (int)$data['id_post'] : 0;
            $reason = isset($data['reason']) ? trim($data['reason']) : 'other';
            $description = isset($data['description']) ? trim($data['description']) : '';

            // Validate post ID
            if ($postId <= 0) {
                throw new Exception('Invalid post ID');
            }

            // Check if post exists
            $post = $this->postModel->find($postId);
            if (!$post) {
                throw new Exception('Post not found');
            }

            // Check if user has already reported this post
            $userId = (int)$_SESSION['userID'];
            if ($this->reportModel->hasAlreadyReported($postId, $userId)) {
                http_response_code(409);
                echo json_encode([
                    'success' => false, 
                    'error' => 'You have already reported this post',
                    'alreadyReported' => true
                ]);
                exit;
            }

            // Validate reason
            $validReasons = ['spam', 'harassment', 'hate_speech', 'fake_information', 'inappropriate_content', 'other'];
            if (!in_array($reason, $validReasons)) {
                $reason = 'other';
            }

            // Sanitize description
            $description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
            if (strlen($description) > 1000) {
                $description = substr($description, 0, 1000);
            }

            // Create report
            $reportId = $this->reportModel->create($postId, $userId, $reason, $description);
            
            if ($reportId) {
                // Get report count for this post
                $reports = $this->reportModel->getReportsByPost($postId);
                $count = count($reports);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Post reported successfully',
                    'report_id' => $reportId,
                    'count' => $count
                ]);
            } else {
                throw new Exception('Failed to create report');
            }
        } catch (Exception $e) {
            error_log("PostReportController error: " . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Check if user has reported a post
     */
    public function check() {
        header('Content-Type: application/json; charset=utf-8');
        
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        if (!isset($_SESSION['userID'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'User not logged in']);
            exit;
        }

        $postId = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
        if ($postId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid post ID']);
            exit;
        }

        $userId = (int)$_SESSION['userID'];
        $isReported = $this->reportModel->hasAlreadyReported($postId, $userId);
        $reports = $this->reportModel->getReportsByPost($postId);
        $count = count($reports);

        echo json_encode([
            'success' => true,
            'isReported' => $isReported,
            'count' => $count
        ]);
        exit;
    }
}





