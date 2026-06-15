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
    public function __construct(
        public int $employeeId,
        public string $fileUrl,
        public string $sender,
        public ?string $accountName = null,
        public ?string $liveStart = null,
        public ?string $liveEnd = null
    ) {
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

        // 3. OCR pake Gemini Vision (baca angka GMV dll dari screenshot)
        $gmvAmount = null;
        $orderCount = null;
        $productSold = null;
        $viewersCount = null;
        $highestViewers = null;
        $rawOcrText = null;
        $geminiKey = env('GEMINI_API_KEY');

        if ($geminiKey) {
            try {
                $base64Image = base64_encode($imageContent);

                $geminiResponse = Http::timeout(30)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$geminiKey}", [
                    'contents' => [[
                        'parts' => [
                            [
                                'inline_data' => [
                                    'mime_type' => 'image/jpeg',
                                    'data' => $base64Image,
                                ]
                            ],
                            [
                                'text' => 'Ini adalah gambar/foto layar laptop/HP dari halaman statistik live (Shopee Seller Center atau TikTok Live). Ekstrak metrik berikut dari layar: 1. Total GMV (atau "Penjualan (Rp)", "Omset"). 2. Total Pesanan. 3. Produk Terjual. 4. Total Dilihat (Penonton). 5. Penonton Tertinggi. 6. Nama Platform ("Shopee" atau "TikTok", tebak dari warna/teks di gambar). Balas HANYA dengan JSON mentah tanpa markdown, dengan format persis seperti ini: {"gmv": angka, "pesanan": angka, "produk_terjual": angka, "penonton": angka, "penonton_tertinggi": angka, "platform": "Shopee/TikTok/Lainnya"}. Hapus titik/koma dari angka (contoh 523.680 jadi 523680). Jika ada data yang tidak ditemukan, beri nilai 0.'
                            ]
                        ]
                    ]]
                ]);

                if ($geminiResponse->successful()) {
                    $rawOcrText = $geminiResponse->json('candidates.0.content.parts.0.text');
                    // Clean up markdown block if Gemini adds it
                    $cleanJsonStr = str_replace(['```json', '```'], '', $rawOcrText);
                    $parsed = json_decode(trim($cleanJsonStr), true);
                    
                    if (is_array($parsed)) {
                        $gmvAmount = (int) ($parsed['gmv'] ?? 0);
                        $orderCount = (int) ($parsed['pesanan'] ?? 0);
                        $productSold = (int) ($parsed['produk_terjual'] ?? 0);
                        $viewersCount = (int) ($parsed['penonton'] ?? 0);
                        $highestViewers = (int) ($parsed['penonton_tertinggi'] ?? 0);
                        $platform = $parsed['platform'] ?? 'Lainnya';
                    }
                    Log::info("KOKI GMV: OCR Gemini berhasil. Raw: {$rawOcrText}");
                } else {
                    Log::error("KOKI GMV: Gemini API Error! Status: " . $geminiResponse->status() . " Body: " . $geminiResponse->body());
                }
            } catch (\Exception $e) {
                Log::error("KOKI GMV: OCR Gemini gagal (Exception): " . $e->getMessage());
            }
        } else {
            Log::warning("KOKI GMV: GEMINI_API_KEY belum diset, OCR dilewati.");
        }

        // 4. Minta Konfirmasi ke User via Bot
        $provider = \App\Services\MessageProviderFactory::create();
        
        if ($gmvAmount !== null && $gmvAmount > 0) {
            // Simpan data sementara ke state
            $stateService = new \App\Services\DatabaseConversationState();
            $stateService->setCurrentStep($this->sender, 'waiting_gmv_confirmation', [
                'employee_id' => $this->employeeId,
                'screenshot_path' => $filename,
                'gmv_amount' => $gmvAmount,
                'order_count' => $orderCount,
                'product_sold' => $productSold,
                'viewers_count' => $viewersCount,
                'highest_viewers' => $highestViewers,
                'platform' => $platform ?? 'Lainnya',
                'account_name' => $this->accountName,
                'live_start' => $this->liveStart,
                'live_end' => $this->liveEnd,
                'raw_ocr_text' => $rawOcrText,
                'live_date' => now()->format('Y-m-d'),
            ]);

            $platformDisplay = $platform ?? 'Lainnya';
            $formattedGmv = number_format($gmvAmount, 0, ',', '.');
            $msg = "📸 *Laporan Omset Diterima*\n\n"
                 . "Aku udah baca metrik dari foto kamu nih:\n";
            if ($this->accountName) {
                $msg .= "🏪 Akun: *{$this->accountName}*\n";
            }
            if ($this->liveStart && $this->liveEnd) {
                $msg .= "⏰ Jam Live: *{$this->liveStart} - {$this->liveEnd}*\n";
            }
            $msg .= "📱 Platform: *{$platformDisplay}*\n"
                 . "💰 GMV/Omset: *Rp {$formattedGmv}*\n"
                 . "📦 Pesanan: *{$orderCount}*\n"
                 . "🛍️ Produk Terjual: *{$productSold}*\n"
                 . "👁️ Dilihat: *{$viewersCount}*\n"
                 . "🔥 Penonton Tertinggi: *{$highestViewers}*\n\n"
                 . "Udah bener belum datanya? 🤔\n"
                 . "(Balas: *Ya* / *Tidak*)";
            
            $provider->sendMessage($this->sender, $msg);
            Log::info("KOKI GMV: Menunggu konfirmasi user. Angka: {$gmvAmount}");
        } else {
            // Kalau gagal baca angka atau dapet 0
            $msg = "📸 *Laporan GMV Diterima*\n\n"
                 . "Aduh, aku gagal baca angka dari screenshot yang kamu kirim (atau kebaca 0). Gambarnya burem atau angkanya nggak kelihatan jelas nih 🥺\n\n"
                 . "Coba kirim ulang gambarnya yang lebih tajam ya! Atau kalau mau cepat, ketik manual aja: */gmv [angka_omset]* (contoh: */gmv 500000*)";
            $provider->sendMessage($this->sender, $msg);
            Log::warning("KOKI GMV: Gagal baca angka, minta upload ulang. Raw: {$rawOcrText}");
        }
    }
}
