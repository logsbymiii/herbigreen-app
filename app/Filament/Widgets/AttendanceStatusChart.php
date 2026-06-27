<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Support\RawJs;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;

class AttendanceStatusChart extends ChartWidget implements HasActions
{
    use InteractsWithActions;
    protected static string $view = 'filament.widgets.chart-with-actions';
    
    protected static ?int $sort = 2; // Keep it next to EmployeeStatusChart
    protected ?string $heading = 'Kehadiran Karyawan Hari Ini';
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

        // Get actual counts
        $wfh = Attendance::whereDate('date', $today)->where('type', 'wfh')->count();
        $sakit = Attendance::whereDate('date', $today)->where('type', 'sakit')->count();
        $izin = Attendance::whereDate('date', $today)->where('type', 'izin')->count();
        
        // Asumsi sisanya hadir/belum absen, tapi kalau tidak diabsen kita tidak tahu.
        // Jika asusmsinya semua yang tidak WFH/Sakit/Izin harus ke kantor (Hadir).
        // Kita hitung yang absen "hadir" jika ada, jika tidak, kurangi total aktif.
        $hadir = Attendance::whereDate('date', $today)->where('type', 'hadir')->count();
        
        // Yang belum absen sama sekali = Total Karyawan Aktif - (Yang udah ngabsen)
        $belumAbsen = max(0, $totalKaryawanAktif - ($hadir + $wfh + $sakit + $izin));

        return [
            'datasets' => [
                [
                    'label' => 'Kehadiran',
                    'data' => [$hadir, $wfh, $sakit, $izin, $belumAbsen],
                    'backgroundColor' => [
                        '#10B981', // Hadir (Green)
                        '#3B82F6', // WFH (Blue)
                        '#F59E0B', // Sakit (Yellow)
                        '#6366F1', // Izin (Indigo)
                        '#EF4444', // Belum Absen (Red)
                    ],
                    'borderColor' => [
                        '#10B981', 
                        '#3B82F6', 
                        '#F59E0B', 
                        '#6366F1',
                        '#EF4444',
                    ],
                    'borderWidth' => 0,
                    'hoverOffset' => 4
                ],
            ],
            'labels' => ['Hadir', 'WFH', 'Sakit', 'Izin', 'Belum Absen'],
        ];
    }

    public function getEmployeesByStatus(string $status)
    {
        $today = Carbon::today();
        $query = Employee::where('is_active', true)->with('division');
        
        $statusKey = strtolower($status);
        
        if (in_array($statusKey, ['hadir', 'wfh', 'sakit', 'izin'])) {
            return $query->whereHas('attendances', function($q) use ($today, $statusKey) {
                $q->whereDate('date', $today)->where('type', $statusKey);
            })->get();
        } else {
            // Belum Absen
            return $query->whereDoesntHave('attendances', function($q) use ($today) {
                $q->whereDate('date', $today);
            })->get();
        }
    }

    public function showAttendanceDetailsAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('showAttendanceDetails')
            ->modalHeading(fn (array $arguments) => 'Detail Kehadiran: ' . ($arguments['status'] ?? ''))
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
                        \$wire.mountAction('showAttendanceDetails', { status: label });
                    }
                }
            JS),
        ];
    }
}
