<?php

namespace App\Http\Controllers;

use App\Models\ChatHistory;
use Illuminate\Http\Request;
use Gemini\Laravel\Facades\Gemini;

class ChatController extends Controller
{
    private $systemPrompt = "Anda adalah asisten AI yang ahli dalam manajemen dan pengelolaan sampah. " .
        "Fokus pada edukasi lingkungan, cara mendaur ulang, dan solusi pengelolaan sampah yang berkelanjutan. " .
        "Berikan jawaban yang informatif, ramah, dan mendidik.";

    public function sendMessage(Request $request)
    {
        $userMessage = $request->input('message');

        // $fullPrompt = $systemPrompt . "\n\nPertanyaan Pengguna: " . $userMessage;
        $previousChat = ChatHistory::latest()->take(3)->get();

        $contextPrompt = $this->buildContextPrompt($previousChat, $userMessage);

        try {
            $result = Gemini::geminiPro()->generateContent($contextPrompt);

            ChatHistory::create([
                'user_message' => $userMessage,
                'ai_response' => $result->text(),
                'system_prompt' => $this->systemPrompt
            ]);

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

    private function buildContextPrompt($previousChats, $newMessage)
    {
        $contextPrompt = $this->systemPrompt . "\n\nKonteks Percakapan Sebelumnya:\n";
        foreach ($previousChats as $chat) {
            $contextPrompt .= "- Pertanyaan: {$chat->user_message}\n";
            $contextPrompt .= "  Jawaban: {$chat->ai_response}\n\n";
        }

        $contextPrompt .= "Pertanyaan Terbaru: {$newMessage}\n";
        $contextPrompt .= "Jawab dengan memperhatikan konteks sebelumnya:";

        return $contextPrompt;
    }

    public function chatHistory()
    {
        $chats = ChatHistory::latest()->take(20)->get();

        return response()->json($chats);
    }
}
