<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatController extends Controller
{
    public function index()
    {
        $conversations = Conversation::oldest()->get();

        return view('chat', compact('conversations'));
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        // Save user message to database
        $conversation = new Conversation;
        $conversation->message = $validated['message'];
        $conversation->sender = 'user';
        $conversation->save();

        try {
            $response = Http::withToken(config('app.openai.api_key'))
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-3.5-turbo', // Use the GPT-3.5 model
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                        ['role' => 'user', 'content' => $validated['message']],
                    ],
                ]);

            if ($response->failed()) {
                throw new \Exception('OpenAI API Error: '.$response->body());
            }

            $reply = $response->json('choices.0.message.content');
        } catch (Throwable $throwable) {
            // Log the error and return a 500 response with a message
            Log::error($throwable->getMessage());

            return response()->json(['error' => 'Something went wrong. Please try again later.'], 500);
        }

        // Save ChatGPT's response to database
        $conversation = new Conversation;
        $conversation->message = $reply;
        $conversation->sender = 'chatgpt';
        $conversation->save();

        $replyChunks = str_split($reply, 200); // Adjust chunk size as needed

        return response()->stream(function () use ($replyChunks) {
            foreach ($replyChunks as $chunk) {
                echo $chunk;
                ob_flush();
                flush();
                usleep(20000); // Adjust delay for typing speed
            }
        });
    }
}
