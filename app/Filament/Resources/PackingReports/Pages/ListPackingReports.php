<?php

namespace App\Filament\Resources\PackingReports\Pages;

use App\Filament\Resources\PackingReports\PackingReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPackingReports extends ListRecords
{
    protected static string $resource = PackingReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}