// Chatbot functionality for donation platform
document.addEventListener('DOMContentLoaded', function() {
    const chatbotToggle = document.getElementById('chatbotToggle');
    const chatbotContainer = document.getElementById('chatbotContainer');
    const chatbotMessages = document.getElementById('chatbotMessages');
    const chatbotInput = document.getElementById('chatbotInput');
    const chatbotSendBtn = document.getElementById('chatbotSendBtn');
    const chatbotCloseBtn = document.getElementById('chatbotCloseBtn');
    const chatbotMinimizeBtn = document.getElementById('chatbotMinimizeBtn');

    let isOpen = false;
    let isMinimized = false;

    // Toggle chatbot
    if (chatbotToggle) {
        chatbotToggle.addEventListener('click', function() {
            if (isMinimized) {
                openChatbot();
            } else {
                toggleChatbot();
            }
        });
    }

    // Close chatbot
    if (chatbotCloseBtn) {
        chatbotCloseBtn.addEventListener('click', function() {
            closeChatbot();
        });
    }

    // Minimize chatbot
    if (chatbotMinimizeBtn) {
        chatbotMinimizeBtn.addEventListener('click', function() {
            minimizeChatbot();
        });
    }

    function toggleChatbot() {
        if (isOpen) {
            closeChatbot();
        } else {
            openChatbot();
        }
    }

    function openChatbot() {
        if (chatbotContainer) {
            chatbotContainer.classList.add('open');
            isOpen = true;
            isMinimized = false;
            chatbotInput.focus();
        }
    }

    function closeChatbot() {
        if (chatbotContainer) {
            chatbotContainer.classList.remove('open');
            isOpen = false;
        }
    }

    function minimizeChatbot() {
        if (chatbotContainer) {
            chatbotContainer.classList.add('minimized');
            isMinimized = true;
            isOpen = false;
        }
    }

    // Send message
    function sendMessage() {
        const message = chatbotInput.value.trim();
        if (!message) return;

        // Add user message to chat
        addMessage('user', message);
        chatbotInput.value = '';
        chatbotInput.disabled = true;
        chatbotSendBtn.disabled = true;

        // Show typing indicator
        const typingId = addMessage('bot', '...', true);

        // Send to backend
        fetch('../../controller/chatbot.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: message })
        })
        .then(response => {
            // Check if response is ok
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.error || 'Server error occurred');
                });
            }
            return response.json();
        })
        .then(data => {
            // Remove typing indicator
            removeMessage(typingId);

            if (data.success) {
                addMessage('bot', data.response);
            } else {
                // Show actual error message from backend
                let errorMsg = data.error || 'Sorry, I encountered an error. Please try again later.';
                
                // Special handling for loading errors (Hugging Face free tier)
                if (data.loading_error) {
                    errorMsg = '⏳ ' + errorMsg + '\n\n💡 The model is starting up. Please wait 10-30 seconds and try again.';
                }
                
                addMessage('bot', errorMsg);
                console.error('Chatbot API error:', errorMsg);
            }
        })
        .catch(error => {
            console.error('Chatbot error:', error);
            removeMessage(typingId);
            const errorMsg = error.message || 'Sorry, I couldn\'t connect to the service. Please check your connection and try again.';
            addMessage('bot', errorMsg);
        })
        .finally(() => {
            chatbotInput.disabled = false;
            chatbotSendBtn.disabled = false;
            chatbotInput.focus();
        });
    }

    // Add message to chat
    function addMessage(sender, text, isTyping = false) {
        const messageDiv = document.createElement('div');
        const messageId = 'msg-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        messageDiv.id = messageId;
        messageDiv.className = `chatbot-message chatbot-message-${sender}`;
        
        if (isTyping) {
            messageDiv.classList.add('typing');
        }

        const messageContent = document.createElement('div');
        messageContent.className = 'chatbot-message-content';
        messageContent.textContent = text;
        messageDiv.appendChild(messageContent);

        chatbotMessages.appendChild(messageDiv);
        scrollToBottom();

        return messageId;
    }

    // Remove message
    function removeMessage(messageId) {
        const message = document.getElementById(messageId);
        if (message) {
            message.remove();
        }
    }

    // Scroll to bottom
    function scrollToBottom() {
        if (chatbotMessages) {
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        }
    }

    // Send button click
    if (chatbotSendBtn) {
        chatbotSendBtn.addEventListener('click', sendMessage);
    }

    // Enter key to send
    if (chatbotInput) {
        chatbotInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }

    // Add welcome message
    if (chatbotMessages && chatbotMessages.children.length === 0) {
        addMessage('bot', 'Hello! I\'m here to help you with questions about our donation platform. How can I assist you today?');
    }
});

