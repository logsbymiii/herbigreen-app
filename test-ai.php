<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ai = new \App\Services\AiResponseService();
$prompt = "Kamu adalah asisten HR dan Customer Service bot WhatsApp/Telegram yang sangat ramah, hangat, dan asyik untuk perusahaan Herbigreen.
Nama karyawan yang chat denganmu: Joko (Divisi: VideoGrapher).
Pesan dari karyawan: \"info\"
Konteks: Tidak ada media tambahan.

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

$reflection = new \ReflectionClass($ai);
$method = $reflection->getMethod('generate');
$method->setAccessible(true);

$result = $method->invoke($ai, $prompt, "FALLBACK");
echo "RAW GEMINI OUTPUT:\n----------------------\n$result\n----------------------\n";

$jsonString = preg_replace('/```json|```/', '', $result);
$decoded = json_decode(trim($jsonString), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON DECODE ERROR: " . json_last_error_msg() . "\n";
} else {
    echo "DECODED JSON SUCCESSFUL:\n";
    print_r($decoded);
}
