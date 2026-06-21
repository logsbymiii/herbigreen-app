<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReminderAbsenPagi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reminder-absen-pagi';

    protected $description = 'Kirim reminder absen pagi jam 07:45';

    public function handle()
    {
        $employees = \App\Models\Employee::whereNotNull('telegram_id')->get();
        $provider = \App\Services\BotHandlers\MessageProviderFactory::create('telegram');

        foreach ($employees as $emp) {
            $sudahAbsen = \App\Models\Attendance::where('employee_id', $emp->id)
                ->whereDate('date', now()->format('Y-m-d'))
                ->exists();

            if (!$sudahAbsen) {
                $provider->sendMessage($emp->telegram_id, "☀️ Pagi {$emp->name}! Yuk jangan lupa ketik /absen dulu sebelum mulai kerja ya! Jangan sampai telat! ⏰");
            }
        }
    }
}
