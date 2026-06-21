<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class AttendanceMatrixWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.attendance-matrix-widget';
    
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;

    public function getViewData(): array
    {
        $daysInMonth = now()->daysInMonth;
        $month = now()->month;
        $year = now()->year;
        
        $dates = [];
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $dates[] = \Carbon\Carbon::createFromDate($year, $month, $i)->format('Y-m-d');
        }

        $employees = \App\Models\Employee::where('role', '!=', 'admin')->get();
        
        $matrix = [];
        foreach ($employees as $emp) {
            $attendances = \App\Models\Attendance::where('employee_id', $emp->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->get()
                ->keyBy('date');
                
            $row = [];
            foreach ($dates as $date) {
                $type = $attendances->has($date) ? $attendances[$date]->type : '-';
                $row[$date] = $type;
            }
            $matrix[$emp->name] = $row;
        }

        return [
            'dates' => $dates,
            'matrix' => $matrix,
            'monthName' => now()->translatedFormat('F Y'),
        ];
    }
}
