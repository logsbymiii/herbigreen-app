<?php

namespace App\Services\BotHandlers;

use App\Models\Employee;
use App\Models\Division;
use App\Jobs\ProcessDailyReportJob;
use App\Jobs\ProcessSmartDailyReportJob;
use App\Jobs\ProcessAttendanceJob;
use App\Jobs\ProcessGmvReportJob;
use App\Services\AiResponseService;

class TelegramBotCommandHandler extends BaseBotCommandHandler
{
    public function handle(int | string $identifier, string $message, array $rawUpdate): array
    {
        $chatId = $identifier;
        $command = $this->getCommandName($message);

        if ($command) {
            $this->logConversation($chatId, "command_{$command}", $message);

            return match ($command) {
                'start'   => $this->handleStart($chatId),
                'daftar'  => $this->handleDaftar($chatId),
                'bantuan' => $this->handleBantuan($chatId),
                'lapor'   => $this->handleLapor($chatId),
                'absen'   => $this->handleAbsen($chatId),
                'izin'    => $this->handleAbsen($chatId), // alias /absen
                'edit_laporan' => $this->handleEditLaporan($chatId),
                'edit_profil'  => $this->handleEditProfil($chatId),
                'gmv'          => $this->handleGmv($chatId, $message),
                default   => ['status' => false, 'message' => 'Command tidak dikenal'],
            };
        }

        // Jika bukan command, cek apakah ada conversation ongoing
        $currentStep = $this->conversationState->getCurrentStep($chatId);

        if ($currentStep && $currentStep !== 'start') {
            $this->logConversation($chatId, $currentStep, $message);
            return $this->handleConversationStep($chatId, $currentStep, $message, $rawUpdate);
        }

        return ['status' => false, 'message' => 'Tidak ada command atau conversation aktif'];
    }

    private function handleLapor(int | string $chatId): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();

        if (!$employee) {
            $this->sendMessage($chatId, "❌ Kamu belum terdaftar. Ketik /daftar untuk mendaftar dulu ya!");
            return ['status' => true, 'message' => 'Not registered'];
        }

        $nama = $employee->name;
        $isHostLive = strtolower($employee->division->name ?? '') === 'host live';

        // AI generate sapaan yang beda tiap hari
        $ai = new AiResponseService();
        $sapaan = $ai->greetingLapor($nama);

        $menu = "{$sapaan}\n\n";
        $menu .= "Mau laporan apa hari ini?\n\n";
        $menu .= "1️⃣ Laporan Harian (teks)\n";
        $menu .= "2️⃣ Laporan Harian + Foto\n";
        if ($isHostLive) {
            $menu .= "3️⃣ Laporan GMV (Screenshot)\n";
        }
        $menu .= "\nBalas dengan angka pilihanmu!";

        $this->conversationState->setCurrentStep($chatId, 'awaiting_report_type', [
            'is_host_live' => $isHostLive,
        ]);

