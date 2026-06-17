<?php

namespace App\Filament\Resources\AffiliateReports\Pages;

use App\Filament\Resources\AffiliateReports\AffiliateReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAffiliateReports extends ListRecords
{
    protected static string $resource = AffiliateReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
