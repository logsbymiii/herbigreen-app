<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$emps = \App\Models\Employee::with('division')->get();
foreach($emps as $emp) {
    $divName = $emp->division ? $emp->division->name : 'NULL';
    echo "ID: {$emp->id} | Name: {$emp->name} | Div: {$divName}\n";
}
