<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MessageClassifier;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessDailyReportJob;
use App\Jobs\ProcessAttendanceJob;
use App\Models\Employee;

class WebhookController extends Controller
{
    public function receive(Request $request, MessageClassifier $classifier)
    {
        Log::info("RAW DATA DARI FONNTE:", $request->all());

        $sender = $request->input('sender');
        $message = $request->input('message');
        $urlFile = $request->input('url');

        // 1. Cari data karyawan berdasarkan nomor WA
        $employee = Employee::where('phone', $sender)->first();

        if (!$employee) {
            Log::warning("Pesan ditolak! Nomor $sender nggak terdaftar.");
            return response()->json(['status' => false, 'message' => 'Unregistered number']);
        }

        // 2. Klasifikasi tipe pesan (daily_report, attendance, atau gmv_report)
        $type = $classifier->classify($sender, $message, !empty($urlFile));

        // Bersihin teks dari mantra biar gak masuk ke database
        $cleanContent = preg_replace('/(#lapor|\/lapor)\s*/i', '', $message);

        Log::info("WA MASUK PAK! Tipe: $type | Dari: {$employee->name} | Isi: $cleanContent");

        // 3. LOGIKA LEMPAR NOTA KE DAPUR (Sesuai Tipe)
        if ($type === 'daily_report') {
            ProcessDailyReportJob::dispatch($employee->id, $cleanContent, $urlFile);
            Log::info("KASIR: Nota Laporan dilempar ke dapur!");

        } elseif ($type === 'attendance') { // 2. PAKE ELSEIF BIAR RAPI
            ProcessAttendanceJob::dispatch($employee->id, $message);
            Log::info("KASIR: Nota Absen dilempar ke dapur!");

        } elseif ($type === 'gmv_report') {
            // Nanti kita bikin koki khusus GMV di sini
            Log::info("KASIR: Pesanan GMV terdeteksi tapi koki belum siap.");
        }

        // 4. Kasih tau Fonnte kalau datanya udah kita terima
        return response()->json([
            'status' => true
        ]);
    }
}
