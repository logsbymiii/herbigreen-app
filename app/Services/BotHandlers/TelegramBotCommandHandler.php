<?php

namespace App\Services\BotHandlers;

use App\Models\Employee;
use App\Models\Division;
use App\Jobs\ProcessDailyReportJob;
use App\Jobs\ProcessSmartDailyReportJob;
use App\Jobs\ProcessAttendanceJob;
use App\Jobs\ProcessGmvReportJob;
use App\Models\Report;
use App\Models\Attendance;
use Carbon\Carbon;
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
                'wfh'     => $this->handleWfh($chatId),
                'wfc'     => $this->handleWfh($chatId), // alias /wfh
                'edit_laporan' => $this->handleEditLaporan($chatId),
                'edit_profil'  => $this->handleEditProfil($chatId),
                'status'       => $this->handleStatus($chatId),
                'gmv'          => $this->handleGmv($chatId, $message),
                'init_management' => $this->handleInitManagement($chatId),
                default   => ['status' => false, 'message' => 'Command tidak dikenal'],
            };
        }

        // Cek intercept untuk approval WFH dari HR
        if (preg_match('/^(ACC|TOLAK)\s+WFH\s+(\d+)$/i', trim($message), $matches)) {
            return $this->handleWfhApproval($chatId, strtoupper($matches[1]), $matches[2]);
        }

        // Cek apakah user mau batalin proses
        $msgLower = strtolower(trim($message));
        if (in_array($msgLower, ['batal', 'cancel', 'gak jadi', 'stop']) || str_contains($msgLower, 'gak jadi') || str_contains($msgLower, 'batal')) {
            $this->conversationState->clearState($chatId);
            $this->sendMessage($chatId, "Proses dibatalkan. Silakan ketik /start untuk kembali ke menu utama.");
            return ['status' => true];
        }

        // Jika bukan command, cek apakah ada conversation ongoing
        $currentStep = $this->conversationState->getCurrentStep($chatId);

        if ($currentStep && $currentStep !== 'start') {
            $this->logConversation($chatId, $currentStep, $message);
            return $this->handleConversationStep($chatId, $currentStep, $message, $rawUpdate);
        }

        // Coba baca intensi pakai AI jika karyawan sudah terdaftar
        $employee = Employee::where('telegram_id', $chatId)->first();
        if ($employee && !empty(trim($message))) {
            $hasFile = isset($rawUpdate['message']['photo']) || isset($rawUpdate['message']['document']);
            $today = Carbon::today();
            $laporan = \App\Models\SmartDailyReport::where('employee_id', $employee->id)->whereDate('report_date', $today->format('Y-m-d'))->latest()->first();
            $absen = Attendance::where('employee_id', $employee->id)->whereDate('date', $today->format('Y-m-d'))->latest()->first();
            
            $todaysReportContent = $laporan ? $laporan->raw_report : null;
            $todaysAttendanceStatus = $absen ? "Telah tercatat kehadiran: " . ucfirst($absen->type) : "Telah tercatat kehadiran: BELUM ABSEN SAMA SEKALI";
            
            $ai = new AiResponseService();
            $aiResult = $ai->analyzeIntentAndReply($employee->name, $employee->division->name ?? 'Tim', $message, $hasFile, $todaysReportContent, $todaysAttendanceStatus);
            
            $intent = $aiResult['intent'] ?? 'general_chat';
            $reply = $aiResult['reply'] ?? "Oke sip!";
            
            if ($intent === 'status') {
                return $this->handleStatus($chatId);
            } elseif ($intent === 'attendance') {
                return $this->handleAbsen($chatId);
            } elseif ($intent === 'report') {
                return $this->handleLapor($chatId);
            } elseif ($intent === 'gmv_report') {
                return $this->handleGmv($chatId, $message);
            } else {
                $this->sendMessage($chatId, $reply);
                return ['status' => true];
            }
        }

        return ['status' => false, 'message' => 'Tidak ada command atau conversation aktif'];
    }

    private function handleStatus(int | string $chatId): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();

        if (!$employee) {
            $this->sendMessage($chatId, "❌ Nomor Telegram kamu belum terdaftar.\nKetik /daftar untuk mendaftar dulu ya!");
            return ['status' => true];
        }

        $today   = Carbon::today();
        $laporan = \App\Models\SmartDailyReport::where('employee_id', $employee->id)
            ->whereDate('report_date', $today->format('Y-m-d'))
            ->latest()
            ->first();

        $absen = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today->format('Y-m-d'))
            ->latest()
            ->first();

        $status  = "📊 *Status Laporan Hari Ini*\n";
        $status .= "👤 {$employee->name} — {$employee->division->name}\n";
        $status .= "📅 " . $today->translatedFormat('l, d F Y') . "\n";
        $status .= str_repeat("─", 28) . "\n";

        if ($laporan) {
            $jam     = Carbon::parse($laporan->created_at)->setTimezone('Asia/Jakarta')->format('H:i');
            $status .= "✅ *Laporan:* Sudah dikirim pukul {$jam} WIB\n";
            $status .= "📝 *Ringkasan AI:*\n" . ($laporan->ai_insight ?? 'Belum diproses AI') . "\n\n";
        } else {
            $status .= "❌ *Laporan:* Belum dikirim\n";
        }

        if ($absen) {
            $tipeAbsen = ucfirst($absen->type ?? 'absen');
            $status   .= "📋 *Kehadiran:* {$tipeAbsen}\n";
        } else {
            $status .= "🔴 *Kehadiran:* Belum Absen\n";
        }

        if (!$laporan) {
            $status .= "\n⚠️ Jangan lupa kirim laporan hari ini ya!";
        }

        $this->sendMessage($chatId, $status);
        return ['status' => true];
    }

    private function handleWfhApproval(int | string $chatId, string $action, string $wfhId): array
    {
        // Pastikan yang nge-chat adalah HR (Simau)
        $simauId = env('SIMAU_TELEGRAM_ID');
        if ($chatId != $simauId) {
            $this->sendMessage($chatId, "❌ Hanya HR yang bisa menyetujui WFH.");
            return ['status' => true];
        }

        $wfhRequest = \App\Models\WfhRequest::find($wfhId);
        if (!$wfhRequest) {
            $this->sendMessage($chatId, "❌ Pengajuan WFH #{$wfhId} tidak ditemukan.");
            return ['status' => true];
        }

        if ($wfhRequest->status !== 'pending') {
            $this->sendMessage($chatId, "⚠️ Pengajuan WFH #{$wfhId} sudah berstatus: {$wfhRequest->status}");
            return ['status' => true];
        }

        $wfhRequest->status = $action === 'ACC' ? 'approved' : 'rejected';
        $wfhRequest->responded_at = now();
        $wfhRequest->save();

        $employee = $wfhRequest->employee;
        $tanggal = \Carbon\Carbon::parse($wfhRequest->request_date)->format('d M Y');

        // Balas ke HR
        $statusText = $action === 'ACC' ? 'Disetujui ✅' : 'Ditolak ❌';
        $this->sendMessage($chatId, "Sip! Pengajuan WFH #{$wfhId} a.n {$employee->name} untuk tanggal {$tanggal} telah *{$statusText}*.");

        // Notif ke User
        $userMsg = $action === 'ACC' 
            ? "🎉 *PENGUMUMAN*\n\nPengajuan WFH kamu untuk tanggal {$tanggal} *DISETUJUI* oleh HR. \nJangan lupa /absen Hadir pakai opsi WFH ya!"
            : "❌ *PENGUMUMAN*\n\nMaaf, pengajuan WFH kamu untuk tanggal {$tanggal} *DITOLAK* oleh HR. Harap tetap bekerja dari kantor.";
            
        $this->sendMessage($employee->telegram_id, $userMsg);

        return ['status' => true];
    }

    private function handleLapor(int | string $chatId): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();

        if (!$employee) {
            $this->sendMessage($chatId, "❌ Anda belum terdaftar. Silakan ketik /daftar untuk melakukan pendaftaran.");
            return ['status' => true, 'message' => 'Not registered'];
        }

        $nama = $employee->name;
        $isHostLive = strtolower($employee->division->name ?? '') === 'host live';

        $menu = "👋 Halo {$nama},\n\nSilakan pilih jenis laporan:\n\n";
        $menu .= "1. Laporan Harian (teks)\n";
        $menu .= "2. Laporan Foto\n";

        if ($isHostLive) {
            $menu .= "3. Laporan GMV & Screenshot Omset\n";
        }
        $menu .= "\nBalas dengan angka pilihan Anda.";

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
            $this->sendMessage($chatId, "❌ Anda belum terdaftar. Silakan ketik /daftar untuk melakukan pendaftaran.");
            return ['status' => true, 'message' => 'Not registered'];
        }

        if (strtolower($employee->division?->name) !== 'host live') {
            $this->sendMessage($chatId, "❌ Fitur ini khusus untuk divisi Host Live!");
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
            $this->sendMessage($chatId, "❌ Nominal tidak valid. Pastikan Anda hanya memasukkan karakter angka.");
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
             . "Apakah angka tersebut sudah benar?\n"
             . "(Balas: *Ya* / *Tidak*)";
        
        $this->sendMessage($chatId, $msg);
        
        return ['status' => true];
    }

    private function handleAbsen(int | string $chatId): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();

        if (!$employee) {
            $this->sendMessage($chatId, "❌ Anda belum terdaftar. Silakan ketik /daftar untuk melakukan pendaftaran.");
            return ['status' => true, 'message' => 'Not registered'];
        }

        $sudahAbsen = \App\Models\Attendance::where('employee_id', $employee->id)
            ->whereDate('date', now()->format('Y-m-d'))
            ->whereIn('type', ['hadir', 'wfh', 'telat', 'sakit', 'izin', 'alpa'])
            ->exists();

        if ($sudahAbsen) {
            $this->sendMessage($chatId, "⚠️ Anda sudah tercatat melakukan absensi hari ini. Terima kasih!");
            return ['status' => true];
        }

        $menu = "👋 Halo {$employee->name},\n\nSilakan pilih jenis absensi:\n\n";
        $menu .= "1. Hadir (Di Kantor)\n";
        $menu .= "2. Hadir (Pengajuan WFH / Sedang WFH)\n";
        $menu .= "3. Sakit\n";
        $menu .= "4. Izin\n\n";
        $menu .= "Balas dengan angka pilihan Anda.";

        $this->conversationState->setCurrentStep($chatId, 'awaiting_absen_type');
        $this->sendMessage($chatId, $menu);
        return ['status' => true, 'message' => 'Absen menu shown'];
    }

    private function handleWfh(int | string $chatId): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();

        if (!$employee) {
            $this->sendMessage($chatId, "❌ Anda belum terdaftar. Silakan ketik /daftar untuk melakukan pendaftaran.");
            return ['status' => true];
        }

        $this->conversationState->setCurrentStep($chatId, 'awaiting_wfh_reason');
        $this->sendMessage($chatId, "🏠 *Pengajuan WFH / WFC*\n\nSilakan ketik alasan pengajuan Work From Home / Cafe Anda:");
        return ['status' => true];
    }
    
    private function processWfhReason(int | string $chatId, string $message): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();
        $reason = trim($message);
        
        if (strlen($reason) < 5) {
            $this->sendMessage($chatId, "❌ Alasan terlalu singkat. Mohon berikan keterangan yang lebih komprehensif.");
            return ['status' => true];
        }

        // Buat pengajuan WFH di database
        $wfhRequest = \App\Models\WfhRequest::create([
            'employee_id' => $employee->id,
            'request_date' => now()->format('Y-m-d'),
            'reason' => $reason,
            'status' => 'pending'
        ]);

        $this->conversationState->clearState($chatId);
        $this->sendMessage($chatId, "✅ Pengajuan WFH / WFC kamu berhasil dikirim ke HRD. Harap menunggu persetujuan.");

        // Kirim Notifikasi ke HR
        $simauId = env('SIMAU_TELEGRAM_ID');
        if ($simauId) {
            $hrMessage = "🔔 *PENGAJUAN WFH / WFC BARU*\n\n"
                       . "👤 Nama: *{$employee->name}*\n"
                       . "📅 Tanggal: *" . now()->format('d M Y') . "*\n"
                       . "📝 Alasan: _{$reason}_\n\n"
                       . "Untuk memproses, balas pesan ini dengan format:\n"
                       . "`ACC WFH {$wfhRequest->id}` atau `TOLAK WFH {$wfhRequest->id}`";
            
            $this->sendMessage($simauId, $hrMessage);
        }

        return ['status' => true];
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
            $prompt = "📝 *LAPORAN HARIAN HERBIGREEN* 🌿\n\n"
                    . "Silakan ketik laporan aktivitasmu hari ini. Biar rapi dan gampang direkap oleh atasan, yuk pakai format standar ini:\n\n"
                    . "🎯 *Fokus Utama:* \n"
                    . "*(Gol utama hari ini)*\n\n"
                    . "✅ *Tugas Selesai:*\n- \n- \n\n"
                    . "🚧 *Kendala / Butuh Bantuan:*\n(Kosongi kalau aman)\n\n"
                    . "_Tinggal copy-paste tulisan di atas dan isi ya!_ ✨";
            
            $this->sendMessage($chatId, $prompt);
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
            $this->sendMessage($chatId, "📝 Silakan ketik *nama akun* yang Anda gunakan untuk live.

_Contoh: HERBITOK OFFICIAL_");
            return ['status' => true];
        } else {
            $this->sendMessage($chatId, "❌ Pilihan tidak valid. Silakan balas dengan angka 1, 2, atau 3.
_(Ketik *batal* untuk membatalkan)_");
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
            $this->sendMessage($chatId, "⚠️ Laporan terlalu singkat, *{$employee->name}*.\nHarap tuliskan minimal 10 karakter yang mendeskripsikan aktivitas Anda hari ini.");
            return ['status' => true];
        }

        // Fitur baru: Karyawan boleh mengirim laporan berkali-kali dalam sehari.
        // Laporan akan di-append/digabung oleh ProcessSmartDailyReportJob.
        
        ProcessSmartDailyReportJob::dispatchSync($employee->id, $cleanMessage, (string) $chatId);
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

        $typeMap = ['1' => 'hadir', '2' => 'wfh', '3' => 'sakit', '4' => 'izin'];
        $choice = strtolower(trim($message));

        // Toleransi Typo Tingkat Dewa
        if (in_array($choice, ['1', 'satu', 'pertama', 'hadir', 'kantor'])) $choice = '1';
        if (in_array($choice, ['2', 'dua', 'kedua', 'wfh', 'rumah'])) $choice = '2';
        if (in_array($choice, ['3', 'tiga', 'ketiga', 'sakit'])) $choice = '3';
        if (in_array($choice, ['4', 'empat', 'keempat', 'izin'])) $choice = '4';

        if (!isset($typeMap[$choice])) {
            $this->sendMessage($chatId, "❌ Pilihan tidak valid. Silakan balas dengan angka 1, 2, 3, atau 4.\n_(Ketik *batal* untuk membatalkan)_");
            return ['status' => true];
        }

        $type = $typeMap[$choice];
        
        if ($type === 'hadir' || $type === 'wfh') {
            // Cek apakah hari ini sudah absen hadir (Berlaku untuk semua, termasuk admin)
            $sudahAbsen = \App\Models\Attendance::where('employee_id', $employee->id)
                ->whereDate('date', now()->format('Y-m-d'))
                ->whereIn('type', ['hadir', 'wfh', 'telat'])
                ->exists();
                
            if ($sudahAbsen) {
                $this->conversationState->clearState($chatId);
                $this->sendMessage($chatId, "⚠️ Anda sudah tercatat melakukan absensi hari ini.");
                return ['status' => true];
            }

            if ($type === 'wfh') {
                $isWfhApproved = \App\Models\WfhRequest::where('employee_id', $employee->id)
                    ->whereDate('request_date', now()->format('Y-m-d'))
                    ->where('status', 'approved')
                    ->exists();
                
                if (!$isWfhApproved) {
                    // Kalau belum disetujui, arahkan ke form pengajuan WFH
                    $this->conversationState->setCurrentStep($chatId, 'awaiting_wfh_reason');
                    $this->sendMessage($chatId, "🏠 *Pengajuan WFH*\n\nAnda belum memiliki izin WFH yang disetujui oleh HRD untuk hari ini. Silakan ketik alasan pengajuan Work From Home Anda hari ini untuk diteruskan ke HRD:");
                    return ['status' => true];
                }
            }
            $isHostLive = strtolower($employee->division->name ?? '') === 'host live';
            if ($isHostLive) {
                $this->conversationState->setCurrentStep($chatId, 'awaiting_host_sessions', ['type' => $type]);
                $this->sendMessage($chatId, "🎙️ *Host Live*\n\nHari ini kamu bertugas untuk berapa sesi Live?\n\n1. Satu Sesi\n2. Dua Sesi (Pagi & Malam)\n\nBalas dengan angka 1 atau 2.");
                return ['status' => true];
            }
            
            $this->conversationState->clearState($chatId);
            $appUrl = url("/webapp/absen?type={$type}&uid={$employee->telegram_id}&sessions=1");
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '📸 Buka Kamera Absen', 'web_app' => ['url' => $appUrl]]
                    ]
                ]
            ];
            
            $botToken = env('TELEGRAM_BOT_TOKEN');
            \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => "📍 *Absen 1-Klik Aktif!*\n\nKlik tombol di bawah untuk membuka kamera absen. Lokasi dan foto akan dikirim otomatis! 🚀",
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode($keyboard)
            ]);
            return ['status' => true];
        }

        // Kalau Sakit atau Izin, tanya alasannya
        $this->conversationState->setCurrentStep($chatId, 'awaiting_absen_reason', ['type' => $type]);
        $this->sendMessage($chatId, "📝 Silakan ketik alasan {$type} Anda.\n\n*(Opsional: Anda juga bisa melampirkan foto surat dokter/bukti izin dengan caption alasannya)*");
        return ['status' => true];
    }

    private function processHostSessions(int | string $chatId, string $message): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();
        if (!$employee) {
            $this->conversationState->clearState($chatId);
            return ['status' => false];
        }

        $choice = trim($message);
        if (!in_array($choice, ['1', '2'])) {
            $this->sendMessage($chatId, "❌ Pilihan tidak valid. Silakan balas dengan angka 1 atau 2.");
            return ['status' => true];
        }

        $tempData = $this->conversationState->getTempData($chatId);
        $type = $tempData['type'] ?? 'hadir';

        $this->conversationState->clearState($chatId);
        
        $appUrl = url("/webapp/absen?type={$type}&uid={$employee->telegram_id}&sessions={$choice}");
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📸 Buka Kamera Absen', 'web_app' => ['url' => $appUrl]]
                ]
            ]
        ];
        
        $botToken = env('TELEGRAM_BOT_TOKEN');
        \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => "📍 *Absen 1-Klik Aktif!* (Target: {$choice} Sesi)\n\nKlik tombol di bawah untuk membuka kamera absen. Lokasi dan foto akan dikirim otomatis! 🚀",
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode($keyboard)
        ]);
        return ['status' => true];
    }

    private function processAbsenReason(int | string $chatId, string $message, array $rawUpdate): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();
        if (!$employee) {
            $this->conversationState->clearState($chatId);
            return ['status' => false];
        }

        $tempData = $this->conversationState->getTempData($chatId);
        $type = $tempData['type'] ?? 'izin';
        $reason = trim($message);

        if (strlen($reason) < 3 && empty($rawUpdate['message']['photo']) && empty($rawUpdate['message']['document'])) {
            $this->sendMessage($chatId, "⚠️ Alasan terlalu singkat. Mohon berikan alasan yang jelas.");
            return ['status' => true];
        }

        // Extract photo URL if exists
        $urlFile = null;
        $fileId = null;
        if (isset($rawUpdate['message']['photo']) && is_array($rawUpdate['message']['photo'])) {
            $fileId = end($rawUpdate['message']['photo'])['file_id'];
        } elseif (isset($rawUpdate['message']['document'])) {
            $fileId = $rawUpdate['message']['document']['file_id'];
        }

        if ($fileId) {
            $botToken = env('TELEGRAM_BOT_TOKEN');
            $response = \Illuminate\Support\Facades\Http::get("https://api.telegram.org/bot{$botToken}/getFile", [
                'file_id' => $fileId
            ]);
            if ($response->successful()) {
                $filePath = $response->json('result.file_path');
                if ($filePath) {
                    $urlFile = "https://api.telegram.org/file/bot{$botToken}/{$filePath}";
                }
            }
        }

        ProcessAttendanceJob::dispatchSync($employee->id, $reason, $type, $urlFile);
        $this->conversationState->clearState($chatId);

        // AI generate konfirmasi yang kontekstual
        $ai = new AiResponseService();
        $konfirmasi = $ai->confirmAbsen($employee->name, $type);
        $this->sendMessage($chatId, $konfirmasi);
        return ['status' => true];
    }
    
    private function processAbsenLocation(int | string $chatId, array $rawUpdate): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();
        $location = $rawUpdate['message']['location'] ?? null;
        
        if (!$location) {
            $this->sendMessage($chatId, "❌ Mohon kirimkan lokasi Anda menggunakan fitur lampiran Location Telegram.");
            return ['status' => true];
        }

        $lat = $location['latitude'];
        $lng = $location['longitude'];
        
        $officeLat = env('OFFICE_LATITUDE', -7.6631268);
        $officeLng = env('OFFICE_LONGITUDE', 112.6964359);
        $officeRadius = env('OFFICE_RADIUS', 200);

        // Rumus Haversine buat hitung jarak
        $earthRadius = 6371000; // Meter
        $latDelta = deg2rad($lat - $officeLat);
        $lngDelta = deg2rad($lng - $officeLng);
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($officeLat)) * cos(deg2rad($lat)) *
            sin($lngDelta / 2) * sin($lngDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        $isWfh = \App\Models\WfhRequest::where('employee_id', $employee->id)
            ->whereDate('request_date', now()->format('Y-m-d'))
            ->where('status', 'approved')
            ->exists();

        if ($distance > $officeRadius && !$isWfh) {
            $this->conversationState->clearState($chatId);
            $jarak = round($distance);
            $this->sendMessage($chatId, "❌ *ABSEN DITOLAK!*\n\nKamu terdeteksi berada di luar area kantor (jarakmu {$jarak} meter dari titik kantor, maksimal {$officeRadius} meter).\n\nJika kamu WFH atau tugas luar, pastikan sudah mendapatkan izin dari HR.");
            return ['status' => true];
        }

        $this->conversationState->setCurrentStep($chatId, 'awaiting_absen_photo', [
            'lat' => $lat,
            'lng' => $lng,
            'is_wfh' => $isWfh
        ]);
        
        $msg = $isWfh ? "✅ Lokasi aman! (Mode WFH Aktif)." : "✅ Lokasi aman! (Jarak: ".round($distance)." meter).";
        $this->sendMessage($chatId, "{$msg}\n\n📸 Silakan *kirim foto selfie* Anda sebagai bukti kehadiran.");
        return ['status' => true];
    }
    
    private function processAbsenPhoto(int | string $chatId, array $rawUpdate): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();
        
        $photoId = null;
        if (isset($rawUpdate['message']['photo']) && is_array($rawUpdate['message']['photo'])) {
            $photoId = end($rawUpdate['message']['photo'])['file_id'];
        }

        if (!$photoId) {
            $this->sendMessage($chatId, "❌ Mohon kirimkan foto selfie Anda langsung dari kamera Telegram.");
            return ['status' => true];
        }

        $botToken = env('TELEGRAM_BOT_TOKEN');
        $proofPath = null;
        if ($photoId) {
            $fileResponse = \Illuminate\Support\Facades\Http::get("https://api.telegram.org/bot{$botToken}/getFile?file_id={$photoId}");
            $filePath = $fileResponse->json('result.file_path');
            $urlFile = "https://api.telegram.org/file/bot{$botToken}/{$filePath}";

            // Upload to Cloudflare R2
            try {
                $fileContent = file_get_contents($urlFile);
                if ($fileContent) {
                    // Validasi wajah menggunakan AI (Gemini Vision)
                    $geminiKey = config('services.gemini.api_key') ?? env('GEMINI_API_KEY');
                    $isFaceValid = false; // Default: tolak! Hanya lolos kalau Gemini bilang YA
                    if ($geminiKey) {
                        try {
                            $base64Image = base64_encode($fileContent);
                            $geminiResponse = \Illuminate\Support\Facades\Http::timeout(15)->withHeaders([
                                'Content-Type' => 'application/json',
                            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$geminiKey}", [
                                'contents' => [[
                                    'parts' => [
                                        [
                                            'inline_data' => [
                                                'mime_type' => 'image/jpeg',
                                                'data' => $base64Image,
                                            ]
                                        ],
                                        [
                                            'text' => "Apakah ada wajah manusia (selfie) yang terlihat jelas di foto ini? Jawab HANYA dengan kata 'YA' atau 'TIDAK' tanpa tambahan kalimat apapun."
                                        ]
                                    ]
                                ]]
                            ]);

                            if ($geminiResponse->successful()) {
                                $aiAnswer = strtoupper(trim($geminiResponse->json('candidates.0.content.parts.0.text')));
                                \Illuminate\Support\Facades\Log::info("KOKI AI ABSEN: Gemini menjawab -> " . $aiAnswer);
                                
                                // Lebih galak: kalau nggak secara tegas jawab YA, tolak!
                                if (!str_contains($aiAnswer, 'YA')) {
                                    $isFaceValid = false;
                                }
                            } else {
                                \Illuminate\Support\Facades\Log::error("KOKI AI ABSEN GAGAL: " . $geminiResponse->body());
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Gagal validasi wajah via AI: " . $e->getMessage());
                            // Fallback: anggap valid jika API error
                        }
                    }

                    if (!$isFaceValid) {
                        $this->sendMessage($chatId, "❌ *Absen Ditolak!*\n\nWajah kamu tidak terlihat dengan jelas di foto ini. Pastikan kamu mengambil foto selfie yang menampilkan wajahmu.\n\n📸 Silakan *kirim ulang foto selfie* Anda.");
                        return ['status' => true];
                    }

                    $fileName = 'attendances/' . uniqid('absen_') . '.jpg';
                    \Illuminate\Support\Facades\Storage::disk('r2')->put($fileName, $fileContent);
                    $proofPath = $fileName;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Gagal upload absen ke R2: " . $e->getMessage());
                $proofPath = $urlFile; // Fallback ke URL Telegram
            }
        }
        
        $tempData = $this->conversationState->getTempData($chatId);
        
        // Simpan langsung karena kita udah dapat foto
        $attendanceType = ($tempData['is_wfh'] ?? false) ? 'wfh' : 'hadir';
        
        // Cek kalau waktu absen lebih dari jam 08:30 otomatis jadi telat (kecuali WFH)
        if ($attendanceType === 'hadir' && now()->format('H:i') > '08:30') {
            $attendanceType = 'telat';
        }
        \App\Models\Attendance::create([
            'employee_id' => $employee->id,
            'type' => $attendanceType,
            'latitude' => $tempData['lat'] ?? null,
            'longitude' => $tempData['lng'] ?? null,
            'proof_path' => $proofPath,
            'date' => now()->format('Y-m-d'),
            'clocked_in_at' => now(),
        ]);

        $this->conversationState->clearState($chatId);
        
        if ($attendanceType === 'wfh') {
            $msg = "✅ Sip, absen masuk *WFH* kamu berhasil dicatat! Selamat bekerja dari rumah! 🏡🔥";
        } elseif ($attendanceType === 'telat') {
            $msg = "⚠️ Absen dicatat sebagai *Telat* (melewati batas jam 08:30). Tetap semangat kerjanya ya! 🔥";
        } else {
            $msg = "✅ Sip, absen masuk *Hadir* kamu berhasil dicatat! Selamat bekerja! 🔥";
        }
        $this->sendMessage($chatId, $msg);
        return ['status' => true];
    }

    private function handleStart(int | string $chatId): array
    {
        $this->conversationState->clearState($chatId);

        $employee = Employee::where('telegram_id', $chatId)->first();

        if ($employee) {
            $isHostLive = strtolower($employee->division?->name ?? '') === 'host live';
            
            $welcome = "👋 *Halo {$employee->name}, selamat datang di Layanan Herbigreen Bot.*\n\n"
                     . "Silakan gunakan menu di bawah ini untuk kebutuhan operasional Anda:\n\n"
                     . "📋 */absen* - Lapor absensi (Hadir/Sakit/Izin)\n"
                     . "📝 */lapor* - Kirim laporan harian atau foto\n"
                     . "💰 */gmv* - Lapor omset GMV (Khusus Host Live)\n"
                     . "✏️ */edit_laporan* - Ubah laporan hari ini\n"
                     . "👤 */edit_profil* - Ubah data profil kamu\n"
                     . "❓ */bantuan* - Panduan lengkap cara pakai bot\n\n";

            $welcome .= "_Ketik salah satu command di atas (pakai garis miring /) buat mulai._";
        } else {
            $welcome = "👋 *Selamat Datang di Herbigreen Bot!*\n\n"
                     . "Untuk menggunakan layanan bot ini, Anda diwajibkan mendaftar terlebih dahulu.\n\n"
                     . "Ketik: */daftar* untuk memulai pendaftaran.";
        }

        $this->sendMessage($chatId, $welcome);

        return ['status' => true, 'message' => 'Start command handled'];
    }

    private function handleDaftar(int | string $chatId): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();

        if ($employee) {
            $this->sendMessage($chatId, "❌ Anda telah terdaftar sebagai: *{$employee->name}*\n\nSilakan gunakan menu /edit_profil untuk mengubah data.");
            return ['status' => true, 'message' => 'Already registered'];
        }

        $this->conversationState->setCurrentStep($chatId, 'awaiting_name');
        $this->sendMessage($chatId, "Yuk mulai daftar! Silakan ketik *nama lengkapmu* sekarang:");

        return ['status' => true, 'message' => 'Daftar conversation started'];
    }

    private function handleBantuan(int | string $chatId): array
    {
        $help = "❓ *Bantuan Herbigreen Bot*\n\n"
              . "Sistem telah terintegrasi dengan AI untuk memudahkan pelaporan Anda.\n\n"
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
              . "Gunakan perintah */daftar* untuk registrasi akun baru.";

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
            'awaiting_wfh_reason'   => $this->processWfhReason($chatId, $message),
            'awaiting_report_type'  => $this->processReportType($chatId, $message, $rawUpdate ?? []),
            'awaiting_report_text'  => $this->processReportText($chatId, $message),
            'awaiting_absen_type'   => $this->processAbsenType($chatId, $message),
            'awaiting_host_sessions'=> $this->processHostSessions($chatId, $message),
            'awaiting_absen_reason' => $this->processAbsenReason($chatId, $message, $rawUpdate ?? []),
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
            $this->sendMessage($chatId, "❌ Nama akun tidak valid. Silakan ketik ulang.

_Contoh: HERBITOK OFFICIAL_");
            return ['status' => true];
        }

        $this->conversationState->updateTempData($chatId, ['account_name' => $accountName]);
        $this->conversationState->setCurrentStep($chatId, 'awaiting_gmv_time');

        $this->sendMessage($chatId, "⏰ Silakan masukkan jam operasional live Anda.

Ketik format: *[jam mulai]-[jam selesai]*
_Contoh: 14.00-15.00_");
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
            $this->sendMessage($chatId, "⏳ Data diterima. Sistem sedang memproses lampiran screenshot Anda, mohon tunggu.");
            
            // Langsung dispatch job secara sinkron
            // Karena sinkron, job akan set 'waiting_gmv_confirmation' state
            // JANGAN di-clear setelah job selesai.
            ProcessGmvReportJob::dispatchSync(
                $tempData['employee_id'],
                $tempData['url_file'],
                $chatId,
                $tempData['account_name'] ?? null,
                $tempData['live_start'] ?? null,
                $tempData['live_end'] ?? null
            );
        } else {
            $this->conversationState->setCurrentStep($chatId, 'awaiting_gmv_screenshot');
            $this->sendMessage($chatId, "📸 Silakan kirimkan *screenshot GMV* Anda.\n\n_(Mohon pastikan tangkapan layar jelas dan bukan foto dari layar perangkat lain)_");
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
            $this->sendMessage($chatId, "❌ Sistem tidak mendeteksi foto. Silakan kirimkan *screenshot GMV* Anda sebagai format gambar (bukan file dokumen).");
            return ['status' => true];
        }

        // Ambil data sesi dari state
        $tempData = $this->conversationState->getTempData($chatId);

        $this->sendMessage($chatId, "⏳ Sistem sedang memproses gambar Anda, mohon tunggu sebentar.");

        // Dispatch job secara sinkron biar user gak nunggu queue worker
        // Jangan hapus state setelahnya karena Job yang akan nge-set state berikutnya.
        ProcessGmvReportJob::dispatchSync(
            $tempData['employee_id'],
            $urlFile,
            $chatId,
            $tempData['account_name'] ?? null,
            $tempData['live_start'] ?? null,
            $tempData['live_end'] ?? null
        );
        return ['status' => true];
    }

    private function processGmvConfirmation(string $chatId, string $message): array
    {
        $message = strtolower(trim($message));
        $tempData = $this->conversationState->getTempData($chatId);

        if (preg_match('/\b(ya+|yes+|y+|iya+|oke+|ok+|setuju|gas|betul|bener|benar|hooh|sip|mantap)\b/i', $message)) {
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

            $platformDisplay = $tempData['platform'] ?? 'Lainnya';
            $accountDisplay = $tempData['account_name'] ?? '';
            $timeDisplay = '';
            if (!empty($tempData['live_start']) && !empty($tempData['live_end'])) {
                $timeDisplay = " ({$tempData['live_start']}-{$tempData['live_end']})";
            }
            $gmvFormatted = number_format($tempData['gmv_amount'], 0, ',', '.');
            $pesanan = $tempData['order_count'] ?? 0;
            $produk_terjual = $tempData['product_sold'] ?? 0;
            $penonton = $tempData['viewers_count'] ?? 0;
            $penonton_tertinggi = $tempData['highest_viewers'] ?? 0;
            
            $newReportText = "Melaporkan data sesi Live Streaming:\n"
                           . "- Platform: {$platformDisplay}\n"
                           . "- Akun: {$accountDisplay}{$timeDisplay}\n"
                           . "- Total Omset/GMV: Rp {$gmvFormatted}\n"
                           . "- Jumlah Pesanan: {$pesanan}\n"
                           . "- Produk Terjual: {$produk_terjual}\n"
                           . "- Total Dilihat: {$penonton}\n"
                           . "- Penonton Tertinggi: {$penonton_tertinggi}\n";
            
            // Lemparkan ke Smart AI untuk dibikinkan Executive Summary yang panjang dan rapi
            \App\Jobs\ProcessSmartDailyReportJob::dispatch($tempData['employee_id'], $newReportText, (string)$chatId);

            $this->conversationState->clearState($chatId);
            $this->sendMessage($chatId, "✅ Mantap! Laporan GMV berhasil disimpan ke sistem.");
            \Illuminate\Support\Facades\Log::info("KOKI GMV: User confirm YA (Tele). Disimpan.");
        } elseif (in_array($message, ['tidak', 't', 'salah', 'enggak', 'nggak', 'tdk'])) {
            $this->conversationState->clearState($chatId);
            $this->sendMessage($chatId, "❌ Laporan dibatalkan.

Silakan kirim ulang gambar dengan kualitas yang lebih tajam/jelas untuk diproses oleh sistem.");
            \Illuminate\Support\Facades\Log::info("KOKI GMV: User confirm TIDAK (Tele). Dibatalkan.");
        } else {
            $this->sendMessage($chatId, "Respons tidak dikenali. Mohon balas dengan *Ya* jika data benar, atau *Tidak* jika salah.");
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
            $this->sendMessage($chatId, "❌ Nomor WhatsApp tidak valid. Harap periksa kembali.");
            return ['status' => true, 'message' => 'Invalid phone'];
        }

        if (Employee::where('phone', $phone)->exists()) {
            $this->sendMessage($chatId, "❌ Nomor WhatsApp ini sudah terdaftar dalam sistem. Harap gunakan nomor yang berbeda.");
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
                                   . "Apakah data di atas sudah benar? Ketik: *ya* atau *tidak*");

        return ['status' => true, 'message' => 'Phone processed'];
    }

    private function processConfirmation(int | string $chatId, string $message): array
    {
        $answer = strtolower(trim($message));
        
        $isPositive = preg_match('/\b(ya+|yes+|y+|iya+|oke+|ok+|setuju|gas|betul|bener|benar|hooh)\b/i', $answer);
        $isNegative = preg_match('/\b(tidak|gak|enggak|nggak|no|n|batal|cancel|salah)\b/i', $answer);

        if ($isNegative) {
            $this->conversationState->clearState($chatId);
            $this->sendMessage($chatId, "❌ Pendaftaran dibatalkan. Anda dapat mengulangi proses dengan mengetik /daftar.");
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
            $balasan = $generateMethod->invoke($ai, $prompt, "Mohon konfirmasi. Ketik *ya* jika data pendaftaran sudah benar, atau *tidak* untuk membatalkan.");
            
            $this->sendMessage($chatId, $balasan);
            return ['status' => true, 'message' => 'Registration paused for confirmation'];
        }

        $tempData = $this->conversationState->getTempData($chatId);

        // Cek apakah ada akun yang soft deleted
        $employee = Employee::withTrashed()
            ->where('telegram_id', (string) $chatId)
            ->orWhere('phone', $tempData['phone'])
            ->first();

        if ($employee) {
            $employee->restore();
            $employee->update([
                'name' => $tempData['name'],
                'division_id' => $tempData['division_id'],
                'phone' => $tempData['phone'],
                'telegram_id' => (string) $chatId,
                'is_active' => true,
            ]);
        } else {
            $employee = Employee::create([
                'name' => $tempData['name'],
                'division_id' => $tempData['division_id'],
                'phone' => $tempData['phone'],
                'telegram_id' => (string) $chatId,
                'is_active' => true,
            ]);
        }

        $this->conversationState->clearState($chatId);

        $this->sendMessage($chatId, "✅ *Pendaftaran Berhasil!*\n\n"
                                   . "Sip, datamu udah kedaftar ya, {$employee->name}! 🎉\n\n"
                                   . "Btw, nomor ini nantinya bakal dipakai buat komunikasi soal absen dan laporan kerjamu. Kalau ke depannya kamu mau:\n"
                                   . "📝 *Lapor harian* (kerjaan atau omset jualan)\n"
                                   . "🤒 *Absensi* (hadir, sakit, atau izin)\n"
                                   . "❓ Nanya soal laporanmu hari ini udah terekap atau belum\n\n"
                                   . "Tinggal chat ke sini aja ya, nanti aku bantu proses. Semangat kerjanya! 😊");

        return ['status' => true, 'message' => 'Registration completed'];
    }

    private function handleEditLaporan(int | string $chatId): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();
        if (!$employee) {
            $this->sendMessage($chatId, "❌ Anda belum terdaftar. Silakan ketik /daftar untuk melakukan pendaftaran.");
            return ['status' => true];
        }

        $sudahLapor = \App\Models\SmartDailyReport::where('employee_id', $employee->id)
            ->whereDate('report_date', now()->format('Y-m-d'))
            ->exists();

        if (!$sudahLapor) {
            $this->sendMessage($chatId, "⚠️ Anda belum mengirimkan laporan hari ini. Silakan ketik laporan Anda atau gunakan menu /lapor.");
            return ['status' => true];
        }

        $this->conversationState->setCurrentStep($chatId, 'awaiting_edit_report_text');
        $this->sendMessage($chatId, "📝 Silakan ketik *teks laporan revisi* Anda secara lengkap. Laporan lama Anda akan digantikan dengan data baru ini.");
        return ['status' => true];
    }

    private function processEditReportText(int | string $chatId, string $message): array
    {
        $employee = Employee::where('telegram_id', $chatId)->first();
        $report = \App\Models\SmartDailyReport::where('employee_id', $employee->id)
            ->whereDate('report_date', now()->format('Y-m-d'))
            ->first();

        if ($report) {
            // Kita biarkan ProcessSmartDailyReportJob yang handle penggabungan/AI-nya
            \App\Jobs\ProcessSmartDailyReportJob::dispatchSync($employee->id, trim($message), (string) $chatId);
            
            $this->conversationState->clearState($chatId);
            $this->sendMessage($chatId, "✅ Laporan tambahan Anda berhasil diproses dan digabungkan oleh AI!");
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
            $this->sendMessage($chatId, "❌ Anda belum terdaftar. Silakan ketik /daftar untuk melakukan pendaftaran.");
            return ['status' => true];
        }

        $info = "👤 *Profil Kamu Saat Ini:*\n"
              . "Nama: {$employee->name}\n"
              . "Divisi: {$employee->division->name}\n"
              . "No WA: {$employee->phone}\n\n"
              . "Pilih data yang ingin Anda ubah:\n"
              . "1. Ubah Nama\n"
              . "2. Ubah Divisi\n"
              . "3. Ubah Nomor WA\n\n"
              . "Balas dengan angka pilihan Anda.";

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
            $this->sendMessage($chatId, "Silakan ketik *Nama Baru* Anda:");
        } elseif ($choice === '2') {
            $divisions = Division::all()->values();
            $divisionList = $divisions->map(fn($d, $index) => ($index + 1) . ". {$d->name}")->implode("\n");
            $this->sendMessage($chatId, "Silakan pilih *Divisi Baru* Anda:

{$divisionList}

Balas dengan angka urutan divisi.");
        } else {
            $this->sendMessage($chatId, "Silakan ketik *Nomor WA Baru* Anda (Format: 62xxx / 08xxx):");
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
            $this->sendMessage($chatId, "✅ Pembaruan berhasil! Nama Anda saat ini: *{$newValue}*");
        } elseif ($choice === '2') {
            $inputIndex = intval($newValue) - 1;
            $divisions = Division::all()->values();
            $division = $divisions->get($inputIndex);
            
            if (!$division) {
                $this->sendMessage($chatId, "❌ Divisi tidak ditemukan. Ketik ulang nomor divisi yang benar:");
                return ['status' => true];
            }
            $employee->update(['division_id' => $division->id]);
            $this->sendMessage($chatId, "✅ Pembaruan berhasil! Divisi Anda saat ini: *{$division->name}*");
        } elseif ($choice === '3') {
            $phone = preg_replace('/\D/', '', $newValue);
            if (str_starts_with($phone, '8')) $phone = '62' . substr($phone, 1);
            if (str_starts_with($phone, '08')) $phone = '62' . substr($phone, 1);
            
            $employee->update(['phone' => $phone]);
            $this->sendMessage($chatId, "✅ Pembaruan berhasil! Nomor WA Anda saat ini: *{$phone}*");
        }

        $this->conversationState->clearState($chatId);
        return ['status' => true];
    }

    private function handleInitManagement(string $chatId): array
    {
        \Illuminate\Support\Facades\Storage::put('management_group_id.txt', $chatId);
        $this->sendMessage($chatId, "✅ Siap bos! Grup ini sekarang jadi pusat notifikasi kehadiran (izin/sakit/wfh) dan penerima laporan PDF harian.");
        return ['status' => true];
    }
}
