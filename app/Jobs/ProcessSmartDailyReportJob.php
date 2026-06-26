<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\SmartDailyReport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessSmartDailyReportJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(
        public int $employeeId,
        public string $rawReportText,
        public string $senderId
    ) {}

    public function handle(): void
    {
        Log::info("KOKI SMART REPORT: Mulai memproses laporan harian dari Employee {$this->employeeId}...");

        $employee = Employee::with('division')->find($this->employeeId);
        if (!$employee || !$employee->division) {
            Log::error("KOKI SMART REPORT GAGAL: Karyawan atau divisi tidak ditemukan.");
            return;
        }

        $divisionName = $employee->division->name;
        $geminiKey = env('GEMINI_API_KEY');

        // Cek apakah sudah ada laporan sebelumnya hari ini untuk digabungkan
        $existingReport = SmartDailyReport::where('employee_id', $this->employeeId)
            ->whereDate('report_date', now()->format('Y-m-d'))
            ->first();

        $mergedRawText = $this->rawReportText;
        if ($existingReport) {
            $mergedRawText = $existingReport->raw_report . "\n\n[Tambahan/Update Laporan]:\n" . $this->rawReportText;
            Log::info("KOKI SMART REPORT: Menggabungkan laporan tambahan untuk Employee {$this->employeeId}.");
        }

        $extractedMetrics = [];
        $aiInsight = "Laporan berhasil diterima.";
        $kendala = null;

        if ($geminiKey) {
            try {
                $prompt = "Kamu adalah AI analis kinerja karyawan. Karyawan ini berada di divisi: '{$divisionName}'.\n\n"
                        . "Berikut adalah laporan harian yang mereka kirim (teks mentah, mungkin berisi beberapa update/tambahan dalam sehari):\n"
                        . "```\n{$mergedRawText}\n```\n\n"
                        . "Tugasmu:\n"
                        . "1. Ekstrak data kuantitatif yang relevan berdasarkan laporannya (misal: jumlah chat, jumlah video diedit, jumlah pesanan, total sampel, dll). Ubah jadi format key-value JSON yang ringkas. Pastikan kunci (key) HANYA menggunakan bahasa Indonesia dengan format snake_case (contoh: video_baru, logo_dibuat, postingan_diupload). Jika ada laporan tambahan, gabungkan/akumulasikan total angkanya jika relevan.\n"
                        . "2. Buatkan ringkasan eksekutif (ai_insight) yang menjabarkan isi laporan secara LENGKAP namun lebih rapi, terstruktur, dan profesional. WAJIB gunakan format Markdown (gunakan bullet points, dan BOLD untuk metrik/angka penting). Rangkum SELURUH poin utama pencapaian dari semua update yang ada secara kronologis atau logis. (JANGAN cuma sekadar memuji/memberi saran pendek, jadikan ini laporan utuh yang layak dibaca manajer).\n"
                        . "3. Ekstrak kendala atau masalah yang dialami (jika ada) ke dalam key `kendala`. Jika tidak ada kendala, isi dengan null atau string kosong.\n\n"
                        . "Format balasan WAJIB berupa JSON mentah TANPA markdown (tanpa ```json dll), dengan struktur persis seperti ini:\n"
                        . "{\n"
                        . "  \"extracted_metrics\": {\"kunci\": \"nilai angka/teks ringkas\"},\n"
                        . "  \"ai_insight\": \"Ringkasan eksekutif berformat Markdown di sini...\",\n"
                        . "  \"kendala\": \"Tuliskan kendalanya di sini jika ada...\"\n"
                        . "}";

                $geminiResponse = Http::timeout(45)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$geminiKey}", [
                    'contents' => [[
                        'parts' => [['text' => $prompt]]
                    ]]
                ]);

                if ($geminiResponse->successful()) {
                    $rawText = $geminiResponse->json('candidates.0.content.parts.0.text');
                    $cleanJsonStr = str_replace(['```json', '```'], '', $rawText);
                    $parsed = json_decode(trim($cleanJsonStr), true);

                    if (is_array($parsed)) {
                        $extractedMetrics = $parsed['extracted_metrics'] ?? [];
                        $aiInsight = $parsed['ai_insight'] ?? "Bagus, pertahankan kerjamu hari ini!";
                        $kendala = $parsed['kendala'] ?? null;
                    }
                    Log::info("KOKI SMART REPORT: Gemini berhasil parsing dan generate Executive Summary.");
                } else {
                    Log::error("KOKI SMART REPORT: Gemini API Error! Status: " . $geminiResponse->status() . " Body: " . $geminiResponse->body());
                    $aiInsight = "Catatan diterima (Gagal AI Parse).";
                }
            } catch (\Exception $e) {
                Log::error("KOKI SMART REPORT: Gemini gagal (Exception): " . $e->getMessage());
                $aiInsight = "Catatan diterima (AI Exception).";
            }
        }

        // Simpan ke database
        SmartDailyReport::updateOrCreate(
            [
                'employee_id' => $this->employeeId,
                'report_date' => now()->format('Y-m-d'),
            ],
            [
                'raw_report' => $mergedRawText,
                'extracted_metrics' => $extractedMetrics,
                'ai_insight' => $aiInsight,
                'kendala' => $kendala,
            ]
        );

        // Laporan berhasil disimpan ke tabel smart_daily_reports.
        // Sengaja TIDAK mengirim notifikasi ke user dari sini,
        // karena user sudah dibalas secara casual oleh ProcessIncomingMessageJob.
        Log::info("KOKI SMART REPORT: Data disimpan. Selesai tanpa membalas ulang ke user.");
    }
}
