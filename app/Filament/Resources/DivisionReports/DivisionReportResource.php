<?php

namespace App\Filament\Resources\DivisionReports;

use App\Filament\Resources\DivisionReports\Pages\CreateDivisionReport;
use App\Filament\Resources\DivisionReports\Pages\EditDivisionReport;
use App\Filament\Resources\DivisionReports\Pages\ListDivisionReports;
use App\Filament\Resources\SmartDailyReports\Schemas\SmartDailyReportForm;
use App\Filament\Resources\SmartDailyReports\Tables\SmartDailyReportsTable;
use App\Models\SmartDailyReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DivisionReportResource extends Resource
{
    protected static ?string $model = SmartDailyReport::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationLabel = 'Laporan Per Divisi';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan & Analitik';

    protected static ?int $navigationSort = 2;

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): string
    {
        if ($record && $record->employee) {
            return "Laporan {$record->employee->name}";
        }
        return 'Laporan Divisi';
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDivisionReports::route('/'),
            'create' => CreateDivisionReport::route('/create'),
            'edit' => EditDivisionReport::route('/{record}/edit'),
        ];
    }
}
