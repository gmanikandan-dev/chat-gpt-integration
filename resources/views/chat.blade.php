<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ChatGPT Voice</title>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
<div class="w-full max-w-2xl bg-white rounded-lg shadow-lg">
    <div class="bg-blue-600 text-white py-3 px-5 rounded-t-lg">
        <h1 class="text-lg font-semibold">ChatGPT Voice</h1>
    </div>
    <div class="p-5 h-96 overflow-y-auto" id="chatBox">
        @foreach ($conversations as $conversation)
            <div class="mb-4">
                <p class="{{ $conversation->sender === 'user' ? 'text-right' : 'text-left' }}">
                    <span class="inline-block px-4 py-2 rounded-lg {{ $conversation->sender === 'user' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' }}">
                        {{ $conversation->message }}
                    </span>
                </p>
            </div>
        @endforeach
    </div>
    <form id="chatForm" class="flex p-5 border-t">
        <input type="text" id="message" placeholder="Type your message..." 
               class="flex-grow border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
        <button type="submit" class="ml-4 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Send</button>
    </form>
</div>

<script>
    axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    document.getElementById('chatForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const messageInput = document.getElementById('message');
        const message = messageInput.value.trim();

        if (!message) return;

        // Fade the send button
        const sendButton = document.querySelector('#chatForm button');
        sendButton.classList.add('opacity-50');

        axios.post('/chat', { message })
            .then(response => {
                // Remove fade
                sendButton.classList.remove('opacity-50');

                // Add user message
                const chatBox = document.getElementById('chatBox');
                chatBox.innerHTML += `
                    <div class="mb-4">
                        <p class="text-right">
                            <span class="inline-block px-4 py-2 rounded-lg bg-blue-500 text-white">
                                ${message}
                            </span>
                        </p>
                    </div>
                `;

                // Add ChatGPT response
                chatBox.innerHTML += `
                    <div class="mb-4">
                        <p class="text-left">
                            <span class="inline-block px-4 py-2 rounded-lg bg-gray-200 text-gray-700">
                                ${response.data.reply}
                            </span>
                        </p>
                    </div>
                `;

                messageInput.value = '';
                chatBox.scrollTop = chatBox.scrollHeight;
            })
            .catch(error => console.error(error));
    });
</script>

</body>
</html>
