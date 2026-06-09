<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AiResponseService;
use Illuminate\Support\Facades\Http;

class TestAiConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Gemini AI Connection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Testing Gemini AI Connection...");

        $apiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');

        if (!$apiKey) {
            $this->error("❌ FAIL: GEMINI_API_KEY is missing in your .env file!");
            $this->line("Please open .env and add: GEMINI_API_KEY=your_key_here");
            return 1;
        }

        $this->info("API Key found. Attempting to connect to Gemini...");

        try {
            $response = Http::timeout(10)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key={$apiKey}",
                [
                    'contents' => [[
                        'parts' => [['text' => 'Balas pesan ini dengan "Halo! Koneksi AI Berhasil!"']]
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 50,
                    ]
                ]
            );

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text');
                $this->info("✅ SUCCESS! Gemini responded: " . trim($text));
                return 0;
            }

            $this->error("❌ FAIL: Connected but received error from Gemini.");
            $this->line("Response: " . $response->body());
            return 1;

        } catch (\Exception $e) {
            $this->error("❌ FAIL: Connection Error!");
            $this->line($e->getMessage());
            return 1;
        }
    }
}
