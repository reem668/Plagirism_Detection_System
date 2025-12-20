/**
 * Floating Chatbot Widget
 * ML-powered chatbot for plagiarism detection system
 */

(function() {
    'use strict';
    
    // Create chatbot HTML structure
    const chatbotHTML = `
        <div id="chatbotWidget" class="chatbot-widget">
            <div id="chatbotToggle" class="chatbot-toggle">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <span class="chatbot-badge" id="chatbotBadge" style="display: none;">1</span>
            </div>
            <div id="chatbotWindow" class="chatbot-window">
                <div class="chatbot-header">
                    <div class="chatbot-header-content">
                        <div class="chatbot-avatar">🤖</div>
                        <div>
                            <h3>AI Assistant</h3>
                            <p>Plagiarism Detection Helper</p>
                        </div>
                    </div>
                    <button id="chatbotClose" class="chatbot-close">×</button>
                </div>
                <div id="chatbotMessages" class="chatbot-messages">
                    <div class="chatbot-message bot-message">
                        <div class="message-avatar">🤖</div>
                        <div class="message-content">
                            <p>Hello! 👋 I'm your AI assistant. I can help you with:</p>
                            <ul>
                                <li>Submitting your work</li>
                                <li>Understanding plagiarism scores</li>
                                <li>Checking submission status</li>
                                <li>Viewing instructor feedback</li>
                            </ul>
                            <p>How can I assist you today?</p>
                        </div>
                    </div>
                </div>
                <div id="chatbotSuggestions" class="chatbot-suggestions"></div>
                <div class="chatbot-input-container">
                    <input 
                        type="text" 
                        id="chatbotInput" 
                        class="chatbot-input" 
                        placeholder="Type your message..."
                        autocomplete="off"
                    >
                    <button id="chatbotSend" class="chatbot-send">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Inject chatbot HTML
    document.body.insertAdjacentHTML('beforeend', chatbotHTML);
    
    // Get elements
    const widget = document.getElementById('chatbotWidget');
    const toggle = document.getElementById('chatbotToggle');
    const window = document.getElementById('chatbotWindow');
    const closeBtn = document.getElementById('chatbotClose');
    const messagesContainer = document.getElementById('chatbotMessages');
    const input = document.getElementById('chatbotInput');
    const sendBtn = document.getElementById('chatbotSend');
    const suggestionsContainer = document.getElementById('chatbotSuggestions');
    
    let isOpen = false;
    let conversationHistory = [];
    
    // Toggle chatbot
    toggle.addEventListener('click', () => {
        isOpen = !isOpen;
        window.style.display = isOpen ? 'flex' : 'none';
        if (isOpen) {
            input.focus();
            hideBadge();
        }
    });
    
    closeBtn.addEventListener('click', () => {
        isOpen = false;
        window.style.display = 'none';
    });
    
    // Send message
    function sendMessage() {
        const message = input.value.trim();
        if (!message) return;
        
        // Add user message to UI
        addMessage(message, 'user');
        input.value = '';
        
        // Show typing indicator
        const typingId = showTyping();
        
        // Send to backend
        fetch('/Plagirism_Detection_System/app/Views/student/chatbot.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ message: message })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            removeTyping(typingId);
            
            if (data.success) {
                addMessage(data.message, 'bot');
                
                // Show suggestions if available
                if (data.suggestions && data.suggestions.length > 0) {
                    showSuggestions(data.suggestions);
                } else {
                    hideSuggestions();
                }
            } else {
                // Show error message, and log details if available
                let errorMsg = data.message || 'Sorry, I encountered an error. Please try again.';
                if (data.error) {
                    console.error('Chatbot error details:', data.error, data.file, data.line);
                }
                addMessage(errorMsg, 'bot');
            }
        })
        .catch(error => {
            removeTyping(typingId);
            // Check if it's a network error or server error
            if (error.message && error.message.includes('Failed to fetch')) {
                addMessage('Unable to connect to the server. Please check your connection and try again.', 'bot');
            } else {
                addMessage('Sorry, I encountered an error. Please try again.', 'bot');
            }
            console.error('Chatbot error:', error);
        });
    }
    
    // Add message to chat
    function addMessage(text, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbot-message ${sender}-message`;
        
        const avatar = sender === 'bot' ? '🤖' : '👤';
        const content = sender === 'bot' 
            ? `<div class="message-avatar">${avatar}</div><div class="message-content"><p>${formatMessage(text)}</p></div>`
            : `<div class="message-content"><p>${escapeHtml(text)}</p></div><div class="message-avatar">${avatar}</div>`;
        
        messageDiv.innerHTML = content;
        messagesContainer.appendChild(messageDiv);
        scrollToBottom();
        
        // Store in history
        conversationHistory.push({ sender, text });
    }
    
    // Format bot message (support line breaks, lists, etc.)
    function formatMessage(text) {
        // Convert line breaks
        text = escapeHtml(text).replace(/\n/g, '<br>');
        
        // Convert bullet points
        text = text.replace(/^•\s(.+)$/gm, '<li>$1</li>');
        text = text.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');
        
        // Convert numbered lists
        text = text.replace(/^\d+\.\s(.+)$/gm, '<li>$1</li>');
        
        return text;
    }
    
    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Show typing indicator
    function showTyping() {
        const typingId = 'typing-' + Date.now();
        const typingDiv = document.createElement('div');
        typingDiv.id = typingId;
        typingDiv.className = 'chatbot-message bot-message typing-indicator';
        typingDiv.innerHTML = `
            <div class="message-avatar">🤖</div>
            <div class="message-content">
                <div class="typing-dots">
                    <span></span><span></span><span></span>
                </div>
            </div>
        `;
        messagesContainer.appendChild(typingDiv);
        scrollToBottom();
        return typingId;
    }
    
    // Remove typing indicator
    function removeTyping(id) {
        const typing = document.getElementById(id);
        if (typing) typing.remove();
    }
    
    // Show suggestions
    function showSuggestions(suggestions) {
        suggestionsContainer.innerHTML = '';
        suggestions.forEach(suggestion => {
            const btn = document.createElement('button');
            btn.className = 'chatbot-suggestion';
            btn.textContent = suggestion;
            btn.addEventListener('click', () => {
                input.value = suggestion;
                sendMessage();
            });
            suggestionsContainer.appendChild(btn);
        });
        suggestionsContainer.style.display = 'flex';
    }
    
    // Hide suggestions
    function hideSuggestions() {
        suggestionsContainer.style.display = 'none';
    }
    
    // Scroll to bottom
    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Show badge
    function showBadge() {
        const badge = document.getElementById('chatbotBadge');
        if (badge) badge.style.display = 'block';
    }
    
    // Hide badge
    function hideBadge() {
        const badge = document.getElementById('chatbotBadge');
        if (badge) badge.style.display = 'none';
    }
    
    // Event listeners
    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
    
    // Auto-open on page load (optional - can be removed)
    // setTimeout(() => {
    //     if (!isOpen) {
    //         showBadge();
    //     }
    // }, 3000);
})();

