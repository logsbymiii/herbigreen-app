<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReminderLaporMalam extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reminder-lapor-malam';

    protected $description = 'Kirim reminder khusus host sesi malam jam 22:30';

    public function handle()
    {
        $providerType = env('MESSAGE_PROVIDER', 'fonnte');
        $identifierColumn = $providerType === 'telegram' ? 'telegram_id' : 'phone';

        $employees = \App\Models\Employee::whereNotNull($identifierColumn)
            ->whereHas('division', function($q) {
                $q->where('name', 'like', '%Host Live%');
            })
            ->whereIn('shift', ['malam', 'full'])
            ->get();
            
        $provider = \App\Services\MessageProviderFactory::create();

        \Illuminate\Support\Facades\Log::info("KOKI REMINDER: Memulai proses reminder lapor malam (22:30).");
        
        $count = 0;
        foreach ($employees as $emp) {
            $absenHariIni = \App\Models\Attendance::where('employee_id', $emp->id)
                ->whereDate('date', now()->format('Y-m-d'))
                ->whereIn('type', ['hadir', 'wfh', 'telat'])
                ->first();
            
            $expectedSessions = $absenHariIni ? $absenHariIni->expected_sessions : 1;
            
            $gmvCount = \App\Models\GmvReport::where('employee_id', $emp->id)
                ->whereDate('live_date', now()->format('Y-m-d'))->count();
                
            $belumLengkap = $gmvCount < $expectedSessions;

            if ($belumLengkap) {
                $provider->sendMessage($emp->{$identifierColumn}, "🌙 Selamat Malam, {$emp->name}. Mohon jangan lupa untuk mengirimkan laporan GMV shift malam Anda sebelum mengakhiri pekerjaan. Terima kasih. 😴");
                $count++;
            }
        }
        
        \Illuminate\Support\Facades\Log::info("KOKI REMINDER: Selesai mengirim reminder lapor malam ke {$count} host live.");
    }
}
