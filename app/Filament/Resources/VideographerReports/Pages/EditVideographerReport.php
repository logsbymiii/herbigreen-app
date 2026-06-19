<?php

namespace App\Filament\Resources\VideographerReports\Pages;

use App\Filament\Resources\VideographerReports\VideographerReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVideographerReport extends EditRecord
{
    protected static string $resource = VideographerReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}