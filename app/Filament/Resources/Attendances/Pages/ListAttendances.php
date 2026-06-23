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
            'semua' => \Filament\Schemas\Components\Tabs\Tab::make('Semua'),
            'hadir' => \Filament\Schemas\Components\Tabs\Tab::make('Hadir')
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'hadir')),
            'wfh' => \Filament\Schemas\Components\Tabs\Tab::make('WFH')
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'wfh')),
            'sakit' => \Filament\Schemas\Components\Tabs\Tab::make('Sakit')
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'sakit')),
            'izin' => \Filament\Schemas\Components\Tabs\Tab::make('Izin')
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'izin')),
            'cuti' => \Filament\Schemas\Components\Tabs\Tab::make('Cuti')
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'cuti')),
        ];
    }
}
