<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AiResponseService;

use App\Jobs\ProcessSmartDailyReportJob;
use App\Models\Employee;
use App\Models\Division;

// Create dummy division and employee if not exists
$div = Division::firstOrCreate(['name' => 'Editor Konten']);
$emp = Employee::firstOrCreate(
    ['telegram_id' => '12345678'],
    ['name' => 'Test Employee', 'division_id' => $div->id, 'phone' => '081234567890']
);

putenv('LLM_CHAT_API_KEY=sk-eavYEGWB7evW5BBAKEB2rA');
putenv('LLM_BASE_URL=https://lite.koboillm.com/v1/chat/completions');
putenv('LLM_CHAT_MODEL=gpt-4o-mini');

$job = new ProcessSmartDailyReportJob($emp->id, "Hari ini saya mengedit 5 video pendek dan membuat 2 logo.", "12345678");
$job->handle();

$report = \App\Models\SmartDailyReport::where('employee_id', $emp->id)->latest()->first();
echo "AI Insight: " . $report->ai_insight . "\n";

