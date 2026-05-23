<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Report;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class SendNightSummary extends Command
{
    protected $signature = 'app:send-night-summary';
    protected $description = 'Kirim rekap harian ke nomor Bos (Mas Jodi)';

    public function handle()
    {
        $today = Carbon::today();
        $totalLaporan = Report::whereDate('created_at', $today)->count();
        $totalIzin = Attendance::whereDate('date', $today)->count();

        $employees = Employee::with('division')->where('is_active', true)->get();
        $belumLapor = [];

        foreach ($employees as $emp) {
            $hasReport = $emp->reports()->whereDate('created_at', $today)->exists();
            $hasAttendance = $emp->attendances()->whereDate('date', $today)->exists();

            if (!$hasReport && !$hasAttendance) {
                $divisi = $emp->division?->name ?? 'Tanpa Divisi';
                $belumLapor[] = "{$emp->name} - {$divisi}";
            }
        }

        $countBelumLapor = count($belumLapor);
        $listBelumLapor = empty($belumLapor) ? 'Nihil' : implode(', ', $belumLapor);
        $tanggal = $today->format('d M Y');

        $pesan = "📊 *Rekap Herbigreen {$tanggal}*\n\n"
               . "✅ Laporan Masuk: {$totalLaporan}\n"
               . "⏸️ Izin/Sakit: {$totalIzin}\n"
               . "❌ Belum Lapor: {$countBelumLapor}\n\n"
               . "Daftar belum lapor:\n{$listBelumLapor}";

        $token = env('FONNTE_TOKEN');
        $adminPhone = env('ADMIN_PHONE');

        if ($token && $adminPhone) {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $adminPhone,
                'message' => $pesan,
            ]);

            if ($response->successful()) {
                $this->info("✅ Berhasil kirim rekap malam ke Bos ({$adminPhone}).");
            } else {
                $this->error("❌ Gagal kirim rekap malam ke Bos.");
            }
        } else {
            $this->error("❌ Token Fonnte atau Nomor Admin belum di-set di .env!");
        }
    }
}