        $this->sendMessage($chatId, $menu);
        return ['status' => true, 'message' => 'Lapor menu shown'];
    }

    private function handleGmv(int | string $chatId, string $message): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();

        if (!$employee) {
            $this->sendMessage($chatId, "❌ Kamu belum terdaftar. Ketik /daftar untuk mendaftar dulu ya!");
            return ['status' => true, 'message' => 'Not registered'];
        }

        if (strtolower($employee->division?->name) !== 'host live') {
            $this->sendMessage($chatId, "❌ Fitur ini khusus untuk divisi Host Live ya!");
            return ['status' => true, 'message' => 'Not host live'];
        }

        // Ekstrak angka dari pesan /gmv [angka]
        $parts = explode(' ', $message);
        if (count($parts) < 2) {
            $this->sendMessage($chatId, "❌ Format salah. Ketik manual dengan format: */gmv [angka_omset]* (contoh: */gmv 500000*)");
            return ['status' => true];
        }

        $amountRaw = preg_replace('/\D/', '', $parts[1]);
        if (empty($amountRaw)) {
            $this->sendMessage($chatId, "❌ Angka tidak valid. Pastikan hanya mengetik angka ya.");
            return ['status' => true];
        }

        $gmvAmount = (int) $amountRaw;

        // Bikin state untuk konfirmasi manual
        $this->conversationState->setCurrentStep($chatId, 'waiting_gmv_confirmation', [
            'employee_id' => $employee->id,
            'screenshot_path' => 'manual_input',
            'gmv_amount' => $gmvAmount,
            'order_count' => 0,
            'product_sold' => 0,
            'viewers_count' => 0,
            'highest_viewers' => 0,
            'platform' => 'Manual',
            'raw_ocr_text' => 'Manual Input',
            'live_date' => now()->format('Y-m-d'),
        ]);

        $formattedGmv = number_format($gmvAmount, 0, ',', '.');
        $msg = "📝 *Laporan Omset Manual Diterima*\n\n"
             . "💰 GMV/Omset: *Rp {$formattedGmv}*\n\n"
             . "Udah bener belum angkanya? 🤔\n"
             . "(Balas: *Ya* / *Tidak*)";
        
        $this->sendMessage($chatId, $msg);
        
        return ['status' => true];
    }

    private function handleAbsen(int | string $chatId): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();

        if (!$employee) {
            $this->sendMessage($chatId, "❌ Kamu belum terdaftar. Ketik /daftar untuk mendaftar dulu ya!");
            return ['status' => true, 'message' => 'Not registered'];
        }

        // AI generate sapaan yang empatik
        $ai = new AiResponseService();
        $sapaan = $ai->greetingAbsen($employee->name);

        $menu = "{$sapaan}\n\nMau lapor apa?\n\n";
        $menu .= "1️⃣ Sakit\n";
        $menu .= "2️⃣ Izin\n";
        $menu .= "3️⃣ Cuti\n";
        $menu .= "\nBalas dengan angka pilihanmu!";

        $this->conversationState->setCurrentStep($chatId, 'awaiting_absen_type');
        $this->sendMessage($chatId, $menu);
        return ['status' => true, 'message' => 'Absen menu shown'];
    }

    private function processReportType(int | string $chatId, string $message, array $rawUpdate): array
    {
        $choice = strtolower(trim($message));
        $tempData = $this->conversationState->getTempData($chatId);
        $isHostLive = $tempData['is_host_live'] ?? false;

        // Toleransi Typo Tingkat Dewa
        if (in_array($choice, ['1', 'satu', 'pertama'])) $choice = '1';
        if (in_array($choice, ['2', 'dua', 'kedua'])) $choice = '2';
        if (in_array($choice, ['3', 'tiga', 'ketiga'])) $choice = '3';

        if ($choice === '1') {
            $this->conversationState->setCurrentStep($chatId, 'awaiting_report_text');
            $this->sendMessage($chatId, "📝 Silakan ketik laporan harianmu sekarang!");
            return ['status' => true];
        } elseif ($choice === '2') {
            $this->conversationState->setCurrentStep($chatId, 'awaiting_report_text');
            $this->sendMessage($chatId, "📸 Silakan kirim *foto beserta caption* laporan harianmu!");
            return ['status' => true];
        } elseif ($choice === '3' && $isHostLive) {
            $employee = Employee::where('telegram_id', $chatId)->first();
            $this->conversationState->setCurrentStep($chatId, 'awaiting_gmv_account', [
                'employee_id' => $employee->id,
            ]);
            $this->sendMessage($chatId, "📝 Oke! Ketik *nama akun* yang kamu pakai live ya\n\n_Contoh: HERBITOK USQI_");
            return ['status' => true];
        } else {
            $this->sendMessage($chatId, "❌ Pilihan tidak valid. Balas dengan angka 1, 2, atau 3 ya!");
            return ['status' => true];
        }
    }

    private function processReportText(int | string $chatId, string $message): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();

        if (!$employee) {
            $this->conversationState->clearState($chatId);
            return ['status' => false];
        }

        // Validasi: laporan tidak boleh kosong atau terlalu pendek
        $cleanMessage = trim($message);
        if (strlen($cleanMessage) < 10) {
            $this->sendMessage($chatId, "⚠️ Laporan terlalu singkat, *{$employee->name}*!\nMinimal 10 karakter ya. Ceritain sedikit aktivitas hari ini! 📝");
            return ['status' => true];
        }

        $sudahLapor = false;
        if ($employee->role !== 'admin') {
            $sudahLapor = \App\Models\Report::where('employee_id', $employee->id)
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->exists();
        }

        if ($sudahLapor) {
            $this->conversationState->clearState($chatId);
            $this->sendMessage($chatId, "⚠️ Kamu sudah lapor hari ini, *{$employee->name}*. Laporan hanya bisa dikirim sekali sehari ya!");
            return ['status' => true];
        }

        ProcessSmartDailyReportJob::dispatch($employee->id, $cleanMessage, (string) $chatId);
        $this->conversationState->clearState($chatId);

        // AI generate konfirmasi yang beda tiap hari
        $ai = new AiResponseService();
        $konfirmasi = $ai->confirmLaporan($employee->name);
        $this->sendMessage($chatId, $konfirmasi);
        return ['status' => true];
    }

    private function processAbsenType(int | string $chatId, string $message): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();

        if (!$employee) {
            $this->conversationState->clearState($chatId);
            return ['status' => false];
        }

        $typeMap = ['1' => 'sakit', '2' => 'izin', '3' => 'cuti'];
        $choice = strtolower(trim($message));

        // Toleransi Typo Tingkat Dewa
        if (in_array($choice, ['1', 'satu', 'pertama', 'sakit'])) $choice = '1';
        if (in_array($choice, ['2', 'dua', 'kedua', 'izin'])) $choice = '2';
        if (in_array($choice, ['3', 'tiga', 'ketiga', 'cuti'])) $choice = '3';

        if (!isset($typeMap[$choice])) {
            $this->sendMessage($chatId, "❌ Pilihan tidak valid. Balas dengan 1 (Sakit), 2 (Izin), atau 3 (Cuti) ya!");
            return ['status' => true];
        }

        $type = $typeMap[$choice];
        ProcessAttendanceJob::dispatch($employee->id, $type);
        $this->conversationState->clearState($chatId);

        // AI generate konfirmasi yang kontekstual
        $ai = new AiResponseService();
        $konfirmasi = $ai->confirmAbsen($employee->name, $type);
        $this->sendMessage($chatId, $konfirmasi);
        return ['status' => true];
    }

    private function handleStart(int | string $chatId): array
    {
        $this->conversationState->clearState($chatId);

        $employee = Employee::where('telegram_id', $chatId)->first();

        if ($employee) {
            $isHostLive = strtolower($employee->division?->name ?? '') === 'host live';
            
            $welcome = "👋 *Halo {$employee->name}, selamat datang kembali di Herbigreen Bot!*\n\n"
                     . "Sekarang aku udah lebih pintar lho! Kamu nggak perlu lagi ngapalin command-command kaku.\n\n"
                     . "🗣️ *Tinggal ajak ngobrol aja, contohnya:*\n"
                     . "• _\"min, aku hari ini sakit, gausah masuk ya\"_ (Otomatis absen)\n"
                     . "• _\"nih laporan jualan hari ini, laku 10 paket\"_ (Otomatis lapor)\n"
                     . "• _\"laporanku hari ini udah masuk blm ya?\"_ (Cek status)\n\n";

            if ($isHostLive) {
                $welcome .= "📊 *Khusus Divisi Host Live:*\n"
                          . "Untuk kirim Laporan GMV beserta screenshot, ketik */lapor* lalu pilih angka *3*. Aku bakal nuntun kamu step by step!\n\n";
            }

            $welcome .= "Coba aja langsung sapa aku atau kirim laporanmu! 😊";
        } else {
            $welcome = "👋 *Selamat Datang di Herbigreen Bot!*\n\n"
                     . "Untuk menggunakan bot ini, kamu harus daftar dulu ya.\n\n"
                     . "Ketik: */daftar* untuk memulai pendaftaran.";
        }

        $this->sendMessage($chatId, $welcome);

        return ['status' => true, 'message' => 'Start command handled'];
    }

    private function handleDaftar(int | string $chatId): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();

        if ($employee) {
            $this->sendMessage($chatId, "❌ Kamu sudah terdaftar sebagai: *{$employee->name}*\n\nJika ada perubahan data, hubungi admin.");
            return ['status' => true, 'message' => 'Already registered'];
        }

        // AI generate sapaan pembuka yang beda-beda
        $ai = new AiResponseService();
        $sapaan = $ai->greetingDaftar();

        $this->conversationState->setCurrentStep($chatId, 'awaiting_name');
        $this->sendMessage($chatId, "{$sapaan}\n\nYuk mulai daftar! Silakan masukkan *nama lengkapmu*:");

        return ['status' => true, 'message' => 'Daftar conversation started'];
    }

    private function handleBantuan(int | string $chatId): array
    {
        $help = "❓ *Bantuan Herbigreen Bot*\n\n"
              . "Sekarang bot ini sudah pintar pakai AI! Kamu bisa langsung chat pakai bahasa sehari-hari.\n\n"
              . "📝 *Lapor Harian*\n"
              . "Ketik aja misal: _\"laporan hr ini laku 5 botol\"_ atau ketik */lapor*\n\n"
              . "📊 *Laporan GMV (Khusus Host Live)*\n"
              . "1. Ketik */lapor*\n"
              . "2. Pilih angka *3*\n"
              . "3. Ikuti arahan bot (masukkan nama akun, jam, lalu kirim screenshot)\n\n"
              . "🏥 *Lapor Sakit/Izin*\n"
              . "Ketik misal: _\"aku hari ini izin ya ada urusan keluarga\"_\n\n"
              . "🔍 *Cek Status*\n"
              . "Ketik misal: _\"aku udah lapor belum ya hari ini?\"_\n\n"
              . "Pendaftaran tetep pakai command */daftar* ya. Sisanya bebas ngobrol!";

        $this->sendMessage($chatId, $help);

        return ['status' => true, 'message' => 'Help command handled'];
    }

    private function handleConversationStep(int | string $chatId, string $step, string $message, array $rawUpdate = []): array
    {
        $messageLower = strtolower(trim($message));
        if (preg_match('/\b(batal|cancel|gak jadi|ulang|salah)\b/i', $messageLower) && $step !== 'confirm_registration') {
            $this->conversationState->clearState($chatId);
            $this->sendMessage($chatId, "❌ Proses dibatalkan/diulang.\n\nKetik /daftar jika ingin mendaftar ulang dari awal.");
            return ['status' => false, 'message' => 'Process cancelled by user'];
        }

        return match ($step) {
            'awaiting_name'         => $this->processName($chatId, $message),
            'awaiting_division'     => $this->processDivision($chatId, $message),
            'awaiting_phone'        => $this->processPhone($chatId, $message),
            'confirm_registration'  => $this->processConfirmation($chatId, $message),
            'awaiting_report_type'  => $this->processReportType($chatId, $message, $rawUpdate ?? []),
            'awaiting_report_text'  => $this->processReportText($chatId, $message),
            'awaiting_absen_type'   => $this->processAbsenType($chatId, $message),
            'awaiting_edit_report_text'  => $this->processEditReportText($chatId, $message),
            'awaiting_edit_profile_choice' => $this->processEditProfileChoice($chatId, $message),
            'awaiting_edit_profile_value'  => $this->processEditProfileValue($chatId, $message),
            'waiting_gmv_confirmation' => $this->processGmvConfirmation($chatId, $message),
            'awaiting_gmv_account' => $this->processGmvAccount($chatId, $message),
            'awaiting_gmv_time' => $this->processGmvTime($chatId, $message),
            'awaiting_gmv_screenshot' => $this->processGmvScreenshot($chatId, $message, $rawUpdate),
            default => ['status' => false, 'message' => 'Unknown step'],
        };
    }

    private function processGmvAccount(string $chatId, string $message): array
    {
        $accountName = trim($message);

        if (strlen($accountName) < 2) {
            $this->sendMessage($chatId, "❌ Nama akun terlalu pendek. Coba ketik lagi ya!\n\n_Contoh: HERBITOK USQI_");
            return ['status' => true];
        }

        $this->conversationState->updateTempData($chatId, ['account_name' => $accountName]);
        $this->conversationState->setCurrentStep($chatId, 'awaiting_gmv_time');

        $this->sendMessage($chatId, "⏰ Jam berapa live-nya?\n\nKetik format: *[jam mulai]-[jam selesai]*\n_Contoh: 14.00-15.00_");
        return ['status' => true];
    }

    private function processGmvTime(string $chatId, string $message): array
    {
        $timeInput = trim($message);

        // Parse format: 14.00-15.00 atau 14:00-15:00 atau 14.00 - 15.00
        $timeInput = str_replace(' ', '', $timeInput);
        if (preg_match('/(\d{1,2}[\.\:]\d{2})\s*[-–]\s*(\d{1,2}[\.\:]\d{2})/', $timeInput, $matches)) {
            $liveStart = str_replace('.', ':', $matches[1]);
            $liveEnd = str_replace('.', ':', $matches[2]);
        } else {
            $this->sendMessage($chatId, "❌ Format jam belum tepat nih.\n\nKetik format: *[jam mulai]-[jam selesai]*\n_Contoh: 14.00-15.00_");
            return ['status' => true];
        }

        $this->conversationState->updateTempData($chatId, [
            'live_start' => $liveStart,
            'live_end' => $liveEnd,
        ]);
        
        $tempData = $this->conversationState->getTempData($chatId);
        
        if (!empty($tempData['url_file'])) {
            // Langsung dispatch job karena fotonya udah dikirim duluan
            ProcessGmvReportJob::dispatch(
                $tempData['employee_id'],
                $tempData['url_file'],
                $chatId,
                $tempData['account_name'] ?? null,
                $tempData['live_start'] ?? null,
                $tempData['live_end'] ?? null
            );

            $this->sendMessage($chatId, "⏳ Oke, datanya udah lengkap! Aku baca screenshot yang tadi ya... tunggu bentar!");
            $this->conversationState->clearState($chatId);
        } else {
            $this->conversationState->setCurrentStep($chatId, 'awaiting_gmv_screenshot');
            $this->sendMessage($chatId, "📸 Mantap! Sekarang kirim *screenshot GMV*-nya ya\n\n_(Pastikan kirim Screenshot Asli dari HP ya, jangan foto layar HP pakai HP lain biar angkanya jelas dibaca robot)_");
        }

        return ['status' => true];
    }

    private function processGmvScreenshot(string $chatId, string $message, array $rawUpdate = []): array
    {
        // Cek apakah ada foto di update
        $urlFile = null;
        $photos = $rawUpdate['message']['photo'] ?? [];

        if (!empty($photos)) {
            // Ambil foto resolusi tertinggi (index terakhir)
            $bestPhoto = end($photos);
            $fileId = $bestPhoto['file_id'];

            // Download file via Telegram API
            $token = config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN');
            $fileInfoResponse = \Illuminate\Support\Facades\Http::get("https://api.telegram.org/bot{$token}/getFile", [
                'file_id' => $fileId,
            ]);

            if ($fileInfoResponse->successful()) {
                $filePath = $fileInfoResponse->json('result.file_path');
                $urlFile = "https://api.telegram.org/file/bot{$token}/{$filePath}";
            }
        }

        if (!$urlFile) {
            $this->sendMessage($chatId, "❌ Aku belum terima fotonya nih. Coba kirim *screenshot GMV*-nya ya 📸\n\n_(Pastikan kirim sebagai foto, bukan file)_");
            return ['status' => true];
        }

        // Ambil data sesi dari state
        $tempData = $this->conversationState->getTempData($chatId);

        // Dispatch job untuk OCR via Gemini
        ProcessGmvReportJob::dispatch(
            $tempData['employee_id'],
            $urlFile,
            $chatId,
            $tempData['account_name'] ?? null,
            $tempData['live_start'] ?? null,
            $tempData['live_end'] ?? null
        );

        $this->sendMessage($chatId, "⏳ Oke, aku baca dulu screenshot-nya ya... tunggu bentar!");
        // State akan di-clear setelah konfirmasi di processGmvConfirmation
        // Tapi kita clear step supaya nggak loop
        $this->conversationState->clearState($chatId);
        return ['status' => true];
    }

    private function processGmvConfirmation(string $chatId, string $message): array
    {
        $message = strtolower(trim($message));
        $tempData = $this->conversationState->getTempData($chatId);

        if (preg_match('/\b(ya|y|benar|bener|betul|iya+|yes|yosh|yoi|yo|oke|ok|sip|mantap|gas)\b/i', $message)) {
            // Simpan ke database
            \App\Models\GmvReport::create([
                'employee_id'     => $tempData['employee_id'],
                'screenshot_path' => $tempData['screenshot_path'],
                'gmv_amount'      => $tempData['gmv_amount'],
                'order_count'     => $tempData['order_count'] ?? 0,
                'product_sold'    => $tempData['product_sold'] ?? 0,
                'viewers_count'   => $tempData['viewers_count'] ?? 0,
                'highest_viewers' => $tempData['highest_viewers'] ?? 0,
                'platform'        => $tempData['platform'] ?? 'Lainnya',
                'account_name'    => $tempData['account_name'] ?? null,
                'live_start'      => $tempData['live_start'] ?? null,
                'live_end'        => $tempData['live_end'] ?? null,
                'raw_ocr_text'    => $tempData['raw_ocr_text'],
                'live_date'       => $tempData['live_date'],
            ]);

            // Tambahkan juga ke Laporan Harian sebagai history
            $platformDisplay = $tempData['platform'] ?? 'Lainnya';
            $accountDisplay = $tempData['account_name'] ?? '';
            $timeDisplay = '';
            if (!empty($tempData['live_start']) && !empty($tempData['live_end'])) {
                $timeDisplay = "\nJam Live: {$tempData['live_start']} - {$tempData['live_end']}";
            }
            $gmvFormatted = number_format($tempData['gmv_amount'], 0, ',', '.');
            \App\Models\Report::create([
                'employee_id' => $tempData['employee_id'],
                'type' => 'harian',
                'content' => "Laporan GMV [{$platformDisplay}] {$accountDisplay}: Rp {$gmvFormatted}{$timeDisplay}\nPesanan: " . ($tempData['order_count'] ?? 0) . "\nProduk Terjual: " . ($tempData['product_sold'] ?? 0) . "\nPenonton: " . ($tempData['viewers_count'] ?? 0) . "\nPenonton Tertinggi: " . ($tempData['highest_viewers'] ?? 0),
                'reported_at' => now(),
            ]);

            $this->conversationState->clearState($chatId);
            $this->sendMessage($chatId, "✅ Mantap! Laporan GMV berhasil disimpan ke sistem.");
            \Illuminate\Support\Facades\Log::info("KOKI GMV: User confirm YA (Tele). Disimpan.");
        } elseif (in_array($message, ['tidak', 't', 'salah', 'enggak', 'nggak', 'tdk'])) {
            $this->conversationState->clearState($chatId);
            $this->sendMessage($chatId, "❌ Oke, laporan dibatalkan.\n\nCoba kirim ulang gambarnya yang lebih tajam/terang ya biar AI gampang bacanya!");
            \Illuminate\Support\Facades\Log::info("KOKI GMV: User confirm TIDAK (Tele). Dibatalkan.");
        } else {
            $this->sendMessage($chatId, "Aku kurang paham maksudmu. Tolong balas dengan *Ya* jika angkanya benar, atau *Tidak* jika salah.");
        }

        return ['status' => true, 'message' => 'GMV confirmation processed'];
    }

    private function processName(int | string $chatId, string $message): array
    {
        $name = trim($message);

        if (strlen($name) < 3) {
            $this->sendMessage($chatId, "❌ Nama terlalu pendek. Minimal 3 karakter.\n\nSilakan masukkan nama lagi:");
            return ['status' => true, 'message' => 'Invalid name'];
        }

        $this->conversationState->updateTempData($chatId, ['name' => $name]);
        $this->conversationState->setCurrentStep($chatId, 'awaiting_division');

        $divisions = Division::all()->values();
        $divisionList = $divisions->map(fn($d, $index) => ($index + 1) . ". {$d->name}")->implode("\n");

        $this->sendMessage($chatId, "✅ Nama tercatat: *{$name}*\n\n"
                                   . "🏢 Sekarang pilih divisi kamu:\n\n{$divisionList}\n\n"
                                   . "Balas dengan nomor divisi (contoh: 1)");

        return ['status' => true, 'message' => 'Name processed, waiting for division'];
    }

    private function processDivision(int | string $chatId, string $message): array
    {
        $inputIndex = intval(trim($message)) - 1;
        $divisions = Division::all()->values();
        $division = $divisions->get($inputIndex);

        if (!$division) {
            $this->sendMessage($chatId, "❌ Divisi tidak ditemukan. Coba lagi dengan nomor yang benar dari daftar di atas.");
            return ['status' => true, 'message' => 'Invalid division'];
        }

        $this->conversationState->updateTempData($chatId, ['division_id' => $division->id]);
        $this->conversationState->setCurrentStep($chatId, 'awaiting_phone');

        $this->sendMessage($chatId, "✅ Divisi: *{$division->name}*\n\n"
                                   . "📱 Terakhir, masukkan nomor WA kamu:\n"
                                   . "(Format: 62xxx atau 08xx)");

        return ['status' => true, 'message' => 'Division processed'];
    }

    private function processPhone(int | string $chatId, string $message): array
    {
        $phone = preg_replace('/\D/', '', $message);

        if (str_starts_with($phone, '8')) {
            $phone = '62' . substr($phone, 1);
        }

        if (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        if (strlen($phone) < 10 || strlen($phone) > 15) {
            $this->sendMessage($chatId, "❌ Nomor WA tidak valid. Coba lagi.");
            return ['status' => true, 'message' => 'Invalid phone'];
        }

        if (Employee::where('phone', $phone)->exists()) {
            $this->sendMessage($chatId, "❌ Nomor WA ini sudah terdaftar. Gunakan nomor lain.");
            return ['status' => true, 'message' => 'Phone already exists'];
        }

        $this->conversationState->updateTempData($chatId, ['phone' => $phone]);
        $this->conversationState->setCurrentStep($chatId, 'confirm_registration');

        $tempData = $this->conversationState->getTempData($chatId);
        $division = Division::find($tempData['division_id']);

        $this->sendMessage($chatId, "📋 *Konfirmasi Data:*\n\n"
                                   . "👤 Nama: {$tempData['name']}\n"
                                   . "🏢 Divisi: {$division->name}\n"
                                   . "📱 Nomor WA: {$phone}\n\n"
                                   . "Setuju? Ketik: *ya* atau *tidak*");

        return ['status' => true, 'message' => 'Phone processed'];
    }

    private function processConfirmation(int | string $chatId, string $message): array
    {
        $answer = strtolower(trim($message));
        
        $isPositive = preg_match('/\b(ya+|yes+|y+|iya+|oke+|ok+|setuju|gas|betul|bener|benar|hooh)\b/i', $answer);
        $isNegative = preg_match('/\b(tidak|gak|enggak|nggak|no|n|batal|cancel|salah)\b/i', $answer);

        if ($isNegative) {
            $this->conversationState->clearState($chatId);
            $this->sendMessage($chatId, "❌ Pendaftaran dibatalkan. Kamu bisa mengulangi dengan mengetik /daftar");
            return ['status' => true, 'message' => 'Process cancelled by user'];
        }

        if (!$isPositive) {
            // Berarti dia balas hal lain (misal "halo")
            $ai = new AiResponseService();
            // Panggil API Gemini khusus untuk ngeles
            $prompt = "User sedang mendaftar tapi dia malah jawab: '{$message}'. Balas ramah, SANGAT SINGKAT (maks 1 kalimat pendek), santai layaknya teman kerja, dan ingatkan dia untuk balas 'ya' jika setuju dengan data pendaftaran, atau 'tidak' untuk batal.";
            
            // Kita hack sedikit generate langsung
            $reflection = new \ReflectionClass($ai);
            $generateMethod = $reflection->getMethod('generate');
            $generateMethod->setAccessible(true);
            $balasan = $generateMethod->invoke($ai, $prompt, "Halo! Kita kan lagi proses daftar nih, datanya udah bener belum? Ketik *ya* kalau setuju, atau *tidak* buat batalin.");
            
            $this->sendMessage($chatId, $balasan);
            return ['status' => true, 'message' => 'Registration paused for confirmation'];
        }

        $tempData = $this->conversationState->getTempData($chatId);

        $employee = Employee::create([
            'name' => $tempData['name'],
            'division_id' => $tempData['division_id'],
            'phone' => $tempData['phone'],
            'telegram_id' => (string) $chatId,
            'is_active' => true,
        ]);

        $this->conversationState->clearState($chatId);

        $this->sendMessage($chatId, "✅ *Pendaftaran Berhasil!*\n\n"
                                   . "Sip, datamu udah kedaftar ya, {$employee->name}! 🎉\n\n"
                                   . "Btw, nomor ini nantinya bakal dipakai buat komunikasi soal absen dan laporan kerjamu. Kalau ke depannya kamu mau:\n"
                                   . "📝 *Lapor harian* (kerjaan atau omset jualan)\n"
                                   . "🤒 *Izin* nggak masuk, sakit, atau cuti\n"
                                   . "❓ Nanya soal laporanmu hari ini udah terekap atau belum\n\n"
                                   . "Tinggal chat ke sini aja ya, nanti aku bantu proses. Semangat kerjanya! 😊");

        return ['status' => true, 'message' => 'Registration completed'];
    }

    private function handleEditLaporan(int | string $chatId): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();
        if (!$employee) {
            $this->sendMessage($chatId, "❌ Kamu belum terdaftar. Ketik /daftar untuk mendaftar dulu ya!");
            return ['status' => true];
        }

        $sudahLapor = \App\Models\Report::where('employee_id', $employee->id)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->exists();

        if (!$sudahLapor) {
            $this->sendMessage($chatId, "⚠️ Kamu belum mengirim laporan apapun hari ini. Ketik laporan langsung atau pakai /lapor.");
            return ['status' => true];
        }

        $this->conversationState->setCurrentStep($chatId, 'awaiting_edit_report_text');
        $this->sendMessage($chatId, "📝 Oke! Silakan ketik *seluruh teks laporan barumu* untuk hari ini. Laporan lamamu akan diganti dengan yang baru ini.");
        return ['status' => true];
    }

    private function processEditReportText(int | string $chatId, string $message): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();
        $report = \App\Models\Report::where('employee_id', $employee->id)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->first();

        if ($report) {
            $report->update(['content' => trim($message)]);
            $this->conversationState->clearState($chatId);
            $this->sendMessage($chatId, "✅ Sip! Laporanmu hari ini udah berhasil diperbarui.");
        } else {
            $this->conversationState->clearState($chatId);
            $this->sendMessage($chatId, "❌ Gagal memperbarui. Laporan tidak ditemukan.");
        }
        return ['status' => true];
    }

    private function handleEditProfil(int | string $chatId): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();
        if (!$employee) {
            $this->sendMessage($chatId, "❌ Kamu belum terdaftar. Ketik /daftar untuk mendaftar dulu ya!");
            return ['status' => true];
        }

        $info = "👤 *Profil Kamu Saat Ini:*\n"
              . "Nama: {$employee->name}\n"
              . "Divisi: {$employee->division->name}\n"
              . "No WA: {$employee->phone}\n\n"
              . "Pilih data yang mau kamu ubah:\n"
              . "1️⃣ Ubah Nama\n"
              . "2️⃣ Ubah Divisi\n"
              . "3️⃣ Ubah Nomor WA\n\n"
              . "Balas dengan angkanya saja ya!";

        $this->conversationState->setCurrentStep($chatId, 'awaiting_edit_profile_choice');
        $this->sendMessage($chatId, $info);
        return ['status' => true];
    }

    private function processEditProfileChoice(int | string $chatId, string $message): array
    {
        $choice = trim($message);
        if (!in_array($choice, ['1', '2', '3'])) {
            $this->sendMessage($chatId, "❌ Pilihan nggak valid. Balas dengan angka 1, 2, atau 3.");
            return ['status' => true];
        }

        $this->conversationState->setCurrentStep($chatId, 'awaiting_edit_profile_value', ['edit_choice' => $choice]);
        
        if ($choice === '1') {
            $this->sendMessage($chatId, "Ketik *Nama Baru* kamu:");
        } elseif ($choice === '2') {
            $divisions = Division::all()->values();
            $divisionList = $divisions->map(fn($d, $index) => ($index + 1) . ". {$d->name}")->implode("\n");
            $this->sendMessage($chatId, "Pilih *Divisi Baru* kamu:\n\n{$divisionList}\n\nBalas dengan nomor divisi.");
        } else {
            $this->sendMessage($chatId, "Ketik *Nomor WA Baru* kamu (Format: 62xxx / 08xxx):");
        }
        
        return ['status' => true];
    }

    private function processEditProfileValue(int | string $chatId, string $message): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();
        $tempData = $this->conversationState->getTempData($chatId);
        $choice = $tempData['edit_choice'] ?? '1';
        $newValue = trim($message);

        if ($choice === '1') {
            $employee->update(['name' => $newValue]);
            $this->sendMessage($chatId, "✅ Berhasil! Nama kamu sekarang jadi: *{$newValue}*");
        } elseif ($choice === '2') {
            $inputIndex = intval($newValue) - 1;
            $divisions = Division::all()->values();
            $division = $divisions->get($inputIndex);
            
            if (!$division) {
                $this->sendMessage($chatId, "❌ Divisi tidak ditemukan. Ketik ulang nomor divisi yang benar:");
                return ['status' => true];
            }
            $employee->update(['division_id' => $division->id]);
            $this->sendMessage($chatId, "✅ Berhasil! Divisi kamu sekarang jadi: *{$division->name}*");
        } elseif ($choice === '3') {
            $phone = preg_replace('/\D/', '', $newValue);
            if (str_starts_with($phone, '8')) $phone = '62' . substr($phone, 1);
            if (str_starts_with($phone, '08')) $phone = '62' . substr($phone, 1);
            
            $employee->update(['phone' => $phone]);
            $this->sendMessage($chatId, "✅ Berhasil! Nomor WA kamu sekarang jadi: *{$phone}*");
        }

        $this->conversationState->clearState($chatId);
        return ['status' => true];
    }
}
