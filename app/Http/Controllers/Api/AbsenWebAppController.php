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
        
        // --- VALIDASI WAJAH KOBOI AI (LITELLM) ---
        $llmKey = env('LLM_VISION_API_KEY');
        $llmUrl = env('LLM_BASE_URL', 'https://litellm.koboi2026.biz.id/v1/chat/completions');
        $llmModel = env('LLM_VISION_MODEL', 'gpt-4o');
        
        $isFaceValid = false; // Sistem kembali ketat (Bypass mati)
        if ($llmKey) {
            try {
                $llmResponse = \Illuminate\Support\Facades\Http::timeout(20)->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$llmKey}"
                ])->post($llmUrl, [
                    'model' => $llmModel,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => "Apakah ada orang, wajah, atau bagian tubuh manusia di foto ini (walaupun sedikit gelap/blur)? Jawab 'YA' jika ada indikasi manusia. Jawab 'TIDAK' jika ini murni foto tembok kosong, lantai, atau layar hitam. Jawab HANYA dengan kata 'YA' atau 'TIDAK'."
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => "data:image/jpeg;base64,{$imageParts[1]}"
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'max_tokens' => 10,
                    'temperature' => 0.0
                ]);

                if ($llmResponse->successful()) {
                    $aiAnswer = strtoupper(trim($llmResponse->json('choices.0.message.content')));
                    \Illuminate\Support\Facades\Log::info("KOBOI AI ABSEN WEBAPP: Menjawab -> " . $aiAnswer);
                    if (str_contains($aiAnswer, 'YA')) {
                        $isFaceValid = true;
                    }
                } else {
                    \Illuminate\Support\Facades\Log::error("KOBOI AI ABSEN WEBAPP GAGAL: " . $llmResponse->body());
                    // Fallback: anggap valid jika API Koboi error sementara
                    $isFaceValid = true; 
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Gagal validasi wajah via KOBOI AI WebApp: " . $e->getMessage());
                $isFaceValid = true; // Fallback
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
