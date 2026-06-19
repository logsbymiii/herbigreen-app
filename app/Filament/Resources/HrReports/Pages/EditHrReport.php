<?php

namespace App\Filament\Resources\HrReports\Pages;

use App\Filament\Resources\HrReports\HrReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHrReport extends EditRecord
{
    protected static string $resource = HrReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}