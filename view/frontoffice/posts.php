<?php
/**
 * Posts View - Modern UX/UI Design
 * Tabbed interface with All Posts, My Posts, Liked, Saved
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../config/autoload.php';
require_once __DIR__ . '/../../models/Post.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../services/PostCommentService.php';
require_once __DIR__ . '/../../services/PostLikeService.php';
require_once __DIR__ . '/../../services/PostReportService.php';
require_once __DIR__ . '/../../services/PostSaveService.php';

// Force baseUrl to correct value
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $protocol . '://' . $host . '/wafra/wafra-integration';
if (!defined('BASE_URL')) {
    define('BASE_URL', $baseUrl);
}

// Check if user is logged in
if (!isset($_SESSION['userID']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: ' . $baseUrl . '/view/frontoffice/login.php');
    exit;
}

$pdo = Database::connect();
$postModel = new Post($pdo);
$userModel = new User($pdo);
$commentService = new PostCommentService($pdo);
$likeService = new PostLikeService($pdo);
$reportService = new PostReportService($pdo);
$saveService = new PostSaveService($pdo);

$userId = (int)$_SESSION['userID'];
$currentUser = $userModel->getUserByCin($userId);

// Get user avatar
$userAvatar = BASE_URL . '/view/frontoffice/assets/images/default-avatar.png';
if (!empty($currentUser['profile_picture'])) {
    $avatarPath = __DIR__ . '/../../uploads/profile_pictures/' . basename($currentUser['profile_picture']);
    if (file_exists($avatarPath)) {
        $userAvatar = BASE_URL . '/uploads/profile_pictures/' . basename($currentUser['profile_picture']);
    }
}

// Get all posts with user info
$allPosts = $postModel->getAll();
$myPosts = $postModel->getByUserId($userId);
$likedPosts = $postModel->getLikedByUser($userId);
$savedPosts = $saveService->getSavedPosts($userId);

// Enhance posts with user info and interaction states
function enhancePosts($posts, $userId, $userModel, $likeService, $saveService, $reportService, $commentService, $baseUrl) {
    $enhanced = [];
    foreach ($posts as $post) {
        $postUserId = $post['id_user'] ?? null;
        $postUser = $postUserId ? $userModel->getUserByCin($postUserId) : null;
        
        $post['user_avatar'] = $baseUrl . '/view/frontoffice/assets/images/default-avatar.png';
        $post['user_name'] = $post['nom'] ?? 'Utilisateur';
        if ($postUser) {
            $post['user_name'] = trim(($postUser['firstname'] ?? '') . ' ' . ($postUser['lastname'] ?? ''));
            if (empty($post['user_name'])) {
                $post['user_name'] = $post['nom'] ?? 'Utilisateur';
            }
            if (!empty($postUser['profile_picture'])) {
                $avatarPath = __DIR__ . '/../../uploads/profile_pictures/' . basename($postUser['profile_picture']);
                if (file_exists($avatarPath)) {
                    $post['user_avatar'] = $baseUrl . '/uploads/profile_pictures/' . basename($postUser['profile_picture']);
                }
            }
        }
        
        $post['is_liked'] = $likeService->isLiked($post['id_post'], $userId);
        $post['is_saved'] = $saveService->isSaved($post['id_post'], $userId);
        $post['is_reported'] = $reportService->isReported($post['id_post'], $userId);
        $post['is_owner'] = ($post['id_user'] == $userId);
        
        // Ensure counts are set (use existing or default to 0)
        if (!isset($post['likes_count'])) {
            $post['likes_count'] = $likeService->getLikeCount($post['id_post']);
        }
        if (!isset($post['comments_count'])) {
            $post['comments_count'] = $commentService->getCommentCount($post['id_post']);
        }
        
        $enhanced[] = $post;
    }
    return $enhanced;
}

$allPosts = enhancePosts($allPosts, $userId, $userModel, $likeService, $saveService, $reportService, $commentService, $baseUrl);
$myPosts = enhancePosts($myPosts, $userId, $userModel, $likeService, $saveService, $reportService, $commentService, $baseUrl);
$likedPosts = enhancePosts($likedPosts, $userId, $userModel, $likeService, $saveService, $reportService, $commentService, $baseUrl);
$savedPosts = enhancePosts($savedPosts, $userId, $userModel, $likeService, $saveService, $reportService, $commentService, $baseUrl);

$pageTitle = 'Posts - Wafra';
$pageDescription = 'Partagez vos expériences et échangez avec la communauté';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap core CSS -->
    <link href="<?= $baseUrl ?>/view/frontoffice/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/fontawesome.css">
    
    <!-- Top Bar CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/topbar.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/topbar-enhanced.css">
    
    <!-- Chatbot CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/components/chatbot.css">
    
    <style>
        :root {
            --primary-color: #f5a425;
            --primary-hover: #e0941a;
            --text-primary: #1a1a1a;
            --text-secondary: #666;
            --text-light: #999;
            --bg-white: #ffffff;
            --bg-light: #f8f9fa;
            --border-color: #e0e0e0;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
            --radius: 12px;
            --radius-sm: 8px;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            padding: 0 !important;
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding-top: 60px !important;
            background: #f5f7fa;
            color: var(--text-primary);
        }
        
        .top-bar {
            left: 0 !important;
            width: 100% !important;
        }
        
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        /* Tab Navigation */
        .tabs-container {
            background: var(--bg-white);
            border-radius: var(--radius);
            padding: 0;
            margin-bottom: 30px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }
        
        .tabs-nav {
            display: flex;
            border-bottom: 2px solid var(--bg-light);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .tab-item {
            flex: 1;
            min-width: 120px;
            padding: 18px 24px;
            text-align: center;
            cursor: pointer;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            position: relative;
            white-space: nowrap;
        }
        
        .tab-item:hover {
            color: var(--primary-color);
            background: rgba(245, 164, 37, 0.05);
        }
        
        .tab-item.active {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .tab-item.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary-color);
            border-radius: 2px 2px 0 0;
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-10px); }
        }
        
        /* Create Post Form */
        .create-post-card {
            background: var(--bg-white);
            border-radius: var(--radius);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }
        
        .create-post-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border-color);
        }
        
        .create-post-form {
            display: none;
        }
        
        .create-post-form.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(245, 164, 37, 0.1);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-secondary {
            background: var(--bg-light);
            color: var(--text-primary);
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        /* Post Card */
        .posts-grid {
            display: grid;
            gap: 20px;
        }
        
        .post-card {
            background: var(--bg-white);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .post-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        
        .post-card.double-clicked {
            animation: likePulse 0.6s ease;
        }
        
        @keyframes likePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        
        .post-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .post-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border-color);
        }
        
        .post-meta {
            flex: 1;
        }
        
        .post-author-name {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 15px;
            margin-bottom: 2px;
        }
        
        .post-timestamp {
            font-size: 13px;
            color: var(--text-light);
        }
        
        .post-actions-header {
            display: flex;
            gap: 8px;
        }
        
        .post-action-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .post-action-icon:hover {
            background: var(--bg-light);
            color: var(--primary-color);
        }
        
        .post-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
            line-height: 1.4;
        }
        
        .post-region {
            display: inline-block;
            background: rgba(245, 164, 37, 0.1);
            color: var(--primary-color);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        
        .post-content {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .post-content.expanded {
            display: block;
            -webkit-line-clamp: unset;
        }
        
        .read-more-btn {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            border: none;
            background: none;
            padding: 0;
            margin-top: 4px;
        }
        
        .read-more-btn:hover {
            text-decoration: underline;
        }
        
        .post-media {
            margin: 16px 0;
            border-radius: var(--radius-sm);
            overflow: hidden;
        }
        
        .post-media img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .post-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 16px;
            border-top: 1px solid var(--bg-light);
            margin-top: 16px;
        }
        
        .post-actions {
            display: flex;
            gap: 24px;
            align-items: center;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            background: var(--bg-light);
            color: var(--primary-color);
        }
        
        .action-btn.liked {
            color: #e74c3c;
        }
        
        .action-btn.saved {
            color: var(--primary-color);
        }
        
        .action-btn.reported {
            color: var(--text-light);
            cursor: not-allowed;
        }
        
        .action-btn i {
            font-size: 16px;
        }
        
        .action-count {
            font-weight: 600;
        }
        
        /* My Posts Extra Actions */
        .post-owner-actions {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }
        
        /* Comments Side Panel */
        .comments-panel {
            position: fixed;
            top: 60px;
            right: -400px;
            width: 400px;
            height: calc(100vh - 60px);
            background: var(--bg-white);
            box-shadow: -4px 0 24px rgba(0,0,0,0.1);
            transition: right 0.3s ease;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        
        .comments-panel.active {
            right: 0;
        }
        
        .comments-panel-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .comments-panel-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        
        .comments-panel-footer {
            padding: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        .comment-item {
            padding: 12px 0;
            border-bottom: 1px solid var(--bg-light);
        }
        
        .comment-item:last-child {
            border-bottom: none;
        }
        
        .comment-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }
        
        .comment-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .comment-author {
            font-weight: 600;
            font-size: 14px;
            color: var(--text-primary);
        }
        
        .comment-time {
            font-size: 12px;
            color: var(--text-light);
            margin-left: auto;
        }
        
        .comment-text {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.5;
            margin-left: 40px;
        }
        
        .comment-actions {
            margin-left: 40px;
            margin-top: 8px;
            display: flex;
            gap: 12px;
        }
        
        .comment-input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            resize: vertical;
            font-family: inherit;
            font-size: 14px;
        }
        
        .comment-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state-icon {
            font-size: 64px;
            color: var(--text-light);
            margin-bottom: 16px;
        }
        
        .empty-state-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .empty-state-text {
            color: var(--text-secondary);
            margin-bottom: 24px;
        }
        
        /* Stats Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            animation: fadeIn 0.2s;
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: var(--bg-white);
            border-radius: var(--radius);
            padding: 32px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: slideUp 0.3s;
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .modal-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-light);
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        .close-btn:hover {
            background: var(--bg-light);
            color: var(--text-primary);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 24px;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
            background: var(--bg-light);
            border-radius: var(--radius-sm);
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        /* Edit Form */
        .edit-form {
            display: none;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--bg-light);
        }
        
        .edit-form.active {
            display: block;
        }
        
        /* See Likers Button */
        .see-likers-btn {
            background: none;
            border: none;
            padding: 4px 8px;
            margin-left: 4px;
            cursor: pointer;
            font-size: 13px;
            color: var(--text-secondary);
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            border-radius: var(--radius-sm);
        }
        
        .see-likers-btn:hover {
            color: var(--primary-color);
            background-color: var(--bg-light);
        }
        
        .likers-link {
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .see-likers-btn {
                font-size: 12px;
                padding: 2px 6px;
            }
        }
        
        /* Likers Modal */
        .likers-modal-content {
            max-width: 500px;
            padding: 0;
        }
        
        .likers-modal-body {
            max-height: 60vh;
            overflow-y: auto;
            padding: 20px;
        }
        
        .likers-modal-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--border-color);
            text-align: center;
        }
        
        .liker-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: var(--radius-sm);
            transition: background-color 0.2s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .liker-item:hover {
            background-color: var(--bg-light);
        }
        
        .liker-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .liker-info {
            flex: 1;
            min-width: 0;
        }
        
        .liker-name {
            font-weight: 600;
            font-size: 15px;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .liker-date {
            font-size: 12px;
            color: var(--text-light);
        }
        
        .likers-empty {
            text-align: center;
            padding: 60px 20px;
        }
        
        .likers-empty-icon {
            font-size: 64px;
            color: var(--text-light);
            margin-bottom: 16px;
        }
        
        .likers-empty-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .likers-empty-text {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        /* Contact Button Styles in Posts */
        .post-contact-section {
            padding: 12px 16px;
            border-top: 1px solid var(--bg-light);
            background: linear-gradient(135deg, #fff5e6 0%, #ffe8cc 100%);
            border-radius: 0 0 var(--radius) var(--radius);
            margin-top: 12px;
        }
        
        .contact-button-wrapper {
            margin: 0;
        }
        
        .contact-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            width: 100%;
            justify-content: center;
        }
        
        .contact-btn:hover:not(:disabled) {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 164, 37, 0.3);
        }
        
        .contact-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .contact-helper-text {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 4px;
            justify-content: center;
        }
        
        .contact-helper-text i {
            color: #6c757d;
        }
        
        /* Overlay for mobile */
        .overlay {
            display: none;
            position: fixed;
            top: 60px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        .overlay.active {
            display: block;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 20px 15px;
            }
            
            .tabs-nav {
                overflow-x: auto;
                scrollbar-width: none;
            }
            
            .tabs-nav::-webkit-scrollbar {
                display: none;
            }
            
            .tab-item {
                min-width: 100px;
                padding: 14px 16px;
                font-size: 13px;
            }
            
            .comments-panel {
                width: 100%;
                right: -100%;
            }
            
            .post-card {
                padding: 16px;
            }
            
            .post-title {
                font-size: 18px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Loading State */
        .loading {
            text-align: center;
            padding: 40px;
            color: var(--text-light);
        }
        
        .spinner {
            border: 3px solid var(--bg-light);
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    </style>
</head>
<body class="no-sidebar">

<!-- Top Bar -->
<div class="top-bar">
    <div class="top-bar-left">
        <a href="<?= $baseUrl ?>/view/frontoffice/index.php" style="text-decoration: none;">
            <h4 style="margin: 0; color: #ffd700; font-weight: 700;">Wafra</h4>
        </a>
    </div>
    <div class="top-bar-right">
        <div class="user-info">
            <i class="fa fa-user-circle" style="font-size: 24px; color: #f5a425;"></i>
            <span class="user-name"><?= htmlspecialchars($_SESSION['firstname'] ?? '') . ' ' . htmlspecialchars($_SESSION['lastname'] ?? '') ?></span>
        </div>
        <!-- Notifications Bell -->
        <div class="notification-container" style="position: relative; margin-right: 10px;">
            <button class="notification-bell" id="notificationBell" onclick="toggleNotificationDropdown()" aria-label="Notifications" style="background: transparent; border: none; color: #fff; font-size: 20px; cursor: pointer; padding: 8px 12px; position: relative;">
                <i class="fa fa-bell"></i>
                <span class="notification-badge" id="notificationBadge" style="position: absolute; top: 5px; right: 5px; background: #e74c3c; color: #fff; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; display: none; align-items: center; justify-content: center; font-weight: bold;">0</span>
            </button>
            <div class="notification-dropdown" id="notificationDropdown" style="position: absolute; top: 100%; right: 0; background: #fff; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); width: 350px; max-height: 500px; overflow-y: auto; z-index: 1000; margin-top: 10px;">
                <div class="notification-header" style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                    <h5 style="margin: 0; font-weight: 600;">Notifications</h5>
                    <button onclick="markAllNotificationsRead()" style="background: none; border: none; color: #f5a425; cursor: pointer; font-size: 12px;">Tout marquer comme lu</button>
                </div>
                <div class="notification-list" id="notificationList" style="max-height: 400px; overflow-y: auto;">
                    <div class="text-center" style="padding: 20px; color: #999;">
                        <i class="fa fa-spinner fa-spin"></i> Chargement...
                    </div>
                </div>
            </div>
        </div>
        <a href="<?= $baseUrl ?>/view/frontoffice/index.php" class="profile-link" style="margin-right: 10px;">
            <i class="fa fa-home"></i>
            <span>Home</span>
        </a>
        <a href="<?= $baseUrl ?>/view/frontoffice/posts.php" class="profile-link">
            <i class="fa fa-comments"></i>
            <span>Posts</span>
        </a>
        <a href="<?= $baseUrl ?>/index.php?action=donations" class="profile-link" style="margin-right: 10px;">
            <i class="fa fa-gift"></i>
            <span>Donations</span>
        </a>
        <a href="<?= $baseUrl ?>/view/frontoffice/messages.php" class="profile-link" style="position: relative;">
            <i class="fa fa-envelope"></i>
            <span>Messages</span>
            <span class="message-badge" id="messageBadge" style="position: absolute; top: -5px; right: -5px; background: #e74c3c; color: #fff; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; display: none; align-items: center; justify-content: center; font-weight: bold; z-index: 1000; line-height: 18px; text-align: center;">0</span>
        </a>
        <a href="<?= $baseUrl ?>/view/frontoffice/profile.php" class="profile-link">
            <i class="fa fa-user"></i>
            <span>Profil</span>
        </a>
        <a href="<?= $baseUrl ?>/index.php?action=logout" class="logout-link">
            <i class="fa fa-sign-out-alt"></i>
            <span>Déconnexion</span>
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <!-- Tab Navigation -->
    <div class="tabs-container">
        <div class="tabs-nav">
            <button class="tab-item active" data-tab="all">
                <i class="fa fa-globe"></i> Tous les posts
            </button>
            <button class="tab-item" data-tab="my">
                <i class="fa fa-user"></i> Mes posts
            </button>
            <button class="tab-item" data-tab="liked">
                <i class="fa fa-heart"></i> J'aime
            </button>
            <button class="tab-item" data-tab="saved">
                <i class="fa fa-bookmark"></i> Enregistrés
            </button>
        </div>
    </div>
    
    <!-- Create Post Card (shown in All Posts and My Posts) -->
    <div class="create-post-card" id="createPostCard">
        <div class="create-post-header">
            <img src="<?= htmlspecialchars($userAvatar) ?>" alt="Avatar" class="user-avatar">
            <button class="btn-secondary" onclick="toggleCreateForm()" style="flex: 1; text-align: left; justify-content: flex-start;">
                <i class="fa fa-edit"></i> Quoi de neuf ?
            </button>
        </div>
        <form id="createPostForm" class="create-post-form" action="<?= $baseUrl ?>/index.php?action=forum_create" method="POST" enctype="multipart/form-data" onsubmit="return validatePostForm(event)">
            <div class="form-group">
                <input type="text" name="titre" class="form-control" placeholder="Titre *" required>
            </div>
            <div class="form-group">
                <input type="text" name="region" class="form-control" placeholder="Région (optionnel)">
            </div>
            <div class="form-group">
                <textarea name="description" class="form-control" rows="4" placeholder="Description *" required></textarea>
            </div>
            <div class="form-group">
                <input type="text" name="numero" class="form-control" placeholder="Numéro de téléphone (optionnel)">
            </div>
            <div class="form-group">
                <input type="file" name="media" class="form-control" accept="image/*">
            </div>
            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-paper-plane"></i> Publier
                </button>
                <button type="button" class="btn btn-secondary" onclick="toggleCreateForm()">
                    Annuler
                </button>
            </div>
        </form>
    </div>
    
    <!-- All Posts Tab -->
    <div class="tab-content active" id="tab-all">
        <div class="posts-grid" id="posts-all">
            <?php if (empty($allPosts)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fa fa-inbox"></i></div>
                    <div class="empty-state-title">Aucun post pour le moment</div>
                    <div class="empty-state-text">Soyez le premier à partager !</div>
                </div>
            <?php else: ?>
                <?php foreach ($allPosts as $post): ?>
                    <?php 
                    // Make userId available to template as currentUserId
                    $currentUserId = $userId;
                    include __DIR__ . '/post-card-template.php'; 
                    ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- My Posts Tab -->
    <div class="tab-content" id="tab-my">
        <div class="posts-grid" id="posts-my">
            <?php if (empty($myPosts)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fa fa-file-alt"></i></div>
                    <div class="empty-state-title">Vous n'avez pas encore de posts</div>
                    <div class="empty-state-text">Créez votre premier post pour commencer !</div>
                </div>
            <?php else: ?>
                <?php foreach ($myPosts as $post): ?>
                    <?php 
                    $currentUserId = $userId;
                    include __DIR__ . '/post-card-template.php'; 
                    ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Liked Posts Tab -->
    <div class="tab-content" id="tab-liked">
        <div class="posts-grid" id="posts-liked">
            <?php if (empty($likedPosts)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fa fa-heart"></i></div>
                    <div class="empty-state-title">Aucun post aimé</div>
                    <div class="empty-state-text">Les posts que vous aimez apparaîtront ici</div>
                </div>
            <?php else: ?>
                <?php foreach ($likedPosts as $post): ?>
                    <?php 
                    $currentUserId = $userId;
                    include __DIR__ . '/post-card-template.php'; 
                    ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Saved Posts Tab -->
    <div class="tab-content" id="tab-saved">
        <div class="posts-grid" id="posts-saved">
            <?php if (empty($savedPosts)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fa fa-bookmark"></i></div>
                    <div class="empty-state-title">Aucun post enregistré</div>
                    <div class="empty-state-text">Enregistrez des posts pour les retrouver facilement</div>
                </div>
            <?php else: ?>
                <?php foreach ($savedPosts as $post): ?>
                    <?php 
                    $currentUserId = $userId;
                    include __DIR__ . '/post-card-template.php'; 
                    ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Comments Side Panel -->
<div class="overlay" id="commentsOverlay" onclick="closeCommentsPanel()"></div>
<div class="comments-panel" id="commentsPanel">
    <div class="comments-panel-header">
        <h3 style="margin: 0; font-size: 18px; font-weight: 600;">Commentaires</h3>
        <button class="close-btn" onclick="closeCommentsPanel()">
            <i class="fa fa-times"></i>
        </button>
    </div>
    <div class="comments-panel-body" id="commentsList">
        <div class="loading">
            <div class="spinner"></div>
            <div>Chargement...</div>
        </div>
    </div>
    <div class="comments-panel-footer">
        <textarea class="comment-input" id="commentInput" placeholder="Ajouter un commentaire..." rows="3"></textarea>
        <button class="btn btn-primary" style="width: 100%; margin-top: 12px;" onclick="submitComment()" id="commentSubmitBtn" disabled>
            <i class="fa fa-paper-plane"></i> Commenter
        </button>
    </div>
</div>

<!-- Stats Modal -->
<div class="modal" id="statsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Statistiques du post</h3>
            <button class="close-btn" onclick="closeStatsModal()">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="stats-grid" id="statsContent">
            <!-- Stats will be loaded here -->
        </div>
    </div>
</div>

<!-- Report Modal -->
<div class="modal" id="reportModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fa fa-flag" style="color: #e74c3c; margin-right: 8px;"></i>
                Signaler ce post
            </h3>
            <button class="close-btn" onclick="closeReportModal()">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <form id="reportForm" onsubmit="return submitReport(event)">
            <input type="hidden" id="report-post-id" value="">
            <div class="form-group">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">
                    Raison du signalement <span style="color: #e74c3c;">*</span>
                </label>
                <select id="report-reason" class="form-control" required style="margin-bottom: 16px;">
                    <option value="">-- Sélectionnez une raison --</option>
                    <option value="spam">Spam</option>
                    <option value="harassment">Harcèlement</option>
                    <option value="hate_speech">Discours de haine</option>
                    <option value="fake_information">Fausse information</option>
                    <option value="inappropriate_content">Contenu inapproprié</option>
                    <option value="other">Autre</option>
                </select>
            </div>
            <div class="form-group">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">
                    Description (optionnel)
                </label>
                <textarea id="report-description" class="form-control" rows="4" 
                          placeholder="Décrivez brièvement la raison du signalement (max 1000 caractères)..."
                          maxlength="1000"></textarea>
                <small style="color: var(--text-light); font-size: 12px; margin-top: 4px; display: block;">
                    <span id="report-char-count">0</span> / 1000 caractères
                </small>
            </div>
            <div id="report-error" style="display: none; padding: 12px; background: #fee; border: 1px solid #fcc; border-radius: var(--radius-sm); margin-bottom: 16px; color: #c33;">
                <i class="fa fa-exclamation-circle"></i> <span id="report-error-text"></span>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary" id="report-submit-btn">
                    <i class="fa fa-flag"></i> Signaler
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeReportModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<!-- Comment Report Modal -->
<div class="modal" id="commentReportModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fa fa-flag" style="color: #e74c3c; margin-right: 8px;"></i>
                Signaler ce commentaire
            </h3>
            <button class="close-btn" onclick="closeCommentReportModal()">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <form id="commentReportForm" onsubmit="return submitCommentReport(event)">
            <input type="hidden" id="comment-report-id" value="">
            <div class="form-group">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">
                    Raison du signalement <span style="color: #e74c3c;">*</span>
                </label>
                <select id="comment-report-reason" class="form-control" required style="margin-bottom: 16px;">
                    <option value="">-- Sélectionnez une raison --</option>
                    <option value="spam">Spam</option>
                    <option value="harassment">Harcèlement / Haine</option>
                    <option value="inappropriate_content">Contenu inapproprié</option>
                    <option value="other">Autre</option>
                </select>
            </div>
            <div class="form-group">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">
                    Message (optionnel)
                </label>
                <textarea id="comment-report-description" class="form-control" rows="4" 
                          placeholder="Décrivez brièvement la raison du signalement (max 1000 caractères)..."
                          maxlength="1000"></textarea>
                <small style="color: var(--text-light); font-size: 12px; margin-top: 4px; display: block;">
                    <span id="comment-report-char-count">0</span> / 1000 caractères
                </small>
            </div>
            <div id="comment-report-error" style="display: none; padding: 12px; background: #fee; border: 1px solid #fcc; border-radius: var(--radius-sm); margin-bottom: 16px; color: #c33;">
                <i class="fa fa-exclamation-circle"></i> <span id="comment-report-error-text"></span>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary" id="comment-report-submit-btn">
                    <i class="fa fa-flag"></i> Signaler
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeCommentReportModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<!-- Likers Modal -->
<div class="modal" id="likersModal">
    <div class="modal-content likers-modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fa fa-heart" style="color: #e74c3c; margin-right: 8px;"></i>
                Personnes qui ont aimé
            </h3>
            <button class="close-btn" onclick="closeLikersModal()" aria-label="Fermer">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="likers-modal-body" id="likersList">
            <div class="loading">
                <div class="spinner"></div>
                <div>Chargement...</div>
            </div>
        </div>
        <div class="likers-modal-footer" id="likersFooter" style="display: none;">
            <button class="btn btn-secondary" onclick="loadMoreLikers()" id="loadMoreLikersBtn">
                <i class="fa fa-chevron-down"></i> Charger plus
            </button>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="<?= $baseUrl ?>/view/frontoffice/vendor/jquery/jquery.min.js"></script>
<script src="<?= $baseUrl ?>/view/frontoffice/vendor/bootstrap/js/bootstrap.min.js"></script>

<script>
const baseUrl = '<?= $baseUrl ?>';
const currentUserId = <?= $userId ?>;
let currentPostId = null;
let currentTab = 'all';

// Tab Switching
document.querySelectorAll('.tab-item').forEach(tab => {
    tab.addEventListener('click', function() {
        const tabName = this.dataset.tab;
        switchTab(tabName);
    });
});

function switchTab(tabName) {
    // Update active tab
    document.querySelectorAll('.tab-item').forEach(t => t.classList.remove('active'));
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    
    // Update active content
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById(`tab-${tabName}`).classList.add('active');
    
    // Show/hide create post card
    const createCard = document.getElementById('createPostCard');
    if (tabName === 'all' || tabName === 'my') {
        createCard.style.display = 'block';
    } else {
        createCard.style.display = 'none';
    }
    
    currentTab = tabName;
}

// Create Post Form
function toggleCreateForm() {
    const form = document.getElementById('createPostForm');
    form.classList.toggle('active');
}

function validatePostForm(event) {
    const titre = event.target.titre.value.trim();
    const description = event.target.description.value.trim();
    
    if (!titre || titre.length < 3) {
        alert('Le titre doit contenir au moins 3 caractères');
        event.preventDefault();
        return false;
    }
    
    if (!description || description.length < 10) {
        alert('La description doit contenir au moins 10 caractères');
        event.preventDefault();
        return false;
    }
    
    return true;
}

// Like Toggle
function toggleLike(postId, event) {
    if (event) event.stopPropagation();
    
    fetch(baseUrl + '/index.php?action=api_post_like', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'toggle', id_post: postId })
    })
    .then(async response => {
        const responseText = await response.text();
        console.log('Like response status:', response.status);
        console.log('Like response text:', responseText);
        
        if (!response.ok) {
            throw new Error('HTTP error: ' + response.status + ': ' + responseText.substring(0, 100));
        }
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('JSON parse error:', e, 'Response:', responseText);
            throw new Error('Invalid JSON response: ' + responseText.substring(0, 200));
        }
        
        return data;
    })
    .then(data => {
        if (data && data.success) {
            const btn = document.querySelector(`[onclick*="toggleLike(${postId}"]`);
            const countSpan = document.getElementById(`like-count-${postId}`);
            
            if (btn) {
                if (data.isLiked) {
                    btn.classList.add('liked');
                } else {
                    btn.classList.remove('liked');
                }
            }
            
            if (countSpan) {
                countSpan.textContent = data.count || 0;
            }
            
            // Update tab if needed
            if (currentTab === 'liked' && !data.isLiked) {
                const postCard = document.querySelector(`[data-post-id="${postId}"]`);
                if (postCard) {
                    postCard.style.animation = 'fadeOut 0.3s';
                    setTimeout(() => postCard.remove(), 300);
                }
            }
        } else {
            console.error('Like error:', data);
            alert('Erreur: ' + (data?.error || 'Erreur lors du like'));
        }
    })
    .catch(error => {
        console.error('Like error:', error);
        alert('Erreur lors du like. Veuillez réessayer.');
    });
}

// Double-click to like
document.addEventListener('dblclick', function(e) {
    const postCard = e.target.closest('.post-card');
    if (postCard && !e.target.closest('.post-actions')) {
        const postId = parseInt(postCard.dataset.postId);
        if (postId) {
            postCard.classList.add('double-clicked');
            setTimeout(() => postCard.classList.remove('double-clicked'), 600);
            toggleLike(postId);
        }
    }
});

// Save Toggle
function toggleSave(postId, event) {
    if (event) event.stopPropagation();
    
    fetch(baseUrl + '/index.php?action=api_post_save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'toggle', id_post: postId })
    })
    .then(async response => {
        const responseText = await response.text();
        console.log('Save response status:', response.status);
        console.log('Save response text:', responseText);
        
        if (!response.ok) {
            throw new Error('HTTP error: ' + response.status + ': ' + responseText.substring(0, 100));
        }
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('JSON parse error:', e, 'Response:', responseText);
            throw new Error('Invalid JSON response: ' + responseText.substring(0, 200));
        }
        
        return data;
    })
    .then(data => {
        if (data && data.success) {
            // Update save button state in all tabs
            document.querySelectorAll(`[onclick*="toggleSave(${postId}"]`).forEach(btn => {
                if (data.isSaved) {
                    btn.classList.add('saved');
                } else {
                    btn.classList.remove('saved');
                }
            });
            
            // Always update the saved tab (add or remove)
            if (data.isSaved) {
                // Add post to saved tab immediately, regardless of current tab
                addPostToSavedTab(postId);
            } else {
                // Remove post from saved tab if it exists
                const savedContainer = document.getElementById('posts-saved');
                if (savedContainer) {
                    const postCard = savedContainer.querySelector(`[data-post-id="${postId}"]`);
                    if (postCard) {
                        postCard.style.animation = 'fadeOut 0.3s';
                        setTimeout(() => {
                            postCard.remove();
                            // Show empty state if no posts left
                            const remainingPosts = savedContainer.querySelectorAll('.post-card');
                            if (remainingPosts.length === 0) {
                                savedContainer.innerHTML = `
                                    <div class="empty-state">
                                        <div class="empty-state-icon"><i class="fa fa-bookmark"></i></div>
                                        <div class="empty-state-title">Aucun post enregistré</div>
                                        <div class="empty-state-text">Enregistrez des posts pour les retrouver facilement</div>
                                    </div>
                                `;
                            }
                        }, 300);
                    }
                }
            }
        } else {
            console.error('Save error:', data);
            alert('Erreur: ' + (data?.error || 'Erreur lors de l\'enregistrement'));
        }
    })
    .catch(error => {
        console.error('Save error:', error);
        alert('Erreur lors de l\'enregistrement. Veuillez réessayer.');
    });
}

// Comments Panel
function openCommentsPanel(postId) {
    currentPostId = postId;
    document.getElementById('commentsPanel').classList.add('active');
    document.getElementById('commentsOverlay').classList.add('active');
    loadComments(postId);
}

function closeCommentsPanel() {
    document.getElementById('commentsPanel').classList.remove('active');
    document.getElementById('commentsOverlay').classList.remove('active');
    currentPostId = null;
}

function loadComments(postId) {
    const commentsList = document.getElementById('commentsList');
    commentsList.innerHTML = '<div class="loading"><div class="spinner"></div><div>Chargement...</div></div>';
    
    fetch(baseUrl + '/index.php?action=api_post_comment', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get', id_post: postId })
    })
    .then(async response => {
        const responseText = await response.text();
        console.log('Load comments response status:', response.status);
        console.log('Load comments response text:', responseText);
        
        if (!response.ok) {
            throw new Error('HTTP error: ' + response.status + ': ' + responseText.substring(0, 100));
        }
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('JSON parse error:', e, 'Response:', responseText);
            throw new Error('Invalid JSON response: ' + responseText.substring(0, 200));
        }
        
        return data;
    })
    .then(data => {
        if (data && data.success) {
            renderComments(data.comments || []);
            if (currentPostId === postId) {
                updateCommentCount(postId, data.count || 0);
            }
        } else {
            console.error('Load comments error:', data);
            commentsList.innerHTML = '<div class="empty-state"><div class="empty-state-text">Erreur: ' + (data?.error || 'Erreur lors du chargement') + '</div></div>';
        }
    })
    .catch(error => {
        console.error('Load comments error:', error);
        commentsList.innerHTML = '<div class="empty-state"><div class="empty-state-text">Erreur lors du chargement. Veuillez réessayer.</div></div>';
    });
}

