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
        public Employee $employee,
        public string $message,
        public ?string $urlFile = null,
        public int | string $sender = 0,
        public ?array $location = null
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
            $provider->sendMessage($this->sender, "Proses pelaporan. Mohon ikuti langkah-langkah di bawah ini 👇");
            $handler = \App\Services\BotHandlers\BotHandlerFactory::create('telegram');
            $handler->handle($this->sender, '/lapor', []);
        } elseif ($intent === 'attendance') {
            $provider->sendMessage($this->sender, "Proses absensi. Silakan pilih jenis absensi Anda di bawah ini 👇");
            $handler = \App\Services\BotHandlers\BotHandlerFactory::create('telegram');
            $handler->handle($this->sender, '/absen', []);

        } elseif ($intent === 'gmv_report') {
            $stateService = new \App\Services\DatabaseConversationState();
            
            // Cek batasan laporan GMV harian (maksimal 3x)
            $gmvCount = \App\Models\GmvReport::where('employee_id', $this->employee->id)
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->count();
                
            if ($gmvCount >= 3) {
                $provider->sendMessage($this->sender, "⚠️ Kamu sudah mengirimkan laporan GMV sebanyak 3 kali hari ini.\nBatas maksimal laporan GMV adalah 3 kali sehari. Terima kasih atas kerja kerasmu!");
                return;
            }

            $gmvAccount = $analysis['gmv_account'] ?? null;
            $gmvStart = $analysis['gmv_start'] ?? null;
            $gmvEnd = $analysis['gmv_end'] ?? null;
            
            $isComplete = !empty($gmvAccount) && !empty($gmvStart) && !empty($gmvEnd);

            if ($isComplete && $this->urlFile) {
                ProcessGmvReportJob::dispatch($this->employee->id, $this->urlFile, $this->sender, $gmvAccount, $gmvStart, $gmvEnd);
                $provider->sendMessage($this->sender, "📸 Data lengkap. Sistem sedang memproses tangkapan layar Anda, mohon tunggu sebentar.");
            } elseif ($isComplete && !$this->urlFile) {
                $stateService->setCurrentStep($this->sender, 'awaiting_gmv_screenshot', [
                    'employee_id' => $this->employee->id,
                    'account_name' => $gmvAccount,
                    'live_start' => $gmvStart,
                    'platform' => $analysis['gmv_platform'] ?? null
                ]);
                $provider->sendMessage($this->sender, "📸 Silakan kirimkan *screenshot GMV* Anda.\n\n_(Mohon pastikan tangkapan layar jelas dan bukan foto dari layar perangkat lain)_");
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
                $reply .= "\n\n📝 *Isi Laporanmu:*\n_{$todaysReportContent}_";
            }
            $provider->sendMessage($this->sender, $reply);

        } elseif ($intent === 'edit_report') {
            $isHostLive = strtolower($this->employee->division?->name ?? '') === 'host live';
            $isGmvEdit = $isHostLive && (str_contains(strtolower($this->message), 'gmv') || str_contains(strtolower($this->message), 'omset'));

            if ($isGmvEdit) {
                // Ekstrak angka saja
                $newAmountStr = preg_replace('/\D/', '', $extractedData);
                if (!$newAmountStr) {
                    $newAmountStr = preg_replace('/\D/', '', $this->message);
                }

                if ($newAmountStr) {
                    $gmvReport = \App\Models\GmvReport::where('employee_id', $this->employee->id)
                        ->whereDate('created_at', now()->format('Y-m-d'))
                        ->orderBy('id', 'desc')
                        ->first();
                        
                    if ($gmvReport) {
                        $gmvReport->update(['gmv_amount' => $newAmountStr]);
                        $formatted = number_format((float)$newAmountStr, 0, ',', '.');
                        $provider->sendMessage($this->sender, "✅ Sip! Omset GMV kamu hari ini udah berhasil diperbarui jadi *Rp {$formatted}*.");
                        return;
                    } else {
                        $provider->sendMessage($this->sender, "⚠️ Kamu belum ngirim laporan GMV hari ini, jadi belum ada yang bisa diedit.");
                        return;
                    }
                }
            }

            $report = \App\Models\SmartDailyReport::where('employee_id', $this->employee->id)
                ->whereDate('report_date', now()->format('Y-m-d'))
                ->first();
            
            if (!$report) {
                $provider->sendMessage($this->sender, "⚠️ Anda belum memiliki laporan hari ini yang dapat diubah. Silakan kirim laporan baru Anda secara langsung.");
            } else {
                $cleanExtract = trim($extractedData);
                // Jika AI berhasil mengekstrak teks laporan baru, dan bukan sekadar mengulang chat user:
                if (strlen($cleanExtract) > 10 && $cleanExtract !== trim($this->message)) {
                    // Biarkan ProcessSmartDailyReportJob yang handle penggabungan/AI-nya
                    \App\Jobs\ProcessSmartDailyReportJob::dispatchSync($this->employee->id, $cleanExtract, (string)$this->sender);
                    $provider->sendMessage($this->sender, "✅ Pembaruan berhasil! Laporan tambahan Anda berhasil diproses dan digabungkan oleh AI!");
                } else {
                    $stateService = new \App\Services\DatabaseConversationState();
                    $stateService->setCurrentStep($this->sender, 'awaiting_edit_report_text');
                    $provider->sendMessage($this->sender, "📝 Silakan ketik *teks laporan revisi* Anda secara lengkap. Laporan lama Anda akan digantikan dengan data baru ini.");
                }
            }

        } elseif ($intent === 'end_conversation' || $intent === 'general_chat') {
            $menuHint = "\n\n_Untuk akses cepat, gunakan menu:_ 📋 */absen* | 📝 */lapor* | 👤 */edit_profil*";
            $provider->sendMessage($this->sender, $reply . $menuHint);
        } else {
            $provider->sendMessage($this->sender, $reply);
        }
    }
}
