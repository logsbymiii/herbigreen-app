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
use App\Services\FonnteService;
class WebhookController extends Controller
{
    public function receive(Request $request, MessageClassifier $classifier, FonnteService $fonnte)
    {
        Log::info("RAW DATA DARI FONNTE:", $request->all());

        $sender = $request->input('sender');
        $message = $request->input('message');
        $urlFile = $request->input('url');

        if(str_starts_with($sender, '08')){
            $sender = '62'. substr($sender, 1);
        }

        // 1. Cari data karyawan berdasarkan nomor
        $employee = Employee::where('phone', $sender)->first();

      if (!$employee) {
      Log::warning("Pesan ditolak! Nomor $sender nggak terdaftar.");

      // Panggil mulut bot buat bales otomatis
        $balasan = "Maaf, nomor WA Anda belum terdaftar di sistem kami. Silakan hubungi (Admin) untuk pendaftaran. 🙏";
        $fonnte->sendMessage($sender, $balasan);

    return response()->json(['status' => false, 'message' => 'Unregistered number']);
}

        // 2. Klasifikasi tipe pesan (daily_report, attendance, atau gmv_report)
        $type = $classifier->classify($sender, $message, !empty($urlFile));

        // Bersihin teks dari mantra biar gak masuk ke database
        $cleanContent = preg_replace('/(#lapor|\/lapor)\s*/i', '', $message);

        Log::info("WA MASUK PAK! Tipe: $type | Dari: {$employee->name} | Isi: $cleanContent");

       // 3. LOGIKA LEMPAR NOTA KE DAPUR (Sesuai Tipe)
        if ($type === 'daily_report') {

            // Cek apakah karyawan ini udah setor laporan hari ini
            $sudahLapor = Report::where('employee_id', $employee->id)
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->exists();

            if ($sudahLapor) {
                Log::warning("KASIR: Laporan {$employee->name} ditolak karena sudah lapor hari ini.");

                $balasan = "Halo {$employee->name}, kamu sudah mengirim laporan untuk hari ini. Laporan cukup dikirim 1 kali sehari saja ya. Terima kasih! 🙏";
                $fonnte->sendMessage($sender, $balasan);
            } else {
                ProcessDailyReportJob::dispatch($employee->id, $cleanContent, $urlFile);
                Log::info("KASIR: Nota Laporan dilempar ke dapur!");
            }

        } elseif ($type === 'attendance') {
            ProcessAttendanceJob::dispatch($employee->id, $message);
            Log::info("KASIR: Nota Absen dilempar ke dapur!");

        } elseif ($type === 'gmv_report') {
            ProcessGmvReportJob::dispatch($employee->id, $urlFile);
            Log::info("KASIR: Nota GMV (Screenshot) dilempar ke dapur!");
        }

        // 4. Kasih tau Fonnte kalau datanya udah kita terima
        return response()->json([
            'status' => true
        ]);
    }
}
