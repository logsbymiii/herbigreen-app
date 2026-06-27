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
        $providerType = env('MESSAGE_PROVIDER', 'fonnte');
        $identifierColumn = $providerType === 'telegram' ? 'telegram_id' : 'phone';

        // Kecuali host sesi malam
        $employees = \App\Models\Employee::whereNotNull($identifierColumn)->get();
            
        $provider = \App\Services\MessageProviderFactory::create();

        \Illuminate\Support\Facades\Log::info("KOKI REMINDER: Memulai proses reminder lapor awal (15:45).");
        
        $count = 0;
        foreach ($employees as $emp) {
            $isHostLive = strtolower($emp->division->name ?? '') === 'host live';
            if ($isHostLive) continue; // Host live ada jam sendiri
            
            $sudahLapor = \App\Models\SmartDailyReport::where('employee_id', $emp->id)
                ->whereDate('report_date', now()->format('Y-m-d'))
                ->exists();

            if (!$sudahLapor) {
                $provider->sendMessage($emp->{$identifierColumn}, "🔔 Halo, {$emp->name}. Mengingatkan bahwa jam kerja hampir usai. Mohon segera mengisi laporan aktivitas harian Anda. 📝");
                $count++;
            }
        }
        
        \Illuminate\Support\Facades\Log::info("KOKI REMINDER: Selesai mengirim reminder lapor awal ke {$count} karyawan.");
    }
}
