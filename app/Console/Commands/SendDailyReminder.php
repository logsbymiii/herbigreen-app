<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Report;
use App\Services\MessageProviderFactory;
use App\Services\AiResponseService;
use Carbon\Carbon;

class SendDailyReminder extends Command
{
    protected $signature = 'app:send-daily-reminder';
    protected $description = 'Kirim reminder AI-personalized ke karyawan yang belum lapor hari ini';

    public function handle()
    {
        $today    = Carbon::today();
        $provider = MessageProviderFactory::create();
        $ai       = new AiResponseService();

        $employees = Employee::with('division')->where('is_active', true)->get();

        $this->info('Mulai kirim smart reminder...');

        foreach ($employees as $emp) {
            $hasReport     = $emp->reports()->whereDate('created_at', $today)->exists();
            $hasAttendance = $emp->attendances()->whereDate('date', $today)->exists();

            if ($hasReport || $hasAttendance) {
                continue; // Sudah lapor, skip
            }

            // Hitung berapa hari berturut-turut belum lapor
            $hariTerlambat = 0;
            for ($i = 1; $i <= 7; $i++) {
                $tgl          = Carbon::today()->subDays($i);
                $lapor        = $emp->reports()->whereDate('created_at', $tgl)->exists();
                $absen        = $emp->attendances()->whereDate('date', $tgl)->exists();
                $isWeekend    = $tgl->isWeekend();

                if ($isWeekend) continue;
                if ($lapor || $absen) break;
                $hariTerlambat++;
            }

            $divisi = $emp->division->name ?? 'Umum';

            // AI generate pesan yang personal
            $pesan = $ai->generateSmartReminder($emp->name, $divisi, $hariTerlambat);

            $recipient = $emp->telegram_id ?? $emp->phone;

            if ($provider->sendMessage($recipient, $pesan)) {
                $this->info("✅ Smart reminder terkirim ke: {$emp->name} (telat {$hariTerlambat} hari)");
            } else {
                $this->error("❌ Gagal kirim ke: {$emp->name}");
            }
        }

        $this->info('🏁 Selesai kirim smart reminder!');
    }
}