function renderComments(comments) {
    const commentsList = document.getElementById('commentsList');
    
    if (comments.length === 0) {
        commentsList.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fa fa-comment"></i></div><div class="empty-state-text">Aucun commentaire</div></div>';
        return;
    }
    
    commentsList.innerHTML = comments.map(comment => {
        const canDelete = comment.id_user == currentUserId;
        const date = new Date(comment.date_comment);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        let timeStr = '';
        if (diffDays > 7) {
            timeStr = date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
        } else if (diffDays > 0) {
            timeStr = diffDays + 'j';
        } else if (diffHours > 0) {
            timeStr = diffHours + 'h';
        } else if (diffMins > 0) {
            timeStr = diffMins + 'min';
        } else {
            timeStr = 'À l\'instant';
        }
        
        const userName = (comment.firstname || '') + ' ' + (comment.lastname || '');
        const displayName = userName.trim() || 'Utilisateur';
        
        // Get user avatar - check if profile_picture exists and is valid
        let avatar = baseUrl + '/view/frontoffice/assets/images/default-avatar.png';
        if (comment.profile_picture && comment.profile_picture.trim() !== '') {
            // profile_picture stores just the filename, so we need to construct the full path
            const filename = escapeHtml(comment.profile_picture);
            // Remove any path components if present (just get the filename)
            const basename = filename.split('/').pop();
            const avatarPath = baseUrl + '/uploads/profile_pictures/' + basename;
            avatar = avatarPath;
        }
        
        return `
            <div class="comment-item" id="comment-${comment.id_comment}">
                <div class="comment-header">
                    <img src="${escapeHtml(avatar)}" alt="Avatar" class="comment-avatar" onerror="this.src='${baseUrl}/view/frontoffice/assets/images/default-avatar.png'">
                    <span class="comment-author">${escapeHtml(displayName)}</span>
                    <span class="comment-time">${timeStr}</span>
                </div>
                <div class="comment-text">${escapeHtml(comment.comment_text).replace(/\n/g, '<br>')}</div>
                <div class="comment-actions">
                    ${canDelete ? `<button class="btn btn-sm btn-danger" onclick="deleteComment(${comment.id_comment})"><i class="fa fa-trash"></i> Supprimer</button>` : ''}
                    <button class="btn btn-sm btn-outline-warning" onclick="reportComment(${comment.id_comment}, event)" title="Signaler ce commentaire">
                        <i class="fa fa-flag"></i> Signaler
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

function submitComment() {
    if (!currentPostId) return;
    
    const input = document.getElementById('commentInput');
    const text = input.value.trim();
    
    if (!text) {
        alert('Veuillez entrer un commentaire');
        return;
    }
    
    // Disable button during submission
    const submitBtn = document.getElementById('commentSubmitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Envoi...';
    
    fetch(baseUrl + '/index.php?action=api_post_comment', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'add', id_post: currentPostId, comment_text: text })
    })
    .then(async response => {
        const responseText = await response.text();
        console.log('Add comment response status:', response.status);
        console.log('Add comment response text:', responseText);
        
        if (!response.ok) {
            throw new Error('HTTP error: ' + response.status + ': ' + responseText.substring(0, 100));
        }
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('JSON parse error:', e, 'Response:', responseText);
            throw new Error('Invalid JSON response: ' + responseText.substring(0, 200));
        }
        
        return data;
    })
    .then(data => {
        if (data && data.success) {
            input.value = '';
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa fa-paper-plane"></i> Commenter';
            loadComments(currentPostId);
            if (data.count !== undefined) {
                updateCommentCount(currentPostId, data.count);
            }
        } else {
            console.error('Add comment error:', data);
            alert('Erreur: ' + (data?.error || 'Erreur lors de l\'ajout'));
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa fa-paper-plane"></i> Commenter';
        }
    })
    .catch(error => {
        console.error('Add comment error:', error);
        alert('Erreur lors de l\'ajout du commentaire. Veuillez réessayer.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa fa-paper-plane"></i> Commenter';
    });
}

function deleteComment(commentId) {
    if (!confirm('Supprimer ce commentaire ?')) return;
    
    fetch(baseUrl + '/index.php?action=api_post_comment', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id_comment: commentId })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error: ' + response.status);
        }
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response:', text);
                throw new Error('Invalid response format');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data && data.success) {
            const commentEl = document.getElementById(`comment-${commentId}`);
            if (commentEl) {
                commentEl.remove();
            }
            if (currentPostId) {
                loadComments(currentPostId);
            }
        } else {
            console.error('Delete comment error:', data);
            alert('Erreur: ' + (data?.error || 'Erreur lors de la suppression'));
        }
    })
    .catch(error => {
        console.error('Delete comment error:', error);
        alert('Erreur lors de la suppression. Veuillez réessayer.');
    });
}

function updateCommentCount(postId, count) {
    const countSpan = document.getElementById(`comment-count-${postId}`);
    if (countSpan) {
        countSpan.textContent = count;
    }
}

// Comment input validation
document.getElementById('commentInput').addEventListener('input', function() {
    document.getElementById('commentSubmitBtn').disabled = this.value.trim().length === 0;
});

// Report
function reportPost(postId, event) {
    if (event) event.stopPropagation();
    document.getElementById('report-post-id').value = postId;
    document.getElementById('reportModal').classList.add('active');
}

function closeReportModal() {
    document.getElementById('reportModal').classList.remove('active');
    document.getElementById('report-reason').value = '';
    document.getElementById('report-description').value = '';
    document.getElementById('report-char-count').textContent = '0';
    document.getElementById('report-error').style.display = 'none';
    const submitBtn = document.getElementById('report-submit-btn');
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa fa-flag"></i> Signaler';
    }
}

function submitReport(event) {
    event.preventDefault();
    const postId = parseInt(document.getElementById('report-post-id').value);
    const reason = document.getElementById('report-reason').value.trim();
    const description = document.getElementById('report-description').value.trim();
    const errorDiv = document.getElementById('report-error');
    const errorText = document.getElementById('report-error-text');
    const submitBtn = document.getElementById('report-submit-btn');
    
    // Hide previous errors
    errorDiv.style.display = 'none';
    
    // Validate
    if (!reason) {
        errorText.textContent = 'Veuillez sélectionner une raison';
        errorDiv.style.display = 'block';
        return false;
    }
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Envoi...';
    
    fetch(baseUrl + '/index.php?action=api_post_report', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            action: 'report', 
            id_post: postId, 
            reason: reason,
            description: description
        })
    })
    .then(async response => {
        const responseText = await response.text();
        console.log('Report response status:', response.status);
        console.log('Report response text:', responseText);
        
        if (!response.ok) {
            throw new Error('HTTP error: ' + response.status + ': ' + responseText.substring(0, 100));
        }
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('JSON parse error:', e, 'Response:', responseText);
            throw new Error('Invalid JSON response: ' + responseText.substring(0, 200));
        }
        
        return data;
    })
    .then(data => {
        if (data && data.success) {
            // Show success message
            showToast('Post signalé avec succès', 'success');
            
            // Close modal
            closeReportModal();
            
            // Update all report buttons for this post
            document.querySelectorAll(`[onclick*="reportPost(${postId}"]`).forEach(btn => {
                btn.classList.add('reported');
                btn.disabled = true;
                btn.title = 'Déjà signalé';
            });
        } else {
            // Show error
            console.error('Report failed:', data);
            if (data?.alreadyReported) {
                errorText.textContent = 'Vous avez déjà signalé ce post';
            } else {
                const errorMsg = data?.error || 'Erreur lors du signalement';
                errorText.textContent = errorMsg;
                console.error('Report error message:', errorMsg);
            }
            errorDiv.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa fa-flag"></i> Signaler';
        }
    })
    .catch(error => {
        console.error('Report error:', error);
        console.error('Error details:', error.message, error.stack);
        errorText.textContent = 'Erreur lors du signalement: ' + (error.message || 'Erreur inconnue. Veuillez réessayer.');
        errorDiv.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa fa-flag"></i> Signaler';
    });
    
    return false;
}

// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: ${type === 'success' ? '#2ecc71' : '#e74c3c'};
        color: white;
        padding: 16px 24px;
        border-radius: var(--radius-sm);
        box-shadow: var(--shadow-lg);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
        display: flex;
        align-items: center;
        gap: 12px;
        max-width: 400px;
    `;
    toast.innerHTML = `
        <i class="fa fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${escapeHtml(message)}</span>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Character counter for report description
document.addEventListener('DOMContentLoaded', function() {
    const descTextarea = document.getElementById('report-description');
    const charCount = document.getElementById('report-char-count');
    
    if (descTextarea && charCount) {
        descTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }
});

// Stats Modal
function showStats(postId, event) {
    if (event) event.stopPropagation();
    
    fetch(baseUrl + '/index.php?action=api_post_comment', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get', id_post: postId })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error: ' + response.status);
        }
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response:', text);
                throw new Error('Invalid response format');
            });
        }
        return response.json();
    })
    .then(data => {
        const likesCount = document.getElementById(`like-count-${postId}`)?.textContent || 0;
        const commentsCount = (data && data.count) ? data.count : 0;
        
        document.getElementById('statsContent').innerHTML = `
            <div class="stat-item">
                <div class="stat-value">${likesCount}</div>
                <div class="stat-label">J'aime</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">${commentsCount}</div>
                <div class="stat-label">Commentaires</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">-</div>
                <div class="stat-label">Vues</div>
            </div>
        `;
        document.getElementById('statsModal').classList.add('active');
    })
    .catch(error => {
        console.error('Stats error:', error);
        // Still show modal with available data
        const likesCount = document.getElementById(`like-count-${postId}`)?.textContent || 0;
        document.getElementById('statsContent').innerHTML = `
            <div class="stat-item">
                <div class="stat-value">${likesCount}</div>
                <div class="stat-label">J'aime</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">-</div>
                <div class="stat-label">Commentaires</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">-</div>
                <div class="stat-label">Vues</div>
            </div>
        `;
        document.getElementById('statsModal').classList.add('active');
    });
}

function closeStatsModal() {
    document.getElementById('statsModal').classList.remove('active');
}

// Likers Modal Functions
let currentLikersPostId = null;
let likersOffset = 0;
let likersHasMore = false;

function showLikersModal(postId, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    currentLikersPostId = postId;
    likersOffset = 0;
    likersHasMore = false;
    
    const modal = document.getElementById('likersModal');
    const likersList = document.getElementById('likersList');
    const footer = document.getElementById('likersFooter');
    
    modal.classList.add('active');
    footer.style.display = 'none';
    likersList.innerHTML = '<div class="loading"><div class="spinner"></div><div>Chargement...</div></div>';
    
    // Prevent body scroll when modal is open
    document.body.style.overflow = 'hidden';
    
    // Load likers
    loadLikers(postId, 0, true);
    
    // Focus management for accessibility
    const closeBtn = modal.querySelector('.close-btn');
    if (closeBtn) {
        setTimeout(() => closeBtn.focus(), 100);
    }
}

function closeLikersModal() {
    const modal = document.getElementById('likersModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    currentLikersPostId = null;
    likersOffset = 0;
    likersHasMore = false;
}

function loadLikers(postId, offset = 0, replace = false) {
    const likersList = document.getElementById('likersList');
    const footer = document.getElementById('likersFooter');
    const loadMoreBtn = document.getElementById('loadMoreLikersBtn');
    
    if (!replace && offset === 0) {
        likersList.innerHTML = '<div class="loading"><div class="spinner"></div><div>Chargement...</div></div>';
    }
    
    const limit = 20;
    const url = `${baseUrl}/index.php?action=api_post_like&action=get_likers&id_post=${postId}&limit=${limit}&offset=${offset}`;
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(async response => {
        const responseText = await response.text();
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        try {
            return JSON.parse(responseText);
        } catch (e) {
            console.error('Invalid JSON response:', responseText);
            throw new Error('Invalid JSON response');
        }
    })
    .then(data => {
        if (data.success) {
            if (replace || offset === 0) {
                likersList.innerHTML = '';
            }
            
            if (data.users && data.users.length > 0) {
                data.users.forEach(user => {
                    const likerItem = createLikerItem(user);
                    likersList.appendChild(likerItem);
                });
                
                likersOffset = offset + data.users.length;
                likersHasMore = data.has_more || false;
                
                if (likersHasMore) {
                    footer.style.display = 'block';
                    loadMoreBtn.disabled = false;
                } else {
                    footer.style.display = 'none';
                }
            } else {
                if (replace || offset === 0) {
                    likersList.innerHTML = `
                        <div class="likers-empty">
                            <div class="likers-empty-icon">
                                <i class="fa fa-heart"></i>
                            </div>
                            <div class="likers-empty-title">Aucun like pour le moment</div>
                            <div class="likers-empty-text">Soyez le premier à aimer ce post !</div>
                        </div>
                    `;
                }
                footer.style.display = 'none';
            }
            
            // Show message if user can't see full list
            if (data.message && (replace || offset === 0)) {
                likersList.innerHTML = `
                    <div class="likers-empty">
                        <div class="likers-empty-icon">
                            <i class="fa fa-lock"></i>
                        </div>
                        <div class="likers-empty-title">Accès restreint</div>
                        <div class="likers-empty-text">${escapeHtml(data.message)}</div>
                    </div>
                `;
            }
        } else {
            likersList.innerHTML = `
                <div class="likers-empty">
                    <div class="likers-empty-icon">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                    <div class="likers-empty-title">Erreur</div>
                    <div class="likers-empty-text">Impossible de charger les likes</div>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading likers:', error);
        likersList.innerHTML = `
            <div class="likers-empty">
                <div class="likers-empty-icon">
                    <i class="fa fa-exclamation-triangle"></i>
                </div>
                <div class="likers-empty-title">Erreur</div>
                <div class="likers-empty-text">Une erreur s'est produite lors du chargement</div>
            </div>
        `;
    });
}

