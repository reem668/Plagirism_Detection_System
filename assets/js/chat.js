document.addEventListener('DOMContentLoaded', function() {
    const chatSelect   = document.getElementById('chatInstructorSelect');
    const chatWindow   = document.getElementById('chatWindow');
    const chatForm     = document.getElementById('chatForm');
    const chatInput    = document.getElementById('chatMessage');
    const chatSendBtn  = document.getElementById('chatSendBtn');

    if (!chatSelect || !chatWindow || !chatForm) return;

    let instructorId = null;   // student is logged in, choosing an instructor
    let fetchTimer   = null;

    function renderMessages(messages) {
        chatWindow.innerHTML = '';

        if (!messages.length) {
            chatWindow.innerHTML = '<p class="chat-placeholder">No messages yet</p>';
            return;
        }

        messages.forEach(msg => {
            const msgDiv = document.createElement('div');
            msgDiv.className =
                'chat-message ' +
                (msg.is_mine ? 'chat-message-sent' : 'chat-message-received');

            msgDiv.innerHTML = `
                <div class="chat-bubble">
                    <strong>${msg.sender_name}</strong><br>
                    ${msg.message}<br>
                    <small>${msg.time}</small>
                </div>
            `;

            chatWindow.appendChild(msgDiv);
        });

        chatWindow.scrollTop = chatWindow.scrollHeight;
    }

    async function fetchMessages() {
        if (!instructorId) return;

        try {
<<<<<<< HEAD
            const baseUrl = window.BASE_URL || '/Plagirism_Detection_System';

            // Student page: PHP endpoint expects ?instructor_id=...
            const res  = await fetch(
                `${baseUrl}/app/Views/student/chat_fetch.php?instructor_id=${encodeURIComponent(instructorId)}`,
                { cache: 'no-store' }
            );

            const data = await res.json();

            if (data.success) {
                renderMessages(data.messages);
            } else {
                console.error('Failed to fetch messages:', data.error || data.message);
            }
        } catch (err) {
            console.error('Chat fetch error:', err);
        }
=======
            const res = await fetch(`chat_fetch.php?instructor_id=${instructorId}`, {cache:'no-store'});
            const data = await res.json();
            if (data.success) renderMessages(data.messages);
        } catch(err) { console.error(err); }
>>>>>>> parent of 95da686 (done trash and restore thing , added courses search)
    }

    chatSelect.addEventListener('change', function() {
        instructorId = this.value || null;

        if (fetchTimer) clearInterval(fetchTimer);

        if (!instructorId) {
            chatInput.disabled   = true;
            chatSendBtn.disabled = true;
            chatWindow.innerHTML =
                '<p class="chat-placeholder">Select an instructor</p>';
            return;
        }

        chatInput.disabled   = false;
        chatSendBtn.disabled = false;
        chatWindow.innerHTML =
            '<p class="chat-placeholder">Loading messages...</p>';

        fetchMessages();
        fetchTimer = setInterval(fetchMessages, 3000);
    });

    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        if (!instructorId) return alert('Select an instructor first');

        const msg = chatInput.value.trim();
        if (!msg) return;

        chatInput.value = '';

        const formData = new FormData();
        formData.append('_csrf', window.CSRF_TOKEN);
        formData.append('instructor_id', instructorId);
        formData.append('message', msg);

        try {
<<<<<<< HEAD
            const baseUrl = window.BASE_URL || '/Plagirism_Detection_System';

            const res  = await fetch(
                `${baseUrl}/app/Views/student/chat_send.php`,
                { method: 'POST', body: formData }
            );

            const data = await res.json();

            if (data.success) {
                fetchMessages();
            } else {
                alert(data.message || 'Failed to send');
            }
        } catch (err) {
            console.error('Chat send error:', err);
            alert('Network error');
        }
=======
            const res = await fetch('chat_send.php', { method:'POST', body: formData });
            const data = await res.json();
            if (data.success) fetchMessages();
            else alert(data.message || 'Failed to send');
        } catch(err) { console.error(err); alert('Network error'); }
>>>>>>> parent of 95da686 (done trash and restore thing , added courses search)
    });

    window.addEventListener('beforeunload', () => {
        if (fetchTimer) clearInterval(fetchTimer);
    });
});
