<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReminderLaporMalam extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reminder-lapor-malam';

    protected $description = 'Kirim reminder khusus host sesi malam jam 22:30';

    public function handle()
    {
        $employees = \App\Models\Employee::whereNotNull('telegram_id')
            ->whereHas('division', function($q) {
                $q->where('name', 'like', '%Host Live%');
            })->get();
            
        $provider = \App\Services\MessageProviderFactory::create();

        foreach ($employees as $emp) {
            $sudahLapor = \App\Models\Report::where('employee_id', $emp->id)
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->exists();

            if (!$sudahLapor) {
                $provider->sendMessage($emp->telegram_id, "🌙 Semangat malam {$emp->name}! Udah jam 22:30 nih. Yuk buruan laporan GMV-nya disetorin sebelum jam 23:45 ya! 💸");
            }
        }
    }
}
