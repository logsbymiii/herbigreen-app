<?php

namespace App\Filament\Widgets;

use App\Models\GmvReport;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GmvPerHostChart extends ChartWidget
{
   protected ?string $heading = 'Top 5 GMV Host (Minggu Ini)';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;
    protected ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();

        // Ambil data GMV, jumlahkan per karyawan (Host), urutkan dari yang terbesar
        $reports = GmvReport::with('employee')
            ->where('created_at', '>=', $startOfWeek)
            ->select('employee_id', DB::raw('SUM(gmv_amount) as total_gmv'))
            ->groupBy('employee_id')
            ->orderByDesc('total_gmv')
            ->take(5) // Ambil 5 terbaik aja
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total GMV (Rp)',
                    'data' => $reports->pluck('total_gmv')->toArray(),
                    'backgroundColor' => '#023337',
                    'borderRadius' => 4,
                    'borderWidth' => 0,
                    'hoverBorderWidth' => 0,
                    'borderColor' => 'transparent',
                    'hoverBorderColor' => 'transparent',
                ],
            ],
            'labels' => $reports->map(function($report) {
                return $report->employee->name ?? 'Tanpa Nama';
            })->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
