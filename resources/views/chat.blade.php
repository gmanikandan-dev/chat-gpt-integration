<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with ChatGPT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
<div class="w-full max-w-2xl bg-white rounded-lg shadow-lg">
    <div class="bg-blue-600 text-white py-3 px-5 rounded-t-lg">
        <h1 class="text-lg font-semibold">Chat with ChatGPT</h1>
    </div>
    <div class="p-5 h-96 overflow-y-auto" id="chatBox">
        <!-- Display stored messages -->
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
    const chatBox = document.getElementById('chatBox');
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('message');

    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const message = messageInput.value.trim();
        if (!message) return;

        // Add user's message to the chat box
        chatBox.innerHTML += `
            <div class="mb-4">
                <p class="text-right">
                    <span class="inline-block px-4 py-2 rounded-lg bg-blue-500 text-white">
                        ${message}
                    </span>
                </p>
            </div>
        `;
        chatBox.scrollTop = chatBox.scrollHeight;
        messageInput.value = ''; // Clear input

        // Add typing indicator at the bottom of the chat box
        const typingIndicator = document.createElement('div');
        typingIndicator.classList.add('mb-4', 'text-left');
        typingIndicator.innerHTML = `
            <span class="inline-block px-4 py-2 rounded-lg bg-gray-200 text-gray-700">ChatGPT is typing...</span>
        `;
        chatBox.appendChild(typingIndicator);
        chatBox.scrollTop = chatBox.scrollHeight;

        try {
            const response = await fetch('/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({ message }),
            });

            if (!response.body) {
                throw new Error('No response body');
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let done = false;
            let value = await reader.read();
            let chunk = decoder.decode(value.value, { stream: true });

            // Process streaming response
            while (!done) {
                done = value.done;

                // Update ChatGPT's message dynamically
                for (const char of chunk) {
                    typingIndicator.innerHTML += char;
                    await new Promise(resolve => setTimeout(resolve, 20));
                    chatBox.scrollTop = chatBox.scrollHeight;
                }

                if (!done) {
                    value = await reader.read();
                    chunk = decoder.decode(value.value, { stream: true });
                }
            }

            // Reload the page after receiving the response
            location.reload();
            
        } catch (error) {
            console.error('Error:', error);

            // Remove typing indicator and show error message
            typingIndicator.remove();
            chatBox.innerHTML += `
                <div class="mb-4">
                    <p class="text-left">
                        <span class="inline-block px-4 py-2 rounded-lg bg-red-500 text-white">
                            Unable to process your request. Please try again later.
                        </span>
                    </p>
                </div>
            `;
        }
    });
</script>

</body>
</html>
