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
    public string $attendanceType;

    public function __construct(int $employeeId, string $message, string $attendanceType = 'izin')
    {
        $this->employeeId = $employeeId;
        $this->message = $message;
        $this->attendanceType = $attendanceType;
    }

    public function handle(): void
    {
        Attendance::create([
            'employee_id' => $this->employeeId,
            'type'        => $this->attendanceType,
            'note'        => $this->message,
            'date'        => now()->format('Y-m-d'),
        ]);

        Log::info("KOKI ABSEN: ID {$this->employeeId} berhasil absen dengan status: {$this->attendanceType}");
    }
}
