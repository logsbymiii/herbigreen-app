<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestAiConnection extends Command
{
    protected $signature = 'ai:test';
    protected $description = 'Test Groq AI Connection';

    public function handle()
    {
        $this->info("Testing Groq AI Connection...");

        $apiKey = env('GROQ_API_KEY');

        if (!$apiKey) {
            $this->error("❌ FAIL: GROQ_API_KEY is missing in your .env file!");
            $this->line("Please open .env and add: GROQ_API_KEY=your_key_here");
            return 1;
        }

        $this->info("API Key found. Attempting to connect to Groq...");

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'llama3-70b-8192',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Balas pesan ini dengan "Halo! Koneksi AI Berhasil!" tanpa tambahan teks apapun.'
                    ]
                ],
                'temperature' => 0.1,
                'max_tokens' => 50,
            ]);

            if ($response->successful()) {
                $text = $response->json('choices.0.message.content');
                $this->info("✅ SUCCESS! Groq responded: " . trim($text));
                return 0;
            }

            $this->error("❌ FAIL: Connected but received error from Groq.");
            $this->line("Response: " . $response->body());
            return 1;

        } catch (\Exception $e) {
            $this->error("❌ FAIL: Connection Error!");
            $this->line($e->getMessage());
            return 1;
        }
    }
}
