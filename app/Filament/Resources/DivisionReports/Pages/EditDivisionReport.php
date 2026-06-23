<?php

namespace App\Filament\Resources\DivisionReports\Pages;

use App\Filament\Resources\DivisionReports\DivisionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDivisionReport extends EditRecord
{
    protected static string $resource = DivisionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
