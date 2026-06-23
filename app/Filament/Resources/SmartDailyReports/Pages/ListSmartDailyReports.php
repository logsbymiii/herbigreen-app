<?php

namespace App\Filament\Resources\SmartDailyReports\Pages;

use App\Filament\Resources\SmartDailyReports\SmartDailyReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSmartDailyReports extends ListRecords
{
    protected static string $resource = SmartDailyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }


}
