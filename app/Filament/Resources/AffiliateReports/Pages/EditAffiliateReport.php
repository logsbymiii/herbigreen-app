<?php

namespace App\Filament\Resources\AffiliateReports\Pages;

use App\Filament\Resources\AffiliateReports\AffiliateReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAffiliateReport extends EditRecord
{
    protected static string $resource = AffiliateReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