function createLikerItem(user) {
    const item = document.createElement('a');
    item.href = `${baseUrl}/view/frontoffice/profile.php?user_id=${user.id}`;
    item.className = 'liker-item';
    
    // Format date
    let dateStr = '';
    if (user.date_like) {
        const date = new Date(user.date_like);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffDays > 7) {
            dateStr = date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
        } else if (diffDays > 0) {
            dateStr = diffDays + 'j';
        } else if (diffHours > 0) {
            dateStr = diffHours + 'h';
        } else if (diffMins > 0) {
            dateStr = diffMins + 'min';
        } else {
            dateStr = 'À l\'instant';
        }
    }
    
    item.innerHTML = `
        <img src="${escapeHtml(user.avatar)}" alt="${escapeHtml(user.name)}" class="liker-avatar" onerror="this.src='${baseUrl}/view/frontoffice/assets/images/default-avatar.png'">
        <div class="liker-info">
            <div class="liker-name">${escapeHtml(user.name)}</div>
            <div class="liker-date">${dateStr}</div>
        </div>
    `;
    
    return item;
}

function loadMoreLikers() {
    if (!currentLikersPostId || !likersHasMore) return;
    
    const loadMoreBtn = document.getElementById('loadMoreLikersBtn');
    loadMoreBtn.disabled = true;
    loadMoreBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Chargement...';
    
    loadLikers(currentLikersPostId, likersOffset, false);
    setTimeout(() => {
        loadMoreBtn.disabled = false;
        loadMoreBtn.innerHTML = '<i class="fa fa-chevron-down"></i> Charger plus';
    }, 500);
}

