<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class SendDailyReminder extends Command
{
    protected $signature = 'app:send-daily-reminder';
    protected $description = 'Kirim reminder WA ke karyawan yang belum lapor absen hari ini';

    public function handle()
    {
        $today = Carbon::today();
        $employees = Employee::where('is_active', true)->get();
        $token = env('FONNTE_TOKEN');

        if (!$token) {
            $this->error('❌ Token Fonnte belum ada di .env!');
            return;
        }

        $this->info('Mulai mengecek data karyawan...');

        foreach ($employees as $emp) {
            // Cek apakah ada report atau attendance HARI INI
            $hasReport = $emp->reports()->whereDate('created_at', $today)->exists();
            $hasAttendance = $emp->attendances()->whereDate('date', $today)->exists();

            if (!$hasReport && !$hasAttendance) {
                $pesan = "Halo *{$emp->name}*, kamu belum kirim laporan hari ini. Mohon segera lapor sebelum jam 6 sore ya! 🌿";

                $response = Http::withHeaders([
                    'Authorization' => $token,
                ])->post('https://api.fonnte.com/send', [
                    'target' => $emp->phone,
                    'message' => $pesan,
                    'delay' => '2', // Jeda 2 detik biar nggak dikira spam
                ]);

                if ($response->successful()) {
                    $this->info("✅ Kirim reminder ke: {$emp->name} ({$emp->phone})");
                } else {
                    $this->error("❌ Gagal kirim ke: {$emp->name}");
                }
            }
        }
        $this->info('🏁 Selesai kirim reminder!');
    }
}
