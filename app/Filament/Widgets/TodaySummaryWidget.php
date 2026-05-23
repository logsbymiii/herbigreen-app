<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Report;
use App\Models\Attendance;
use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stats;
use Carbon\Carbon;


class TodaySummaryWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();

        // 1. Hitung laporan masuk hari ini
        $laporanMasuk = Report::whereDate('created_at', $today)->count();

        // 2. Hitung karyawan absen/izin hari ini (sesuaikan kolom 'created_at' atau 'date' di DB lu)
        $izinHariIni = Attendance::whereDate('created_at', $today)->count();

        // 3. Hitung yang belum lapor (Total Karyawan Aktif - Laporan Masuk)
        $totalKaryawanAktif = Employee::where('is_active', true)->count();
        $belumLapor = $totalKaryawanAktif - $laporanMasuk;

        return [
            Stat::make('Laporan Masuk Hari Ini', $laporanMasuk)
                ->description('Total laporan diterima sistem')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success'),

            Stat::make('Karyawan Izin/Sakit', $izinHariIni)
                ->description('Total absen hari ini')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color('warning'),

            Stat::make('Belum Lapor', $belumLapor > 0 ? $belumLapor : 0)
                ->description('Karyawan aktif yang belum setor')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),
        ];
    }
}
