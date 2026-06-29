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
        $providerType = env('MESSAGE_PROVIDER', 'fonnte');
        $identifierColumn = $providerType === 'telegram' ? 'telegram_id' : 'phone';

        $employees = \App\Models\Employee::whereNotNull($identifierColumn)->get();
            
        $provider = \App\Services\MessageProviderFactory::create();

        \Illuminate\Support\Facades\Log::info("KOKI REMINDER: Memulai proses reminder lapor wajib (16:00).");
        
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
                $provider->sendMessage($emp->{$identifierColumn}, "⚠️ Halo, {$emp->name}. Waktu pelaporan wajib harian telah tiba. Mohon segera mengirimkan laporan aktivitas Anda hari ini. Terima kasih.");
                $count++;
            }
        }
        
        \Illuminate\Support\Facades\Log::info("KOKI REMINDER: Selesai mengirim reminder lapor wajib ke {$count} karyawan.");
    }
}
