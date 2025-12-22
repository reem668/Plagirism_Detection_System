document.addEventListener('DOMContentLoaded', function() {
    const chatSelect   = document.getElementById('chatInstructorSelect');
    const chatWindow   = document.getElementById('chatWindow');
    const chatForm     = document.getElementById('chatForm');
    const chatInput    = document.getElementById('chatMessage');
    const chatSendBtn  = document.getElementById('chatSendBtn');

    if (!chatSelect || !chatWindow || !chatForm) return;

    let instructorId = null;
    let fetchTimer = null;

    function renderMessages(messages) {
        chatWindow.innerHTML = '';
        if (!messages.length) {
            chatWindow.innerHTML = '<p class="chat-placeholder">No messages yet</p>';
            return;
        }
        messages.forEach(msg => {
            const msgDiv = document.createElement('div');
            msgDiv.className = 'chat-message ' + (msg.sender === 'student' ? 'chat-message-sent' : 'chat-message-received');
            msgDiv.innerHTML = `
                <div class="chat-bubble">
                    <strong>${msg.sender_name}</strong><br>
                    ${msg.message}<br>
                    <small>${msg.time}</small>
                </div>`;
            chatWindow.appendChild(msgDiv);
        });
        chatWindow.scrollTop = chatWindow.scrollHeight;
    }

    async function fetchMessages() {
        if (!instructorId) return;
        try {
            // Get BASE_URL from window or use default
            const baseUrl = window.BASE_URL || '/Plagirism_Detection_System';
            const res = await fetch(`${baseUrl}/ajax/chat_fetch.php?instructor_id=${instructorId}`, {cache:'no-store'});
            const data = await res.json();
            if (data.success) renderMessages(data.messages);
        } catch(err) { console.error('Chat fetch error:', err); }
    }

    chatSelect.addEventListener('change', function() {
        instructorId = this.value || null;
        if (fetchTimer) clearInterval(fetchTimer);
        if (!instructorId) {
            chatInput.disabled = true;
            chatSendBtn.disabled = true;
            chatWindow.innerHTML = '<p class="chat-placeholder">Select an instructor</p>';
            return;
        }
        chatInput.disabled = false;
        chatSendBtn.disabled = false;
        chatWindow.innerHTML = '<p class="chat-placeholder">Loading messages...</p>';
        fetchMessages();
        fetchTimer = setInterval(fetchMessages, 3000);
    });

    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        if (!instructorId) return alert('Select instructor first');
        const msg = chatInput.value.trim();
        if (!msg) return;
        chatInput.value = '';
        const formData = new FormData();
        formData.append('_csrf', window.CSRF_TOKEN);
        formData.append('instructor_id', instructorId);
        formData.append('message', msg);
        try {
            // Get BASE_URL from window or use default
            const baseUrl = window.BASE_URL || '/Plagirism_Detection_System';
            const res = await fetch(`${baseUrl}/ajax/chat_send.php`, { method:'POST', body: formData });
            const data = await res.json();
            if (data.success) fetchMessages();
            else alert(data.message || 'Failed to send');
        } catch(err) { console.error('Chat send error:', err); alert('Network error'); }
    });

    window.addEventListener('beforeunload', () => { if (fetchTimer) clearInterval(fetchTimer); });
});
