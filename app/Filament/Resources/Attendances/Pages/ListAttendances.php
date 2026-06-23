<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Filament\Resources\Attendances\AttendanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

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
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => \Filament\Resources\Components\Tab::make('Semua'),
            'hadir' => \Filament\Resources\Components\Tab::make('Hadir')
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'hadir')),
            'wfh' => \Filament\Resources\Components\Tab::make('WFH')
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'wfh')),
            'sakit' => \Filament\Resources\Components\Tab::make('Sakit')
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'sakit')),
            'izin' => \Filament\Resources\Components\Tab::make('Izin')
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'izin')),
            'cuti' => \Filament\Resources\Components\Tab::make('Cuti')
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'cuti')),
        ];
    }
}
