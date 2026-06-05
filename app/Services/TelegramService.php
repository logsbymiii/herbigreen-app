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

            $response = Http::post($url, [
                'chat_id' => $chatId,
                'text'    => $message,
                'parse_mode' => 'Markdown',
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