// Close modal on outside click
document.addEventListener('click', function(event) {
    const modal = document.getElementById('likersModal');
    if (event.target === modal) {
        closeLikersModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('likersModal');
        if (modal && modal.classList.contains('active')) {
            closeLikersModal();
        }
    }
});

// Edit Post
function editPost(postId, event) {
    if (event) event.stopPropagation();
    const postCard = document.querySelector(`[data-post-id="${postId}"]`);
    const editForm = postCard.querySelector('.edit-form');
    editForm.classList.toggle('active');
}

function saveEdit(postId, event) {
    event.preventDefault();
    const form = event.target;
    const titre = form.titre.value.trim();
    const description = form.description.value.trim();
    const region = form.region.value.trim();
    
    if (!titre || titre.length < 3) {
        alert('Le titre doit contenir au moins 3 caractères');
        return false;
    }
    
    if (!description || description.length < 10) {
        alert('La description doit contenir au moins 10 caractères');
        return false;
    }
    
    const formData = new FormData();
    formData.append('id_post', postId);
    formData.append('titre', titre);
    formData.append('description', description);
    formData.append('region', region);
    if (form.media.files[0]) {
        formData.append('media', form.media.files[0]);
    }
    
    fetch(baseUrl + '/index.php?action=forum_update', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            location.reload();
        } else {
            alert('Erreur lors de la mise à jour');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur lors de la mise à jour');
    });
    
    return false;
}

