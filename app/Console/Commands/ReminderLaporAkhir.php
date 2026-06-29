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
        $providerType = env('MESSAGE_PROVIDER', 'fonnte');
        $identifierColumn = $providerType === 'telegram' ? 'telegram_id' : 'phone';

        $employees = \App\Models\Employee::whereNotNull($identifierColumn)->get();
            
        $provider = \App\Services\MessageProviderFactory::create();

        \Illuminate\Support\Facades\Log::info("KOKI REMINDER: Memulai proses reminder lapor akhir (17:00).");
        
        $count = 0;
        foreach ($employees as $emp) {
            $isHostLive = strtolower($emp->division->name ?? '') === 'host live';
            if ($isHostLive) {
                $absenHariIni = \App\Models\Attendance::where('employee_id', $emp->id)
                    ->whereDate('date', now()->format('Y-m-d'))
                    ->whereIn('type', ['hadir', 'wfh', 'telat'])
                    ->first();
                $expectedSessions = $absenHariIni ? $absenHariIni->expected_sessions : 1;
                if ($expectedSessions < 2) continue;
                $gmvCount = \App\Models\GmvReport::where('employee_id', $emp->id)
                    ->whereDate('live_date', now()->format('Y-m-d'))->count();
                if ($gmvCount >= 1) continue;
            }
            
            $sudahLapor = \App\Models\SmartDailyReport::where('employee_id', $emp->id)
                ->whereDate('report_date', now()->format('Y-m-d'))
                ->exists();

            if (!$sudahLapor) {
                $provider->sendMessage($emp->{$identifierColumn}, "🚨 Peringatan Terakhir, {$emp->name}. Mohon segera mengirimkan laporan harian Anda sebelum batas waktu habis, agar kehadiran Anda tetap terhitung hari ini.");
                $count++;
            }
        }
        
        \Illuminate\Support\Facades\Log::info("KOKI REMINDER: Selesai mengirim reminder lapor akhir ke {$count} karyawan.");
    }
}
