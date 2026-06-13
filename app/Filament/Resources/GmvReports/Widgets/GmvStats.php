<?php

namespace App\Filament\Resources\GmvReports\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GmvStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $todayGmv = \App\Models\GmvReport::whereDate('created_at', now()->format('Y-m-d'))->sum('gmv_amount');
        $monthGmv = \App\Models\GmvReport::whereMonth('created_at', now()->format('m'))
                        ->whereYear('created_at', now()->format('Y'))
                        ->sum('gmv_amount');
        
        $totalOrders = \App\Models\GmvReport::whereMonth('created_at', now()->format('m'))
                        ->whereYear('created_at', now()->format('Y'))
                        ->sum('order_count');

        return [
            Stat::make('Omset Hari Ini', 'Rp ' . number_format($todayGmv, 0, ',', '.'))
                ->description('Total GMV dari semua platform hari ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Omset Bulan Ini', 'Rp ' . number_format($monthGmv, 0, ',', '.'))
                ->description('Total GMV bulan ' . now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
            Stat::make('Total Pesanan (Bulan Ini)', number_format($totalOrders, 0, ',', '.') . ' Pesanan')
                ->description('Jumlah paket bulan ini')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('warning'),
        ];
    }
}
