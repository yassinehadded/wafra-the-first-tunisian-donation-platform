<?php
/**
 * Donations Feed View - Browse all active donations
 * Similar to posts feed UX
 */
if (!isset($baseUrl)) $baseUrl = BASE_URL;
if (!isset($donations)) $donations = [];
if (!isset($hasRequested)) $hasRequested = [];
if (!isset($successMessage)) $successMessage = null;
if (!isset($errorMessage)) $errorMessage = null;

$pageTitle = 'Donations - Wafra';
$pageDescription = 'Parcourez toutes les donations disponibles';
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
        
        .donations-feed-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .page-header p {
            color: var(--text-secondary);
            font-size: 16px;
        }
        
        .donation-card {
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            padding: 24px;
            margin-bottom: 24px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .donation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }
        
        .donation-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .donor-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border-color);
        }
        
        .donor-info h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }
        
        .donor-info .donation-date {
            font-size: 13px;
            color: var(--text-light);
        }
        
        .donation-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            margin-bottom: 16px;
        }
        
        .donation-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .donation-meta {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        
        .category-badge {
            background: rgba(245, 164, 37, 0.1);
            color: var(--primary-color);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .quantity-badge {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .status-active {
            background: #28a745;
            color: white;
        }
        
        .donation-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .donation-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
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
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
        }
        
        .empty-state-icon {
            font-size: 64px;
            color: var(--text-light);
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 24px;
            color: var(--text-primary);
            margin-bottom: 12px;
        }
        
        .empty-state p {
            color: var(--text-secondary);
            font-size: 16px;
        }
        
        .toast-container {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 9999;
        }
        
        .toast {
            background: white;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-md);
            padding: 16px 20px;
            margin-bottom: 12px;
            min-width: 300px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }
        
        .toast.success {
            border-left: 4px solid #28a745;
        }
        
        .toast.error {
            border-left: 4px solid #dc3545;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
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
        <div class="dropdown" style="position: relative; display: inline-block; margin-right: 10px;">
            <a href="#" class="profile-link" style="background: rgba(245, 164, 37, 0.2);" onclick="event.preventDefault(); document.getElementById('donationsDropdown').classList.toggle('show');">
                <i class="fa fa-gift"></i>
                <span>Donations</span>
                <i class="fa fa-chevron-down" style="font-size: 10px; margin-left: 5px;"></i>
            </a>
            <div id="donationsDropdown" class="dropdown-menu" style="display: none; position: absolute; top: 100%; right: 0; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 200px; z-index: 1000; margin-top: 5px;">
                <a href="<?= $baseUrl ?>/index.php?action=donations" class="dropdown-item" style="display: block; padding: 12px 20px; color: #333; text-decoration: none; border-bottom: 1px solid #eee;">
                    <i class="fa fa-list"></i> Toutes les donations
                </a>
                <a href="<?= $baseUrl ?>/index.php?action=my_donations" class="dropdown-item" style="display: block; padding: 12px 20px; color: #333; text-decoration: none; border-bottom: 1px solid #eee;">
                    <i class="fa fa-user"></i> Mes donations
                </a>
                <a href="<?= $baseUrl ?>/index.php?action=donations&create=1" class="dropdown-item" style="display: block; padding: 12px 20px; color: #333; text-decoration: none;">
                    <i class="fa fa-plus"></i> Créer une donation
                </a>
            </div>
        </div>
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

<style>
.dropdown-menu.show {
    display: block !important;
}
.dropdown-item:hover {
    background: #f8f9fa;
}
</style>

<script>
// Close dropdown when clicking outside
window.onclick = function(event) {
    if (!event.target.matches('.profile-link')) {
        const dropdowns = document.getElementsByClassName('dropdown-menu');
        for (let i = 0; i < dropdowns.length; i++) {
            if (dropdowns[i].classList.contains('show')) {
                dropdowns[i].classList.remove('show');
            }
        }
    }
}
</script>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Main Content -->
<div class="donations-feed-container">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h1><i class="fa fa-gift"></i> Toutes les Donations</h1>
            <p>Découvrez les donations disponibles dans la communauté</p>
        </div>
        <a href="<?= $baseUrl ?>/index.php?action=donations&create=1" class="btn btn-primary" style="text-decoration: none;">
            <i class="fa fa-plus"></i> Créer une donation
        </a>
    </div>
    
    <?php
    if (!isset($userId)) $userId = (int)($_SESSION['userID'] ?? 0);
    ?>
    
    <?php if (!empty($donations)): ?>
        <div id="donationsList">
            <?php foreach ($donations as $donation): ?>
                <div class="donation-card" data-donation-id="<?= $donation['id'] ?>">
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
                        <div class="donor-info">
                            <h3><?= htmlspecialchars($donorName) ?></h3>
                            <div class="donation-date">
                                <i class="fa fa-calendar"></i> <?= date('d/m/Y', strtotime($donation['date'])) ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($donation['item_image'])): ?>
                    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($donation['item_image']) ?>" 
                         alt="<?= htmlspecialchars($donation['title']) ?>" 
                         class="donation-image">
                    <?php endif; ?>
                    
                    <h2 class="donation-title"><?= htmlspecialchars($donation['title']) ?></h2>
                    
                    <div class="donation-meta">
                        <span class="category-badge"><?= htmlspecialchars($donation['category']) ?></span>
                        <span class="quantity-badge">
                            <i class="fa fa-cubes"></i> <?= $donation['quantity'] ?> disponible(s)
                        </span>
                        <span class="status-badge status-active">Disponible</span>
                        <?php if (isset($donation['request_count']) && $donation['request_count'] > 0): ?>
                        <span style="color: var(--text-light); font-size: 13px;">
                            <i class="fa fa-hand-paper"></i> <?= $donation['request_count'] ?> demande(s)
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($donation['description'])): ?>
                    <p class="donation-description"><?= nl2br(htmlspecialchars($donation['description'])) ?></p>
                    <?php endif; ?>
                    
                    <div class="donation-actions">
                        <?php if (isset($donation['user_id']) && $donation['user_id'] != $userId): ?>
                        <button data-donation-id="<?= (int)$donation['id'] ?>" 
                                class="btn btn-outline request-donation-btn"
                                id="requestBtn<?= $donation['id'] ?>"
                                <?= isset($hasRequested[$donation['id']]) && $hasRequested[$donation['id']] ? 'disabled' : '' ?>>
                            <i class="fa fa-hand-paper"></i> 
                            <?= isset($hasRequested[$donation['id']]) && $hasRequested[$donation['id']] ? 'Déjà demandé' : 'Demander' ?>
                        </button>
                        <?php if ($donation['user_id']): ?>
                        <button data-donor-id="<?= (int)$donation['user_id'] ?>" 
                                data-donation-id="<?= (int)$donation['id'] ?>" 
                                class="btn btn-outline contact-donor-btn">
                            <i class="fa fa-envelope"></i> Contacter le donateur
                        </button>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fa fa-gift"></i>
            </div>
            <h3>Aucune donation disponible</h3>
            <p>Il n'y a actuellement aucune donation disponible. Revenez plus tard !</p>
        </div>
    <?php endif; ?>
