<?php

namespace App\Filament\Resources\CrmReports\Pages;

use App\Filament\Resources\CrmReports\CrmReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCrmReports extends ListRecords
{
    protected static string $resource = CrmReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
