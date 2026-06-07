<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\GmvReport;

class ProcessGmvReportJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public int $employeeId;
    public ?string $urlFile;

    public function __construct(int $employeeId, ?string $urlFile)
    {
        $this->employeeId = $employeeId;
        $this->urlFile = $urlFile;
    }

    public function handle(): void
    {
        if (!$this->urlFile) {
            Log::warning("KOKI GMV: Karyawan ID {$this->employeeId} lapor GMV tapi lupa ngirim screenshot!");
            return;
        }

        // 1. Download foto dari Telegram/WA
        $response = Http::withoutVerifying()->timeout(30)->get($this->urlFile);

        if (!$response->successful()) {
            throw new \Exception("KOKI GMV GAGAL: File tidak bisa didownload. Status: " . $response->status());
        }

        $imageContent = $response->body();

        // 2. Upload ke Cloudflare R2
        $filename = 'gmv/' . $this->employeeId . '_' . now()->format('Ymd_His') . '_' . Str::random(5) . '.jpg';
        Storage::disk('r2')->put($filename, $imageContent, 'public');
        Log::info("KOKI GMV: Screenshot berhasil diupload ke R2: {$filename}");

        // 3. OCR pake Gemini Vision (baca angka GMV dari screenshot)
        $gmvAmount = null;
        $rawOcrText = null;
        $geminiKey = env('GEMINI_API_KEY');

        if ($geminiKey) {
            try {
                $base64Image = base64_encode($imageContent);

                $geminiResponse = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$geminiKey}", [
                    'contents' => [[
                        'parts' => [
                            [
                                'inline_data' => [
                                    'mime_type' => 'image/jpeg',
                                    'data' => $base64Image,
                                ]
                            ],
                            [
                                'text' => 'Ini adalah screenshot hasil live streaming TikTok/Shopee. Temukan angka GMV (Gross Merchandise Value) atau total pendapatan/revenue. Balas HANYA dengan angka saja tanpa titik, koma, atau simbol apapun. Contoh: 5000000. Jika tidak ada angka GMV yang jelas, balas dengan: 0'
                            ]
                        ]
                    ]]
                ]);

                if ($geminiResponse->successful()) {
                    $rawOcrText = $geminiResponse->json('candidates.0.content.parts.0.text');
                    $cleanNumber = preg_replace('/[^0-9]/', '', trim($rawOcrText));
                    $gmvAmount = (int) $cleanNumber ?: null;
                    Log::info("KOKI GMV: OCR Gemini berhasil. Raw: {$rawOcrText} | Parsed: {$gmvAmount}");
                }
            } catch (\Exception $e) {
                Log::error("KOKI GMV: OCR Gemini gagal: " . $e->getMessage());
            }
        } else {
            Log::warning("KOKI GMV: GEMINI_API_KEY belum diset, OCR dilewati.");
        }

        // 4. Simpan ke database
        GmvReport::create([
            'employee_id'     => $this->employeeId,
            'screenshot_path' => $filename,
            'gmv_amount'      => $gmvAmount,
            'raw_ocr_text'    => $rawOcrText,
            'live_date'       => now()->format('Y-m-d'),
        ]);

        Log::info("KOKI GMV: Selesai! GMV={$gmvAmount}, File={$filename}");
    }
}
