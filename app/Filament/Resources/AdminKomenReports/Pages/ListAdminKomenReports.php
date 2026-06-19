<?php

namespace App\Filament\Resources\AdminKomenReports\Pages;

use App\Filament\Resources\AdminKomenReports\AdminKomenReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdminKomenReports extends ListRecords
{
    protected static string $resource = AdminKomenReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
