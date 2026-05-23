<?php

namespace App\Filament\Resources\GmvReports\Pages;

use App\Filament\Resources\GmvReports\GmvReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGmvReport extends EditRecord
{
    protected static string $resource = GmvReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
