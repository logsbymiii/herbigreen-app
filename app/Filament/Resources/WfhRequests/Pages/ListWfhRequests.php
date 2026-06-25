<?php

namespace App\Filament\Resources\WfhRequests\Pages;

use App\Filament\Resources\WfhRequests\WfhRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWfhRequests extends ListRecords
{
    protected static string $resource = WfhRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
