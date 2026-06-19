<?php

namespace App\Filament\Resources\AdminKomenReports\Pages;

use App\Filament\Resources\AdminKomenReports\AdminKomenReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdminKomenReport extends EditRecord
{
    protected static string $resource = AdminKomenReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
