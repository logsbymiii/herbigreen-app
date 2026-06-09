<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Report;
use App\Models\Attendance;
use App\Services\MessageProviderFactory;
use App\Services\AiResponseService;
use Carbon\Carbon;

class SendNightSummary extends Command
{
    protected $signature = 'app:send-night-summary';
    protected $description = 'Kirim rekap harian + AI insight ke Mas Jodi';

    public function handle()
    {
        $today        = Carbon::today();
        $totalLaporan = Report::whereDate('created_at', $today)->count();
        $totalIzin    = Attendance::whereDate('date', $today)->count();

        $employees   = Employee::with('division')->where('is_active', true)->get();
        $belumLapor  = [];
        $totalAktif  = $employees->count();

        foreach ($employees as $emp) {
            $hasReport     = $emp->reports()->whereDate('created_at', $today)->exists();
            $hasAttendance = $emp->attendances()->whereDate('date', $today)->exists();

            if (!$hasReport && !$hasAttendance) {
                $divisi       = $emp->division->name ?? 'Tanpa Divisi';
                $belumLapor[] = "{$emp->name} ({$divisi})";
            }
        }

        $countBelumLapor = count($belumLapor);
        $listBelumLapor  = empty($belumLapor) ? 'Nihil ✨' : implode("\n• ", $belumLapor);
        $tanggal         = $today->translatedFormat('l, d F Y');
        $persentase      = $totalAktif > 0 ? round(($totalLaporan / $totalAktif) * 100) : 0;

        // Generate AI insight
        $ai      = new AiResponseService();
        $insight = $ai->generateNightSummaryInsight(
            $totalAktif,
            $totalLaporan,
            $totalIzin,
            $countBelumLapor,
            $belumLapor
        );

        $pesan  = "📊 *Rekap Harian Herbigreen*\n";
        $pesan .= "📅 {$tanggal}\n";
        $pesan .= str_repeat("─", 28) . "\n\n";
        $pesan .= "✅ Laporan masuk : *{$totalLaporan}*\n";
        $pesan .= "🏥 Izin/Sakit   : *{$totalIzin}*\n";
        $pesan .= "❌ Belum lapor  : *{$countBelumLapor}*\n";
        $pesan .= "📈 Compliance   : *{$persentase}%*\n\n";

        if (!empty($belumLapor)) {
            $pesan .= "👤 *Yang belum lapor:*\n• {$listBelumLapor}\n\n";
        }

        $pesan .= "🤖 *AI Insight:*\n{$insight}";

        $adminPhone = env('ADMIN_PHONE');
        $adminTgId  = env('ADMIN_TELEGRAM_ID');
        $recipient  = $adminTgId ?? $adminPhone;

        if (!$recipient) {
            $this->error("❌ ADMIN_TELEGRAM_ID / ADMIN_PHONE belum di-set di .env!");
            return;
        }

        $provider = MessageProviderFactory::create();
        if ($provider->sendMessage($recipient, $pesan)) {
            $this->info("✅ Rekap malam + AI insight berhasil dikirim ke admin.");
        } else {
            $this->error("❌ Gagal kirim rekap malam.");
        }
    }
}
