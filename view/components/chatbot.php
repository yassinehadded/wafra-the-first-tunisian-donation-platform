<?php
/**
 * Chatbot Component
 * Reusable chatbot component for all pages
 */
// Use $baseUrl if available, otherwise fallback to BASE_URL
$chatbotBaseUrl = isset($baseUrl) ? $baseUrl : (defined('BASE_URL') ? BASE_URL : '');
$chatbotApiUrl = $chatbotBaseUrl . '/index.php?action=chatbot';
?>
<!-- Chatbot Toggle Button -->
<button id="chatbotToggle" class="chatbot-toggle" aria-label="Open chatbot">
    <i class="fa fa-comments"></i>
</button>

<!-- Chatbot Container -->
<div id="chatbotContainer" class="chatbot-container">
    <div class="chatbot-header">
        <h3>
            <i class="fa fa-robot"></i>
            <?php echo isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'Admin Assistant' : 'Donation Assistant'; ?>
        </h3>
        <div class="chatbot-header-actions">
            <button id="chatbotMinimizeBtn" class="chatbot-header-btn" aria-label="Minimize">
                <i class="fa fa-minus"></i>
            </button>
            <button id="chatbotCloseBtn" class="chatbot-header-btn" aria-label="Close">
                <i class="fa fa-times"></i>
            </button>
        </div>
    </div>
    <div id="chatbotMessages" class="chatbot-messages"></div>
    <div class="chatbot-input-container">
        <input 
            type="text" 
            id="chatbotInput" 
            class="chatbot-input" 
            placeholder="<?php echo isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'Ask about platform administration...' : 'Ask about donations...'; ?>"
            autocomplete="off"
        >
        <button id="chatbotSendBtn" class="chatbot-send-btn" aria-label="Send message">
            <i class="fa fa-paper-plane"></i>
        </button>
    </div>
</div>

<!-- Chatbot CSS is loaded in the main page head -->
<script>
    // Chatbot API URL
    window.CHATBOT_API_URL = '<?php echo $chatbotApiUrl; ?>';
</script>
<script src="<?php echo isset($baseUrl) ? $baseUrl : BASE_URL; ?>/view/components/chatbot.js"></script>

