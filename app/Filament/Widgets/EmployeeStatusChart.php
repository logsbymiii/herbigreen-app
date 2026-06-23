<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\SmartDailyReport;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;

class EmployeeStatusChart extends ChartWidget
{
    protected static ?int $sort = 2;
    protected ?string $heading = 'Status Karyawan Hari Ini';
    protected ?string $maxHeight = '250px';
    protected int | string | array $columnSpan = [
        'sm' => 'full',
        'md' => 1,
        'xl' => 1,
    ];

    protected function getData(): array
    {
        $today = Carbon::today();

        $totalKaryawanAktif = Employee::where('is_active', true)->count();

        $laporanMasuk = Employee::where('is_active', true)
            ->whereHas('smartDailyReports', function($q) use ($today) {
                $q->whereDate('report_date', $today);
            })->count();

        $izinHariIni = Employee::where('is_active', true)
            ->whereDoesntHave('smartDailyReports', function($q) use ($today) {
                $q->whereDate('report_date', $today);
            })
            ->whereHas('attendances', function($q) use ($today) {
                $q->whereDate('date', $today)
                  ->whereIn('type', ['sakit', 'izin', 'cuti']);
            })->count();
        
        // Sisa karyawan yang belum ada kabar apa-apa
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
                    'borderColor' => [
                        '#10B981', 
                        '#F59E0B', 
                        '#EF4444', 
                    ],
                    'borderWidth' => 0,
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

    protected function getOptions(): array
    {
        return [
            'elements' => [
                'arc' => [
                    'borderWidth' => 0,
                    'borderColor' => 'transparent',
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
        ];
    }
}
