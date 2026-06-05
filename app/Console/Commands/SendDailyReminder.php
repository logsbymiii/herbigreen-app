<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Services\MessageProviderFactory;
use Carbon\Carbon;

class SendDailyReminder extends Command
{
    protected $signature = 'app:send-daily-reminder';
    protected $description = 'Kirim reminder ke karyawan yang belum lapor hari ini';

    public function handle()
    {
        $today = Carbon::today();
        $employees = Employee::where('is_active', true)->get();
        $provider = MessageProviderFactory::create();

        $this->info('Mulai mengecek data karyawan...');

        foreach ($employees as $emp) {
            $hasReport = $emp->reports()->whereDate('created_at', $today)->exists();
            $hasAttendance = $emp->attendances()->whereDate('date', $today)->exists();

            if (!$hasReport && !$hasAttendance) {
                $pesan = "Halo *{$emp->name}*, kamu belum kirim laporan hari ini. Mohon segera lapor sebelum jam 6 sore ya! 🌿";

                $recipient = $emp->telegram_id ?? $emp->phone;
                if ($provider->sendMessage($recipient, $pesan)) {
                    $this->info("✅ Kirim reminder ke: {$emp->name}");
                } else {
                    $this->error("❌ Gagal kirim ke: {$emp->name}");
                }
            }
        }
        $this->info('🏁 Selesai kirim reminder!');
    }
}