// Delete Post
function deletePost(postId, event) {
    if (event) event.stopPropagation();
    if (!confirm('Supprimer ce post ? Cette action est irréversible.')) return;
    
    window.location.href = baseUrl + '/index.php?action=forum_delete&id=' + postId;
}

// Read More
function toggleReadMore(postId) {
    const content = document.getElementById(`post-content-${postId}`);
    const btn = document.getElementById(`read-more-${postId}`);
    if (content && btn) {
        content.classList.toggle('expanded');
        btn.textContent = content.classList.contains('expanded') ? 'Lire moins' : 'Lire plus';
    }
}

// Add post to saved tab
function addPostToSavedTab(postId) {
    console.log('Adding post to saved tab:', postId);
    // Check if post already exists in saved tab
    const savedContainer = document.getElementById('posts-saved');
    if (!savedContainer) {
        console.error('Saved container not found');
        return;
    }
    
    const existingPost = savedContainer.querySelector(`[data-post-id="${postId}"]`);
    if (existingPost) {
        // Post already exists, just update save button state
        console.log('Post already exists in saved tab, updating button');
        const saveBtn = existingPost.querySelector(`[onclick*="toggleSave(${postId}"]`);
        if (saveBtn) {
            saveBtn.classList.add('saved');
        }
        return;
    }
    
    // Find the post in any tab (including current tab)
    const allTabs = ['posts-all', 'posts-my', 'posts-liked', 'posts-saved'];
    let sourcePost = null;
    
    for (const tabId of allTabs) {
        const tab = document.getElementById(tabId);
        if (tab) {
            const post = tab.querySelector(`[data-post-id="${postId}"]`);
            if (post) {
                sourcePost = post;
                break;
            }
        }
    }
    
    // Also try to find in the currently visible tab (in case it's not in the list above)
    if (!sourcePost) {
        const currentTabContent = document.querySelector('.tab-content.active');
        if (currentTabContent) {
            const post = currentTabContent.querySelector(`[data-post-id="${postId}"]`);
            if (post) {
                sourcePost = post;
            }
        }
    }
    
    if (sourcePost) {
        console.log('Found source post, cloning...');
        // Clone the post card
        const clonedPost = sourcePost.cloneNode(true);
        
        // Update the save button state
        const saveBtn = clonedPost.querySelector(`[onclick*="toggleSave(${postId}"]`);
        if (saveBtn) {
            saveBtn.classList.add('saved');
        }
        
        // Always update counts to ensure they're accurate
        updatePostCounts(postId, clonedPost);
        
        // Remove empty state if exists (before adding post)
        const emptyState = savedContainer.querySelector('.empty-state');
        if (emptyState) {
            emptyState.remove();
        }
        
        // Add to saved tab with animation
        clonedPost.style.opacity = '0';
        clonedPost.style.transform = 'translateY(-10px)';
        savedContainer.insertBefore(clonedPost, savedContainer.firstChild);
        
        console.log('Post added to saved tab');
        
        // Animate in
        setTimeout(() => {
            clonedPost.style.transition = 'all 0.3s ease';
            clonedPost.style.opacity = '1';
            clonedPost.style.transform = 'translateY(0)';
        }, 10);
    } else {
        // Post not found in other tabs - log for debugging
        console.warn('Post not found in any tab, cannot add to saved tab:', postId);
        console.log('Available tabs:', ['posts-all', 'posts-my', 'posts-liked', 'posts-saved'].map(id => {
            const tab = document.getElementById(id);
            return { id, exists: !!tab, posts: tab ? tab.querySelectorAll('.post-card').length : 0 };
        }));
    }
}

