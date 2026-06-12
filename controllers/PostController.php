<?php
/**
 * Post Controller
 * Handles post-related operations
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../models/Post.php';

class PostController {
    private $pdo;
    private $postModel;
    private $userModel;

    public function __construct() {
        $this->pdo = Database::connect();
        $this->postModel = new Post($this->pdo);
        $this->userModel = new User($this->pdo);
    }

    /**
     * List all posts (frontoffice)
     */
    public function listFront() {
        session_start();

        if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
            header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
            exit;
        }

        $posts = $this->postModel->getAll();
        
        // Store in session for view
        $_SESSION['posts_data'] = $posts;
        
        header('Location: ' . BASE_URL . '/view/frontoffice/posts.php');
        exit;
    }

    /**
     * Show post details
     */
    public function show($id) {
        session_start();

        if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
            header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
            exit;
        }

        $post = $this->postModel->find($id);
        
        if (!$post) {
            header('Location: ' . BASE_URL . '/view/frontoffice/forum.php?error=post_not_found');
            exit;
        }

        $_SESSION['post_details'] = $post;
        header('Location: ' . BASE_URL . '/view/frontoffice/forum.php?post_id=' . $id);
        exit;
    }

    /**
     * Create a new post
     */
    public function create() {
        session_start();
        error_log('PostController: Starting post creation');

        if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
            error_log('PostController: User not authenticated or invalid role');
            header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log('PostController: Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
            header('Location: ' . BASE_URL . '/view/frontoffice/forum.php?error=invalid_method');
            exit;
        }

        $userId = (int)$_SESSION['userID'];
        error_log('PostController: User ID: ' . $userId);
        
        $user = $this->getUserInfo($userId);
        error_log('PostController: User info: ' . print_r($user, true));

        // Handle file upload
        $media = '';
        if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
            error_log('PostController: Handling file upload');
            $uploadDir = __DIR__ . '/../uploads/posts/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    error_log('PostController: Failed to create upload directory: ' . $uploadDir);
                } else {
                    error_log('PostController: Created upload directory: ' . $uploadDir);
                }
            }
            
            $fileExtension = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
            $fileName = time() . '_' . uniqid() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            error_log('PostController: Attempting to move uploaded file to: ' . $filePath);
            if (move_uploaded_file($_FILES['media']['tmp_name'], $filePath)) {
                $media = 'uploads/posts/' . $fileName;
                error_log('PostController: File uploaded successfully: ' . $media);
            } else {
                error_log('PostController: Failed to move uploaded file. Error: ' . $_FILES['media']['error']);
                error_log('PostController: Upload details: ' . print_r($_FILES['media'], true));
            }
        } else if (isset($_FILES['media'])) {
            error_log('PostController: File upload error: ' . $_FILES['media']['error']);
        }

        try {
            $postData = [
                'id_user' => $userId,
                'nom' => $user['firstname'] . ' ' . $user['lastname'],
                'numero' => $_POST['numero'] ?? '',
                'email' => $user['email'] ?? $_POST['email'] ?? '',
                'titre' => $_POST['titre'] ?? '',
                'region' => $_POST['region'] ?? '',
                'description' => $_POST['description'] ?? '',
                'media' => $media,
                'date_creation' => date('Y-m-d')
            ];

            error_log('PostController: Post data prepared: ' . print_r($postData, true));
            
            // Log database connection status
            error_log('PostController: Database connection status: ' . ($this->pdo ? 'Connected' : 'Not connected'));
            
            // Test database connection
            try {
                $test = $this->pdo->query('SELECT 1')->fetch();
                error_log('PostController: Database test query successful');
            } catch (PDOException $e) {
                error_log('PostController: Database test query failed: ' . $e->getMessage());
            }

            $result = $this->postModel->create($postData);
            error_log('PostController: Post creation result: ' . ($result ? 'Success' : 'Failed'));
            
            if ($result) {
                error_log('PostController: Redirecting to forum list with success');
                header('Location: ' . BASE_URL . '/index.php?action=forum_list&success=post_created');
            } else {
                error_log('PostController: Post creation failed, checking for PDO error');
                $errorInfo = $this->pdo->errorInfo();
                error_log('PostController: PDO error info: ' . print_r($errorInfo, true));
                header('Location: ' . BASE_URL . '/view/frontoffice/forum.php?error=post_creation_failed&details=' . urlencode($errorInfo[2] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            error_log('PostController: Exception during post creation: ' . $e->getMessage());
            error_log('PostController: Stack trace: ' . $e->getTraceAsString());
            header('Location: ' . BASE_URL . '/view/frontoffice/forum.php?error=post_creation_failed&exception=' . urlencode($e->getMessage()));
        }
        exit;
    }

    /**
     * Update a post
     */
    public function update() {
        session_start();

        if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
            header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/view/frontoffice/forum.php?error=invalid_method');
            exit;
        }

        $postId = isset($_POST['id_post']) ? (int)$_POST['id_post'] : 0;
        $userId = (int)$_SESSION['userID'];
        $user = $this->getUserInfo($userId);

        if ($postId <= 0) {
            header('Location: ' . BASE_URL . '/view/frontoffice/forum.php?error=invalid_post_id');
            exit;
        }

        // Handle file upload
        $media = null;
        if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/posts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
            $fileName = time() . '_' . uniqid() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['media']['tmp_name'], $filePath)) {
                $media = 'uploads/posts/' . $fileName;
            }
        }

        $postData = [
            'nom' => $user['firstname'] . ' ' . $user['lastname'],
            'numero' => $_POST['numero'] ?? '',
            'email' => $user['email'] ?? $_POST['email'] ?? '',
            'titre' => $_POST['titre'] ?? '',
            'region' => $_POST['region'] ?? '',
            'description' => $_POST['description'] ?? '',
            'date_creation' => $_POST['date_creation'] ?? date('Y-m-d')
        ];

        if ($media !== null) {
            $postData['media'] = $media;
        }

        $result = $this->postModel->update($postId, $postData, $userId);
        
        if ($result) {
            header('Location: ' . BASE_URL . '/index.php?action=forum_list&success=post_updated');
        } else {
            header('Location: ' . BASE_URL . '/view/frontoffice/forum.php?error=post_update_failed');
        }
        exit;
    }

    /**
     * Delete a post
     */
    public function delete($id) {
        session_start();

        if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
            header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
            exit;
        }

        $userId = (int)$_SESSION['userID'];
        
        // Delete all reports associated with this post before deleting the post
        require_once __DIR__ . '/../models/PostReport.php';
        $postReportModel = new PostReport($this->pdo);
        $postReportModel->deleteReportsByPost($id);
        
        $result = $this->postModel->delete($id, $userId);
        
        if ($result) {
            header('Location: ' . BASE_URL . '/index.php?action=forum_list&success=post_deleted');
        } else {
            header('Location: ' . BASE_URL . '/view/frontoffice/forum.php?error=post_delete_failed');
        }
        exit;
    }

    /**
     * Search posts
     */
    public function search() {
        session_start();

        if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
            header('Location: ' . BASE_URL . '/view/frontoffice/login.php');
            exit;
        }

        $query = $_GET['q'] ?? '';
        $filters = [
            'region' => $_GET['region'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'status' => $_GET['status'] ?? '',
            'sort' => $_GET['sort'] ?? 'date_desc'
        ];

        $posts = $this->postModel->search($query, $filters);
        $_SESSION['posts_data'] = $posts;
        
        header('Location: ' . BASE_URL . '/view/frontoffice/forum.php?search=1');
        exit;
    }

    /**
     * Get user info by CIN
     */
    private function getUserInfo($cin) {
        require_once __DIR__ . '/../models/User.php';
        $userModel = new User($this->pdo);
        $user = $userModel->getUserByCin($cin);
        return $user ?: ['firstname' => '', 'lastname' => '', 'email' => ''];
    }
}

