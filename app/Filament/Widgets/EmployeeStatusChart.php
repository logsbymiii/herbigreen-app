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
    protected string $view = 'filament.widgets.chart-with-actions';
    protected static ?int $sort = 2;
    protected ?string $heading = 'Status Laporan Hari Ini';
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

        $belumLapor = max(0, $totalKaryawanAktif - $laporanMasuk);

        return [
            'datasets' => [
                [
                    'label' => 'Karyawan',
                    'data' => [$laporanMasuk, $belumLapor],
                    'backgroundColor' => [
                        '#10B981', // Success (Green) for Laporan Masuk
                        '#EF4444', // Danger (Red) for Belum Lapor
                    ],
                    'borderColor' => [
                        '#10B981', 
                        '#EF4444', 
                    ],
                    'borderWidth' => 0,
                    'hoverOffset' => 4
                ],
            ],
            'labels' => ['Sudah Lapor', 'Belum Lapor'],
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
        } else {
            return $query->whereDoesntHave('smartDailyReports', function($q) use ($today) {
                $q->whereDate('report_date', $today);
            })->get();
        }
    }

    public function showDetailsAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('showDetails')
            ->modalHeading(fn (array $arguments) => 'Detail Karyawan: ' . ($arguments['status'] ?? ''))
            ->modalContent(function (array $arguments) {
                $status = $arguments['status'] ?? '';
                return view('filament.widgets.employee-status-details', ['status' => $status, 'chartType' => 'report']);
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup');
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
