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
     * Generate sapaan dinamis saat user bilang halo/hai/selamat pagi
     */
    public function greetingMenu(string $nama): string
    {
        $prompt = "Kamu adalah asisten HR bot WhatsApp Herbigreen.
Buatkan sapaan singkat yang hangat dan casual (1 kalimat saja) untuk karyawan bernama {$nama} yang baru aja chat bot.
Variasikan gaya — bisa pagi/siang/malam yang kontekstual, fun, atau friendly.
Gunakan bahasa Indonesia. Akhiri dengan emoji.
PENTING: Balas HANYA sapaan singkatnya saja, tanpa penjelasan.";

        return $this->generate($prompt, "Halo, *{$nama}*! Senang bisa membantu 😊");
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
     * Generate reminder personal per karyawan berdasarkan kebiasaan lapor
     */
    public function generateSmartReminder(string $nama, string $divisi, int $hariTerlambat = 0): string
    {
        $konteks = $hariTerlambat > 0
            ? "Karyawan ini sudah {$hariTerlambat} hari berturut-turut tidak lapor."
            : "Karyawan ini biasanya lapor, tapi belum lapor hari ini.";

        $prompt = "Kamu adalah asisten HR bot Herbigreen.
{$konteks}
Buatkan pesan reminder singkat (2-3 kalimat) yang personal dan persuasif untuk karyawan bernama {$nama} dari divisi {$divisi} yang belum mengirim laporan hari ini.
Jangan terlalu formal, tapi tetap profesional. Bisa sedikit playful atau empathetic tergantung konteks.
Gunakan bahasa Indonesia casual. Akhiri dengan emoji yang relevan.
PENTING: Balas HANYA pesannya saja, tanpa penjelasan.";

        return $this->generate($prompt, "Halo *{$nama}*, jangan lupa kirim laporan hari ini ya sebelum jam 6 sore! 🌿");
    }

    /**
     * Generate insight AI untuk rekap malam harian ke Mas Jodi
     */
    public function generateNightSummaryInsight(int $total, int $laporan, int $izin, int $belumLapor, array $namaBelumLapor): string
    {
        $listBelum = empty($namaBelumLapor) ? 'Nihil' : implode(', ', array_slice($namaBelumLapor, 0, 5));
        $persentase = $total > 0 ? round(($laporan / $total) * 100) : 0;

        $prompt = "Kamu adalah AI analis HR untuk perusahaan Herbigreen.
Data kehadiran hari ini:
- Total karyawan aktif: {$total}
- Yang sudah lapor: {$laporan} ({$persentase}%)
- Yang izin/sakit: {$izin}
- Yang belum lapor sama sekali: {$belumLapor}
- Nama yang belum lapor: {$listBelum}

Berikan insight singkat (2-3 kalimat) dan rekomendasi tindakan buat manajer dalam bahasa Indonesia yang natural dan actionable.
Fokus pada pola yang perlu diperhatikan. Jangan lebay.
PENTING: Balas HANYA insight-nya saja.";

        $fallback = $belumLapor === 0
            ? "🎉 Hari yang luar biasa! Semua karyawan sudah lapor."
            : "⚠️ Ada {$belumLapor} karyawan yang perlu di-follow up besok.";

        return $this->generate($prompt, $fallback);
    }

    /**
     * Generate laporan mingguan AI untuk Mas Jodi (setiap Senin)
     */
    public function generateWeeklySummary(array $stats): string
    {
        $divisiStats = collect($stats['per_divisi'])->map(function ($d) {
            return "{$d['nama']}: {$d['laporan']} laporan, {$d['izin']} izin";
        })->implode(' | ');

        $prompt = "Kamu adalah AI analis HR senior untuk perusahaan Herbigreen.
Berikut data performa tim minggu lalu:
- Total laporan masuk: {$stats['total_laporan']}
- Total karyawan aktif: {$stats['total_karyawan']}
- Rata-rata laporan per hari: {$stats['rata_laporan_per_hari']}
- Total izin/sakit: {$stats['total_izin']}
- Tingkat kepatuhan laporan: {$stats['persentase_compliance']}%
- Performa per divisi: {$divisiStats}
- Karyawan paling konsisten: {$stats['top_reporter']}
- Karyawan perlu perhatian: {$stats['needs_attention']}

Buatkan laporan mingguan eksekutif (maksimal 5 kalimat) yang mencakup:
1. Ringkasan performa keseluruhan
2. Divisi terbaik dan yang perlu perhatian
3. Rekomendasi konkret untuk minggu ini
Gunakan bahasa Indonesia profesional tapi tidak kaku. Sertakan emoji yang relevan.
PENTING: Balas HANYA laporannya saja.";

        return $this->generate($prompt, "📊 Laporan minggu lalu: {$stats['total_laporan']} laporan masuk dari {$stats['total_karyawan']} karyawan aktif.");
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
