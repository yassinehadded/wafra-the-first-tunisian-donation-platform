// Chatbot functionality
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

    if (chatbotToggle) {
        chatbotToggle.addEventListener('click', function() {
            if (isMinimized) {
                openChatbot();
            } else {
                toggleChatbot();
            }
        });
    }

    if (chatbotCloseBtn) {
        chatbotCloseBtn.addEventListener('click', function() {
            closeChatbot();
        });
    }

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
            if (chatbotInput) chatbotInput.focus();
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

    function sendMessage() {
        const message = chatbotInput.value.trim();
        if (!message) return;

        addMessage('user', message);
        chatbotInput.value = '';
        chatbotInput.disabled = true;
        chatbotSendBtn.disabled = true;

        const typingId = addMessage('bot', '...', true);

        const apiUrl = window.CHATBOT_API_URL || '../index.php?action=chatbot';
        
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: message })
        })
        .then(response => {
            // Check if response is actually JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned invalid response. Please try again.');
                });
            }
            
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.error || 'Server error occurred');
                }).catch(err => {
                    // If JSON parsing fails, throw a generic error
                    if (err instanceof SyntaxError) {
                        throw new Error('Server returned invalid response. Please try again.');
                    }
                    throw err;
                });
            }
            return response.json();
        })
        .then(data => {
            removeMessage(typingId);

            if (data.success) {
                addMessage('bot', data.response);
            } else {
                let errorMsg = data.error || 'Sorry, I encountered an error. Please try again later.';
                
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
            if (chatbotInput) chatbotInput.focus();
        });
    }

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

    function removeMessage(messageId) {
        const message = document.getElementById(messageId);
        if (message) {
            message.remove();
        }
    }

    function scrollToBottom() {
        if (chatbotMessages) {
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        }
    }

    if (chatbotSendBtn) {
        chatbotSendBtn.addEventListener('click', sendMessage);
    }

    if (chatbotInput) {
        chatbotInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }

    if (chatbotMessages && chatbotMessages.children.length === 0) {
        const welcomeMsg = window.location.pathname.includes('backoffice') || window.location.pathname.includes('dashboard')
            ? 'Hello! I\'m here to help you with platform administration. How can I assist you today?'
            : 'Hello! I\'m here to help you with questions about our donation platform. How can I assist you today?';
        addMessage('bot', welcomeMsg);
    }
});

