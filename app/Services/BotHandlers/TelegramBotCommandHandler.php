<?php

namespace App\Services\BotHandlers;

use App\Models\Employee;
use App\Models\Division;

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
                default => ['status' => false, 'message' => 'Command tidak dikenal'],
            };
        }

        // Jika bukan command, cek apakah ada conversation ongoing
        $currentStep = $this->conversationState->getCurrentStep($chatId);

        if ($currentStep && $currentStep !== 'start') {
            $this->logConversation($chatId, $currentStep, $message);
            return $this->handleConversationStep($chatId, $currentStep, $message);
        }

        return ['status' => false, 'message' => 'Tidak ada command atau conversation aktif'];
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

    private function handleConversationStep(int | string $chatId, string $step, string $message): array
    {
        return match ($step) {
            'awaiting_name' => $this->processName($chatId, $message),
            'awaiting_division' => $this->processDivision($chatId, $message),
            'awaiting_phone' => $this->processPhone($chatId, $message),
            'confirm_registration' => $this->processConfirmation($chatId, $message),
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
