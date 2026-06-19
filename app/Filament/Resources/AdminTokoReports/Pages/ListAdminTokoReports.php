<?php

namespace App\Filament\Resources\AdminTokoReports\Pages;

use App\Filament\Resources\AdminTokoReports\AdminTokoReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdminTokoReports extends ListRecords
{
    protected static string $resource = AdminTokoReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}