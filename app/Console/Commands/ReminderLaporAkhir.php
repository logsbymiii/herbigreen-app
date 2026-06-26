<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReminderLaporAkhir extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reminder-lapor-akhir';

    protected $description = 'Kirim reminder terakhir jam 17:00';

    public function handle()
    {
        $employees = \App\Models\Employee::whereNotNull('telegram_id')
            ->get();
            
        $provider = \App\Services\BotHandlers\MessageProviderFactory::create('telegram');

        foreach ($employees as $emp) {
            $isHostLive = strtolower($emp->division->name ?? '') === 'host live';
            if ($isHostLive) continue;
            
            $sudahLapor = \App\Models\Report::where('employee_id', $emp->id)
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->exists();

            if (!$sudahLapor) {
                $provider->sendMessage($emp->telegram_id, "🚨 ALERT TERAKHIR! Jam 17:00 nih {$emp->name}! Buruan kirim laporan atau dianggap alpa! ☠️");
            }
        }
    }
}
