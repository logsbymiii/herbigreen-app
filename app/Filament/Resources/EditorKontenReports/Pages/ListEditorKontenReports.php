<?php

namespace App\Filament\Resources\EditorKontenReports\Pages;

use App\Filament\Resources\EditorKontenReports\EditorKontenReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEditorKontenReports extends ListRecords
{
    protected static string $resource = EditorKontenReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}