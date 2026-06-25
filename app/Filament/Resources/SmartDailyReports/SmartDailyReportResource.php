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

    protected static ?string $modelLabel = 'Laporan Kinerja Harian';
    protected static ?string $pluralModelLabel = 'Laporan Kinerja Harian';
    protected static ?string $navigationGroup = 'Laporan Kinerja';
    protected static ?string $navigationLabel = 'Laporan Kinerja Harian';
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): string
    {
        if ($record && $record->employee) {
            return "Laporan {$record->employee->name}";
        }
        return 'Laporan';
    }
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



    public static function getPages(): array
    {
        return [
            'index' => ListSmartDailyReports::route('/'),
            'create' => CreateSmartDailyReport::route('/create'),
            'edit' => EditSmartDailyReport::route('/{record}/edit'),
        ];
    }
}
