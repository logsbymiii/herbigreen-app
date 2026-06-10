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

        $totalToday = $laporanMasuk + $izinHariIni + $belumLapor;
        $circumference = 188.5;

        $laporanDash = 0;
        $izinDash = 0;
        $belumLaporDash = 0;

        if ($totalToday > 0) {
            $laporanDash = ($laporanMasuk / $totalToday) * $circumference;
            $izinDash = ($izinHariIni / $totalToday) * $circumference;
            $belumLaporDash = ($belumLapor / $totalToday) * $circumference;
        }

        return [
            'totalKaryawan' => $totalKaryawanAktif,
            'laporanMasuk' => $laporanMasuk,
            'izinHariIni' => $izinHariIni,
            'belumLapor' => $belumLapor,
            'recentReports' => $recentReports,
            'laporanDash' => $laporanDash,
            'izinDash' => $izinDash,
            'belumLaporDash' => $belumLaporDash,
            'circumference' => $circumference,
        ];
    }
}
