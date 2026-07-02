<?php

require 'vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$llmKey = 'sk-sZ4gmsH-88oDeCTdAPEq3w';
$llmUrl = 'https://lite.koboillm.com/v1/chat/completions';
$llmModel = 'gpt-4o-mini';

echo "Testing LLM API...\n";
echo "URL: $llmUrl\n";
echo "Model: $llmModel\n";

$response = Http::timeout(20)->withHeaders([
    'Content-Type' => 'application/json',
    'Authorization' => "Bearer {$llmKey}"
])->post($llmUrl, [
    'model' => $llmModel,
    'messages' => [
        [
            'role' => 'user',
            'content' => 'Halo, ini tes 123'
        ]
    ]
]);

echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";
