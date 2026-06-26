<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$e = \App\Models\Employee::where('role', 'admin')->first() ?? \App\Models\Employee::first();
if (!$e) {
    die("No employee found.\n");
}
echo "Dispatching job for employee: {$e->name}\n";

try {
    \App\Jobs\ProcessSmartDailyReportJob::dispatchSync($e->id, 'Saya hari ini selesai mengedit 5 video dan desain 2 logo', $e->telegram_id ?? '123');
    echo "Job dispatched successfully.\n";
} catch (\Exception $ex) {
    echo "Exception: " . $ex->getMessage() . "\n";
    echo $ex->getTraceAsString() . "\n";
}
