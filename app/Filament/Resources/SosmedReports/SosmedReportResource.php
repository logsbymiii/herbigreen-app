<?php

namespace App\Filament\Resources\SosmedReports;

use App\Filament\Resources\SosmedReports\Pages\CreateSosmedReport;
use App\Filament\Resources\SosmedReports\Pages\EditSosmedReport;
use App\Filament\Resources\SosmedReports\Pages\ListSosmedReports;
use App\Filament\Resources\SmartDailyReports\Schemas\SmartDailyReportForm;
use App\Filament\Resources\SmartDailyReports\Tables\SmartDailyReportsTable;
use App\Models\SmartDailyReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SosmedReportResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = SmartDailyReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShare;

    protected static ?string $navigationLabel = 'Laporan Sosial Media';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan & Analitik';

    protected static ?int $navigationSort = 6;

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): string
    {
        if ($record && $record->employee) {
            return "Sosmed {$record->employee->name}";
        }
        return 'Laporan Sosmed';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('employee.division', function ($query) {
            $query->where('name', 'Admin Sosial Media');
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
            'index' => ListSosmedReports::route('/'),
            'create' => CreateSosmedReport::route('/create'),
            'edit' => EditSosmedReport::route('/{record}/edit'),
        ];
    }
}