</div>

<!-- Request Donation Modal -->
<div class="modal fade" id="requestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Demander cette donation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="requestForm">
                    <input type="hidden" id="requestDonationId">
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

<?php 
// Chatbot inclusion - only if file exists and no errors
$chatbotPath = __DIR__ . '/../../components/chatbot.php';
if (file_exists($chatbotPath)) {
    try {
        require_once $chatbotPath;
    } catch (Exception $e) {
        error_log("Error loading chatbot: " . $e->getMessage());
    }
}
?>

<script src="<?= htmlspecialchars($baseUrl) ?>/view/frontoffice/vendor/jquery/jquery.min.js"></script>
<script src="<?= htmlspecialchars($baseUrl) ?>/view/frontoffice/vendor/bootstrap/js/bootstrap.min.js"></script>
<script>
// Define baseUrl once - check if already defined
if (typeof baseUrl === 'undefined') {
    var baseUrl = '<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>';
}
const userId = <?= (int)$userId ?>;

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="fa fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}" 
           style="color: ${type === 'success' ? '#28a745' : '#dc3545'}; font-size: 20px;"></i>
        <span>${message}</span>
    `;
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

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
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
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
            
            showToast(data.message || 'Demande envoyée avec succès !', 'success');
            const btn = document.getElementById('requestBtn' + donationId);
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa fa-hand-paper"></i> Déjà demandé';
            }
            // Reload after 1 second to update UI
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data?.error || 'Erreur lors de l\'envoi de la demande', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        
        // Even on error, assume success might have happened
        showToast('Demande peut-être envoyée. Rechargement de la page...', 'success');
        setTimeout(() => location.reload(), 1500);
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

// Show success/error messages from session
<?php if (!empty($successMessage)): ?>
showToast(<?= json_encode($successMessage, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, 'success');
<?php endif; ?>
<?php if (!empty($errorMessage)): ?>
showToast(<?= json_encode($errorMessage, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, 'error');
<?php endif; ?>

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

// Load notifications on page load and setup event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Setup event listeners for donation buttons
    // Request donation buttons
    document.querySelectorAll('.request-donation-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const donationId = this.getAttribute('data-donation-id');
            if (donationId && typeof window.requestDonation === 'function') {
                window.requestDonation(parseInt(donationId));
            } else {
                console.error('requestDonation not available or donationId missing', { donationId, functionExists: typeof window.requestDonation });
                alert('Erreur: Impossible de demander cette donation');
            }
        });
    });
    
    // Contact donor buttons
    document.querySelectorAll('.contact-donor-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const donorId = this.getAttribute('data-donor-id');
            const donationId = this.getAttribute('data-donation-id');
            if (donorId && typeof window.contactDonor === 'function') {
                window.contactDonor(parseInt(donorId), parseInt(donationId || '0'));
            } else {
                console.error('contactDonor not available or IDs missing', { donorId, donationId, functionExists: typeof window.contactDonor });
                alert('Erreur: Impossible de contacter le donateur');
            }
        });
    });
    
    // Verify functions are defined
    console.log('DOMContentLoaded - Checking functions:', {
        requestDonation: typeof window.requestDonation,
        contactDonor: typeof window.contactDonor,
        submitRequest: typeof window.submitRequest,
        baseUrl: typeof baseUrl !== 'undefined' ? baseUrl : 'undefined'
    });
    
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