// Update post counts from server
function updatePostCounts(postId, postElement) {
    if (!postElement) return;
    
    // Get like count
    fetch(baseUrl + '/index.php?action=api_post_like', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get', id_post: postId })
    })
    .then(async response => {
        const responseText = await response.text();
        if (!response.ok) return;
        try {
            const data = JSON.parse(responseText);
            if (data && data.success) {
                const likeCountEl = postElement.querySelector(`#like-count-${postId}`);
                if (likeCountEl) {
                    likeCountEl.textContent = data.count || 0;
                }
            }
        } catch (e) {
            console.error('Error parsing like count:', e);
        }
    })
    .catch(error => console.error('Error fetching like count:', error));
    
    // Get comment count
    fetch(baseUrl + '/index.php?action=api_post_comment', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get', id_post: postId })
    })
    .then(async response => {
        const responseText = await response.text();
        if (!response.ok) return;
        try {
            const data = JSON.parse(responseText);
            if (data && data.success) {
                const commentCountEl = postElement.querySelector(`#comment-count-${postId}`);
                if (commentCountEl) {
                    commentCountEl.textContent = data.count || 0;
                }
            }
        } catch (e) {
            console.error('Error parsing comment count:', e);
        }
    })
    .catch(error => console.error('Error fetching comment count:', error));
}

