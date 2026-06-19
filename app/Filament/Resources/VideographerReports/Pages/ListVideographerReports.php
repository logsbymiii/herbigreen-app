<?php

namespace App\Filament\Resources\VideographerReports\Pages;

use App\Filament\Resources\VideographerReports\VideographerReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVideographerReports extends ListRecords
{
    protected static string $resource = VideographerReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}