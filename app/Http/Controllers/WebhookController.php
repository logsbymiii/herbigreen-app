<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MessageClassifier;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessDailyReportJob;
use App\Models\Employee; // PENTING: Wajib panggil model Employee

class WebhookController extends Controller
{
    public function receive(Request $request, MessageClassifier $classifier)
    {
        Log::info("RAW DATA DARI FONNTE:", $request->all());

        $sender = $request->input('sender');
        $message = $request->input('message');
        $urlFile = $request->input('url');

        // 1. Cari dulu ini nomor WA karyawan siapa?
        $employee = Employee::where('phone', $sender)->first();

        // Kalau nomornya ga ada di database, tolak!
        if (!$employee) {
            Log::warning("Pesan ditolak! Nomor $sender nggak terdaftar.");
            return response()->json(['status' => false, 'message' => 'Unregistered number']);
        }

        // 2. Klasifikasi tipe file/pesan
        $type = $classifier->classify($sender, $message, !empty($urlFile));
        $reportContent = preg_replace('/(#lapor|\/lapor)\s*/i', '', $message);

        // Biar muncul di laravel.log (udah pake nama karyawan biar keren)
        Log::info("WA MASUK PAK! Tipe: $type | Dari: {$employee->name} | Isi: $reportContent");

        // 3. Simpan ke Database sesuai tipe
        if ($type === 'daily_report') {
            // Lempar ke antrean Job!
            ProcessDailyReportJob::dispatch($employee->id, $reportContent, $urlFile);
            Log::info("KASIR: Nota dilempar ke dapur!");
        }
        // else if buat attendance nanti nyusul

        // 4. PENTING: Balasan 200 OK ke Fonnte WAJIB di paling bawah!
        return response()->json([
            'status' => true
        ]);
    }
}
