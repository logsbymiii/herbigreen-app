<?php

namespace App\Filament\Resources\SmartDailyReports\Pages;

use App\Filament\Resources\SmartDailyReports\SmartDailyReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSmartDailyReport extends EditRecord
{
    protected static string $resource = SmartDailyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
