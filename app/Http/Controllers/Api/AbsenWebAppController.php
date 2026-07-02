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
        $expectedSessions = $request->input('sessions', 1);

        if (!$telegramId || !$lat || !$lng || !$photoBase64) {
            return response()->json(['status' => false, 'message' => 'Data tidak lengkap']);
        }

        $employee = Employee::where('telegram_id', $telegramId)->first();
        if (!$employee) {
            return response()->json(['status' => false, 'message' => 'Karyawan tidak ditemukan']);
        }

        // --- CEK DOUBLE ABSEN ---
        $sudahAbsen = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', now()->format('Y-m-d'))
            ->whereIn('type', ['hadir', 'wfh', 'telat'])
            ->exists();

        if ($sudahAbsen) {
            return response()->json(['status' => false, 'message' => 'Anda sudah tercatat melakukan absensi hari ini.']);
        }

        // --- CEK JARAK & LOKASI ---
        $officeLat = env('OFFICE_LATITUDE', -7.6631268);
        $officeLng = env('OFFICE_LONGITUDE', 112.6964359);
        $officeRadius = 200; // Hardcoded untuk demo

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
            return response()->json([
                'status' => false, 
                'message' => "ABSEN DITOLAK! Kamu berada di luar area kantor."
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
        
        // --- VALIDASI WAJAH AI GEMINI ---
        $geminiKey = config('services.gemini.api_key') ?? env('GEMINI_API_KEY');
        $isFaceValid = false; // SUPER KETAT
        if ($geminiKey) {
            try {
                $geminiResponse = \Illuminate\Support\Facades\Http::timeout(15)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$geminiKey}", [
                    'contents' => [[
                        'parts' => [
                            [
                                'inline_data' => [
                                    'mime_type' => 'image/jpeg',
                                    'data' => $imageParts[1],
                                ]
                            ],
                            [
                                'text' => "Apakah ada orang, wajah, atau bagian tubuh manusia di foto ini (walaupun sedikit gelap/blur)? Jawab 'YA' jika ada indikasi manusia. Jawab 'TIDAK' jika ini murni foto tembok kosong, lantai, atau layar hitam. Jawab HANYA dengan kata 'YA' atau 'TIDAK'."
                            ]
                        ]
                    ]]
                ]);

                if ($geminiResponse->successful()) {
                    $aiAnswer = strtoupper(trim($geminiResponse->json('candidates.0.content.parts.0.text')));
                    \Illuminate\Support\Facades\Log::info("KOKI AI ABSEN WEBAPP: Gemini menjawab -> " . $aiAnswer);
                    if (str_contains($aiAnswer, 'YA')) {
                        $isFaceValid = true;
                    }
                } else {
                    \Illuminate\Support\Facades\Log::error("KOKI AI ABSEN WEBAPP GAGAL: " . $geminiResponse->body());
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Gagal validasi wajah via AI WebApp: " . $e->getMessage());
            }
        }

        if (!$isFaceValid) {
            return response()->json([
                'status' => false, 
                'message' => 'ABSEN DITOLAK! Wajah kamu tidak terlihat dengan jelas. Pastikan foto selfie menampilkan wajahmu.'
            ]);
        }

        try {
            Storage::disk('r2')->put($filename, $imageBase64, 'public');
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Gagal menyimpan foto ke cloud storage']);
        }

        // --- SIMPAN ABSEN ---
        $attendanceType = ($type == 'wfh') ? 'wfh' : 'hadir';
        
        // Cek telat jam 08:30
        if ($attendanceType === 'hadir' && now()->format('H:i') > '08:30') {
            $attendanceType = 'telat';
        }

        $note = ($type == 'wfh') ? 'Hadir (WFH) via WebApp' : 'Hadir via WebApp';
        if ($attendanceType === 'telat') {
            $note = 'Telat via WebApp';
        }
        
        Attendance::create([
            'employee_id' => $employee->id,
            'type'        => $attendanceType,
            'note'        => $note,
            'latitude'    => $lat,
            'longitude'   => $lng,
            'date'        => now()->format('Y-m-d'),
            'clocked_in_at' => now(),
            'proof_path'  => $filename,
            'expected_sessions' => $expectedSessions,
        ]);

        // Kirim Notif ke Telegram User
        try {
            $provider = MessageProviderFactory::create();
            
            if ($attendanceType === 'telat') {
                $lokasiText = "Telat (Luar Batas Waktu)";
            } else {
                $lokasiText = ($isWfh) ? "Hadir (WFH)" : "Hadir (Di Kantor)";
            }
            
            if ($isFreelance && $distance > $officeRadius) $lokasiText .= " [Remote/Freelance]";

            $judulNotif = ($attendanceType === 'telat') ? "⚠️ *Absen Masuk (Terlambat)*" : "✅ *Absen Masuk Berhasil*";
            $provider->sendMessage($telegramId, "{$judulNotif}\n\nStatus: {$lokasiText}\nJam: " . now()->format('H:i') . "\n\nData kehadiran telah tercatat di sistem.");
        } catch (\Exception $e) {
            // Abaikan jika error kirim pesan Telegram
        }

        return response()->json(['status' => true]);
    }
}
