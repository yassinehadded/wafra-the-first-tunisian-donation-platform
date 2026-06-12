<?php
/**
 * Donations View - Front Office
 * Form to submit donation and list user's donations
 */
$pageTitle = 'Donations - Wafra';
$pageDescription = 'Soumettez une donation ou consultez vos donations';
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
            --radius: 12px;
            --radius-sm: 8px;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-light);
            padding-top: 80px;
        }
        
        .donations-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .donation-card {
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .donation-form {
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
            display: block;
        }
        
        .form-group label.required::after {
            content: " *";
            color: #dc3545;
        }
        
        .form-control {
            border: 2px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(245, 164, 37, 0.1);
            outline: none;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 12px 30px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 164, 37, 0.3);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-active {
            background: #28a745;
            color: white;
        }
        
        .status-fulfilled {
            background: #6c757d;
            color: white;
        }
        
        .donation-item {
            border-bottom: 1px solid var(--border-color);
            padding: 20px 0;
        }
        
        .donation-item:last-child {
            border-bottom: none;
        }
        
        .donation-image-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            margin-top: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state-icon {
            font-size: 64px;
            color: var(--text-light);
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
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
                <span class="notification-badge" id="notificationBadge" style="position: absolute; top: 5px; right: 5px; background: #e74c3c; color: #fff; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; display: none; align-items: center; justify-content: center; font-weight: bold; z-index: 1000; line-height: 18px; text-align: center;">0</span>
            </button>
            <div class="notification-dropdown" id="notificationDropdown" style="position: absolute; top: 100%; right: 0; background: #fff; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); width: 350px; max-height: 500px; overflow-y: auto; z-index: 1000; margin-top: 10px; display: none;">
                <div class="notification-header" style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f8f9fa;">
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
        <a href="<?= $baseUrl ?>/view/frontoffice/posts.php" class="profile-link" style="margin-right: 10px;">
            <i class="fa fa-comments"></i>
            <span>Posts</span>
        </a>
        <a href="<?= $baseUrl ?>/view/frontoffice/messages.php" class="profile-link" style="position: relative; margin-right: 10px;">
            <i class="fa fa-envelope"></i>
            <span>Messages</span>
            <span class="message-badge" id="messageBadge" style="position: absolute; top: -5px; right: -5px; background: #e74c3c; color: #fff; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; display: none; align-items: center; justify-content: center; font-weight: bold; z-index: 1000; line-height: 18px; text-align: center;">0</span>
        </a>
        <a href="<?= $baseUrl ?>/index.php?action=donations" class="profile-link" style="background: rgba(245, 164, 37, 0.2); margin-right: 10px;">
            <i class="fa fa-gift"></i>
            <span>Donations</span>
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
<div class="donations-container">
    <div class="row">
        <div class="col-md-12">
            <h1 style="margin-bottom: 30px; color: var(--text-primary);">
                <i class="fa fa-gift"></i> Mes Donations
            </h1>
        </div>
    </div>
    
    <?php if ($successMessage): ?>
    <div class="alert alert-success">
        <i class="fa fa-check-circle"></i> <?= htmlspecialchars($successMessage) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($errorMessage): ?>
    <div class="alert alert-danger">
        <i class="fa fa-exclamation-circle"></i> <?= $errorMessage ?>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Form Section -->
        <div class="col-md-6">
            <div class="donation-form">
                <h3 style="margin-bottom: 25px; color: var(--text-primary);">
                    <i class="fa fa-plus-circle"></i> Nouvelle Donation
                </h3>
                
                <form method="POST" action="<?= $baseUrl ?>/index.php?action=donation_submit" enctype="multipart/form-data" id="donationForm">
                    <div class="form-group">
                        <label class="required">Nom du donateur</label>
                        <input type="text" name="donor_name" class="form-control" 
                               value="<?= htmlspecialchars($oldData['donor_name'] ?? ($currentUser['firstname'] ?? '') . ' ' . ($currentUser['lastname'] ?? '')) ?>"
                               required pattern="[A-Za-zÀ-ÿ\s]{3,50}" 
                               title="3 à 50 caractères, lettres uniquement">
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="donor_email" class="form-control" 
                               value="<?= htmlspecialchars($oldData['donor_email'] ?? ($currentUser['email'] ?? '')) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Téléphone</label>
                        <input type="tel" name="donor_phone" class="form-control" 
                               placeholder="8 chiffres" maxlength="8" 
                               pattern="[0-9]{8}"
                               value="<?= htmlspecialchars($oldData['donor_phone'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Titre</label>
                        <input type="text" name="title" class="form-control" 
                               placeholder="Ex: Livres d'occasion" 
                               required minlength="3"
                               value="<?= htmlspecialchars($oldData['title'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="4" 
                                  placeholder="Décrivez votre donation..."><?= htmlspecialchars($oldData['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Catégorie</label>
                        <select name="category" class="form-control" required>
                            <option value="">-- Sélectionnez --</option>
                            <option value="Books" <?= ($oldData['category'] ?? '') === 'Books' ? 'selected' : '' ?>>Livres</option>
                            <option value="Clothing" <?= ($oldData['category'] ?? '') === 'Clothing' ? 'selected' : '' ?>>Vêtements</option>
                            <option value="Electronics" <?= ($oldData['category'] ?? '') === 'Electronics' ? 'selected' : '' ?>>Électronique</option>
                            <option value="Food" <?= ($oldData['category'] ?? '') === 'Food' ? 'selected' : '' ?>>Nourriture</option>
                            <option value="Other" <?= ($oldData['category'] ?? '') === 'Other' ? 'selected' : '' ?>>Autre</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Quantité</label>
                        <input type="number" name="quantity" class="form-control" 
                               min="1" required
                               value="<?= htmlspecialchars($oldData['quantity'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Image</label>
                        <input type="file" name="item_image" class="form-control" 
                               accept="image/jpeg,image/png,image/gif,image/webp">
                        <small class="text-muted">Formats acceptés: JPEG, PNG, GIF, WebP (max 5MB)</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-paper-plane"></i> Soumettre la donation
                    </button>
                </form>
            </div>
        </div>
        
        <!-- List Section -->
        <div class="col-md-6">
            <div class="donation-card">
                <h3 style="margin-bottom: 25px; color: var(--text-primary);">
                    <i class="fa fa-list"></i> Historique
                </h3>
                
                <?php if (empty($userDonations)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fa fa-inbox"></i>
                    </div>
                    <h4>Aucune donation</h4>
                    <p>Vous n'avez pas encore soumis de donation.</p>
                </div>
                <?php else: ?>
                <div id="donationsList">
                    <?php foreach ($userDonations as $donation): ?>
                    <div class="donation-item">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong>#<?= $donation['id'] ?></strong> - <?= htmlspecialchars($donation['title']) ?>
                            </div>
                            <div>
                                <span class="status-badge status-<?= strtolower($donation['status']) ?>">
                                    <?= htmlspecialchars($donation['status']) === 'active' ? 'Active' : 'Remplie' ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <span class="badge badge-secondary"><?= htmlspecialchars($donation['category']) ?></span>
                            <small class="text-muted ml-2">
                                <i class="fa fa-calendar"></i> <?= date('d/m/Y', strtotime($donation['date'])) ?>
                            </small>
                            <small class="text-muted ml-2">
                                <i class="fa fa-cubes"></i> Quantité: <?= $donation['quantity'] ?>
                            </small>
                        </div>
                        
                        <?php if (!empty($donation['description'])): ?>
                        <p class="text-muted" style="font-size: 14px; margin-bottom: 10px;">
                            <?= htmlspecialchars(mb_substr($donation['description'], 0, 100)) ?><?= mb_strlen($donation['description']) > 100 ? '...' : '' ?>
                        </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($donation['item_image'])): ?>
                        <div class="mb-2">
                            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($donation['item_image']) ?>" 
                                 alt="<?= htmlspecialchars($donation['title']) ?>" 
                                 class="donation-image-preview">
                        </div>
                        <?php endif; ?>
                        
                        <div class="donation-actions">
                            <?php if ($donation['status'] === 'active'): ?>
                            <button onclick="deleteDonation(<?= $donation['id'] ?>)" 
                                    class="btn btn-sm btn-danger">
                                <i class="fa fa-trash"></i> Supprimer
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php 
$chatbotPath = __DIR__ . '/../../components/chatbot.php';
if (file_exists($chatbotPath)) {
    require_once $chatbotPath;
}
?>

<script src="<?= $baseUrl ?>/view/frontoffice/vendor/jquery/jquery.min.js"></script>
<script src="<?= $baseUrl ?>/view/frontoffice/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    function deleteDonation(id) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette donation ?')) {
            return;
        }
        
        window.location.href = '<?= $baseUrl ?>/index.php?action=donation_delete&id=' + id;
    }
// Notifications JavaScript
var baseUrl = '<?= $baseUrl ?>';

// Toggle notification dropdown
function toggleNotificationDropdown() {
    const dropdown = document.getElementById('notificationDropdown');
    if (dropdown) {
        const isVisible = dropdown.style.display === 'block';
        dropdown.style.display = isVisible ? 'none' : 'block';
        
        if (!isVisible) {
            loadNotifications();
        }
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('notificationDropdown');
    const bell = document.getElementById('notificationBell');
    if (dropdown && bell && !dropdown.contains(event.target) && !bell.contains(event.target)) {
        dropdown.style.display = 'none';
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
        'post_removed': 'fa-ban',
        'donation_request': 'fa-hand-paper',
        'donation_approved': 'fa-check-circle',
        'donation_denied': 'fa-times-circle'
    };
    return icons[type] || 'fa-bell';
}

// Handle notification click
window.handleNotificationClick = function(notificationId, entityType, entityId) {
    markNotificationAsRead(notificationId);
    
    if (entityType === 'conversation') {
        window.location.href = baseUrl + '/view/frontoffice/messages.php?conversation_id=' + entityId;
    } else if (entityType === 'post') {
        window.location.href = baseUrl + '/view/frontoffice/posts.php?post_id=' + entityId;
    } else if (entityType === 'comment') {
        fetch(baseUrl + '/index.php?action=api_post_comment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_comment', id_comment: entityId })
        })
        .then(response => response.json())
        .then(data => {
            if (data && data.success && data.comment && data.comment.id_post) {
                window.location.href = baseUrl + '/view/frontoffice/posts.php?post_id=' + data.comment.id_post + '&comment_id=' + entityId;
            } else {
                window.location.href = baseUrl + '/view/frontoffice/posts.php';
            }
        })
        .catch(error => {
            console.error('Error fetching comment:', error);
            window.location.href = baseUrl + '/view/frontoffice/posts.php';
        });
    } else if (entityType === 'donation') {
        window.location.href = baseUrl + '/index.php?action=donation_show&id=' + entityId;
    }
    
    const dropdown = document.getElementById('notificationDropdown');
    if (dropdown) dropdown.style.display = 'none';
};

// Mark notification as read
function markNotificationAsRead(notificationId) {
    fetch(baseUrl + '/index.php?action=api_notifications&subaction=markRead', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    })
    .catch(error => console.error('Error marking notification as read:', error));
}

// Mark all notifications as read
window.markAllNotificationsRead = function() {
    fetch(baseUrl + '/index.php?action=api_notifications&subaction=markAllRead', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    })
    .catch(error => console.error('Error marking all as read:', error));
};

// Update notification badge
function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.setProperty('display', 'flex', 'important');
        } else {
            badge.style.setProperty('display', 'none', 'important');
        }
    }
}

// Load notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    setInterval(loadNotifications, 30000); // Update every 30 seconds
});

// Escape HTML helper
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
</body>
</html>

