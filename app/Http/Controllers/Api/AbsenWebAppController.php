<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\WfhRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\MessageProviderFactory;

class AbsenWebAppController extends Controller
{
    public function showWebapp(Request $request)
    {
        return view('webapp.absen');
    }

    public function submitAbsen(Request $request)
    {
        $telegramId = $request->input('uid');
        $type = $request->input('type');
        $lat = $request->input('latitude');
        $lng = $request->input('longitude');
        $photoBase64 = $request->input('photo');

        if (!$telegramId || !$lat || !$lng || !$photoBase64) {
            return response()->json(['status' => false, 'message' => 'Data tidak lengkap']);
        }

        $employee = Employee::where('telegram_id', $telegramId)->first();
        if (!$employee) {
            return response()->json(['status' => false, 'message' => 'Karyawan tidak ditemukan']);
        }

        // --- CEK JARAK & LOKASI ---
        $officeLat = env('OFFICE_LATITUDE', -7.662837363034964);
        $officeLng = env('OFFICE_LONGITUDE', 112.69715613912979);
        $officeRadius = env('OFFICE_RADIUS', 50);

        // Rumus Haversine buat hitung jarak
        $earthRadius = 6371000; // dalam meter
        $dLat = deg2rad($officeLat - $lat);
        $dLng = deg2rad($officeLng - $lng);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat)) * cos(deg2rad($officeLat)) * sin($dLng/2) * sin($dLng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;

        $isWfh = WfhRequest::where('employee_id', $employee->id)
            ->whereDate('request_date', now()->format('Y-m-d'))
            ->where('status', 'approved')
            ->exists();

        // Keringanan untuk Content Creator / Admin Komen, dan Bebas untuk role Admin
        $isFreelance = in_array(strtolower($employee->division?->name ?? ''), ['content creator', 'admin komen']);
        $isAdmin = strtolower($employee->role ?? '') === 'admin';

        if ($distance > $officeRadius && !$isWfh && !$isFreelance && !$isAdmin) {
            $distFormat = number_format($distance, 0, ',', '.');
            return response()->json([
                'status' => false, 
                'message' => "ABSEN DITOLAK! Kamu berada di luar area kantor (jarakmu {$distFormat} meter dari pusat kantor, maksimal {$officeRadius}m)."
            ]);
        }

        // --- PROSES FOTO ---
        $imageParts = explode(";base64,", $photoBase64);
        if (count($imageParts) != 2) {
            return response()->json(['status' => false, 'message' => 'Format foto tidak valid']);
        }
        
        $imageTypeAux = explode("image/", $imageParts[0]);
        $imageType = $imageTypeAux[1] ?? 'jpeg';
        $imageBase64 = base64_decode($imageParts[1]);

        $filename = 'attendances/webapp_' . Str::random(20) . '.' . $imageType;
        
        try {
            Storage::disk('r2')->put($filename, $imageBase64, 'public');
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Gagal menyimpan foto ke cloud storage']);
        }

        // --- SIMPAN ABSEN ---
        Attendance::create([
            'employee_id' => $employee->id,
            'type'        => 'hadir',
            'note'        => ($type == 'wfh') ? 'Hadir (WFH) via WebApp' : 'Hadir via WebApp',
            'date'        => now()->format('Y-m-d'),
            'proof_path'  => $filename,
        ]);

        // Kirim Notif ke Telegram User
        try {
            $provider = MessageProviderFactory::create();
            $lokasiText = ($isWfh) ? "Hadir (WFH)" : "Hadir (Jarak: " . number_format($distance, 0) . "m)";
            if ($isFreelance && $distance > $officeRadius) $lokasiText .= " [Remote/Freelance]";

            $provider->sendMessage($telegramId, "✅ *Absen Masuk Berhasil!*\n\nStatus: {$lokasiText}\nJam: " . now()->format('H:i') . "\n\nSemangat kerjanya hari ini bosku! 🔥");
        } catch (\Exception $e) {
            // Abaikan jika error kirim pesan Telegram
        }

        return response()->json(['status' => true]);
    }
}
