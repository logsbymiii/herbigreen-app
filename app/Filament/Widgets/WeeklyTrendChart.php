<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class WeeklyTrendChart extends ChartWidget
{
    protected ?string $heading = 'Tren Laporan Masuk (7 Hari Terakhir)';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full'; // Biar ngebentang luas

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        // Looping mundur dari 6 hari lalu sampai hari ini
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d M'); // Format: "22 May"

            $data[] = Report::whereDate('created_at', $date)->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Laporan',
                    'data' => $data,
                    'borderColor' => '#00C253', // Garis hijau
                    'fill' => false,
                    'tension' => 0.3, // Biar garisnya agak melengkung estetik, nggak kaku
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    // Opsional: Buang angka desimal di sumbu Y
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'ticks' => ['stepSize' => 1],
                ],
            ],
        ];
    }
}
