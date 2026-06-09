<?php

namespace App\Services\BotHandlers;

use App\Models\Employee;
use App\Models\Division;
use App\Models\Report;
use App\Models\Attendance;
use App\Jobs\ProcessDailyReportJob;
use App\Jobs\ProcessAttendanceJob;
use App\Jobs\ProcessGmvReportJob;
use App\Services\AiResponseService;
use Carbon\Carbon;

class FonnteBotCommandHandler extends BaseBotCommandHandler
{
    public function handle(int|string $identifier, string $message, array $rawUpdate): array
    {
        $phone   = (string) $identifier;
        $command = $this->getCommandName($message);

        if ($command) {
            $this->logConversation($phone, "command_{$command}", $message);

            return match ($command) {
                'start'   => $this->handleStart($phone),
                'daftar'  => $this->handleDaftar($phone),
                'bantuan' => $this->handleBantuan($phone),
                'lapor'   => $this->handleLapor($phone),
                'absen'   => $this->handleAbsen($phone),
                'izin'    => $this->handleAbsen($phone),
                'status'  => $this->handleStatus($phone),
                default   => ['status' => false, 'message' => 'Command tidak dikenal'],
            };
        }

        // Cek apakah ada conversation yang sedang berjalan
        $currentStep = $this->conversationState->getCurrentStep($phone);

        if ($currentStep && $currentStep !== 'start') {
            $this->logConversation($phone, $currentStep, $message);
            return $this->handleConversationStep($phone, $currentStep, $message, $rawUpdate);
        }

        return ['status' => false, 'message' => 'Tidak ada command atau conversation aktif'];
    }

    // ── /start ────────────────────────────────────────────────
    private function handleStart(string $phone): array
    {
        $this->conversationState->clearState($phone);

        $welcome = "👋 *Selamat Datang di Herbigreen Bot!*\n\n"
                 . "📋 *Menu tersedia:*\n\n"
                 . "1️⃣ /lapor — Kirim laporan harian\n"
                 . "2️⃣ /absen — Lapor izin/sakit/cuti\n"
                 . "3️⃣ /status — Cek status laporan hari ini\n"
                 . "4️⃣ /bantuan — Panduan penggunaan\n\n"
                 . "Ketik salah satu perintah di atas ya! 😊";

        $this->sendMessage($phone, $welcome);
        return ['status' => true, 'message' => 'Start handled'];
    }

    // ── /status ───────────────────────────────────────────────
    private function handleStatus(string $phone): array
    {
        $employee = Employee::where('phone', $phone)->first();

        if (!$employee) {
            $this->sendMessage($phone, "❌ Nomor WA kamu belum terdaftar.\nKetik /daftar untuk mendaftar dulu ya!");
            return ['status' => true];
        }

        $today   = Carbon::today();
        $laporan = Report::where('employee_id', $employee->id)
            ->whereDate('created_at', $today)
            ->latest()
            ->first();

        $absen = Attendance::where('employee_id', $employee->id)
            ->whereDate('created_at', $today)
            ->latest()
            ->first();

        $status  = "📊 *Status Laporan Hari Ini*\n";
        $status .= "👤 {$employee->name} — {$employee->division->name}\n";
        $status .= "📅 " . $today->translatedFormat('l, d F Y') . "\n";
        $status .= str_repeat("─", 28) . "\n";

        if ($laporan) {
            $jam     = Carbon::parse($laporan->created_at)->setTimezone('Asia/Jakarta')->format('H:i');
            $status .= "✅ *Laporan:* Sudah dikirim pukul {$jam} WIB\n";
        } else {
            $status .= "❌ *Laporan:* Belum dikirim\n";
        }

        if ($absen) {
            $tipeAbsen = ucfirst($absen->type ?? 'absen');
            $status   .= "📋 *Kehadiran:* {$tipeAbsen}\n";
        } else {
            $status .= "🟢 *Kehadiran:* Hadir\n";
        }

        if (!$laporan && !$absen) {
            $status .= "\n⚠️ Jangan lupa kirim laporan hari ini ya!";
        }

        $this->sendMessage($phone, $status);
        return ['status' => true];
    }

    // ── /lapor ────────────────────────────────────────────────
    private function handleLapor(string $phone): array
    {
        $employee = Employee::where('phone', $phone)->first();

        if (!$employee) {
            $this->sendMessage($phone, "❌ Kamu belum terdaftar.\nKetik /daftar untuk mendaftar dulu ya!");
            return ['status' => true];
        }

        $isHostLive = strtolower($employee->division->name ?? '') === 'host live';

        $ai     = new AiResponseService();
        $sapaan = $ai->greetingLapor($employee->name);

        $menu  = "{$sapaan}\n\nMau laporan apa hari ini?\n\n";
        $menu .= "1️⃣ Laporan Harian (teks)\n";
        $menu .= "2️⃣ Laporan Harian + Foto\n";
        if ($isHostLive) {
            $menu .= "3️⃣ Laporan GMV (Screenshot)\n";
        }
        $menu .= "\nBalas dengan angka pilihanmu!";

        $this->conversationState->setCurrentStep($phone, 'awaiting_report_type', [
            'is_host_live' => $isHostLive,
        ]);

        $this->sendMessage($phone, $menu);
        return ['status' => true];
    }

