<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\AttendanceMatrixWidget;
use App\Filament\Widgets\TodayAttendanceWidget;

class AbsensiDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static string|\UnitEnum|null $navigationGroup = 'HR & Karyawan';
    
    protected static ?string $navigationLabel = 'Dashboard Absensi';
    
    protected static ?string $title = 'Dashboard Absensi Karyawan';

    protected string $view = 'filament.pages.absensi-dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            TodayAttendanceWidget::class,
            AttendanceMatrixWidget::class,
        ];
    }
}
