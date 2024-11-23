<?php

namespace App\Http\Controllers;

use App\Services\PromptService;
use Illuminate\Http\Request;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Session;

class ChatController extends Controller
{
    private $commonInputs;
    private $commonResponses;
    private $systemPrompt;

    public function __construct()
    {
        $this->commonInputs = PromptService::getCommonInputs();
        $this->commonResponses = PromptService::getCommonResponses();
        $this->systemPrompt = PromptService::getSystemPrompt();
    }


    public function sendMessage(Request $request)
    {
        $userMessage = $request->input('message');

        if ($this->isCommonInput($userMessage)) {
            $response = $this->getCommonResponse($userMessage);
            $this->saveToHistory($userMessage, $response);

            return response()->json([
                'success' => true,
                'response' => $response
            ]);
        }

        try {
            $chatHistory = $this->getChatHistory();
            $contextPrompt = $this->buildContextPrompt($chatHistory, $userMessage);
            $result = Gemini::geminiPro()->generateContent($contextPrompt);
            $this->saveToHistory($userMessage, $result->text());

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
            $contextPrompt .= "- Pertanyaan: {$chat['user_message']}\n";
            $contextPrompt .= "  Jawaban: {$chat['ai_response']}\n\n";
        }

        $contextPrompt .= "Pertanyaan Terbaru: {$newMessage}\n";
        $contextPrompt .= "Jawab dengan memperhatikan konteks sebelumnya:";

        return $contextPrompt;
    }

    private function isCommonInput($message)
    {
        return in_array(strtolower($message), array_map('strtolower', $this->commonInputs));
    }

    private function getCommonResponse($message)
    {
        $hour = date('H');
        $responseType = $this->getResponseType($message, $hour);
        $response = $this->commonResponses[$responseType];
        return $response[array_rand($response)];
    }

    private function getResponseType($message, $hour)
    {
        if (strpos($message, 'pagi') !== false || strpos($message, 'morning') !== false || ($hour >= 5 && $hour < 12)) {
            return 'morning';
        } elseif (strpos($message, 'siang') !== false || strpos($message, 'afternoon') !== false || ($hour >= 12 && $hour < 15)) {
            return 'afternoon';
        } elseif (strpos($message, 'sore') !== false || strpos($message, 'evening') !== false || ($hour >= 15 && $hour < 18)) {
            return 'evening';
        } elseif (strpos($message, 'malam') !== false || ($hour >= 18 || $hour < 5)) {
            return 'night';
        }
        return 'general';
    }

    private function getChatHistory()
    {
        $chatHistory = Session::get('chat_history', []);
        return array_slice($chatHistory, -5);
    }

    private function saveToHistory($userMessage, $aiResponse)
    {
        $chatHistory = Session::get('chat_history', []);
        $chatHistory[] = [
            'user_message' => $userMessage,
            'ai_response' => $aiResponse
        ];
        Session::put('chat_history', $chatHistory);
    }

    public function createChatHistory()
    {
        return response()->json(Session::get('chat_history', []));
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