    // ── /absen ────────────────────────────────────────────────
    private function handleAbsen(string $phone): array
    {
        $employee = Employee::where('phone', $phone)->first();

        if (!$employee) {
            $this->sendMessage($phone, "❌ Kamu belum terdaftar.\nKetik /daftar untuk mendaftar dulu ya!");
            return ['status' => true];
        }

        $ai     = new AiResponseService();
        $sapaan = $ai->greetingAbsen($employee->name);

        $menu  = "{$sapaan}\n\nMau lapor apa?\n\n";
        $menu .= "1️⃣ Sakit\n";
        $menu .= "2️⃣ Izin\n";
        $menu .= "3️⃣ Cuti\n";
        $menu .= "\nBalas dengan angka pilihanmu!";

        $this->conversationState->setCurrentStep($phone, 'awaiting_absen_type');
        $this->sendMessage($phone, $menu);
        return ['status' => true];
    }

    // ── /daftar ───────────────────────────────────────────────
    private function handleDaftar(string $phone): array
    {
        $employee = Employee::where('phone', $phone)->first();

        if ($employee) {
            $this->sendMessage($phone, "ℹ️ Kamu sudah terdaftar sebagai: *{$employee->name}*\n\nJika ada perubahan data, hubungi admin ya.");
            return ['status' => true];
        }

        $ai     = new AiResponseService();
        $sapaan = $ai->greetingDaftar();

        $this->conversationState->setCurrentStep($phone, 'awaiting_name');
        $this->sendMessage($phone, "{$sapaan}\n\nYuk mulai daftar! Silakan masukkan *nama lengkapmu*:");

        return ['status' => true];
    }

    // ── /bantuan ──────────────────────────────────────────────
    private function handleBantuan(string $phone): array
    {
        $help  = "❓ *Panduan Bot Herbigreen*\n\n";
        $help .= "📝 */lapor* — Kirim laporan harian\n";
        $help .= "🏥 */absen* — Lapor izin/sakit/cuti\n";
        $help .= "📊 */status* — Cek status laporan hari ini\n";
        $help .= "👤 */daftar* — Daftar sebagai karyawan baru\n\n";
        $help .= "Untuk bantuan lainnya, hubungi admin. 🙏";

        $this->sendMessage($phone, $help);
        return ['status' => true];
    }

    // ── Conversation Steps ────────────────────────────────────
    private function handleConversationStep(string $phone, string $step, string $message, array $rawUpdate): array
    {
        return match ($step) {
            'awaiting_name'        => $this->processName($phone, $message),
            'awaiting_division'    => $this->processDivision($phone, $message),
            'confirm_registration' => $this->processConfirmation($phone, $message),
            'awaiting_report_type' => $this->processReportType($phone, $message, $rawUpdate),
            'awaiting_report_text' => $this->processReportText($phone, $message),
            'awaiting_absen_type'  => $this->processAbsenType($phone, $message),
            default                => ['status' => false, 'message' => 'Unknown step'],
        };
    }

    private function processName(string $phone, string $message): array
    {
        $name = trim($message);

        if (strlen($name) < 3) {
            $this->sendMessage($phone, "❌ Nama terlalu pendek. Minimal 3 karakter.\nSilakan masukkan nama lagi:");
            return ['status' => true];
        }

        $this->conversationState->updateTempData($phone, ['name' => $name]);
        $this->conversationState->setCurrentStep($phone, 'awaiting_division');

        $divisions    = Division::all();
        $divisionList = $divisions->map(fn($d) => "{$d->id}. {$d->name}")->implode("\n");

        $this->sendMessage($phone, "✅ Nama tercatat: *{$name}*\n\n"
            . "🏢 Sekarang pilih divisimu:\n\n{$divisionList}\n\n"
            . "Balas dengan nomor divisi (contoh: 1)");

        return ['status' => true];
    }

