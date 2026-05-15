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
        try {
            if (!$this->urlFile) {
                Log::warning("KOKI GMV: Karyawan ID {$this->employeeId} lapor GMV tapi lupa ngirim screenshot!");
                return;
            }

            // 1. Eksekusi Download dari Fonnte
            $response = Http::get($this->urlFile);

            if ($response->successful()) {
                // 2. Bikin nama file unik biar gak ketimpa (contoh: gmv_1_20260516_12345.jpg)
                $filename = 'gmv_' . $this->employeeId . '_' . now()->format('Ymd_His') . '_' . Str::random(5) . '.jpg';

                // 3. Tentukan folder tujuan (storage/app/public/gmv)
                $path = 'public/gmv/' . $filename;

                // 4. Simpan filenya!
                Storage::put($path, $response->body());

                Log::info("KOKI GMV: Berhasil simpan screenshot GMV di {$path}. (Integrasi AI tunggu Fase 6)");
            } else {
                Log::error("KOKI GMV GAGAL: File dari URL gak bisa didownload.");
            }

        } catch (\Exception $e) {
            Log::error("KOKI GMV SYSTEM ERROR: " . $e->getMessage());
        }
    }
}
