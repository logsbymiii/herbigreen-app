<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$reports = \App\Models\GmvReport::all();
echo "Total GMV Reports: " . $reports->count() . "\n";
foreach($reports as $r) {
    echo "ID: {$r->id} | Emp ID: {$r->employee_id} | GMV: {$r->gmv_amount} | Date: {$r->live_date}\n";
}
