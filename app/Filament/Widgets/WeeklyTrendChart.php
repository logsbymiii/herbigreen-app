<?php

namespace App\Filament\Widgets;

use App\Models\SmartDailyReport;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class WeeklyTrendChart extends ChartWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = [
        'sm' => 'full',
        'md' => 1,
        'xl' => 1,
    ];
    protected ?string $maxHeight = '250px';

    public ?string $filter = 'days';

    protected function getFilters(): ?array
    {
        return [
            'days' => '7 Hari Terakhir',
            'weeks' => '7 Minggu Terakhir',
        ];
    }

    public function getHeading(): string
    {
        return 'Tren Laporan Masuk';
    }

    protected function getData(): array
    {
        $data = [];
        $labels = [];
        $activeFilter = $this->filter;

        if ($activeFilter === 'weeks') {
            for ($i = 6; $i >= 0; $i--) {
                $startOfWeek = Carbon::now()->subWeeks($i)->startOfWeek();
                $endOfWeek = Carbon::now()->subWeeks($i)->endOfWeek();
                $labels[] = 'Mg ' . $startOfWeek->format('W');
                $data[] = SmartDailyReport::whereBetween('report_date', [$startOfWeek, $endOfWeek])->count();
            }
        } else {
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $labels[] = $date->translatedFormat('D');
                $data[] = SmartDailyReport::whereDate('report_date', $date)->count();
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Laporan',
                    'data' => $data,
                    'borderColor' => '#4EA674',
                    'backgroundColor' => 'rgba(78, 166, 116, 0.15)',
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#4EA674',
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'ticks' => ['stepSize' => 1],
                    'grid' => ['display' => true, 'color' => 'rgba(0,0,0,0.04)'],
                ],
                'x' => [
                    'grid' => ['display' => false],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
