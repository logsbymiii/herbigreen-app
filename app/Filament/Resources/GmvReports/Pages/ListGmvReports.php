<?php

namespace App\Filament\Resources\GmvReports\Pages;

use App\Filament\Resources\GmvReports\GmvReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGmvReports extends ListRecords
{
    protected static string $resource = GmvReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\GmvReports\Widgets\GmvStats::class,
        ];
    }
}
