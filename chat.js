document.addEventListener('DOMContentLoaded', () => {
    const chatbotHtml = `
    <div id="chatbot-ui">
        <button id="chatbot-toggle" aria-label="Toggle chat">
            <svg viewBox="0 0 24 24" fill="none" class="chat-icon"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2v10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <svg viewBox="0 0 24 24" fill="none" class="close-icon" style="display:none;"><path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <div id="chatbot-window" class="hidden">
            <div class="chatbot-header">
                <strong>Fitness AI Assistant</strong>
                <p>Ask about gym, health & our packages</p>
            </div>
            <div id="chatbot-messages">
                <div class="message bot">
                    Hello! I'm your Alamin Fitness AI. How can I help you today?
                </div>
            </div>
            <form id="chatbot-form">
                <input type="text" id="chatbot-input" placeholder="Type a message..." required />
                <button type="submit">Send</button>
            </form>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', chatbotHtml);

    const toggleBtn = document.getElementById('chatbot-toggle');
    const windowEl = document.getElementById('chatbot-window');
    const form = document.getElementById('chatbot-form');
    const input = document.getElementById('chatbot-input');
    const messagesEl = document.getElementById('chatbot-messages');
    const chatIcon = toggleBtn.querySelector('.chat-icon');
    const closeIcon = toggleBtn.querySelector('.close-icon');

    toggleBtn.addEventListener('click', () => {
        windowEl.classList.toggle('hidden');
        if(windowEl.classList.contains('hidden')) {
            chatIcon.style.display = 'block';
            closeIcon.style.display = 'none';
        } else {
            chatIcon.style.display = 'none';
            closeIcon.style.display = 'block';
            input.focus();
        }
    });

    const addMessage = (text, sender) => {
        const div = document.createElement('div');
        div.className = `message ${sender}`;
        div.textContent = text;
        messagesEl.appendChild(div);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const text = input.value.trim();
        if(!text) return;

        addMessage(text, 'user');
        input.value = '';

        const loader = document.createElement('div');
        loader.className = 'message bot loader';
        loader.textContent = 'Typing...';
        messagesEl.appendChild(loader);
        messagesEl.scrollTop = messagesEl.scrollHeight;

        try {
            const response = await fetch('chat_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            });

            const data = await response.json();
            loader.remove();
            
            if(data.reply) {
                addMessage(data.reply, 'bot');
            } else {
                addMessage('Something went wrong. Please try again.', 'bot');
            }
        } catch (err) {
            loader.remove();
            addMessage('Network error. Please try again.', 'bot');
        }
    });
});
