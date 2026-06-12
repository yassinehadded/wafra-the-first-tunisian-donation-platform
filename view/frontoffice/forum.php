<?php
/**
 * Forum View
 * Displays posts, comments, and likes
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
require_once __DIR__ . '/../../services/PostCommentService.php';
require_once __DIR__ . '/../../services/PostLikeService.php';

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
$commentService = new PostCommentService($pdo);
$likeService = new PostLikeService($pdo);

$userId = (int)$_SESSION['userID'];

// Get posts from session or fetch fresh
$posts = $_SESSION['posts_data'] ?? $postModel->getAll();
unset($_SESSION['posts_data']);

// Get specific post if requested
$selectedPost = null;
if (isset($_GET['post_id'])) {
    $selectedPost = $postModel->find((int)$_GET['post_id']);
    if ($selectedPost) {
        // Get comments and likes for this post
        $selectedPost['comments'] = $commentService->getCommentsByPost($selectedPost['id_post']);
        $selectedPost['likes_count'] = $likeService->getLikeCount($selectedPost['id_post']);
        $selectedPost['is_liked'] = $likeService->isLiked($selectedPost['id_post'], $userId);
    }
}

$pageTitle = 'Forum - Wafra';
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
    <link href="https://fonts.googleapis.com/css?family=Montserrat:100,200,300,400,500,600,700,800,900" rel="stylesheet">
    
    <!-- Bootstrap core CSS -->
    <link href="<?= $baseUrl ?>/view/frontoffice/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Additional CSS Files -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/fontawesome.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/templatemo-grad-school.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/owl.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/lightbox.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/flex-slider.css">
    
    <!-- Top Bar CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/topbar.css">
    
    <!-- Chatbot CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/components/chatbot.css">
    
    <style>
        .sidebar-nav {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #1a1a1a;
            padding: 20px 0;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-nav .logo {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #333;
            margin-bottom: 20px;
        }
        
        .sidebar-nav .logo a {
            color: #ffd700;
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
        }
        
        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav ul li {
            margin: 0;
        }
        
        .sidebar-nav ul li a {
            display: block;
            padding: 15px 20px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-nav ul li a:hover,
        .sidebar-nav ul li a.active {
            background: #2a2a2a;
            border-left-color: #f5a425;
            color: #f5a425;
        }
        
        body {
            padding-left: 250px;
        }
        
        .main-content {
            min-height: 100vh;
        }
        
        .section {
            padding: 100px 0 80px;
        }
        
        .post-card {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .post-author {
            font-weight: 600;
            color: #333;
        }
        
        .post-date {
            color: #999;
            font-size: 14px;
        }
        
        .post-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 10px;
        }
        
        .post-region {
            display: inline-block;
            background: #f5a425;
            color: #fff;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .post-description {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .post-media {
            margin: 20px 0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .post-media img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .post-actions {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .action-btn:hover {
            color: #f5a425;
        }
        
        .action-btn.liked {
            color: #e74c3c;
        }
        
        .comments-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .comment-form {
            margin-bottom: 20px;
        }
        
        .comment-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
        }
        
        .comment-item {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .comment-author {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .comment-text {
            color: #555;
            margin-bottom: 5px;
        }
        
        .comment-date {
            font-size: 12px;
            color: #999;
        }
        
        .btn-primary {
            background: #f5a425;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-primary:hover {
            background: #e0941a;
        }
        
        .create-post-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #f5a425;
            color: #fff;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(245, 164, 37, 0.4);
            transition: transform 0.3s, box-shadow 0.3s;
            z-index: 999;
        }
        
        .create-post-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(245, 164, 37, 0.6);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background: #fff;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        @media (max-width: 768px) {
            body {
                padding-left: 0;
            }
            
            .sidebar-nav {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .sidebar-nav.active {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <div class="top-bar-left">
        <h4 style="margin: 0; color: #ffd700; font-weight: 700;">Wafra</h4>
    </div>
    <div class="top-bar-right">
        <div class="user-info">
            <i class="fa fa-user-circle" style="font-size: 24px; color: #f5a425;"></i>
            <span class="user-name"><?= htmlspecialchars($_SESSION['firstname'] ?? '') . ' ' . htmlspecialchars($_SESSION['lastname'] ?? '') ?></span>
        </div>
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

<!-- Left Sidebar Navigation -->
<nav class="sidebar-nav" id="sidebar">
    <div class="logo">
        <a href="<?= $baseUrl ?>/view/frontoffice/index.php" style="color: #ffd700;">Wafra</a>
    </div>
    <ul>
        <li><a href="<?= $baseUrl ?>/view/frontoffice/index.php">Accueil</a></li>
        <li><a href="<?= $baseUrl ?>/view/frontoffice/index.php#events">Événements</a></li>
        <li><a href="<?= $baseUrl ?>/view/frontoffice/index.php#reservations">Réservations</a></li>
        <li><a href="<?= $baseUrl ?>/view/frontoffice/forum.php" class="active">Forum</a></li>
    </ul>
</nav>

<!-- Main Content -->
<div class="main-content">
    <section class="section" id="forum">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-heading text-center">
                        <h2>Forum Communautaire</h2>
                        <p>Partagez vos expériences et échangez avec la communauté</p>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <?php if ($selectedPost): ?>
                        <!-- Single Post View -->
                        <div class="post-card">
                            <div class="post-header">
                                <div>
                                    <div class="post-author"><?= htmlspecialchars($selectedPost['nom'] ?? 'Utilisateur') ?></div>
                                    <div class="post-date"><?= date('d/m/Y', strtotime($selectedPost['date_creation'])) ?></div>
                                </div>
                                <?php if ($selectedPost['id_user'] == $userId): ?>
                                    <div>
                                        <a href="<?= $baseUrl ?>/index.php?action=forum_delete&id=<?= $selectedPost['id_post'] ?>" 
                                           onclick="return confirm('Supprimer ce post ?')" 
                                           class="btn btn-sm btn-danger">Supprimer</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="post-title"><?= htmlspecialchars($selectedPost['titre'] ?? '') ?></h3>
                            
                            <?php if (!empty($selectedPost['region'])): ?>
                                <span class="post-region"><?= htmlspecialchars($selectedPost['region']) ?></span>
                            <?php endif; ?>
                            
                            <div class="post-description">
                                <?= nl2br(htmlspecialchars($selectedPost['description'] ?? '')) ?>
                            </div>
                            
                            <?php if (!empty($selectedPost['media'])): ?>
                                <div class="post-media">
                                    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($selectedPost['media']) ?>" alt="Post media">
                                </div>
                            <?php endif; ?>
                            
                            <div class="post-actions">
                                <button class="action-btn <?= $selectedPost['is_liked'] ? 'liked' : '' ?>" 
                                        onclick="toggleLike(<?= $selectedPost['id_post'] ?>)">
                                    <i class="fa fa-heart"></i>
                                    <span id="like-count-<?= $selectedPost['id_post'] ?>"><?= $selectedPost['likes_count'] ?></span>
                                </button>
                                <button class="action-btn" onclick="toggleComments(<?= $selectedPost['id_post'] ?>)">
                                    <i class="fa fa-comment"></i>
                                    <span id="comment-count-<?= $selectedPost['id_post'] ?>"><?= count($selectedPost['comments']) ?></span>
                                </button>
                            </div>
                            
                            <div class="comments-section" id="comments-<?= $selectedPost['id_post'] ?>">
                                <div class="comment-form">
                                    <textarea class="comment-input" id="comment-input-<?= $selectedPost['id_post'] ?>" 
                                              placeholder="Ajouter un commentaire..." rows="3"></textarea>
                                    <button class="btn-primary" onclick="addComment(<?= $selectedPost['id_post'] ?>)">
                                        Commenter
                                    </button>
                                </div>
                                
                                <div id="comments-list-<?= $selectedPost['id_post'] ?>">
                                    <?php foreach ($selectedPost['comments'] as $comment): ?>
                                        <div class="comment-item" id="comment-<?= $comment['id_comment'] ?>">
                                            <div class="comment-author">
                                                <?= htmlspecialchars(($comment['firstname'] ?? '') . ' ' . ($comment['lastname'] ?? 'Utilisateur')) ?>
                                            </div>
                                            <div class="comment-text"><?= nl2br(htmlspecialchars($comment['comment_text'])) ?></div>
                                            <div class="comment-date"><?= date('d/m/Y H:i', strtotime($comment['date_comment'])) ?></div>
                                            <?php if ($comment['id_user'] == $userId): ?>
                                                <button onclick="deleteComment(<?= $comment['id_comment'] ?>, <?= $selectedPost['id_post'] ?>)" 
                                                        class="btn btn-sm btn-danger">Supprimer</button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <a href="<?= $baseUrl ?>/view/frontoffice/forum.php" class="btn-primary">Retour à la liste</a>
                    <?php else: ?>
                        <!-- Posts List -->
                        <?php if (empty($posts)): ?>
                            <div class="text-center" style="padding: 50px;">
                                <h3>Aucun post pour le moment</h3>
                                <p>Soyez le premier à partager !</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($posts as $post): ?>
                                <?php
                                $isLiked = $likeService->isLiked($post['id_post'], $userId);
                                $likesCount = $post['likes_count'] ?? $likeService->getLikeCount($post['id_post']);
                                $commentsCount = $post['comments_count'] ?? $commentService->getCommentCount($post['id_post']);
                                ?>
                                <div class="post-card">
                                    <div class="post-header">
                                        <div>
                                            <div class="post-author"><?= htmlspecialchars($post['nom'] ?? 'Utilisateur') ?></div>
                                            <div class="post-date"><?= date('d/m/Y', strtotime($post['date_creation'])) ?></div>
                                        </div>
                                        <?php if ($post['id_user'] == $userId): ?>
                                            <div>
                                                <a href="<?= $baseUrl ?>/index.php?action=forum_delete&id=<?= $post['id_post'] ?>" 
                                                   onclick="return confirm('Supprimer ce post ?')" 
                                                   class="btn btn-sm btn-danger">Supprimer</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h3 class="post-title">
                                        <a href="<?= $baseUrl ?>/index.php?action=forum_show&id=<?= $post['id_post'] ?>" 
                                           style="color: inherit; text-decoration: none;">
                                            <?= htmlspecialchars($post['titre'] ?? '') ?>
                                        </a>
                                    </h3>
                                    
                                    <?php if (!empty($post['region'])): ?>
                                        <span class="post-region"><?= htmlspecialchars($post['region']) ?></span>
                                    <?php endif; ?>
                                    
                                    <div class="post-description">
                                        <?= nl2br(htmlspecialchars(substr($post['description'] ?? '', 0, 200))) ?>
                                        <?php if (strlen($post['description'] ?? '') > 200): ?>...<?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($post['media'])): ?>
                                        <div class="post-media">
                                            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($post['media']) ?>" alt="Post media">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="post-actions">
                                        <button class="action-btn <?= $isLiked ? 'liked' : '' ?>" 
                                                onclick="toggleLike(<?= $post['id_post'] ?>)">
                                            <i class="fa fa-heart"></i>
                                            <span id="like-count-<?= $post['id_post'] ?>"><?= $likesCount ?></span>
                                        </button>
                                        <a href="<?= $baseUrl ?>/index.php?action=forum_show&id=<?= $post['id_post'] ?>" 
                                           class="action-btn" style="text-decoration: none;">
                                            <i class="fa fa-comment"></i>
                                            <span><?= $commentsCount ?></span>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Create Post Button -->
<button class="create-post-btn" onclick="openCreatePostModal()" title="Créer un post">
    <i class="fa fa-plus"></i>
</button>

<!-- Create Post Modal -->
<div id="createPostModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeCreatePostModal()">&times;</span>
        <h2>Créer un nouveau post</h2>
        <form action="<?= $baseUrl ?>/index.php?action=forum_create" method="POST" enctype="multipart/form-data">
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Titre *</label>
                <input type="text" name="titre" class="form-control" required>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Région</label>
                <input type="text" name="region" class="form-control">
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Description *</label>
                <textarea name="description" class="form-control" rows="5" required></textarea>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Numéro de téléphone</label>
                <input type="text" name="numero" class="form-control">
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Image/Media</label>
                <input type="file" name="media" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn-primary">Publier</button>
            <button type="button" class="btn-secondary" onclick="closeCreatePostModal()">Annuler</button>
        </form>
    </div>
</div>

<!-- Scripts -->
<script src="<?= $baseUrl ?>/view/frontoffice/vendor/jquery/jquery.min.js"></script>
<script src="<?= $baseUrl ?>/view/frontoffice/vendor/bootstrap/js/bootstrap.min.js"></script>

<script>
const baseUrl = '<?= $baseUrl ?>';

function toggleLike(postId) {
    if (!postId) {
        console.error('Invalid post ID:', postId);
        return;
    }
    
    console.log('Attempting to toggle like for post:', postId);
    
    // Show loading state
    const likeBtn = document.querySelector(`button[onclick*="toggleLike(${postId})"]`);
    const likeIcon = likeBtn?.querySelector('i');
    const originalIcon = likeIcon?.className;
    
    if (likeIcon) {
        likeIcon.className = 'fa fa-spinner fa-spin';
    }
    
    fetch(baseUrl + '/index.php?action=api_post_like', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=toggle&id_post=' + postId,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('Like toggle response:', data);
        
        if (data.success) {
            const likeCount = document.getElementById(`like-count-${postId}`);
            
            // Update like button state
            if (likeBtn) {
                if (data.isLiked) {
                    likeBtn.classList.add('liked');
                    likeBtn.title = 'Je n\'aime plus';
                } else {
                    likeBtn.classList.remove('liked');
                    likeBtn.title = 'J\'aime';
                }
            }
            
            // Update like count
            if (likeCount) {
                likeCount.textContent = data.count;
                // Add animation
                likeCount.classList.add('pulse');
                setTimeout(() => likeCount.classList.remove('pulse'), 500);
            }
            
            console.log('Like toggled successfully');
        } else {
            console.error('Error toggling like:', data.error || 'Unknown error');
            showError('Erreur lors du like: ' + (data.error || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Error toggling like:', error);
        showError('Erreur de connexion au serveur: ' + error.message);
    })
    .finally(() => {
        // Reset icon
        if (likeIcon && originalIcon) {
            likeIcon.className = originalIcon;
        }
    });
}

function showError(message) {
    // Check if toast notification exists, if not create it
    let toast = document.getElementById('error-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'error-toast';
        toast.style.position = 'fixed';
        toast.style.bottom = '20px';
        toast.style.right = '20px';
        toast.style.backgroundColor = '#f8d7da';
        toast.style.color = '#721c24';
        toast.style.padding = '15px 25px';
        toast.style.borderRadius = '4px';
        toast.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
        toast.style.zIndex = '9999';
        toast.style.maxWidth = '300px';
        toast.style.wordBreak = 'break-word';
        document.body.appendChild(toast);
    }
    
    toast.textContent = message;
    toast.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        toast.style.display = 'none';
    }, 5000);
}

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
    .pulse {
        display: inline-block;
        animation: pulse 0.5s ease-in-out;
    }
    .action-btn i.fa-spinner {
        margin-right: 5px;
    }
`;
document.head.appendChild(style);

function addComment(postId) {
    const input = document.getElementById('comment-input-' + postId);
    const commentText = input.value.trim();
    
    if (!commentText) {
        alert('Veuillez entrer un commentaire');
        return;
    }
    
    fetch(baseUrl + '/index.php?action=api_post_comment', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            id_post: postId,
            comment_text: commentText
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            loadComments(postId);
            const countSpan = document.getElementById('comment-count-' + postId);
            if (countSpan) {
                countSpan.textContent = data.count;
            }
        } else {
            alert('Erreur: ' + (data.error || 'Erreur lors de l\'ajout du commentaire'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur lors de l\'ajout du commentaire');
    });
}

function deleteComment(commentId, postId) {
    if (!confirm('Supprimer ce commentaire ?')) {
        return;
    }
    
    fetch(baseUrl + '/index.php?action=api_post_comment', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'delete',
            id_comment: commentId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('comment-' + commentId).remove();
            loadComments(postId);
        } else {
            alert('Erreur lors de la suppression');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur lors de la suppression');
    });
}

function loadComments(postId) {
    fetch(baseUrl + '/index.php?action=api_post_comment', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'get',
            id_post: postId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const commentsList = document.getElementById('comments-list-' + postId);
            if (commentsList) {
                commentsList.innerHTML = '';
                data.comments.forEach(comment => {
                    const commentDiv = document.createElement('div');
                    commentDiv.className = 'comment-item';
                    commentDiv.id = 'comment-' + comment.id_comment;
                    commentDiv.innerHTML = `
                        <div class="comment-author">${escapeHtml((comment.firstname || '') + ' ' + (comment.lastname || 'Utilisateur'))}</div>
                        <div class="comment-text">${escapeHtml(comment.comment_text).replace(/\n/g, '<br>')}</div>
                        <div class="comment-date">${new Date(comment.date_comment).toLocaleString('fr-FR')}</div>
                        ${comment.id_user == <?= $userId ?> ? `<button onclick="deleteComment(${comment.id_comment}, ${postId})" class="btn btn-sm btn-danger">Supprimer</button>` : ''}
                    `;
                    commentsList.appendChild(commentDiv);
                });
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function toggleComments(postId) {
    const section = document.getElementById('comments-' + postId);
    if (section) {
        section.style.display = section.style.display === 'none' ? 'block' : 'none';
    }
}

function openCreatePostModal() {
    document.getElementById('createPostModal').style.display = 'block';
}

function closeCreatePostModal() {
    document.getElementById('createPostModal').style.display = 'none';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('createPostModal');
    if (event.target == modal) {
        closeCreatePostModal();
    }
}
</script>

<!-- Chatbot -->
<?php require_once __DIR__ . '/../components/chatbot.php'; ?>

</body>
</html>






