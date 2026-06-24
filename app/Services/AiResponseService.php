<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiResponseService
{
    private ?string $apiKey;

    public function __construct()
    {
        // Ganti jadi pakai Groq
        $this->apiKey = env('GROQ_API_KEY');
    }

    /**
     * Generate sapaan dinamis saat user bilang halo/hai/selamat pagi
     */
    public function greetingMenu(string $nama): string
    {
        $prompt = "Kamu adalah asisten HR (satu orang) di WhatsApp Herbigreen. WAJIB selalu gunakan kata ganti 'aku' (jangan pernah pakai kata 'kami').
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
        $prompt = "Kamu adalah asisten HR (satu orang) di perusahaan Herbigreen. WAJIB selalu gunakan kata ganti 'aku' (jangan pernah pakai kata 'kami').
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
        $prompt = "Kamu adalah asisten HR (satu orang) di Herbigreen. WAJIB selalu gunakan kata ganti 'aku' (jangan pernah pakai kata 'kami').
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
        $prompt = "Kamu adalah asisten HR (satu orang) di Herbigreen. WAJIB selalu gunakan kata ganti 'aku' (jangan pernah pakai kata 'kami'). Ada orang asing (belum terdaftar) yang chat kamu dengan pesan: '{$pesan_masuk}'.
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
        $prompt = "Kamu adalah asisten HR (satu orang) di Herbigreen. WAJIB selalu gunakan kata ganti 'aku' (jangan pernah pakai kata 'kami').
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
        $prompt = "Kamu adalah asisten HR (satu orang) di Herbigreen. WAJIB selalu gunakan kata ganti 'aku' (jangan pernah pakai kata 'kami').
Buatkan pesan konfirmasi singkat (1-2 kalimat) yang hangat untuk karyawan bernama {$nama} yang baru saja lapor {$type}.
Variasikan pesannya — bisa doain cepat sembuh (kalau sakit) atau semoga urusan lancar (kalau izin).
Gunakan bahasa Indonesia. Akhiri dengan emoji.
PENTING: Balas HANYA pesannya saja.";

        $responseTemplates = [
            'sakit' => "✅ *Syafakallah, {$nama}!* Semoga lekas sembuh dan bisa beraktivitas kembali ya.",
            'izin'  => "✅ *Siap, {$nama}.* Izin sudah dicatat. Semoga urusannya lancar!",
            'telat' => "✅ *Oke, {$nama}.* Hati-hati di jalan, utamakan keselamatan ya!",
        ];

        return $this->generate($prompt, $responseTemplates[$type] ?? "✅ *Tercatat, {$nama}!* Sudah kami rekam.");
    }

    /**
     * Generate balasan setelah laporan berhasil disimpan
     */
    public function confirmLaporan(string $nama): string
    {
        $prompt = "Kamu adalah asisten HR (satu orang) di Herbigreen. WAJIB selalu gunakan kata ganti 'aku' (jangan pernah pakai kata 'kami').
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

        $prompt = "Kamu adalah asisten HR atau supervisor di Herbigreen. Kamu adalah SATU ORANG (individu), jadi WAJIB menggunakan kata ganti 'aku' (jangan pernah menggunakan kata 'kami').
{$konteks}
Buatkan pesan reminder singkat (2-3 kalimat) yang personal dan persuasif untuk karyawan bernama {$nama} dari divisi {$divisi} yang belum mengirim laporan hari ini.
Berperanlah layaknya HRD/supervisor sungguhan yang sedang memantau progress mereka secara langsung. Jangan terlalu kaku.
Gunakan bahasa Indonesia casual ('aku' dan 'kamu'). Akhiri dengan emoji yang relevan.
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
    public function analyzeIntentAndReply(string $nama, string $divisi, string $pesan, bool $hasFile, ?string $todaysReportContent = null, ?string $todaysAttendanceStatus = null): array
    {
        $fileContext = $hasFile ? "(Karyawan melampirkan gambar/file)" : "(Tidak ada lampiran)";
        $reportStatusContext = $todaysReportContent 
            ? "Status Laporan Hari Ini: SUDAH LAPOR\nIsi Laporan Hari Ini: \"{$todaysReportContent}\"" 
            : "Status Laporan Hari Ini: BELUM LAPOR";
            
        if ($todaysAttendanceStatus) {
            $reportStatusContext .= "\n" . $todaysAttendanceStatus;
        }

        $currentTime = now()->translatedFormat('l, d F Y H:i');
        $prompt = "Kamu adalah asisten HR dan Operasional (SATU ORANG) bernama 'Mbak HR' di perusahaan Herbigreen. Gunakan kata ganti 'aku' HANYA untuk menyebut dirimu sendiri (jangan gunakan kata 'kami'). Panggil karyawan dengan sapaan santai menggunakan namanya yaitu {$nama}.
Kamu melayani karyawan bernama {$nama} dari divisi {$divisi}. Waktu saat ini: {$currentTime} (Gunakan waktu ini sebagai acuan untuk menyapa pagi/siang/sore/malam).

Data Saat Ini:
{$reportStatusContext}
Pesan Karyawan: \"{$pesan}\"
Lampiran: {$fileContext}

Tugasmu:
1. Pahami intensi/tujuan dari pesan karyawan tersebut.
2. Buat balasan ('reply') yang SANGAT SINGKAT (maksimal 1 kalimat pendek), santai, dan ramah layaknya teman kerja. JANGAN kaku. JANGAN panjang lebar atau bertele-tele. JANGAN pakai kata-kata aneh. Beri respon yang mengalir natural.
3. JANGAN mengulang sapaan (Halo/Pagi/Siang/Sore/Malam) terus-menerus di setiap balasan biar nggak kayak robot. 
4. JIKA karyawan memberikan laporan harian atau gambar kerja, balas dengan apresiasi singkat dan tegaskan bahwa laporannya SUDAH DICATAT. Contoh: \"Sip, laporan harianmu udah aku catet ya!\"
5. JIKA karyawan mengabarkan tentang absen, hadir, WFH, sakit, izin, cuti, telat (intent: attendance), balas SANGAT SINGKAT (karena akan otomatis diarahkan ke menu absen).
6. JIKA karyawan mengecek status atau nanya \"aku lapor apa?\" (intent: status): 
   - Kalau SUDAH LAPOR kerja, kasih tau santai aja bahwa datanya udah aman (contoh: \"Udah aman bos! Tadi kamu lapor ini:\"). JANGAN masukkan isi laporannya ke dalam balasanmu!
   - Kalau SUDAH ABSEN (Hadir/WFH/Sakit/Izin/Cuti), sebutkan status absennya.
   - Kalau BELUM SAMA SEKALI, ingatkan santai buat lapor atau absen.
7. JIKA karyawan HANYA menyapa (\"halo\", \"pagi\", \"test\"), sapa balik santai dan tanyakan ada yang bisa dibantu.
8. JIKA karyawan membatalkan atau mengakhiri percakapan (\"gak jadi\", \"oke\", \"sip\", \"makasih\", \"baik\", \"batal\"), balas SANGAT SINGKAT (contoh: \"Oke sip!\", \"Sama-sama!\"). JANGAN tanya balik \"ada yang bisa dibantu?\".
9. JIKA karyawan tanya cara pakai bot atau cara lapor, langsung berikan panduan singkat: 'Ketik aja /start buat liat menu lengkapnya!'
10. Ekstrak data jika ada teks laporan. JANGAN PERNAH meringkas isi laporan. Jika user memberikan laporan, isi 'extracted_data' dengan KATA-KATA PERSIS (exact match) dari laporan user secara full. JIKA user berniat mengedit laporan (intent: edit_report), ekstrak HANYA teks laporan baru/revisinya ke dalam 'extracted_data' dan biarkan 'reply' kosong.
11. Output HARUS format JSON murni.

Aturan Intent:
- 'report' jika karyawan memberikan laporan hasil kerja/kegiatan, ngirim gambar tanpa teks (selain divisi Host Live), ATAU menyatakan ingin lapor (contoh: 'aku mau lapor', 'mau laporan').
- 'gmv_report' jika divisi 'Host Live' membahas GMV/omset ATAU sekadar ngirim gambar/lampiran tanpa teks (asumsikan itu foto omset).
- 'attendance' jika membahas absen, hadir, wfh, sakit, izin, cuti, telat.
- 'status' jika tanya laporan masuk/belum atau ngecek status hari ini.
- 'edit_report' jika memberitahu ada laporan yang salah atau ingin mengubah laporan.
- 'end_conversation' jika pesan HANYA akhiran (oke, sip, makasih, batal, baik).
- 'general_chat' jika ngobrol biasa/nyapa.

Format JSON yang diharapkan:
{
  \"intent\": \"report|gmv_report|attendance|status|edit_report|end_conversation|general_chat\",
  \"attendance_type\": \"sakit|izin|cuti|telat|hadir|wfh (isi jika attendance, selain itu kosong)\",
  \"extracted_data\": \"Isi text laporan user secara FULL dan PERSIS (jangan diringkas)\",
  \"gmv_account\": \"Nama akun live (jika intent gmv_report dan disebutkan)\",
  \"gmv_start\": \"Jam mulai live format HH:MM (jika disebutkan)\",
  \"gmv_end\": \"Jam selesai live format HH:MM (jika disebutkan)\",
  \"reply\": \"Balasan kamu yang sangat casual dan singkat\"
}
";

        $jsonString = $this->generate($prompt, json_encode([
            'intent' => 'general_chat',
            'attendance_type' => '',
            'extracted_data' => '',
            'gmv_account' => '',
            'gmv_start' => '',
            'gmv_end' => '',
            'reply' => "Halo {$nama}! Ada yang bisa kubantu hari ini? 😊"
        ]), true);

        // Bersihkan kalau ada sisa markdown
        $jsonString = preg_replace('/```json|```/i', '', $jsonString);
        
        $decoded = json_decode(trim($jsonString), true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['intent'], $decoded['reply'])) {
            return $decoded;
        }

        \Illuminate\Support\Facades\Log::warning("AI JSON Parse Failed atau Key Hilang. Raw Output: " . $jsonString);

        return [
            'intent' => 'general_chat',
            'extracted_data' => '',
            'reply' => "Halo {$nama}! Aku kurang nangkap nih maksudnya, mau lapor harian atau ada yang lain? 😊"
        ];
    }

    /**
     * Core function: hit Groq API, fallback ke static kalo gagal
     */
    private function generate(string $prompt, string $fallback, bool $isJson = false): string
    {
        if (!$this->apiKey) {
            \Illuminate\Support\Facades\Log::warning('AiResponseService: GROQ_API_KEY belum diset.');
            return $fallback;
        }

        try {
            // Groq API Endpoint (OpenAI compatible)
            $payload = [
                'model' => 'llama-3.3-70b-versatile',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $isJson ? 'You are a helpful assistant that outputs ONLY valid JSON. Do not include any markdown blocks or conversational text outside the JSON.' : 'You are a helpful, human-like HR assistant. Respond directly without any formatting wrappers.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => $isJson ? 0.3 : 0.7,
                'max_tokens' => 500,
            ];

            if ($isJson) {
                $payload['response_format'] = ['type' => 'json_object'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.groq.com/openai/v1/chat/completions', $payload);

            if ($response->successful()) {
                $text = $response->json('choices.0.message.content');
                if ($text) {
                    return trim($text);
                }
            }

            \Illuminate\Support\Facades\Log::warning('AiResponseService: Groq response gagal atau kosong. Status: ' . $response->status() . ' Body: ' . $response->body());
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AiResponseService Error: ' . $e->getMessage());
        }

        return $fallback;
    }
}
