<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeadlineLaporSore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:deadline-lapor-sore';

    protected $description = 'Tutup laporan jam 19:00 dan anggap tidak masuk (alpa) jika belum lapor';

    public function handle()
    {
        $providerType = env('MESSAGE_PROVIDER', 'fonnte');
        $identifierColumn = $providerType === 'telegram' ? 'telegram_id' : 'phone';

        $employees = \App\Models\Employee::whereNotNull($identifierColumn)->get();
            
        $provider = \App\Services\MessageProviderFactory::create();

        \Illuminate\Support\Facades\Log::info("KOKI REMINDER: Memulai proses deadline lapor sore (19:00).");
        
        $count = 0;
        foreach ($employees as $emp) {
            $isHostLive = strtolower($emp->division->name ?? '') === 'host live';
            if ($isHostLive) continue;
            
            $sudahLapor = \App\Models\SmartDailyReport::where('employee_id', $emp->id)
                ->whereDate('report_date', now()->format('Y-m-d'))
                ->exists();

            if (!$sudahLapor) {
                // Tandai alpa (atau ubah attendance yang ada jadi alpa)
                \App\Models\Attendance::updateOrCreate(
                    ['employee_id' => $emp->id, 'date' => now()->format('Y-m-d')],
                    ['type' => 'alpa']
                );
                
                $provider->sendMessage($emp->{$identifierColumn}, "❌ Batas waktu pengisian laporan telah habis. Kehadiran Anda hari ini otomatis diubah menjadi *TIDAK MASUK (ALPA)* karena tidak ada laporan yang diterima. 📉");
                $count++;
            }
        }
        
        \Illuminate\Support\Facades\Log::info("KOKI REMINDER: Selesai mengirim deadline lapor sore (Alpa) ke {$count} karyawan.");
    }
}
