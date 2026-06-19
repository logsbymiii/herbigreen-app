<?php

namespace App\Filament\Resources\HostLiveReports\Pages;

use App\Filament\Resources\HostLiveReports\HostLiveReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHostLiveReport extends EditRecord
{
    protected static string $resource = HostLiveReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}