// Fetch post details from server
async function fetchPostDetails(postId) {
    try {
        // We'll need to create an API endpoint for this, but for now
        // we can use the existing post card structure
        // This is a placeholder - in a real scenario, you'd fetch from API
        return null;
    } catch (error) {
        console.error('Error fetching post details:', error);
        return null;
    }
}

// Render a post card (simplified version)
function renderPostCard(postData, container, prepend = false) {
    // This would render a full post card
    // For now, we'll use the clone method above
}

// Utility
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modals on outside click
window.addEventListener('click', function(event) {
    const statsModal = document.getElementById('statsModal');
    const reportModal = document.getElementById('reportModal');
    
    if (event.target === statsModal) {
        closeStatsModal();
    }
    if (event.target === reportModal) {
        closeReportModal();
    }
    const commentReportModal = document.getElementById('commentReportModal');
    if (event.target === commentReportModal) {
        closeCommentReportModal();
    }
});

// Comment Report Functions
function reportComment(commentId, event) {
    if (event) event.stopPropagation();
    document.getElementById('comment-report-id').value = commentId;
    document.getElementById('commentReportModal').classList.add('active');
}

function closeCommentReportModal() {
    document.getElementById('commentReportModal').classList.remove('active');
    document.getElementById('comment-report-reason').value = '';
    document.getElementById('comment-report-description').value = '';
    document.getElementById('comment-report-char-count').textContent = '0';
    document.getElementById('comment-report-error').style.display = 'none';
    const submitBtn = document.getElementById('comment-report-submit-btn');
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa fa-flag"></i> Signaler';
    }
}

function submitCommentReport(event) {
    event.preventDefault();
    const commentId = parseInt(document.getElementById('comment-report-id').value);
    const reason = document.getElementById('comment-report-reason').value.trim();
    const description = document.getElementById('comment-report-description').value.trim();
    const errorDiv = document.getElementById('comment-report-error');
    const errorText = document.getElementById('comment-report-error-text');
    const submitBtn = document.getElementById('comment-report-submit-btn');
    
    errorDiv.style.display = 'none';
    
    if (!reason) {
        errorText.textContent = 'Veuillez sélectionner une raison';
        errorDiv.style.display = 'block';
        return false;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Envoi...';
    
    fetch(baseUrl + '/index.php?action=api_comment_report', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            action: 'report', 
            id_comment: commentId, 
            reason: reason,
            description: description
        })
    })
    .then(async response => {
        const responseText = await response.text();
        console.log('Comment report response status:', response.status);
        console.log('Comment report response text:', responseText);
        
        if (!response.ok) {
            throw new Error('HTTP error: ' + response.status + ': ' + responseText.substring(0, 100));
        }
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('JSON parse error:', e, 'Response:', responseText);
            throw new Error('Invalid JSON response: ' + responseText.substring(0, 200));
        }
        
        return data;
    })
    .then(data => {
        if (data && data.success) {
            showToast('Commentaire signalé avec succès', 'success');
            closeCommentReportModal();
            
            // Update report button for this comment
            document.querySelectorAll(`[onclick*="reportComment(${commentId}"]`).forEach(btn => {
                btn.classList.add('reported');
                btn.disabled = true;
                btn.title = 'Déjà signalé';
                btn.innerHTML = '<i class="fa fa-flag"></i> Signalé';
            });
        } else {
            if (data?.alreadyReported) {
                errorText.textContent = 'Vous avez déjà signalé ce commentaire';
            } else {
                errorText.textContent = data?.error || 'Erreur lors du signalement';
            }
            errorDiv.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa fa-flag"></i> Signaler';
        }
    })
    .catch(error => {
        console.error('Comment report error:', error);
        errorText.textContent = 'Erreur lors du signalement. Veuillez réessayer.';
        errorDiv.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa fa-flag"></i> Signaler';
    });
    
    return false;
}

