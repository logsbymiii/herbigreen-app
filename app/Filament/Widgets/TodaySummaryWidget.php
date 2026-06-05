<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Report;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;

class TodaySummaryWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today = Carbon::today();

        $laporanMasuk = Report::whereDate('created_at', $today)->count();
        $izinHariIni = Attendance::whereDate('created_at', $today)->count();
        $totalKaryawanAktif = Employee::where('is_active', true)->count();
        $belumLapor = max(0, $totalKaryawanAktif - $laporanMasuk);

        return [
            Stat::make('Total Karyawan', $totalKaryawanAktif)
                ->description('Karyawan aktif terdaftar')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Laporan Masuk', $laporanMasuk)
                ->description('Laporan diterima hari ini')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success'),

            Stat::make('Izin / Sakit', $izinHariIni)
                ->description('Absen hari ini')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color('warning'),

            Stat::make('Belum Lapor', $belumLapor)
                ->description('Belum setor laporan')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),
        ];
    }
}
