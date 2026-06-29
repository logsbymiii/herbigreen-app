<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GeneratePdfRecap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-pdf-recap';

    protected $description = 'Generate rekap harian PDF dan kirim ke Mas Jodi jam 23:59';

    public function handle()
    {
        $date = now()->format('Y-m-d');
        
        $employees = \App\Models\Employee::with(['division'])->get();
        
        // Pasang data attendance dan report hari ini ke tiap employee
        foreach ($employees as $emp) {
            $emp->attendance_today = \App\Models\Attendance::where('employee_id', $emp->id)
                ->whereDate('date', $date)->first();
                
            $emp->report_today = \App\Models\SmartDailyReport::where('employee_id', $emp->id)
                ->whereDate('report_date', $date)->first();
                
            $gmvQuery = \App\Models\GmvReport::where('employee_id', $emp->id)
                ->whereDate('live_date', $date);

            $emp->gmv_stats = [
                'gmv_amount' => $gmvQuery->sum('gmv_amount'),
                'order_count' => $gmvQuery->sum('order_count'),
                'product_sold' => $gmvQuery->sum('product_sold'),
                'viewers_count' => $gmvQuery->sum('viewers_count'),
                'highest_viewers' => $gmvQuery->max('highest_viewers'),
            ];
            
            $emp->gmv_today = $emp->gmv_stats['gmv_amount'];
            
            // Cek WFH status
            $emp->is_wfh = \App\Models\WfhRequest::where('employee_id', $emp->id)
                ->whereDate('request_date', $date)
                ->where('status', 'approved')
                ->exists();
        }

        // --- SECTION 1: Executive Summary Stats ---
        $totalHadir = $employees->filter(fn($e) => $e->attendance_today && $e->attendance_today->type === 'hadir')->count();
        $totalSakitIzin = $employees->filter(fn($e) => $e->attendance_today && in_array($e->attendance_today->type, ['sakit', 'izin']))->count();
        $totalAlpa = $employees->filter(fn($e) => !$e->attendance_today || $e->attendance_today->type === 'alpa')->count();
        $totalLaporan = $employees->filter(fn($e) => $e->report_today != null)->count();

        // --- SECTION 2: Financial & Performance ---
        $totalGmv = $employees->sum('gmv_today');
        $top3Gmv = $employees->filter(fn($e) => $e->gmv_today > 0)
                             ->sortByDesc('gmv_today')
                             ->take(3)
                             ->values();

        // --- SECTION 3: Wall of Shame ---
        $wfhList = $employees->filter(fn($e) => $e->is_wfh)->values();
        $lateList = $employees->filter(function($e) {
            if ($e->attendance_today && $e->attendance_today->type === 'hadir' && $e->attendance_today->clocked_in_at) {
                return \Carbon\Carbon::parse($e->attendance_today->clocked_in_at)->format('H:i') > '08:00';
            }
            return false;
        })->values();
        $noReportList = $employees->filter(fn($e) => !$e->report_today)->values();

        // Group by division untuk Section 4
        $divisions = $employees->groupBy(function($emp) {
            return $emp->division->name ?? 'Lainnya';
        });

        // Kumpulkan semua raw report untuk dikasih ke AI
        $allReportsText = "";
        foreach ($employees as $emp) {
            if ($emp->report_today && !empty($emp->report_today->raw_report)) {
                $divName = $emp->division->name ?? 'Lainnya';
                $gmvInfo = "";
                if ($emp->gmv_today > 0) {
                    $gmvInfo = "\n\nDATA SISTEM (Tercatat di Database GMV):\n" .
                               "- Omset (GMV): Rp " . number_format($emp->gmv_today, 0, ',', '.') . "\n" .
                               "- Pesanan: " . $emp->gmv_stats['order_count'] . "\n" .
                               "- Produk Terjual: " . $emp->gmv_stats['product_sold'] . "\n" .
                               "- Total Penonton: " . $emp->gmv_stats['viewers_count'] . "\n" .
                               "- Penonton Tertinggi (Peak): " . $emp->gmv_stats['highest_viewers'];
                }
                $allReportsText .= "Karyawan: {$emp->name} | Divisi: {$divName}\nLaporan Teks: {$emp->report_today->raw_report}{$gmvInfo}\n\n";
            }
        }

        $executiveSummary = "Tidak ada ringkasan AI karena data laporan tidak mencukupi atau terjadi error.";
        $geminiKey = env('GEMINI_API_KEY');

        if (!empty($allReportsText) && $geminiKey) {
            try {
                $prompt = "Kamu adalah Chief Operating Officer (COO) cerdas. Berikut adalah gabungan laporan harian dari semua karyawan hari ini:\n"
                        . "```\n{$allReportsText}\n```\n\n"
                        . "Buatkan 'Executive Summary' singkat. Ringkasan HANYA mencakup:\n"
                        . "1. Penilaian performa tim hari ini (Singkat).\n"
                        . "2. Highlight divisi atau individu yang mencapai target bagus.\n"
                        . "3. Red Flags (Masalah/Blocker) yang butuh atensi.\n\n"
                        . "Format menggunakan Markdown yang rapi (bold, bullet points). Gunakan bahasa Indonesia profesional tapi ringkas.\n"
                        . "PENTING: DILARANG KERAS MENGGUNAKAN EMOJI SAMA SEKALI karena tidak disupport oleh PDF (akan menjadi tanda tanya). Gunakan teks biasa saja.";

                $geminiResponse = \Illuminate\Support\Facades\Http::timeout(60)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$geminiKey}", [
                    'contents' => [[
                        'parts' => [['text' => $prompt]]
                    ]]
                ]);

                if ($geminiResponse->successful()) {
                    $rawContent = $geminiResponse->json('candidates.0.content.parts.0.text') ?? $executiveSummary;
                    // Hapus karakter emoji dengan regex sebagai perlindungan ekstra untuk dompdf
                    $cleanedContent = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $rawContent); // Hapus emoji modern
                    $cleanedContent = preg_replace('/[\x{2600}-\x{27BF}]/u', '', $cleanedContent); // Hapus simbol lama
                    
                    $executiveSummary = $cleanedContent;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Executive Summary Error: " . $e->getMessage());
            }
        }

        // Generate PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.daily_recap', [
            'date' => now()->format('d F Y'),
            'divisions' => $divisions,
            'executiveSummary' => \Illuminate\Support\Str::markdown($executiveSummary),
            'stats' => [
                'hadir' => $totalHadir,
                'sakit_izin' => $totalSakitIzin,
                'alpa' => $totalAlpa,
                'laporan' => $totalLaporan,
                'total_gmv' => $totalGmv,
            ],
            'top3Gmv' => $top3Gmv,
            'wfhList' => $wfhList,
            'lateList' => $lateList,
            'noReportList' => $noReportList,
        ]);
        
        $fileName = "Rekap_Harian_Herbigreen_{$date}.pdf";
        $pdfContent = $pdf->output();

        // Kirim ke Telegram Mas Jodi
        $masJodiId = env('MAS_JODI_TELEGRAM_ID');
        $botToken = env('TELEGRAM_BOT_TOKEN');

        if (\Illuminate\Support\Facades\Storage::exists('management_group_id.txt')) {
            $managementGroupId = trim(\Illuminate\Support\Facades\Storage::get('management_group_id.txt'));
            $provider = \App\Services\MessageProviderFactory::create();
            
            // Kirim PDF langsung (Summary panjang sudah ada di dalam PDF)
            $botToken = env('TELEGRAM_BOT_TOKEN');
            if ($botToken) {
                // Bisa dikirim ke private chat maupun group
                \Illuminate\Support\Facades\Http::attach(
                    'document',
                    $pdfContent,
                    $fileName
                )->post("https://api.telegram.org/bot{$botToken}/sendDocument", [
                    'chat_id' => $managementGroupId,
                    'caption' => "📊 *REKAP HARIAN HERBIGREEN*\nTanggal: " . now()->format('d M Y') . "\n\nSilakan unduh file PDF di atas untuk melihat detail laporan harian seluruh tim beserta Executive Summary (Analisis AI).",
                    'parse_mode' => 'Markdown'
                ]);
            }
            $this->info("PDF & Summary berhasil dikirim ke Management Group.");
        } else {
            $this->error("Management Group ID belum di-set via /init_management.");
        }
    }
}
