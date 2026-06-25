<?php

namespace App\Filament\Resources\Tests\Pages;

use App\Filament\Resources\Tests\TestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTests extends ManageRecords
{
    protected static string $resource = TestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
