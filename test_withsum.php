<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$q = \App\Models\Employee::query()->withSum(['gmvReports as gmv_today' => function($q) {
    $q->whereDate('live_date', now()->format('Y-m-d'));
}], 'gmv_amount');
$sql = $q->toSql();
echo $sql . "\n";
