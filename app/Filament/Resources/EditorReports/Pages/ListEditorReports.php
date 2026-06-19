<?php

namespace App\Filament\Resources\EditorReports\Pages;

use App\Filament\Resources\EditorReports\EditorReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEditorReports extends ListRecords
{
    protected static string $resource = EditorReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}