// Character counter for comment report description
// Handle comment_id and post_id parameters to scroll to specific comment
document.addEventListener('DOMContentLoaded', function() {
    // Check if we have a comment_id in URL
    const urlParams = new URLSearchParams(window.location.search);
    const commentId = urlParams.get('comment_id');
    const postId = urlParams.get('post_id');
    
    if (commentId) {
        // If we have a post_id, open that post's comments first
        if (postId) {
            setTimeout(() => {
                openCommentsPanel(postId);
                // Wait for comments to load, then scroll
                setTimeout(() => {
                    scrollToComment(commentId);
                }, 1500);
            }, 500);
        } else {
            // Try to find the comment in any open comments panel
            setTimeout(() => {
                scrollToComment(commentId);
            }, 1000);
        }
    } else if (postId) {
        // Just open the post's comments
        setTimeout(() => {
            openCommentsPanel(postId);
        }, 500);
    }
    
    function scrollToComment(commentId) {
        // Try different possible comment element IDs/classes
        const commentElement = document.getElementById(`comment-${commentId}`) ||
                               document.querySelector(`[data-comment-id="${commentId}"]`) ||
                               document.querySelector(`.comment-item[data-id="${commentId}"]`);
        
        if (commentElement) {
            commentElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            // Highlight the comment briefly
            commentElement.style.backgroundColor = '#fff3cd';
            commentElement.style.transition = 'background-color 0.3s';
            setTimeout(() => {
                commentElement.style.backgroundColor = '';
            }, 2000);
        } else {
            // If comment not found, try again after a short delay (comments might still be loading)
            setTimeout(() => {
                const retryElement = document.getElementById(`comment-${commentId}`);
                if (retryElement) {
                    retryElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    retryElement.style.backgroundColor = '#fff3cd';
                    setTimeout(() => {
                        retryElement.style.backgroundColor = '';
                    }, 2000);
                }
            }, 1000);
        }
    }
    
    window.scrollToComment = scrollToComment;
});

document.addEventListener('DOMContentLoaded', function() {
    const descTextarea = document.getElementById('comment-report-description');
    const charCount = document.getElementById('comment-report-char-count');
    
    if (descTextarea && charCount) {
        descTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }

    // ========== NOTIFICATION SYSTEM ==========
    let notificationPollInterval = null;
    const NOTIFICATION_POLL_INTERVAL = 30000; // 30 seconds

    // Load notifications on page load
    loadNotifications();
    updateNotificationCount();
    
    // Start polling for new notifications
    notificationPollInterval = setInterval(() => {
        updateNotificationCount();
        // Reload notifications if dropdown is open
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown && dropdown.classList.contains('show')) {
                loadNotifications();
            }
    }, NOTIFICATION_POLL_INTERVAL);

    // Toggle notification dropdown (enhanced version)
    window.toggleNotificationDropdown = function() {
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown) return;
        
        const isVisible = dropdown.classList.contains('show');
        if (isVisible) {
            dropdown.classList.remove('show');
        } else {
            dropdown.classList.add('show');
            loadNotifications();
        }
    };

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const container = document.querySelector('.notification-container');
        const dropdown = document.getElementById('notificationDropdown');
        if (container && dropdown && !container.contains(event.target) && dropdown.classList.contains('show')) {
            dropdown.classList.remove('show');
        }
    });

    // Load notifications
    function loadNotifications() {
        const list = document.getElementById('notificationList');
        if (!list) return;
        list.innerHTML = '<div class="text-center" style="padding: 20px; color: #999;"><i class="fa fa-spinner fa-spin"></i> Chargement...</div>';
        
        fetch(baseUrl + '/index.php?action=api_notifications&subaction=getUnread&limit=10')
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.json();
            })
            .then(data => {
                if (data.success && data.notifications) {
                    renderNotifications(data.notifications);
                    updateNotificationBadge(data.unread_count || 0);
                } else {
                    list.innerHTML = '<div class="text-center" style="padding: 20px; color: #999;">Aucune notification</div>';
                }
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
                if (list) list.innerHTML = '<div class="text-center" style="padding: 20px; color: #e74c3c;">Erreur de chargement</div>';
            });
    }

    // Render notifications
    function renderNotifications(notifications) {
        const list = document.getElementById('notificationList');
        if (!list) return;
        
        if (notifications.length === 0) {
            list.innerHTML = '<div class="text-center" style="padding: 20px; color: #999;">Aucune notification</div>';
            return;
        }
        
        list.innerHTML = notifications.map(notif => {
            const unreadClass = !notif.is_read ? 'unread' : '';
            const icon = getNotificationIcon(notif.type);
            return `
                <div class="notification-item ${unreadClass}" onclick="handleNotificationClick(${notif.id}, '${notif.entity_type}', ${notif.entity_id})" style="padding: 12px 15px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s; ${unreadClass ? 'background: #f8f9fa; font-weight: 600;' : ''}">
                    <div style="display: flex; align-items: start; gap: 10px;">
                        <i class="fa ${icon}" style="color: #f5a425; font-size: 18px; margin-top: 2px;"></i>
                        <div style="flex: 1;">
                            <div style="font-size: 14px; color: #333; margin-bottom: 4px;">${escapeHtml(notif.message)}</div>
                            <div style="font-size: 11px; color: #999;">${notif.time_ago || ''}</div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Get notification icon based on type
    function getNotificationIcon(type) {
        const icons = {
            'post_liked': 'fa-heart',
            'post_commented': 'fa-comment',
            'comment_replied': 'fa-reply',
            'post_milestone': 'fa-trophy',
            'post_removed': 'fa-ban'
        };
        return icons[type] || 'fa-bell';
    }

    // Handle notification click
    window.handleNotificationClick = function(notificationId, entityType, entityId) {
        // Mark as read
        markNotificationAsRead(notificationId);
        
        // Navigate based on entity type
        if (entityType === 'conversation') {
            // Redirect to messages page with conversation ID
            window.location.href = baseUrl + '/view/frontoffice/messages.php?conversation_id=' + entityId;
        } else if (entityType === 'post') {
            window.location.href = baseUrl + '/view/frontoffice/posts.php?post_id=' + entityId;
        } else if (entityType === 'comment') {
            // For comments, we need to get the post_id from the comment
            // Fetch comment details to get post_id
            fetch(baseUrl + '/index.php?action=api_post_comment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_comment', id_comment: entityId })
            })
            .then(response => response.json())
            .then(data => {
                if (data && data.success && data.comment && data.comment.id_post) {
                    // Redirect with both post_id and comment_id
                    window.location.href = baseUrl + '/view/frontoffice/posts.php?post_id=' + data.comment.id_post + '&comment_id=' + entityId;
                } else {
                    // Fallback: just redirect to posts page
                    window.location.href = baseUrl + '/view/frontoffice/posts.php';
                }
            })
            .catch(error => {
                console.error('Error fetching comment:', error);
                // Fallback: just redirect to posts page
                window.location.href = baseUrl + '/view/frontoffice/posts.php';
            });
        }
        
        // Close dropdown
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown) dropdown.classList.remove('show');
    };

    // Mark notification as read
    function markNotificationAsRead(notificationId) {
        fetch(baseUrl + '/index.php?action=api_notifications&subaction=markAsRead', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationCount();
                loadNotifications();
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
    }

    // Mark all as read
    window.markAllNotificationsRead = function() {
        fetch(baseUrl + '/index.php?action=api_notifications&subaction=markAllAsRead', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationCount();
                loadNotifications();
            }
        })
        .catch(error => console.error('Error marking all as read:', error));
    };

    // Update notification count badge
    function updateNotificationCount() {
        fetch(baseUrl + '/index.php?action=api_notifications&subaction=getCount')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationBadge(data.count || 0);
                }
            })
            .catch(error => console.error('Error updating notification count:', error));
    }

    // Update badge display
    function updateNotificationBadge(count) {
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    // Update message count badge
    function updateMessageCount() {
        fetch(baseUrl + '/index.php?action=api_message&subaction=get_unread_count')
            .then(async response => {
                const responseText = await response.text();
                if (!response.ok) {
                    return { success: false };
                }
                try {
                    return JSON.parse(responseText);
                } catch (e) {
                    return { success: false };
                }
            })
            .then(data => {
                if (data.success) {
                    updateMessageBadge(data.count || 0);
                }
            })
            .catch(error => console.error('Error updating message count:', error));
    }
    
    // Update message badge display
    function updateMessageBadge(count) {
        const badge = document.getElementById('messageBadge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.setProperty('display', 'flex', 'important');
                badge.style.setProperty('visibility', 'visible', 'important');
                badge.style.setProperty('opacity', '1', 'important');
            } else {
                badge.style.setProperty('display', 'none', 'important');
            }
        }
    }
    
    // Load message count on page load and update periodically
    updateMessageCount();
    setInterval(updateMessageCount, 30000); // Update every 30 seconds

    // Escape HTML helper
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

<!-- Chatbot -->
<?php require_once __DIR__ . '/../components/chatbot.php'; ?>

</body>
</html>
