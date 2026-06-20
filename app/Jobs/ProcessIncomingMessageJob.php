<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\Report;
use App\Services\AiResponseService;
use App\Services\MessageProviderFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessIncomingMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Employee $employee,
        protected string $message,
        protected ?string $urlFile,
        protected string|int $sender
    ) {}

    public function handle(): void
    {
        $division = $this->employee->division?->name ?? 'Umum';
        
        Log::info("PESAN MASUK DARI {$this->employee->name}: {$this->message} (Queued)");

        $todaysReportContent = null;
        if (strtolower($division) === 'host live') {
            $gmvReports = \App\Models\GmvReport::where('employee_id', $this->employee->id)
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->get();
            if ($gmvReports->count() > 0) {
                $details = [];
                $totalGmv = 0;
                foreach ($gmvReports as $report) {
                    $platform = $report->platform ?? 'Lainnya';
                    $details[] = "{$platform}: Rp " . number_format($report->gmv_amount, 0, ',', '.');
                    $totalGmv += $report->gmv_amount;
                }
                $todaysReportContent = "Laporan GMV hari ini:\n- " . implode("\n- ", $details) . "\nTotal: Rp " . number_format($totalGmv, 0, ',', '.');
            }
        }

        if (!$todaysReportContent) {
            $todaysReport = Report::where('employee_id', $this->employee->id)
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->first();
            $todaysReportContent = $todaysReport?->content;
        }

        $todaysAttendance = \App\Models\Attendance::where('employee_id', $this->employee->id)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->first();
        $todaysAttendanceStatus = $todaysAttendance ? "Status Absen Hari Ini: " . strtoupper($todaysAttendance->type) . " ({$todaysAttendance->reason})" : null;

        $ai = new AiResponseService();
        $analysis = $ai->analyzeIntentAndReply($this->employee->name, $division, $this->message, !empty($this->urlFile), $todaysReportContent, $todaysAttendanceStatus);
        
        $intent = $analysis['intent'] ?? 'general_chat';
        $reply = $analysis['reply'] ?? "Halo {$this->employee->name}! Ada yang bisa kubantu?";
        $extractedData = $analysis['extracted_data'] ?? $this->message;

        Log::info("AI INTENT: {$intent} | DATA: {$extractedData}");

        $provider = MessageProviderFactory::create();

        if ($intent === 'report') {
            $sudahLapor = false;
            
            // Bypass limit for admin role so they can test reports multiple times
            if ($this->employee->role !== 'admin') {
                $sudahLapor = Report::where('employee_id', $this->employee->id)
                    ->whereDate('created_at', now()->format('Y-m-d'))
                    ->exists();
            }

            if ($sudahLapor) {
                $provider->sendMessage($this->sender, "Eh, kayanya kamu udah lapor deh hari ini! Laporannya cukup sekali sehari aja yaa. Semangat terus! 🙌");
                return;
            } else {
                ProcessDailyReportJob::dispatch($this->employee->id, $this->message, $this->urlFile);
                ProcessSmartDailyReportJob::dispatch($this->employee->id, $this->message, $this->sender);
                $provider->sendMessage($this->sender, $reply);
            }

        } elseif ($intent === 'attendance') {
            $attendanceType = $analysis['attendance_type'] ?? 'izin';
            $attendanceType = strtolower(trim(explode(' ', $attendanceType)[0])); 
            if (!in_array($attendanceType, ['sakit', 'izin', 'cuti', 'telat'])) $attendanceType = 'izin';

            ProcessAttendanceJob::dispatch($this->employee->id, $this->message, $attendanceType, $this->urlFile);
            $provider->sendMessage($this->sender, $reply);

        } elseif ($intent === 'gmv_report') {
            $stateService = new \App\Services\DatabaseConversationState();
            
            $gmvAccount = $analysis['gmv_account'] ?? null;
            $gmvStart = $analysis['gmv_start'] ?? null;
            $gmvEnd = $analysis['gmv_end'] ?? null;
            
            $isComplete = !empty($gmvAccount) && !empty($gmvStart) && !empty($gmvEnd);

            if ($isComplete && $this->urlFile) {
                ProcessGmvReportJob::dispatch($this->employee->id, $this->urlFile, $this->sender, $gmvAccount, $gmvStart, $gmvEnd);
                $provider->sendMessage($this->sender, "📸 Data komplit! Aku baca screenshot-nya dulu ya... tunggu bentar!");
            } elseif ($isComplete && !$this->urlFile) {
                $stateService->setCurrentStep($this->sender, 'awaiting_gmv_screenshot', [
                    'employee_id' => $this->employee->id,
                    'account_name' => $gmvAccount,
                    'live_start' => $gmvStart,
                    'live_end' => $gmvEnd,
                ]);
                $provider->sendMessage($this->sender, "Sip, infonya udah kucatat! Sekarang kirim *screenshot GMV*-nya ya 📸\n\n_(Pastikan kirim Screenshot Asli dari HP ya, jangan foto layar HP pakai HP lain)_");
            } elseif ($this->urlFile) {
                $stateService->setCurrentStep($this->sender, 'awaiting_gmv_account', [
                    'employee_id' => $this->employee->id,
                    'url_file' => $this->urlFile,
                ]);
                $provider->sendMessage($this->sender, "📸 Screenshot GMV diterima! Biar laporannya lengkap, ketik *nama akun* yang kamu pakai live dulu yuk\n\n_Contoh: HERBITOK USQI_");
            } else {
                $stateService->setCurrentStep($this->sender, 'awaiting_gmv_account', [
                    'employee_id' => $this->employee->id,
                ]);
                $provider->sendMessage($this->sender, "Boleh! Laporan GMV ya? Ketik *nama akun* yang kamu pakai live dulu yuk\n\n_Contoh: HERBITOK USQI_");
            }

        } elseif ($intent === 'status') {
            if ($todaysReportContent) {
                $reply .= "\n\n```text\n{$todaysReportContent}\n```";
            }
            $provider->sendMessage($this->sender, $reply);
        } else {
            $provider->sendMessage($this->sender, $reply);
        }
    }
}
