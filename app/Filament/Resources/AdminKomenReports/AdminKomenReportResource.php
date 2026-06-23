<?php

namespace App\Filament\Resources\AdminKomenReports;

use App\Filament\Resources\AdminKomenReports\Pages\CreateAdminKomenReport;
use App\Filament\Resources\AdminKomenReports\Pages\EditAdminKomenReport;
use App\Filament\Resources\AdminKomenReports\Pages\ListAdminKomenReports;
use App\Filament\Resources\SmartDailyReports\Schemas\SmartDailyReportForm;
use App\Filament\Resources\SmartDailyReports\Tables\SmartDailyReportsTable;
use App\Models\SmartDailyReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AdminKomenReportResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = SmartDailyReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Laporan Admin Komen';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan & Analitik';

    protected static ?int $navigationSort = 11;

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): string
    {
        if ($record && $record->employee) {
            return "Admin Komen {$record->employee->name}";
        }
        return 'Laporan Admin Komen';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('employee.division', function ($query) {
            $query->where('name', 'Admin Komen');
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminKomenReports::route('/'),
            'create' => CreateAdminKomenReport::route('/create'),
            'edit' => EditAdminKomenReport::route('/{record}/edit'),
        ];
    }
}
