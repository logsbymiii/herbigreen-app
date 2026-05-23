<?php

namespace App\Filament\Resources\GmvReports;

use App\Filament\Resources\GmvReports\Pages\CreateGmvReport;
use App\Filament\Resources\GmvReports\Pages\EditGmvReport;
use App\Filament\Resources\GmvReports\Pages\ListGmvReports;
use App\Filament\Resources\GmvReports\Schemas\GmvReportForm;
use App\Filament\Resources\GmvReports\Tables\GmvReportsTable; // <-- Ini wajib biar file Table lu kepake!
use App\Models\GmvReport;
use App\Models\GmvReports;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GmvReportResource extends Resource
{
    protected static ?string $model = GmvReports::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return GmvReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        // Nah, di sini kita cukup manggil file GmvReportsTable lu yang udah bener!
        return GmvReportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGmvReports::route('/'),
            'create' => CreateGmvReport::route('/create'),
            'edit' => EditGmvReport::route('/{record}/edit'),
        ];
    }
}
