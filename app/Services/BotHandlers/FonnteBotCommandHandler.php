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
                'edit_laporan' => $this->handleEditLaporan($phone),
                'edit_profil'  => $this->handleEditProfil($phone),
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

        $employee = Employee::where('phone', $phone)->first();

        if ($employee) {
            $welcome = "👋 *Halo {$employee->name}, selamat datang kembali di Herbigreen Bot!*\n\n"
                     . "Sekarang aku udah lebih pintar lho! Kamu nggak perlu lagi ngapalin command-command kaku.\n\n"
                     . "🗣️ *Tinggal ajak ngobrol aja, contohnya:*\n"
                     . "• _\"min, aku hari ini sakit, gausah masuk ya\"_ (Otomatis absen)\n"
                     . "• _\"nih laporan jualan hari ini, laku 10 paket\"_ (Otomatis lapor)\n"
                     . "• _\"laporanku hari ini udah masuk blm ya?\"_ (Cek status)\n\n"
                     . "Coba aja langsung sapa aku atau kirim laporanmu! 😊";
        } else {
            $welcome = "👋 *Selamat Datang di Herbigreen Bot!*\n\n"
                     . "Untuk menggunakan bot ini, kamu harus daftar dulu ya.\n\n"
                     . "Ketik: */daftar* untuk memulai pendaftaran.";
        }

        $this->sendMessage($phone, $welcome);

        return ['status' => true, 'message' => 'Start command handled'];
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
        $help = "❓ *Bantuan Herbigreen Bot*\n\n"
              . "Sekarang bot ini sudah pintar pakai AI! Kamu bisa langsung chat pakai bahasa sehari-hari.\n\n"
              . "📝 *Lapor Harian*\n"
              . "Ketik aja misal: _\"laporan hr ini laku 5 botol\"_ atau _\"ini ss gmv ku\"_ (sambil kirim gambar)\n\n"
              . "🏥 *Lapor Sakit/Izin*\n"
              . "Ketik misal: _\"aku hari ini izin ya ada urusan keluarga\"_\n\n"
              . "🔍 *Cek Status*\n"
              . "Ketik misal: _\"aku udah lapor belum ya hari ini?\"_\n\n"
              . "Pendaftaran tetep pakai command */daftar* ya. Sisanya bebas ngobrol!";

        $this->sendMessage($phone, $help);

        return ['status' => true, 'message' => 'Help command handled'];
    }

    // ── Conversation Steps ────────────────────────────────────
    private function handleConversationStep(string $phone, string $step, string $message, array $rawUpdate): array
    {
        return match ($step) {
            'awaiting_name'        => $this->processName($phone, $message),
            'awaiting_division'    => $this->processDivision($phone, $message),
            'confirm_registration' => $this->processConfirmation($phone, $message),
            'awaiting_report_type' => $this->processReportType($phone, $message, $rawUpdate),
            'awaiting_report_text'  => $this->processReportText($phone, $message),
            'awaiting_absen_type'   => $this->processAbsenType($phone, $message),
            'awaiting_edit_report_text'  => $this->processEditReportText($phone, $message),
            'awaiting_edit_profile_choice' => $this->processEditProfileChoice($phone, $message),
            'awaiting_edit_profile_value'  => $this->processEditProfileValue($phone, $message),
            'waiting_gmv_confirmation' => $this->processGmvConfirmation($phone, $message),
            default => ['status' => false, 'message' => 'Unknown step'],
        };
    }

    private function processGmvConfirmation(string $phone, string $message): array
    {
        $message = strtolower(trim($message));
        $tempData = $this->conversationState->getTempData($phone);

        if (in_array($message, ['ya', 'y', 'benar', 'bener', 'betul', 'iya'])) {
            // Simpan ke database
            \App\Models\GmvReport::create([
                'employee_id'     => $tempData['employee_id'],
                'screenshot_path' => $tempData['screenshot_path'],
                'gmv_amount'      => $tempData['gmv_amount'],
                'order_count'     => $tempData['order_count'] ?? 0,
                'product_sold'    => $tempData['product_sold'] ?? 0,
                'viewers_count'   => $tempData['viewers_count'] ?? 0,
                'highest_viewers' => $tempData['highest_viewers'] ?? 0,
                'raw_ocr_text'    => $tempData['raw_ocr_text'],
                'live_date'       => $tempData['live_date'],
            ]);

            $this->conversationState->clearState($phone);
            $this->sendMessage($phone, "✅ Mantap! Laporan GMV berhasil disimpan ke sistem.");
            \Illuminate\Support\Facades\Log::info("KOKI GMV: User confirm YA. Disimpan.");
        } elseif (in_array($message, ['tidak', 't', 'salah', 'enggak', 'nggak', 'tdk'])) {
            $this->conversationState->clearState($phone);
            $this->sendMessage($phone, "❌ Oke, laporan dibatalkan.\n\nCoba kirim ulang gambarnya yang lebih tajam/terang ya biar AI gampang bacanya!");
            \Illuminate\Support\Facades\Log::info("KOKI GMV: User confirm TIDAK. Dibatalkan.");
        } else {
            $this->sendMessage($phone, "Aku kurang paham maksudmu. Tolong balas dengan *Ya* jika angkanya benar, atau *Tidak* jika salah.");
        }

        return ['status' => true, 'message' => 'GMV confirmation processed'];
    }

    private function processName(string $phone, string $message): array
    {
        $name = trim($message);

        if (strlen($name) < 3) {
            $this->sendMessage($phone, "❌ Nama terlalu pendek. Minimal 3 karakter.\n\nSilakan masukkan nama lagi:");
            return ['status' => true, 'message' => 'Invalid name'];
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
            return ['status' => true, 'message' => 'Invalid division'];
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
        
        $positiveAnswers = ['ya', 'yes', 'y', 'iya', 'oke', 'ok', 'setuju', 'gas', 'betul', 'bener', 'hooh'];
        $negativeAnswers = ['tidak', 'gak', 'enggak', 'no', 'n', 'batal', 'cancel'];

        $isPositive = false;
        $isNegative = false;

        foreach ($positiveAnswers as $pos) {
            if (str_contains($answer, $pos)) {
                $isPositive = true;
                break;
            }
        }

        foreach ($negativeAnswers as $neg) {
            if (str_contains($answer, $neg) && !$isPositive) {
                $isNegative = true;
                break;
            }
        }

        if ($isNegative) {
            $this->conversationState->clearState($phone);
            $this->sendMessage($phone, "❌ Pendaftaran dibatalkan.\n\nKetik /daftar jika ingin mendaftar ulang.");
            return ['status' => false, 'message' => 'Registration cancelled'];
        }

        if (!$isPositive) {
            // Berarti dia balas hal lain (misal "halo")
            $ai = new AiResponseService();
            // Panggil API Gemini khusus untuk ngeles
            $prompt = "User sedang mendaftar tapi dia malah jawab: '{$message}'. Balas ramah dan ingatkan dia untuk balas 'ya' jika setuju dengan data pendaftaran, atau 'tidak' untuk batal.";
            
            // Kita hack sedikit generate langsung
            $reflection = new \ReflectionClass($ai);
            $generateMethod = $reflection->getMethod('generate');
            $generateMethod->setAccessible(true);
            $balasan = $generateMethod->invoke($ai, $prompt, "Halo! Kita kan lagi proses daftar nih, datanya udah bener belum? Ketik *ya* kalau setuju, atau *tidak* buat batalin.");
            
            $this->sendMessage($phone, $balasan);
            return ['status' => true, 'message' => 'Registration paused for confirmation'];
        }

        $tempData = $this->conversationState->getTempData($phone);

        $employee = Employee::create([
            'name'        => $tempData['name'],
            'division_id' => $tempData['division_id'],
            'phone'       => $phone,
            'is_active'   => true,
        ]);

        $this->conversationState->clearState($phone);

        $this->sendMessage($phone, "✅ *Pendaftaran Berhasil!*\n\n"
                                 . "Selamat datang, {$employee->name}! 🎉\n\n"
                                 . "Sekarang kamu bisa langsung ngobrol sama aku buat lapor harian atau urusan lain. Tinggal ketik aja pesannya!");

        return ['status' => true, 'message' => 'Registration completed'];
    }

    private function handleEditLaporan(int | string $phone): array
    {
        $employee = Employee::where('phone', $phone)->first();
        if (!$employee) {
            $this->sendMessage($phone, "❌ Kamu belum terdaftar. Ketik /daftar untuk mendaftar dulu ya!");
            return ['status' => true];
        }

        $sudahLapor = \App\Models\Report::where('employee_id', $employee->id)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->exists();

        if (!$sudahLapor) {
            $this->sendMessage($phone, "⚠️ Kamu belum mengirim laporan apapun hari ini. Ketik laporan langsung atau pakai /lapor.");
            return ['status' => true];
        }

        $this->conversationState->setCurrentStep($phone, 'awaiting_edit_report_text');
        $this->sendMessage($phone, "📝 Oke! Silakan ketik *seluruh teks laporan barumu* untuk hari ini. Laporan lamamu akan diganti dengan yang baru ini.");
        return ['status' => true];
    }

    private function processEditReportText(int | string $phone, string $message): array
    {
        $employee = Employee::where('phone', $phone)->first();
        $report = \App\Models\Report::where('employee_id', $employee->id)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->first();

        if ($report) {
            $report->update(['note' => trim($message)]);
            $this->conversationState->clearState($phone);
            $this->sendMessage($phone, "✅ Sip! Laporanmu hari ini udah berhasil diperbarui.");
        } else {
            $this->conversationState->clearState($phone);
            $this->sendMessage($phone, "❌ Gagal memperbarui. Laporan tidak ditemukan.");
        }
        return ['status' => true];
    }

    private function handleEditProfil(int | string $phone): array
    {
        $employee = Employee::where('phone', $phone)->first();
        if (!$employee) {
            $this->sendMessage($phone, "❌ Kamu belum terdaftar. Ketik /daftar untuk mendaftar dulu ya!");
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

        $this->conversationState->setCurrentStep($phone, 'awaiting_edit_profile_choice');
        $this->sendMessage($phone, $info);
        return ['status' => true];
    }

    private function processEditProfileChoice(int | string $phone, string $message): array
    {
        $choice = trim($message);
        if (!in_array($choice, ['1', '2', '3'])) {
            $this->sendMessage($phone, "❌ Pilihan nggak valid. Balas dengan angka 1, 2, atau 3.");
            return ['status' => true];
        }

        $this->conversationState->setCurrentStep($phone, 'awaiting_edit_profile_value', ['edit_choice' => $choice]);
        
        if ($choice === '1') {
            $this->sendMessage($phone, "Ketik *Nama Baru* kamu:");
        } elseif ($choice === '2') {
            $divisions = Division::all()->map(fn($d) => "{$d->id}. {$d->name}")->implode("\n");
            $this->sendMessage($phone, "Pilih *Divisi Baru* kamu:\n\n{$divisions}\n\nBalas dengan nomor divisi.");
        } else {
            $this->sendMessage($phone, "Ketik *Nomor WA Baru* kamu (Format: 62xxx / 08xxx):");
        }
        
        return ['status' => true];
    }

    private function processEditProfileValue(int | string $phone, string $message): array
    {
        $employee = Employee::where('phone', $phone)->first();
        $tempData = $this->conversationState->getTempData($phone);
        $choice = $tempData['edit_choice'] ?? '1';
        $newValue = trim($message);

        if ($choice === '1') {
            $employee->update(['name' => $newValue]);
            $this->sendMessage($phone, "✅ Berhasil! Nama kamu sekarang jadi: *{$newValue}*");
        } elseif ($choice === '2') {
            $divisionId = intval($newValue);
            $division = Division::find($divisionId);
            if (!$division) {
                $this->sendMessage($phone, "❌ Divisi tidak ditemukan. Ketik ulang nomor divisi yang benar:");
                return ['status' => true];
            }
            $employee->update(['division_id' => $division->id]);
            $this->sendMessage($phone, "✅ Berhasil! Divisi kamu sekarang jadi: *{$division->name}*");
        } elseif ($choice === '3') {
            $newPhone = preg_replace('/\D/', '', $newValue);
            if (str_starts_with($newPhone, '8')) $newPhone = '62' . substr($newPhone, 1);
            if (str_starts_with($newPhone, '08')) $newPhone = '62' . substr($newPhone, 1);
            
            $employee->update(['phone' => $newPhone]);
            $this->sendMessage($phone, "✅ Berhasil! Nomor WA kamu sekarang jadi: *{$newPhone}*");
        }

        $this->conversationState->clearState($phone);
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
