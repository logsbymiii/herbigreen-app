<?php

namespace App\Jobs;

use App\Models\Attendance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAttendanceJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public int $employeeId;
    public string $message;

    public function __construct(int $employeeId, string $message)
    {
        $this->employeeId = $employeeId;
        $this->message = $message;
    }

    public function handle(): void
    {
        try {
            $messageLower = strtolower($this->message);

            $status = 'other';
            if (str_contains($messageLower, 'izin')) $status = 'izin';
            if (str_contains($messageLower, 'sakit')) $status = 'sakit';
            if (str_contains($messageLower, 'cuti')) $status = 'cuti';

            Attendance::create([
                'employee_id' => $this->employeeId,
                'status'      => $status,
                'notes'       => $this->message,
                'submitted_at'=> now(),
            ]);

            Log::info("KOKI ABSEN: ID {$this->employeeId} berhasil absen dengan status: {$status}");
        } catch (\Exception $e) {
            Log::error("KOKI ABSEN GAGAL: " . $e->getMessage());
        }
    }
}
