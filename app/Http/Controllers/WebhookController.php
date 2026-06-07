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
    public function receive(Request $request, MessageClassifier $classifier)
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
            $balasan = "Maaf, nomor WA Anda belum terdaftar di sistem kami. Ketik /daftar untuk melakukan pendaftaran mandiri. 🙏";
            $provider->sendMessage($sender, $balasan);
            return response()->json(['status' => false, 'message' => 'Unregistered number']);
        }

        $this->processMessage($employee, $message, $urlFile, $sender, $classifier);

        return response()->json(['status' => true]);
    }

    public function receiveTelegram(Request $request, MessageClassifier $classifier)
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
            $balasan = "Maaf, Telegram Anda belum terdaftar di sistem kami. Ketik /daftar untuk melakukan pendaftaran mandiri. 🙏";
            $provider->sendMessage($chatId, $balasan);
            return response()->json(['status' => false, 'message' => 'Unregistered user']);
        }

        $this->processMessage($employee, $message, $urlFile, $chatId, $classifier);

        return response()->json(['status' => true]);
    }

    private function processMessage($employee, $message, $urlFile, $sender, MessageClassifier $classifier): void
    {
        $division = $employee->division->name ?? null;
        $type = $classifier->classify($sender, $message, !empty($urlFile), $division);
        $cleanContent = preg_replace('/(#lapor|\/lapor)\s*/i', '', $message);

        Log::info("PESAN MASUK! Tipe: $type | Dari: {$employee->name} | Isi: $cleanContent");

        $provider = MessageProviderFactory::create();

        if ($type === 'greeting') {
            $ai = new AiResponseService();
            $sapaan = $ai->greetingMenu($employee->name);

            $menu  = "{$sapaan}\n\n";
            $menu .= "📌 *Menu Herbigreen Bot:*\n\n";
            $menu .= "1️⃣ */lapor* — Kirim laporan harian\n";
            $menu .= "2️⃣ */absen* — Lapor izin/sakit/cuti\n";
            $menu .= "3️⃣ */status* — Cek status laporanmu hari ini\n";
            $menu .= "4️⃣ */bantuan* — Panduan penggunaan\n\n";
            $menu .= "Ketik salah satu perintah di atas ya! 😊";

            $provider->sendMessage($sender, $menu);
            Log::info("KASIR: Greeting terdeteksi, menu dikirim ke {$employee->name}");

        } elseif ($type === 'daily_report') {
            $sudahLapor = Report::where('employee_id', $employee->id)
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->exists();

            if ($sudahLapor) {
                Log::warning("KASIR: Laporan {$employee->name} ditolak karena sudah lapor hari ini.");
                $balasan = "Yth. {$employee->name}, Anda sudah mengirimkan laporan untuk hari ini. Laporan hanya perlu dikirimkan satu kali dalam sehari. Terima kasih.";
                $provider->sendMessage($sender, $balasan);
            } else {
                ProcessDailyReportJob::dispatch($employee->id, $cleanContent, $urlFile);
                Log::info("KASIR: Nota Laporan dilempar ke dapur!");
                $provider->sendMessage($sender, "✅ *Terima Kasih*\nLaporan harian Anda telah berhasil dicatat ke dalam sistem kami.");
            }

        } elseif ($type === 'attendance') {
            ProcessAttendanceJob::dispatch($employee->id, $message);
            Log::info("KASIR: Nota Absen dilempar ke dapur!");
            $provider->sendMessage($sender, "✅ *Tercatat*\nInformasi absensi/izin Anda telah berhasil diperbarui di sistem absensi.");

        } elseif ($type === 'gmv_report') {
            ProcessGmvReportJob::dispatch($employee->id, $urlFile);
            Log::info("KASIR: Nota GMV (Screenshot) dilempar ke dapur!");
            $provider->sendMessage($sender, "✅ *Laporan GMV Diterima*\nScreenshot laporan GMV Anda sedang diproses. Terima kasih atas kerja kerasnya hari ini.");
        }
    }
}
