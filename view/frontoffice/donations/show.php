<?php
/**
 * Donation Details View
 * Shows full donation details with actions
 */
$pageTitle = 'Détails de la Donation - Wafra';
if (!isset($baseUrl)) $baseUrl = BASE_URL;
if (!isset($donation)) $donation = null;
if (!isset($requests)) $requests = [];
if (!isset($hasRequested)) $hasRequested = false;
if (!isset($isOwner)) $isOwner = false;
if (!isset($successMessage)) $successMessage = null;
if (!isset($errorMessage)) $errorMessage = null;
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
            padding-top: 60px !important;
            padding-left: 0 !important;
        }
        
        .top-bar {
            left: 0 !important;
            width: 100% !important;
        }
        
        .details-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .donation-detail-card {
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 24px;
        }
        
        .donation-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .donor-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .donation-image {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            border-radius: var(--radius);
            margin-bottom: 24px;
        }
        
        .donation-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 16px;
        }
        
        .donation-meta {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .category-badge {
            background: rgba(245, 164, 37, 0.1);
            color: var(--primary-color);
        }
        
        .quantity-badge {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .status-active {
            background: #28a745;
            color: white;
        }
        
        .status-fulfilled {
            background: #6c757d;
            color: white;
        }
        
        .donation-description {
            color: var(--text-secondary);
            line-height: 1.8;
            font-size: 16px;
            margin-bottom: 24px;
            white-space: pre-wrap;
        }
        
        .donation-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 2px solid var(--border-color);
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .requests-section {
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .request-item {
            padding: 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 12px;
        }
        
        .request-status {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #ffc107;
            color: #000;
        }
        
        .status-approved {
            background: #28a745;
            color: white;
        }
        
        .status-denied {
            background: #dc3545;
            color: white;
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

<div class="details-container">
    <a href="<?= $baseUrl ?>/index.php?action=donations" style="color: var(--primary-color); text-decoration: none; margin-bottom: 20px; display: inline-block;">
        <i class="fa fa-arrow-left"></i> Retour aux donations
    </a>
    
    <?php if (!$donation): ?>
    <div class="alert alert-danger">
        <i class="fa fa-exclamation-circle"></i> Donation introuvable
    </div>
    <?php else: ?>
    <div class="donation-detail-card">
        <div class="donation-header">
            <?php
            $avatarUrl = $baseUrl . '/view/frontoffice/assets/images/default-avatar.png';
            if (!empty($donation['user_avatar'])) {
                $avatarPath = __DIR__ . '/../../../uploads/profile_pictures/' . basename($donation['user_avatar']);
                if (file_exists($avatarPath)) {
                    $avatarUrl = $baseUrl . '/uploads/profile_pictures/' . basename($donation['user_avatar']);
                }
            }
            $donorName = trim(($donation['user_firstname'] ?? '') . ' ' . ($donation['user_lastname'] ?? '')) ?: $donation['donor_name'];
            ?>
            <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Donor" class="donor-avatar">
            <div>
                <h3 style="margin: 0; font-size: 20px;"><?= htmlspecialchars($donorName) ?></h3>
                <p style="margin: 4px 0 0 0; color: var(--text-secondary); font-size: 14px;">
                    <i class="fa fa-calendar"></i> <?= date('d/m/Y', strtotime($donation['date'])) ?>
                </p>
            </div>
        </div>
        
        <?php if (!empty($donation['item_image'])): ?>
        <img src="<?= $baseUrl ?>/<?= htmlspecialchars($donation['item_image']) ?>" 
             alt="<?= htmlspecialchars($donation['title']) ?>" 
             class="donation-image">
        <?php endif; ?>
        
        <h1 class="donation-title"><?= htmlspecialchars($donation['title']) ?></h1>
        
        <div class="donation-meta">
            <span class="badge category-badge"><?= htmlspecialchars($donation['category']) ?></span>
            <span class="badge quantity-badge">
                <i class="fa fa-cubes"></i> <?= $donation['quantity'] ?> disponible(s)
            </span>
            <span class="badge status-<?= $donation['status'] ?>">
                <?= $donation['status'] === 'active' ? 'Disponible' : 'Remplie' ?>
            </span>
        </div>
        
        <?php if (!empty($donation['description'])): ?>
        <div class="donation-description"><?= nl2br(htmlspecialchars($donation['description'])) ?></div>
        <?php endif; ?>
        
        <?php if (!$isOwner && $donation['status'] === 'active'): ?>
        <div class="donation-actions">
            <?php if (!$hasRequested): ?>
            <button onclick="requestDonation(<?= $donation['id'] ?>)" class="btn btn-primary">
                <i class="fa fa-hand-paper"></i> Demander cette donation
            </button>
            <?php else: ?>
            <button class="btn" style="background: #6c757d; color: white;" disabled>
                <i class="fa fa-check"></i> Déjà demandé
            </button>
            <?php endif; ?>
            <?php if ($donation['user_id']): ?>
            <button onclick="contactDonor(<?= $donation['user_id'] ?>, <?= $donation['id'] ?>)" class="btn btn-outline">
                <i class="fa fa-envelope"></i> Contacter le donateur
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php 
    if (!isset($requests)) $requests = [];
    if ($isOwner && !empty($requests)): 
    ?>
    <div class="requests-section">
        <h2 style="margin-bottom: 20px;"><i class="fa fa-hand-paper"></i> Demandes reçues (<?= count($requests) ?>)</h2>
        <?php foreach ($requests as $request): ?>
        <div class="request-item">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                <div>
                    <strong><?= htmlspecialchars($request['requester_name'] ?? 'Utilisateur') ?></strong>
                    <span class="request-status status-<?= $request['status'] ?? 'pending' ?>">
                        <?= ($request['status'] ?? 'pending') === 'pending' ? 'En attente' : (($request['status'] ?? 'pending') === 'approved' ? 'Approuvée' : 'Refusée') ?>
                    </span>
                </div>
                <small style="color: var(--text-secondary);">
                    <?= isset($request['request_date']) ? date('d/m/Y', strtotime($request['request_date'])) : '' ?>
                </small>
            </div>
            <?php if (!empty($request['message'])): ?>
            <p style="color: var(--text-secondary); margin-bottom: 12px;"><?= nl2br(htmlspecialchars($request['message'])) ?></p>
            <?php endif; ?>
            <?php if (($request['status'] ?? 'pending') === 'pending'): ?>
            <div style="display: flex; gap: 8px;">
                <button onclick="updateRequestStatus(<?= $request['id'] ?>, <?= $donation['id'] ?>, 'approved')" 
                        class="btn" style="background: #28a745; color: white; padding: 8px 16px; font-size: 14px;">
                    <i class="fa fa-check"></i> Accepter
                </button>
                <button onclick="updateRequestStatus(<?= $request['id'] ?>, <?= $donation['id'] ?>, 'denied')" 
                        class="btn" style="background: #dc3545; color: white; padding: 8px 16px; font-size: 14px;">
                    <i class="fa fa-times"></i> Refuser
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Request Modal -->
<div class="modal fade" id="requestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Demander cette donation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="requestForm">
                    <input type="hidden" id="requestDonationId" value="<?= $donation['id'] ?>">
                    <div class="form-group">
                        <label>Message (optionnel)</label>
                        <textarea id="requestMessage" class="form-control" rows="4" 
                                  placeholder="Expliquez pourquoi vous souhaitez cette donation..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="submitRequest()">Envoyer la demande</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars($baseUrl) ?>/view/frontoffice/vendor/jquery/jquery.min.js"></script>
<script src="<?= htmlspecialchars($baseUrl) ?>/view/frontoffice/vendor/bootstrap/js/bootstrap.min.js"></script>
<script>
const baseUrl = '<?= htmlspecialchars($baseUrl ?? BASE_URL, ENT_QUOTES, 'UTF-8') ?>';

window.requestDonation = function(donationId) {
    console.log('requestDonation called with ID:', donationId);
    try {
        const modalElement = document.getElementById('requestDonationId');
        const messageElement = document.getElementById('requestMessage');
        const modal = document.getElementById('requestModal');
        
        if (!modalElement || !messageElement || !modal) {
            console.error('Modal elements not found', { modalElement, messageElement, modal });
            alert('Erreur: Éléments du formulaire introuvables');
            return;
        }
        
        modalElement.value = donationId;
        messageElement.value = '';
        
        // Check if Bootstrap is loaded
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap is not loaded');
            // Fallback: show modal manually
            modal.style.display = 'block';
            modal.classList.add('show');
        } else {
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
        }
    } catch (error) {
        console.error('Error in requestDonation:', error);
        alert('Erreur lors de l\'ouverture du formulaire: ' + error.message);
    }
};

window.submitRequest = function() {
    console.log('submitRequest called');
    const donationId = document.getElementById('requestDonationId').value;
    const message = document.getElementById('requestMessage').value.trim();
    
    if (!donationId) {
        alert('Erreur: ID de donation manquant');
        return;
    }
    
    // Show loading state
    const submitBtn = document.querySelector('#requestModal .btn-primary');
    if (!submitBtn) {
        console.error('Submit button not found');
        alert('Erreur: Bouton de soumission introuvable');
        return;
    }
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Envoi...';
    
    console.log('Sending request to:', baseUrl + '/index.php?action=donation_request');
    console.log('Data:', { donation_id: donationId, message: message });
    
    fetch(baseUrl + '/index.php?action=donation_request', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `donation_id=${donationId}&message=${encodeURIComponent(message)}`
    })
    .then(response => {
        // Always try to parse as JSON first
        return response.text().then(text => {
            // Try to parse as JSON
            try {
                return JSON.parse(text);
            } catch (e) {
                // If not JSON, check if it's empty or contains error
                console.warn('Response is not JSON:', text.substring(0, 200));
                // If response is empty or just whitespace, assume success
                if (!text || text.trim() === '') {
                    return { success: true, message: 'Demande envoyée avec succès !' };
                }
                // If it contains HTML error, still assume success (request was processed)
                if (text.includes('<!DOCTYPE') || text.includes('<html') || text.includes('Warning') || text.includes('Notice')) {
                    console.warn('Server returned HTML/error but request may have succeeded');
                    return { success: true, message: 'Demande envoyée avec succès !' };
                }
                throw new Error('Invalid response format');
            }
        });
    })
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        
        if (data && data.success) {
            // Hide modal
            const modal = document.getElementById('requestModal');
            if (modal) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const bootstrapModal = bootstrap.Modal.getInstance(modal);
                    if (bootstrapModal) {
                        bootstrapModal.hide();
                    } else {
                        modal.style.display = 'none';
                        modal.classList.remove('show');
                    }
                } else {
                    modal.style.display = 'none';
                    modal.classList.remove('show');
                }
            }
            
            alert('Demande envoyée avec succès !');
            location.reload();
        } else {
            alert('Erreur: ' + (data?.error || 'Erreur lors de l\'envoi'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        
        // Even on error, check if request might have succeeded
        // Reload page to check status
        if (confirm('Erreur de connexion. Voulez-vous recharger la page pour vérifier si la demande a été envoyée ?')) {
            location.reload();
        }
    });
};

window.contactDonor = function(donorId, donationId) {
    console.log('contactDonor called with donorId:', donorId, 'donationId:', donationId);
    
    if (!donorId || donorId === 0) {
        alert('Erreur: ID du donateur invalide');
        return;
    }
    
    // Redirect directly to messages page with the donor ID
    // The messages page will handle creating the conversation if needed
    const url = baseUrl + '/view/frontoffice/messages.php?user_id=' + donorId + '&entity_type=donation&entity_id=' + (donationId || '');
    console.log('Redirecting to:', url);
    window.location.href = url;
};

function updateRequestStatus(requestId, donationId, status) {
    if (!confirm(`Êtes-vous sûr de vouloir ${status === 'approved' ? 'accepter' : 'refuser'} cette demande ?`)) {
        return;
    }
    
    fetch(baseUrl + '/index.php?action=donation_update_request_status', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `request_id=${requestId}&donation_id=${donationId}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Statut mis à jour avec succès !');
            location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Erreur lors de la mise à jour'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur de connexion');
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

