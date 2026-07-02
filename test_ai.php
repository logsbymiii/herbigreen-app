<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AiResponseService;

$ai = new AiResponseService();
$reflection = new \ReflectionClass($ai);
$keyProp = $reflection->getProperty('apiKey');
$keyProp->setAccessible(true);
$keyProp->setValue($ai, 'sk-eavYEGWB7evW5BBAKEB2rA');

$urlProp = $reflection->getProperty('baseUrl');
$urlProp->setAccessible(true);
$urlProp->setValue($ai, 'https://lite.koboillm.com/v1/chat/completions');

$modelProp = $reflection->getProperty('model');
$modelProp->setAccessible(true);
$modelProp->setValue($ai, 'gpt-4o-mini');

$result = $ai->analyzeIntentAndReply('helmi', 'Tim', 'aku mau ubah profil dong', false, null, 'BELUM ABSEN SAMA SEKALI');

echo "Intent: " . $result['intent'] . "\n";
echo "Reply: " . $result['reply'] . "\n";
var_dump($result);
