<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Employee;
use Carbon\Carbon;

class HeroKpiWidget extends Widget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.hero-kpi-widget';

    protected function getViewData(): array
    {
        $today = Carbon::today();
        
        $totalKaryawanAktif = Employee::where('is_active', true)->count();
        
        $laporanMasukHariIni = Employee::where('is_active', true)
            ->whereHas('smartDailyReports', function($q) use ($today) {
                $q->whereDate('report_date', $today);
            })->count();
            
        $laporanKemarin = Employee::where('is_active', true)
            ->whereHas('smartDailyReports', function($q) use ($today) {
                $q->whereDate('report_date', $today->copy()->subDay());
            })->count();
            
        $trend = 0;
        if ($laporanKemarin > 0) {
            $trend = round((($laporanMasukHariIni - $laporanKemarin) / $laporanKemarin) * 100);
        }

        return [
            'laporanMasuk' => $laporanMasukHariIni,
            'totalKaryawan' => $totalKaryawanAktif,
            'trend' => $trend
        ];
    }
}
