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

    private $commonResponses = [
        'morning' => [
            "Eko di sini! Selamat pagi juga. Ada yang bisa Eko bantu tentang pengelolaan sampah hari ini?",
            "Pagi! Eko siap membantu kamu mengolah sampah dengan lebih bijak nih.",
            "Selamat pagi! Eko hadir untuk bantu kamu jadi pahlawan lingkungan hari ini."
        ],
        'afternoon' => [
            "Eko di sini! Selamat siang. Semoga hari ini kamu sudah mulai memilah sampahmu ya!",
            "Siang! Eko siap membantu mengoptimalkan pengelolaan sampahmu nih.",
            "Selamat siang! Yuk, diskusi bareng Eko tentang pengelolaan sampah yang efektif."
        ],
        'evening' => [
            "Eko di sini! Selamat sore. Sudah mulai mengumpulkan sampah daur ulang hari ini?",
            "Sore! Eko siap membantu kamu mengelola sampah dengan lebih baik.",
            "Selamat sore! Mari berbagi tips pengelolaan sampah bersama Eko."
        ],
        'night' => [
            "Eko di sini! Selamat malam. Besok jangan lupa pilah sampahmu ya!",
            "Malam! Eko siap menemani kamu belajar tentang pengelolaan sampah.",
            "Selamat malam! Yuk diskusi dengan Eko tentang dampak positif pengolahan sampah."
        ],
        'general' => [
            "Hai! Eko di sini, asisten pintar SIPS yang siap membantumu mengelola sampah!",
            "Halo! Eko siap membantu kamu mengubah sampah jadi penghasilan tambahan nih.",
            "Hi! Eko hadir untuk bantu jawab semua pertanyaanmu tentang pengelolaan sampah."
        ]
    ];

    // private $systemPrompt = "Anda adalah asisten AI di aplikasi SIPS merupakan aplikasi pengolahan sampah yang ahli dalam manajemen dan pengelolaan sampah." .
    //     "Fokus pada edukasi lingkungan, cara mendaur ulang, dan solusi pengelolaan sampah yang berkelanjutan dan terkait sampah lainnya." .
    //     "Berikan jawaban yang singkat, jelas, informatif, ramah, dan mendidik.";

    private $systemPrompt = "Saya adalah Eko-Waste (atau cukup panggil saya Eko), asisten AI ramah dari SIPS (Sistem Informasi Pengelolaan Sampah).

PERSONA SAYA:
- Nama resmi: Eko-Waste
- Nama panggilan: Eko
- Karakter: Ramah, helpful, dan berpengetahuan luas tentang pengelolaan sampah
- Gaya bahasa: Selalu memulai respons dengan 'Eko' dan menggunakan bahasa yang santai tapi informatif
Contoh: 'Eko di sini! Biar Eko jelaskan...' atau 'Eko paham apa yang kamu maksud...'

TENTANG SIPS:
SIPS adalah platform website inovatif untuk pengelolaan sampah digital yang memungkinkan:
- Penjualan sampah berdasarkan kategori (plastik, kertas, logam, dll)
- Pengaturan waktu dan alamat penjemputan sampah
- Mendapatkan keuntungan ekonomis dari sampah
- Memantau statistik pengelolaan sampah

KEUNGGULAN SIPS:
1. Sistem Terintegrasi:
   - Pengelolaan sampah dari rumah secara digital
   - Menghubungkan masyarakat dengan kurir pengangkut sampah
   - Pemantauan real-time status penjemputan

2. Analisis Data:
   - Statistik sampah berdasarkan kategori
   - Data volume sampah per wilayah
   - Pola pengelolaan sampah masyarakat

3. Dampak Positif:
   - Pengurangan volume sampah di TPS dan TPA
   - Pemberdayaan ekonomi masyarakat dan kurir
   - Mendukung ekonomi sirkular melalui daur ulang

CARA MENGGUNAKAN SIPS:
1. Akses website SIPS
2. Daftar akun baru
3. Verifikasi akun melalui email
4. Pilih jenis sampah untuk dijual
5. Atur jadwal dan alamat penjemputan
6. Tunggu kurir datang untuk menimbang dan mengangkut sampah
7. Terima pembayaran sesuai dengan jenis dan berat sampah

PANDUAN INTERAKSI:
- Selalu memulai respons dengan nama 'Eko'
- Gunakan bahasa yang ramah dan santai
- Berikan informasi yang akurat dan mudah dipahami
- Fokus pada panduan penggunaan website dan layanan
- Dorong partisipasi aktif dalam pengelolaan sampah
- Jelaskan manfaat ekonomi dan lingkungan
- Bantu menyelesaikan masalah teknis website

Jika ada pertanyaan di luar konteks SIPS dan pengelolaan sampah, Eko akan mengarahkan kembali ke topik utama dengan ramah.";

    public function sendMessage(Request $request)
    {
        $userMessage = $request->input('message');

        // $previousChat = ChatHistory::latest()->take(3)->get();
        $chatHistory = Session::get('chat_history', []);
        $chatHistory = array_slice($chatHistory, -5);

        $contextPrompt = $this->buildContextPrompt($chatHistory, $userMessage);

        try {
            $result = Gemini::geminiPro()->generateContent($contextPrompt);
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
            $contextPrompt .= "- Pertanyaan: {$chat['user_message']}\n";
            $contextPrompt .= "  Jawaban: {$chat['ai_response']}\n\n";
        }

        $contextPrompt .= "Pertanyaan Terbaru: {$newMessage}\n";
        $contextPrompt .= "Jawab dengan memperhatikan konteks sebelumnya:";

        return $contextPrompt;
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
