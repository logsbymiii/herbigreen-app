<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SmartDailyReportOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $today = now()->format('Y-m-d');

        $totalReports = \App\Models\SmartDailyReport::whereDate('report_date', $today)->count();

        $crmCount = \App\Models\SmartDailyReport::whereDate('report_date', $today)
            ->whereHas('employee.division', function ($query) {
                $query->where('name', 'Admin CRM');
            })->count();

        $affiliateCount = \App\Models\SmartDailyReport::whereDate('report_date', $today)
            ->whereHas('employee.division', function ($query) {
                $query->where('name', 'Admin Affiliate');
            })->count();

        return [
            Stat::make('Analisis AI Selesai', $totalReports . ' Laporan')
                ->description('Total laporan harian diproses AI')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('primary'),
            Stat::make('Kinerja CRM (Hari Ini)', $crmCount . ' Laporan')
                ->description('Laporan masuk dari tim CRM')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('success'),
            Stat::make('Kinerja Affiliate (Hari Ini)', $affiliateCount . ' Laporan')
                ->description('Laporan masuk dari tim Affiliate')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),
        ];
    }
}
