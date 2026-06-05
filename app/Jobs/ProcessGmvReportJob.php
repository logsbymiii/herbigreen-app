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
use App\Models\GmvReports;

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

        $response = Http::get($this->urlFile);

        if ($response->successful()) {
            $filename = 'gmv_' . $this->employeeId . '_' . now()->format('Ymd_His') . '_' . Str::random(5) . '.jpg';
            $path = 'public/gmv/' . $filename;

            Storage::put($path, $response->body());

            GmvReports::create([
                'employee_id'       => $this->employeeId,
                'screenshot_path'   => $path,
                'gmv_amount'        => null,
                'raw_ocr_text'      => null,
                'live_date'         =>now()->format('Y-m-d'),
            ]);

            Log::info("KOKI GMV: Berhasil simpan screenshot GMV di {$path}. (Integrasi AI tunggu Fase 6)");
        } else {
            throw new \Exception("KOKI GMV GAGAL: File dari URL gak bisa didownload. Status: " . $response->status());
        }
    }
}
