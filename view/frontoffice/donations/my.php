<?php
/**
 * My Donations View
 * User's donations and requests
 */
$pageTitle = 'Mes Donations - Wafra';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= $baseUrl ?>/view/frontoffice/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/fontawesome.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/topbar.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/topbar-enhanced.css">
    
    <style>
        :root {
            --primary-color: #f5a425;
            --text-primary: #1a1a1a;
            --text-secondary: #666;
            --bg-white: #ffffff;
            --bg-light: #f8f9fa;
            --border-color: #e0e0e0;
            --radius: 12px;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-light);
            padding-top: 80px;
        }
        
        .my-donations-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            color: var(--text-secondary);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .donation-item, .request-item {
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 16px;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>

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
        <a href="<?= $baseUrl ?>/index.php?action=donations" class="profile-link" style="background: rgba(245, 164, 37, 0.2); margin-right: 10px;">
            <i class="fa fa-gift"></i>
            <span>Donations</span>
        </a>
        <a href="<?= $baseUrl ?>/view/frontoffice/messages.php" class="profile-link" style="position: relative; margin-right: 10px;">
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

<div class="my-donations-container">
    <h1 style="margin-bottom: 30px;"><i class="fa fa-gift"></i> Mes Donations</h1>
    
    <?php if ($successMessage): ?>
    <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
        <i class="fa fa-check-circle"></i> <?= htmlspecialchars($successMessage) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($errorMessage): ?>
    <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
        <i class="fa fa-exclamation-circle"></i> <?= $errorMessage ?>
    </div>
    <?php endif; ?>
    
    <div class="tabs">
        <button class="tab active" onclick="switchTab('my-donations')">Mes Donations Créées</button>
        <button class="tab" onclick="switchTab('my-requests')">Mes Demandes</button>
    </div>
    
    <div id="my-donations" class="tab-content active">
        <?php if (empty($userDonations)): ?>
        <div class="empty-state">
            <i class="fa fa-gift" style="font-size: 48px; margin-bottom: 16px;"></i>
            <h3>Aucune donation créée</h3>
            <p>Vous n'avez pas encore créé de donation.</p>
            <a href="<?= $baseUrl ?>/index.php?action=donations&create=1" class="btn btn-primary" style="margin-top: 16px;">
                <i class="fa fa-plus"></i> Créer une donation
            </a>
        </div>
        <?php else: ?>
        <?php foreach ($userDonations as $donation): ?>
        <div class="donation-item">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                <div>
                    <h3 style="margin: 0; font-size: 20px;"><?= htmlspecialchars($donation['title']) ?></h3>
                    <p style="margin: 4px 0; color: var(--text-secondary);">
                        <span class="badge" style="background: rgba(245, 164, 37, 0.1); color: var(--primary-color); padding: 4px 12px; border-radius: 12px; font-size: 12px;">
                            <?= htmlspecialchars($donation['category']) ?>
                        </span>
                        <span style="margin-left: 8px;">
                            <i class="fa fa-cubes"></i> <?= $donation['quantity'] ?> disponible(s)
                        </span>
                    </p>
                </div>
                <span class="badge" style="background: <?= $donation['status'] === 'active' ? '#28a745' : '#6c757d' ?>; color: white; padding: 6px 12px; border-radius: 12px; font-size: 12px;">
                    <?= $donation['status'] === 'active' ? 'Active' : 'Remplie' ?>
                </span>
            </div>
            
            <?php if (!empty($donation['description'])): ?>
            <p style="color: var(--text-secondary); margin-bottom: 12px;">
                <?= htmlspecialchars(mb_substr($donation['description'], 0, 150)) ?><?= mb_strlen($donation['description']) > 150 ? '...' : '' ?>
            </p>
            <?php endif; ?>
            
            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">
                <a href="<?= $baseUrl ?>/index.php?action=donation_show&id=<?= $donation['id'] ?>" class="btn btn-primary">
                    <i class="fa fa-eye"></i> Voir
                </a>
                <?php if ($donation['status'] === 'active'): ?>
                <a href="<?= $baseUrl ?>/index.php?action=donation_mark_fulfilled&id=<?= $donation['id'] ?>" 
                   class="btn btn-success"
                   onclick="return confirm('Marquer cette donation comme remplie ?')">
                    <i class="fa fa-check"></i> Marquer comme remplie
                </a>
                <a href="<?= $baseUrl ?>/index.php?action=donation_delete&id=<?= $donation['id'] ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette donation ?')">
                    <i class="fa fa-trash"></i> Supprimer
                </a>
                <?php endif; ?>
            </div>
            
            <?php 
            // Show received requests for this donation
            $requests = $donationRequests[$donation['id']] ?? [];
            if (!empty($requests)): 
            ?>
            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border-color);">
                <h4 style="font-size: 16px; margin-bottom: 12px; color: var(--text-primary);">
                    <i class="fa fa-hand-paper"></i> Demandes reçues (<?= count($requests) ?>)
                </h4>
                <?php foreach ($requests as $req): ?>
                <div style="background: var(--bg-light); padding: 12px; border-radius: 8px; margin-bottom: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                        <div style="flex: 1;">
                            <strong style="color: var(--text-primary);">
                                <?php if ($req['requester_firstname'] || $req['requester_lastname']): ?>
                                    <?= htmlspecialchars(trim(($req['requester_firstname'] ?? '') . ' ' . ($req['requester_lastname'] ?? ''))) ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($req['requester_name'] ?? 'Utilisateur') ?>
                                <?php endif; ?>
                            </strong>
                            <br>
                            <small style="color: var(--text-secondary);">
                                <i class="fa fa-envelope"></i> <?= htmlspecialchars($req['requester_email'] ?? $req['email'] ?? 'N/A') ?>
                            </small>
                            <?php if (!empty($req['message'])): ?>
                            <p style="margin: 8px 0 0 0; color: var(--text-secondary); font-size: 14px;">
                                <?= nl2br(htmlspecialchars($req['message'])) ?>
                            </p>
                            <?php endif; ?>
                            <small style="color: var(--text-secondary);">
                                <i class="fa fa-calendar"></i> <?= date('d/m/Y', strtotime($req['request_date'])) ?>
                            </small>
                        </div>
                        <div style="margin-left: 12px;">
                            <span class="badge" style="background: <?= $req['status'] === 'approved' ? '#28a745' : ($req['status'] === 'denied' ? '#dc3545' : '#ffc107') ?>; color: <?= $req['status'] === 'pending' ? '#000' : 'white' ?>; padding: 4px 8px; border-radius: 6px; font-size: 11px;">
                                <?= $req['status'] === 'pending' ? 'En attente' : ($req['status'] === 'approved' ? 'Approuvée' : 'Refusée') ?>
                            </span>
                        </div>
                    </div>
                    <?php if ($req['status'] === 'pending' && $donation['status'] === 'active'): ?>
                    <div style="display: flex; gap: 8px; margin-top: 8px;">
                        <button onclick="approveRequest(<?= $req['id'] ?>, <?= $donation['id'] ?>)" 
                                class="btn btn-success" style="font-size: 12px; padding: 6px 12px;">
                            <i class="fa fa-check"></i> Accepter
                        </button>
                        <button onclick="rejectRequest(<?= $req['id'] ?>, <?= $donation['id'] ?>)" 
                                class="btn btn-danger" style="font-size: 12px; padding: 6px 12px;">
                            <i class="fa fa-times"></i> Refuser
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div id="my-requests" class="tab-content">
        <?php if (empty($userRequests)): ?>
        <div class="empty-state">
            <i class="fa fa-hand-paper" style="font-size: 48px; margin-bottom: 16px;"></i>
            <h3>Aucune demande</h3>
            <p>Vous n'avez pas encore fait de demande de donation.</p>
        </div>
        <?php else: ?>
        <?php foreach ($userRequests as $request): ?>
        <div class="request-item">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                <div>
                    <h3 style="margin: 0; font-size: 18px;"><?= htmlspecialchars($request['donation_title'] ?? 'Donation') ?></h3>
                    <p style="margin: 4px 0; color: var(--text-secondary); font-size: 14px;">
                        <?php if ($request['donor_firstname'] || $request['donor_lastname']): ?>
                        Donateur: <?= htmlspecialchars(trim(($request['donor_firstname'] ?? '') . ' ' . ($request['donor_lastname'] ?? ''))) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <span class="badge" style="background: <?= $request['status'] === 'approved' ? '#28a745' : ($request['status'] === 'denied' ? '#dc3545' : '#ffc107') ?>; color: <?= $request['status'] === 'pending' ? '#000' : 'white' ?>; padding: 6px 12px; border-radius: 12px; font-size: 12px;">
                    <?= $request['status'] === 'pending' ? 'En attente' : ($request['status'] === 'approved' ? 'Approuvée' : 'Refusée') ?>
                </span>
            </div>
            
            <?php if (!empty($request['message'])): ?>
            <p style="color: var(--text-secondary); margin-bottom: 12px;"><?= nl2br(htmlspecialchars($request['message'])) ?></p>
            <?php endif; ?>
            
            <small style="color: var(--text-secondary);">
                <i class="fa fa-calendar"></i> <?= date('d/m/Y', strtotime($request['request_date'])) ?>
            </small>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="<?= htmlspecialchars($baseUrl) ?>/view/frontoffice/vendor/jquery/jquery.min.js"></script>
