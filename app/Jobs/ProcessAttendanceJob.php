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
        $messageLower = strtolower($this->message);

        $attendanceType = 'alpa';
        if (str_contains($messageLower, 'sakit')) $attendanceType = 'sakit';
        elseif (str_contains($messageLower, 'cuti')) $attendanceType = 'cuti';
        elseif (str_contains($messageLower, 'izin')) $attendanceType = 'izin';

        Attendance::create([
            'employee_id' => $this->employeeId,
            'type'        => $attendanceType,
            'note'        => $this->message,
            'date'        => now()->format('Y-m-d'),
        ]);

        Log::info("KOKI ABSEN: ID {$this->employeeId} berhasil absen dengan status: {$attendanceType}");
    }
}
