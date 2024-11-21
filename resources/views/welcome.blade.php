<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ChatBot</title>
    @vite('resources/css/app.css')
    <style>
        .chat-container::-webkit-scrollbar {
            width: 6px;
        }

        .chat-container::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
        }
    </style>
</head>

<body class="bg-dark min-h-[150vh]">
    <div class="flex items-center justify-center h-screen">
        <div class="text-center text-white">
            <h1 class="text-4xl font-bold">Chatbot Laravel</h1>
        </div>
    </div>

    <div class="fixed bottom-5 right-5 z-50">
        <button id="chatTrigger" class="bg-primary size-full p-2 rounded-full">
            <svg xmlns="http://www.w3.org/2000/svg" width="2em" height="2em" viewBox="0 0 24 24">
                <g fill="white" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                    <path d="M12 8V4H8" />
                    <rect width="16" height="12" x="4" y="8" rx="2" />
                    <path d="M2 14h2m16 0h2m-7-1v2m-6-2v2" />
                </g>
            </svg>
        </button>
    </div>

    <div id="chatModal" class="fixed inset-0 z-50 overflow-hidden {{ app('request')->is('/') ? 'hidden' : '' }}">
        <div class="fixed right-5 bottom-20 bg-black bg-opacity-50 flex items-center justify-center">
            <div class="bg-white w-96 max-h-[80vh] rounded-lg shadow-xl flex flex-col">
                <!-- Header -->
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">Waste Management AI</h3>
                    <button id="closeModal" class="text-gray-500 hover:text-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Chat Container -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chatResponse">
                    <!-- Chat messages response -->
                </div>

                <!-- Input Area -->
                <div class="p-4 border-t">
                    <textarea id="chatInput"
                        class="w-full h-20 p-2 border rounded resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Tanya tentang pengelolaan sampah..."></textarea>
                    <button id="sendMessage"
                        class="mt-2 w-full bg-primary text-white py-2 rounded hover:bg-opacity-90 transition">
                        Kirim Pesan
                    </button>
                    <button id="clearChatHistory"
                        class="mt-2 w-full bg-red-500 text-white py-2 rounded hover:bg-red-600 transition">
                        Hapus Riwayat Chat
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chatTrigger = document.getElementById('chatTrigger');
            const chatModal = document.getElementById('chatModal');
            const closeModal = document.getElementById('closeModal');
            const sendMessage = document.getElementById('sendMessage');
            const chatInput = document.getElementById('chatInput');
            const chatResponse = document.getElementById('chatResponse');

            // Fungsi untuk menambahkan pesan
            function appendMessage(sender, message) {
                const messageElement = document.createElement('div');
                messageElement.classList.add(
                    'max-w-xl',
                    'p-3',
                    'rounded-lg',
                    'flex'
                );

                if (sender === 'user') {
                    messageElement.classList.add('bg-blue-100', 'self-end', 'ml-auto');
                } else {
                    messageElement.classList.add('bg-white', 'self-start', 'mr-auto');
                }

                messageElement.innerHTML =
                    `<p class="${sender === 'user' ? 'text-blue-800' : 'text-gray-800'}">${message}</p>`;

                chatResponse.appendChild(messageElement);
                chatResponse.scrollTop = chatResponse.scrollHeight;
            }

            async function loadChatHistory() {
                try {
                    const response = await fetch('/chat-history');
                    const history = await response.json();

                    chatResponse.innerHTML = '';

                    history.forEach(chat => {
                        if (chat.user_message) {
                            appendMessage('user', chat.user_message);
                        }
                        if (chat.ai_response) {
                            appendMessage('ai', chat.ai_response);
                        }
                    });
                } catch (error) {
                    console.error('Gagal memuat history:', error);
                    chatResponse.innerHTML =
                        '<p class="text-red-500 text-center">Gagal memuat riwayat chat</p>';
                }
            }

            const clearChatHistory = document.getElementById('clearChatHistory');
            clearChatHistory.addEventListener('click', async () => {
                try {
                    const response = await fetch('/clear-chat', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .content
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        chatResponse.innerHTML = '';
                    }
                } catch (error) {
                    console.error('Gagal menghapus riwayat chat:', error);
                    appendMessage('ai', 'Gagal menghapus riwayat chat. Silakan coba lagi.');
                }
            });

            chatTrigger.addEventListener('click', () => {
                chatModal.classList.remove('hidden');
                loadChatHistory();
            });

            closeModal.addEventListener('click', () => {
                chatModal.classList.add('hidden');
            });

            sendMessage.addEventListener('click', async () => {
                const message = chatInput.value.trim();

                if (message) {
                    appendMessage('user', message);
                    sendMessage.disabled = true;
                    sendMessage.textContent = 'Mengirim...';

                    try {
                        const response = await fetch('/chat', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                message: message
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            appendMessage('ai', data.response);
                        } else {
                            appendMessage('ai', 'Maaf, terjadi kesalahan.');
                        }
                    } catch (error) {
                        appendMessage('ai', 'Gagal mengirim pesan. Coba lagi.');
                    } finally {
                        sendMessage.disabled = false
                        sendMessage.textContent = 'Kirim Pesan'
                        chatInput.value = ''
                    }
                }
            });

            chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault()
                    sendMessage.click()
                }
            });
        });
    </script>
</body>

</html>
