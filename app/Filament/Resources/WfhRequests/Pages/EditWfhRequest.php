<?php

namespace App\Filament\Resources\WfhRequests\Pages;

use App\Filament\Resources\WfhRequests\WfhRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWfhRequest extends EditRecord
{
    protected static string $resource = WfhRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
