<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Support\RawJs;
use App\Models\SmartDailyReport;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;

class EmployeeStatusChart extends ChartWidget implements HasActions
{
    use InteractsWithActions;
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
                  ->whereIn('type', ['sakit', 'izin']);
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

    public function getEmployeesByStatus(string $status)
    {
        $today = Carbon::today();
        $query = Employee::where('is_active', true)->with('division');
        
        if ($status === 'Sudah Lapor') {
            return $query->whereHas('smartDailyReports', function($q) use ($today) {
                $q->whereDate('report_date', $today);
            })->get();
        } elseif ($status === 'Izin / Sakit') {
            return $query->whereDoesntHave('smartDailyReports', function($q) use ($today) {
                $q->whereDate('report_date', $today);
            })->whereHas('attendances', function($q) use ($today) {
                $q->whereDate('date', $today)->whereIn('type', ['sakit', 'izin']);
            })->get();
        } else {
            return $query->whereDoesntHave('smartDailyReports', function($q) use ($today) {
                $q->whereDate('report_date', $today);
            })->whereDoesntHave('attendances', function($q) use ($today) {
                $q->whereDate('date', $today)->whereIn('type', ['sakit', 'izin']);
            })->get();
        }
    }

    public function showDetailsAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('showDetails')
            ->modalHeading(fn (array $arguments) => 'Detail Karyawan: ' . ($arguments['status'] ?? ''))
            ->modalContent(function (array $arguments) {
                $status = $arguments['status'] ?? '';
                $employees = $this->getEmployeesByStatus($status);
                return view('filament.widgets.employee-status-details', ['employees' => $employees, 'status' => $status]);
            })
            ->modalSubmitAction(false)
            ->modalCancelAction(false);
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
            'onClick' => RawJs::make(<<<JS
                function(event, elements, chart) {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const label = chart.data.labels[index];
                        // Trigger Livewire / Filament action
                        \$wire.mountAction('showDetails', { status: label });
                    }
                }
            JS),
        ];
    }
}
