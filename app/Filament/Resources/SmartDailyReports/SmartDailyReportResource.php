<?php

namespace App\Filament\Resources\SmartDailyReports;

use App\Filament\Resources\SmartDailyReports\Pages\CreateSmartDailyReport;
use App\Filament\Resources\SmartDailyReports\Pages\EditSmartDailyReport;
use App\Filament\Resources\SmartDailyReports\Pages\ListSmartDailyReports;
use App\Filament\Resources\SmartDailyReports\Schemas\SmartDailyReportForm;
use App\Filament\Resources\SmartDailyReports\Tables\SmartDailyReportsTable;
use App\Models\SmartDailyReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SmartDailyReportResource extends Resource
{
    protected static ?string $model = SmartDailyReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'Semua Laporan';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan AI';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return SmartDailyReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SmartDailyReportsTable::configure($table);
    }



    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\SmartDailyReportOverview::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmartDailyReports::route('/'),
            'create' => CreateSmartDailyReport::route('/create'),
            'edit' => EditSmartDailyReport::route('/{record}/edit'),
        ];
    }
}
