<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class DivisionReportChart extends ChartWidget
{
    protected ?string $heading = 'Laporan per Divisi (7 Hari Terakhir)';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $reports = Report::with('employee')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->get();

       // Kelompokin berdasarkan NAMA divisi
        $data = $reports->groupBy(function($report) {
            // Tambahin ?->name di sini:
            return $report->employee->division?->name ?? 'Tanpa Divisi';
        })->map(function($group) {
            return $group->count();
        });

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Laporan',
                    'data' => $data->values()->toArray(),
                    'backgroundColor' => '#00C253', // Ijo khas Herbigreen
                    'borderRadius' => 4, // Biar ujung barnya agak melengkung dikit, gen Z aesthetic wkwk
                ],
            ],
            'labels' => $data->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
    protected function getOptions(): array
{
    return [
        'scales' => [
            'y' => [
                'ticks' => [
                    'stepSize' => 1, // Maksa angkanya naik per 1 (1, 2, 3, dst)
                ],
            ],
        ],
    ];
}
}
