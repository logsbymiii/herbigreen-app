<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use App\Models\Attendance;
use App\Models\Employee;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class DailyRatioChart extends ChartWidget
{
    protected ?string $heading = 'Rasio Kehadiran Hari Ini';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $today = Carbon::today();

        $laporanMasuk = Report::whereDate('created_at', $today)->count();
        $izin = Attendance::whereDate('created_at', $today)->count();

        $totalAktif = Employee::where('is_active', true)->count();
        $belumLapor = max(0, $totalAktif - $laporanMasuk - $izin);

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Karyawan',
                    'data' => [$laporanMasuk, $izin, $belumLapor],
                    'backgroundColor' => [
                        '#00C253', // Hijau Herbigreen (Hadir)
                        '#F59E0B', // Kuning/Orange (Izin)
                        '#EF4444', // Merah (Belum Lapor)
                    ],
                    'hoverOffset' => 4,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => ['Hadir/Lapor', 'Izin/Sakit', 'Belum Lapor'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
