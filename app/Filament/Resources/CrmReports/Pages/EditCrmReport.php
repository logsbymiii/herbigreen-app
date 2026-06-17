<?php

namespace App\Filament\Resources\CrmReports\Pages;

use App\Filament\Resources\CrmReports\CrmReportResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCrmReport extends EditRecord
{
    protected static string $resource = CrmReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
