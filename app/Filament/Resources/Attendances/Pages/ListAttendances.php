<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Filament\Resources\Attendances\AttendanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\On;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\AttendanceCalendarWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \pxlrbt\FilamentExcel\Actions\Pages\ExportAction::make()
                ->label('Export Excel')
                ->color('success')
                ->icon('heroicon-o-document-arrow-down')
                ->exports([
                    \pxlrbt\FilamentExcel\Exports\ExcelExport::make()
                        ->fromTable()
                        ->except(['No'])
                        ->withFilename('Export_Presensi_' . date('Y-m-d'))
                ]),
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => \Filament\Schemas\Components\Tabs\Tab::make('Semua'),
            'hadir' => \Filament\Schemas\Components\Tabs\Tab::make('Hadir')
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'hadir')),
            'wfh' => \Filament\Schemas\Components\Tabs\Tab::make('WFH')
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'wfh')),
            'sakit' => \Filament\Schemas\Components\Tabs\Tab::make('Sakit')
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'sakit')),
            'izin' => \Filament\Schemas\Components\Tabs\Tab::make('Izin')
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'izin')),
        ];
    }

    #[On('filterByDate')]
    public function handleFilterByDate($date)
    {
        $this->tableFilters['date']['date_from'] = $date;
        $this->tableFilters['date']['date_until'] = $date;
        $this->resetTable();
    }
}
