# Dual Message Provider Setup (Fonnte + Telegram)

## Arsitektur

```
MessageProviderInterface (Contract)
    ├── FonnteService (WhatsApp via Fonnte API)
    └── TelegramService (Telegram Bot)

MessageProviderFactory (Pilih provider berdasarkan .env)

WebhookController
    ├── receive() → Fonnte webhook
    └── receiveTelegram() → Telegram webhook
```

## Setup

### 1. Jalankan Migration
```bash
php artisan migrate
```

Ini akan menambah kolom `telegram_id` ke tabel `employees`.

### 2. Update `.env`

Tambahkan konfigurasi ini ke file `.env`:

```env
# Pilih provider: 'fonnte' atau 'telegram'
MESSAGE_PROVIDER=fonnte

# Telegram Bot Token (dapatkan dari @BotFather di Telegram)
TELEGRAM_BOT_TOKEN=your_telegram_bot_token_here
```

**Saat ini:** `MESSAGE_PROVIDER=fonnte` (pakai Fonnte)
**Nanti (switch ke Telegram):** `MESSAGE_PROVIDER=telegram`

### 3. Webhook Endpoints

**Fonnte Webhook:**
```
POST /api/webhook/fonnte
```

**Telegram Webhook:**
```
POST /api/webhook/telegram
```

## Cara Kerja

### Fonnte (WhatsApp)
1. Employee kirim pesan ke nomor bot WA
2. Fonnte mengirim webhook ke `/api/webhook/fonnte`
3. WebhookController.receive() memproses pesan
4. Balas menggunakan FonnteService.sendMessage()

### Telegram
1. Employee kirim pesan ke bot Telegram
2. Telegram mengirim update ke `/api/webhook/telegram`
3. WebhookController.receiveTelegram() memproses pesan
4. Balas menggunakan TelegramService.sendMessage()

### Employee Model
- **Fonnte:** Gunakan kolom `phone` (format: 62xxx)
- **Telegram:** Gunakan kolom `telegram_id` (numeric chat ID)

## Switching Provider

### Dari Fonnte ke Telegram:
```env
MESSAGE_PROVIDER=telegram
```

### Dari Telegram ke Fonnte:
```env
MESSAGE_PROVIDER=fonnte
```

Tidak perlu restart atau deploy — cukup ubah .env!

## File yang Dibuat/Diubah

**Baru:**
- `app/Contracts/MessageProviderInterface.php` - Interface
- `app/Services/TelegramService.php` - Telegram implementation
- `app/Services/MessageProviderFactory.php` - Factory pattern
- `database/migrations/2026_06_04_000001_add_telegram_id_to_employees_table.php` - Migration

**Diubah:**
- `app/Services/FonnteService.php` - Implement interface
- `app/Http/Controllers/WebhookController.php` - Support dual webhooks
- `routes/api.php` - Tambah Telegram endpoint

## Testing

### Test Fonnte (manual atau Postman)
```bash
POST /api/webhook/fonnte
Content-Type: application/json

{
  "sender": "085606178752",
  "message": "sakit",
  "url": null
}
```

### Test Telegram (manual atau Postman)
```bash
POST /api/webhook/telegram
Content-Type: application/json

{
  "message": {
    "from": {"id": 123456789},
    "text": "sakit"
  }
}
```

## Next Steps

1. ✅ Abstraksi message provider
2. ✅ Setup Telegram service
3. ✅ Update webhook controller
4. ⏭️ Run migration untuk tambah `telegram_id`
5. ⏭️ Setup Telegram Bot Token di `.env`
6. ⏭️ Update employee records dengan Telegram ID (jika perlu)
