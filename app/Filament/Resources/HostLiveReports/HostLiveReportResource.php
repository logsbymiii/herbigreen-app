<?php

namespace App\Filament\Resources\HostLiveReports;

use App\Filament\Resources\HostLiveReports\Pages\CreateHostLiveReport;
use App\Filament\Resources\HostLiveReports\Pages\EditHostLiveReport;
use App\Filament\Resources\HostLiveReports\Pages\ListHostLiveReports;
use App\Filament\Resources\SmartDailyReports\Schemas\SmartDailyReportForm;
use App\Filament\Resources\SmartDailyReports\Tables\SmartDailyReportsTable;
use App\Models\SmartDailyReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HostLiveReportResource extends Resource
{
    protected static ?string $model = SmartDailyReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    protected static ?string $navigationLabel = 'Laporan Host Live';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan AI';

    protected static ?int $navigationSort = 9;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('employee.division', function ($query) {
            $query->where('name', 'Host Live');
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
            'index' => ListHostLiveReports::route('/'),
            'create' => CreateHostLiveReport::route('/create'),
            'edit' => EditHostLiveReport::route('/{record}/edit'),
        ];
    }
}