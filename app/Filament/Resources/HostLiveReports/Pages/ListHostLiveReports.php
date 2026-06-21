<?php

namespace App\Filament\Resources\HostLiveReports\Pages;

use App\Filament\Resources\HostLiveReports\HostLiveReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHostLiveReports extends ListRecords
{
    protected static string $resource = HostLiveReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\HostLiveLeaderboardWidget::class,
            \App\Filament\Widgets\GmvPerHostChart::class,
        ];
    }
}