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
            
            $sudahLapor = \App\Models\Report::where('employee_id', $emp->id)
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->exists();

            if (!$sudahLapor) {
                $provider->sendMessage($emp->telegram_id, "‼️ Woy {$emp->name}! Udah jam 16:00 nih! WAJIB banget ngirim laporan sekarang juga ya! 🤬");
            }
        }
    }
}
