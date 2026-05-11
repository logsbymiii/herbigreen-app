<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Report;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessDailyReportJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public int $employeeId;
    public string $reportContent;
    public ?string $mediaPath;

    public function __construct(int $employeeId, string $reportContent, ?string $mediaPath)
    {
        $this->employeeId = $employeeId;
        $this->reportContent = $reportContent;
        $this->mediaPath = $mediaPath;
    }

    public function handle(): void
    {
        try {
            $finalPath = null;

            if (!empty($this->mediaPath)) {
                Log::info("KOKI: Nyoba download dari: " . $this->mediaPath);

                // Pake timeout & tanpa verifikasi SSL biar gak rewel
                $response = Http::withoutVerifying()->timeout(30)->get($this->mediaPath);

                if ($response->successful()) {
                    $filename = 'reports/' . Str::random(30) . '.png'; // Paksa .png dulu buat tes

                    // Simpen fisiknya
                    $stored = Storage::disk('public')->put($filename, $response->body());

                    if ($stored) {
                        $finalPath = $filename;
                        Log::info("KOKI: BERHASIL SIMPEN KE STORAGE: " . $filename);
                    } else {
                        Log::error("KOKI: GAGAL NULIS FILE KE FOLDER STORAGE!");
                    }
                } else {
                    Log::error("KOKI: GAGAL DOWNLOAD. Status Code: " . $response->status());
                }
            }

            Report::create([
                'employee_id' => $this->employeeId,
                'type'        => 'daily_report',
                'content'     => $this->reportContent,
                'media_path'  => $finalPath ?? $this->mediaPath,
                'reported_at' => now(),
            ]);

            Log::info("KOKI: Proses Selesai!");
        } catch (\Exception $e) {
            Log::error("KOKI CRASH: " . $e->getMessage());
        }
    }
}
