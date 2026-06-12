<?php
// Post Card Template - Reusable component for displaying posts
// Ensure $baseUrl is available
if (!isset($baseUrl)) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $protocol . '://' . $host . '/wafra/wafra-integration';
}

// Get current user ID from session if not provided
if (!isset($currentUserId) && isset($_SESSION['userID'])) {
    $currentUserId = (int)$_SESSION['userID'];
}

$postId = $post['id_post'];
$isOwner = $post['is_owner'] ?? false;
$isLiked = $post['is_liked'] ?? false;
$isSaved = $post['is_saved'] ?? false;
$isReported = $post['is_reported'] ?? false;
$userName = $post['user_name'] ?? 'Utilisateur';
$userAvatar = $post['user_avatar'] ?? $baseUrl . '/view/frontoffice/assets/images/default-avatar.png';
$postTitle = htmlspecialchars($post['titre'] ?? '');
$postDescription = htmlspecialchars($post['description'] ?? '');
$postRegion = htmlspecialchars($post['region'] ?? '');
$postDate = $post['date_creation'] ?? date('Y-m-d');
$likesCount = $post['likes_count'] ?? 0;
$commentsCount = $post['comments_count'] ?? 0;
$postMedia = $post['media'] ?? '';

// Format timestamp
$timestamp = '';
if ($postDate) {
    $dateObj = new DateTime($postDate);
    $now = new DateTime();
    $diff = $now->diff($dateObj);
    
    if ($diff->days > 7) {
        $timestamp = $dateObj->format('d/m/Y');
    } elseif ($diff->days > 0) {
        $timestamp = $diff->days . 'j';
    } elseif ($diff->h > 0) {
        $timestamp = $diff->h . 'h';
    } elseif ($diff->i > 0) {
        $timestamp = $diff->i . 'min';
    } else {
        $timestamp = 'À l\'instant';
    }
}
?>
<div class="post-card" data-post-id="<?= $postId ?>">
    <div class="post-header">
        <img src="<?= htmlspecialchars($userAvatar) ?>" alt="Avatar" class="post-avatar">
        <div class="post-meta">
            <div class="post-author-name"><?= htmlspecialchars($userName) ?></div>
            <div class="post-timestamp"><?= $timestamp ?></div>
        </div>
        <div class="post-actions-header">
            <?php if ($isOwner): ?>
                <button class="post-action-icon" onclick="showStats(<?= $postId ?>, event)" title="Statistiques">
                    <i class="fa fa-chart-bar"></i>
                </button>
                <button class="post-action-icon" onclick="editPost(<?= $postId ?>, event)" title="Modifier">
                    <i class="fa fa-edit"></i>
                </button>
                <button class="post-action-icon" onclick="deletePost(<?= $postId ?>, event)" title="Supprimer" style="color: #e74c3c;">
                    <i class="fa fa-trash"></i>
                </button>
            <?php else: ?>
                <button class="post-action-icon report-btn <?= $isReported ? 'reported' : '' ?>" 
                        onclick="reportPost(<?= $postId ?>, event)" 
                        <?= $isReported ? 'disabled title="Déjà signalé"' : 'title="Signaler"' ?>>
                    <i class="fa fa-flag"></i>
                </button>
            <?php endif; ?>
        </div>
    </div>
    
    <h3 class="post-title"><?= $postTitle ?></h3>
    
    <?php if (!empty($postRegion)): ?>
        <span class="post-region"><?= $postRegion ?></span>
    <?php endif; ?>
    
    <div class="post-content" id="post-content-<?= $postId ?>">
        <?= nl2br($postDescription) ?>
    </div>
    
    <?php if (strlen($postDescription) > 150): ?>
        <button class="read-more-btn" id="read-more-<?= $postId ?>" onclick="toggleReadMore(<?= $postId ?>)">
            Lire plus
        </button>
    <?php endif; ?>
    
    <?php if (!empty($postMedia)): ?>
        <div class="post-media">
            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($postMedia) ?>" alt="Post media">
        </div>
    <?php endif; ?>
    
    <!-- Edit Form (for owner) -->
    <?php if ($isOwner): ?>
        <form class="edit-form" onsubmit="return saveEdit(<?= $postId ?>, event)">
            <div class="form-group">
                <input type="text" name="titre" class="form-control" value="<?= $postTitle ?>" required>
            </div>
            <div class="form-group">
                <input type="text" name="region" class="form-control" value="<?= $postRegion ?>" placeholder="Région">
            </div>
            <div class="form-group">
                <textarea name="description" class="form-control" rows="4" required><?= $postDescription ?></textarea>
            </div>
            <div class="form-group">
                <input type="file" name="media" class="form-control" accept="image/*">
            </div>
            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="editPost(<?= $postId ?>, event)">
                    Annuler
                </button>
            </div>
        </form>
    <?php endif; ?>
    
    <?php if (!$isOwner): ?>
        <!-- Contact Post Owner Button -->
        <div class="post-contact-section" style="padding: 12px 16px; border-top: 1px solid var(--bg-light, #f0f0f0); background: #fafafa;">
            <?php
            $postOwnerId = $post['id_user'] ?? null;
            if ($postOwnerId && isset($currentUserId) && $postOwnerId != $currentUserId):
                require_once __DIR__ . '/components/contact-button.php';
                echo renderContactButton(
                    (int)$postOwnerId,
                    'post',
                    (int)$postId,
                    [
                        'label' => '💬 Contacter le propriétaire',
                        'icon' => 'fa-comments',
                        'size' => 'md',
                        'show_helper' => true
                    ]
                );
            endif;
            ?>
        </div>
    <?php endif; ?>
    
    <div class="post-footer">
        <div class="post-actions">
            <button class="action-btn <?= $isLiked ? 'liked' : '' ?>" onclick="toggleLike(<?= $postId ?>, event)">
                <i class="fa fa-heart"></i>
                <span class="action-count" id="like-count-<?= $postId ?>"><?= $likesCount ?></span>
            </button>
            <?php if ($likesCount > 0): ?>
                <button class="see-likers-btn" onclick="showLikersModal(<?= $postId ?>, event)" title="Voir qui a aimé">
                    <span class="likers-link"><?= $likesCount ?> <?= $likesCount === 1 ? 'like' : 'likes' ?></span>
                </button>
            <?php endif; ?>
            <button class="action-btn" onclick="openCommentsPanel(<?= $postId ?>)">
                <i class="fa fa-comment"></i>
                <span class="action-count" id="comment-count-<?= $postId ?>"><?= $commentsCount ?></span>
            </button>
            <button class="action-btn <?= $isSaved ? 'saved' : '' ?>" onclick="toggleSave(<?= $postId ?>, event)">
                <i class="fa fa-bookmark"></i>
            </button>
        </div>
    </div>
</div>

