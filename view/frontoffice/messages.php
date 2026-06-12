<?php
/**
 * Messages View - Private messaging system
 * Inbox and chat interface
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../config/autoload.php';
require_once __DIR__ . '/../../models/User.php';

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
$userModel = new User($pdo);
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

// Get conversation ID from query string (if viewing a specific conversation)
$conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : null;
$viewMode = $conversationId ? 'chat' : 'inbox';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Messages - Wafra</title>
    
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
        
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f7fa;
            padding-top: 60px !important;
            padding-left: 0 !important;
        }
        
        /* Ensure top-bar is properly positioned - override topbar.css */
        .top-bar {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            z-index: 1000 !important;
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%) !important;
            border-bottom: 2px solid #f5a425 !important;
            padding: 0 30px !important;
            height: 60px !important;
        }
        
        .top-bar-left {
            display: flex !important;
            align-items: center !important;
            gap: 20px !important;
        }
        
        .top-bar-right {
            display: flex !important;
            align-items: center !important;
            gap: 15px !important;
        }
        
        .user-info {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            color: #fff !important;
        }
        
        .user-avatar {
            width: 32px !important;
            height: 32px !important;
            border-radius: 50% !important;
            object-fit: cover !important;
            max-width: 32px !important;
            max-height: 32px !important;
            flex-shrink: 0 !important;
        }
        
        .user-name {
            font-weight: 600 !important;
            color: #ffd700 !important;
        }
        
        .profile-link, .logout-link {
            color: #fff !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            padding: 10px 20px !important;
            border-radius: 25px !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            transition: all 0.3s ease !important;
            white-space: nowrap !important;
        }
        
        .profile-link {
            background: linear-gradient(135deg, #f5a425 0%, #e5941f 100%) !important;
            box-shadow: 0 2px 8px rgba(245, 164, 37, 0.3) !important;
        }
        
        .profile-link:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(245, 164, 37, 0.5) !important;
        }
        
        .logout-link {
            background: transparent !important;
            border: 2px solid rgba(255,255,255,0.3) !important;
        }
        
        .logout-link:hover {
            background: rgba(255,255,255,0.1) !important;
            border-color: rgba(255,255,255,0.5) !important;
        }
        
        /* Fix image sizing globally */
        img {
            max-width: 100%;
            height: auto;
        }
        
        /* Ensure all avatars have proper sizing */
        .conversation-avatar,
        .chat-header-avatar,
        .message-avatar,
        .user-avatar {
            object-fit: cover !important;
            flex-shrink: 0 !important;
        }
        
        .messages-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: flex;
            gap: 20px;
            height: calc(100vh - 120px);
        }
        
        /* Inbox Sidebar */
        .inbox-sidebar {
            width: 350px;
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .inbox-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .inbox-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .inbox-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .conversation-item {
            padding: 16px 20px;
            border-bottom: 1px solid var(--bg-light);
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .conversation-item:hover {
            background-color: var(--bg-light);
        }
        
        .conversation-item.active {
            background-color: #fff5e6;
            border-left: 3px solid var(--primary-color);
        }
        
        .conversation-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            max-width: 50px;
            max-height: 50px;
        }
        
        .conversation-info {
            flex: 1;
            min-width: 0;
        }
        
        .conversation-name {
            font-weight: 600;
            font-size: 15px;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .conversation-preview {
            font-size: 13px;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
        }
        
        .conversation-time {
            font-size: 12px;
            color: var(--text-light);
        }
        
        .unread-badge {
            background: var(--primary-color);
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 600;
            min-width: 20px;
            text-align: center;
        }
        
        /* Chat Area */
        .chat-area {
            flex: 1;
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .chat-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--bg-white);
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .chat-header-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            max-width: 48px;
            max-height: 48px;
            flex-shrink: 0;
            border: 2px solid var(--primary-color);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .chat-header-info {
            flex: 1;
            min-width: 0;
        }
        
        .chat-header-name {
            font-weight: 600;
            font-size: 18px;
            color: var(--text-primary);
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .chat-context {
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-height: 0; /* Important for flex children */
        }
        
        #chatView {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
        }
        
        .message {
            display: flex;
            gap: 8px;
            max-width: 70%;
            animation: fadeIn 0.3s;
            margin-bottom: 8px;
        }
        
        .message.sent {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        
        .message-wrapper {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            max-width: 32px;
            max-height: 32px;
        }
        
        .message-content {
            background: var(--bg-light);
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            line-height: 1.5;
            color: var(--text-primary);
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .message.sent .message-content {
            background: var(--primary-color);
            color: white;
        }
        
        .message-time {
            font-size: 11px;
            color: var(--text-light);
            margin-top: 4px;
            text-align: right;
        }
        
        .message.sent .message-time {
            text-align: left;
        }
        
        .chat-input-area {
            padding: 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 12px;
        }
        
        .chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-family: inherit;
            resize: none;
            max-height: 120px;
        }
        
        .chat-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .send-btn {
            padding: 12px 24px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .send-btn:hover:not(:disabled) {
            background: var(--primary-hover);
        }
        
        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            text-align: center;
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
            font-size: 14px;
            margin-bottom: 20px;
            max-width: 400px;
        }
        
        .empty-state-cta {
            margin-top: 20px;
        }
        
        .empty-state-cta .btn {
            padding: 12px 24px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        
        .empty-state-cta .btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 164, 37, 0.3);
        }
        
        /* Context Banner in Chat */
        .chat-context-banner {
            background: linear-gradient(135deg, #fff5e6 0%, #ffe8cc 100%);
            border-left: 4px solid var(--primary-color);
            padding: 16px 20px;
            margin-bottom: 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .chat-context-banner-title {
            font-weight: 600;
            font-size: 14px;
            color: var(--text-primary);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .chat-context-banner-content {
            font-size: 13px;
            color: var(--text-secondary);
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .chat-context-banner-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            margin-top: 4px;
        }
        
        .chat-context-banner-link:hover {
            text-decoration: underline;
        }
        
        /* Trust Signals */
        .trust-signal {
            font-size: 12px;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
        }
        
        .trust-signal i {
            color: #28a745;
        }
        
        /* Contact Button Styles */
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
        
        .contact-btn i {
            font-size: 16px;
        }
        
        .contact-helper-text {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .contact-helper-text i {
            color: #6c757d;
        }
        
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
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .messages-container {
                flex-direction: column;
                height: auto;
            }
            
            .inbox-sidebar {
                width: 100%;
                max-height: 300px;
            }
            
            .chat-area {
                min-height: 500px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <?php
    // Include topbar from posts.php pattern
    $userName = trim(($currentUser['firstname'] ?? '') . ' ' . ($currentUser['lastname'] ?? ''));
    if (empty($userName)) {
        $userName = 'Utilisateur';
    }
    ?>
    <div class="top-bar">
        <div class="top-bar-left">
            <a href="<?= $baseUrl ?>/view/frontoffice/index.php" style="text-decoration: none;">
                <h4 style="margin: 0; color: #ffd700; font-weight: 700;">Wafra</h4>
            </a>
        </div>
        <div class="top-bar-right">
            <div class="user-info">
                <img src="<?= htmlspecialchars($userAvatar) ?>" alt="Avatar" class="user-avatar" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; margin-right: 8px;">
                <span class="user-name"><?= htmlspecialchars($userName) ?></span>
            </div>
            <!-- Notifications Bell -->
            <div class="notification-container" style="position: relative; margin-right: 10px;">
                <button class="notification-bell" id="notificationBell" onclick="toggleNotificationDropdown()" aria-label="Notifications" style="background: transparent; border: none; color: #fff; font-size: 20px; cursor: pointer; padding: 8px 12px; position: relative;">
                    <i class="fa fa-bell"></i>
                    <span class="notification-badge" id="notificationBadge" style="position: absolute; top: 5px; right: 5px; background: #e74c3c; color: #fff; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; display: none; align-items: center; justify-content: center; font-weight: bold;">0</span>
                </button>
                <div class="notification-dropdown" id="notificationDropdown" style="position: absolute; top: 100%; right: 0; background: #fff; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); width: 350px; max-height: 500px; overflow-y: auto; z-index: 1000; margin-top: 10px; display: none;">
                    <div class="notification-header" style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                        <h5 style="margin: 0; font-weight: 600;">Notifications</h5>
                        <button onclick="markAllNotificationsRead()" style="background: none; border: none; color: #f5a425; cursor: pointer; font-size: 12px;">Tout marquer comme lu</button>
                    </div>
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
    
    <div class="messages-container">
        <!-- Inbox Sidebar -->
        <div class="inbox-sidebar">
            <div class="inbox-header">
                <h2>Messages</h2>
            </div>
            <div class="inbox-list" id="inboxList" style="display: block; visibility: visible;">
                <div class="loading">
                    <div class="spinner"></div>
                    <div>Chargement...</div>
                </div>
            </div>
        </div>
        
        <!-- Chat Area -->
        <div class="chat-area" id="chatArea">
            <div class="empty-state" id="emptyState">
                <div class="empty-state-icon">
                    <i class="fa fa-comments"></i>
                </div>
                <div class="empty-state-title">Sélectionnez une conversation</div>
                <div class="empty-state-text">Choisissez une conversation dans la liste pour commencer à discuter</div>
            </div>
            
            <div id="chatView" style="display: none;">
                <div class="chat-header" id="chatHeader"></div>
                <div class="chat-messages" id="chatMessages"></div>
                <div class="chat-input-area">
                    <textarea class="chat-input" id="messageInput" placeholder="Tapez votre message..." rows="1"></textarea>
                    <button class="send-btn" id="sendBtn" onclick="sendMessage()">
                        <i class="fa fa-paper-plane"></i> Envoyer
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const baseUrl = '<?= $baseUrl ?>';
        const currentUserId = <?= $userId ?>;
        let currentConversationId = <?= $conversationId ? $conversationId : 'null' ?>;
        let messagePollInterval = null;
        let conversationsData = []; // Store conversations globally
        
        // Handle browser back/forward buttons
        window.addEventListener('popstate', function(event) {
            const urlParams = new URLSearchParams(window.location.search);
            const convId = urlParams.get('conversation_id');
            if (convId) {
                currentConversationId = parseInt(convId);
                loadChat(currentConversationId);
            } else {
                currentConversationId = null;
                document.getElementById('emptyState').style.display = 'flex';
                document.getElementById('chatView').style.display = 'none';
            }
        });
        
        // Load conversations on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded, loading conversations...');
            console.log('Current conversation ID from URL:', currentConversationId);
            
            // Check if user_id is in URL (for creating new conversation)
            const urlParams = new URLSearchParams(window.location.search);
            const otherUserId = urlParams.get('user_id');
            const entityType = urlParams.get('entity_type');
            const entityId = urlParams.get('entity_id');
            
            if (otherUserId && !currentConversationId) {
                // Create or get conversation with the specified user
                console.log('Creating/getting conversation with user:', otherUserId, 'entity:', entityType, entityId);
                createOrGetConversation(parseInt(otherUserId), entityType, entityId ? parseInt(entityId) : null);
            } else {
                loadConversations();
                
                // If conversation_id is in URL, load it after conversations are loaded
                if (currentConversationId) {
                    setTimeout(() => {
                        console.log('Loading chat for conversation:', currentConversationId);
                        loadChat(currentConversationId);
                    }, 500); // Wait for conversations to load first
                }
            }
        });
        
        // Create or get conversation with a user
        function createOrGetConversation(otherUserId, entityType, entityId) {
            const data = {
                other_user_id: otherUserId,
                entity_type: entityType || null,
                entity_id: entityId || null
            };
            
            fetch(baseUrl + '/index.php?action=api_message&subaction=create_conversation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data && data.success && data.conversation) {
                    console.log('Conversation created/retrieved:', data.conversation);
                    // Update URL to use conversation_id instead of user_id
                    const newUrl = baseUrl + '/view/frontoffice/messages.php?conversation_id=' + data.conversation.id;
                    window.history.replaceState({ conversationId: data.conversation.id }, '', newUrl);
                    currentConversationId = data.conversation.id;
                    
                    // Reload conversations to include the new one, then load the chat
                    loadConversations();
                    setTimeout(() => {
                        loadChat(data.conversation.id);
                    }, 500);
                } else {
                    console.error('Failed to create conversation:', data);
                    alert('Erreur lors de la création de la conversation: ' + (data.error || 'Erreur inconnue'));
                    // Still load conversations normally
                    loadConversations();
                }
            })
            .catch(error => {
                console.error('Error creating conversation:', error);
                alert('Erreur de connexion lors de la création de la conversation');
                // Still load conversations normally
                loadConversations();
            });
        }
        
        // Load conversations list
        function loadConversations() {
            fetch(baseUrl + '/index.php?action=api_message&subaction=get_conversations', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(async response => {
                    const responseText = await response.text();
                    console.log('Conversations response status:', response.status);
                    console.log('Conversations response text (first 1000 chars):', responseText.substring(0, 1000));
                    console.log('Conversations response text length:', responseText.length);
                    
                    // Try to parse and show full debug info
                    try {
                        const fullData = JSON.parse(responseText);
                        console.log('Full parsed response:', fullData);
                        if (fullData.debug) {
                            console.log('=== FULL DEBUG INFO ===');
                            console.log('Direct SQL test count:', fullData.debug.direct_sql_test_count);
                            console.log('Direct SQL test result:', fullData.debug.direct_sql_test_result);
                            console.log('Service conversations count:', fullData.debug.service_conversations_count);
                            console.log('Service returned sample:', fullData.debug.service_returned_sample);
                            console.log('API formatted count:', fullData.debug.api_formatted_count);
                            console.log('API formatted sample:', fullData.debug.api_formatted_sample);
                            console.log('=======================');
                        }
                    } catch (e) {
                        console.error('Error parsing JSON:', e);
                    }
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    try {
                        return JSON.parse(responseText);
                    } catch (e) {
                        console.error('Invalid JSON response:', responseText.substring(0, 200));
                        throw new Error('Invalid JSON response');
                    }
                })
                .then(data => {
                    console.log('Conversations API response:', data);
                    
                    // Log debug info if available
                    if (data && data.debug) {
                        console.log('=== DEBUG INFO ===');
                        console.log('User ID:', data.debug.user_id);
                        console.log('Raw conversations in DB (for this user):', data.debug.raw_conversations_count);
                        console.log('Raw conversations data:', data.debug.raw_conversations);
                        console.log('Sample conversations (all):', data.debug.sample_all_conversations);
                        console.log('Direct SQL test count:', data.debug.direct_sql_test_count);
                        console.log('Direct SQL test result:', data.debug.direct_sql_test_result);
                        console.log('Direct SQL test error:', data.debug.direct_sql_test_error);
                        console.log('Service returned conversations:', data.debug.service_conversations_count);
                        console.log('Service returned sample:', data.debug.service_returned_sample);
                        console.log('API formatted count:', data.debug.api_formatted_count);
                        console.log('API formatted sample:', data.debug.api_formatted_sample);
                        if (data.debug.db_check_error) {
                            console.error('DB check error:', data.debug.db_check_error);
                        }
                        console.log('==================');
                    }
                    
                    if (data && data.success) {
                        // Store conversations data globally
                        conversationsData = Array.isArray(data.conversations) ? data.conversations : [];
                        console.log('Stored conversations:', conversationsData.length, conversationsData);
                        
                        if (conversationsData.length > 0) {
                            console.log('Calling renderConversations with', conversationsData.length, 'conversations');
                            renderConversations(conversationsData);
                        } else {
                            console.log('No conversations in response, showing empty state');
                            // Show debug info in empty state if available
                            if (data.debug && data.debug.raw_conversations_count > 0) {
                                console.warn('WARNING: Conversations exist in DB but were filtered out!');
                                console.warn('This might be due to is_blocked filter or user ID mismatch');
                            }
                            renderConversations([]);
                        }
                        
                        // If we have a current conversation, update header
                        if (currentConversationId) {
                            const conversation = conversationsData.find(c => c.id === currentConversationId);
                            if (conversation) {
                                console.log('Found current conversation:', conversation);
                                updateChatHeaderWithUserInfo(conversation.other_user_name, conversation.other_user_avatar);
                            } else {
                                console.log('Current conversation not found in list:', currentConversationId);
                            }
                        }
                    } else {
                        console.error('Failed to load conversations - API returned error:', data);
                        const container = document.getElementById('inboxList');
                        if (container) {
                            container.innerHTML = '<div class="empty-state"><div class="empty-state-text">Erreur lors du chargement: ' + (data?.error || 'Erreur inconnue') + '</div></div>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading conversations:', error);
                    document.getElementById('inboxList').innerHTML = 
                        '<div class="empty-state"><div class="empty-state-text">Erreur de connexion</div></div>';
                });
        }
        
        // Render conversations list
        function renderConversations(conversations) {
            const container = document.getElementById('inboxList');
            if (!container) {
                console.error('inboxList container not found!');
                return;
            }
            
            console.log('renderConversations called with:', conversations.length, 'conversations');
            console.log('Conversations data:', conversations);
            
            if (!conversations || conversations.length === 0) {
                console.log('No conversations, showing empty state');
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fa fa-comments"></i></div>
                        <div class="empty-state-title">Vous n'avez pas encore de conversations</div>
                        <div class="empty-state-text">
                            Vous pouvez contacter d'autres utilisateurs pour discuter de dons, demandes ou posts.
                            <br><br>
                            <strong>Comment commencer ?</strong><br>
                            • Ouvrez une demande de don et cliquez sur "Contacter le propriétaire"<br>
                            • Après un don, vous pouvez envoyer un message au bénéficiaire<br>
                            • Consultez les profils des utilisateurs pour les contacter
                        </div>
                        <div class="empty-state-cta">
                            <a href="${baseUrl}/view/frontoffice/posts.php" class="btn">
                                <i class="fa fa-search"></i> Parcourir les demandes
                            </a>
                        </div>
                        <div class="trust-signal" style="margin-top: 24px; justify-content: center;">
                            <i class="fa fa-shield-alt"></i>
                            <span>Messages privés et sécurisés • Modération par les administrateurs</span>
                        </div>
                    </div>
                `;
                return;
            }
            
            console.log('Rendering', conversations.length, 'conversations');
            
            const html = conversations.map(conv => {
                console.log('Rendering conversation:', conv.id, conv.other_user_name);
                const timeStr = formatTime(conv.last_message_at);
                const preview = conv.last_message ? (conv.last_message.length > 50 ? conv.last_message.substring(0, 50) + '...' : conv.last_message) : 'Aucun message';
                const isActive = conv.id === currentConversationId;
                
                return `
                    <div class="conversation-item ${isActive ? 'active' : ''}" 
                         onclick="loadChat(${conv.id}); return false;"
                         data-conversation-id="${conv.id}"
                         role="button"
                         tabindex="0"
                         onkeydown="if(event.key==='Enter'||event.key===' '){loadChat(${conv.id}); event.preventDefault();}">
                        <img src="${escapeHtml(conv.other_user_avatar)}" alt="${escapeHtml(conv.other_user_name)}" 
                             class="conversation-avatar" 
                             onerror="this.src='${baseUrl}/view/frontoffice/assets/images/default-avatar.png'">
                        <div class="conversation-info">
                            <div class="conversation-name">${escapeHtml(conv.other_user_name || 'Utilisateur')}</div>
                            <div class="conversation-preview">${escapeHtml(preview)}</div>
                        </div>
                        <div class="conversation-meta">
                            <div class="conversation-time">${timeStr}</div>
                            ${conv.unread_count > 0 ? `<div class="unread-badge">${conv.unread_count}</div>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
            
            console.log('Generated HTML length:', html.length);
            console.log('HTML preview (first 500 chars):', html.substring(0, 500));
            container.innerHTML = html;
            console.log('Conversations rendered successfully');
            
            // Verify the HTML was inserted
            const items = container.querySelectorAll('.conversation-item');
            console.log('Conversation items found in DOM after render:', items.length);
            if (items.length === 0 && conversations.length > 0) {
                console.error('WARNING: No conversation items found in DOM after rendering!');
                console.error('Container innerHTML length:', container.innerHTML.length);
                console.error('Container element:', container);
            } else {
                console.log('Successfully rendered', items.length, 'conversation items');
            }
        }
        
        // Load chat for a conversation
        function loadChat(conversationId, event) {
            // Prevent any default behavior if event is provided
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            // Update URL without reloading page
            const newUrl = baseUrl + '/view/frontoffice/messages.php?conversation_id=' + conversationId;
            if (window.location.href !== newUrl) {
                window.history.pushState({ conversationId: conversationId }, '', newUrl);
            }
            
            currentConversationId = conversationId;
            
            // Update active state
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            const activeItem = document.querySelector(`[onclick="loadChat(${conversationId})"]`);
            if (activeItem) {
                activeItem.classList.add('active');
            }
            
            // Find conversation data from stored conversations
            const conversation = conversationsData.find(c => c.id === conversationId);
            if (conversation) {
                // Update chat header immediately with user info from conversation data
                updateChatHeaderWithUserInfo(conversation.other_user_name, conversation.other_user_avatar);
            } else {
                // Fallback: try to get from DOM element
                if (activeItem) {
                    const otherUserName = activeItem.querySelector('.conversation-name')?.textContent || 'Utilisateur';
                    const otherUserAvatar = activeItem.querySelector('.conversation-avatar')?.src || baseUrl + '/view/frontoffice/assets/images/default-avatar.png';
                    updateChatHeaderWithUserInfo(otherUserName, otherUserAvatar);
                }
            }
            
            // Show chat view, hide empty state
            const emptyState = document.getElementById('emptyState');
            const chatView = document.getElementById('chatView');
            
            if (emptyState) {
                emptyState.style.display = 'none';
            }
            if (chatView) {
                chatView.style.display = 'flex';
                // Ensure proper layout
                chatView.style.flexDirection = 'column';
                chatView.style.height = '100%';
            } else {
                console.error('chatView element not found!');
            }
            
            // Load messages
            loadMessages(conversationId);
            
            // Start polling for new messages
            if (messagePollInterval) {
                clearInterval(messagePollInterval);
            }
            messagePollInterval = setInterval(() => {
                loadMessages(conversationId, true);
            }, 5000);
        }
        
        // Update chat header with user info
        function updateChatHeaderWithUserInfo(name, avatar) {
            const chatHeader = document.getElementById('chatHeader');
            if (!chatHeader) {
                console.error('Chat header element not found');
                return;
            }
            
            // Ensure we have valid values
            const displayName = name || 'Utilisateur';
            const displayAvatar = avatar || baseUrl + '/view/frontoffice/assets/images/default-avatar.png';
            
            console.log('Updating chat header:', { name: displayName, avatar: displayAvatar });
            
            chatHeader.innerHTML = `
                <img src="${escapeHtml(displayAvatar)}" alt="${escapeHtml(displayName)}" class="chat-header-avatar"
                     onerror="this.src='${baseUrl}/view/frontoffice/assets/images/default-avatar.png'; this.onerror=null;">
                <div class="chat-header-info">
                    <div class="chat-header-name">${escapeHtml(displayName)}</div>
                    <div class="chat-context">Messages privés</div>
                </div>
            `;
        }
        
        // Load messages for a conversation
        function loadMessages(conversationId, silent = false) {
            if (!silent) {
                document.getElementById('chatMessages').innerHTML = 
                    '<div class="loading"><div class="spinner"></div><div>Chargement...</div></div>';
            }
            
            fetch(baseUrl + `/index.php?action=api_message&subaction=get_messages&conversation_id=${conversationId}`, {
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
                        console.error('Invalid JSON response:', responseText.substring(0, 200));
                        throw new Error('Invalid JSON response');
                    }
                })
                .then(data => {
                    if (data.success) {
                        renderMessages(data.messages);
                        updateChatHeader(data.messages);
                    }
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                    if (!silent) {
                        document.getElementById('chatMessages').innerHTML = 
                            '<div class="empty-state"><div class="empty-state-text">Erreur lors du chargement</div></div>';
                    }
                });
        }
        
        // Render messages
        function renderMessages(messages) {
            const container = document.getElementById('chatMessages');
            
            if (messages.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-text">Aucun message. Commencez la conversation !</div>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = messages.map(msg => {
                const timeStr = formatTime(msg.created_at);
                const isSent = msg.is_sender;
                
                return `
                    <div class="message ${isSent ? 'sent' : ''}">
                        <img src="${escapeHtml(msg.sender_avatar)}" alt="${escapeHtml(msg.sender_name)}" 
                             class="message-avatar"
                             onerror="this.src='${baseUrl}/view/frontoffice/assets/images/default-avatar.png'">
                        <div>
                            <div class="message-content">${escapeHtml(msg.message).replace(/\n/g, '<br>')}</div>
                            <div class="message-time">${timeStr}</div>
                        </div>
                    </div>
                `;
            }).join('');
            
            // Auto-scroll to bottom
            container.scrollTop = container.scrollHeight;
        }
        
        // Update chat header with context
        function updateChatHeader(messages) {
            const conversationId = currentConversationId;
            if (!conversationId) return;
            
            // Get conversation info from stored data
            const conversation = conversationsData.find(c => c.id === conversationId);
            if (conversation) {
                // Update header with user info from conversation data
                updateChatHeaderWithUserInfo(conversation.other_user_name, conversation.other_user_avatar);
            } else {
                // Fallback: try to get from DOM
                const activeItem = document.querySelector('.conversation-item.active');
                if (activeItem) {
                    const name = activeItem.querySelector('.conversation-name')?.textContent || 'Utilisateur';
                    const avatar = activeItem.querySelector('.conversation-avatar')?.src || baseUrl + '/view/frontoffice/assets/images/default-avatar.png';
                    updateChatHeaderWithUserInfo(name, avatar);
                } else {
                    return;
                }
            }
            
            // Get conversation context from API
            fetch(baseUrl + `/index.php?action=api_message&subaction=get_conversation_context&conversation_id=${conversationId}`, {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            })
            .then(async response => {
                const responseText = await response.text();
                try {
                    return response.ok ? JSON.parse(responseText) : null;
                } catch (e) {
                    return null;
                }
            })
            .then(contextData => {
                let contextBanner = '';
                if (contextData && contextData.success && contextData.context) {
                    const ctx = contextData.context;
                    if (ctx.entity_type === 'post') {
                        contextBanner = `
                            <div class="chat-context-banner">
                                <div class="chat-context-banner-title">
                                    <i class="fa fa-info-circle"></i>
                                    Vous discutez concernant :
                                </div>
                                <div class="chat-context-banner-content">
                                    <span><strong>📌 Post :</strong> ${escapeHtml(ctx.entity_title || 'Post')}</span>
                                    ${ctx.entity_link ? `<a href="${escapeHtml(ctx.entity_link)}" class="chat-context-banner-link">Voir le post →</a>` : ''}
                                </div>
                            </div>
                        `;
                    } else if (ctx.entity_type === 'donation' || ctx.entity_type === 'request') {
                        contextBanner = `
                            <div class="chat-context-banner">
                                <div class="chat-context-banner-title">
                                    <i class="fa fa-info-circle"></i>
                                    Vous discutez concernant :
                                </div>
                                <div class="chat-context-banner-content">
                                    <span><strong>📌 Demande de don :</strong> ${escapeHtml(ctx.entity_title || 'Demande')}</span>
                                    ${ctx.entity_amount ? `<span><strong>💰 Montant :</strong> ${escapeHtml(ctx.entity_amount)}</span>` : ''}
                                    ${ctx.entity_link ? `<a href="${escapeHtml(ctx.entity_link)}" class="chat-context-banner-link">Voir la demande →</a>` : ''}
                                </div>
                            </div>
                        `;
                    }
                }
                
                // Header is already updated by updateChatHeaderWithUserInfo, just add context banner
                if (contextBanner) {
                    const chatView = document.getElementById('chatView');
                    const existingBanner = chatView.querySelector('.chat-context-banner');
                    if (existingBanner) {
                        existingBanner.remove();
                    }
                    const chatHeader = document.getElementById('chatHeader');
                    chatHeader.insertAdjacentHTML('afterend', contextBanner);
                }
            })
            .catch(error => {
                console.error('Error loading context:', error);
                // Header is already set, no need to update
            });
        }
        
        // Send message
        function sendMessage() {
            if (!currentConversationId) return;
            
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            const sendBtn = document.getElementById('sendBtn');
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
            
            fetch(baseUrl + '/index.php?action=api_message&subaction=send_message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    conversation_id: currentConversationId,
                    message: message
                })
            })
            .then(async response => {
                const responseText = await response.text();
                console.log('Send message response status:', response.status);
                console.log('Send message response text:', responseText);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                try {
                    return JSON.parse(responseText);
                } catch (e) {
                    console.error('Invalid JSON response:', responseText.substring(0, 200));
                    throw new Error('Invalid JSON response: ' + responseText.substring(0, 200));
                }
            })
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadMessages(currentConversationId, true);
                    loadConversations(); // Refresh inbox
                } else {
                    alert('Erreur: ' + (data.error || 'Impossible d\'envoyer le message'));
                }
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fa fa-paper-plane"></i> Envoyer';
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Erreur: ' + (error.message || 'Impossible d\'envoyer le message. Vérifiez votre connexion.'));
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fa fa-paper-plane"></i> Envoyer';
            });
        }
        
        // Enable send button when input has text
        document.getElementById('messageInput').addEventListener('input', function() {
            const sendBtn = document.getElementById('sendBtn');
            sendBtn.disabled = this.value.trim().length === 0;
        });
        
        // Send on Enter (Shift+Enter for new line)
        document.getElementById('messageInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // Helper functions
        function formatTime(timestamp) {
            if (!timestamp) return '';
            const date = new Date(timestamp);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if (diffDays > 7) {
                return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
            } else if (diffDays > 0) {
                return diffDays + 'j';
            } else if (diffHours > 0) {
                return diffHours + 'h';
            } else if (diffMins > 0) {
                return diffMins + 'min';
            } else {
                return 'À l\'instant';
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // ========== NOTIFICATION SYSTEM ==========
        let notificationPollInterval = null;
        const NOTIFICATION_POLL_INTERVAL = 30000; // 30 seconds

        // Toggle notification dropdown (enhanced version)
        window.toggleNotificationDropdown = function() {
            const dropdown = document.getElementById('notificationDropdown');
            if (!dropdown) return;
            
            const isVisible = dropdown.classList.contains('show');
            if (isVisible) {
                dropdown.classList.remove('show');
                dropdown.style.display = 'none';
            } else {
                dropdown.classList.add('show');
                dropdown.style.display = 'block';
                loadNotifications();
            }
        };

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const container = document.querySelector('.notification-container');
            const dropdown = document.getElementById('notificationDropdown');
            if (container && dropdown && !container.contains(event.target) && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
                dropdown.style.display = 'none';
            }
        });

        // Load notifications
        function loadNotifications() {
            const list = document.getElementById('notificationList');
            if (!list) return;
            list.innerHTML = '<div class="text-center" style="padding: 20px; color: #999;"><i class="fa fa-spinner fa-spin"></i> Chargement...</div>';
            
            fetch(baseUrl + '/index.php?action=api_notifications&subaction=getUnread&limit=10')
                .then(async response => {
                    const responseText = await response.text();
                    if (!response.ok) {
                        throw new Error('Network error');
                    }
                    try {
                        return JSON.parse(responseText);
                    } catch (e) {
                        console.error('Invalid JSON response:', responseText.substring(0, 200));
                        return { success: false, notifications: [] };
                    }
                })
                .then(data => {
                    if (data.success && data.notifications) {
                        renderNotifications(data.notifications);
                        updateNotificationBadge(data.unread_count || 0);
                    } else {
                        if (list) list.innerHTML = '<div class="text-center" style="padding: 20px; color: #999;">Aucune notification</div>';
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
                const timeStr = formatTime(notif.created_at);
                const entityType = notif.entity_type || '';
                const entityId = notif.entity_id || 0;
                const isRead = notif.is_read || false;
                
                return `
                    <div class="notification-item ${unreadClass}" style="padding: 12px 15px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s; ${!isRead ? 'background: #f8f9fa; font-weight: 600;' : ''}" onclick="handleNotificationClick(${notif.id}, '${escapeHtml(entityType)}', ${entityId})">
                        <div style="font-size: 14px; color: #333;">${escapeHtml(notif.message)}</div>
                        <div style="font-size: 11px; color: #999; margin-top: 4px;">${timeStr}</div>
                    </div>
                `;
            }).join('');
        }
        
        // Handle notification click
        window.handleNotificationClick = function(notificationId, entityType, entityId) {
            console.log('Notification clicked:', { notificationId, entityType, entityId });
            
            // Mark as read
            markNotificationRead(notificationId);
            
            // Navigate based on entity type
            if (entityType === 'conversation') {
                // Redirect to messages page with conversation ID
                console.log('Redirecting to conversation:', entityId);
                window.location.href = baseUrl + '/view/frontoffice/messages.php?conversation_id=' + entityId;
            } else if (entityType === 'post') {
                window.location.href = baseUrl + '/view/frontoffice/posts.php?post_id=' + entityId;
            } else if (entityType === 'comment') {
                window.location.href = baseUrl + '/view/frontoffice/posts.php';
            }
            
            // Close dropdown
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown) {
                dropdown.classList.remove('show');
                dropdown.style.display = 'none';
            }
        };
        
        function markNotificationRead(notificationId) {
            fetch(baseUrl + '/index.php?action=api_notifications&subaction=markAsRead&id=' + notificationId, {
                method: 'POST'
            })
            .then(async response => {
                const responseText = await response.text();
                try {
                    return JSON.parse(responseText);
                } catch (e) {
                    return { success: false };
                }
            })
            .then(data => {
                if (data.success) {
                    loadNotifications();
                    updateNotificationCount();
                }
            })
            .catch(error => console.error('Error marking notification as read:', error));
        }
        
        function markAllNotificationsRead() {
            fetch(baseUrl + '/index.php?action=api_notifications&subaction=markAllAsRead', {
                method: 'POST'
            })
            .then(async response => {
                const responseText = await response.text();
                try {
                    return JSON.parse(responseText);
                } catch (e) {
                    return { success: false };
                }
            })
            .then(data => {
                if (data.success) {
                    loadNotifications();
                    updateNotificationCount();
                }
            })
            .catch(error => console.error('Error marking all as read:', error));
        }
        
        function updateNotificationCount() {
            fetch(baseUrl + '/index.php?action=api_notifications&subaction=getCount')
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
                        updateNotificationBadge(data.count || 0);
                    }
                })
                .catch(error => console.error('Error updating notification count:', error));
        }
        
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
        
        // Load notification count on page load
        document.addEventListener('DOMContentLoaded', function() {
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
        });
    </script>

<!-- Chatbot -->
<?php 
// Pass $baseUrl to chatbot component
$baseUrl = $baseUrl ?? (defined('BASE_URL') ? BASE_URL : '');
include __DIR__ . '/../components/chatbot.php'; 
?>

</body>
</html>

