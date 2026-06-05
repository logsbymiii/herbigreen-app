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
    protected int | string | array $columnSpan = 1;

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
                        '#4EA674', // Ocean Green (Hadir)
                        '#F0D411', // Pending Yellow (Izin)
                        '#EF4343', // Error Red (Belum Lapor)
                    ],
                    'hoverOffset' => 4,
                    'borderWidth' => 0,
                    'hoverBorderWidth' => 0,
                    'borderColor' => 'transparent',
                    'hoverBorderColor' => 'transparent',
                ],
            ],
            'labels' => ['Hadir/Lapor', 'Izin/Sakit', 'Belum Lapor'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
