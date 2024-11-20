<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Waste Management ChatBot</title>
    @vite('resources/css/app.css')
    <style>
        /* Custom scrollbar */
        .chat-container::-webkit-scrollbar {
            width: 6px;
        }

        .chat-container::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar for Chat History -->
        <div class="w-1/4 bg-white border-r border-gray-200 flex flex-col">
            <div class="p-4 border-b">
                <h2 class="text-xl font-bold text-gray-800">Waste Management</h2>
                <p class="text-sm text-gray-500">AI Chat Assistant</p>
            </div>

            <!-- Chat History List -->
            <div id="chatHistoryList" class="overflow-y-auto flex-1">
                <!-- Chat history items will be dynamically populated here -->
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="w-3/4 flex flex-col">
            <!-- Chat Header -->
            <div class="bg-white p-4 border-b flex items-center">
                <div class="ml-3">
                    <h3 class="font-bold text-lg">Waste Management AI</h3>
                    <span class="text-sm text-green-500">Online</span>
                </div>
            </div>

            <!-- Chat Messages Container -->
            <div id="chatContainer" class="flex-1 overflow-y-auto p-4 chat-container bg-[#f0f2f5]">
                <div id="chatMessages" class="space-y-4">
                    <!-- Chat messages will be dynamically added here -->
                </div>
            </div>

            <!-- Message Input Area -->
            <div class="bg-white p-4 border-t flex items-center">
                <textarea id="chatInput" rows="1"
                    class="w-full px-3 py-2 border rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Ketik pesan tentang sampah..."></textarea>
                <button id="sendMessage"
                    class="ml-2 bg-green-500 text-white p-2 rounded-full hover:bg-green-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chatInput = document.getElementById('chatInput');
            const sendMessage = document.getElementById('sendMessage');
            const chatMessages = document.getElementById('chatMessages');
            const chatContainer = document.getElementById('chatContainer');

            // Auto-resize textarea
            chatInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            // Send Message Function
            async function sendChatMessage() {
                const message = chatInput.value.trim();
                if (!message) return;

                // Add user message to chat
                appendMessage('user', message);

                // Disable input during sending
                chatInput.value = '';
                chatInput.style.height = 'auto';
                sendMessage.disabled = true;

                try {
                    const response = await fetch('/chat', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            message: message
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Add AI response to chat
                        appendMessage('ai', data.response);
                    } else {
                        appendMessage('ai', 'Maaf, terjadi kesalahan.');
                    }
                } catch (error) {
                    appendMessage('ai', 'Gagal mengirim pesan. Coba lagi.');
                } finally {
                    sendMessage.disabled = false;
                }
            }

            // Append Message to Chat
            // Fungsi untuk menambahkan class dengan benar
            function appendMessage(sender, message) {
                const messageElement = document.createElement('div');

                // Gunakan classList.add untuk menambahkan class secara terpisah
                messageElement.classList.add(
                    'max-w-xl',
                    'p-3',
                    'rounded-lg',
                    'flex' // Tambahkan flex untuk kontrol layout
                );

                // Tambahkan class kondisional dengan benar
                if (sender === 'user') {
                    messageElement.classList.add('bg-blue-100', 'self-end', 'ml-auto');
                } else {
                    messageElement.classList.add('bg-white', 'self-start', 'mr-auto');
                }

                messageElement.innerHTML = `
        <p class="${sender === 'user' ? 'text-blue-800' : 'text-gray-800'}">
            ${message}
        </p>
    `;

                chatMessages.appendChild(messageElement);

                // Auto scroll to bottom
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
            // Send message on button click
            sendMessage.addEventListener('click', sendChatMessage);

            // Send message on Enter key
            chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendChatMessage();
                }
            });

            // Load initial chat history
            async function loadChatHistory() {
                try {
                    const response = await fetch('/chat-history');
                    const history = await response.json();

                    // Clear existing messages
                    chatMessages.innerHTML = '';

                    // Add historical messages
                    history.forEach(chat => {
                        appendMessage('user', chat.user_message);
                        appendMessage('ai', chat.ai_response);
                    });
                } catch (error) {
                    console.error('Gagal memuat history:', error);
                }
            }

            // Load history when page loads
            loadChatHistory();
        });
    </script>
</body>

</html>
