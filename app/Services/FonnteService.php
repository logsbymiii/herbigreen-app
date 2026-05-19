<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    public function sendMessage(string $phone, string $message): bool
    {
        $token = env('FONNTE_TOKEN');

        if (!$token) {
            Log::error('FONNTE ERROR: FONNTE_TOKEN belum dipasang cak');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
                'target'  => $phone,
                'message' => $message,
                'countryCode' => '62',
            ]);

            if ($response->successful()) {
                Log::info("WA TERKIRIM ke {$phone} | Isi: {$message}");
                return true;
            }

            Log::error("GAGAL KIRIM WA ke {$phone} | Response Fonnte: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("SYSTEM ERROR (FonnteService): " . $e->getMessage());
            return false;
        }
    }
}
