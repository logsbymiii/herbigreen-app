<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MessageClassifier;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessDailyReportJob;
use App\Jobs\ProcessAttendanceJob;
use App\Models\Employee;
use App\Jobs\ProcessGmvReportJob;
use App\Models\Report;
use App\Services\MessageProviderFactory;
use App\Services\AiResponseService;

class WebhookController extends Controller
{
    public function receive(Request $request)
    {
        try {
            Log::info("RAW DATA DARI FONNTE:", $request->all());

            $sender = $request->input('sender');
            $message = $request->input('message');
            $urlFile = $request->input('url');

            if(str_starts_with($sender, '08')){
                $sender = '62'. substr($sender, 1);
            }

            // Coba handle sebagai bot command (/daftar, /start) atau percakapan interaktif
            $handler = \App\Services\BotHandlers\BotHandlerFactory::create('fonnte');
            $handlerResult = $handler->handle($sender, (string)$message, $request->all());

            if (isset($handlerResult['status']) && $handlerResult['status'] === true) {
                return response()->json(['status' => true]);
            }

            // Jika bukan command atau percakapan, cek apakah employee sudah terdaftar
            $employee = Employee::where('phone', $sender)->first();

            if (!$employee) {
                Log::warning("Pesan ditolak! Nomor $sender nggak terdaftar.");
                $provider = MessageProviderFactory::create();
                $ai = new AiResponseService();
                $balasan = $ai->greetingUnregistered($message);
                $provider->sendMessage($sender, $balasan);
                return response()->json(['status' => false, 'message' => 'Unregistered number']);
            }

            $this->processMessage($employee, $message, $urlFile, $sender);

            return response()->json(['status' => true]);
        } catch (\Throwable $e) {
            Log::error("WEBHOOK ERROR (Fonnte): " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $sender = $request->input('sender');
            if ($sender) {
                if(str_starts_with($sender, '08')) $sender = '62'. substr($sender, 1);
                $provider = MessageProviderFactory::create();
                $provider->sendMessage($sender, "Duh, sistem pusat lagi pusing nih (Server Error) 🤕. Sabar ya, tunggu bentar terus coba lagi nanti!");
            }
            return response()->json(['status' => false, 'message' => 'Internal server error']);
        }
    }

    public function receiveTelegram(Request $request)
    {
        try {
            Log::info("RAW DATA DARI TELEGRAM:", $request->all());

            $update = $request->all();
            if (!isset($update['message'])) {
                return response()->json(['status' => false, 'message' => 'No message in update']);
            }

            $message_data = $update['message'];
            $chatId = $message_data['from']['id'] ?? null;
            $message = $message_data['text'] ?? $message_data['caption'] ?? '';
            $urlFile = null;

            // Anti-Spam Gambar: Mencegah user mengirim >1 gambar lewat Telegram media group.
            if (isset($message_data['media_group_id'])) {
                $mediaGroupId = $message_data['media_group_id'];
                $cacheKey = "telegram_media_group_{$mediaGroupId}";
                if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                    Log::info("Mengabaikan foto duplikat dari media group: {$mediaGroupId}");
                    return response()->json(['status' => true, 'message' => 'Ignored duplicate photo']);
                }
                // Simpan media_group_id ke cache selama 1 menit (hanya proses foto pertama yang masuk)
                \Illuminate\Support\Facades\Cache::put($cacheKey, true, 60);
                
                // Beritahu user agar tidak kirim banyak-banyak
                $botToken = env('TELEGRAM_BOT_TOKEN');
                \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => "Satu-satu ya kirim fotonya, aku pusing kalau dibombardir! 😂 Aku cuma memproses foto yang pertama masuk ya."
                ]);
            }

            // Ambil URL file dari Telegram jika ada foto atau dokumen
            $fileId = null;
            if (isset($message_data['photo']) && is_array($message_data['photo'])) {
                // Ambil resolusi tertinggi (elemen terakhir dari array photo)
                $fileId = end($message_data['photo'])['file_id'];
            } elseif (isset($message_data['document'])) {
                $fileId = $message_data['document']['file_id'];
            }

            if ($fileId) {
                $botToken = env('TELEGRAM_BOT_TOKEN');
                $response = \Illuminate\Support\Facades\Http::get("https://api.telegram.org/bot{$botToken}/getFile", [
                    'file_id' => $fileId
                ]);

                if ($response->successful()) {
                    $filePath = $response->json('result.file_path');
                    if ($filePath) {
                        // Konstruksi full URL download, sama seperti format URL dari Fonnte
                        $urlFile = "https://api.telegram.org/file/bot{$botToken}/{$filePath}";
                    }
                }
            }

            if (!$chatId || ($message === '' && !$urlFile)) {
                Log::warning("Telegram data tidak lengkap");
                return response()->json(['status' => false, 'message' => 'Incomplete data']);
            }

