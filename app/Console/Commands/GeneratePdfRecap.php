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
        
        $employees = \App\Models\Employee::with(['division'])->where('role', '!=', 'admin')->get();
        
        // Pasang data attendance dan report hari ini ke tiap employee
        foreach ($employees as $emp) {
            $emp->attendance_today = \App\Models\Attendance::where('employee_id', $emp->id)
                ->whereDate('date', $date)->first();
                
            $emp->report_today = \App\Models\SmartDailyReport::where('employee_id', $emp->id)
                ->whereDate('report_date', $date)->first();
        }

        // Group by division
        $divisions = $employees->groupBy(function($emp) {
            return $emp->division->name ?? 'Lainnya';
        });

        // Generate PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.daily_recap', [
            'date' => now()->format('d M Y'),
            'divisions' => $divisions
        ]);
        
        $fileName = "Rekap_Harian_Herbigreen_{$date}.pdf";
        $pdfContent = $pdf->output();

        // Kirim ke Telegram Mas Jodi
        $masJodiId = env('MAS_JODI_TELEGRAM_ID');
        $botToken = env('TELEGRAM_BOT_TOKEN');

        if ($masJodiId && $botToken) {
            $response = \Illuminate\Support\Facades\Http::attach(
                'document',
                $pdfContent,
                $fileName
            )->post("https://api.telegram.org/bot{$botToken}/sendDocument", [
                'chat_id' => $masJodiId,
                'caption' => "📊 *REKAP HARIAN HERBIGREEN*\nTanggal: " . now()->format('d M Y') . "\n\nBerikut terlampir laporan rekapitulasi performa, metrik, dan kendala dari semua tim hari ini. Silakan dicek bos! 🚀",
                'parse_mode' => 'Markdown'
            ]);

            if ($response->successful()) {
                $this->info("PDF berhasil dikirim ke Mas Jodi.");
            } else {
                $this->error("Gagal kirim PDF: " . $response->body());
            }
        } else {
            $this->error("MAS_JODI_TELEGRAM_ID atau TELEGRAM_BOT_TOKEN belum diatur.");
        }
    }
}
