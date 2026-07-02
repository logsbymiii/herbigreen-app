# [Migrasi Penuh ke Premium Koboi LLM - Dual API]

Mengganti semua otak AI di aplikasi menjadi menggunakan **Koboi LLM** (LiteLLM Proxy) premium milik lo, dengan arsitektur **Terpisah (Dual API)**:
1. **AI Vision (Foto Selfie):** Khusus untuk identifikasi wajah, menggunakan API Key `sk-Uu3Yn...`.
2. **AI Teks (Insight & Chat):** Khusus untuk *parsing* laporan harian, menggunakan API Key `sk-wXwVj...`.

Ini akan memastikan sistem stabil 100% tanpa ada *Rate Limit* murahan lagi, dan pemakaian kuota lu jadi lebih rapi/terpisah!

## User Review Required

> [!WARNING]
> Karena kita akan mengganti seluruh otak AI di tengah-tengah acara lu, pastikan proxy Koboi LLM lu beneran stabil dan *credit*-nya cukup!

## Open Questions

> [!IMPORTANT]
> Gue udah dapet URL-nya (`https://litellm.koboi2026.biz.id/v1/chat/completions`) dan kedua API Key-nya. **TAPI**, untuk parameter `model`, gue bakal *default* ke `gpt-4o` untuk dua-duanya. 
> Kalau lu mau pakai model yang beda (misal foto pakai `gpt-4o` tapi teks pakai `llama-3`), nanti lu tinggal ubah di `.env`. Setuju? Kalau setuju, langsung klik **Proceed** dan gue eksekusi sekarang juga!

## Proposed Changes

### Konfigurasi `.env`
Gue bakal nambahin variabel baru di `.env` lokal:
- `LLM_BASE_URL=https://litellm.koboi2026.biz.id/v1/chat/completions`
- `LLM_VISION_API_KEY=sk-Uu3YnMpqSmqCWFyOf3oqZA`
- `LLM_VISION_MODEL=gpt-4o`
- `LLM_CHAT_API_KEY=sk-wXwVjxpeTLAKJYwd5KXOEg`
- `LLM_CHAT_MODEL=gpt-4o`

---

### Sistem Face Detection (Vision / Foto)

#### [MODIFY] [AbsenWebAppController.php](file:///c:/laragon/www/herbigreen-app/app/Http/Controllers/Api/AbsenWebAppController.php)
- Mengganti *endpoint* Google Gemini dengan *endpoint* OpenAI Format (Koboi).
- Menggunakan kredensial `LLM_VISION_API_KEY` dan `LLM_VISION_MODEL`.
- Mencabut bypass `isFaceValid = true` menjadi kembali ke `false` (sistem deteksi wajah kembali ketat).

#### [MODIFY] [TelegramBotCommandHandler.php](file:///c:/laragon/www/herbigreen-app/app/Services/BotHandlers/TelegramBotCommandHandler.php)
- Mengganti *endpoint* Google Gemini dengan *endpoint* OpenAI Format (Koboi) untuk absen manual via Telegram.
- Menggunakan kredensial Vision.
- Mencabut bypass deteksi wajah.

---

### Sistem Parse Laporan Harian (Chat / Teks)

#### [MODIFY] [ProcessSmartDailyReportJob.php](file:///c:/laragon/www/herbigreen-app/app/Jobs/ProcessSmartDailyReportJob.php)
- Mengubah HTTP Request nembak ke Koboi LLM.
- Menggunakan kredensial `LLM_CHAT_API_KEY` dan `LLM_CHAT_MODEL`.
- Menyesuaikan struktur *JSON Response* dari format Google (`candidates.0...`) menjadi format OpenAI (`choices.0.message.content`).

## Verification Plan
1. Tes kirim foto selfie via WebApp dan pastikan AI Koboi mengenali wajahnya (Bypass mati).
2. Tes kirim laporan harian *template* dan pastikan AI Koboi memberikan hasil *parsing* dengan benar.
