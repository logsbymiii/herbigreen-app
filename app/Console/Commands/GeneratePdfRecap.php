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

    protected $description = 'Generate rekap harian PDF dan kirim ke Mas Jodi jam 00:00';

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
                
            $emp->gmv_today = \App\Models\GmvReport::where('employee_id', $emp->id)
                ->whereDate('live_date', $date)->sum('gmv_amount');
        }

        // Group by division
        $divisions = $employees->groupBy(function($emp) {
            return $emp->division->name ?? 'Lainnya';
        });

        // Kumpulkan semua raw report untuk dikasih ke AI
        $allReportsText = "";
        foreach ($employees as $emp) {
            if ($emp->report_today && !empty($emp->report_today->raw_report)) {
                $divName = $emp->division->name ?? 'Lainnya';
                $gmvInfo = $emp->gmv_today > 0 ? "\nData Sistem (GMV Leaderboard): Rp " . number_format($emp->gmv_today, 0, ',', '.') : "";
                $allReportsText .= "Karyawan: {$emp->name} | Divisi: {$divName}\nLaporan: {$emp->report_today->raw_report}{$gmvInfo}\n\n";
            }
        }

        $executiveSummary = "Tidak ada ringkasan AI karena data laporan tidak mencukupi atau terjadi error.";
        $geminiKey = env('GEMINI_API_KEY');

        if (!empty($allReportsText) && $geminiKey) {
            try {
                $prompt = "Kamu adalah Chief Operating Officer (COO) cerdas. Berikut adalah gabungan laporan harian dari semua karyawan hari ini:\n"
                        . "```\n{$allReportsText}\n```\n\n"
                        . "Buatkan 'Executive Summary' untuk CEO (Mas Jodi). Ringkasan harus mencakup:\n"
                        . "1. Penilaian keseluruhan performa tim hari ini (Singkat).\n"
                        . "2. Highlight divisi atau individu yang mencapai target bagus.\n"
                        . "3. Red Flags (Masalah/Blocker) yang butuh atensi.\n\n"
                        . "Format menggunakan Markdown yang rapi (bold, bullet points). Gunakan bahasa Indonesia profesional tapi asik dibaca.";

                $geminiResponse = \Illuminate\Support\Facades\Http::timeout(60)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$geminiKey}", [
                    'contents' => [[
                        'parts' => [['text' => $prompt]]
                    ]]
                ]);

                if ($geminiResponse->successful()) {
                    $executiveSummary = $geminiResponse->json('candidates.0.content.parts.0.text') ?? $executiveSummary;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Executive Summary Error: " . $e->getMessage());
            }
        }

        // Generate PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.daily_recap', [
            'date' => now()->format('d M Y'),
            'divisions' => $divisions,
            'executiveSummary' => \Illuminate\Support\Str::markdown($executiveSummary)
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