            // Coba handle sebagai bot command (/daftar, /start) atau percakapan interaktif
            $handler = \App\Services\BotHandlers\BotHandlerFactory::create('telegram');
            $handlerResult = $handler->handle($chatId, $message, $update);

            if (isset($handlerResult['status']) && $handlerResult['status'] === true) {
                return response()->json(['status' => true]);
            }

            // Jika bukan command atau percakapan, cek apakah employee sudah terdaftar
            $employee = Employee::where('telegram_id', $chatId)->first();

            if (!$employee) {
                Log::warning("Pesan Telegram ditolak! Chat ID $chatId nggak terdaftar.");
                $provider = MessageProviderFactory::create();
                $ai = new AiResponseService();
                $balasan = $ai->greetingUnregistered($message);
                $provider->sendMessage($chatId, $balasan);
                return response()->json(['status' => false, 'message' => 'Unregistered user']);
            }

            $this->processMessage($employee, $message, $urlFile, $chatId);

            return response()->json(['status' => true]);
        } catch (\Throwable $e) {
            Log::error("WEBHOOK ERROR (Telegram): " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $chatId = request()->input('message.from.id');
            if ($chatId) {
                $provider = MessageProviderFactory::create();
                $provider->sendMessage($chatId, "Duh, sistem pusat lagi pusing nih (Server Error) 🤕. Sabar ya, tunggu bentar terus coba lagi nanti!");
            }
            return response()->json(['status' => false, 'message' => 'Internal server error']);
        }
    }

    private function processMessage($employee, $message, $urlFile, $sender): void
    {
        $division = $employee->division?->name ?? 'Umum';
        
        Log::info("PESAN MASUK DARI {$employee->name}: $message");

        $todaysReportContent = null;
        if (strtolower($division) === 'host live') {
            $gmvReports = \App\Models\GmvReport::where('employee_id', $employee->id)
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
            $todaysReport = \App\Models\Report::where('employee_id', $employee->id)
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->first();
            $todaysReportContent = $todaysReport?->content;
        }

        $ai = new AiResponseService();
        $analysis = $ai->analyzeIntentAndReply($employee->name, $division, $message, !empty($urlFile), $todaysReportContent);
        
        $intent = $analysis['intent'] ?? 'general_chat';
        $reply = $analysis['reply'] ?? "Halo {$employee->name}! Ada yang bisa kubantu?";
        $extractedData = $analysis['extracted_data'] ?? $message;

        Log::info("AI INTENT: $intent | DATA: $extractedData");

        $provider = MessageProviderFactory::create();

        if ($intent === 'report') {
            $sudahLapor = Report::where('employee_id', $employee->id)
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->exists();

            if ($sudahLapor) {
                // Jangan save lagi, tapi kasih tau kalau udah lapor
                $provider->sendMessage($sender, "Eh, kayanya kamu udah lapor deh hari ini! Laporannya cukup sekali sehari aja yaa. Semangat terus! 🙌");
                return;
            } else {
                ProcessDailyReportJob::dispatch($employee->id, $extractedData, $urlFile);
                $provider->sendMessage($sender, $reply);
            }

        } elseif ($intent === 'attendance') {
            $attendanceType = $analysis['attendance_type'] ?? 'izin';
            // Bersihkan string attendanceType barangkali AI nulis aneh
            $attendanceType = strtolower(trim(explode(' ', $attendanceType)[0])); 
            if (!in_array($attendanceType, ['sakit', 'izin', 'cuti', 'telat'])) $attendanceType = 'izin';

            ProcessAttendanceJob::dispatch($employee->id, $extractedData, $attendanceType, $urlFile);
            $provider->sendMessage($sender, $reply);

        } elseif ($intent === 'gmv_report') {
            $stateService = new \App\Services\DatabaseConversationState();
            if ($urlFile) {
                // Ada foto → simpan foto ke state sementara, lalu tanya akun
                $stateService->setCurrentStep($sender, 'awaiting_gmv_account', [
                    'employee_id' => $employee->id,
                    'url_file' => $urlFile,
                ]);
                $provider->sendMessage($sender, "📸 Screenshot GMV diterima! Biar laporannya lengkap, ketik *nama akun* yang kamu pakai live dulu yuk\n\n_Contoh: HERBITOK USQI_");
            } else {
                // Nggak ada foto → tanya akun
                $stateService->setCurrentStep($sender, 'awaiting_gmv_account', [
                    'employee_id' => $employee->id,
                ]);
                $provider->sendMessage($sender, "Boleh! Laporan GMV ya? Ketik *nama akun* yang kamu pakai live dulu yuk\n\n_Contoh: HERBITOK USQI_");
            }

        } elseif ($intent === 'status') {
            // Karena AI sudah membalas status dengan natural, kita cukup kirim balasannya
            $provider->sendMessage($sender, $reply);
            
        } else {
            // General chat, bot cuma balas obrolan biasa
            $provider->sendMessage($sender, $reply);
        }
    }
}
