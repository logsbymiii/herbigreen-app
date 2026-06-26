<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReminderLaporWajib extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reminder-lapor-wajib';

    protected $description = 'Kirim reminder wajib lapor jam 16:00';

    public function handle()
    {
        $employees = \App\Models\Employee::whereNotNull('telegram_id')
            ->get();
            
        $provider = \App\Services\MessageProviderFactory::create();

        foreach ($employees as $emp) {
            $isHostLive = strtolower($emp->division->name ?? '') === 'host live';
            if ($isHostLive) continue;
            
            $sudahLapor = \App\Models\SmartDailyReport::where('employee_id', $emp->id)
                ->whereDate('report_date', now()->format('Y-m-d'))
                ->exists();

            if (!$sudahLapor) {
                $provider->sendMessage($emp->telegram_id, "⚠️ Halo, {$emp->name}. Waktu pelaporan wajib harian telah tiba. Mohon segera mengirimkan laporan aktivitas Anda hari ini. Terima kasih.");
            }
        }
    }
}
