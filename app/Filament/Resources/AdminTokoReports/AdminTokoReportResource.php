<?php

namespace App\Filament\Resources\AdminTokoReports;

use App\Filament\Resources\AdminTokoReports\Pages\CreateAdminTokoReport;
use App\Filament\Resources\AdminTokoReports\Pages\EditAdminTokoReport;
use App\Filament\Resources\AdminTokoReports\Pages\ListAdminTokoReports;
use App\Filament\Resources\SmartDailyReports\Schemas\SmartDailyReportForm;
use App\Filament\Resources\SmartDailyReports\Tables\SmartDailyReportsTable;
use App\Models\SmartDailyReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AdminTokoReportResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = SmartDailyReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $navigationLabel = 'Laporan Toko';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan & Analitik';

    protected static ?int $navigationSort = 3;

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): string
    {
        if ($record && $record->employee) {
            return "Admin Toko {$record->employee->name}";
        }
        return 'Laporan Admin Toko';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('employee.division', function ($query) {
            $query->whereIn('name', ['Admin Toko', 'Head & Admin Toko']);
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
            'index' => ListAdminTokoReports::route('/'),
            'create' => CreateAdminTokoReport::route('/create'),
            'edit' => EditAdminTokoReport::route('/{record}/edit'),
        ];
    }
}