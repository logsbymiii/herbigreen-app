<?php

namespace App\Filament\Resources\HrReports;

use App\Filament\Resources\HrReports\Pages\CreateHrReport;
use App\Filament\Resources\HrReports\Pages\EditHrReport;
use App\Filament\Resources\HrReports\Pages\ListHrReports;
use App\Filament\Resources\SmartDailyReports\Schemas\SmartDailyReportForm;
use App\Filament\Resources\SmartDailyReports\Tables\SmartDailyReportsTable;
use App\Models\SmartDailyReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HrReportResource extends Resource
{
    protected static ?string $model = SmartDailyReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $navigationLabel = 'Laporan HR & Brand Manager';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan AI';

    protected static ?int $navigationSort = 13;

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): string
    {
        if ($record && $record->employee) {
            return "HR {$record->employee->name}";
        }
        return 'Laporan HR';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('employee.division', function ($query) {
            $query->where('name', 'HR & Brand Manager');
        });
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
            'index' => ListHrReports::route('/'),
            'create' => CreateHrReport::route('/create'),
            'edit' => EditHrReport::route('/{record}/edit'),
        ];
    }
}