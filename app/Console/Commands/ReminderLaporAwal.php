<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReminderLaporAwal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reminder-lapor-awal';

    protected $description = 'Kirim reminder laporan jam 15:45';

    public function handle()
    {
        // Kecuali host sesi malam
        $employees = \App\Models\Employee::whereNotNull('telegram_id')
            ->get();
            
        $provider = \App\Services\MessageProviderFactory::create();

        foreach ($employees as $emp) {
            $isHostLive = strtolower($emp->division->name ?? '') === 'host live';
            if ($isHostLive) continue; // Host live ada jam sendiri
            
            $sudahLapor = \App\Models\SmartDailyReport::where('employee_id', $emp->id)
                ->whereDate('report_date', now()->format('Y-m-d'))
                ->exists();

            if (!$sudahLapor) {
                $provider->sendMessage($emp->telegram_id, "🔔 Halo {$emp->name}! Udah jam 15:45 nih. Yuk laporin hari ini ngapain aja sebelum jam pulang ya! 📝");
            }
        }
    }
}
