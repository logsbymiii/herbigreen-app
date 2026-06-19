<?php

namespace App\Filament\Resources\SosmedReports\Pages;

use App\Filament\Resources\SosmedReports\SosmedReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSosmedReports extends ListRecords
{
    protected static string $resource = SosmedReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}