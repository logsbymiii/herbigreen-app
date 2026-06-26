<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Create a Division
$div = \App\Models\Division::firstOrCreate(['name' => 'Host Live']);

// 2. Create an Employee
$emp = \App\Models\Employee::firstOrCreate(
    ['email' => 'test@host.com'],
    ['name' => 'Test Host', 'division_id' => $div->id, 'password' => bcrypt('password')]
);

// 3. Create a GMV Report for today
\App\Models\GmvReport::create([
    'employee_id' => $emp->id,
    'gmv_amount' => 500000,
    'live_date' => now()->format('Y-m-d'),
]);

// 4. Run the Leaderboard Query
$query = \App\Models\Employee::query()
    ->whereHas('division', function ($query) {
        $query->where('name', 'like', '%Host Live%');
    })
    ->withSum(['gmvReports as gmv_today' => function ($query) {
        $query->whereDate('live_date', now()->format('Y-m-d'));
    }], 'gmv_amount')
    ->withSum(['gmvReports as gmv_this_month' => function ($query) {
        $query->whereMonth('live_date', now()->month)
              ->whereYear('live_date', now()->year);
    }], 'gmv_amount')
    ->orderByRaw('(SELECT COALESCE(SUM(gmv_amount), 0) FROM gmv_reports WHERE gmv_reports.employee_id = employees.id AND MONTH(live_date) = ? AND YEAR(live_date) = ? AND gmv_reports.deleted_at IS NULL) DESC', [now()->month, now()->year]);

$results = $query->get();

foreach ($results as $result) {
    echo "Host: {$result->name} | Today: {$result->gmv_today_sum_gmv_amount} | This Month: {$result->gmv_this_month_sum_gmv_amount}\n";
}
