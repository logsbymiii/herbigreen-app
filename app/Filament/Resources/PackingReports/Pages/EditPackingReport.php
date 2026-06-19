<?php

namespace App\Filament\Resources\PackingReports\Pages;

use App\Filament\Resources\PackingReports\PackingReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPackingReport extends EditRecord
{
    protected static string $resource = PackingReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}