    private function processDivision(string $phone, string $message): array
    {
        $divisionId = intval(trim($message));
        $division   = Division::find($divisionId);

        if (!$division) {
            $this->sendMessage($phone, "❌ Divisi tidak ditemukan. Coba lagi dengan nomor yang benar.");
            return ['status' => true];
        }

        $this->conversationState->updateTempData($phone, ['division_id' => $divisionId]);
        $this->conversationState->setCurrentStep($phone, 'confirm_registration');

        $tempData = $this->conversationState->getTempData($phone);

        $this->sendMessage($phone, "📋 *Konfirmasi Data Pendaftaran:*\n\n"
            . "👤 Nama: {$tempData['name']}\n"
            . "🏢 Divisi: {$division->name}\n"
            . "📱 Nomor WA: {$phone}\n\n"
            . "Ketik *ya* untuk konfirmasi atau *tidak* untuk batal.");

        return ['status' => true];
    }

    private function processConfirmation(string $phone, string $message): array
    {
        $answer = strtolower(trim($message));

        if (!in_array($answer, ['ya', 'yes', 'y'])) {
            $this->conversationState->clearState($phone);
            $this->sendMessage($phone, "❌ Pendaftaran dibatalkan.\n\nKetik /daftar jika ingin mencoba lagi.");
            return ['status' => true];
        }

        $tempData = $this->conversationState->getTempData($phone);

        $employee = Employee::create([
            'name'        => $tempData['name'],
            'division_id' => $tempData['division_id'],
            'phone'       => $phone,
            'is_active'   => true,
        ]);

        $this->conversationState->clearState($phone);

        $this->sendMessage($phone, "🎉 *Pendaftaran Berhasil!*\n\n"
            . "Selamat datang, *{$employee->name}*!\n\n"
            . "Mulai gunakan bot:\n"
            . "/lapor — lapor harian\n"
            . "/absen — lapor absen\n"
            . "/status — cek status laporan");

        return ['status' => true];
    }

    private function processReportType(string $phone, string $message, array $rawUpdate): array
    {
        $choice     = trim($message);
        $tempData   = $this->conversationState->getTempData($phone);
        $isHostLive = $tempData['is_host_live'] ?? false;

        if ($choice === '1') {
            $this->conversationState->setCurrentStep($phone, 'awaiting_report_text');
            $this->sendMessage($phone, "📝 Silakan ketik laporan harianmu sekarang!");
            return ['status' => true];
        }

        if ($choice === '2') {
            $this->conversationState->setCurrentStep($phone, 'awaiting_report_text');
            $this->sendMessage($phone, "📸 Silakan kirim *foto beserta caption* laporan harianmu!");
            return ['status' => true];
        }

        if ($choice === '3' && $isHostLive) {
            $this->conversationState->clearState($phone);
            $this->sendMessage($phone, "📊 Silakan kirim *screenshot GMV* kamu sekarang!");
            return ['status' => true];
        }

        $this->sendMessage($phone, "❌ Pilihan tidak valid. Balas dengan angka yang tersedia ya!");
        return ['status' => true];
    }

    private function processReportText(string $phone, string $message): array
    {
        $employee = Employee::where('phone', $phone)->first();

        if (!$employee) {
            $this->conversationState->clearState($phone);
            return ['status' => false];
        }

        $cleanMessage = trim($message);
        if (strlen($cleanMessage) < 10) {
            $this->sendMessage($phone, "⚠️ Laporan terlalu singkat, *{$employee->name}*!\nMinimal 10 karakter ya. Ceritain aktivitas hari ini! 📝");
            return ['status' => true];
        }

        $sudahLapor = Report::where('employee_id', $employee->id)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->exists();

        if ($sudahLapor) {
            $this->conversationState->clearState($phone);
            $this->sendMessage($phone, "⚠️ Kamu sudah lapor hari ini, *{$employee->name}*.\nLaporan hanya bisa dikirim sekali sehari ya!");
            return ['status' => true];
        }

        ProcessDailyReportJob::dispatch($employee->id, $cleanMessage, null);
        $this->conversationState->clearState($phone);

        $ai        = new AiResponseService();
        $konfirmasi = $ai->confirmLaporan($employee->name);
        $this->sendMessage($phone, $konfirmasi);

        return ['status' => true];
    }

    private function processAbsenType(string $phone, string $message): array
    {
        $employee = Employee::where('phone', $phone)->first();

        if (!$employee) {
            $this->conversationState->clearState($phone);
            return ['status' => false];
        }

        $typeMap = ['1' => 'sakit', '2' => 'izin', '3' => 'cuti'];
        $choice  = trim($message);

        if (!isset($typeMap[$choice])) {
            $this->sendMessage($phone, "❌ Pilihan tidak valid. Balas dengan 1, 2, atau 3 ya!");
            return ['status' => true];
        }

        $type = $typeMap[$choice];
        ProcessAttendanceJob::dispatch($employee->id, $type);
        $this->conversationState->clearState($phone);

        $ai        = new AiResponseService();
        $konfirmasi = $ai->confirmAbsen($employee->name, $type);
        $this->sendMessage($phone, $konfirmasi);

        return ['status' => true];
    }
}
