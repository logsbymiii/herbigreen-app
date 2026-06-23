<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Report;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;

class EmployeeStatusChart extends ChartWidget
{
    protected static ?int $sort = 1;
    protected static ?string $heading = 'Status Karyawan Hari Ini';
    protected static ?string $maxHeight = '250px';
    protected int | string | array $columnSpan = 1; // You can adjust this to 'full' or '1' depending on layout needs

    protected function getData(): array
    {
        $today = Carbon::today();

        $laporanMasuk = Report::whereDate('created_at', $today)->count();
        $izinHariIni = Attendance::whereDate('created_at', $today)
                                ->whereIn('type', ['sakit', 'izin'])
                                ->count();
        $totalKaryawanAktif = Employee::where('is_active', true)->count();
        
        $belumLapor = max(0, $totalKaryawanAktif - $laporanMasuk - $izinHariIni);

        return [
            'datasets' => [
                [
                    'label' => 'Karyawan',
                    'data' => [$laporanMasuk, $izinHariIni, $belumLapor],
                    'backgroundColor' => [
                        '#10B981', // Success (Green) for Laporan Masuk
                        '#F59E0B', // Warning (Yellow) for Izin/Sakit
                        '#EF4444', // Danger (Red) for Belum Lapor
                    ],
                    'hoverOffset' => 4
                ],
            ],
            'labels' => ['Sudah Lapor', 'Izin / Sakit', 'Belum Lapor'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
