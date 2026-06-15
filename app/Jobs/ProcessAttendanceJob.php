<?php

namespace App\Jobs;

use App\Models\Attendance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessAttendanceJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public int $employeeId;
    public string $message;
    public string $attendanceType;
    public ?string $mediaPath;

    public function __construct(int $employeeId, string $message, string $attendanceType = 'izin', ?string $mediaPath = null)
    {
        $this->employeeId = $employeeId;
        $this->message = $message;
        $this->attendanceType = $attendanceType;
        $this->mediaPath = $mediaPath;
    }

    public function handle(): void
    {
        $finalPath = null;

        if (!empty($this->mediaPath)) {
            try {
                $response = Http::withoutVerifying()->timeout(30)->get($this->mediaPath);

                if ($response->successful()) {
                    $filename = 'attendances/' . Str::random(30) . '.jpg';
                    $stored = Storage::disk('r2')->put($filename, $response->body(), 'public');

                    if ($stored) {
                        $finalPath = $filename;
                    }
                }
            } catch (\Exception $e) {
                Log::error("KOKI ABSEN ERROR (Download Foto): " . $e->getMessage());
            }
        }

        Attendance::create([
            'employee_id' => $this->employeeId,
            'type'        => $this->attendanceType,
            'note'        => $this->message,
            'date'        => now()->format('Y-m-d'),
            'proof_path'  => $finalPath,
        ]);

        Log::info("KOKI ABSEN: ID {$this->employeeId} berhasil absen dengan status: {$this->attendanceType}");
    }
}
