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
        $provider = \App\Services\MessageProviderFactory::create();

        \Illuminate\Support\Facades\Log::info("KOKI REMINDER: Memulai proses reminder absen pagi.");
        
        $count = 0;
        foreach ($employees as $emp) {
            $sudahAbsen = \App\Models\Attendance::where('employee_id', $emp->id)
                ->whereDate('date', now()->format('Y-m-d'))
                ->exists();

            if (!$sudahAbsen) {
                $provider->sendMessage($emp->telegram_id, "☀️ Selamat Pagi, {$emp->name}. Jangan lupa untuk melakukan absensi kehadiran sebelum memulai aktivitas hari ini. Selamat bekerja! ⏰");
                $count++;
            }
        }
        
        \Illuminate\Support\Facades\Log::info("KOKI REMINDER: Selesai mengirim reminder absen pagi ke {$count} karyawan.");
    }
}