<script src="<?= htmlspecialchars($baseUrl) ?>/view/frontoffice/vendor/bootstrap/js/bootstrap.min.js"></script>
<script>
const baseUrl = '<?= htmlspecialchars($baseUrl) ?>';

function switchTab(tabName) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    
    event.target.classList.add('active');
    document.getElementById(tabName).classList.add('active');
}

function approveRequest(requestId, donationId) {
    if (!confirm('Êtes-vous sûr de vouloir accepter cette demande ? Un email avec vos coordonnées sera envoyé au demandeur.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('request_id', requestId);
    formData.append('donation_id', donationId);
    formData.append('status', 'approved');
    
    fetch(baseUrl + '/index.php?action=donation_update_request_status', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid response format');
                }
            });
        }
    })
    .then(data => {
        if (data && data.success) {
            alert('Demande acceptée avec succès ! Un email a été envoyé au demandeur avec vos coordonnées.');
            location.reload();
        } else {
            alert('Erreur: ' + (data?.error || 'Erreur lors de l\'acceptation'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur de connexion. Veuillez réessayer.');
    });
}

function rejectRequest(requestId, donationId) {
    const reason = prompt('Raison du refus (optionnel):');
    if (reason === null) {
        return; // User cancelled
    }
    
    const formData = new FormData();
    formData.append('request_id', requestId);
    formData.append('donation_id', donationId);
    formData.append('status', 'denied');
    formData.append('reason', reason || '');
    
    fetch(baseUrl + '/index.php?action=donation_update_request_status', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid response format');
                }
            });
        }
    })
    .then(data => {
        if (data && data.success) {
            alert('Demande refusée avec succès !');
            location.reload();
        } else {
            alert('Erreur: ' + (data?.error || 'Erreur lors du refus'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur de connexion. Veuillez réessayer.');
    });
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

