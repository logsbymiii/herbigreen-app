<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiResponseService
{
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
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
     * Generate sapaan dinamis saat user belum terdaftar
     */
    public function greetingUnregistered(string $pesan_masuk): string
    {
        $prompt = "Kamu adalah asisten HR bot Herbigreen. Ada orang asing (belum terdaftar) yang chat bot dengan pesan: '{$pesan_masuk}'.
Buatkan balasan ramah layaknya CS (maksimal 2 kalimat) yang ngasih tau kalau mereka belum terdaftar dan minta mereka ngenalin diri. Arahkan mereka untuk ketik '/daftar'.
Boleh sedikit playful atau casual (misal: 'eh halo! maaf banget nih, kayaknya kita belum kenalan. Ketik /daftar dulu yuk!').
Gunakan bahasa Indonesia. Akhiri dengan emoji.
PENTING: Balas HANYA sapaan singkatnya saja.";

        return $this->generate($prompt, "Eh halo! Maaf banget nih, kayaknya kamu belum terdaftar di sistem. Ketik /daftar dulu yuk biar kita bisa kenalan! 😊");
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
     * Menganalisis pesan user dan menghasilkan intent serta balasan natural.
     * Mengembalikan array dengan key 'intent', 'extracted_data', dan 'reply'.
     */
    public function analyzeIntentAndReply(string $nama, string $divisi, string $pesan, bool $hasMedia = false): array
    {
        $konteksMedia = $hasMedia ? "User juga mengirimkan sebuah gambar/file media bersama pesan ini." : "Tidak ada media tambahan.";

        $prompt = "Kamu adalah asisten HR dan Customer Service bot WhatsApp/Telegram yang sangat ramah, hangat, dan asyik untuk perusahaan Herbigreen.
Nama karyawan yang chat denganmu: {$nama} (Divisi: {$divisi}).
Pesan dari karyawan: \"{$pesan}\"
Konteks: {$konteksMedia}

Tugasmu:
1. Pahami intensi/tujuan dari pesan karyawan tersebut.
2. Buat balasan ('reply') layaknya CS manusia yang empati dan komunikatif. Jangan kaku. Gunakan bahasa Indonesia sehari-hari, sedikit gaul nggak masalah, sisipkan emoji. Jangan panggil dirimu 'bot'. Jangan pernah kirim menu angka 1,2,3,4.
3. Ekstrak data jika ada informasi laporan (misalnya jumlah jualan) atau alasan absen/izin.
4. Output HARUS dalam format JSON murni, tanpa markdown ```json.

Aturan Intent:
- 'report' jika mereka memberikan laporan hasil kerja/penjualan/kegiatan.
- 'gmv_report' jika mereka dari divisi 'Host Live' dan bahas GMV/omset, apalagi jika ada media.
- 'attendance' jika mereka lapor sakit, izin, cuti, atau telat.
- 'status' jika mereka tanya apakah laporan hari ini sudah masuk/belum.
- 'general_chat' jika mereka hanya menyapa (halo, selamat pagi) atau curhat/ngobrol biasa.

Format JSON yang diharapkan:
{
  \"intent\": \"report|gmv_report|attendance|status|general_chat\",
  \"extracted_data\": \"Ringkasan laporan atau alasan absen (jika ada, jika tidak kosongkan)\",
  \"reply\": \"Balasan kamu ke karyawan tersebut\"
}
";

        $jsonString = $this->generate($prompt, json_encode([
            'intent' => 'general_chat',
            'extracted_data' => '',
            'reply' => "Halo {$nama}! Ada yang bisa dibantu hari ini? 😊"
        ]));

        // Bersihkan kalau ada sisa markdown
        $jsonString = preg_replace('/```json|```/', '', $jsonString);
        
        $decoded = json_decode(trim($jsonString), true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['intent'], $decoded['reply'])) {
            return $decoded;
        }

        return [
            'intent' => 'general_chat',
            'extracted_data' => '',
            'reply' => "Halo {$nama}! Aku kurang nangkap nih maksudnya, mau lapor harian atau ada yang lain? 😊"
        ];
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
            $response = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key={$this->apiKey}",
                [
                    'contents' => [[
                        'parts' => [['text' => $prompt]]
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.9,      // Makin tinggi = makin kreatif/random
                        'maxOutputTokens' => 300,  // Cukup panjang untuk JSON
                        'responseMimeType' => 'application/json',
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
