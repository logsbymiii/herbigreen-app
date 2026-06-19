<?php

namespace App\Filament\Resources\PackingReports\Pages;

use App\Filament\Resources\PackingReports\PackingReportResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePackingReport extends CreateRecord
{
    protected static string $resource = PackingReportResource::class;
}