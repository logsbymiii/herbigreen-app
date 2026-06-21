<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// 1. Reminder Absen (07:45)
Schedule::command('app:reminder-absen-pagi')->dailyAt('07:45');

// 2. Reminder Lapor Awal (15:45)
Schedule::command('app:reminder-lapor-awal')->dailyAt('15:45');

// 3. Reminder Lapor Wajib (16:00)
Schedule::command('app:reminder-lapor-wajib')->dailyAt('16:00');

// 4. Reminder Lapor Akhir (17:00)
Schedule::command('app:reminder-lapor-akhir')->dailyAt('17:00');

// 5. Deadline Lapor Sore (19:00)
Schedule::command('app:deadline-lapor-sore')->dailyAt('19:00');

// 6. Reminder Lapor Malam (Khusus Host Sesi Malam) (22:30)
Schedule::command('app:reminder-lapor-malam')->dailyAt('22:30');

// 7. Rekap Laporan PDF ke Mas Jodi (00:00)
Schedule::command('app:generate-pdf-recap')->dailyAt('00:00');
