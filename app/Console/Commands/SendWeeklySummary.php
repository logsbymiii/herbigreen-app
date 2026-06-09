<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Report;
use App\Models\Attendance;
use App\Models\Division;
use App\Services\MessageProviderFactory;
use App\Services\AiResponseService;
use Carbon\Carbon;

class SendWeeklySummary extends Command
{
    protected $signature = 'app:send-weekly-summary';
    protected $description = 'Kirim laporan mingguan AI ke Mas Jodi setiap Senin pagi';

    public function handle()
    {
        $startOfWeek = Carbon::now()->subWeek()->startOfWeek(); // Senin minggu lalu
        $endOfWeek   = Carbon::now()->subWeek()->endOfWeek();   // Minggu minggu lalu

        $this->info("Mengumpulkan data: {$startOfWeek->format('d M')} - {$endOfWeek->format('d M Y')}...");

        $totalKaryawan = Employee::where('is_active', true)->count();
        $totalLaporan  = Report::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
        $totalIzin     = Attendance::whereBetween('date', [$startOfWeek, $endOfWeek])->count();

        // Rata-rata laporan per hari kerja (Senin-Jumat = 5 hari)
        $rataLaporan = $totalKaryawan > 0 ? round($totalLaporan / 5, 1) : 0;

        // Compliance: (laporan + izin) / (karyawan * 5 hari kerja) * 100
        $totalHarusLapor   = $totalKaryawan * 5;
        $totalSudahLapor   = $totalLaporan + $totalIzin;
        $persentaseCompliance = $totalHarusLapor > 0
            ? round(($totalSudahLapor / $totalHarusLapor) * 100)
            : 0;

        // Performa per divisi
        $divisions  = Division::with('employees')->get();
        $perDivisi  = [];
        foreach ($divisions as $div) {
            $empIds    = $div->employees()->where('is_active', true)->pluck('id');
            $laporan   = Report::whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->whereIn('employee_id', $empIds)->count();
            $izin      = Attendance::whereBetween('date', [$startOfWeek, $endOfWeek])
                ->whereIn('employee_id', $empIds)->count();

            if ($empIds->count() > 0) {
                $perDivisi[] = [
                    'nama'    => $div->name,
                    'laporan' => $laporan,
                    'izin'    => $izin,
                    'anggota' => $empIds->count(),
                ];
            }
        }

        // Top reporter: karyawan dengan laporan terbanyak minggu ini
        $topEmployee  = Report::whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->selectRaw('employee_id, COUNT(*) as total')
            ->groupBy('employee_id')
            ->orderByDesc('total')
            ->with('employee')
            ->first();
        $topReporter  = $topEmployee ? "{$topEmployee->employee->name} ({$topEmployee->total} laporan)" : 'Belum ada data';

        // Needs attention: karyawan yang sama sekali tidak lapor seminggu
        $needsAttentionList = [];
        $allEmployees = Employee::with('division')->where('is_active', true)->get();
        foreach ($allEmployees as $emp) {
            $laporan = $emp->reports()->whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
            $absen   = $emp->attendances()->whereBetween('date', [$startOfWeek, $endOfWeek])->count();
            if ($laporan === 0 && $absen === 0) {
                $needsAttentionList[] = $emp->name;
            }
        }
        $needsAttention = empty($needsAttentionList)
            ? 'Semua karyawan sudah lapor'
            : implode(', ', array_slice($needsAttentionList, 0, 3)) . (count($needsAttentionList) > 3 ? ' dst.' : '');

        $stats = [
            'total_laporan'          => $totalLaporan,
            'total_karyawan'         => $totalKaryawan,
            'rata_laporan_per_hari'  => $rataLaporan,
            'total_izin'             => $totalIzin,
            'persentase_compliance'  => $persentaseCompliance,
            'per_divisi'             => $perDivisi,
            'top_reporter'           => $topReporter,
            'needs_attention'        => $needsAttention,
        ];

        // Generate AI weekly summary
        $ai      = new AiResponseService();
        $summary = $ai->generateWeeklySummary($stats);

        $periode = $startOfWeek->format('d M') . ' – ' . $endOfWeek->format('d M Y');

        $pesan  = "📅 *Laporan Mingguan Herbigreen*\n";
        $pesan .= "Periode: {$periode}\n";
        $pesan .= str_repeat("─", 28) . "\n\n";
        $pesan .= "📊 *Statistik Minggu Ini:*\n";
        $pesan .= "• Laporan masuk   : {$totalLaporan}\n";
        $pesan .= "• Izin/Sakit      : {$totalIzin}\n";
        $pesan .= "• Compliance      : {$persentaseCompliance}%\n";
        $pesan .= "• Rata/hari       : {$rataLaporan} laporan\n\n";

        // Per divisi
        $pesan .= "🏢 *Per Divisi:*\n";
        foreach ($perDivisi as $div) {
            $pesan .= "• {$div['nama']}: {$div['laporan']} laporan, {$div['izin']} izin\n";
        }

        $pesan .= "\n🤖 *AI Analysis:*\n{$summary}";

        if (!empty($needsAttentionList)) {
            $pesan .= "\n\n⚠️ *Perlu follow-up:* " . implode(', ', $needsAttentionList);
        }

        $adminTgId  = env('ADMIN_TELEGRAM_ID');
        $adminPhone = env('ADMIN_PHONE');
        $recipient  = $adminTgId ?? $adminPhone;

        if (!$recipient) {
            $this->error("❌ ADMIN_TELEGRAM_ID / ADMIN_PHONE belum di-set di .env!");
            return;
        }

        $provider = MessageProviderFactory::create();
        if ($provider->sendMessage($recipient, $pesan)) {
            $this->info("✅ Laporan mingguan AI berhasil dikirim ke admin.");
        } else {
            $this->error("❌ Gagal kirim laporan mingguan.");
        }
    }
}
