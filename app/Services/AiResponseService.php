<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiResponseService
{
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    /**
     * Generate sapaan dinamis saat user ketik /lapor
     */
    public function greetingLapor(string $nama): string
    {
        $prompt = "Kamu adalah asisten HR bot WhatsApp/Telegram untuk perusahaan bernama Herbigreen. 
Buatkan sapaan singkat (maksimal 2 kalimat) yang hangat, semangat, dan sedikit casual untuk karyawan bernama {$nama} yang baru saja akan mengirimkan laporan harian.
Variasikan gaya sapaan setiap saat — bisa pake analogi, quotes singkat, atau just being fun.
Gunakan bahasa Indonesia. Jangan lebay. Akhiri dengan emoji yang relevan.
PENTING: Balas HANYA sapaan singkatnya saja, tanpa penjelasan tambahan.";

        return $this->generate($prompt, "Halo, *{$nama}*! Yuk laporan hariannya! 💪");
    }

    /**
     * Generate sapaan dinamis saat user ketik /daftar
     */
    public function greetingDaftar(): string
    {
        $prompt = "Kamu adalah asisten HR bot WhatsApp/Telegram untuk perusahaan Herbigreen.
Buatkan sapaan pembuka yang hangat dan mengundang (maksimal 2 kalimat) untuk karyawan baru yang baru saja mau mendaftarkan diri ke sistem.
Variasikan gaya sapaan — bisa friendly, fun, atau encouraging.
Gunakan bahasa Indonesia. Jangan lebay.
PENTING: Balas HANYA sapaan singkatnya saja, tanpa penjelasan tambahan.";

        return $this->generate($prompt, "Halo! Selamat datang di Herbigreen! 🎉");
    }

    /**
     * Generate sapaan dinamis saat user ketik /absen atau /izin
     */
    public function greetingAbsen(string $nama): string
    {
        $prompt = "Kamu adalah asisten HR bot Herbigreen.
Buatkan sapaan singkat (1-2 kalimat) yang empatik dan supportif untuk karyawan bernama {$nama} yang mau lapor absen/izin/sakit.
Variasikan gaya — bisa caring, encouraging, atau ringan tapi tetap sopan.
Gunakan bahasa Indonesia. Akhiri dengan emoji yang relevan.
PENTING: Balas HANYA sapaan singkatnya saja.";

        return $this->generate($prompt, "Halo, *{$nama}*! Semoga semuanya baik-baik saja ya. 🙏");
    }

    /**
     * Generate konfirmasi setelah absen berhasil dicatat
     */
    public function confirmAbsen(string $nama, string $type): string
    {
        $prompt = "Kamu adalah asisten HR bot Herbigreen.
Buatkan pesan konfirmasi singkat (1-2 kalimat) yang hangat untuk karyawan bernama {$nama} yang baru saja lapor {$type}.
Variasikan pesannya — bisa doain cepat sembuh (kalau sakit), semoga urusan lancar (kalau izin), atau nikmati istirahat (kalau cuti).
Gunakan bahasa Indonesia. Akhiri dengan emoji.
PENTING: Balas HANYA pesannya saja.";

        $fallback = match($type) {
            'sakit' => "✅ *Tercatat, {$nama}!* Semoga lekas sembuh ya, istirahat yang cukup! 🙏",
            'cuti'  => "✅ *Tercatat, {$nama}!* Selamat menikmati cutinya, istirahat yang baik! 🌴",
            default => "✅ *Tercatat, {$nama}!* Izinmu sudah direkam. Semoga urusannya lancar! 💪",
        };

        return $this->generate($prompt, $fallback);
    }

    /**
     * Generate balasan setelah laporan berhasil disimpan
     */
    public function confirmLaporan(string $nama): string
    {
        $prompt = "Kamu adalah asisten HR bot Herbigreen.
Buatkan konfirmasi singkat (1-2 kalimat) yang hangat dan menyemangati untuk karyawan bernama {$nama} yang baru saja berhasil mengirim laporan harian.
Variasikan pesannya — bisa apresiasi, motivasi, atau fun fact singkat soal produktivitas.
Gunakan bahasa Indonesia. Akhiri dengan emoji.
PENTING: Balas HANYA konfirmasinya saja.";

        return $this->generate($prompt, "✅ *Terima kasih, {$nama}!* Laporan harianmu sudah berhasil dicatat. Semangat terus! 💪");
    }

    /**
     * Core function: hit Gemini API, fallback ke static kalo gagal
     */
    private function generate(string $prompt, string $fallback): string
    {
        if (!$this->apiKey) {
            return $fallback;
        }

        try {
            $response = Http::timeout(10)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$this->apiKey}",
                [
                    'contents' => [[
                        'parts' => [['text' => $prompt]]
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.9,      // Makin tinggi = makin kreatif/random
                        'maxOutputTokens' => 100,  // Biar nggak kepanjangan
                    ]
                ]
            );

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text');
                if ($text) {
                    return trim($text);
                }
            }

            Log::warning('AiResponseService: Gemini response kosong, pakai fallback.');
        } catch (\Exception $e) {
            Log::error('AiResponseService: Gagal hit Gemini API: ' . $e->getMessage());
        }

        return $fallback;
    }
}
