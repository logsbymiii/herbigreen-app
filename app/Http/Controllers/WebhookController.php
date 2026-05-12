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

        $employee = Employee::where('phone', $sender)->first();

        if (!$employee) {
            Log::warning("Pesan ditolak! Nomor $sender nggak terdaftar.");
            return response()->json(['status' => false, 'message' => 'Unregistered number']);
        }

        $type = $classifier->classify($sender, $message, !empty($urlFile));

        $cleanContent = preg_replace('/(#lapor|\/lapor)\s*/i', '', $message);

        Log::info("WA MASUK PAK! Tipe: $type | Dari: {$employee->name} | Isi: $cleanContent");

        if ($type === 'daily_report') {
            ProcessDailyReportJob::dispatch($employee->id, $cleanContent, $urlFile);
            Log::info("KASIR: Nota Laporan dilempar ke dapur!");

        } elseif ($type === 'attendance') {
            ProcessAttendanceJob::dispatch($employee->id, $message);
            Log::info("KASIR: Nota Absen dilempar ke dapur!");

        } elseif ($type === 'gmv_report') {
            Log::info("KASIR: Pesanan GMV terdeteksi tapi koki belum siap.");
        }

        return response()->json([
            'status' => true
        ]);
    }
}
