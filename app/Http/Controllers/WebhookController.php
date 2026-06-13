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
    }

    public function receiveTelegram(Request $request)
    {
        Log::info("RAW DATA DARI TELEGRAM:", $request->all());

        $update = $request->all();
        if (!isset($update['message'])) {
            return response()->json(['status' => false, 'message' => 'No message in update']);
        }

        $message_data = $update['message'];
        $chatId = $message_data['from']['id'] ?? null;
        $message = $message_data['text'] ?? $message_data['caption'] ?? '';
        $urlFile = null;

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
    }

    private function processMessage($employee, $message, $urlFile, $sender): void
    {
        $division = $employee->division?->name ?? 'Umum';
        
        Log::info("PESAN MASUK DARI {$employee->name}: $message");

        $todaysReportContent = null;
        if (strtolower($division) === 'host live') {
            $gmvReport = \App\Models\GmvReport::where('employee_id', $employee->id)
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->first();
            if ($gmvReport) {
                $todaysReportContent = "Laporan GMV: Rp " . number_format($gmvReport->gmv_amount, 0, ',', '.') . " (" . $gmvReport->order_count . " pesanan)";
            }
        }

        if (!$todaysReportContent) {
            $todaysReport = \App\Models\Report::where('employee_id', $employee->id)
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->first();
            $todaysReportContent = $todaysReport?->note;
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

            ProcessAttendanceJob::dispatch($employee->id, $extractedData, $attendanceType);
            $provider->sendMessage($sender, $reply);

        } elseif ($intent === 'gmv_report') {
            if ($urlFile) {
                ProcessGmvReportJob::dispatch($employee->id, $urlFile, $sender);
            } else {
                ProcessGmvReportJob::dispatch($employee->id, '', $sender);
            }
            $provider->sendMessage($sender, $reply);

        } elseif ($intent === 'status') {
            // Karena AI sudah membalas status dengan natural, kita cukup kirim balasannya
            $provider->sendMessage($sender, $reply);
            
        } else {
            // General chat, bot cuma balas obrolan biasa
            $provider->sendMessage($sender, $reply);
        }
    }
}
