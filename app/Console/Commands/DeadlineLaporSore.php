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
                // Tandai alpa (atau ubah attendance yang ada jadi alpa)
                \App\Models\Attendance::updateOrCreate(
                    ['employee_id' => $emp->id, 'date' => now()->format('Y-m-d')],
                    ['type' => 'alpa']
                );
                
                $provider->sendMessage($emp->telegram_id, "❌ WAKTU HABIS! Laporan udah nggak diterima lagi. Kamu otomatis dihitung *TIDAK MASUK (ALPA)* hari ini karena telat lapor! 📉");
            }
        }
    }
}
