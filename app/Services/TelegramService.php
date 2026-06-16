<?php

namespace App\Services;

use App\Contracts\MessageProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService implements MessageProviderInterface
{
    private string $token;
    private string $baseUrl = 'https://api.telegram.org/bot';

    public function __construct()
    {
        $this->token = env('TELEGRAM_BOT_TOKEN');

        if (!$this->token) {
            Log::error('TELEGRAM ERROR: TELEGRAM_BOT_TOKEN belum dipasang cak');
        }
    }

    public function sendMessage(string $chatId, string $message): bool
    {
        if (!$this->token) {
            Log::error('TELEGRAM ERROR: TELEGRAM_BOT_TOKEN tidak tersedia');
            return false;
        }

        try {
            $url = "{$this->baseUrl}{$this->token}/sendMessage";

            // 1. Amankan karakter HTML bawaan user (biar Telegram ga crash kalau ada < atau >)
            $safeMessage = htmlspecialchars($message, ENT_NOQUOTES, 'UTF-8');

            // 2. Convert manual *teks* jadi <b>teks</b> dan _teks_ jadi <i>teks</i>
            // Pakai regex non-greedy supaya formatnya presisi
            $htmlMessage = preg_replace('/\*(.*?)\*/', '<b>$1</b>', $safeMessage);
            $htmlMessage = preg_replace('/_(.*?)_/', '<i>$1</i>', $htmlMessage);

            $response = Http::post($url, [
                'chat_id' => $chatId,
                'text'    => $htmlMessage,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                Log::info("TELEGRAM TERKIRIM ke {$chatId} | Isi: {$message}");
                return true;
            }

            Log::error("GAGAL KIRIM TELEGRAM ke {$chatId} | Response: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("SYSTEM ERROR (TelegramService): " . $e->getMessage());
            return false;
        }
    }

    public function getProviderName(): string
    {
        return 'telegram';
    }
}
