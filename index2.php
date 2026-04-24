<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Management DB Chatbot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 h-screen flex flex-col items-center justify-center p-4">

    <!-- Chat Container -->
    <div class="w-full max-w-4xl bg-slate-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col h-[85vh] border border-slate-700">
        
        <!-- Header -->
        <header class="bg-indigo-600 p-4 shrink-0 flex items-center justify-between">
            <h1 class="text-xl font-bold tracking-tight">AI Gym Database Manager</h1>
            <span class="text-xs bg-indigo-500 px-2 py-1 rounded text-indigo-100">Connected to MySQL</span>
        </header>

        <!-- Messages Area -->
        <div id="chat-messages" class="flex-1 overflow-y-auto p-4 space-y-4">
            <!-- Initial Bot Message -->
            <div class="flex flex-col space-y-1">
                <div class="self-start bg-slate-700 text-slate-200 px-4 py-3 rounded-2xl rounded-tl-none max-w-[80%] shadow-sm">
                    Hello! I'm your gym database assistant. You can ask me to list members, check bookings, or view attendance records. How can I help?
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-4 bg-slate-800 border-t border-slate-700 shrink-0">
            <form id="chat-form" class="flex items-center gap-2">
                <input type="text" id="user-input" 
                    class="flex-1 bg-slate-900 border border-slate-600 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all placeholder-slate-500" 
                    placeholder="Type a command (e.g., 'Show all members')..." required autocomplete="off">
                <button type="submit" 
                    class="bg-indigo-600 hover:bg-indigo-500 text-white p-3 rounded-xl transition-colors font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        const chatMessages = document.getElementById('chat-messages');
        const chatForm = document.getElementById('chat-form');
        const userInput = document.getElementById('user-input');

        function appendMessage(role, content, isData = false) {
            const wrapper = document.createElement('div');
            wrapper.className = "flex flex-col space-y-1";
            
            const messageDiv = document.createElement('div');
            const isUser = role === 'user';
            
            messageDiv.className = isUser 
                ? "self-end bg-indigo-600 text-white px-4 py-3 rounded-2xl rounded-tr-none max-w-[80%] shadow-md"
                : "self-start bg-slate-700 text-slate-200 px-4 py-3 rounded-2xl rounded-tl-none max-w-[90%] shadow-sm overflow-x-auto";

            if (isData && Array.isArray(content) && content.length > 0) {
                // Table Rendering
                let tableHtml = '<table class="w-full text-sm text-left text-slate-300">';
                tableHtml += '<thead class="text-xs text-slate-400 uppercase bg-slate-900/50"><tr>';
                
                Object.keys(content[0]).forEach(key => {
                    tableHtml += `<th scope="col" class="px-3 py-2 border-b border-slate-600">${key}</th>`;
                });
                tableHtml += '</tr></thead><tbody>';

                content.forEach(row => {
                    tableHtml += '<tr class="bg-slate-800/50 hover:bg-slate-700/50 border-b border-slate-600">';
                    Object.values(row).forEach(val => {
                        tableHtml += `<td class="px-3 py-2">${val}</td>`;
                    });
                    tableHtml += '</tr>';
                });
                tableHtml += '</tbody></table>';
                messageDiv.innerHTML = tableHtml;
            } else {
                messageDiv.textContent = content;
            }

            wrapper.appendChild(messageDiv);
            chatMessages.appendChild(wrapper);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function appendLoading() {
            const wrapper = document.createElement('div');
            wrapper.id = 'loading-indicator';
            wrapper.className = "flex flex-col space-y-1 animate-pulse";
            wrapper.innerHTML = `
                <div class="self-start bg-slate-700/50 px-4 py-3 rounded-2xl rounded-tl-none max-w-[20%]">
                    <div class="flex space-x-2">
                        <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce delay-75"></div>
                        <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce delay-150"></div>
                    </div>
                </div>
            `;
            chatMessages.appendChild(wrapper);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function removeLoading() {
            const loader = document.getElementById('loading-indicator');
            if (loader) loader.remove();
        }

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = userInput.value.trim();
            if (!message) return;

            appendMessage('user', message);
            userInput.value = '';
            appendLoading();

            try {
                const response = await fetch('server.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: message })
                });

                const contentType = response.headers.get('content-type');
                if (!response.ok) {
                   throw new Error("Server error: " + response.statusText);
                }

                const data = await response.json();
                removeLoading();

                if (data.error) {
                    appendMessage('bot', "Error: " + data.error);
                } else {
                    if (data.chat_response) {
                        appendMessage('bot', data.chat_response);
                    }
                    if (data.data) {
                        appendMessage('bot', data.data, true);
                    }
                }

            } catch (error) {
                removeLoading();
                appendMessage('bot', "Error: " + error.message);
            }
        });
    </script>
</body>
</html>
