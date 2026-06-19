<?php

namespace App\Filament\Resources\ContentCreatorReports\Pages;

use App\Filament\Resources\ContentCreatorReports\ContentCreatorReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContentCreatorReport extends EditRecord
{
    protected static string $resource = ContentCreatorReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}