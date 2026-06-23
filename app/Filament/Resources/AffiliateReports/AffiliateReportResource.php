<?php

namespace App\Filament\Resources\AffiliateReports;

use App\Filament\Resources\AffiliateReports\Pages\CreateAffiliateReport;
use App\Filament\Resources\AffiliateReports\Pages\EditAffiliateReport;
use App\Filament\Resources\AffiliateReports\Pages\ListAffiliateReports;
use App\Filament\Resources\SmartDailyReports\Schemas\SmartDailyReportForm;
use App\Filament\Resources\SmartDailyReports\Tables\SmartDailyReportsTable;
use App\Models\SmartDailyReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AffiliateReportResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = SmartDailyReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Laporan Affiliate';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan & Analitik';

    protected static ?int $navigationSort = 4;

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): string
    {
        if ($record && $record->employee) {
            return "Affiliate {$record->employee->name}";
        }
        return 'Laporan Affiliate';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('employee.division', function ($query) {
            $query->where('name', 'Admin Affiliate');
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
            'index' => ListAffiliateReports::route('/'),
            'create' => CreateAffiliateReport::route('/create'),
            'edit' => EditAffiliateReport::route('/{record}/edit'),
        ];
    }
}
