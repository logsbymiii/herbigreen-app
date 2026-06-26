<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$emps = \App\Models\Employee::whereHas('division', function($q) {
    $q->where('name', 'like', '%Host Live%');
})->withSum(['gmvReports as gmv_this_month' => function($q) {
    $q->whereMonth('created_at', now()->month);
}], 'gmv_amount')->get();

foreach($emps as $emp) {
    echo "ID: {$emp->id} | Name: {$emp->name} | Keys: " . implode(',', array_keys($emp->toArray())) . "\n";
}
