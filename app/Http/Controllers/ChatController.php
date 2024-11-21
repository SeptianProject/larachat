<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Session;

class ChatController extends Controller
{
    private $commonInputs = [
        'test',
        'testing',
        'tes',
        'coba',
        'hai',
        'hello',
        'hi',
        'halo',
        'ping',
        'p',
        'hei',
        'hey',
        'good morning',
        'good afternoon',
        'good evening',
        'selamat pagi',
        'selamat siang',
        'selamat sore',
        'selamat malam'
    ];

    private $systemPrompt = "Anda adalah asisten AI yang ahli dalam manajemen dan pengelolaan sampah. " .
        "Fokus pada edukasi lingkungan, cara mendaur ulang, dan solusi pengelolaan sampah yang berkelanjutan. " .
        "Berikan jawaban yang informatif, ramah, dan mendidik.";

    public function sendMessage(Request $request)
    {
        $userMessage = $request->input('message');

        // $previousChat = ChatHistory::latest()->take(3)->get();

        $chatHistory = Session::get('chat_history', []);
        $chatHistory = array_slice($chatHistory, -5);

        $contextPrompt = $this->buildContextPrompt($chatHistory, $userMessage);

        try {
            $result = Gemini::geminiPro()->generateContent($contextPrompt);

            // with db
            // ChatHistory::create([
            //     'user_message' => $userMessage,
            //     'ai_response' => $result->text(),
            //     'system_prompt' => $this->systemPrompt
            // ]);

            // with session
            $chatHistory[] = [
                'user_message' => $userMessage,
                'ai_response' => $result->text()
            ];

            Session::put('chat_history', $chatHistory);

            return response()->json([
                'success' => true,
                'response' => $result->text()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'response' => $e->getMessage()
            ], 500);
        }
    }

    private function buildContextPrompt($chatHistory, $newMessage)
    {
        $contextPrompt = $this->systemPrompt . "\n\nKonteks Percakapan Sebelumnya:\n";

        foreach ($chatHistory as $chat) {
            // with db
            // $contextPrompt .= "- Pertanyaan: {$chat->user_message}\n";
            // $contextPrompt .= "  Jawaban: {$chat->ai_response}\n\n";

            // with session
            $contextPrompt .= "- Pertanyaan: {$chat['user_message']}\n";
            $contextPrompt .= "  Jawaban: {$chat['ai_response']}\n\n";
        }

        $contextPrompt .= "Pertanyaan Terbaru: {$newMessage}\n";
        $contextPrompt .= "Jawab dengan memperhatikan konteks sebelumnya:";

        return $contextPrompt;
    }

    public function createChatHistory()
    {
        // with db
        // $chats = ChatHistory::latest()->take(20)->get();

        // with session
        $chatHistory = Session::get('chat_history', []);

        return response()->json($chatHistory);
    }

    public function clearChatHistory()
    {
        Session::forget('chat_history');

        return response()->json([
            'success' => true,
            'message' => 'Chat history berhasil dihapus'
        ]);
    }
}
