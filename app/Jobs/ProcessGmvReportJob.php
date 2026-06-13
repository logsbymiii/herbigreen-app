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

    /**
     * Create a new job instance.
     */
    public function __construct(public int $employeeId, public string $fileUrl, public string $sender)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("KOKI GMV: Mulai proses download screenshot...");

        if (empty($this->fileUrl)) {
            Log::warning("KOKI GMV GAGAL: User tidak mengirimkan gambar.");
            $provider = \App\Services\MessageProviderFactory::create();
            $provider->sendMessage($this->sender, "Eh, kamu mau lapor GMV tapi lupa ngelampirin fotonya nih! 😅 Coba kirim ulang pesannya beserta foto layarnya ya.");
            return;
        }

        // 1. Download file
        $response = Http::withoutVerifying()->timeout(30)->get($this->fileUrl);

        if (!$response->successful()) {
            throw new \Exception("KOKI GMV GAGAL: File tidak bisa didownload. URL: {$this->fileUrl} Status: " . $response->status());
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
                                'text' => 'Ini adalah gambar/foto layar laptop/HP dari halaman Shopee Seller Center atau TikTok Live. Abaikan pantulan cahaya. Tugasmu HANYA mencari angka Total GMV, Total Pendapatan, Omset, atau tulisan "Penjualan (Rp)". Berikan HANYA angka hasil akhirnya saja (biasanya angka yang paling besar di tengah layar), tanpa titik, koma, huruf, atau simbol apapun. Contoh jika tertulis 523.680, balas HANYA: 523680. Jika tidak ada angka sama sekali, balas: 0'
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

        // 4. Minta Konfirmasi ke User via Bot
        $provider = \App\Services\MessageProviderFactory::create();
        
        if ($gmvAmount !== null && $gmvAmount > 0) {
            // Simpan data sementara ke state
            $stateService = new \App\Services\ConversationStateService();
            $stateService->setState($this->sender, 'waiting_gmv_confirmation', [
                'employee_id' => $this->employeeId,
                'screenshot_path' => $filename,
                'gmv_amount' => $gmvAmount,
                'raw_ocr_text' => $rawOcrText,
                'live_date' => now()->format('Y-m-d'),
            ]);

            $formattedGmv = number_format($gmvAmount, 0, ',', '.');
            $msg = "📸 *Laporan GMV Diterima*\n\n"
                 . "Aku berhasil baca screenshot kamu nih. Angka GMV yang kudapet: *Rp {$formattedGmv}*\n\n"
                 . "Bener nggak angka segitu? 🤔\n"
                 . "(Balas: *Ya* / *Tidak*)";
            
            $provider->sendMessage($this->sender, $msg);
            Log::info("KOKI GMV: Menunggu konfirmasi user. Angka: {$gmvAmount}");
        } else {
            // Kalau gagal baca angka atau dapet 0
            $msg = "📸 *Laporan GMV Diterima*\n\n"
                 . "Aduh, aku gagal baca angka dari screenshot yang kamu kirim (atau kebaca 0). Gambarnya burem atau angkanya nggak kelihatan jelas nih 🥺\n\n"
                 . "Coba kirim ulang gambarnya yang lebih tajam ya!";
            $provider->sendMessage($this->sender, $msg);
            Log::warning("KOKI GMV: Gagal baca angka, minta upload ulang. Raw: {$rawOcrText}");
        }
    }
}
