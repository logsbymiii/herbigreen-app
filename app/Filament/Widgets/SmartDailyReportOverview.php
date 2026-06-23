<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SmartDailyReportOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $today = \Carbon\Carbon::today();

        $totalReports = \App\Models\SmartDailyReport::whereDate('report_date', $today)->count();

        return [
            Stat::make('Analisis AI Selesai', $totalReports . ' Laporan')
                ->description('Total laporan harian diproses AI ✨')
                ->color('success')
                ->icon('heroicon-o-sparkles'),
        ];
    }
}
