<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\ExportAction;
use App\Filament\Exports\AttendanceExporter;

class AttendanceCalendarWidget extends Widget implements HasActions
{
    use InteractsWithActions;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.attendance-calendar-widget';

    protected int | string | array $columnSpan = 'full';

    public $currentMonth;
    public $currentYear;
    public $calendarData = [];

    public function mount()
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
        $this->generateCalendar();
    }

    public function previousMonth()
    {
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->generateCalendar();
    }

    public function nextMonth()
    {
        // Cegah masuk ke masa depan
        if ($this->currentMonth == now()->month && $this->currentYear == now()->year) {
            return;
        }
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->generateCalendar();
    }

    public function generateCalendar()
    {
        $startOfMonth = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        
        $attendances = Attendance::whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])->get();

        $this->calendarData = [];
        
        $currentDate = $startOfMonth->copy();
        while ($currentDate <= $endOfMonth) {
            $dateString = $currentDate->format('Y-m-d');
            $dayAttendances = $attendances->where('date', $dateString);
            
            $status = 'none';
            if ($dayAttendances->count() > 0) {
                if ($dayAttendances->whereIn('type', ['sakit', 'izin'])->count() > 0) {
                    $status = 'warning'; // Ada izin/sakit
                } else {
                    $status = 'success'; // Lengkap hadir
                }
            }

            $this->calendarData[] = [
                'date' => $currentDate->day,
                'dayOfWeek' => $currentDate->dayOfWeek,
                'status' => $status,
                'isToday' => $currentDate->isToday(),
            ];
            $currentDate->addDay();
        }
    }
    #[\Livewire\Attributes\Computed]
    public function exportAction(): \Filament\Actions\Action
    {
        return ExportAction::make('export')
            ->exporter(AttendanceExporter::class)
            ->label('Ekspor Spreadsheet')
            ->color('success')
            ->icon('heroicon-o-document-arrow-down');
    }
}
