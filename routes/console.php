<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Laporan anak mangkir dikirim tiap jam 8 pagi
Schedule::command('app:send-morning-exception')->dailyAt('08:00');

// Reminder belum absen dikirim tiap jam 3 sore
Schedule::command('app:send-daily-reminder')->dailyAt('15:00');

// Rekap total harian dikirim tiap jam 8 malam
Schedule::command('app:send-night-summary')->dailyAt('20:00');

// Laporan mingguan AI dikirim tiap Senin pagi jam 7
Schedule::command('app:send-weekly-summary')->weeklyOn(1, '07:00');
