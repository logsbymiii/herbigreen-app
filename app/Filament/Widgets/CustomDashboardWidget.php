<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class CustomDashboardWidget extends Widget
{
    protected string $view = 'filament.widgets.custom-dashboard-widget';

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $today = \Carbon\Carbon::today();

        $laporanMasuk = \App\Models\Report::whereDate('created_at', $today)->count();
        $izinHariIni = \App\Models\Attendance::whereDate('created_at', $today)->count();
        $totalKaryawanAktif = \App\Models\Employee::where('is_active', true)->count();
        $belumLapor = max(0, $totalKaryawanAktif - $laporanMasuk);

        $recentReports = \App\Models\Report::with('employee.division')->latest('created_at')->limit(5)->get();

        return [
            'totalKaryawan' => $totalKaryawanAktif,
            'laporanMasuk' => $laporanMasuk,
            'izinHariIni' => $izinHariIni,
            'belumLapor' => $belumLapor,
            'recentReports' => $recentReports,
        ];
    }
}
