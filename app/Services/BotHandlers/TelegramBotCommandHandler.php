<?php

namespace App\Services\BotHandlers;

use App\Models\Employee;
use App\Models\Division;
use App\Jobs\ProcessDailyReportJob;
use App\Jobs\ProcessAttendanceJob;
use App\Jobs\ProcessGmvReportJob;

class TelegramBotCommandHandler extends BaseBotCommandHandler
{
    public function handle(int | string $identifier, string $message, array $rawUpdate): array
    {
        $chatId = $identifier;
        $command = $this->getCommandName($message);

        if ($command) {
            $this->logConversation($chatId, "command_{$command}", $message);

            return match ($command) {
                'start' => $this->handleStart($chatId),
                'daftar' => $this->handleDaftar($chatId),
                'bantuan' => $this->handleBantuan($chatId),
                'lapor' => $this->handleLapor($chatId),
                'absen' => $this->handleAbsen($chatId),
                default => ['status' => false, 'message' => 'Command tidak dikenal'],
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

        $menu = "👋 Halo, *{$nama}*! Mau laporan apa hari ini?\n\n";
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

    private function handleAbsen(int | string $chatId): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();

        if (!$employee) {
            $this->sendMessage($chatId, "❌ Kamu belum terdaftar. Ketik /daftar untuk mendaftar dulu ya!");
            return ['status' => true, 'message' => 'Not registered'];
        }

        $nama = $employee->name;
        $menu = "👋 Halo, *{$nama}*! Mau lapor absen apa?\n\n";
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
        $choice = trim($message);
        $tempData = $this->conversationState->getTempData($chatId);
        $isHostLive = $tempData['is_host_live'] ?? false;

        // Cek apakah ada foto yang dikirim langsung
        $hasPhoto = isset($rawUpdate['message']['photo']) || isset($rawUpdate['message']['document']);

        if ($choice === '1') {
            $this->conversationState->setCurrentStep($chatId, 'awaiting_report_text');
            $this->sendMessage($chatId, "📝 Silakan ketik laporan harianmu sekarang!");
            return ['status' => true];
        } elseif ($choice === '2') {
            $this->conversationState->setCurrentStep($chatId, 'awaiting_report_text');
            $this->sendMessage($chatId, "📸 Silakan kirim *foto beserta caption* laporan harianmu!");
            return ['status' => true];
        } elseif ($choice === '3' && $isHostLive) {
            $this->conversationState->clearState($chatId);
            $this->sendMessage($chatId, "📊 Silakan kirim *screenshot GMV* kamu sekarang!");
            return ['status' => true];
        } else {
            $this->sendMessage($chatId, "❌ Pilihan tidak valid. Balas dengan angka yang tersedia ya!");
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

        $sudahLapor = \App\Models\Report::where('employee_id', $employee->id)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->exists();

        if ($sudahLapor) {
            $this->conversationState->clearState($chatId);
            $this->sendMessage($chatId, "⚠️ Kamu sudah lapor hari ini, *{$employee->name}*. Laporan hanya bisa dikirim sekali sehari ya!");
            return ['status' => true];
        }

        ProcessDailyReportJob::dispatch($employee->id, $message, null);
        $this->conversationState->clearState($chatId);
        $this->sendMessage($chatId, "✅ *Terima kasih, {$employee->name}!*\nLaporan harianmu sudah berhasil dicatat. Semangat terus! 💪");
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
        $choice = trim($message);

        if (!isset($typeMap[$choice])) {
            $this->sendMessage($chatId, "❌ Pilihan tidak valid. Balas dengan 1, 2, atau 3 ya!");
            return ['status' => true];
        }

        $type = $typeMap[$choice];
        ProcessAttendanceJob::dispatch($employee->id, $type);
        $this->conversationState->clearState($chatId);
        $this->sendMessage($chatId, "✅ *Tercatat, {$employee->name}!*\nAbsen *{$type}* kamu sudah berhasil direkam. Semoga lekas sembuh/istirahat ya! 🙏");
        return ['status' => true];
    }

    private function handleStart(int | string $chatId): array
    {
        $this->conversationState->clearState($chatId);

        $welcome = "👋 *Selamat Datang di Herbigreen Bot!*\n\n"
                 . "📋 *Pilih menu di bawah:*\n"
                 . "/lapor - Lapor penjualan harian\n"
                 . "/absen - Lapor absen (izin/sakit/cuti)\n"
                 . "/daftar - Daftar sebagai employee\n"
                 . "/bantuan - Petunjuk cara pakai\n\n"
                 . "Ketik perintah di atas untuk memulai!";

        $this->sendMessage($chatId, $welcome);

        return ['status' => true, 'message' => 'Start command handled'];
    }

    private function handleDaftar(int | string $chatId): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();

        if ($employee) {
            $this->sendMessage($chatId, "❌ Kamu sudah terdaftar sebagai: *{$employee->name}*\n\nJika ada perubahan data, hubungi admin.");
            return ['status' => false, 'message' => 'Already registered'];
        }

        $this->conversationState->setCurrentStep($chatId, 'awaiting_name');
        $this->sendMessage($chatId, "📝 Baik! Mari daftar.\n\nSilakan masukkan *nama lengkapmu*:");

        return ['status' => true, 'message' => 'Daftar conversation started'];
    }

    private function handleBantuan(int | string $chatId): array
    {
        $help = "❓ *Panduan Penggunaan Bot Herbigreen*\n\n"
              . "📊 *Lapor Penjualan*\n"
              . "Ketik: /lapor [nominal]\n"
              . "Contoh: /lapor 500000\n\n"
              . "🏥 *Lapor Absen*\n"
              . "Ketik: sakit / izin / cuti\n"
              . "Contoh: sakit (keterangan)\n\n"
              . "👤 *Daftar Baru*\n"
              . "Ketik: /daftar\n"
              . "Ikuti instruksi yang diberikan bot.\n\n"
              . "Untuk bantuan lainnya, hubungi admin.";

        $this->sendMessage($chatId, $help);

        return ['status' => true, 'message' => 'Help command handled'];
    }

    private function handleConversationStep(int | string $chatId, string $step, string $message, array $rawUpdate = []): array
    {
        return match ($step) {
            'awaiting_name'         => $this->processName($chatId, $message),
            'awaiting_division'     => $this->processDivision($chatId, $message),
            'awaiting_phone'        => $this->processPhone($chatId, $message),
            'confirm_registration'  => $this->processConfirmation($chatId, $message),
            'awaiting_report_type'  => $this->processReportType($chatId, $message, $rawUpdate ?? []),
            'awaiting_report_text'  => $this->processReportText($chatId, $message),
            'awaiting_absen_type'   => $this->processAbsenType($chatId, $message),
            default => ['status' => false, 'message' => 'Unknown step'],
        };
    }

    private function processName(int | string $chatId, string $message): array
    {
        $name = trim($message);

        if (strlen($name) < 3) {
            $this->sendMessage($chatId, "❌ Nama terlalu pendek. Minimal 3 karakter.\n\nSilakan masukkan nama lagi:");
            return ['status' => false, 'message' => 'Invalid name'];
        }

        $this->conversationState->updateTempData($chatId, ['name' => $name]);
        $this->conversationState->setCurrentStep($chatId, 'awaiting_division');

        $divisions = Division::all();
        $divisionList = $divisions->map(fn($d) => "{$d->id}. {$d->name}")->implode("\n");

        $this->sendMessage($chatId, "✅ Nama tercatat: *{$name}*\n\n"
                                   . "🏢 Sekarang pilih divisi kamu:\n\n{$divisionList}\n\n"
                                   . "Balas dengan nomor divisi (contoh: 1)");

        return ['status' => true, 'message' => 'Name processed'];
    }

    private function processDivision(int | string $chatId, string $message): array
    {
        $divisionId = intval(trim($message));
        $division = Division::find($divisionId);

        if (!$division) {
            $this->sendMessage($chatId, "❌ Divisi tidak ditemukan. Coba lagi dengan nomor yang benar.");
            return ['status' => false, 'message' => 'Invalid division'];
        }

        $this->conversationState->updateTempData($chatId, ['division_id' => $divisionId]);
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
            return ['status' => false, 'message' => 'Invalid phone'];
        }

        if (Employee::where('phone', $phone)->exists()) {
            $this->sendMessage($chatId, "❌ Nomor WA ini sudah terdaftar. Gunakan nomor lain.");
            return ['status' => false, 'message' => 'Phone already exists'];
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

        if ($answer !== 'ya' && $answer !== 'yes') {
            $this->conversationState->clearState($chatId);
            $this->sendMessage($chatId, "❌ Pendaftaran dibatalkan.\n\nKetik /daftar jika ingin mendaftar ulang.");
            return ['status' => false, 'message' => 'Registration cancelled'];
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
                                   . "Selamat datang, {$employee->name}! 🎉\n\n"
                                   . "Mulai gunakan bot dengan mengetik:\n"
                                   . "/lapor - untuk lapor penjualan\n"
                                   . "/absen - untuk lapor absen");

        return ['status' => true, 'message' => 'Registration completed'];
    }
}
