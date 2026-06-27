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

        // Notif Management untuk izin/sakit/wfh
        if (in_array($this->attendanceType, ['sakit', 'izin', 'wfh'])) {
            if (\Illuminate\Support\Facades\Storage::exists('management_group_id.txt')) {
                $managementGroupId = \Illuminate\Support\Facades\Storage::get('management_group_id.txt');
                $employee = \App\Models\Employee::find($this->employeeId);
                if ($employee && $managementGroupId) {
                    $provider = \App\Services\MessageProviderFactory::create();
                    $notifText = "🔔 *[Info Kehadiran]*\n{$employee->name} dari divisi {$employee->division?->name} menyatakan *" . strtoupper($this->attendanceType) . "* hari ini.\n\nAlasan: {$this->message}";
                    $provider->sendMessage(trim($managementGroupId), $notifText);
                }
            }
        }

        // Shoutout Komunitas untuk Absen Paling Pagi (Hadir)
        if ($this->attendanceType === 'hadir') {
            $totalHadirToday = Attendance::where('date', now()->format('Y-m-d'))
                                         ->where('type', 'hadir')
                                         ->count();
                                         
            // Jika dia adalah orang pertama yang hadir hari ini
            if ($totalHadirToday === 1 && \Illuminate\Support\Facades\Storage::exists('community_group_id.txt')) {
                $communityGroupId = \Illuminate\Support\Facades\Storage::get('community_group_id.txt');
                $employee = \App\Models\Employee::find($this->employeeId);
                if ($employee && $communityGroupId) {
                    $provider = \App\Services\MessageProviderFactory::create();
                    $time = now()->format('H:i');
                    $msg = "🌅 *MORNING SHOUTOUT!*\n\n"
                         . "Berikan tepuk tangan buat *{$employee->name}* yang jadi pemegang rekor absen paling pagi hari ini (Jam {$time} WIB)! 🏆\n\n"
                         . "Semangat kerjanya, ayo yang lain jangan mau kalah! 🔥";
                    $provider->sendMessage(trim($communityGroupId), $msg);
                }
            }
        }
    }
}
